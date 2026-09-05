<?php
/**
 * Loads the Andy widget on public pages.
 *
 * @package AndyChat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueues https://app.andypartner.com/widget.js once, with the same globals the vanilla snippet sets.
 *
 * wp_enqueue_scripts only fires for the public front end, so wp-admin, the login screen and feeds never load it.
 */
function andy_chat_enqueue_widget(): void {
	if ( ! andy_chat_is_widget_active() ) {
		return;
	}

	$settings = andy_chat_get_settings();

	wp_enqueue_script(
		'andy-chat-widget',
		ANDY_CHAT_WIDGET_URL,
		array(),
		null, // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- remote script, no cache-busting query.
		array(
			'in_footer' => false,
			'strategy'  => 'async',
		)
	);

	wp_add_inline_script(
		'andy-chat-widget',
		sprintf(
			'window.ANDY_CHATBOT_ID = %s; window.ANDY_CHAT_API_URL = %s;',
			wp_json_encode( $settings['embed_id'], JSON_UNESCAPED_SLASHES ),
			wp_json_encode( ANDY_CHAT_API_URL, JSON_UNESCAPED_SLASHES )
		),
		'before'
	);
}
add_action( 'wp_enqueue_scripts', 'andy_chat_enqueue_widget' );
