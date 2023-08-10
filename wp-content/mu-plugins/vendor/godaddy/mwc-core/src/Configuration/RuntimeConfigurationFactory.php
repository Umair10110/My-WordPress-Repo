<?php

namespace GoDaddy\WordPress\MWC\Core\Configuration;

use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Models\Contracts\HostingPlanContract;
use GoDaddy\WordPress\MWC\Common\Platforms\Exceptions\PlatformRepositoryException;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformRepositoryFactory;
use GoDaddy\WordPress\MWC\Common\Traits\IsSingletonTrait;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Configuration\Contracts\RuntimeConfigurationContract as CartRecoveryEmailsRuntimeConfigurationContract;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Exceptions\CartRecoveryException;

class RuntimeConfigurationFactory
{
    use IsSingletonTrait;

    /**
     * Returns an instance of Cart Recovery Emails feature runtime configuration.
     *
     * @return CartRecoveryEmailsRuntimeConfigurationContract
     * @throws CartRecoveryException
     * @throws PlatformRepositoryException
     */
    public function getCartRecoveryEmailsRuntimeConfiguration() : CartRecoveryEmailsRuntimeConfigurationContract
    {
        $className = TypeHelper::string(Configuration::get('features.cart_recovery_emails.runtime_configuration'), '');

        if (! $className) {
            throw new CartRecoveryException('Runtime configuration for cart recovery emails feature is not set.');
        }

        if (! class_exists($className)) {
            throw new CartRecoveryException("Class {$className} does not exist.");
        }

        $classInterfaces = class_implements($className);

        if (! is_array($classInterfaces) || ! in_array(CartRecoveryEmailsRuntimeConfigurationContract::class, $classInterfaces, true)) {
            throw new CartRecoveryException("{$className} must implement CartRecoveryEmailsRuntimeConfigurationContract.");
        }

        /* @phpstan-ignore-next-line */
        return new $className($this->getHostingPlan());
    }

    /**
     * Gets site's hosting plan object.
     *
     * @return HostingPlanContract
     * @throws PlatformRepositoryException
     */
    protected function getHostingPlan() : HostingPlanContract
    {
        return PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->getPlan();
    }
}
