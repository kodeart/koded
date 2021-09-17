<?php declare(strict_types=1);

namespace Koded\Framework;

use Koded\DIContainer;
use Koded\Framework\Middleware\{CallableMiddleware, CorsMiddleware, GzipMiddleware};
use Koded\Http\Interfaces\{HttpStatus, Request, Response};
use Koded\Stdlib\Configuration;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};
use Psr\Log\LoggerInterface;
use Whoops\Handler\{JsonResponseHandler, PrettyPageHandler};
use Whoops\Run as WoopsRunner;

(new WoopsRunner)
    ->prependHandler(new PrettyPageHandler)
    ->prependHandler(new JsonResponseHandler)
    ->register();

class App implements RequestHandlerInterface
{
    private int $offset = 0;
    /** @var array<int, MiddlewareInterface> */
    private array $stack = [];
    private array $explicit = [];
    /** @var array<string, callable|null> */
    private array $handlers = [];
    private DIContainer $container;
    private mixed $responder;

    public function __construct(
        array $modules = [],
        Configuration|string $config = '',
        private mixed $renderer = 'start_response',
        private array $middleware = [])
    {
        \date_default_timezone_set('UTC');
        $this->container = new DIContainer(new Module($config), ...$modules);
        $this->middleware = [new GzipMiddleware, CorsMiddleware::class, ...$middleware];
        $this->withErrorHandler(HTTPError::class, 'static::httpErrorHandler');
        $this->withErrorHandler(\Exception::class, 'static::phpErrorHandler');
        $this->withErrorHandler(\Error::class, 'static::phpErrorHandler');
    }

    public function __invoke(): mixed
    {
        try {
            $request = $this->container->new(ServerRequestInterface::class)
                ->withAttribute('@media', $this->container->get(Configuration::class)->get('media'));

            $this->responder = $this->getResponder($request, $uriTemplate);
            $this->initialize($uriTemplate);
            $response = $this->handle($request);
        } catch (\Throwable $exception) {
            // [NOTE]: On exception, the state of the immutable request/response
            //  objects are not updated through the middleware "request phase",
            //  therefore the object attributes (and other properties) are lost
            $response = $this->container->get(ResponseInterface::class);
            if (false === $this->handleException($request, $response, $exception)) {
                throw $exception;
            }
        }
        // Share the response object for (custom) renderers
        $this->container->share($response);
        // [OPTIMIZATION]: Consider optimizing this
        $this->container->bind(Response::class, $response::class);
        return ($this->container)($this->renderer);
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @internal
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (isset($this->stack[$this->offset])) {
            //\error_log('[mw:' . $this->offset . ']> ' . \get_debug_type($this->stack[$this->offset]));
            return $this->stack[$this->offset]->process($request, $this->next());
        }
        $this->container->share($request);
        return ($this->container)($this->responder);
    }

    /**
     * Create a route for URI.
     *
     * @param string                            $uriTemplate The URI template
     * @param object|string                     $resource    A PHP callable
     * @param array<MiddlewareInterface|string> $middleware  [optional] List of middlewares for this route
     * @param bool                              $explicit    [optional] If TRUE replace the global middlewares
     * @return self
     */
    public function route(
        string $uriTemplate,
        object|string $resource,
        array $middleware = [],
        bool $explicit = false): App
    {
        $this->container->get(Router::class)->route($uriTemplate, $resource);
        $this->explicit[$uriTemplate] = [$explicit, $middleware];
        return $this;
    }

    /**
     * Group multiple routes (adds prefix to all).
     * See App::route() method.
     *
     * @param string $prefix URI prefix for all routes in this group
     * @param array $routes A list of routes (@see App::route())
     * @param array<int, MiddlewareInterface|string> $middleware Additional middleware for all routes
     * @return self
     */
    public function group(
        string $prefix,
        array $routes,
        array $middleware = []): App
    {
        /** @var array{template: string, resource: object|string, middleware: array, explicit: bool} $route */
        foreach ($routes as $route) {
            // (template, resource, middleware, explicit)
            $route += ['', '', [], false];
            [$uriTemplate, $resource, $mw, $explicit] = $route;
            $this->route($prefix . $uriTemplate, $resource, \array_merge($middleware, $mw), $explicit);
        }
        return $this;
    }

    public function withErrorHandler(string $type, callable|null $handler): App
    {
        if (false === \is_a($type, \Throwable::class, true)) {
            throw new \TypeError('"type" must be an exception type', HttpStatus::CONFLICT);
        }
        if (null === $handler && false === \method_exists($type, 'handle')) {
            throw new \TypeError('Error handler must either be specified explicitly,' .
                                 ' or defined as a static method named "handle" that is a member of' .
                                 ' the given exception type', HttpStatus::NOT_IMPLEMENTED);
        }
        $this->handlers[$type] = $handler;
        return $this;
    }

    public function withoutErrorHandler(string $type): App
    {
        unset($this->handlers[$type]);
        return $this;
    }

