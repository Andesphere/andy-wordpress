<?php
/**
 * Settings → Andy Chat screen.
 *
 * @package AndyChat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const ANDY_CHAT_PAGE_SLUG = 'andy-chat';

/**
 * Signup URL with stable plugin attribution. No plugin-side analytics are involved.
 */
function andy_chat_signup_url(): string {
	return add_query_arg(
		array(
			'utm_source'   => 'wordpress-plugin',
			'utm_medium'   => 'plugin',
			'utm_campaign' => 'andy-chat',
			'utm_content'  => 'settings-page',
		),
		'https://app.andypartner.com/sign-up'
	);
}

/**
 * Registers the menu entry under Settings.
 */
function andy_chat_add_settings_page(): void {
	add_options_page(
		__( 'Andy Chat', 'andy-chat' ),
		__( 'Andy Chat', 'andy-chat' ),
		'manage_options',
		ANDY_CHAT_PAGE_SLUG,
		'andy_chat_render_settings_page'
	);
}
add_action( 'admin_menu', 'andy_chat_add_settings_page' );

/**
 * Registers sections and fields for the Settings API.
 */
function andy_chat_add_settings_fields(): void {
	add_settings_section(
		'andy_chat_disclosure',
		__( 'Before you enable the widget', 'andy-chat' ),
		'andy_chat_render_disclosure_section',
		ANDY_CHAT_PAGE_SLUG
	);

	add_settings_section(
		'andy_chat_connection',
		__( 'Connect your Agent', 'andy-chat' ),
		'andy_chat_render_connection_section',
		ANDY_CHAT_PAGE_SLUG
	);

	add_settings_field(
		'andy_chat_embed_id',
		__( 'Embed id', 'andy-chat' ),
		'andy_chat_render_embed_id_field',
		ANDY_CHAT_PAGE_SLUG,
		'andy_chat_connection',
		array( 'label_for' => 'andy_chat_embed_id' )
	);

	add_settings_field(
		'andy_chat_enabled',
		__( 'Widget', 'andy-chat' ),
		'andy_chat_render_enabled_field',
		ANDY_CHAT_PAGE_SLUG,
		'andy_chat_connection',
		array( 'label_for' => 'andy_chat_enabled' )
	);
}
add_action( 'admin_init', 'andy_chat_add_settings_fields' );

/**
 * Adds a Settings link on the Plugins screen row.
 *
 * @param string[] $links Existing action links.
 * @return string[]
 */
function andy_chat_plugin_action_links( array $links ): array {
	$url = admin_url( 'options-general.php?page=' . ANDY_CHAT_PAGE_SLUG );
	array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'andy-chat' ) . '</a>' );

	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( ANDY_CHAT_FILE ), 'andy_chat_plugin_action_links' );

/**
 * Service and data disclosure. Describes what the widget script really does.
 */
function andy_chat_render_disclosure_section(): void {
	$widget_host = wp_parse_url( ANDY_CHAT_WIDGET_URL, PHP_URL_HOST );
	?>
	<p>
		<?php
		printf(
			/* translators: %s: hostname of the Andy service */
			esc_html__( 'When the widget is on, every public page of this site loads a script from %s, a service run by Andesphere. Nothing is sent while the widget is off.', 'andy-chat' ),
			'<code>' . esc_html( (string) $widget_host ) . '</code>'
		);
		?>
	</p>
	<ul class="ul-disc">
		<li><?php esc_html_e( 'On page load the visitor\'s browser requests the widget script and your Agent\'s public configuration. Andy receives the visitor\'s IP address, browser details and this site\'s address as part of that request.', 'andy-chat' ); ?></li>
		<li><?php esc_html_e( 'When a visitor writes in the chat, the message text and a temporary session id are sent to Andy so your Agent can answer. If your Agent asks for a name and email, those are sent too.', 'andy-chat' ); ?></li>
		<li><?php esc_html_e( 'Andy keeps conversations while the Agent and Workspace exist, or until you delete them in Andy. Andy may keep technical logs for security and debugging. This plugin stores nothing about visitors and sends no analytics of its own.', 'andy-chat' ); ?></li>
		<li><?php esc_html_e( 'Turning the widget off, or deactivating this plugin, stops the script on the next page load. It does not delete conversations already stored in Andy; manage those from the Andy App.', 'andy-chat' ); ?></li>
	</ul>
	<p>
		<?php
		printf(
			/* translators: 1: privacy policy link, 2: terms link, 3: data retention policy link */
			esc_html__( 'Read the %1$s, the %2$s and the %3$s before enabling the widget.', 'andy-chat' ),
			'<a href="https://andypartner.com/legal/privacy" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Andy privacy policy', 'andy-chat' ) . '</a>',
			'<a href="https://andypartner.com/legal/terms" target="_blank" rel="noopener noreferrer">' . esc_html__( 'terms of service', 'andy-chat' ) . '</a>',
			'<a href="https://andypartner.com/legal/data-deletion" target="_blank" rel="noopener noreferrer">' . esc_html__( 'data retention and deletion policy', 'andy-chat' ) . '</a>'
		);
		?>
	</p>
	<?php
}

