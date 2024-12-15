<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Internal\Node;

final class PrototypedArrayNode extends \Symfony\Component\Config\Definition\PrototypedArrayNode {
    use NodeTrait;

    private bool $defaultValueSet = false;
    private mixed $defaultValue_ = null;
    private bool $allowEmptyValue = true;

    public static function fromNode(\Symfony\Component\Config\Definition\PrototypedArrayNode $node): PrototypedArrayNode {
        $_node = new self($node->getName());
        foreach (get_object_vars($node) as $property => $value) {
            $_node->$property = $value;
        }

        return $_node;
    }

    public function setDefaultValue(mixed $value): void {
        $this->defaultValue_ = $value;
        $this->defaultValueSet = true;
    }

    public function hasDefaultValue(): bool {
        return $this->defaultValueSet || parent::hasDefaultValue();
    }

    public function getDefaultValue(): mixed {
        return $this->defaultValueSet
            ? $this->defaultValue_
            : parent::getDefaultValue();
    }

    public function setAllowEmptyValue(bool $boolean): void {
        $this->allowEmptyValue = $boolean;
    }

    protected function normalizeValue(mixed $value): mixed {
        if ($value === null) {
            return null;
        }

        return parent::normalizeValue($value);
    }

    protected function mergeValues(mixed $leftSide, mixed $rightSide): mixed {
        if (null === $rightSide) {
            return $leftSide;
        }
        if (null === $leftSide) {
            return $rightSide;
        }

        return parent::mergeValues($leftSide, $rightSide);
    }

    public function finalizeValue(mixed $value): mixed {
        if ($value === null) {
            return null;
        }

        return parent::finalizeValue($value);
    }

    protected function validateType(mixed $value): void {
        if ($value === null && $this->allowEmptyValue) {
            return;
        }

        parent::validateType($value);
    }
}