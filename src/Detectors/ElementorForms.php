<?php
/**
 * @package Postio\AddressValidation
 */

declare(strict_types=1);

namespace Postio\AddressValidation\Detectors;

use Postio\AddressValidation\Assets;

defined( 'ABSPATH' ) || exit;

/**
 * Elementor Forms (Pro).
 *
 * Forms is Elementor Pro-only — the free Elementor doesn't include it.
 * No composite address field exists; users compose address from
 * individual Text fields and opt in by adding Custom CSS Classes
 * (postio-line-1, postio-line-2, postio-town,
 * postio-postcode) under each field's Advanced tab.
 */
final class ElementorForms implements DetectorInterface {

	public function slug(): string {
		return 'elementor';
	}

	public function is_active(): bool {
		return defined( 'ELEMENTOR_PRO_VERSION' ) || class_exists( 'ElementorPro\\Plugin' );
	}

	public function register(): void {
		add_action( 'wp_footer', [ $this, 'add_scanner' ], 0 );
	}

	public function add_scanner(): void {
		$path = POSTIO_AV_PATH . 'assets/elementor-scanner.js';
		if ( ! is_readable( $path ) ) {
			return;
		}
		Assets::instance()->add_scanner( (string) file_get_contents( $path ) );
	}
}
