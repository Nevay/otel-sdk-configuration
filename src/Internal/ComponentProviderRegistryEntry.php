<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Internal;

use Nevay\OTelSDK\Configuration\ComponentProvider;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\NodeInterface;

/**
 * @internal
 */
final class ComponentProviderRegistryEntry {

    public function __construct(
        public readonly ComponentProvider $componentProvider,
        public ArrayNodeDefinition|NodeInterface $node,
    ) {}
}
