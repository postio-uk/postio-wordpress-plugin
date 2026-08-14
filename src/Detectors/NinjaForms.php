<?php
/**
 * @package Postio\AddressValidation
 */

declare(strict_types=1);

namespace Postio\AddressValidation\Detectors;

use Postio\AddressValidation\Assets;

defined( 'ABSPATH' ) || exit;

/**
 * Ninja Forms.
 *
 * NF's "address" field is a single text input — there's no composite
 * address field in core. To opt in, the user adds Display CSS Classes
 * (postio-line-1, postio-line-2, postio-town,
 * postio-postcode) to the relevant fields. Forms are Backbone-rendered
 * client-side, so the scanner uses MutationObserver + nfFormReady.
 */
final class NinjaForms implements DetectorInterface {

	public function slug(): string {
		return 'ninjaforms';
	}

	public function is_active(): bool {
		return defined( 'NF_PLUGIN_VERSION' ) || class_exists( 'Ninja_Forms' );
	}

	public function register(): void {
		add_action( 'wp_footer', [ $this, 'add_scanner' ], 0 );
	}

	public function add_scanner(): void {
		$path = POSTIO_AV_PATH . 'assets/ninjaforms-scanner.js';
		if ( ! is_readable( $path ) ) {
			return;
		}
		Assets::instance()->add_scanner( (string) file_get_contents( $path ) );
	}
}
