<?php
/**
 * Plugin Name:       Andy Partner
 * Plugin URI:        https://github.com/Andesphere/andy-wordpress
 * Description:       Puts your Andy Partner AI agent on every public page of your site, so visitors get answers and you get the leads.
 * Version:           0.1.1
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

define( 'ANDY_CHAT_VERSION', '0.1.1' );
define( 'ANDY_CHAT_FILE', __FILE__ );
define( 'ANDY_CHAT_OPTION', 'andy_chat_settings' );
define( 'ANDY_CHAT_WIDGET_URL', 'https://app.andypartner.com/widget.js' );
define( 'ANDY_CHAT_API_URL', 'https://app.andypartner.com/api' );

require_once __DIR__ . '/includes/settings.php';
require_once __DIR__ . '/includes/admin-page.php';
require_once __DIR__ . '/includes/widget.php';
