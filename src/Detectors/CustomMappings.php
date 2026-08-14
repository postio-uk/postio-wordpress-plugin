<?php
/**
 * @package Postio\AddressValidation
 */

declare(strict_types=1);

namespace Postio\AddressValidation\Detectors;

use Postio\AddressValidation\Assets;
use Postio\AddressValidation\FieldMap;
use Postio\AddressValidation\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Catch-all detector for builders we don't auto-detect. Reads
 * Settings::get('custom_mappings'), each row a { input, output[] }
 * pair, and queues a setup per row.
 */
final class CustomMappings implements DetectorInterface {

	public function slug(): string {
		return 'custom_mappings';
	}

	public function is_active(): bool {
		$rows = Settings::get( 'custom_mappings' );
		return is_array( $rows ) && ! empty( $rows );
	}

	public function register(): void {
		add_action( 'wp_footer', [ $this, 'enqueue' ], 0 );
	}

	public function enqueue(): void {
		$rows = Settings::get( 'custom_mappings' );
		if ( ! is_array( $rows ) ) {
			return;
		}

		$assets = Assets::instance();
		foreach ( $rows as $row ) {
			if ( empty( $row['input'] ) || empty( $row['output'] ) || ! is_array( $row['output'] ) ) {
				continue;
			}
			$assets->add_setup(
				(string) $row['input'],
				FieldMap::normalize( $row['output'] )
			);
		}
	}
}
