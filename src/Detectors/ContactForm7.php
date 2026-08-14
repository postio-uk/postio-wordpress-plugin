<?php
/**
 * @package Postio\AddressValidation
 */

declare(strict_types=1);

namespace Postio\AddressValidation\Detectors;

use Postio\AddressValidation\Assets;

defined( 'ABSPATH' ) || exit;

/**
 * Contact Form 7.
 *
 * CF7 has no built-in "address" field type — forms are composed from
 * individual [text] tags. To opt in, the user adds these to their CF7 form:
 *
 *   • Additional Settings → `html_class: postio-form`
 *   • field shortcodes with `class:postio-line-1`, `class:postio-town`, etc.
 *
 * The scanner JS finds tagged forms by class and wires each input by
 * convention. Multi-form-safe via the per-form id prefix.
 */
final class ContactForm7 implements DetectorInterface {

	public function slug(): string {
		return 'cf7';
	}

	public function is_active(): bool {
		return defined( 'WPCF7_VERSION' ) || class_exists( 'WPCF7' );
	}

	public function register(): void {
		// wp_footer priority 0 → ahead of Assets::enqueue at priority 1, so
		// our scanner gets registered before the bundle is emitted.
		add_action( 'wp_footer', [ $this, 'add_scanner' ], 0 );
	}

	public function add_scanner(): void {
		$path = POSTIO_AV_PATH . 'assets/cf7-scanner.js';
		if ( ! is_readable( $path ) ) {
			return;
		}
		$js = (string) file_get_contents( $path );
		Assets::instance()->add_scanner( $js );
	}
}
