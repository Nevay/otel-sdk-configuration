<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Internal;

use Nevay\OTelSDK\Configuration\Environment\EnvReader;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\BooleanNodeDefinition;
use Symfony\Component\Config\Definition\Builder\FloatNodeDefinition;
use Symfony\Component\Config\Definition\Builder\IntegerNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\ParentNodeDefinitionInterface;
use Symfony\Component\Config\Definition\Builder\ScalarNodeDefinition;
use Symfony\Component\Config\Definition\Builder\VariableNodeDefinition;
use function filter_var;
use function is_array;
use function is_string;
use function preg_replace_callback;
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
final class EnvSubstitutionNormalization {

    public function __construct(
        private readonly EnvReader $envReader,
    ) {}

    public function apply(ArrayNodeDefinition $root): void {
        foreach ($root->getChildNodeDefinitions() as $childNode) {
            $this->doApply($childNode);
        }
    }

    private function doApply(NodeDefinition $node): void {
        if ($node instanceof ScalarNodeDefinition) {
            $resolveScalars = match (true) {
                $node instanceof BooleanNodeDefinition,
                $node instanceof IntegerNodeDefinition,
                $node instanceof FloatNodeDefinition,
                    => true,
                default
                    => false,
            };
            $node->beforeNormalization()->ifString()->then(fn(string $v) => $this->replaceEnvVariables($v, $resolveScalars))->end();
        }
        if ($node instanceof VariableNodeDefinition) {
            $node->beforeNormalization()->always($this->replaceEnvVariablesRecursive(...))->end();
        }

        if ($node instanceof ParentNodeDefinitionInterface) {
            foreach ($node->getChildNodeDefinitions() as $childNode) {
                $this->doApply($childNode);
            }
        }
    }

    private function replaceEnvVariables(string $value, bool $resolveScalars = false): mixed {
        $replaced = preg_replace_callback(
            '/\$\{(?<ENV_NAME>[a-zA-Z_][a-zA-Z0-9_]*)(?::-(?<DEFAULT_VALUE>[^\n]*))?}/',
            fn(array $matches): string => $this->envReader->read($matches['ENV_NAME']) ?? $matches['DEFAULT_VALUE'] ?? '',
            $value,
            -1,
            $count,
        );

        if (!$count) {
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