/**
 * Where to find the embed id, and the signup link for owners without an account.
 */
function andy_chat_render_connection_section(): void {
	?>
	<p>
		<?php esc_html_e( 'In the Andy App open your Agent, go to Installation and pick the HTML / Vanilla JavaScript tab. The value assigned to ANDY_CHATBOT_ID in that snippet is the public embed id. It is not a secret and no API key is needed.', 'andy-chat' ); ?>
	</p>
	<p>
		<?php
		printf(
			/* translators: %s: link to create an Andy account */
			esc_html__( 'No Andy account yet? %s', 'andy-chat' ),
			'<a href="' . esc_url( andy_chat_signup_url() ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Create your Andy account', 'andy-chat' ) . '</a>'
		);
		?>
	</p>
	<p>
		<?php
		printf(
			/* translators: %s: this site's origin, for example https://example.com */
			esc_html__( 'If your Agent restricts Allowed Origins in Andy, that list must include this site: %s', 'andy-chat' ),
			'<code>' . esc_html( andy_chat_site_origin() ) . '</code>'
		);
		?>
	</p>
	<?php
}

/**
 * The site's browser origin (scheme and host, plus port if any).
 */
function andy_chat_site_origin(): string {
	$parts  = wp_parse_url( home_url( '/' ) );
	$origin = ( $parts['scheme'] ?? 'https' ) . '://' . ( $parts['host'] ?? '' );
	if ( ! empty( $parts['port'] ) ) {
		$origin .= ':' . (int) $parts['port'];
	}

	return $origin;
}

/**
 * Embed id input.
 */
function andy_chat_render_embed_id_field(): void {
	$settings = andy_chat_get_settings();
	?>
	<input type="text" id="andy_chat_embed_id" name="<?php echo esc_attr( ANDY_CHAT_OPTION ); ?>[embed_id]" value="<?php echo esc_attr( $settings['embed_id'] ); ?>" class="regular-text code" maxlength="100" autocomplete="off" spellcheck="false" />
	<p class="description"><?php esc_html_e( 'Letters, digits, hyphens and underscores only. Example: k2f9x0w4d7mqzn1vp8yc3rh6t5ejab0g', 'andy-chat' ); ?></p>
	<?php
}

/**
 * Enabled checkbox.
 */
function andy_chat_render_enabled_field(): void {
	$settings = andy_chat_get_settings();
	?>
	<label for="andy_chat_enabled">
		<input type="checkbox" id="andy_chat_enabled" name="<?php echo esc_attr( ANDY_CHAT_OPTION ); ?>[enabled]" value="1" <?php checked( $settings['enabled'] ); ?> />
		<?php esc_html_e( 'Show the Andy widget on every public page', 'andy-chat' ); ?>
	</label>
	<p class="description"><?php esc_html_e( 'Leave this off until you have read the disclosure above. Activating the plugin alone never loads the widget.', 'andy-chat' ); ?></p>
	<?php
}

/**
 * Renders the page. Capability is checked again here even though the menu already gates it.
 */
function andy_chat_render_settings_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to change Andy Chat settings.', 'andy-chat' ), '', array( 'response' => 403 ) );
	}

	$settings = andy_chat_get_settings();
	if ( andy_chat_is_widget_active() ) {
		$status = __( 'The Andy widget is on. Visitors see it on every public page.', 'andy-chat' );
	} elseif ( '' === $settings['embed_id'] ) {
		$status = __( 'The Andy widget is off. Paste your embed id to get started.', 'andy-chat' );
	} else {
		$status = __( 'The Andy widget is off. Turn it on below when you are ready.', 'andy-chat' );
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Andy Chat', 'andy-chat' ); ?></h1>
		<p id="andy-chat-status"><strong><?php echo esc_html( $status ); ?></strong></p>
		<form action="options.php" method="post">
			<?php
			settings_fields( 'andy_chat' );
			do_settings_sections( ANDY_CHAT_PAGE_SLUG );
			submit_button( __( 'Save settings', 'andy-chat' ) );
			?>
		</form>
	</div>
	<?php
}
