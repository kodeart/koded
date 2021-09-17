<?php declare(strict_types=1);

namespace Koded\Framework;

use Koded\Stdlib\UUID;
use Psr\SimpleCache\CacheInterface;
use function Koded\Stdlib\{json_serialize, json_unserialize};

class Router
{
    private const INDEX = 'router.index';

    private bool $cached;
    private array $index;
    private array $identity = [];
    private array $callback = [];

    public function __construct(private CacheInterface $cache)
    {
        $this->index = $cache->get(self::INDEX, []);
        $this->cached = $cache->has(self::INDEX) && $this->index;
    }

    public function __destruct()
    {
        // Saves the routes index in the cache
        if (false === $this->cached && $this->index) {
            $this->cached = !$this->cache->set(self::INDEX, $this->index);
        }
    }

    public function route(string $template, object|string $resource): void
    {
        \assert('/' === $template[0], 'URI template must begin with "/"');
        \assert(false === \str_contains($template, '//'), 'URI template has duplicate slashes');

        $id = $this->id($template);
        if ($this->index[$id] ?? false) {
            if (empty($this->index[$id]['resource'])) {
                $this->callback[$id] = ['resource' => $resource] + $this->index[$id];
            }
            return;
        }
        $this->cache->set($id, $this->compileRoute($template, $resource, $id));
    }

    public function match(string $path): array
    {
        $id = $this->id($path);
        if ($route = $this->callback[$id] ?? $this->index[$id] ?? false) {
            return $route;
        }
        foreach ($this->index as $id => $route) {
            if (\preg_match($route['regex'], $path, $params)) {
                return $this->normalizeParams($this->callback[$id] ?? $route, $params);
            }
        }
        return [];
    }

    private function normalizeParams(array $route, array $params): array
    {
        if (empty($params)) {
            $route['params'] = [];
            return $route;
        }
        $route['params'] = json_unserialize(json_serialize(
            \array_filter($params, 'is_string', ARRAY_FILTER_USE_KEY),
            JSON_NUMERIC_CHECK)
        );
        return $route;
    }

    private function id(string $value): string
    {
        return 'r.' . \crc32($value);
    }

    private function compileRoute(
        string $template,
        object|string $resource,
        string $id): array
    {
        $route = $this->compileTemplate($template) + [
            'resource' => \is_object($resource) ? '' : $resource,
            'template' => $template,
        ];
        $this->index[$id] = $route;
        if (empty($route['resource'])) {
            $this->callback[$id] = ['resource' => $resource] + $route;
        }
        return $route;
    }

    private function compileTemplate(string $template): array
    {
        // Test for direct URI
        if (false === \str_contains($template, '{') &&
            false === \str_contains($template, '<')) {
            $this->identity[$template] = $template;
            return [
                'regex' => "~^$template\$~ui",
                'identity' => $template
            ];
        }
        $options = '';
        $regex = $identity = $template;
        $types = [
            'str' => '.+?', // non-greedy (stop at first match)
            'int' => '\-?\d+',
            'float' => '(\-?\d*\.\d+)',
            'path' => '.+', // greedy
            'regex' => '',
            'uuid' => UUID::PATTERN,
        ];

        // https://regex101.com/r/xeuMU3/2
        preg_match_all('~{((?:[^{}]*|(?R))*)}~mx',
                       $template,
                       $parameters,
                       PREG_SET_ORDER);

        foreach ($parameters as [$parameter, $param]) {
            [$name, $type, $filter] = explode(':', $param, 3) + [1 => 'str', 2 => ''];
            $this->assertSupportedType($template, $types, $type, $filter);
            $regex = \str_replace($parameter, '(?P<' . $name .'>' . ($filter ?: $types[$type]) . ')', $regex);
            $identity = \str_replace($parameter, $types[$type] ? ":$type" : $filter, $identity);
            ('str' === $type || 'path' === $type) && $options = 'ui';
        }
        /*
         * [NOTE]: Replace :path with :str. The concept of "path" is irrelevant
         *  because the single parameters are matched as non-greedy (first occurrence)
         *  and the path is greedy matched (as many as possible). The implementation
         *  cannot distinguish between both types, therefore limit the types to :str
         *  and disallow routes with multiple :path types.
         */
        $identity = \str_replace(':path', ':str', $identity, $paths);
        $this->assertIdentityAndPaths($template, $identity, $paths);
        $this->identity[$identity] = $template;

        try {
            $regex = "~^$regex\$~$options";
            // TODO: Quick test for duplicate subpattern names
            \preg_match($regex, '/');
            return [
                'regex' => $regex,
                'identity' => $identity
            ];
        } catch (\Throwable $ex) {
            throw new HTTPConflict(
                title: 'PCRE compilation error. ' . $ex->getMessage(),
                detail: $ex->getMessage(),
                instance: $template,
            );
        }
    }

    private function assertSupportedType(
        string $template,
        array $types,
        string $type,
        string $filter): void
    {
        ('regex' === $type && empty($filter)) && throw new HTTPConflict(
            title: 'Invalid route. No regular expression provided',
            detail: 'Provide a proper PCRE regular expression',
            instance: $template,
        );
        isset($types[$type]) || throw (new HTTPConflict(
            title: "Invalid route parameter type $type",
            detail: 'Use one of the supported parameter types',
            instance: $template,
        ))->setMember('supported-types', \array_keys($types));
    }

    private function assertIdentityAndPaths(
        string $template,
        string $identity,
        int $paths): void
    {
        isset($this->identity[$identity]) && throw (new HTTPConflict(
            instance: $template,
            title: 'Duplicate route',
            detail: \sprintf(
                'Detected a multiple route definitions. The URI template ' .
                'for "%s" conflicts with an already registered route "%s".',
                $template, $this->identity[$identity])
        ))->setMember('conflict-route', [$template => $this->identity[$identity]]);

        if ($paths > 1) throw new HTTPConflict(
            title: 'Invalid route. Multiple path parameters in the route template detected',
            detail: 'Only one "path" type is allowed as URI parameter',
            instance: $template,
        );
    }
}
