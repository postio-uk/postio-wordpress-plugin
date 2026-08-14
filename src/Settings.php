<?php
/**
 * @package Postio\AddressValidation
 */

declare(strict_types=1);

namespace Postio\AddressValidation;

defined( 'ABSPATH' ) || exit;

final class Settings {

	public const OPTION_KEY    = 'postio_av_settings';
	public const MENU_SLUG     = 'postio-address-validation';
	public const NONCE_TEST    = 'postio_av_test_connection';
	public const NONCE_DISMISS = 'postio_av_dismiss_notice';

	/** Transient set on activation; drives the one-time "add your key" notice. */
	public const NOTICE_TRANSIENT = 'postio_av_activation_notice';

	public const BUILDERS = [
		'woocommerce'  => 'WooCommerce',
		'gravityforms' => 'Gravity Forms',
		'cf7'          => 'Contact Form 7',
		'wpforms'      => 'WPForms',
		'fluentforms'  => 'Fluent Forms',
		'ninjaforms'   => 'Ninja Forms',
		'forminator'   => 'Forminator',
		'elementor'    => 'Elementor Forms',
	];

	public const OUTPUT_FIELDS = [
		'address_line_1' => 'Line 1',
		'address_line_2' => 'Line 2',
		'post_town'      => 'Town',
		'postcode'       => 'Postcode',
		'country'        => 'Country',
	];

	public const URL_SIGNUP  = 'https://postio.co.uk/signup';
	public const URL_DOCS    = 'https://postio.co.uk/docs';
	public const URL_GUIDE   = 'https://postio.co.uk/integrations/wordpress';
	public const SUPPORT_EMAIL = 'admin@postio.co.uk';

	/**
	 * Path segment under URL_GUIDE for each builder's own guide page.
	 *
	 * Deliberately not derived from the BUILDERS keys — the site uses full
	 * readable slugs (`contact-form-7`) where we use short internal ones
	 * (`cf7`). Keeping the mapping explicit is what made moving these from
	 * fragments to separate pages a one-line change.
	 */
	public const GUIDE_PATHS = [
		'woocommerce'  => 'woocommerce',
		'gravityforms' => 'gravity-forms',
		'cf7'          => 'contact-form-7',
		'wpforms'      => 'wpforms',
		'fluentforms'  => 'fluent-forms',
		'ninjaforms'   => 'ninja-forms',
		'forminator'   => 'forminator',
		'elementor'    => 'elementor',
	];

	/**
	 * WPForms' Address field is a Pro feature. On Lite it appears in the
	 * builder greyed out behind an upgrade prompt and is never registered,
	 * so there is nothing for our detector to find — even though
	 * WPFORMS_VERSION is defined and the detector reports active.
	 *
	 * Without this the settings page tells a Lite user their forms are
	 * "wired automatically" and nothing ever happens.
	 */
	public static function wpforms_is_pro(): bool {
		if ( ! function_exists( 'wpforms' ) ) {
			return defined( 'WPFORMS_PRO' ) && WPFORMS_PRO;
		}
		$wpforms = wpforms();
		return is_object( $wpforms )
			&& method_exists( $wpforms, 'is_pro' )
			&& (bool) $wpforms->is_pro();
	}

	/**
	 * Guide URL for a builder, or the index when the slug is unknown.
	 *
	 * URL_GUIDE stays the index — what the plugin is, how it installs, the
	 * settings screen, custom mappings — and each builder now has its own
	 * page beneath it rather than a fragment on the index.
	 */
	public static function guide_url( string $slug = '' ): string {
		$path = self::GUIDE_PATHS[ $slug ] ?? '';
		return '' === $path ? self::URL_GUIDE : self::URL_GUIDE . '/' . $path;
	}

	/**
	 * Builders with no native address field. Detection alone isn't enough —
	 * the user has to tag their own fields with our classes before anything
	 * fires. The settings page surfaces the exact snippet for these.
	 */
	public const MANUAL_BUILDERS = [ 'cf7', 'ninjaforms', 'elementor' ];

