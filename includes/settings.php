<?php
/**
 * Option storage, defaults and validation.
 *
 * @package AndyChat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Embed ids are the public Agent ids accepted by the Andy widget: letters, digits, hyphen, underscore.
 * Bounds match andy-widget-core's isValidEmbedId().
 */
const ANDY_CHAT_EMBED_ID_PATTERN = '/^[A-Za-z0-9_-]{3,100}$/';

/**
 * Default option shape.
 *
 * @return array{embed_id: string, enabled: bool}
 */
function andy_chat_default_settings(): array {
	return array(
		'embed_id' => '',
		'enabled'  => false,
	);
}

/**
 * Returns the saved settings merged over defaults, with types normalized.
 *
 * @return array{embed_id: string, enabled: bool}
 */
function andy_chat_get_settings(): array {
	$saved = get_option( ANDY_CHAT_OPTION, array() );
	if ( ! is_array( $saved ) ) {
		$saved = array();
	}
	$settings = array_merge( andy_chat_default_settings(), $saved );

	return array(
		'embed_id' => is_string( $settings['embed_id'] ) ? $settings['embed_id'] : '',
		'enabled'  => (bool) $settings['enabled'],
	);
}

/**
 * Whether a string looks like a public embed id.
 *
 * @param string $embed_id Candidate id.
 */
function andy_chat_is_valid_embed_id( string $embed_id ): bool {
	return 1 === preg_match( ANDY_CHAT_EMBED_ID_PATTERN, $embed_id );
}

/**
 * True when the site is configured and switched on, so the widget should load on public pages.
 */
function andy_chat_is_widget_active(): bool {
	$settings = andy_chat_get_settings();

	return $settings['enabled'] && andy_chat_is_valid_embed_id( $settings['embed_id'] );
}

/**
 * Registers the single option. Sanitization runs through the Settings API on every save.
 */
function andy_chat_register_settings(): void {
	register_setting(
		'andy_chat',
		ANDY_CHAT_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'andy_chat_sanitize_settings',
			'default'           => andy_chat_default_settings(),
			'show_in_rest'      => false,
		)
	);
}
add_action( 'admin_init', 'andy_chat_register_settings' );

/**
 * Validates submitted settings.
 *
 * Three outcomes, and nothing in between:
 * - valid id: stored, toggle stored as submitted;
 * - empty id: stored as empty, toggle forced off (an error explains why when it was on);
 * - invalid non-empty id: the whole previously saved state comes back untouched, with an error.
 *   A typo never changes the stored id and never flips the widget on or off.
 *
 * @param mixed $input Raw submitted value.
 * @return array{embed_id: string, enabled: bool}
 */
function andy_chat_sanitize_settings( $input ): array {
	$current = andy_chat_get_settings();

	if ( ! current_user_can( 'manage_options' ) ) {
		return $current;
	}

	$input    = is_array( $input ) ? $input : array();
	$raw_id   = isset( $input['embed_id'] ) && is_string( $input['embed_id'] ) ? $input['embed_id'] : '';
	$embed_id = trim( sanitize_text_field( $raw_id ) );
	$enabled  = ! empty( $input['enabled'] );

	if ( '' !== $embed_id && ! andy_chat_is_valid_embed_id( $embed_id ) ) {
		add_settings_error(
			'andy_chat',
			'andy_chat_embed_id_invalid',
			__( 'That embed id is not valid, so nothing was changed. Copy it from the Installation tab of your Agent in the Andy App: it only contains letters, digits, hyphens and underscores.', 'andy-chat' )
		);

		return $current;
	}

	if ( '' === $embed_id && $enabled ) {
		add_settings_error(
			'andy_chat',
			'andy_chat_embed_id_missing',
			__( 'Enter your Agent\'s embed id before enabling the widget. The widget stays off.', 'andy-chat' )
		);
	}

	$output = array(
		'embed_id' => $embed_id,
		// Same invariant as andy_chat_is_widget_active(): the widget cannot be on without a valid stored id.
		'enabled'  => $enabled && andy_chat_is_valid_embed_id( $embed_id ),
	);

	if ( empty( get_settings_errors( 'andy_chat' ) ) ) {
		add_settings_error(
			'andy_chat',
			'andy_chat_saved',
			$output['enabled']
				? __( 'Settings saved. The Andy widget is on for every public page.', 'andy-chat' )
				: __( 'Settings saved. The Andy widget is off.', 'andy-chat' ),
			'success'
		);
	}

	return $output;
}
