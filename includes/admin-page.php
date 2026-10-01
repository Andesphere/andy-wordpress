<?php
/**
 * Settings → Andy Partner screen.
 *
 * @package AndyChat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const ANDY_CHAT_PAGE_SLUG = 'andy-chat';

/**
 * Account entry with advisory installation context and stable attribution.
 */
function andy_chat_setup_url( bool $existing_account = false ): string {
	$locale = get_user_locale();
	$prefix = 0 === strpos( strtolower( $locale ), 'es' ) ? '' : '/en';
	$query  = array(
		'platform'     => 'wordpress',
		'utm_source'   => 'wordpress-plugin',
		'utm_medium'   => 'plugin',
		'utm_campaign' => 'andy-chat',
		'utm_content'  => 'settings-page',
	);
	$urls = array(
		'site_url'  => home_url( '/' ),
		'return_to' => admin_url( 'options-general.php?page=' . ANDY_CHAT_PAGE_SLUG ),
	);
	foreach ( $urls as $key => $url ) {
		$parts = wp_parse_url( $url );
		// Never put credentials, fragments or extra query fields into the handoff.
		if ( is_array( $parts ) && ! empty( $parts['host'] ) &&
			in_array( $parts['scheme'] ?? '', array( 'http', 'https' ), true ) &&
			! isset( $parts['user'] ) && ! isset( $parts['pass'] ) &&
			! isset( $parts['fragment'] ) &&
			( 'site_url' === $key ? ! isset( $parts['query'] ) : ( $parts['query'] ?? '' ) === 'page=' . ANDY_CHAT_PAGE_SLUG ) ) {
			$query[ $key ] = rawurlencode( $url );
		}
	}

	return add_query_arg( $query, 'https://app.andypartner.com' . $prefix . ( $existing_account ? '/sign-in' : '/sign-up' ) );
}

/** Activation changes no widget setting; offer its next task once to an administrator. */
function andy_chat_note_activation(): void {
	set_transient( 'andy_chat_activation_notice', true, 10 * MINUTE_IN_SECONDS );
}
register_activation_hook( ANDY_CHAT_FILE, 'andy_chat_note_activation' );

/** Native dismissible notice, consumed on its first authorised admin view. */
function andy_chat_activation_notice(): void {
	if ( ! current_user_can( 'manage_options' ) || ! get_transient( 'andy_chat_activation_notice' ) ) {
		return;
	}
	delete_transient( 'andy_chat_activation_notice' );
	?>
	<div class="notice notice-info is-dismissible">
		<p><?php esc_html_e( 'Andy Partner is activated. Review your Agent ID and widget settings, then test a public page.', 'andy-chat' ); ?> <a href="<?php echo esc_url( admin_url( 'options-general.php?page=' . ANDY_CHAT_PAGE_SLUG ) ); ?>"><?php esc_html_e( 'Open Andy Partner settings', 'andy-chat' ); ?></a></p>
	</div>
	<?php
}
add_action( 'admin_notices', 'andy_chat_activation_notice' );

/**
 * Registers the menu entry under Settings.
 */
