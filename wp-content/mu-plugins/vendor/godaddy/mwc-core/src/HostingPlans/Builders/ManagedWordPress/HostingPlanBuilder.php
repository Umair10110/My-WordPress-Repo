<?php

namespace GoDaddy\WordPress\MWC\Core\HostingPlans\Builders\ManagedWordPress;

use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\HostingPlans\Builders\Contracts\HostingPlanBuilderContract;
use GoDaddy\WordPress\MWC\Common\Models\Contracts\HostingPlanContract;
use GoDaddy\WordPress\MWC\Common\Models\HostingPlan;
use GoDaddy\WordPress\MWC\Common\Platforms\Exceptions\PlatformRepositoryException;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformRepositoryFactory;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;

class HostingPlanBuilder implements HostingPlanBuilderContract
{
    use CanGetNewInstanceTrait;

    /**
     * {@inheritDoc}
     */
    public function build() : HostingPlanContract
    {
        $accountPlanName = (string) StringHelper::ensureScalar(Configuration::get('godaddy.account.plan.name'));

        return HostingPlan::seed([
            'name'    => $this->getPlanId($accountPlanName),
            'label'   => $accountPlanName,
            'isTrial' => false,
        ]);
    }

    /**
     * Gets the ID of the hosting plan used by this site.
     *
     * @param string $accountPlanName
     * @return string
     */
    protected function getPlanId(string $accountPlanName) : string
    {
        if (empty($accountPlanName)) {
            return '';
        }

        try {
            $platformRepository = PlatformRepositoryFactory::getNewInstance()->getPlatformRepository();
        } catch (PlatformRepositoryException $exception) {
            return '';
        }

        if (! $platformRepository->hasPlatformData()) {
            return '';
        }

        foreach (ArrayHelper::wrap(Configuration::get('mwp.hosting.plans')) as $id => $plan) {
            if (strtolower($accountPlanName) === strtolower(TypeHelper::string(ArrayHelper::get($plan, 'name'), ''))) {
                return $id;
            }
        }

        // assume that the account is using the smaller hosting plan if we can't determine one
        return 'basic';
    }
}
