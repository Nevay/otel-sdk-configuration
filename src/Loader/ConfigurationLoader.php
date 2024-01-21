<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration\Loader;

use Symfony\Component\Config\Resource\ResourceInterface;

interface ConfigurationLoader {

    public function loadConfiguration(mixed $configuration): void;

    public function addResource(ResourceInterface $resource): void;
}

