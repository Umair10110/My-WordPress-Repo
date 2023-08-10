<?php

namespace GoDaddy\WordPress\MWC\Core\Configuration;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Models\Contracts\HostingPlanContract;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Configuration\Contracts\RuntimeConfigurationContract;

class CartRecoveryEmailsRuntimeConfiguration implements RuntimeConfigurationContract
{
    /** @var HostingPlanContract */
    protected $hostingPlan;

    public function __construct(HostingPlanContract $hostingPlan)
    {
        $this->hostingPlan = $hostingPlan;
    }

    /**
     * {@inheritDoc}
     */
    public function getNumberOfCartRecoveryEmails() : int
    {
        if ($this->hostingPlan->isTrial()) {
            return 3;
        }

        return ArrayHelper::get(
            [
                'expand'        => 2,
                'expand_GDGCPP' => 2,
                'premier'       => 3,
            ],
            $this->hostingPlan->getName(),
            1
        );
    }

    /**
     * {@inheritDoc}
     */
    public function isCartRecoveryEmailAllowed(int $messagePosition) : bool
    {
        return $this->getNumberOfCartRecoveryEmails() >= $messagePosition;
    }
}
