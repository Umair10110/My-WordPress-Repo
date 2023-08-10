<?php

namespace GoDaddy\WordPress\MWC\Core\Interceptors;

use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Components\Exceptions\ComponentClassesNotDefinedException;
use GoDaddy\WordPress\MWC\Common\Components\Exceptions\ComponentLoadFailedException;
use GoDaddy\WordPress\MWC\Common\Components\Traits\HasComponentsFromContainerTrait;
use GoDaddy\WordPress\MWC\Common\Interceptors\AbstractInterceptor;
use GoDaddy\WordPress\MWC\Core\Analytics\Interceptors\GoogleAnalyticsEventInterceptor;
use GoDaddy\WordPress\MWC\Core\Analytics\Interceptors\ScriptEventDataInterceptor;
use GoDaddy\WordPress\MWC\Core\Auth\Sso\WordPress\SsoInterceptor;
use GoDaddy\WordPress\MWC\Core\Channels\Interceptors\FindOrCreateOrderChannelActionInterceptor;
use GoDaddy\WordPress\MWC\Core\FeatureFlags\Interceptors\RefreshFeatureEvaluationsInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Interceptors\StoreIdInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\GiftCertificates\Interceptors\GiftCertificateInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Shipping\Interceptors\StoreLocationChangeInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Stripe\Interceptors\RedirectInterceptor;
use GoDaddy\WordPress\MWC\Core\HostingPlans\Interceptors\DetectHostingPlanChangeActionInterceptor;
use GoDaddy\WordPress\MWC\Core\HostingPlans\Interceptors\RegisterHostingPlanChangeActionInterceptor;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Interceptors\AutoConnectInterceptor;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Interceptors\BroadcastSyncedProductsInterceptor;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Interceptors\PullProductsActionInterceptor;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Interceptors\PushProductsActionInterceptor;
use GoDaddy\WordPress\MWC\Core\Payments\Stripe\Interceptors\CartInterceptor;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Interceptors\CouponInterceptor;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Interceptors\CustomerInterceptor;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Interceptors\DomainChangeInterceptor;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Interceptors\OrderInterceptor;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Interceptors\ProductInterceptor;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Overrides\DefaultSettings;
use GoDaddy\WordPress\MWC\Core\WordPress\Interceptors\DomainAttachNoticeInterceptor;
use GoDaddy\WordPress\MWC\Core\WordPress\Interceptors\EnforcePostNamePermalinksInterceptor;
use GoDaddy\WordPress\MWC\Core\WordPress\Interceptors\ReviewInterceptor;
use GoDaddy\WordPress\MWC\Core\WordPress\Plugins\Overrides\Interceptors\DisableBlockedPluginsInterceptor;

/**
 * The Interceptors class instantiates AbstractInterceptor instances for hooking into actions and filters.
 */
class Interceptors implements ComponentContract
{
    use HasComponentsFromContainerTrait;

    /** @var class-string<AbstractInterceptor>[] list of class names that extend AbstractInterceptor */
    protected array $componentClasses = [
        AutoConnectInterceptor::class,
        BroadcastSyncedProductsInterceptor::class,
        CartInterceptor::class,
        CouponInterceptor::class,
        CustomerInterceptor::class,
        DetectHostingPlanChangeActionInterceptor::class,
        DisableBlockedPluginsInterceptor::class,
        DomainAttachNoticeInterceptor::class,
        DomainChangeInterceptor::class,
        FindOrCreateOrderChannelActionInterceptor::class,
        GiftCertificateInterceptor::class,
        GoogleAnalyticsEventInterceptor::class,
        OrderInterceptor::class,
        ProductInterceptor::class,
        PullProductsActionInterceptor::class,
        PushProductsActionInterceptor::class,
        RedirectInterceptor::class,
        RefreshFeatureEvaluationsInterceptor::class,
        RegisterHostingPlanChangeActionInterceptor::class,
        ReviewInterceptor::class,
        DefaultSettings::class,
        ScriptEventDataInterceptor::class,
        SsoInterceptor::class,
        StoreIdInterceptor::class, // @TODO move this to a new, less generic component in MWC-9753 {agibson 2022-12-21}
        StoreLocationChangeInterceptor::class,
        EnforcePostNamePermalinksInterceptor::class,
    ];

    /**
     * {@inheritDoc}
     *
     * @throws ComponentClassesNotDefinedException|ComponentLoadFailedException
     */
    public function load() : void
    {
        $this->loadComponents();
    }
}
