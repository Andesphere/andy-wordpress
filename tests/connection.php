<?php
/**
 * Runs against an isolated installed WordPress, including its Settings API and script registry.
 *
 * @package AndyChat
 */

require '/wordpress/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-admin/includes/template.php';

$checks = 0;
function expect_same( $expected, $actual, string $label ): void {
	global $checks;
	++$checks;
	if ( $expected !== $actual ) {
		throw new RuntimeException( $label . ': ' . var_export( $actual, true ) );
	}
}
function rendered( callable $callback ): string {
	ob_start();
	$callback();
	return ob_get_clean();
}

$owner = get_user_by( 'login', 'admin' );
expect_same( true, $owner instanceof WP_User, 'Isolated WordPress administrator exists' );
wp_set_current_user( $owner->ID );
$before = get_option( 'andy_chat_settings', null );
$result = activate_plugin( 'andy-chat/andy-chat.php' );
expect_same( null, $result, 'Native plugin activation succeeds' );
expect_same( $before, get_option( ANDY_CHAT_OPTION, null ), 'Activation preserves saved settings' );
expect_same( true, get_transient( 'andy_chat_activation_notice' ), 'Activation offers settings once' );
do_action( 'admin_init' );

foreach ( array( 'en_US' => '/en', 'es_ES' => '', 'es_MX' => '', 'de_DE' => '/en' ) as $language => $prefix ) {
	update_user_meta( $owner->ID, 'locale', $language );
	foreach ( array( false => '/sign-up', true => '/sign-in' ) as $existing => $path ) {
		$url = wp_parse_url( andy_chat_setup_url( (bool) $existing ) );
		expect_same( 'https', $url['scheme'], $language . ' destination scheme' );
		expect_same( 'app.andypartner.com', $url['host'], $language . ' fixed destination' );
		expect_same( $prefix . $path, $url['path'], $language . ' owner entry path' );
		parse_str( $url['query'], $query );
		expect_same(
			array(
				'platform' => 'wordpress',
				'utm_source' => 'wordpress-plugin',
				'utm_medium' => 'plugin',
				'utm_campaign' => 'andy-chat',
				'utm_content' => 'settings-page',
				'site_url' => home_url( '/' ),
				'return_to' => admin_url( 'options-general.php?page=andy-chat' ),
			),
			$query,
			$language . ' exact once-decoded context, attribution and no secrets'
		);
	}
}
update_user_meta( $owner->ID, 'locale', 'en_US' );

foreach ( array( 'https://user:secret@example.test/', 'https://example.test/?token=secret', 'https://example.test/#private', 'javascript:alert(1)' ) as $unsafe ) {
	$filter = static function () use ( $unsafe ): string { return $unsafe; };
	add_filter( 'home_url', $filter );
	parse_str( wp_parse_url( andy_chat_setup_url(), PHP_URL_QUERY ), $query );
	expect_same( false, isset( $query['site_url'] ), 'Unsafe public-site hint omitted' );
	expect_same( 'wordpress', $query['platform'], 'Generic WordPress account entry stays usable' );
	remove_filter( 'home_url', $filter );
}
$unsafe_return = static function (): string { return 'https://example.test/wp-admin/options-general.php?page=andy-chat&nonce=secret'; };
add_filter( 'admin_url', $unsafe_return );
parse_str( wp_parse_url( andy_chat_setup_url( true ), PHP_URL_QUERY ), $query );
expect_same( false, isset( $query['return_to'] ), 'Unexpected return fields omitted' );
remove_filter( 'admin_url', $unsafe_return );

$subscriber_id = wp_insert_user( array( 'user_login' => 'plugin-role-check', 'user_pass' => wp_generate_password(), 'role' => 'subscriber' ) );
expect_same( true, is_int( $subscriber_id ), 'Isolated lower-role fixture created' );
$saved = array( 'embed_id' => 'existing_agent', 'enabled' => false );
update_option( ANDY_CHAT_OPTION, $saved );
wp_set_current_user( $subscriber_id );
expect_same( '', rendered( 'andy_chat_activation_notice' ), 'Lower role cannot see notice' );
expect_same( true, get_transient( 'andy_chat_activation_notice' ), 'Lower role cannot consume owner notice' );
expect_same( $saved, andy_chat_sanitize_settings( array( 'embed_id' => 'other_agent', 'enabled' => 1 ) ), 'Lower role cannot replace ID or enable widget' );
$die_handler = static function (): callable {
	return static function ( $message, $title, $args ): void { throw new RuntimeException( 'permission:' . $args['response'] ); };
};
add_filter( 'wp_die_handler', $die_handler );
try {
	andy_chat_render_settings_page();
	throw new RuntimeException( 'Lower role accessed settings' );
} catch ( RuntimeException $error ) {
	expect_same( 'permission:403', $error->getMessage(), 'Settings render enforces capability' );
}
remove_filter( 'wp_die_handler', $die_handler );
wp_set_current_user( $owner->ID );
$notice = rendered( 'andy_chat_activation_notice' );
expect_same( true, str_contains( $notice, 'notice notice-info is-dismissible' ), 'Activation uses native dismissible notice' );
expect_same( true, str_contains( html_entity_decode( $notice ), admin_url( 'options-general.php?page=andy-chat' ) ), 'Notice targets actual settings' );
expect_same( '', rendered( 'andy_chat_activation_notice' ), 'Notice shown once' );
expect_same( $saved, andy_chat_get_settings(), 'Notice preserves ID and toggle' );

