<?php
/**
 * @package Postio\AddressValidation
 */

declare(strict_types=1);

namespace Postio\AddressValidation\Detectors;

defined( 'ABSPATH' ) || exit;

/**
 * Contract for per-builder integration classes.
 *
 * Lifecycle: Plugin::register_detectors() instantiates each entry in the
 * registry, calls is_active() to confirm the host plugin is loaded, then
 * register() to hook into WordPress. The detector itself is responsible
 * for deciding when to push setups onto Assets (typically at
 * wp_enqueue_scripts, but block-based builders may use later filters).
 */
interface DetectorInterface {

	/** Stable slug — matches a key in Settings::BUILDERS. */
	public function slug(): string;

	/** True when the host plugin (the form builder) is actually loaded. */
	public function is_active(): bool;

	/** Hook into the WordPress lifecycle. Idempotent. */
	public function register(): void;
}
