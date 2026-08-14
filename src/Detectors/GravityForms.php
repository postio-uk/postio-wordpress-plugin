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
 * Gravity Forms — address fields.
 *
 * Each Gravity address field renders as six sub-inputs with predictable
 * IDs (input_{formId}_{fieldId}_{N}, where N is 1..6). Mapping below
 * matches the field labels used by the International/UK address types.
 */
final class GravityForms implements DetectorInterface {

	public function slug(): string {
		return 'gravityforms';
	}

	public function is_active(): bool {
		return class_exists( 'GFForms' ) || class_exists( 'GFCommon' );
	}

	public function register(): void {
		add_filter( 'gform_pre_render', [ $this, 'wire_form' ] );
	}

	/**
	 * @param mixed $form Gravity form array.
	 * @return mixed Untouched.
	 */
	public function wire_form( $form ) {
		if ( ! is_array( $form ) || empty( $form['id'] ) || empty( $form['fields'] ) ) {
			return $form;
		}

		$form_id = (int) $form['id'];

		foreach ( $form['fields'] as $field ) {
			$type     = is_object( $field ) ? ( $field->type ?? null )    : ( $field['type'] ?? null );
			$field_id = is_object( $field ) ? ( $field->id   ?? null )    : ( $field['id']   ?? null );

			if ( 'address' !== $type || ! $field_id ) {
				continue;
			}

			$base = '#input_' . $form_id . '_' . (int) $field_id . '_';

			Assets::instance()->add_setup(
				$base . '1',
				FieldMap::normalize(
					[
						'address_line_1' => $base . '1',
						'address_line_2' => $base . '2',
						'post_town'      => $base . '3',
						'postcode'       => $base . '5',
						'country'        => $base . '6',
					]
				)
			);
		}

		return $form;
	}
}
