<?php
/**
 * Plugin Name:       Postio Address Validation
 * Plugin URI:        https://postio.co.uk/integrations/wordpress
 * Description:       UK address autocomplete for WooCommerce and the major form builders. Powered by the Postio validation API.
 * Version:           0.2.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Postio
 * Author URI:        https://postio.co.uk
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       postio-address-validation
 *
 * @package Postio\AddressValidation
 */

defined( 'ABSPATH' ) || exit;

define( 'POSTIO_AV_VERSION', '0.2.0' );
define( 'POSTIO_AV_FILE', __FILE__ );
define( 'POSTIO_AV_PATH', plugin_dir_path( __FILE__ ) );
define( 'POSTIO_AV_URL', plugin_dir_url( __FILE__ ) );
define( 'POSTIO_AV_BASENAME', plugin_basename( __FILE__ ) );

spl_autoload_register(
	static function ( $class ) {
		$prefix = 'Postio\\AddressValidation\\';
		$len    = strlen( $prefix );

		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
			return;
		}

		$relative = substr( $class, $len );
		$path     = POSTIO_AV_PATH . 'src/' . str_replace( '\\', '/', $relative ) . '.php';

		if ( file_exists( $path ) ) {
			require_once $path;
		}
	}
);

add_action(
	'plugins_loaded',
	static function () {
		\Postio\AddressValidation\Plugin::instance();
	}
);

register_activation_hook(   __FILE__, [ \Postio\AddressValidation\Settings::class,  'on_activation'   ] );
