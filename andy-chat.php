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
 * Domain Path:       /languages
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

/**
 * Loads bundled translations. Any Spanish locale (es_CL, es_MX, ...) reads the bundled es_ES catalog
 * until translate.wordpress.org supplies its own.
 */
function andy_chat_load_textdomain(): void {
	add_filter(
		'load_textdomain_mofile',
		static function ( string $mofile, string $domain ): string {
			if ( 'andy-chat' === $domain && 1 === preg_match( '#/andy-chat-es_[A-Z]{2}\.mo$#', $mofile ) ) {
				return preg_replace( '#es_[A-Z]{2}\.mo$#', 'es_ES.mo', $mofile );
			}
			return $mofile;
		},
		10,
		2
	);
	load_plugin_textdomain( 'andy-chat', false, dirname( plugin_basename( ANDY_CHAT_FILE ) ) . '/languages' );
}
add_action( 'init', 'andy_chat_load_textdomain' );
