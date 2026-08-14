<?php
/**
 * @package Postio\AddressValidation
 */

declare(strict_types=1);

namespace Postio\AddressValidation;

defined( 'ABSPATH' ) || exit;

final class FieldMap {

	public const API_FIELDS = [
		'address_line_1',
		'address_line_2',
		'address_line_3',
		'post_town',
		'postcode',
		'country',
	];

	/**
	 * Drop empty values, trim selectors, keep only known API fields.
	 *
	 * @param array<string,string|null> $output
	 * @return array<string,string>
	 */
	public static function normalize( array $output ): array {
		$out = [];
		foreach ( self::API_FIELDS as $field ) {
			$sel = isset( $output[ $field ] ) ? trim( (string) $output[ $field ] ) : '';
			if ( '' !== $sel ) {
				$out[ $field ] = $sel;
			}
		}
		return $out;
	}
}
