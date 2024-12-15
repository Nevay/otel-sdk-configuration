<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Internal\NodeDefinition;

use Nevay\OTelSDK\Configuration\Internal\Node\StringNode;

/**
 * @internal
 */
final class StringNodeDefinition extends \Symfony\Component\Config\Definition\Builder\StringNodeDefinition {
    use NodeDefinitionTrait;

    protected function instantiateNode(): StringNode {
        return new StringNode($this->name, $this->parent, $this->pathSeparator);
    }
}