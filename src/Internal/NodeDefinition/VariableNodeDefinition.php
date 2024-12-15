<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Internal\NodeDefinition;

use Nevay\OTelSDK\Configuration\Internal\Node\VariableNode;

/**
 * @internal
 */
final class VariableNodeDefinition extends \Symfony\Component\Config\Definition\Builder\VariableNodeDefinition {
    use NodeDefinitionTrait;

    protected function instantiateNode(): VariableNode {
        return new VariableNode($this->name, $this->parent, $this->pathSeparator);
    }
}
