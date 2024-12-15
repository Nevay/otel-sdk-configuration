<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Internal\Node;

/**
 * @internal
 */
trait NodeTrait {

    protected function preNormalize(mixed $value): mixed {
        if ($value === null && $this->allowEmptyValue && $this->defaultValueSet) {
            $value = $this->defaultValue;
        }

        /** @noinspection PhpMultipleClassDeclarationsInspection */
        return parent::preNormalize($value);
    }

    protected function validateType(mixed $value): void {
        if ($value === null && $this->allowEmptyValue) {
            return;
        }

        /** @noinspection PhpMultipleClassDeclarationsInspection */
        parent::validateType($value);
    }

    protected function isValueEmpty(mixed $value): bool {
        return $value === null;
    }
}
