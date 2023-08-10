<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Repositories;

use Exception;
use GoDaddy\WordPress\MWC\Common\Cache\Cache;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformRepositoryFactory;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters\OrderAdapter;

/**
 * Repository class for handling marketplaces orders.
 */
class OrderRepository
{
    /**
     * Gets the count of the Marketplaces orders for the current month.
     *
     * @return int
     */
    protected static function getCurrentMonthMarketplacesOrdersCount() : int
    {
        $cache = (new Cache())->key('gdm_monthly_order_count')->expires(86400);
        $count = $cache->get();

        if (is_numeric($count)) {
            return (int) $count;
        }

        try {
            Register::filter()
                ->setGroup('woocommerce_order_data_store_cpt_get_orders_query')
                ->setHandler(static function ($query, $args) {
                    if (ArrayHelper::accessible($query) && ArrayHelper::accessible($args) && $metaKey = ArrayHelper::get($args, 'gdm_count_orders_by_meta_key')) {
                        $query['meta_query'][] = [
                            'key'     => $metaKey,
                            'compare' => 'EXISTS',
                        ];
                    }

                    return $query;
                })
                ->setArgumentsCount(2)
                ->execute();
        } catch (Exception $exception) {
            return 0;
        }

        $count = count((array) wc_get_orders([
            'limit'                        => -1,
            'return'                       => 'ids',
            'date_created'                 => static::getCurrentMonthQueryInterval(),
            'gdm_count_orders_by_meta_key' => OrderAdapter::MARKETPLACES_CHANNEL_UUID_META_KEY,
        ]));

        $cache->set($count);

        return $count;
    }

    /**
     * Gets an interval for the current month formatted for {@see wc_get_orders()} query.
     *
     * @return string
     */
    protected static function getCurrentMonthQueryInterval() : string
    {
        return strtotime(date('Y-m-1 00:00:00')).'...'.strtotime(date('Y-m-t 23:59:59'));
    }

    /**
     * Gets the quota of monthly Marketplaces orders according to merchant's plan.
     *
     * In case the plan has no associated quota, this will return the highest possible number.
     *
     * @return int
     */
    protected static function getMonthlyMarketplacesOrdersPlanLimit() : int
    {
        try {
            $planName = PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->getPlan()->getName();
        } catch (Exception $exception) {
            return PHP_INT_MAX;
        }

        $planLimits = Configuration::get('marketplaces.plan_limits', []);

        return (int) ArrayHelper::get($planLimits, $planName, PHP_INT_MAX);
    }

    /**
     * Determines if the site has reached the monthly quota of Marketplaces orders.
     *
     * @return bool
     */
    public static function hasReachedMonthlyMarketplacesOrdersLimit() : bool
    {
        return static::getCurrentMonthMarketplacesOrdersCount() >= static::getMonthlyMarketplacesOrdersPlanLimit();
    }

    /**
     * Determines if the site has nearly reached (90%) the monthly quota of Marketplaces orders.
     *
     * @return bool
     */
    public static function isNearMonthlyMarketplacesOrdersLimit() : bool
    {
        return ! static::hasReachedMonthlyMarketplacesOrdersLimit()
            && static::getCurrentMonthMarketplacesOrdersCount() >= static::getMonthlyMarketplacesOrdersPlanLimit() * 0.9;
    }
}
