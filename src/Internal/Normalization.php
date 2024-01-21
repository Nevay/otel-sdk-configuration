<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Internal;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

/**
 * @internal
 */
interface Normalization {

    public function apply(ArrayNodeDefinition $root): void;
}
