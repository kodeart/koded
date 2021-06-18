<?php declare(strict_types=1);

namespace Koded\Framework;

use Koded\Stdlib\UUID;
use Psr\SimpleCache\CacheInterface;
use function Koded\Stdlib\{json_serialize, json_unserialize};

class RegexRouter
{
    public const ALLOWED_CHARS = '[a-z0-9\.\-\_]+';
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
        \assert(\str_starts_with($template, '/'), 'URI template must begin with "/"');
        \assert(!\str_contains($template, '//'), 'URI template has duplicate slashes');

        $id = $this->id($template);
        if ($this->index[$id] ?? false) {
            if (empty($this->index[$id]['resource'])) {
                $this->callback[$id] = ['resource' => $resource] + $this->index[$id];
            }
            return;
        }
        $route = [
            'regex' => '',
            'identity' => '',
            'template' => $template,
            'resource' => \is_object($resource) ? '' : $resource,
        ];
        [$route['regex'], $identity] = $this->compile($template);

        if (isset($this->identity[$identity])) {
            throw (new HTTPConflict(
                title:'Invalid route',
                instance: $template,
                detail: \preg_replace('/['.PHP_EOL.' ]+/', ' ', \sprintf(
                    'Detected a multiple route definitions. The URI template for route "%s" 
                    conflicts with already defined route "%s". Please fix your routes.',
                    $template, $this->identity[$identity]))
            ))->setMember('conflict-route', [$template => $this->identity[$identity]]);
        }
        $route['identity'] = $identity;
        $this->identity[$identity] = $template;
        $this->index[$id] = $route;
        if (empty($route['resource'])) {
            $this->callback[$id] = ['resource' => $resource] + $route;
        }
        $this->cache->set($id, $route);
    }

    public function match(string $path): array
    {
        $id = $this->id($path);
        if ($route = $this->callback[$id] ?? $this->index[$id] ?? false) {
            return $route;
        }
        foreach ($this->index as $id => $route) {
            if (\preg_match($route['regex'], $path, $params)) {
                return $this->normalize($this->callback[$id] ?? $route, $params);
            }
        }
        return [];
    }

    private function normalize(array $route, array $params): array
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
        // Uses the URI template checksum for the route index
        return 'r.' . \crc32($value);
    }

    private function compile(string $template): array
    {
        $regex = $identity = $template;
        if (false === \str_contains($template, '{')) {
            return ['~^' . \preg_quote($template, '/') . '$~ui', $identity];
        }
        $filters = ':[str|int|dir|float|uuid|regex]*(:.*)?'; // TODO dir
        $options = '';
        $types   = [
            //':str'   => '.+',
            ':str'   => '(?:[0-9]*[a-z\.\_\-]|[a-z]+[0-9\.\_\-])[a-z0-9\.\_\-]*',
            ':int'   => '\-?\d+',
            ':float' => '(\-?\d*\.\d+)',
            ':uuid'  => UUID::PATTERN,
            //':dir'  => '.+/([^?]+).*',
            ':dir'  => '.+',
            ':regex' => '',
        ];
        \preg_match_all('~{(' . self::ALLOWED_CHARS . ")($filters)?}~i",
                        $regex, $matches, PREG_SET_ORDER);

        if (empty($matches)) {
            throw (new HTTPConflict(
                title: 'Invalid route',
                instance: $template,
                detail: 'Use supported argument types'
            ))->setMember('supported-types', \array_keys($types));
        }
        foreach ($matches as $match) {
            [$search, $replace, $filter] = $match + [2 => ':str'];
            [, $type, $custom] = \explode(':', $filter) + [2 => '\w+'];
            if ('str' === $type) {
                $options = 'ui';
            }
            $type = $types[$filter] ?? $custom;
            $regex    = \str_replace($search, "(?P<$replace>$type)", $regex);
            $identity = \str_replace($search, $filter, $identity);
        }
        return ['~^' . \str_replace('/', '\/', $regex) . '$~' . $options, $identity];
    }
}
