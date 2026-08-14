<?php
/**
 * @package Postio\AddressValidation
 */

declare(strict_types=1);

namespace Postio\AddressValidation;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	/** @var Plugin|null */
	private static $instance = null;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		Settings::instance()->register();
		Assets::instance()->register();
		$this->register_detectors();

		add_action( 'init', [ $this, 'register_block' ] );
	}

	public function register_block(): void {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}
		register_block_type( POSTIO_AV_PATH . 'src/Block' );
	}

	/**
	 * Maps Settings::BUILDERS slug → detector class.
	 *
	 * @return array<string,class-string<Detectors\DetectorInterface>>
	 */
	public static function detector_registry(): array {
		return [
			'woocommerce'  => Detectors\WooCommerce::class,
			'gravityforms' => Detectors\GravityForms::class,
			'cf7'          => Detectors\ContactForm7::class,
			'wpforms'      => Detectors\WPForms::class,
			'fluentforms'  => Detectors\FluentForms::class,
			'ninjaforms'   => Detectors\NinjaForms::class,
			'forminator'   => Detectors\Forminator::class,
			'elementor'    => Detectors\ElementorForms::class,
		];
	}

	/**
	 * Which host builder plugins are actually present right now.
	 *
	 * Read by the settings page so the "Active builders" list can show real
	 * state rather than eight checkboxes that silently no-op. Safe to call
	 * any time after plugins_loaded.
	 *
	 * @return array<string,bool> Builder slug → host plugin is loaded.
	 */
	public static function detection_report(): array {
		$out = [];
		foreach ( self::detector_registry() as $slug => $class ) {
			/** @var Detectors\DetectorInterface $detector */
			$detector     = new $class();
			$out[ $slug ] = $detector->is_active();
		}
		return $out;
	}

	private function register_detectors(): void {
		// Each entry is registered only when (a) the toggle is on, (b) the
		// host builder plugin is actually active.
		foreach ( self::detector_registry() as $slug => $class ) {
			if ( ! Settings::is_builder_enabled( $slug ) ) {
				continue;
			}
			/** @var Detectors\DetectorInterface $detector */
			$detector = new $class();
			if ( ! $detector->is_active() ) {
				continue;
			}
			$detector->register();
		}

		// Custom mappings — not a per-builder toggle. Always registered if
		// the user has configured any mapping rows.
		$custom = new Detectors\CustomMappings();
		if ( $custom->is_active() ) {
			$custom->register();
		}
	}
}
