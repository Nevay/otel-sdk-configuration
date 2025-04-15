<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Internal;

use Nevay\OTelSDK\Configuration\Environment\EnvReader;
use Nevay\OTelSDK\Configuration\Internal\Node\BooleanNode;
use Nevay\OTelSDK\Configuration\Internal\Node\FloatNode;
use Nevay\OTelSDK\Configuration\Internal\Node\IntegerNode;
use Nevay\OTelSDK\Configuration\Internal\Node\PrototypedArrayNode;
use Nevay\OTelSDK\Configuration\Internal\Node\VariableNode;
use Symfony\Component\Config\Definition\ArrayNode;
use Symfony\Component\Config\Definition\NodeInterface;
use Symfony\Component\Config\Definition\ScalarNode;
use function filter_var;
use function is_array;
use function is_string;
use const FILTER_FLAG_ALLOW_HEX;
use const FILTER_FLAG_ALLOW_OCTAL;
use const FILTER_NULL_ON_FAILURE;
use const FILTER_VALIDATE_FLOAT;
use const FILTER_VALIDATE_INT;
use const INF;
use const NAN;

/**
 * @internal
 */
final class SubstitutionNormalization implements Normalization {

    public function __construct(
        private readonly EnvReader $envReader,
    ) {}

    public function applyToNode(NodeInterface $node, mixed $value): mixed {
        if ($node instanceof PrototypedArrayNode && is_array($value)) {
            foreach ($value as $k => $v) {
                if (($r = $this->applyToNode($node->getPrototype(), $v)) !== $v) {
                    $value[$k] = $r;
                }
            }

            return $value;
        }
        if ($node instanceof ArrayNode && is_array($value)) {
            foreach ($value as $k => $v) {
                if (!$child = $node->getChildren()[$k] ?? null) {
                    continue;
                }

                if (($r = $this->applyToNode($child, $v)) !== $v) {
                    $value[$k] = $r;
                }
            }

            return $value;
        }
        if ($node instanceof ScalarNode && is_string($value)) {
            $resolveScalars = match (true) {
                $node instanceof BooleanNode,
                $node instanceof IntegerNode,
                $node instanceof FloatNode,
                    => true,
                default
                    => false,
            };

            return $this->replaceEnvVariables($value, $resolveScalars);
        }
        if ($node instanceof VariableNode) {
            return $this->replaceEnvVariablesRecursive($value);
        }

        return $value;
    }

    private function replaceEnvVariables(string $value, bool $resolveScalars = false): mixed {
        $replaced = Substitution::process($value, $this->envReader);

        if ($value === $replaced) {
            return $value;
        }
        if ($replaced === '') {
            return null;
        }
        if (!$resolveScalars) {
            return $replaced;
        }

        // https://yaml.org/spec/1.2.2/#103-core-schema
        return match ($replaced) {
            'null', 'Null', 'NULL', '~' => null,
            'true', 'True', 'TRUE' => true,
            'false', 'False', 'FALSE' => false,
            '.nan', '.NaN', '.NAN' => NAN,
            '.inf', '.Inf', '.INF', => INF,
            '+.inf', '+.Inf', '+.INF' => +INF,
            '-.inf', '-.Inf', '-.INF' => -INF,
            default => null
                ?? filter_var($replaced, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE | FILTER_FLAG_ALLOW_OCTAL | FILTER_FLAG_ALLOW_HEX)
                ?? filter_var($replaced, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE)
                ?? $replaced,
        };
    }

    private function replaceEnvVariablesRecursive(mixed $value): mixed {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                if (($r = $this->replaceEnvVariablesRecursive($v)) !== $v) {
                    $value[$k] = $r;
                }
            }
        }
        if (is_string($value)) {
            $value = $this->replaceEnvVariables($value);
        }

        return $value;
    }
}
