<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Pages\Orders\Columns;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Content\AbstractPostsTableColumn;
use GoDaddy\WordPress\MWC\Core\Features\CartRecoveryEmails\Repositories\WooCommerce\OrdersRepository;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Repositories\ChannelRepository;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Adapters\OrderAdapter;

/**
 * Handles the sales channel column in the WooCommerce orders page.
 */
class SalesChannelColumn extends AbstractPostsTableColumn implements ComponentContract
{
    /** @var string post type associated with this column */
    protected $postType = 'shop_order';

    /** @var string the slug for the column */
    protected $slug = 'mwc_marketplaces_sales_channel';

    /** @var int priority needs to be greater than 10 to run after {@see WC_Admin_List_Table_Orders::define_columns()} */
    protected $registerPriority = 20;

    /**
     * SalesChannelColumn constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->setName(__('Sales Channel', 'mwc-core'));
    }

    /**
     * Adds a new "Sales Channel" column immediately after the "Order" column.
     *
     * @param array $columns Registered columns.
     * @return array
     */
    public function register(array $columns) : array
    {
        $orderColumnPosition = array_search('order_number', array_keys($columns), true);

        if (false === $orderColumnPosition) {
            // If `order_number` isn't found then we'll default to the parent class behaviour, which is to just append the column without special ordering.
            return parent::register($columns);
        }

        $salesChannelPosition = $orderColumnPosition + 1;

        return array_merge(
            array_slice($columns, 0, $salesChannelPosition),
            [$this->getSlug() => $this->getName()],
            array_slice($columns, $salesChannelPosition)
        );
    }

    /**
     * Renders the column content.
     *
     * @param int|null $postId
     * @return void
     */
    public function render(int $postId = null) : void
    {
        $sourceOrder = $postId ? OrdersRepository::get($postId) : null;

        if (! $sourceOrder) {
            return;
        }

        // since we are in a callback context, we should catch any exceptions and bail out gracefully
        try {
            $nativeOrder = OrderAdapter::getNewInstance($sourceOrder)->convertFromSource();
        } catch (Exception $exception) {
            return;
        }

        echo ChannelRepository::getLabel($nativeOrder->getMarketplacesChannelType() ?: '');
    }

    /**
     * {@inheritDoc}
     */
    public function load()
    {
        // no-op
    }
}
