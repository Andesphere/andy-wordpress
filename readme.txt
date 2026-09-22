=== Andy Partner ===
Contributors: andesphere
Tags: ai chatbot, chatbot, live chat, customer support, lead generation
Requires at least: 7.1
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 0.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

An AI agent on every page of your site. It answers visitors day and night, collects their contact details and hands the chat to you when needed.

== Description ==

[Andy Partner](https://andypartner.com/wordpress?utm_source=wordpress-org&utm_medium=listing&utm_campaign=andy-partner) is an AI agent for small businesses. You teach it about your business once: your website, your prices, your opening hours, your documents. From then on it answers the people who visit your site, at any hour.

This plugin puts your Andy Partner agent on every public page of your WordPress site. There is no theme file to edit and no code to paste.

**What your visitors get**

* Answers straight away, day or night, based on what your agent knows about your business.
* A person when they need one. Your agent hands the conversation to you, and you reply from the Andy Partner app or its iPhone app.

**What you get**

* The contact details of people who are interested, collected by the agent during the chat.
* One inbox for your website, WhatsApp, Instagram and Slack conversations, when your plan includes those channels and you connect them in Andy Partner.
* A setup that takes about two minutes: install, paste your widget's embed id, switch it on.

**How it works**

1. Create your agent at [andypartner.com](https://andypartner.com/wordpress?utm_source=wordpress-org&utm_medium=listing&utm_campaign=andy-partner) and give it your website so it can learn about your business.
2. Install this plugin and open Settings → Andy Partner.
3. Paste the embed id of your agent, check access, and switch the widget on.

The agent, its knowledge, its look, its languages and billing are all managed in the Andy Partner app. The plugin is free. Andy Partner plans start at US$9 a month after a 14-day free trial.

The plugin stores two values in your WordPress database: the embed id and whether the widget is on. It stores no API key, no secret, and nothing about your visitors.

Settings and messages are available in English and Spanish.

= Third-party service =

This plugin loads a script from Andy Partner, a service operated by Andesphere. It only does so after an administrator has turned the widget on. What is sent, and when:

* On every public page load while the widget is on, the visitor's browser requests `https://app.andypartner.com/widget.js` and the public configuration of your agent from `https://app.andypartner.com/api`. Andy Partner receives the visitor's IP address, browser details and the address of your site as part of those requests.
* When a visitor writes in the chat, the message text and a random conversation id are sent to Andy Partner so your agent can answer. The current widget release creates a new conversation id on every page load and keeps nothing in the visitor's browser between pages or visits. It sets no cookies.
* When an administrator clicks "Check access from this site" on the settings page, the administrator's browser asks Andy Partner for the agent's public configuration and says the request comes from this plugin and which plugin version is installed. Andy Partner uses this to count agents connected through WordPress. Nothing about visitors is sent.
* Andy Partner keeps conversations while the agent and workspace exist, or until you delete them in Andy Partner. Technical logs may be kept for security and debugging.
* Turning the widget off, or deactivating or deleting the plugin, stops the script on the next page load. It does not delete conversations already stored in Andy Partner; manage those from the Andy Partner app.

Policies: [privacy policy](https://andypartner.com/legal/privacy), [terms of service](https://andypartner.com/legal/terms), [data retention and deletion policy](https://andypartner.com/legal/data-deletion).

== Installation ==

1. In Plugins → Add New, search for "Andy Partner" and click Install Now. You can also upload the ZIP, or copy the `andy-chat` folder into `wp-content/plugins/`.
2. Activate Andy Partner. Activation alone loads nothing from Andy Partner.
3. Open Settings → Andy Partner and read the disclosure.
4. In the Andy Partner app open your agent, go to Channels and click Configure on the Website Widget card. In the code snippet that opens, copy the value of `embedId` from `<AndyChat embedId="..." />`. That is the embed id.
5. Paste the embed id and click "Check access from this site". The check asks Andy for the Agent's public configuration from your browser, using the origin of the admin page you are on. When your public site uses a different address, the result says so, because that origin is not tested. It sends no message.
6. Tick "Show the Andy widget on every public page" and save.

If your Agent restricts Allowed Origins in Andy, add your site's origin (for example `https://example.com`) to that list, otherwise the widget cannot load its configuration. The access check tells you when that is the likely cause and which origin to add.

== Frequently Asked Questions ==

= Is it free? =

The plugin is free. Your agent needs an Andy Partner plan: plans start at US$9 a month, and every plan starts with a 14-day free trial.

= Do I need an Andy API key? =

No. The embed id is public. It identifies the Agent, it grants no access to your Andy account.

= Does the widget appear in wp-admin? =

No. It only loads on public pages, and only while it is switched on.

= Can I show the widget on some pages only? =

Not in this version. The widget is either on for every public page or off.

= The access check says Andy did not let the browser read the reply. What now? =

Andy gives the same answer for an unknown embed id and for a site outside the Agent's Allowed Origins, and the browser cannot tell them apart. First compare the embed id with the `embedId` value in the Website Widget snippet of the Andy Partner app (Channels → Website Widget → Configure). If it matches, open the Agent's Settings → Security → Allowed Origins and add the origin shown in the message. A successful check confirms that the origin it ran from can load the Agent; chats still need an active Andy Partner plan.

= What happens when I deactivate the plugin? =

The widget stops loading immediately. Your saved settings stay in the database so reactivating restores them. Deleting the plugin removes the settings.

== Screenshots ==

1. Your Andy Partner agent answering a visitor on a WordPress site.
2. Settings → Andy Partner: paste the embed id, check access and switch the widget on.

== Changelog ==

= 0.1.1 =
* Renamed to Andy Partner, the product's name.
* New plugin icon, banner, screenshots and description.
* The access check tells Andy Partner it came from this plugin, so connected agents can be counted.

= 0.1.0 =
* First release: settings screen, sitewide widget toggle, browser-side access check, English and Spanish.
