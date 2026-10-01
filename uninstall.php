<?php
/**
 * Removes settings and any pending activation notice when deleted from wp-admin.
 *
 * @package AndyChat
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'andy_chat_settings' );
delete_transient( 'andy_chat_activation_notice' );
