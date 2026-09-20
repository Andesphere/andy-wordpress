<?php
/**
 * Plugin Name:       Andy Chat
 * Plugin URI:        https://github.com/Andesphere/andy-wordpress
 * Description:       Adds your Andy AI Agent's chat widget to every public page of your site.
 * Version:           0.1.0
 * Requires at least: 7.1
 * Requires PHP:      8.1
 * Author:            Andesphere
 * Author URI:        https://andypartner.com/
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       andy-chat
 *
 * @package AndyChat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ANDY_CHAT_VERSION', '0.1.0' );
define( 'ANDY_CHAT_FILE', __FILE__ );
define( 'ANDY_CHAT_OPTION', 'andy_chat_settings' );
define( 'ANDY_CHAT_WIDGET_URL', 'https://app.andypartner.com/widget.js' );
define( 'ANDY_CHAT_API_URL', 'https://app.andypartner.com/api' );

require_once __DIR__ . '/includes/settings.php';
require_once __DIR__ . '/includes/admin-page.php';
require_once __DIR__ . '/includes/widget.php';
