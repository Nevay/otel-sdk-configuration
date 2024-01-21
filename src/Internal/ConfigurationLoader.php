<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Internal;

use Nevay\OTelSDK\Configuration\ResourceCollection;
use Symfony\Component\Config\Resource\ResourceInterface;

/**
 * @internal
 */
final class ConfigurationLoader implements \Nevay\OTelSDK\Configuration\Loader\ConfigurationLoader {

    private array $configurations = [];
    private readonly ?ResourceCollection $resources;

    public function __construct(?ResourceCollection $resources) {
        $this->resources = $resources;
    }

    public function loadConfiguration(mixed $configuration): void {
        $this->configurations[] = $configuration;
    }

    public function addResource(ResourceInterface $resource): void {
        $this->resources?->addResource($resource);
    }

    public function getConfigurations(): array {
        return $this->configurations;
    }
}
