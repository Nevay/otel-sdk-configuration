<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Environment;

interface EnvSource {

    public function readRaw(string $name): mixed;
}
