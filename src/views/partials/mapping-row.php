<?php
/**
 * Single custom-mapping row.
 *
 * Variables are prefixed because PHPCS analyses this file on its own and
 * cannot see that it is require()d from inside Settings::render_page(),
 * where they are method-scoped rather than global. Prefixing keeps the
 * PrefixAllGlobals sniff quiet without anyone having to judge whether
 * each warning is a false positive.
 *
 * @var int|string $postio_av_i   Row index (or '__INDEX__' in the JS template).
 * @var array      $postio_av_row [ 'input' => string, 'output' => array<string,string> ]
 *
 * @package Postio\AddressValidation
 */

use Postio\AddressValidation\Settings;

defined( 'ABSPATH' ) || exit;

$postio_av_opt    = Settings::OPTION_KEY;
$postio_av_input  = (string) ( $postio_av_row['input'] ?? '' );
$postio_av_output = is_array( $postio_av_row['output'] ?? null ) ? $postio_av_row['output'] : [];
?>
<div class="postio-av-mapping" data-row>
	<div class="postio-av-mapping-grid">
		<label>
			<span><?php esc_html_e( 'Input selector', 'postio-address-validation' ); ?></span>
			<input
				type="text"
				class="regular-text"
				name="<?php echo esc_attr( $postio_av_opt ); ?>[custom_mappings][<?php echo esc_attr( (string) $postio_av_i ); ?>][input]"
				value="<?php echo esc_attr( $postio_av_input ); ?>"
				placeholder="#address-search"
				spellcheck="false"
			/>
		</label>
		<?php foreach ( Settings::OUTPUT_FIELDS as $postio_av_field => $postio_av_field_label ) : ?>
			<label>
				<span><?php echo esc_html( $postio_av_field_label ); ?></span>
				<input
					type="text"
					name="<?php echo esc_attr( $postio_av_opt ); ?>[custom_mappings][<?php echo esc_attr( (string) $postio_av_i ); ?>][output][<?php echo esc_attr( $postio_av_field ); ?>]"
					value="<?php echo esc_attr( (string) ( $postio_av_output[ $postio_av_field ] ?? '' ) ); ?>"
					placeholder="<?php echo esc_attr( '#' . $postio_av_field ); ?>"
					spellcheck="false"
				/>
			</label>
		<?php endforeach; ?>
	</div>
	<button type="button" class="button-link postio-av-remove-mapping"><?php esc_html_e( 'Remove', 'postio-address-validation' ); ?></button>
</div>
