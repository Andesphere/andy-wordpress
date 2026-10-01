# WordPress connection QA

Tested source: `57d5c64b414e4badd4554179a9bd8f4b1693f4ce`, accepted into the whole plugin integration/PR #16. No release/tag/directory submission. Source ZIP SHA-256: `adde9262661fceca187838e99170689ff80b81ceb883ee475cb65ccef8ee2948`.

## Environment and boundaries

Playground CLI 3.1.52, WASM PHP 8.1.34, SQLite. `--wp=7.1` resolved WordPress **7.1.2**, confirmed by its dashboard/version file. Owned disposable site on port 9505, one worker, process 83957 (initial six-worker boot 81597 was stopped before QA). The browser phase used only its owned Chrome task tab, closed at handback. No imported data, production Andy Agent, key, provider call, Stripe object or account write was used.

The actual tracked-source ZIP was uploaded via WordPress's native ZIP form, installed and activated in the browser. The installed access script matched source SHA-256 `53c5b6d03fbe1dc953d3d9db4a38be4d754dc7a7f6c9377852c7b8e173ad473f`. Production account links were inspected before substitution. A disposable MU fixture then changed only the admin access-check endpoint to same-site controlled configuration/404 responses, retaining `source=wordpress-plugin` and `plugin_version=0.1.2`. The shipped script stayed unchanged. This is access UI evidence, not an actual Andy origin-entitlement or connection result.

Spanish used the official WordPress 7.1.2 `es_ES` core language pack and the regenerated plugin catalog in native `wp-content/languages/plugins/`. The release ZIP excludes `languages/`; no bundled fallback or custom loader was restored. Native General settings selected Español, and the plugin translated through WordPress's language-pack mechanism.

## Executed evidence

| Check | Outcome |
| --- | --- |
| Native ZIP upload/install/activation | Installed successfully; version 0.1.2. |
| Native activation notice | Dismissible settings notice appeared on Plugins; dismissal removed it. Next settings visit did not repeat it. |
| No-ID English state | Widget off, prominent create/existing-Agent/local-ID paths; disclosure before toggle; access needs an ID. |
| Account links in actual EN/ES settings | English `/en/sign-up` and `/en/sign-in`; Spanish `/sign-up` and `/sign-in`; exact public-home/settings-return plus existing four UTMs/platform; no Agent ID, token or nonce. Links were inspected, not followed into production account setup. |
| Save ID while off | Native form persisted `qa_wordpress_allowed`; toggle remained off; reload access was unchecked. |
| Controlled allowed, saved off | Eligibility wording explicitly said no proof of public load, and independently said the saved widget was off. |
| Edit ID and controlled 404 | Edit immediately reset access to unchecked; missing ID explained the plain WordPress Agent ID path; saved value/toggle remained unchanged. |
| Explicit enable/save | Native form persisted enabled; text required opening a public page to test. Controlled access independently reported saved enablement. |
| Invalid ID while attempting toggle off | Native error said nothing changed; previous ID and enabled toggle both remained. |
| Spanish settings/access | Plain Agent ID directions, disclosure, local-ID path, unchecked state and allowed-but-disabled result all translated. Final saved widget restored off. |
| Native Settings API HTTP refusals | 11 assertions through ordinary WordPress login and `options.php`: missing/invalid administrator nonce returned 403; subscriber settings GET and forged POST returned 403; each refusal preserved the saved ID/off value. |
| Anonymous public page after saved-off restoration | No widget script or Agent global emitted. No public SDK bootstrap or message was sent. |

Native form refusal requests used a disposable WordPress subscriber fixture and real core capability/nonce enforcement, not substituted WordPress functions. The access fixture initially returned a malformed 200 body while being prepared; the plugin truthfully reported that configuration was absent. The recorded allowed cases used the correct `{chatbot: {id, name}}` response.

Source checks also passed: shipped-script Bun tests 6/33 expectations; actual WordPress PHP seam 72 assertions; PHP 8.1 parse of seven files; official WP-CLI 2.12.0 catalog regeneration repeated byte-for-byte; gettext format/completeness checks for 64 Spanish messages; version consistency 0.1.2; clean diff check. The canonical ZIP has 11 entries and contains no tests, tools, docs or language fallback.

## Remaining acceptance

The actual cross-repo new/existing-account auth/checkout/context flow, permitted Agent selection, clipboard/selection, phone focus/layout, public origin-restricted widget observation and a learned-site answer remain V1 on the reviewed whole Andy backend. A review found that the existing Andy configuration route reports widget observation for tagged plugin access checks; that route repair must be accepted before claiming the lifecycle distinction through the actual backend. Controlled success and WordPress enablement never count as Connected evidence.

PHP/WASM/SQLite proof does not substitute for CI's MySQL Plugin Check. CI remains owned by the whole PR; this worker did not release or publish anything.

## Lifecycle

Owned server 83957 stopped; port 9505 had no listener. The owned task tab was closed and shared browser/desktop handed back. No Convex state existed. The WordPress/SQLite site, core pack, MU fixture and tool hydration are explicitly synthetic/regenerable; they are removed at worktree handback after the evidence checkpoint is pushed. Remote source/evidence checkpoints remain the durable receipt.