$html = rendered( 'andy_chat_render_settings_page' );
$document = new DOMDocument();
@$document->loadHTML( $html );
$xpath = new DOMXPath( $document );
$nonce = $xpath->query( '//input[@name="_wpnonce"]' )->item( 0 )->getAttribute( 'value' );
expect_same( 1, wp_verify_nonce( $nonce, 'andy_chat-options' ), 'Native Settings API nonce is valid' );
expect_same( false, wp_verify_nonce( 'invalid', 'andy_chat-options' ), 'Invalid settings nonce is rejected' );
expect_same( 'existing_agent', $xpath->query( '//input[@id="andy_chat_embed_id"]' )->item( 0 )->getAttribute( 'value' ), 'Settings render saved ID' );
expect_same( 0, $xpath->query( '//input[@id="andy_chat_enabled"]/@checked' )->length, 'Settings render disabled toggle' );
expect_same( 1, $xpath->query( '//a[@href="#andy_chat_embed_id"]' )->length, 'Existing-ID path stays local' );
expect_same( true, strpos( $html, 'Before you enable the widget' ) < strpos( $html, 'id="andy_chat_enabled"' ), 'Disclosure precedes enablement' );

expect_same( $saved, andy_chat_sanitize_settings( array( 'embed_id' => 'invalid?', 'enabled' => 1 ) ), 'Invalid ID preserves entire saved state' );
expect_same( array( 'embed_id' => '', 'enabled' => false ), andy_chat_sanitize_settings( array( 'embed_id' => '', 'enabled' => 1 ) ), 'Empty ID cannot enable widget' );
expect_same( array( 'embed_id' => 'new_agent', 'enabled' => true ), andy_chat_sanitize_settings( array( 'embed_id' => ' new_agent ', 'enabled' => 1 ) ), 'Valid ID and explicit enablement accepted' );

andy_chat_enqueue_widget();
expect_same( false, wp_script_is( 'andy-chat-widget', 'enqueued' ), 'Saved disabled widget loads nothing' );
update_option( ANDY_CHAT_OPTION, array( 'embed_id' => 'existing_agent', 'enabled' => true ) );
andy_chat_enqueue_widget();
expect_same( 'https://app.andypartner.com/widget.js', wp_scripts()->registered['andy-chat-widget']->src, 'Production widget destination unchanged' );
$public_scripts = rendered( 'wp_print_head_scripts' );
expect_same( true, str_contains( $public_scripts, 'window.ANDY_CHATBOT_ID = "existing_agent"; window.ANDY_CHAT_API_URL = "https://app.andypartner.com/api";' ), 'Public ID and production API globals unchanged' );
andy_chat_enqueue_settings_assets( 'settings_page_andy-chat' );
$admin_scripts = rendered( 'wp_print_footer_scripts' );
preg_match( '/window\.andyChatAccess = (\{.*\});/', $admin_scripts, $matches );
$config = json_decode( $matches[1], true, 512, JSON_THROW_ON_ERROR );
expect_same( 'https://app.andypartner.com/api/chatbot/', $config['endpoint'], 'Public access endpoint unchanged' );
expect_same( 'existing_agent', $config['savedId'], 'Access reports saved ID' );
expect_same( true, $config['enabled'], 'Access reports saved enablement without mutation' );
parse_str( substr( $config['query'], 1 ), $attribution );
expect_same( array( 'source' => 'wordpress-plugin', 'plugin_version' => '0.1.2' ), $attribution, 'Access attribution contains no secret fields' );

deactivate_plugins( 'andy-chat/andy-chat.php' );
expect_same( array( 'embed_id' => 'existing_agent', 'enabled' => true ), andy_chat_get_settings(), 'Deactivation preserves saved settings' );
activate_plugin( 'andy-chat/andy-chat.php' );
expect_same( array( 'embed_id' => 'existing_agent', 'enabled' => true ), andy_chat_get_settings(), 'Reactivation preserves saved settings' );

echo 'PASS: ' . $checks . " actual WordPress assertions\n";
