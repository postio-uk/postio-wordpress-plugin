<?php
/**
 * @package Postio\AddressValidation
 */

declare(strict_types=1);

namespace Postio\AddressValidation\Detectors;

use Postio\AddressValidation\Assets;

defined( 'ABSPATH' ) || exit;

/**
 * Fluent Forms.
 *
 * Address fields are rendered with a .ff-name-address-wrapper container and
 * each sub-input carries data-key_name="address_line_1" / city / state /
 * zip etc. Server-side IDs aren't predictable, so the shipped scanner
 * assigns DOM ids on the fly within each wrapper's scope.
 */
final class FluentForms implements DetectorInterface {

	public function slug(): string {
		return 'fluentforms';
	}

	public function is_active(): bool {
		return defined( 'FLUENTFORM_VERSION' ) || defined( 'FLUENTFORM_FRAMEWORK_UPGRADE' );
	}

	public function register(): void {
		add_action( 'wp_footer', [ $this, 'add_scanner' ], 0 );
	}

	public function add_scanner(): void {
		$path = POSTIO_AV_PATH . 'assets/fluentforms-scanner.js';
		if ( ! is_readable( $path ) ) {
			return;
		}
		Assets::instance()->add_scanner( (string) file_get_contents( $path ) );
	}
}