	/**
	 * Where to put the opt-in classes, per manual builder.
	 *
	 * @return array<string,array{where:string,classes:array<int,string>,note:string}>
	 */
	public static function wiring_help(): array {
		$common = [
			'postio-line-1',
			'postio-line-2',
			'postio-town',
			'postio-postcode',
		];

		return [
			'cf7'        => [
				'where'   => __( 'Add a class to each address field shortcode in your form.', 'postio-address-validation' ),
				'classes' => [
					'[text* line-1 class:postio-line-1]',
					'[text line-2 class:postio-line-2]',
					'[text town class:postio-town]',
					'[text postcode class:postio-postcode]',
				],
				'note'    => __( 'Only postio-line-1 is required. Add [text address-search class:postio-search] above them for a separate lookup box; without it, line 1 doubles as the search input.', 'postio-address-validation' ),
			],
			'ninjaforms' => [
				'where'   => __( 'Edit each field, open Display, and add to Element Class (Display CSS Classes).', 'postio-address-validation' ),
				'classes' => $common,
				'note'    => __( 'Only postio-line-1 is required. Add postio-search to a separate lookup field if you want one; without it, line 1 doubles as the search input.', 'postio-address-validation' ),
			],
			'elementor'  => [
				'where'   => __( 'Edit each form field, open Advanced, and add to CSS Classes.', 'postio-address-validation' ),
				'classes' => $common,
				'note'    => __( 'Elementor Forms is part of Elementor Pro — the free Elementor has no Form widget. Only postio-line-1 is required; add postio-search for a separate lookup field.', 'postio-address-validation' ),
			],
		];
	}

	/** @var Settings|null */
	private static $instance = null;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public static function defaults(): array {
		return [
			'api_key'         => '',
			'auto_detect'     => true,
			'builders'        => array_fill_keys( array_keys( self::BUILDERS ), true ),
			'custom_mappings' => [],
			'bundle_url'      => '',
			'api_url'         => '',
		];
	}

	/**
	 * @param string|null $key Single key to fetch, or null for the full settings array.
	 * @return mixed
	 */
	public static function get( ?string $key = null ) {
		$stored = get_option( self::OPTION_KEY, [] );
		$merged = array_replace_recursive(
			self::defaults(),
			is_array( $stored ) ? $stored : []
		);

		if ( null === $key ) {
			return $merged;
		}
		return $merged[ $key ] ?? null;
	}

	public static function api_key(): string {
		return (string) self::get( 'api_key' );
	}

	/**
	 * Value for the `x-postio-client` request header — a package name and
	 * version, nothing site-specific.
	 */
	public static function client_id(): string {
		return 'postio-wordpress/' . POSTIO_AV_VERSION;
	}

	public static function is_builder_enabled( string $slug ): bool {
		if ( ! self::get( 'auto_detect' ) ) {
			return false;
		}
		$builders = self::get( 'builders' );
		return ! empty( $builders[ $slug ] );
	}

	/** Version of @postio/address-finder-bundled shipped in assets/. */
	public const BUNDLE_VERSION = '1.1.0';

	/**
	 * The address finder script is served from the plugin, not a CDN.
	 *
	 * WordPress.org does not accept plugins that download executable code
	 * from external sources, so assets/address-finder.js is shipped in the
	 * package. It is byte-identical to what cdn.postio.co.uk serves, MIT
	 * licensed, and its full original TypeScript is embedded in the
	 * accompanying source map.
	 *
	 * The setting and the `postio_av_bundle_url` filter still allow an
	 * override — useful for pointing at the CDN or a staging build — but
	 * nothing remote is loaded by default.
	 */
	public static function bundle_url(): string {
		$url = (string) self::get( 'bundle_url' );
		if ( '' === $url ) {
			$url = POSTIO_AV_URL . 'assets/address-finder.js';
		}
		return (string) apply_filters( 'postio_av_bundle_url', $url );
	}

	public static function api_url(): string {
		$url = (string) self::get( 'api_url' );
		if ( '' === $url ) {
			$url = 'https://api.postio.co.uk';
		}
		return (string) apply_filters( 'postio_av_api_url', $url );
	}

