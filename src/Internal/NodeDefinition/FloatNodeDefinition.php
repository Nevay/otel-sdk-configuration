<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Internal\NodeDefinition;

use Nevay\OTelSDK\Configuration\Internal\Node\FloatNode;

/**
 * @internal
 */
final class FloatNodeDefinition extends \Symfony\Component\Config\Definition\Builder\FloatNodeDefinition {
    use NodeDefinitionTrait;

    protected function instantiateNode(): FloatNode {
        return new FloatNode($this->name, $this->parent, $this->min, $this->max, $this->pathSeparator);
    }
}
