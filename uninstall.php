<?php
/**
 * Removes plugin options on uninstall (not deactivate).
 *
 * @package Postio\AddressValidation
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'postio_av_settings' );
delete_transient( 'postio_av_activation_notice' );