	private function __construct() {}

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'add_menu' ] );
		add_action( 'admin_init', [ $this, 'register_setting' ] );
		add_action( 'admin_init', [ $this, 'maybe_dismiss_notice' ] );
		add_action( 'admin_notices', [ $this, 'render_activation_notice' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
		add_action( 'wp_ajax_postio_av_test_connection', [ $this, 'ajax_test_connection' ] );
		add_filter( 'plugin_action_links_' . POSTIO_AV_BASENAME, [ $this, 'action_links' ] );
	}

	public static function page_url(): string {
		return admin_url( 'options-general.php?page=' . self::MENU_SLUG );
	}

	/** Arm the one-time "add your key" notice. Wired to register_activation_hook. */
	public static function on_activation(): void {
		if ( '' === self::api_key() ) {
			set_transient( self::NOTICE_TRANSIENT, 1, MONTH_IN_SECONDS );
		}
	}

	/**
	 * "Settings" next to Activate / Deactivate on the Plugins screen.
	 *
	 * @param array<int,string> $links Existing action links.
	 * @return array<int,string>
	 */
	public function action_links( $links ): array {
		$links = is_array( $links ) ? $links : [];

		array_unshift(
			$links,
			sprintf(
				'<a href="%s">%s</a>',
				esc_url( self::page_url() ),
				esc_html__( 'Settings', 'postio-address-validation' )
			)
		);

		return $links;
	}

	/** Handle the notice's Dismiss link. */
	public function maybe_dismiss_notice(): void {
		if ( empty( $_GET['postio_av_dismiss'] ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['_wpnonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, self::NONCE_DISMISS ) ) {
			return;
		}

		delete_transient( self::NOTICE_TRANSIENT );

		wp_safe_redirect( remove_query_arg( [ 'postio_av_dismiss', '_wpnonce' ] ) );
		exit;
	}

	/**
	 * One-time post-activation nudge. Self-clears the moment a key is saved,
	 * so a configured site never sees it again even if the transient lingers.
	 */
	public function render_activation_notice(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! get_transient( self::NOTICE_TRANSIENT ) ) {
			return;
		}
		if ( '' !== self::api_key() ) {
			delete_transient( self::NOTICE_TRANSIENT );
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && 'settings_page_' . self::MENU_SLUG === $screen->id ) {
			// They're already here — the page speaks for itself.
			return;
		}

		$dismiss = wp_nonce_url(
			add_query_arg( 'postio_av_dismiss', '1' ),
			self::NONCE_DISMISS
		);
		?>
		<div class="notice notice-info">
			<p>
				<strong><?php esc_html_e( 'Postio Address Validation is active.', 'postio-address-validation' ); ?></strong>
				<?php esc_html_e( 'Add your API key to switch on address autocomplete — signing up is free and takes under a minute.', 'postio-address-validation' ); ?>
			</p>
			<p>
				<a href="<?php echo esc_url( self::page_url() ); ?>" class="button button-primary">
					<?php esc_html_e( 'Add API key', 'postio-address-validation' ); ?>
				</a>
				<a href="<?php echo esc_url( $dismiss ); ?>" class="button button-secondary">
					<?php esc_html_e( 'Dismiss', 'postio-address-validation' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	public function add_menu(): void {
		add_options_page(
			__( 'Postio', 'postio-address-validation' ),
			__( 'Postio', 'postio-address-validation' ),
			'manage_options',
			self::MENU_SLUG,
			[ $this, 'render_page' ]
		);
	}

	public function register_setting(): void {
		register_setting(
			'postio_av',
			self::OPTION_KEY,
			[
				'type'              => 'array',
				'sanitize_callback' => [ $this, 'sanitize' ],
				'default'           => self::defaults(),
			]
		);
	}

	/**
	 * @param mixed $input
	 */
	public function sanitize( $input ): array {
		$defaults = self::defaults();

		if ( ! is_array( $input ) ) {
			return $defaults;
		}

		$out = $defaults;

		if ( isset( $input['api_key'] ) ) {
			$key = preg_replace( '/[^\x20-\x7E]/', '', (string) $input['api_key'] );
			$out['api_key'] = substr( trim( (string) $key ), 0, 200 );
		}

		$out['auto_detect'] = ! empty( $input['auto_detect'] );

		foreach ( array_keys( self::BUILDERS ) as $slug ) {
			$out['builders'][ $slug ] = ! empty( $input['builders'][ $slug ] );
		}

		$mappings = [];
		if ( ! empty( $input['custom_mappings'] ) && is_array( $input['custom_mappings'] ) ) {
			foreach ( $input['custom_mappings'] as $row ) {
				if ( ! is_array( $row ) || empty( $row['input'] ) ) {
					continue;
				}
				$output = [];
				foreach ( array_keys( self::OUTPUT_FIELDS ) as $field ) {
					$sel = isset( $row['output'][ $field ] ) ? trim( (string) $row['output'][ $field ] ) : '';
					if ( '' !== $sel ) {
						$output[ $field ] = $this->sanitize_selector( $sel );
					}
				}
				if ( empty( $output ) ) {
					continue;
				}
				$mappings[] = [
					'input'  => $this->sanitize_selector( (string) $row['input'] ),
					'output' => $output,
				];
			}
		}
		$out['custom_mappings'] = $mappings;

		$out['bundle_url'] = isset( $input['bundle_url'] )
			? esc_url_raw( trim( (string) $input['bundle_url'] ) )
			: '';
		$out['api_url'] = isset( $input['api_url'] )
			? esc_url_raw( trim( (string) $input['api_url'] ) )
			: '';

		return $out;
	}

	private function sanitize_selector( string $selector ): string {
		$selector = trim( $selector );
		$selector = (string) preg_replace( '/[<>"\']/', '', $selector );
		return substr( $selector, 0, 200 );
	}

	public function enqueue_admin_assets( string $hook ): void {
		if ( 'settings_page_' . self::MENU_SLUG !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'postio-av-admin',
			POSTIO_AV_URL . 'assets/admin.css',
			[],
			POSTIO_AV_VERSION
		);

		wp_enqueue_script(
			'postio-av-admin',
			POSTIO_AV_URL . 'assets/admin.js',
			[],
			POSTIO_AV_VERSION,
			true
		);

		wp_localize_script(
			'postio-av-admin',
			'PostioAVAdmin',
			[
				'ajax_url'      => admin_url( 'admin-ajax.php' ),
				'nonce'         => wp_create_nonce( self::NONCE_TEST ),
				'option_key'    => self::OPTION_KEY,
				'output_fields' => self::OUTPUT_FIELDS,
				'i18n'          => [
					'test_busy'     => __( 'Testing…', 'postio-address-validation' ),
					'test_fail'     => __( 'Connection failed.', 'postio-address-validation' ),
					'test_empty'    => __( 'Paste a key first.', 'postio-address-validation' ),
					'test_unsaved'  => __( 'Not saved yet — press Save key to use it.', 'postio-address-validation' ),
					'remove_row'    => __( 'Remove', 'postio-address-validation' ),
				],
			]
		);
	}

	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = self::get();
		$detected = Plugin::detection_report();
		$wiring   = self::wiring_help();
		require POSTIO_AV_PATH . 'src/views/settings-page.php';
	}

	public function ajax_test_connection(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				[ 'message' => __( 'Forbidden.', 'postio-address-validation' ) ],
				403
			);
		}

		check_ajax_referer( self::NONCE_TEST, 'nonce' );

		$key = isset( $_POST['api_key'] )
			? sanitize_text_field( wp_unslash( (string) $_POST['api_key'] ) )
			: '';

		if ( '' === $key ) {
			wp_send_json_error(
				[ 'message' => __( 'No API key supplied.', 'postio-address-validation' ) ],
				400
			);
		}

		// Pass the site's home URL as Origin so a key with allowedUrls set
		// (which is the typical browser-bundle deployment) accepts the
		// server-side probe. Without this header the API rejects with
		// origin_required even for legitimate users.
		$response = wp_remote_get(
			trailingslashit( self::api_url() ) . 'v1/connect',
			[
				'timeout' => 10,
				'headers' => [
					'x-api-key' => $key,
					'accept'    => 'application/json',
					'origin'    => home_url(),
					// Identify the integration. This covers the server-side
					// probe only; the customer-facing searches are issued by
					// the browser bundle, which currently has no way to send
					// this. See CLIENT_ID in @postio/core.
					'x-postio-client' => self::client_id(),
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( [ 'message' => $response->get_error_message() ], 502 );
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		if ( 200 === $code && is_array( $body ) && ! empty( $body['success'] ) ) {
			wp_send_json_success(
				[
					'message'   => __( 'Connected — key is active.', 'postio-address-validation' ),
					'requestId' => $body['meta']['requestId'] ?? null,
				]
			);
		}

		$error = is_array( $body ) && ! empty( $body['error'] )
			? (string) $body['error']
			: 'unknown_error';

		wp_send_json_error(
			[
				'message' => sprintf(
					/* translators: %1$d: HTTP status, %2$s: error code from API. */
					__( 'Failed (%1$d): %2$s', 'postio-address-validation' ),
					$code,
					$error
				),
			]
		);
	}
}
