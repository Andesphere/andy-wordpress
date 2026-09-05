# Ticket #9 QA: setup directions match the current Andy Channels interface

Prose-only change: no widget, settings-storage, public-global, version or workflow change. The three
stale strings pointed admins to "Installation → HTML / Vanilla JavaScript", a screen the Andy App no
longer offers.

## Source of truth

Firstmate verified the live app on 2026-09-05 in Chrome: Agent → Channels → Website Widget →
Configure opens "Install Website Widget", which shows React instructions only. Read-only inspection of
the andyChat checkout (`33e74a139`) confirms it:

* `apps/web/src/features/chatbots/components/website-widget-dialog.tsx` renders only the React install
  command and `getReactUsageSnippet()`, whose snippet contains `<AndyChat embedId="<chatbot id>" />`.
  That `embedId` is the same public id the vanilla snippet used as `ANDY_CHATBOT_ID`, so the plugin's
  widget globals are unchanged.
* The old `chatbot.install.*` strings ("Installation", "HTML / Vanilla JavaScript") remain in the
  message catalogs but no component renders them.
* Allowed Origins still lives in the agent's Settings tab, "Security" segment
  (`settings-tab.tsx`, `chatbot.settings.security.allowedDomains`), so "Settings → Security →
  Allowed Origins" stays as written.
* Spanish labels for the new path come from the same catalogs: Canales → Widget de Sitio Web →
  Configurar; Ajustes → Seguridad → Orígenes Permitidos.

## Changed strings

| Where | New English |
| --- | --- |
| readme.txt, Installation step 4 | go to Channels and click Configure on the Website Widget card; copy the value of `embedId` from `<AndyChat embedId="..." />` |
| readme.txt, FAQ on the opaque access reply | compare the embed id with the `embedId` value in the Website Widget snippet (Channels → Website Widget → Configure) |
| Settings screen, "Connect your Agent" intro | same path; the value of embedId in the code snippet is the public embed id |
| Access check, Agent not found | copy the id again from the Website Widget snippet (Channels → Website Widget → Configure) |
| Save validation, invalid id | copy it from the Website Widget snippet (Channels → Website Widget → Configure) |

Spanish translations were updated for all three PHP strings; the readme is English only.

## Validation

Run on 2026-09-05 without native PHP, using WordPress Playground CLI 3.1.52 (WASM PHP 8.1, WP 7.1)
mounted on the source tree, with the exact `wp i18n` commands and headers from `.github/workflows/ci.yml`:

```gherkin
Scenario: Catalogs regenerate to the committed files                                        PASS
  make-pot / make-mo / make-php produce the committed POT, MO and l10n.php with no further diff
Scenario: Spanish catalog complete and well formed                                          PASS
  msgfmt --check --check-format: 50 translated messages; msgcmp against the POT: no output
Scenario: PHP 8.1 syntax                                                                   PASS
  token_get_all(TOKEN_PARSE) accepted andy-chat.php, uninstall.php, includes/*.php, languages/*.php
Scenario: Version header, constant, stable tag and changelog agree                          PASS
  bin/check-release-version.sh → 0.1.0
Scenario: No stale navigation left                                                         PASS
  grep for "Installation tab", "Vanilla" and "ANDY_CHATBOT_ID" hits only the readme section
  header and the widget global in includes/widget.php
```

Plugin Check and the ZIP build run in CI on the pull request. No Andy account, live setting, tag or
WordPress.org resource was touched.
