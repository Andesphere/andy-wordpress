<?php
/**
 * Removes the plugin's only option when the plugin is deleted from wp-admin.
 *
 * @package AndyChat
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'andy_chat_settings' );
