<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration;

use ExampleSDK\ComponentProvider;
use Nevay\OTelSDK\Configuration\Environment\ArrayEnvSource;
use Nevay\OTelSDK\Configuration\Environment\EnvSourceReader;
use Nevay\OTelSDK\Configuration\Environment\PhpIniEnvSource;
use PHPUnit\Framework\Attributes\BackupGlobals;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ConfigurationFactoryTest extends TestCase {

    public function testEnvSubstitutionNotSetEnvVariable(): void {
        $parsed = self::factory()->process([[
            'file_format' => '0.1',
            'resource' => [
                'attributes' => [
                    'service.name' => '${OTEL_SERVICE_NAME}'
                ],
            ],
        ]]);

        $this->assertSame('', self::getPropertiesFromPlugin($parsed)['resource']['attributes']['service.name']);
    }

    #[BackupGlobals(true)]
    public function testEnvSubstitutionString(): void {
        $_SERVER['OTEL_SERVICE_NAME'] = 'example-service';
        $parsed = self::factory()->process([[
            'file_format' => '0.1',
            'resource' => [
                'attributes' => [
                    'service.name' => '${OTEL_SERVICE_NAME}'
                ],
            ],
        ]]);

        $this->assertInstanceOf(ComponentPlugin::class, $parsed);
        $this->assertSame('example-service', self::getPropertiesFromPlugin($parsed)['resource']['attributes']['service.name']);
    }

    #[BackupGlobals(true)]
    public function testEnvSubstitutionNested(): void {
        $_SERVER['OTEL_TRACE_ID_RATIO'] = '0.7';
        $parsed = self::factory()->process([[
            'file_format' => '0.1',
            'tracer_provider' => [
                'sampler' => [
                    'trace_id_ratio_based' => [
                        'ratio' => '${OTEL_TRACE_ID_RATIO}'
                    ],
                ],
            ],
        ]]);

        $this->assertInstanceOf(ComponentPlugin::class, $parsed);
        $this->assertSame(0.7, self::getPropertiesFromPlugin(self::getPropertiesFromPlugin($parsed)['tracer_provider']['sampler'])['ratio']);
    }

    #[BackupGlobals(true)]
    public function testEnvSubstitutionNonString(): void {
        $_SERVER['OTEL_ATTRIBUTE_VALUE_LENGTH_LIMIT'] = '2048';
        $parsed = self::factory()->process([[
            'file_format' => '0.1',
            'attribute_limits' => [
                'attribute_value_length_limit' => '${OTEL_ATTRIBUTE_VALUE_LENGTH_LIMIT}'
            ],
        ]]);

        $this->assertInstanceOf(ComponentPlugin::class, $parsed);
        $this->assertSame(2048, self::getPropertiesFromPlugin($parsed)['attribute_limits']['attribute_value_length_limit']);
    }

    public function testTreatNullAsUnset(): void {
        $parsed = self::factory()->process([[
            'file_format' => '0.1',
            'attribute_limits' => [
                'attribute_value_length_limit' => null,
            ],
        ]]);

        $this->assertInstanceOf(ComponentPlugin::class, $parsed);
        $this->assertSame(4096, self::getPropertiesFromPlugin($parsed)['attribute_limits']['attribute_value_length_limit']);
    }

    private function getPropertiesFromPlugin(ComponentPlugin $plugin): array {
        assert($plugin instanceof Internal\ComponentPlugin);
        return (fn() => $this->properties)->bindTo($plugin, Internal\ComponentPlugin::class)();
    }

    public static function openTelemetryConfigurationDataProvider(): iterable {
        yield 'kitchen-sink' => [__DIR__ . '/configurations/kitchen-sink.yaml'];
        yield 'anchors' => [__DIR__ . '/configurations/anchors.yaml'];
    }

    #[DataProvider('openTelemetryConfigurationDataProvider')]
    public function testOpenTelemetryConfiguration(string $file): void {
        $this->expectNotToPerformAssertions();
        self::factory()->parseFile($file);
    }

    private function factory(): ConfigurationFactory {
        return new ConfigurationFactory(
            [
                new ComponentProvider\Propagator\TextMapPropagatorB3(),
                new ComponentProvider\Propagator\TextMapPropagatorB3Multi(),
                new ComponentProvider\Propagator\TextMapPropagatorBaggage(),
                new ComponentProvider\Propagator\TextMapPropagatorComposite(),
                new ComponentProvider\Propagator\TextMapPropagatorJaeger(),
                new ComponentProvider\Propagator\TextMapPropagatorOTTrace(),
                new ComponentProvider\Propagator\TextMapPropagatorTraceContext(),
                new ComponentProvider\Propagator\TextMapPropagatorXRay(),

                new ComponentProvider\Trace\SamplerAlwaysOff(),
                new ComponentProvider\Trace\SamplerAlwaysOn(),
                new ComponentProvider\Trace\SamplerParentBased(),
                new ComponentProvider\Trace\SamplerTraceIdRatioBased(),
                new ComponentProvider\Trace\SpanExporterConsole(),
                new ComponentProvider\Trace\SpanExporterOtlp(),
                new ComponentProvider\Trace\SpanExporterZipkin(),
                new ComponentProvider\Trace\SpanProcessorBatch(),
                new ComponentProvider\Trace\SpanProcessorSimple(),

                new ComponentProvider\Metrics\AggregationResolverDefault(),
                new ComponentProvider\Metrics\AggregationResolverDrop(),
                new ComponentProvider\Metrics\AggregationResolverExplicitBucketHistogram(),
                new ComponentProvider\Metrics\AggregationResolverLastValue(),
                new ComponentProvider\Metrics\AggregationResolverSum(),
                new ComponentProvider\Metrics\MetricExporterConsole(),
                new ComponentProvider\Metrics\MetricExporterOtlp(),
                new ComponentProvider\Metrics\MetricExporterPrometheus(),
                new ComponentProvider\Metrics\MetricReaderPeriodic(),
                new ComponentProvider\Metrics\MetricReaderPull(),

                new ComponentProvider\Logs\LogRecordExporterConsole(),
                new ComponentProvider\Logs\LogRecordExporterOtlp(),
                new ComponentProvider\Logs\LogRecordProcessorBatch(),
                new ComponentProvider\Logs\LogRecordProcessorSimple(),
            ],
            new ComponentProvider\OpenTelemetryConfiguration(),
            new EnvSourceReader([
                new ArrayEnvSource($_SERVER),
                new PhpIniEnvSource(),
            ]),
        );
    }
}
