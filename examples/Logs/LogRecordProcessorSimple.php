<?php declare(strict_types=1);
namespace ExampleSDK\ComponentProvider\Logs;

use BadMethodCallException;
use ExampleSDK\Logs\LogRecordExporter;
use ExampleSDK\Logs\LogRecordProcessor;
use Nevay\OTelSDK\Configuration\ComponentPlugin;
use Nevay\OTelSDK\Configuration\ComponentProvider;
use Nevay\OTelSDK\Configuration\ComponentProviderRegistry;
use Nevay\OTelSDK\Configuration\Context;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

final class LogRecordProcessorSimple implements ComponentProvider {

    /**
     * @param array{
     *     exporter: ComponentPlugin<LogRecordExporter>,
     * } $properties
     */
    public function createPlugin(array $properties, Context $context): LogRecordProcessor {
        throw new BadMethodCallException('not implemented');
    }

    public function getConfig(ComponentProviderRegistry $registry): ArrayNodeDefinition {
        $node = new ArrayNodeDefinition('simple');
        $node
            ->children()
                ->append($registry->component('exporter', LogRecordExporter::class)->isRequired())
            ->end()
        ;

        return $node;
    }
}
