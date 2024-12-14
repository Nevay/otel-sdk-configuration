<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration;

use Exception;
use Nevay\OTelSDK\Configuration\Environment\EnvReader;
use Nevay\OTelSDK\Configuration\Environment\EnvResourceChecker;
use Nevay\OTelSDK\Configuration\Internal\CompiledConfigurationFactory;
use Nevay\OTelSDK\Configuration\Internal\ComponentProviderRegistry;
use Nevay\OTelSDK\Configuration\Internal\ConfigurationLoader;
use Nevay\OTelSDK\Configuration\Internal\EnvSubstitutionNormalization;
use Nevay\OTelSDK\Configuration\Internal\ResourceCollection;
use Nevay\OTelSDK\Configuration\Internal\TrackingEnvReader;
use Nevay\OTelSDK\Configuration\Loader\YamlExtensionFileLoader;
use Nevay\OTelSDK\Configuration\Loader\YamlSymfonyFileLoader;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\Config\Resource\SelfCheckingResourceChecker;
use Symfony\Component\Config\ResourceCheckerConfigCache;
use Symfony\Component\VarExporter\VarExporter;
use Throwable;
use function class_exists;
use function getcwd;
use function is_file;
use function serialize;
use function sprintf;
use function var_export;

/**
 * @template T
 */
final class ConfigurationFactory {

    private readonly CompiledConfigurationFactory $compiledFactory;

    /**
     * @param iterable<ComponentProvider> $componentProviders
     * @param ComponentProvider<T> $rootComponent
     * @param EnvReader $envReader
     */
    public function __construct(
        private readonly iterable $componentProviders,
        private readonly ComponentProvider $rootComponent,
        private readonly EnvReader $envReader,
    ) {}

    /**
     * @param array $configs configs to process
     * @param ResourceCollection|null $resources resources that can be used for cache invalidation
     * @return ComponentPlugin<T> processed component plugin
     * @throws InvalidConfigurationException if the configuration is invalid
     */
    public function process(array $configs, ?ResourceCollection $resources = null): ComponentPlugin {
        return ($this->compiledFactory ??= $this->compileFactory())
            ->process($configs, $resources);
    }

    /**
     * @param string|list<string> $file path(s) to parse
     * @param string|null $cacheFile path to cache parsed configuration to
     * @param bool $debug will check for cache freshness if debug mode enabled
     * @return ComponentPlugin parsed component plugin
     * @throws Exception if loading of a configuration file fails for any reason
     * @throws InvalidConfigurationException if the configuration is invalid
     * @throws Throwable if a cache file is given and a non-serializable component provider is used
     *
     * @see https://github.com/open-telemetry/opentelemetry-specification/blob/main/specification/configuration/file-configuration.md#parse
     */
    public function parseFile(
        string|array $file,
        ?string $cacheFile = null,
        bool $debug = true,
    ): ComponentPlugin {
        $cache = null;
        $resources = null;
        if ($cacheFile !== null) {
            $cache = new ResourceCheckerConfigCache($cacheFile, [
                new SelfCheckingResourceChecker(),
                new EnvResourceChecker($this->envReader),
            ]);
            if (is_file($cache->getPath())
                && ($configuration = @include $cache->getPath()) instanceof ComponentPlugin
                && (!$debug || $cache->isFresh())) {
                return $configuration;
            }
            $resources = new ResourceCollection();
            $resources->addClassResource(ComponentPlugin::class);
            $resources->addClassResource(VarExporter::class);
        }

        $paths = [];
        if (($cwd = getcwd()) !== false) {
            $paths[] = $cwd;
        }

        $loader = new ConfigurationLoader($resources);
        $locator = new FileLocator($paths);
        $fileLoader = new DelegatingLoader(new LoaderResolver([
            new YamlSymfonyFileLoader($loader, $locator),
            new YamlExtensionFileLoader($loader, $locator),
        ]));

        foreach ((array) $file as $path) {
            $fileLoader->load($path);
        }

        $configuration = ($this->compiledFactory ??= $this->compileFactory())
            ->process($loader->getConfigurations(), $resources);

        $cache?->write(
            class_exists(VarExporter::class)
                ? sprintf('<?php return %s;', VarExporter::export($configuration))
                : sprintf('<?php return unserialize(%s);', var_export(serialize($configuration), true)),
            $resources->toArray()
        );

        return $configuration;
    }

    private function compileFactory(): CompiledConfigurationFactory {
        $envReader = new TrackingEnvReader($this->envReader);
        $normalizations = [
            // Parse MUST perform environment variable substitution.
            new EnvSubstitutionNormalization($envReader),
        ];

        $registry = new ComponentProviderRegistry($normalizations);
        foreach ($this->componentProviders as $provider) {
            $registry->register($provider);
        }

        $root = $this->rootComponent->getConfig($registry);
        foreach ($normalizations as $normalization) {
            $normalization->apply($root);
        }

        $node = $root->getNode(forceRootNode: true);

        return new CompiledConfigurationFactory(
            $this->rootComponent,
            $node,
            [
                $registry,
                $envReader,
            ],
        );
    }
}