function andy_chat_add_settings_page(): void {
	add_options_page(
		__( 'Andy Partner', 'andy-chat' ),
		__( 'Andy Partner', 'andy-chat' ),
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
		__( 'Agent ID', 'andy-chat' ),
		'andy_chat_render_embed_id_field',
		ANDY_CHAT_PAGE_SLUG,
		'andy_chat_connection',
		array( 'label_for' => 'andy_chat_embed_id' )
	);

	add_settings_field(
		'andy_chat_access',
		__( 'Access check', 'andy-chat' ),
		'andy_chat_render_access_field',
		ANDY_CHAT_PAGE_SLUG,
		'andy_chat_connection'
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
		<li><?php esc_html_e( 'When a visitor writes in the chat, the message text and a random conversation id are sent to Andy so your Agent can answer. The current widget release creates a new conversation id on every page load and keeps nothing in the visitor\'s browser between pages or visits. It sets no cookies.', 'andy-chat' ); ?></li>
		<li><?php esc_html_e( 'Andy keeps conversations while the Agent and Workspace exist, or until you delete them in Andy. Andy may keep technical logs for security and debugging. This plugin stores nothing about visitors. Account links share the public site URL and settings return when you open them; the access check identifies this plugin for attribution.', 'andy-chat' ); ?></li>
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
 * The shared WordPress installation view supplies the public Agent ID.
 */
function andy_chat_render_connection_section(): void {
	?>
	<p>
		<?php esc_html_e( 'In Andy open your Agent → Channels → Website Widget → Configure, choose WordPress and copy Agent ID. Paste that ID below. It is public; no API key is needed.', 'andy-chat' ); ?>
	</p>
	<?php if ( andy_chat_is_widget_active() ) : ?>
	<p>
		<?php
		printf(
			/* translators: %s: link to create an Andy account */
			esc_html__( 'No Andy account yet? %s', 'andy-chat' ),
			'<a href="' . esc_url( andy_chat_setup_url() ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Create your Andy account', 'andy-chat' ) . '</a>'
		);
		?>
	</p>
	<p><a href="<?php echo esc_url( andy_chat_setup_url( true ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Already have Andy? Choose an existing Agent', 'andy-chat' ); ?></a></p>
	<?php endif; ?>
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
 * "Check access" button and its live status region.
 *
 * The check runs in assets/access-check.js from the administrator's browser, so it carries the real
 * site origin. It reads the Agent's public configuration only; it never sends a chat message.
 */
function andy_chat_render_access_field(): void {
	$settings = andy_chat_get_settings();
	?>
	<button type="button" id="andy-chat-check-access" class="button button-secondary"><?php esc_html_e( 'Check access from this site', 'andy-chat' ); ?></button>
	<p class="description"><?php esc_html_e( 'Checks whether this browser can read the Agent\'s public configuration. It does not save settings, send a message or prove that the public widget has loaded. The request identifies this plugin for WordPress attribution.', 'andy-chat' ); ?></p>
	<div id="andy-chat-access-result" role="status" aria-live="polite"><p><?php echo esc_html( '' === $settings['embed_id'] ? __( 'Add an Agent ID before checking access.', 'andy-chat' ) : __( 'Access has not been checked in this visit.', 'andy-chat' ) ); ?></p></div>
	<?php
}

/**
 * Loads the access-check script on Settings → Andy Partner only, with its translated copy.
 *
 * Strings stay in PHP so the existing WP-CLI extraction covers them; the script substitutes %s itself
 * because the origin and the embed id are only known in the browser.
 *
 * @param string $hook_suffix Current admin screen.
 */
function andy_chat_enqueue_settings_assets( string $hook_suffix ): void {
	if ( 'settings_page_' . ANDY_CHAT_PAGE_SLUG !== $hook_suffix ) {
		return;
	}
	$settings = andy_chat_get_settings();

	wp_enqueue_script(
		'andy-chat-access-check',
		plugins_url( 'assets/access-check.js', ANDY_CHAT_FILE ),
		array(),
		ANDY_CHAT_VERSION,
		array( 'in_footer' => true )
	);

	$config = array(
		'savedId'    => $settings['embed_id'],
		'enabled'    => andy_chat_is_widget_active(),
		'endpoint'   => ANDY_CHAT_API_URL . '/chatbot/',
		// Tells Andy the check came from this plugin, for WordPress access-check attribution.
		'query'      => '?' . http_build_query( array( 'source' => 'wordpress-plugin', 'plugin_version' => ANDY_CHAT_VERSION ) ),
		'siteOrigin' => andy_chat_site_origin(),
		'pattern'    => trim( ANDY_CHAT_EMBED_ID_PATTERN, '/' ),
		'text'       => array(
			'noId'              => __( 'Add an Agent ID before checking access.', 'andy-chat' ),
			'unchecked'         => __( 'Access has not been checked for this Agent ID.', 'andy-chat' ),
			'invalidId'         => __( 'Enter a valid Agent ID before checking: letters, digits, hyphens and underscores only.', 'andy-chat' ),
			/* translators: 1: Agent ID, 2: this site's origin, for example https://example.com */
			'checking'          => __( 'Checking %1$s from %2$s…', 'andy-chat' ),
			/* translators: 1: Agent name, 2: this site's origin, for example https://example.com */
			'success'           => __( 'Access allowed: Andy returned the configuration of Agent "%1$s" to %2$s. This is eligibility, not proof that the public widget has loaded. Chats still need an active Andy plan.', 'andy-chat' ),
			'widgetOff'         => __( 'The widget is off in your saved WordPress settings. Read the disclosure, enable it and save settings to show it.', 'andy-chat' ),
			'widgetOn'          => __( 'The widget is enabled in your saved WordPress settings. Open a public page to test it.', 'andy-chat' ),
			'unsavedId'         => __( 'This Agent ID has not been saved. Review the widget toggle and save settings after reading the disclosure.', 'andy-chat' ),
			/* translators: %s: the public site's origin, for example https://example.com */
			'successOtherOrigin' => __( 'Visitors load the widget from %s, which this check did not test. If the Agent restricts Allowed Origins, that origin must be on the list too.', 'andy-chat' ),
			/* translators: %s: Agent ID */
			'notFound'          => __( 'Andy found no Agent with ID %s. In Andy open Channels → Website Widget → Configure, choose WordPress and copy Agent ID again.', 'andy-chat' ),
			/* translators: %s: HTTP status code */
			'unexpected'        => __( 'Andy answered with HTTP %s instead of the Agent\'s configuration. Try again in a few minutes. This is not an Allowed Origins problem.', 'andy-chat' ),
			'blocked'           => __( 'Andy answered but did not let this browser read the reply. That happens when the Agent ID does not exist, or when the Agent restricts Allowed Origins and this site is not on the list.', 'andy-chat' ),
			/* translators: %s: this site's origin, for example https://example.com */
			'addOrigin'         => __( 'If the Agent ID matches Andy exactly, open your Agent → Settings → Security → Allowed Origins, add %s and check again.', 'andy-chat' ),
			/* translators: %s: the public site's origin, for example https://example.com */
			'siteOriginDiffers' => __( 'Visitors use %s, so add that origin as well.', 'andy-chat' ),
			'network'           => __( 'Could not reach app.andypartner.com from this browser. Check your internet connection or a content blocker. This says nothing about Allowed Origins.', 'andy-chat' ),
			/* translators: %s: number of seconds waited */
			'timeout'           => __( 'Andy did not answer within %s seconds, so the check was stopped. Try again. If it keeps happening, check your connection or a content blocker. This says nothing about Allowed Origins.', 'andy-chat' ),
			'internal'          => __( 'The check failed inside this plugin before it could judge Andy\'s answer. Reload the page and try again. This says nothing about Allowed Origins.', 'andy-chat' ),
		),
	);

	wp_add_inline_script(
		'andy-chat-access-check',
		'window.andyChatAccess = ' . wp_json_encode( $config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . ';',
		'before'
	);
}
add_action( 'admin_enqueue_scripts', 'andy_chat_enqueue_settings_assets' );

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
		wp_die( esc_html__( 'You do not have permission to change Andy Partner settings.', 'andy-chat' ), '', array( 'response' => 403 ) );
	}

	$settings = andy_chat_get_settings();
	if ( andy_chat_is_widget_active() ) {
		$status = __( 'Widget enabled in WordPress. Open a public page to verify that it loads and answers.', 'andy-chat' );
	} elseif ( '' === $settings['embed_id'] ) {
		$status = __( 'No Agent ID saved. The widget is off.', 'andy-chat' );
	} else {
		$status = __( 'Agent ID saved. The widget is off; check access before enabling it.', 'andy-chat' );
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Andy Partner', 'andy-chat' ); ?></h1>
		<p id="andy-chat-status"><strong><?php echo esc_html( $status ); ?></strong></p>
		<?php if ( ! andy_chat_is_widget_active() ) : ?>
			<div class="card">
				<h2><?php esc_html_e( 'Connect your Andy Agent', 'andy-chat' ); ?></h2>
				<p><?php esc_html_e( 'Create an Andy account or choose an existing Agent, then copy its Agent ID from the WordPress installation view. Activation alone never enables the widget.', 'andy-chat' ); ?></p>
				<p><a class="button button-primary" href="<?php echo esc_url( andy_chat_setup_url() ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Create your Andy account', 'andy-chat' ); ?></a> <a class="button button-secondary" href="<?php echo esc_url( andy_chat_setup_url( true ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Choose an existing Agent', 'andy-chat' ); ?></a></p>
			</div>
		<?php endif; ?>
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
