<?php declare(strict_types=1);

namespace Koded\Framework;

use Koded\{DIContainer, DIModule};
use Koded\Framework\Auth\{AuthBackend, AuthProcessor, BearerAuthProcessor, SessionAuthBackend};
use Koded\Http\{ServerRequest, ServerResponse};
use Koded\Http\Interfaces\{Request, Response};
use Koded\I18n\{I18n, I18nCatalog, CurlyFormatter};
use Koded\Logging\Log;
use Koded\Logging\Processors\Cli;
use Koded\Stdlib\{Config, Configuration, Immutable};
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use function dirname;
use function is_a;
use function is_readable;
use function Koded\Caching\simple_cache_factory;
use function putenv;

final class Module implements DIModule
{
    private const ENV_KEY = '__KODED_CONFIG';

    public function __construct(private Configuration|string $configuration) {}

    public function configure(DIContainer $container): void
    {
        $container->named('$errorSerializer', 'default_serialize_error');
        $container->bind(Request::class,         /* defer */);
        $container->bind(Response::class,        /* defer */);
        $container->bind(Configuration::class,   /* defer */);
        $container->bind(CacheInterface::class,  /* defer */);
        $container->bind(LoggerInterface::class, /* defer */);
        $container->bind(ServerRequestInterface::class, ServerRequest::class);
        $container->bind(ResponseInterface::class, ServerResponse::class);
        // Core instances
        $container->share($conf = $this->configuration());
        I18n::register(I18nCatalog::new($conf), true);
        $container->share(new Log(...$conf->get('logging', [])));
        $container->share(simple_cache_factory(...$conf->get('caching', [])));
        // Default authentication
        $container->bind(AuthBackend::class, SessionAuthBackend::class);
        $container->bind(AuthProcessor::class, BearerAuthProcessor::class);
    }

    private function configuration(): Configuration
    {
        $factory = new Config('', $this->defaultConfiguration());
        if (empty($this->configuration)) {
            goto load;
        }
        if (is_a($this->configuration, Configuration::class, true)) {
            $factory->fromObject($this->configuration);
            //$factory->root = dirname((new ReflectionClass($this->configuration))->getFileName());
        } elseif (is_readable($this->configuration)) {
            putenv(self::ENV_KEY . '=' . $this->configuration);
            $factory->fromEnvVariable(self::ENV_KEY);
            $factory->root = dirname($this->configuration);
        }
        load:
        is_readable("$factory->root/.env") and $factory->fromEnvFile("$factory->root/.env");
        foreach ($factory->get('autoloaders', []) as $autoloader) {
            include_once $autoloader;
        }
        return $factory;
    }

    private function defaultConfiguration(): Immutable
    {
        return new Immutable(
            [
                // I18n directives
                'translation.dir' => __DIR__ . '/../locale',
                'translation.formatter' => CurlyFormatter::class,

                // CORS overrides (all values are scalar)
                'cors.disable' => false,
                'cors.origin' => '',
                'cors.methods' => '',
                'cors.headers' => '',
                'cors.expose' => '',
                'cors.maxAge' => 0,

                // Logging
                'logging' => [
                    [
                        [
                            'class' => Cli::class,
                            'format' => '[levelname] message',
                            'levels' => Log::INFO
                        ]
                    ],
                    'dateformat' => 'd-m-Y H:i:s'
                ],
            ]);
    }
}
