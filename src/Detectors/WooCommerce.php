<?php
/**
 * @package Postio\AddressValidation
 */

declare(strict_types=1);

namespace Postio\AddressValidation\Detectors;

use Postio\AddressValidation\Assets;

defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce — classic and block checkout, plus the My Account address
 * forms. Both checkouts are handled by a single client-side scanner because
 * block checkout renders via React after page load and our id-based
 * selectors need to retry until the React tree mounts.
 */
final class WooCommerce implements DetectorInterface {

	public function slug(): string {
		return 'woocommerce';
	}

	public function is_active(): bool {
		return function_exists( 'WC' ) || class_exists( 'WooCommerce' );
	}

	public function register(): void {
		add_action( 'wp_footer', [ $this, 'add_scanner' ], 0 );
	}

	public function add_scanner(): void {
		if ( ! function_exists( 'is_checkout' ) || ! function_exists( 'is_account_page' ) ) {
			return;
		}
		if ( ! is_checkout() && ! is_account_page() && ! is_cart() ) {
			// Cart can host the express-checkout block in newer WC versions.
			return;
		}

		$path = POSTIO_AV_PATH . 'assets/woocommerce-scanner.js';
		if ( ! is_readable( $path ) ) {
			return;
		}
		Assets::instance()->add_scanner( (string) file_get_contents( $path ) );
	}
}
