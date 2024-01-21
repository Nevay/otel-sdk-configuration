<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Internal;

use Nevay\OTelSDK\Configuration\ComponentProvider;
use Nevay\OTelSDK\Configuration\Context;

/**
 * @template T
 * @implements \Nevay\OTelSDK\Configuration\ComponentPlugin<T>
 *
 * @internal
 */
final class ComponentPlugin implements \Nevay\OTelSDK\Configuration\ComponentPlugin {

    /**
     * @param array $properties resolved properties according to component provider config
     * @param ComponentProvider<T> $provider component provider used to create the component
     */
    public function __construct(
        private readonly array $properties,
        private readonly ComponentProvider $provider,
    ) {}

    public function create(Context $context): mixed {
        return $this->provider->createPlugin($this->properties, $context);
    }
}
