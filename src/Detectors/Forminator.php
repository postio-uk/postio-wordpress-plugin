<?php
/**
 * @package Postio\AddressValidation
 */

declare(strict_types=1);

namespace Postio\AddressValidation\Detectors;

use Postio\AddressValidation\Assets;

defined( 'ABSPATH' ) || exit;

/**
 * Forminator (WPMU DEV).
 *
 * Address fields render with predictable wrapper ids
 * (#{element_id}-street_address, -address_line, -city, -state, -zip,
 * -country) but the input ids contain a per-render uid we can't
 * predict server-side. Scanner walks the DOM by wrapper id.
 */
final class Forminator implements DetectorInterface {

	public function slug(): string {
		return 'forminator';
	}

	public function is_active(): bool {
		return defined( 'FORMINATOR_VERSION' ) || class_exists( 'Forminator' );
	}

	public function register(): void {
		add_action( 'wp_footer', [ $this, 'add_scanner' ], 0 );
	}

	public function add_scanner(): void {
		$path = POSTIO_AV_PATH . 'assets/forminator-scanner.js';
		if ( ! is_readable( $path ) ) {
			return;
		}
		Assets::instance()->add_scanner( (string) file_get_contents( $path ) );
	}
}
