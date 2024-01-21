<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Internal;

use Symfony\Component\Config\Definition\ArrayNode;
use function get_object_vars;

/**
 * @internal
 */
final class ArrayNodeDefaultNull extends ArrayNode {

    public static function fromNode(ArrayNode $node): ArrayNodeDefaultNull {
        $defaultNull = new ArrayNodeDefaultNull($node->getName());
        foreach (get_object_vars($node) as $property => $value) {
            $defaultNull->$property = $value;
        }

        return $defaultNull;
    }

    public function hasDefaultValue(): bool {
        return true;
    }

    public function getDefaultValue(): mixed {
        return null;
    }
}
