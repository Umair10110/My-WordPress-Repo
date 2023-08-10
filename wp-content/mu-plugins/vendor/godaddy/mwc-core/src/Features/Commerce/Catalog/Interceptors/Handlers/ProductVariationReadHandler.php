<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Register\Types\RegisterAction;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers\CheckForDeletedProductHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers\VariantUpdateDeleteHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\ProductReadInterceptor;
use GoDaddy\WordPress\MWC\Core\Interceptors\Handlers\AbstractInterceptorHandler;
use WP_Query;

/**
 * Callback for {@see ProductReadInterceptor}.
 *
 * This is used to reconcile local and remote variants for a product when it is being read in the frontend.
 *
 * @see ProductEditHandler for the backend equivalent
 */
class ProductVariationReadHandler extends AbstractInterceptorHandler
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
     * @return WP_Query|null
     * @throws Exception
     */
    public function run(...$args)
    {
        /** @var WP_Query|null $wpQuery */
        $wpQuery = $args[0] ?? null;

        if ($wpQuery && $this->shouldHandle($wpQuery) && ($productId = $this->getProductIdForQuery($wpQuery))) {
            // Deletes the post cache for a single product while loading the "view product" page in the frontend.
            wp_cache_delete($productId, 'posts');

            // Checks if the product was deleted upstream. If it has, it will be deleted locally and the customer will see a 404 error.
            $this->checkForDeletedProductHelper->checkByLocalId($productId);

            // Ensures local variants match what's upstream.
            $this->variantUpdateDeleteHelper->reconcileVariantsForProductByPostId($productId);
        }

        return $wpQuery;
    }

    /**
     * Determines whether the given query should be handled.
     *
     * @param WP_Query|mixed $wpQuery
     * @return bool
     */
    protected function shouldHandle($wpQuery) : bool
    {
        return $wpQuery instanceof WP_Query
            && ! $wpQuery->is_admin
            && $wpQuery->is_singular
            && 'product' === $wpQuery->get('post_type')
            && $wpQuery->is_main_query();
    }

    /**
     * Gets the variable product ID for the given query.
     *
     * @param WP_Query $wpQuery
     * @return int|null
     */
    protected function getProductIdForQuery(WP_Query $wpQuery) : ?int
    {
        try {
            $this->getActionHook()->deregister();

            // even though get_posts may return multiple items, we know at this point it is for a singular page
            $productPost = $wpQuery->get_posts()[0] ?? null;

            $this->getActionHook()->execute();

            // this is the case where posts are returned as IDs, however this shouldn't happen in the main query; and either way we know it's for a product
            if (is_int($productPost)) {
                return $productPost;
            }

            /* @phpstan-ignore-next-line PhpStan complains about WP_Post properties */
            if (is_object($productPost) && isset($productPost->ID, $productPost->post_type) && 'product' === $productPost->post_type) {
                return (int) $productPost->ID;
            }
        } catch (Exception $exception) {
        }

        return null;
    }

    /**
     * Gets the action hook for the handler.
     *
     * We need to prevent infinite callback loops in {@see ProductVariationReadHandler::getProductIdForQuery()} while we call {@see WP_Query::get_posts()}.
     *
     * @return RegisterAction
     */
    protected function getActionHook() : RegisterAction
    {
        return Register::action()
            ->setGroup('pre_get_posts')
            ->setHandler([ProductVariationReadHandler::class, 'handle'])
            ->setPriority(PHP_INT_MAX);
    }
}
