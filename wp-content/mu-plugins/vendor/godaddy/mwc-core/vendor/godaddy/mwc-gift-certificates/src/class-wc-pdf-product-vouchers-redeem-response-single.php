<?php
/**
 * MWC Gift Certificates
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
 * Do not edit or add to this file if you wish to upgrade MWC Gift Certificates to newer
 * versions in the future. If you wish to customize MWC Gift Certificates for your
 * needs please refer to https://docs.woocommerce.com/document/woocommerce-pdf-product-vouchers/ for more information.
 *
 * @author    SkyVerge
 * @copyright Copyright (c) 2012-2021, SkyVerge, Inc. (info@skyverge.com)
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace GoDaddy\WordPress\MWC\GiftCertificates;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\PluginFramework\v5_11_0 as Framework;

/**
 * Specific class for SPV response.
 *
 * @since 3.5.0
 */
class MWC_Gift_Certificates_Redeem_Response_Single implements MWC_Gift_Certificates_Redeem_Response {

	/**
	 * @var string $message response message
	 */
	private $message;

	/**
	 * @var float $remaining remaining redemption count
	 */
	private $remaining = 0;


	/**
	 * Sets redemption response message
	 *
	 * @since 3.5.0
	 *
	 * @param string $message
	 */
	public function set_message( $message ) {
		$this->message = $message;
	}


	/**
	 * Set remaining count for SPV
	 *
	 * @since 3.5.0
	 *
	 * @param float $remaining
	 */
	public function set_remaining( $remaining ) {
		$this->remaining = $remaining;
	}


	/**
	 * Converts this object into an array.
	 *
	 * @since 3.5.0
	 *
	 * @return array
	 */
	public function to_array() {
		return array(
			'message'   => wp_kses_post( $this->message ),
			'available' => $this->remaining,
		);
	}
}