<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Internal;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\ParentNodeDefinitionInterface;

/**
 * @internal
 */
final class TreatNullAsUnsetNormalization {

    public function apply(NodeDefinition $node): void {
        $node->beforeNormalization()->ifNull()->thenUnset()->end();

        if ($node instanceof ParentNodeDefinitionInterface) {
            foreach ($node->getChildNodeDefinitions() as $childNode) {
                $this->apply($childNode);
            }
        }
    }
}
