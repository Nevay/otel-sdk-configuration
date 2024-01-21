<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Environment;

interface EnvReader {

    public function read(string $name): ?string;
}
