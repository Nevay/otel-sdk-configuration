<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Internal;

/**
 * @internal
 */
interface NormalizationsAware {

    /**
     * @param list<Normalization> $normalizations
     */
    public function setNormalizations(array $normalizations): void;
}