    public function withErrorSerializer(callable $serializer): App
    {
        $this->container->named('$errorSerializer', $serializer);
        return $this;
    }

    private function next(): RequestHandlerInterface
    {
        $self = clone $this;
        $self->offset++;
        return $self;
    }

    private function initialize(?string $uriTemplate): void
    {
        $this->offset = 0;
        $this->stack = [];
        if (empty($uriTemplate)) {
            // Always support CORS requests
            $this->explicit[$uriTemplate] = [true, [CorsMiddleware::class]];
        }
        [$explicit, $middleware] = $this->explicit[$uriTemplate] + [true];
        $this->middleware = false === $explicit ? [...$this->middleware, ...$middleware] : $middleware;
        foreach ($this->middleware as $middleware) {
            $class = 'string' === \get_debug_type($middleware) ? $middleware : \get_class($middleware);
            $this->stack[$class] = match (true) {
                $middleware instanceof MiddlewareInterface => $middleware,
                \is_a($middleware, MiddlewareInterface::class, true) => $this->container->new($middleware),
                \is_callable($middleware) => new CallableMiddleware($middleware),
                default => throw new \InvalidArgumentException(
                    \sprintf('Middleware "%s" must implement %s', $class, MiddlewareInterface::class)
                )
            };
        }
        $this->stack = \array_values($this->stack);
    }

    private function getResponder(
        ServerRequestInterface &$request,
        string|null &$uriTemplate): callable
    {
        $path = \rawurldecode($request->getUri()->getPath());
        $match = $this->container->get(Router::class)->match($path);
        $uriTemplate = $match['template'] ?? null;
        $resource = $match['resource'] ?? null;
        $allowed = \array_keys(map_http_methods($resource ?? new \stdClass));
        $request = $request->withAttribute('@http_methods', $allowed);
        foreach ($match['params'] ?? [] as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }
        $this->container->get(LoggerInterface::class)->debug('> {method} {dir}', [
            'method' => $request->getMethod(),
            'dir' => $path
        ]);
        if (Request::OPTIONS === $method = $request->getMethod()) {
            return create_options_response($allowed);
        }
        if (empty($resource)) {
            return path_not_found($path);
        }
        if (Request::HEAD === $method) {
            return head_response((string)$request->getUri(), $allowed);
        }
        if ($resource instanceof \Closure || \function_exists($resource)) {
            return $resource;
        }
        $responder = $this->container->new($resource);
        if (false === \method_exists($responder, $method)) {
            return method_not_allowed($allowed);
        }
        return [$responder, $method];
    }

    private function handleException(
        ServerRequestInterface $request,
        ResponseInterface &$response,
        \Throwable $ex): bool
    {
        if (!$handler = $this->findErrorHandler($ex)) {
            return false;
        }
        try {
            \call_user_func_array($handler, [$request, &$response, $ex]);
        } catch (HTTPError $error) {
            $this->composeErrorResponse($request, $response, $error);
        }
        return true;
    }

    private function findErrorHandler($ex): callable|null
    {
        $parents = [\get_debug_type($ex)];
        // Iterate the class inheritance chain
        (function($class) use (&$parents) {
            while ($class = \get_parent_class($class)) {
                $parents[] = $class;
            }
        })($ex);
        // Find the parent that matches the exception type
        foreach ($parents as $parent) {
            if (isset($this->handlers[$parent]) && \is_a($ex, $parent, true)) {
                return $this->handlers[$parent];
            }
        }
        return null;
    }

    private function phpErrorHandler(
        ServerRequestInterface $request,
        ResponseInterface &$response,
        \Throwable $ex): void
    {
        \error_log(\sprintf("[%s] %s\n%s",
                            $title = \get_debug_type($ex),
                            $ex->getMessage(),
                            $ex->getTraceAsString()));

        $this->composeErrorResponse(
            $request,
            $response,
            new HTTPError(($ex->getCode() < 100 || $ex->getCode() > 599) ? HttpStatus::CONFLICT : $ex->getCode(),
                title:    $title,
                detail:   $ex->getMessage(),
                previous: $ex
            ));
    }

    private function httpErrorHandler(
        ServerRequestInterface $request,
        ResponseInterface &$response,
        HTTPError $ex): void
    {
        $this->composeErrorResponse($request, $response, $ex);
    }

    private function composeErrorResponse(
        ServerRequestInterface $request,
        ResponseInterface &$response,
        HTTPError $ex): void
    {
        $response = $response->withStatus($ex->getCode());
        foreach ($ex->getHeaders() as $name => $value) {
            $response = $response->withHeader($name, $value);
        }
        try {
            $response = \call_user_func_array(
                $this->container->get('$errorSerializer'),
                [$request, &$response, $ex]
            );
        } catch (\Throwable) {
            // Fallback if error handler does not exist
            $response = default_serialize_error($request, $response, $ex);
        }
    }
}
