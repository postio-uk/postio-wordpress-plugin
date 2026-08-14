<?php
/**
 * @package Postio\AddressValidation
 */

declare(strict_types=1);

namespace Postio\AddressValidation\Detectors;

use Postio\AddressValidation\Assets;
use Postio\AddressValidation\FieldMap;

defined( 'ABSPATH' ) || exit;

/**
 * WPForms — address fields.
 *
 * Detects address fields server-side via wpforms_frontend_form_data and
 * queues setups against WPForms' standard sub-input id pattern:
 *
 *   #wpforms-{form_id}-field_{field_id}            — address1 (primary)
 *   #wpforms-{form_id}-field_{field_id}-address2
 *   #wpforms-{form_id}-field_{field_id}-city
 *   #wpforms-{form_id}-field_{field_id}-state
 *   #wpforms-{form_id}-field_{field_id}-postal
 *
 * The address field type ships with WPForms Lite but only renders on the
 * frontend with WPForms Pro. On Lite-only installs the queued setups
 * silently no-op (mount() pre-resolves selectors and bails).
 *
 * `country` is intentionally not mapped: WPForms uses ISO codes in the
 * country select; the Postio API returns the constituent country name
 * ("England", …). Setting an unmatched value would clear the dropdown.
 */
final class WPForms implements DetectorInterface {

	public function slug(): string {
		return 'wpforms';
	}

	public function is_active(): bool {
		return defined( 'WPFORMS_VERSION' );
	}

	public function register(): void {
		add_filter( 'wpforms_frontend_form_data', [ $this, 'wire_form' ] );
	}

	/**
	 * @param mixed $form_data
	 * @return mixed
	 */
	public function wire_form( $form_data ) {
		if ( ! is_array( $form_data ) || empty( $form_data['id'] ) || empty( $form_data['fields'] ) ) {
			return $form_data;
		}

		$form_id = (int) $form_data['id'];

		foreach ( $form_data['fields'] as $field ) {
			$type     = $field['type'] ?? null;
			$field_id = $field['id']   ?? null;

			if ( 'address' !== $type || ! $field_id ) {
				continue;
			}

			$base = '#wpforms-' . $form_id . '-field_' . $field_id;

			Assets::instance()->add_setup(
				$base,
				FieldMap::normalize(
					[
						'address_line_1' => $base,
						'address_line_2' => $base . '-address2',
						'post_town'      => $base . '-city',
						'postcode'       => $base . '-postal',
					]
				)
			);
		}

		return $form_data;
	}
}
