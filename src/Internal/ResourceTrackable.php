<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Internal;

use Nevay\OTelSDK\Configuration\ResourceCollection;

interface ResourceTrackable {

    public function trackResources(?ResourceCollection $resources): void;
}
