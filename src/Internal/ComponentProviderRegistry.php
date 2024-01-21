<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Internal;

use InvalidArgumentException;
use LogicException;
use Nevay\OTelSDK\Configuration\ComponentProvider;
use Nevay\OTelSDK\Configuration\ResourceCollection;
use Nevay\OTelSDK\Configuration\Validation;
use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\NodeInterface;
use Symfony\Component\Config\Definition\Processor;
use function array_key_first;
use function array_keys;
use function array_map;
use function count;
use function implode;
use function sprintf;

/**
 * @internal
 */
final class ComponentProviderRegistry implements \Nevay\OTelSDK\Configuration\ComponentProviderRegistry, ResourceTrackable {

    /** @var iterable<Normalization> */
    private readonly iterable $normalizations;

    /** @var array<string, array<string, ComponentProvider>> */
    private array $providers = [];
    /** @var array<string, array<string, NodeInterface>> */
    private array $nodes = [];
    private ?ResourceCollection $resources = null;

    /**
     * @param iterable<Normalization> $normalizations
     */
    public function __construct(iterable $normalizations) {
        $this->normalizations = $normalizations;
    }

    public function register(ComponentProvider $provider): void {
        $config = $provider->getConfig($this);

        $name = self::loadName($config);
        $type = self::loadType($provider);
        if (isset($this->providers[$type][$name])) {
            throw new LogicException(sprintf('Duplicate component provider registered for "%s" "%s"', $type, $name));
        }

        foreach ($this->normalizations as $normalization) {
            $normalization->apply($config);
        }

        $this->providers[$type][$name] = $provider;
        $this->nodes[$type][$name] = $config->getNode(forceRootNode: true);
    }

    public function trackResources(?ResourceCollection $resources): void {
        $this->resources = $resources;
    }

    public function component(string $name, string $type): NodeDefinition {
        $node = new ArrayNodeDefaultNullDefinition($name);
        $this->applyToArrayNode($node, $type);

        return $node;
    }

    public function componentList(string $name, string $type): ArrayNodeDefinition {
        $node = new ArrayNodeDefinition($name);
        $this->applyToArrayNode($node->arrayPrototype(), $type);

        return $node;
    }

    public function componentNames(string $name, string $type): ArrayNodeDefinition {
        $node = new ArrayNodeDefinition($name);

        $providers = $this->providers[$type];
        foreach ($this->nodes[$type] as $providerName => $configNode) {
            try {
                $configNode->finalize([]);
            } catch (InvalidConfigurationException) {
                unset($providers[$providerName]);
            }
        }
        if ($providers) {
            $node->scalarPrototype()->validate()->always(Validation::ensureString())->end()->end();
            $node->validate()->always(function(array $value) use ($type): array {
                $plugins = [];
                foreach ($value as $name) {
                    if (!isset($this->providers[$type][$name])) {
                        throw new InvalidArgumentException(sprintf('Unknown provider "%s" for "%s"',
                            $name, $type));
                    }
                    $provider = $this->providers[$type][$name];
                    $this->resources?->addClassResource($provider);

                    $plugins[] = new ComponentPlugin(
                        (new Processor())->process($this->nodes[$type][$name], []),
                        $this->providers[$type][$name],
                    );
                }

                return $plugins;
            });
        }

        return $node;
    }

    private function applyToArrayNode(ArrayNodeDefinition $node, string $type): void {
        $node->info(sprintf('Component "%s"', $type));
        $node->performNoDeepMerging();
        $node->ignoreExtraKeys(false);

        $node->validate()->always(function(array $value) use ($type): ComponentPlugin {
            if (count($value) !== 1) {
                throw new InvalidArgumentException(sprintf('Component "%s" must have exactly one element defined, got %s',
                    $type, implode(', ', array_map(json_encode(...), array_keys($value)) ?: ['none'])));
            }

            $name = array_key_first($value);
            $provider = $this->providers[$type][$name];
            $this->resources?->addClassResource($provider);

            return new ComponentPlugin(
                (new Processor())->process($this->nodes[$type][$name], $value),
                $this->providers[$type][$name],
            );
        });
    }

    private static function loadName(NodeDefinition $node): string {
        static $accessor;
        $accessor ??= (static fn(NodeDefinition $node): ?string => $node->name)->bindTo(null, NodeDefinition::class);

        return $accessor($node);
    }

    private static function loadType(ComponentProvider $provider): string {
        /** @noinspection PhpUnhandledExceptionInspection */
        if ($returnType = (new ReflectionClass($provider))->getMethod('createPlugin')->getReturnType()) {
            return self::typeToString($returnType);
        }

        return 'mixed';
    }

    private static function typeToString(ReflectionType $type): string {
        return match ($type::class) {
            ReflectionNamedType::class => $type->getName(),
            ReflectionUnionType::class => implode('|', array_map(self::typeToString(...), $type->getTypes())),
            ReflectionIntersectionType::class => implode('&', array_map(self::typeToString(...), $type->getTypes())),
        };
    }
}
