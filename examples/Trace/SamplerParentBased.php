<?php declare(strict_types=1);
namespace ExampleSDK\ComponentProvider\Trace;

use BadMethodCallException;
use ExampleSDK\Trace\Sampler;
use Nevay\OTelSDK\Configuration\ComponentPlugin;
use Nevay\OTelSDK\Configuration\ComponentProvider;
use Nevay\OTelSDK\Configuration\ComponentProviderRegistry;
use Nevay\OTelSDK\Configuration\Context;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

final class SamplerParentBased implements ComponentProvider {

    /**
     * @param array{
     *     root: ComponentPlugin<Sampler>,
     *     remote_parent_sampled: ?ComponentPlugin<Sampler>,
     *     remote_parent_not_sampled: ?ComponentPlugin<Sampler>,
     *     local_parent_sampled: ?ComponentPlugin<Sampler>,
     *     local_parent_not_sampled: ?ComponentPlugin<Sampler>,
     * } $properties
     */
    public function createPlugin(array $properties, Context $context): Sampler {
        throw new BadMethodCallException('not implemented');
    }


    public function getConfig(ComponentProviderRegistry $registry): ArrayNodeDefinition {
        $node = new ArrayNodeDefinition('parent_based');
        $node
            ->children()
                ->append($registry->component('root', Sampler::class)->isRequired())
                ->append($registry->component('remote_parent_sampled', Sampler::class))
                ->append($registry->component('remote_parent_not_sampled', Sampler::class))
                ->append($registry->component('local_parent_sampled', Sampler::class))
                ->append($registry->component('local_parent_not_sampled', Sampler::class))
            ->end()
        ;

        return $node;
    }
}
