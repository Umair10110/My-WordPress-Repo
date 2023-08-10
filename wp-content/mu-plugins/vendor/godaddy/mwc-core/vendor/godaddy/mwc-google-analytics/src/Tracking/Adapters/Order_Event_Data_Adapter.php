<?php
/**
 * Google Analytics
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@skyverge.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Google Analytics to newer
 * versions in the future. If you wish to customize Google Analytics for your
 * needs please refer to https://help.godaddy.com/help/40882 for more information.
 *
 * @author      SkyVerge
 * @copyright   Copyright (c) 2015-2023, SkyVerge, Inc.
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace GoDaddy\WordPress\MWC\GoogleAnalytics\Tracking\Adapters;

use Automattic\WooCommerce\Utilities\NumberUtil;
use WC_Abstract_Order;
use WC_Order;

defined( 'ABSPATH' ) or exit;

/**
 * The Order Event Data Adapter class.
 *
 * @since 3.0.0
 */
class Order_Event_Data_Adapter extends Event_Data_Adapter {


	/** @var WC_Abstract_Order the source order or refund */
	protected WC_Abstract_Order $order;


	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 *
	 * @param WC_Abstract_Order $order order or refund
	 */
	public function __construct( WC_Abstract_Order $order ) {

		$this->order = $order;
	}


	/**
	 * Converts the source order into an array.
	 *
	 * @return array
	 * @since 3.0.0
	 *
	 */
	public function convert_from_source(): array {

		return [
			'currency'       => $this->order->get_currency(),
			'transaction_id' => $this->order instanceof WC_Order ? $this->order->get_order_number() : $this->order->get_id(), // refunds do not have a number
			// unfortunately order has no method for getting the total without shipping and tax
			'value'          => NumberUtil::round( $this->order->get_total() - $this->order->get_shipping_total() - $this->order->get_total_tax(), wc_get_price_decimals() ),
			'coupon'         => implode( ',', $this->order->get_coupon_codes() ),
			'shipping'       => NumberUtil::round( $this->order->get_shipping_total(), wc_get_price_decimals() ),
			'tax'            => NumberUtil::round( $this->order->get_total_tax(), wc_get_price_decimals() ),
			'items'          => array_values( array_map(
				function ($item) {
					return ( new Order_Item_Event_Data_Adapter( $this->order, $item ) )->convert_from_source();
				},
				$this->order->get_items()
			) ),
		];
	}


}
