<?php
/**
 * Server-side renderer for the wp:postio/address-finder block.
 *
 * @var array  $attributes  Block attributes.
 * @var string $content     Inner content (unused).
 *
 * @package Postio\AddressValidation
 */

use Postio\AddressValidation\Assets;
use Postio\AddressValidation\FieldMap;

defined( 'ABSPATH' ) || exit;

/*
 * Everything below is deliberately kept inside this closure. A block
 * render template is include()d at global scope, so bare `$id`, `$label`
 * etc. would land in the global namespace — which Plugin Check flags as
 * WordPress.NamingConventions.PrefixAllGlobals, and which risks colliding
 * with whatever else is rendering on the page.
 */
$postio_av_block = static function ( array $attributes ): array {
	$id_attr = isset( $attributes['inputId'] )
		? sanitize_html_class( (string) $attributes['inputId'] )
		: '';

	$id       = '' !== $id_attr ? $id_attr : uniqid( 'postio-block-' );
	$selector = '#' . $id;

	$output = FieldMap::normalize(
		[
			'address_line_1' => $attributes['outputLine1']    ?? '',
			'address_line_2' => $attributes['outputLine2']    ?? '',
			'post_town'      => $attributes['outputTown']     ?? '',
			'postcode'       => $attributes['outputPostcode'] ?? '',
		]
	);

	if ( empty( $output ) ) {
		// No output map — still wire the search box to itself so the
		// dropdown fills the visible field with the picked Line 1.
		$output = [ 'address_line_1' => $selector ];
	}

	Assets::instance()->add_setup( $selector, $output );

	return [
		'id'          => $id,
		'label'       => isset( $attributes['inputLabel'] ) ? (string) $attributes['inputLabel'] : '',
		'placeholder' => isset( $attributes['inputPlaceholder'] ) ? (string) $attributes['inputPlaceholder'] : '',
		'wrapper'     => function_exists( 'get_block_wrapper_attributes' )
			? get_block_wrapper_attributes( [ 'class' => 'postio-address-finder-block' ] )
			: 'class="postio-address-finder-block"',
	];
};

$postio_av_view = $postio_av_block( is_array( $attributes ?? null ) ? $attributes : [] );

?>
<div <?php echo $postio_av_view['wrapper']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( '' !== $postio_av_view['label'] ) : ?>
		<label for="<?php echo esc_attr( $postio_av_view['id'] ); ?>" class="postio-address-finder-label">
			<?php echo esc_html( $postio_av_view['label'] ); ?>
		</label>
	<?php endif; ?>
	<input
		type="text"
		id="<?php echo esc_attr( $postio_av_view['id'] ); ?>"
		placeholder="<?php echo esc_attr( $postio_av_view['placeholder'] ); ?>"
		autocomplete="off"
		spellcheck="false"
	/>
</div>
