<?php
/**
 * Settings page view for Postio Address Validation.
 *
 * Variables provided by Settings::render_page():
 *
 * @var array $settings  Merged settings array.
 * @var array $detected  Builder slug → host plugin is loaded.
 * @var array $wiring    Manual-builder slug → opt-in class instructions.
 *
 * @package Postio\AddressValidation
 */

defined( 'ABSPATH' ) || exit;

use Postio\AddressValidation\Settings;

$postio_av_opt = Settings::OPTION_KEY;
?>
<div class="wrap postio-av-wrap">
	<h1><?php esc_html_e( 'Postio Address Validation', 'postio-address-validation' ); ?></h1>

	<form method="post" action="options.php" id="postio-av-form">
		<?php settings_fields( 'postio_av' ); ?>

		<?php
		$postio_av_has_key   = '' !== trim( (string) $settings['api_key'] );
		$postio_av_link_kses = [ 'a' => [ 'href' => [], 'target' => [], 'rel' => [] ], 'strong' => [] ];
		?>
		<h2><?php esc_html_e( 'API key', 'postio-address-validation' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="postio-av-api-key"><?php esc_html_e( 'Postio API key', 'postio-address-validation' ); ?></label>
				</th>
				<td>
					<?php
					/*
					 * Three states, driven by data-has-key plus an is-modified
					 * class that admin.js toggles. CSS decides what shows:
					 *
					 *  no key            input, Save, and the why-Postio copy.
					 *                    No Test — there is nothing to test yet.
					 *  key saved         input and Test. The copy has done its
					 *                    job and gets out of the way.
					 *  key saved, edited input, Save, Test and Cancel, plus a
					 *                    warning that the typed key is not live
					 *                    yet. Cancel restores the saved value,
					 *                    so a bad paste or a failed test is
					 *                    always recoverable.
					 */
					?>
					<div class="postio-av-key" data-has-key="<?php echo $postio_av_has_key ? '1' : '0'; ?>">
						<input
							type="text"
							class="regular-text"
							id="postio-av-api-key"
							name="<?php echo esc_attr( $postio_av_opt ); ?>[api_key]"
							value="<?php echo esc_attr( $settings['api_key'] ); ?>"
							data-saved="<?php echo esc_attr( $settings['api_key'] ); ?>"
							placeholder="pk_…"
							autocomplete="off"
							spellcheck="false"
						/>

						<span class="postio-av-key-actions">
							<button type="submit" class="button button-primary postio-av-only-save" id="postio-av-save-key">
								<?php esc_html_e( 'Save key', 'postio-address-validation' ); ?>
							</button>
							<button type="button" class="button postio-av-only-test" id="postio-av-test">
								<?php esc_html_e( 'Test connection', 'postio-address-validation' ); ?>
							</button>
							<button type="button" class="button-link postio-av-only-cancel" id="postio-av-revert">
								<?php esc_html_e( 'Cancel', 'postio-address-validation' ); ?>
							</button>
							<span class="postio-av-test-result" id="postio-av-test-result" aria-live="polite"></span>
						</span>

						<p class="postio-av-key-dirty" aria-live="polite">
							<?php esc_html_e( 'This key is not in use yet — save it to apply it. Cancel puts your saved key back.', 'postio-address-validation' ); ?>
						</p>

						<div class="postio-av-key-intro">
							<p class="description">
								<?php
								printf(
									wp_kses(
										/* translators: %s: signup link. */
										__( 'No key yet? Sign up free at %s — the plugin is free; you pay only for the API calls your forms make.', 'postio-address-validation' ),
										$postio_av_link_kses
									),
									'<a href="' . esc_url( Settings::URL_SIGNUP ) . '" target="_blank" rel="noopener">postio.co.uk</a>'
								);
								?>
							</p>

							<h3><?php esc_html_e( 'Why Postio?', 'postio-address-validation' ); ?></h3>
							<p class="description">
								<?php
								esc_html_e(
									"We're a licensed Royal Mail PAF provider, so you're searching the official Postcode Address File — the same file the postman works from. Royal Mail updates it every working day from changes reported on the ground, and we sync nightly, so new builds and renamed streets appear quickly. Pricing is pay as you go at 2.2p a lookup, with no monthly fee and no minimum. Searching is free, so you only pay when a customer actually picks an address.",
									'postio-address-validation'
								);
								?>
							</p>
						</div>
					</div>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Form builder detection', 'postio-address-validation' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Auto-detect', 'postio-address-validation' ); ?></th>
				<td>
					<label>
						<input type="hidden" name="<?php echo esc_attr( $postio_av_opt ); ?>[auto_detect]" value="0" />
						<input type="checkbox" name="<?php echo esc_attr( $postio_av_opt ); ?>[auto_detect]" value="1" <?php checked( $settings['auto_detect'] ); ?> />
						<?php esc_html_e( 'Wire up address fields automatically when a supported builder is active.', 'postio-address-validation' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Active builders', 'postio-address-validation' ); ?></th>
				<td>
					<ul class="postio-av-builders">
					<?php
					foreach ( Settings::BUILDERS as $postio_av_slug => $postio_av_label ) :
						$postio_av_is_present = ! empty( $detected[ $postio_av_slug ] );
						$postio_av_is_manual  = in_array( $postio_av_slug, Settings::MANUAL_BUILDERS, true );
						$postio_av_is_on      = ! empty( $settings['builders'][ $postio_av_slug ] );

						// WPForms Lite has no Address field to detect, so
						// "wired automatically" would be a lie.
						$postio_av_needs_pro = ( 'wpforms' === $postio_av_slug && $postio_av_is_present && ! Settings::wpforms_is_pro() );

						if ( ! $postio_av_is_present ) {
							$postio_av_state = 'off';
							$postio_av_text  = __( 'Not installed', 'postio-address-validation' );
						} elseif ( ! $postio_av_is_on || empty( $settings['auto_detect'] ) ) {
							$postio_av_state = 'off';
							$postio_av_text  = __( 'Installed — switched off below', 'postio-address-validation' );
						} elseif ( $postio_av_needs_pro ) {
							$postio_av_state = 'warn';
							$postio_av_text  = __( 'Detected — Address field needs WPForms Pro', 'postio-address-validation' );
						} elseif ( $postio_av_is_manual ) {
							$postio_av_state = 'warn';
							$postio_av_text  = __( 'Detected — needs field classes', 'postio-address-validation' );
						} else {
							$postio_av_state = 'ok';
							$postio_av_text  = __( 'Detected — wired automatically', 'postio-address-validation' );
						}
						?>

						<?php
						// One guide block per row, rendered below it. WPForms
						// Lite gets its own rather than a class snippet, since
						// there are no classes to add. Kept in a local instead
						// of writing back into $wiring, so the caller's array
						// isn't mutated mid-loop.
						$postio_av_guide = $wiring[ $postio_av_slug ] ?? null;

						if ( $postio_av_needs_pro ) {
							$postio_av_guide = [
								'where'   => __( 'WPForms Lite does not include the Address field — it is greyed out in the builder behind an upgrade prompt.', 'postio-address-validation' ),
								'classes' => [],
								'note'    => __( 'On WPForms Pro this wires itself, with nothing to configure. On Lite, build the address from Single Line Text fields and bind them under Custom mappings below.', 'postio-address-validation' ),
							];
						}
						?>
						<li class="postio-av-builder-row">
							<label>
								<input type="hidden" name="<?php echo esc_attr( $postio_av_opt ); ?>[builders][<?php echo esc_attr( $postio_av_slug ); ?>]" value="0" />
								<input type="checkbox" name="<?php echo esc_attr( $postio_av_opt ); ?>[builders][<?php echo esc_attr( $postio_av_slug ); ?>]" value="1" <?php checked( $postio_av_is_on ); ?> />
								<span class="postio-av-builder-name"><?php echo esc_html( $postio_av_label ); ?></span>
								<a
									class="postio-av-help"
									href="<?php echo esc_url( Settings::guide_url( $postio_av_slug ) ); ?>"
									target="_blank"
									rel="noopener"
									aria-label="<?php
									/* translators: %s: form builder name. */
									echo esc_attr( sprintf( __( 'Setup guide for %s', 'postio-address-validation' ), $postio_av_label ) );
									?>"
								><?php esc_html_e( 'Help?', 'postio-address-validation' ); ?></a>
							</label>
							<span class="postio-av-pill is-<?php echo esc_attr( $postio_av_state ); ?>"><?php echo esc_html( $postio_av_text ); ?></span>

							<?php if ( $postio_av_is_present && 'warn' === $postio_av_state && null !== $postio_av_guide ) : ?>
								<div class="postio-av-wiring">
									<p><?php echo esc_html( $postio_av_guide['where'] ); ?></p>
									<?php if ( ! empty( $postio_av_guide['classes'] ) ) : ?>
										<pre><code><?php echo esc_html( implode( "\n", $postio_av_guide['classes'] ) ); ?></code></pre>
									<?php endif; ?>
									<p class="description"><?php echo esc_html( $postio_av_guide['note'] ); ?></p>
								</div>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
					</ul>
					<p class="description">
						<?php esc_html_e( 'Greyed-out rows are builders you do not have installed. Nothing to configure for the automatic ones — save your key and they work.', 'postio-address-validation' ); ?>
					</p>
					<p class="description postio-av-support">
						<?php
						printf(
							wp_kses(
								/* translators: 1: setup guides link, 2: support email link. */
								__( 'Stuck on a builder? Read the %1$s, or email %2$s and a human will answer.', 'postio-address-validation' ),
								$postio_av_link_kses
							),
							'<a href="' . esc_url( Settings::URL_GUIDE ) . '" target="_blank" rel="noopener">'
								. esc_html__( 'WordPress setup guides', 'postio-address-validation' ) . '</a>',
							'<a href="mailto:' . esc_attr( Settings::SUPPORT_EMAIL ) . '">'
								. esc_html( Settings::SUPPORT_EMAIL ) . '</a>'
						);
						?>
					</p>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Custom mappings', 'postio-address-validation' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'For forms not covered by the detectors above. Each row binds one search input to one or more output fields by CSS selector. Output selectors are optional individually — at least one is required.', 'postio-address-validation' ); ?>
		</p>

		<div id="postio-av-mappings" data-next-index="<?php echo esc_attr( (string) count( $settings['custom_mappings'] ) ); ?>">
			<?php foreach ( $settings['custom_mappings'] as $postio_av_i => $postio_av_row ) : ?>
				<?php require __DIR__ . '/partials/mapping-row.php'; ?>
			<?php endforeach; ?>
		</div>

		<button type="button" class="button" id="postio-av-add-mapping"><?php esc_html_e( 'Add mapping', 'postio-address-validation' ); ?></button>

		<template id="postio-av-mapping-template">
			<?php
			$postio_av_i   = '__INDEX__';
			$postio_av_row = [ 'input' => '', 'output' => [] ];
			require __DIR__ . '/partials/mapping-row.php';
			?>
		</template>

		<details class="postio-av-advanced">
			<summary><?php esc_html_e( 'Advanced', 'postio-address-validation' ); ?></summary>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="postio-av-bundle-url"><?php esc_html_e( 'Bundle URL', 'postio-address-validation' ); ?></label></th>
					<td>
						<input type="url" class="regular-text" id="postio-av-bundle-url" name="<?php echo esc_attr( $postio_av_opt ); ?>[bundle_url]" value="<?php echo esc_attr( $settings['bundle_url'] ); ?>" placeholder="https://cdn.postio.co.uk/v1/address-finder.js" />
						<p class="description"><?php esc_html_e( 'Override the JS bundle URL. Leave blank for the default.', 'postio-address-validation' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="postio-av-api-url"><?php esc_html_e( 'API base URL', 'postio-address-validation' ); ?></label></th>
					<td>
						<input type="url" class="regular-text" id="postio-av-api-url" name="<?php echo esc_attr( $postio_av_opt ); ?>[api_url]" value="<?php echo esc_attr( $settings['api_url'] ); ?>" placeholder="https://api.postio.co.uk" />
						<p class="description"><?php esc_html_e( 'Override the validation API base URL. Leave blank for the default.', 'postio-address-validation' ); ?></p>
					</td>
				</tr>
			</table>
		</details>

		<?php submit_button(); ?>
	</form>

	<p class="postio-av-footer">
		<?php
		printf(
			wp_kses(
				/* translators: 1: docs link, 2: WordPress guide link, 3: support email link. */
				__( '%1$s · %2$s · %3$s', 'postio-address-validation' ),
				$postio_av_link_kses
			),
			'<a href="' . esc_url( Settings::URL_DOCS ) . '" target="_blank" rel="noopener">'
				. esc_html__( 'Documentation', 'postio-address-validation' ) . '</a>',
			'<a href="' . esc_url( Settings::URL_GUIDE ) . '" target="_blank" rel="noopener">'
				. esc_html__( 'WordPress guides', 'postio-address-validation' ) . '</a>',
			'<a href="mailto:' . esc_attr( Settings::SUPPORT_EMAIL ) . '">'
				. esc_html__( 'Support', 'postio-address-validation' ) . '</a>'
		);
		?>
	</p>
</div>
