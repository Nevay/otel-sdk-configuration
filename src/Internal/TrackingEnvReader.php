<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Internal;

use Nevay\OTelSDK\Configuration\Environment\EnvReader;
use Nevay\OTelSDK\Configuration\Environment\EnvResource;
use Nevay\OTelSDK\Configuration\ResourceCollection;

/**
 * @internal
 */
final class TrackingEnvReader implements EnvReader, ResourceTrackable {

    private readonly EnvReader $envReader;
    private ?ResourceCollection $resources = null;

    public function __construct(EnvReader $envReader) {
        $this->envReader = $envReader;
    }

    public function trackResources(?ResourceCollection $resources): void {
        $this->resources = $resources;
    }

    public function read(string $name): ?string {
        $value = $this->envReader->read($name);
        $this->resources?->addResource(new EnvResource($name, $value));

        return $value;
    }
}
