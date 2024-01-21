<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Internal;

use Symfony\Component\Config\Definition\ArrayNode;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\NodeInterface;
use function assert;
use function get_object_vars;

/**
 * @internal
 */
final class ArrayNodeDefaultNullDefinition extends ArrayNodeDefinition {

    protected function createNode(): NodeInterface {
        $node = parent::createNode();
        assert($node instanceof ArrayNode);

        return ArrayNodeDefaultNull::fromNode($node);
    }
}
