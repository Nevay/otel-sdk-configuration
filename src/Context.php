<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration;

use OpenTelemetry\API\Logs\LoggerProviderInterface;
use OpenTelemetry\API\Logs\NoopLoggerProvider;
use OpenTelemetry\API\Metrics\MeterProviderInterface;
use OpenTelemetry\API\Metrics\Noop\NoopMeterProvider;
use OpenTelemetry\API\Trace\NoopTracerProvider;
use OpenTelemetry\API\Trace\TracerProviderInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Context used for component creation.
 */
final class Context {

    /**
     * @param TracerProviderInterface $tracerProvider tracer provider to use for self diagnostics
     * @param MeterProviderInterface $meterProvider meter provider to use for self diagnostics
     * @param LoggerProviderInterface $loggerProvider logger provider to use for self diagnostics
     * @param LoggerInterface $logger logger to use for self diagnostics
     */
    public function __construct(
        public readonly TracerProviderInterface $tracerProvider = new NoopTracerProvider(),
        public readonly MeterProviderInterface $meterProvider = new NoopMeterProvider(),
        public readonly LoggerProviderInterface $loggerProvider = new NoopLoggerProvider(),
        public readonly LoggerInterface $logger = new NullLogger(),
    ) {}
}
