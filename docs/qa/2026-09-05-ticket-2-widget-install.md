# Ticket #2 QA: install Andy Chat and control the sitewide widget

Tested commit: see the PR. Environment: WordPress Playground CLI 3.1.52 (WASM PHP 8.1, WordPress 7.1, SQLite),
port 18842, synthetic site "Andy Chat QA (synthetic)", synthetic embed id `qa0synthetic0embed0id0000000000a`.
No production site and no Andy account were touched. No chat message was sent. The only requests that
reached Andy were the public `widget.js` download and the public `GET /api/chatbot/<synthetic id>` lookup
that the real widget makes on its own.

How the site was driven: the plugin ZIP built by `bin/build-zip.sh` was uploaded through
Plugins → Add New → Upload (WordPress's own `update.php?action=upload-plugin` handler), activated from the
Plugins screen, and configured from Settings → Andy Chat in the T3 built-in browser. Anonymous public page
requests came from `curl` with the Playground auto-login cookie disabled.

## Checklist

```gherkin
Feature: Install Andy Chat and control the sitewide widget

  Scenario: Activation alone makes no Andy request                                   PASS
    Given the ZIP is installed and activated
    When an anonymous visitor loads the home page
    Then the HTML contains no reference to app.andypartner.com

  Scenario: Enabling without an embed id is refused                                  PASS
    When the administrator ticks the toggle with an empty embed id and saves
    Then the error "Enter your Agent's embed id before enabling the widget" shows
    And the toggle stays off

  Scenario: Hostile input is rejected and cannot inject script                       PASS
    When the administrator submits <script>window.__andy_xss=1</script>"onmouseover="alert(1)
    Then the invalid-id error shows, nothing is stored, window.__andy_xss is undefined
    And no inline script containing the payload exists in the page

  Scenario: Authorized save persists                                                  PASS
    When the administrator saves a valid id with the toggle on
    Then "Settings saved. The Andy widget is on for every public page." shows
    And after a full reload the id and toggle are still set
    And after deactivate + reactivate they are still set

  Scenario: Public pages load the widget exactly once with the vanilla contract      PASS
    When an anonymous visitor loads the home page and a single post
    Then exactly one <script async src="https://app.andypartner.com/widget.js"> exists
    And an inline script before it sets window.ANDY_CHATBOT_ID and window.ANDY_CHAT_API_URL
    And in a real browser the widget requested /api/chatbot/<id> from app.andypartner.com/api
    And wp-admin, wp-login.php and the RSS feed contain no widget

  Scenario: Disabling stops the widget on the next request                           PASS
    When the administrator unticks the toggle and saves
    Then the home page contains no reference to app.andypartner.com

  Scenario: Deactivating stops the widget on the next request                        PASS
    Given the toggle is on
    When the plugin is deactivated
    Then the home page contains no reference to app.andypartner.com

  Scenario: Nonce rejection                                                           PASS
    When an administrator session POSTs options.php without a nonce
    Then WordPress answers 403 "The link you followed has expired."
    And the submitted id never reaches the front end

  Scenario: Capability rejection                                                      PASS
    Given a synthetic subscriber "qa-subscriber"
    When they open Settings → Andy Chat
    Then 403 "Sorry, you are not allowed to access this page." and no form is rendered
    When they POST options.php
    Then 403 "Sorry, you are not allowed to manage options for this site."

  Scenario: Spanish                                                                   PASS
    Given the site language is es_CL (not es_ES, to prove the es_* fallback)
    Then the settings page, status line, disclosure, button and the invalid-id error are in Spanish
    And the English base strings render when the site language is en_US

  Scenario: Disclosure and links                                                      PASS
    Then the page names app.andypartner.com, what is sent on page load and on chat,
      retention as stated in Andy's data retention policy, and disconnect behaviour
    And links to https://andypartner.com/legal/privacy, /legal/terms and /legal/data-deletion (all HTTP 200)
    And the signup link is https://app.andypartner.com/sign-up (HTTP 200) with
      utm_source=wordpress-plugin&utm_medium=plugin&utm_campaign=andy-chat&utm_content=settings-page

  Scenario: Uninstall removes the option                                              PASS
    When the plugin is deleted from the Plugins screen and reinstalled from the ZIP
    Then the settings page shows an empty embed id and the toggle off

  Scenario: Plugin Check                                                              PASS (1 warning)
    Local: Tools → Plugin Check, all categories: 0 errors, 1 warning
    CI: WordPress/plugin-check-action on the unpacked ZIP: 0 errors, 1 warning
    The warning is load_plugin_textdomain() "discouraged since 4.6". It stays because the
    bundled es_ES catalog in languages/ only loads through that call until WordPress.org
    ships language packs.
```

## Anonymous front-end excerpt (widget on)

```html
<script id="andy-chat-widget-js-before">
window.ANDY_CHATBOT_ID = "qa0synthetic0embed0id0000000000a"; window.ANDY_CHAT_API_URL = "https://app.andypartner.com/api";
</script>
<script async data-wp-strategy="async" id="andy-chat-widget-js" src="https://app.andypartner.com/widget.js"></script>
```

## Not covered here

- The live access check (success, explicit Allowed Origins denial, other failures) is ticket #3.
- Playground runs PHP in WASM with SQLite. A MySQL-backed install was not exercised locally; CI's
  Plugin Check runs in wp-env (Docker, MySQL) and activated the plugin there.
- No real Agent was configured, so the widget bubble never rendered. The contract proof is the widget
  reading the globals and calling the chatbot endpoint for the configured id.
