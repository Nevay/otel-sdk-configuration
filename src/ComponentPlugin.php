<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Configuration;

/**
 * A component plugin that can be used to create a component.
 *
 * @template T
 */
interface ComponentPlugin {

    /**
     * Creates the component that is provided by this plugin.
     *
     * @param Context $context context used for creation
     * @return T created component
     */
    public function create(Context $context): mixed;
}
