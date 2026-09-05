=== Andy Chat ===
Contributors: andesphere
Tags: chat, chatbot, ai, customer support, live chat
Requires at least: 7.1
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds your Andy AI Agent's chat widget to every public page of your site. Paste the embed id, switch it on, done.

== Description ==

Andy Chat puts the chat widget of your [Andy](https://andypartner.com/) Agent on every public page of your WordPress site without editing theme files.

Setup takes one screen. Open Settings → Andy Chat, paste the public embed id of your Agent, and turn the widget on. The Agent itself, its knowledge, appearance, languages and billing are managed in the Andy App, exactly as for any other widget install.

The plugin stores two values in your WordPress database: the embed id and whether the widget is on. It stores no API key, no secret, and nothing about your visitors. It sends no analytics of its own.

Interface and validation messages are available in English and Spanish.

= Third-party service =

This plugin loads a script from Andy, a service operated by Andesphere. It only does so after an administrator has turned the widget on. What is sent, and when:

* On every public page load while the widget is on, the visitor's browser requests `https://app.andypartner.com/widget.js` and the public configuration of your Agent from `https://app.andypartner.com/api`. Andy receives the visitor's IP address, browser details and the address of your site as part of those requests.
* When a visitor writes in the chat, the message text and a temporary session id are sent to Andy so your Agent can answer. If your Agent is configured to ask for a name and email, those are sent too.
* Andy keeps conversations while the Agent and Workspace exist, or until you delete them in Andy. Technical logs may be kept for security and debugging.
* Turning the widget off, or deactivating or deleting the plugin, stops the script on the next page load. It does not delete conversations already stored in Andy; manage those from the Andy App.

Policies: [privacy policy](https://andypartner.com/legal/privacy), [terms of service](https://andypartner.com/legal/terms), [data retention and deletion policy](https://andypartner.com/legal/data-deletion).

== Installation ==

1. Upload the plugin ZIP through Plugins → Add New → Upload Plugin, or copy the `andy-chat` folder into `wp-content/plugins/`.
2. Activate Andy Chat. Activation alone loads nothing from Andy.
3. Open Settings → Andy Chat and read the disclosure.
4. In the Andy App open your Agent, go to Installation, pick the HTML / Vanilla JavaScript tab and copy the value assigned to `ANDY_CHATBOT_ID`. That is the embed id.
5. Paste the embed id, tick "Show the Andy widget on every public page" and save.

If your Agent restricts Allowed Origins in Andy, add your site's origin (for example `https://example.com`) to that list, otherwise the widget cannot load its configuration.

== Frequently Asked Questions ==

= Do I need an Andy API key? =

No. The embed id is public. It identifies the Agent, it grants no access to your Andy account.

= Does the widget appear in wp-admin? =

No. It only loads on public pages, and only while it is switched on.

= Can I show the widget on some pages only? =

Not in this version. The widget is either on for every public page or off.

= What happens when I deactivate the plugin? =

The widget stops loading immediately. Your saved settings stay in the database so reactivating restores them. Deleting the plugin removes the settings.

== Changelog ==

= 0.1.0 =
* First release: settings screen, sitewide widget toggle, English and Spanish.
