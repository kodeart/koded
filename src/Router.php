<?php declare(strict_types=1);

namespace Koded\Framework;

use Koded\Http\HTTPConflict;
use Koded\Stdlib\UUID;
use Psr\SimpleCache\CacheInterface;
use Throwable;
use function array_filter;
use function array_keys;
use function assert;
use function crc32;
use function explode;
use function is_object;
use function Koded\Stdlib\json_serialize;
use function Koded\Stdlib\json_unserialize;
use function preg_match;
use function preg_match_all;
use function str_contains;
use function str_replace;

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
        assert('/' === ($template[0] ?? ''), __('koded.router.noSlash'));
        assert(false === str_contains($template, '//'), __('koded.router.duplicateSlashes'));

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
            if (preg_match($route['regex'], $path, $params)) {
                return $this->normalizeParams($this->callback[$id] ?? $route, $params);
            }
        }
        return empty($this->index) ? ['resource' => 'no_app_routes'] : [];
    }

    private function normalizeParams(array $route, array $params): array
    {
        $route['params'] = json_unserialize(json_serialize(
            array_filter($params, 'is_string', ARRAY_FILTER_USE_KEY),
            JSON_NUMERIC_CHECK)
        );
        return $route;
    }

    private function id(string $value): string
    {
        return 'r.' . crc32($value);
    }

    private function compileRoute(
        string $template,
        object|string $resource,
        string $id): array
    {
        $route = $this->compileTemplate($template) + [
            'resource' => is_object($resource) ? '' : $resource,
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
        if (false === str_contains($template, '{') &&
            false === str_contains($template, '<')) {
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
            $regex = str_replace($parameter, '(?P<' . $name .'>' . ($filter ?: $types[$type]) . ')', $regex);
            $identity = str_replace($parameter, $types[$type] ? ":$type" : $filter, $identity);
            ('str' === $type || 'path' === $type) && $options = 'ui';
        }
        /*
         * [NOTE]: Replace :path with :str. The concept of "path" is irrelevant
         *  because the single parameters are matched as non-greedy (first occurrence)
         *  and the path is greedy matched (as many as possible). The implementation
         *  cannot distinguish between both types, therefore limit the types to :str
         *  and disallow routes with multiple :path types.
         */
        $identity = str_replace(':path', ':str', $identity, $paths);
        $this->assertIdentityAndPaths($template, $identity, $paths);
        $this->identity[$identity] = $template;

        try {
            $regex = "~^$regex\$~$options";
            // TODO: Quick test for duplicate subpattern names
            preg_match($regex, '/');
            return [
                'regex' => $regex,
                'identity' => $identity
            ];
        } catch (Throwable $ex) {
            throw new HTTPConflict(
                title: __('koded.router.pcre.compilation', [$ex->getMessage()]),
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
        ('regex' === $type and empty($filter)) and throw new HTTPConflict(
            title: __('koded.router.invalidRoute.title'),
            detail: __('koded.router.invalidRoute.detail'),
            instance: $template,
        );
        isset($types[$type]) or throw (new HTTPConflict(
            title: __('koded.router.invalidParam.title', [$type]),
            detail: __('koded.router.invalidParam.detail'),
            instance: $template,
        ))->setMember('supported-types', array_keys($types));
    }

    private function assertIdentityAndPaths(
        string $template,
        string $identity,
        int $paths): void
    {
        isset($this->identity[$identity]) and throw (new HTTPConflict(
            instance: $template,
            title: __('koded.router.duplicateRoute.title'),
            detail: __('koded.router.duplicateRoute.detail', [$template, $this->identity[$identity]])
        ))->setMember('conflict-route', [$template => $this->identity[$identity]]);

        $paths > 1 and throw new HTTPConflict(
            title: __('koded.router.multiPaths.title'),
            detail: __('koded.router.multiPaths.detail'),
            instance: $template,
        );
    }
}
