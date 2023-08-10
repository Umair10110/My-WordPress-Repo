<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Content\Context\Screen;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers\CheckForDeletedProductHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers\VariantUpdateDeleteHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\ProductEditInterceptor;
use GoDaddy\WordPress\MWC\Core\Interceptors\Handlers\AbstractInterceptorHandler;

/**
 * Callback for {@see ProductEditInterceptor}.
 *
 * This is used to reconcile local and remote variants for a product when it is being edited.
 *
 * @see ProductVariationReadHandler for the frontend equivalent
 */
class ProductEditHandler extends AbstractInterceptorHandler
{
    /** @var VariantUpdateDeleteHelper */
    protected VariantUpdateDeleteHelper $variantUpdateDeleteHelper;

    /** @var CheckForDeletedProductHelper */
    protected CheckForDeletedProductHelper $checkForDeletedProductHelper;

    /**
     * Constructor.
     *
     * @param VariantUpdateDeleteHelper $variantUpdateDeleteHelper
     * @param CheckForDeletedProductHelper $checkForDeletedProductHelper
     */
    public function __construct(VariantUpdateDeleteHelper $variantUpdateDeleteHelper, CheckForDeletedProductHelper $checkForDeletedProductHelper)
    {
        $this->variantUpdateDeleteHelper = $variantUpdateDeleteHelper;
        $this->checkForDeletedProductHelper = $checkForDeletedProductHelper;
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public function run(...$args)
    {
        $currentScreen = WordPressRepository::getCurrentScreen();
        $productId = TypeHelper::int(ArrayHelper::get($_GET, 'post'), 0);

        if ($currentScreen instanceof Screen && 'product' === $currentScreen->getObjectType() && 0 !== $productId) {
            // Deletes the post cache for a single product while loading the product edit screen in the admin.
            wp_cache_delete($productId, 'posts');

            // Checks if the product was deleted upstream. If it has, this will halt the rest of the page execution.
            $this->checkForDeletedProductHelper->checkByLocalId($productId);

            // Ensures local variants match what's upstream.
            $this->variantUpdateDeleteHelper->reconcileVariantsForProductByPostId($productId);
        }
    }
}
