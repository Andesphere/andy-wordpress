# WordPress connection handoff

Implementation in progress on `feat/wordpress-activation-14`.

[Plugin spec](https://github.com/Andesphere/andy-wordpress/issues/14), [connection ticket](https://github.com/Andesphere/andy-wordpress/issues/15), and [shared receiving contract](https://github.com/JorgeMenaDev/matias/blob/main/docs/specs/2026-10-01-andy-wordpress-activation.md).

W1 is integrated here after the Andy context and ID-view contracts are accepted. Keep the current Settings API values, disclosure, capability/nonce checks and WordPress.org packaging workflow. The complete plugin change has one PR, coordinated with the Andy activation PR. Deployment and directory publication are separate from implementation readiness.

## Receiving contract

New-account and existing-account links use the fixed Andy `/sign-up` and `/sign-in` destinations. `get_user_locale()` selects Spanish for `es*`; English and unsupported locales use `/en`. The URL carries `platform=wordpress`, `site_url=home_url('/')`, `return_to=admin_url('options-general.php?page=andy-chat')`, and the four existing UTMs. No current-request query, Agent ID, nonce, token or API key is copied. Hints with credentials, fragments or unexpected query fields are omitted. They are advisory; Andy confirms the public site in its authorised Workspace and rechecks the same-origin return before an explicit owner action.

Andy source contract: [wordpress-installation-context.md](https://github.com/Andesphere/andyChat/blob/4bbc477dd6edf1e147b65ac753d0d65b741c2c1a/docs/development/wordpress-installation-context.md). Existing owners choose a permitted existing Agent without a new Workspace, Agent or trial. Generic entry and manual WordPress method remain usable during separate deployments.

## WordPress behaviour

The current native Settings API form, `card`, `notice` and WordPress dismissal styles remain in use. Activation sets only a ten-minute notice flag, consumed on the first authorised admin view; the native dismiss button needs no new endpoint. It never changes the saved `embed_id` or `enabled` values. Deactivation/reactivation preserves those settings; uninstall removes them and any pending notice.

The public field is labelled **Agent ID**, matching Andy's WordPress installation view. Internal option and widget globals retain their existing names. No ID, saved-but-disabled and enabled WordPress settings have distinct wording. Access is unchecked on each visit or ID edit; the existing browser check shows allowed/failed outcomes. A successful check reports the saved toggle state or an unsaved ID without writing anything. It proves origin eligibility only; an owner must test the actual widget on a public page. External-service disclosure stays before the toggle.

## Checks and acceptance

`bun test tests/access-check.test.ts` executes the shipped browser script against controlled DOM/network boundaries. It checks allowed-but-disabled/enabled/unsaved-ID results, exact request destination and credentials policy, unchanged settings, cancellation after ID edits, stale replies, failed access and timeout. `bin/check-release-version.sh` retains 0.1.2; no tag or directory publication is part of this work.

`tests/connection.php` runs against a fresh installed WordPress, not substituted WordPress functions. The source run used Playground 3.1.52, WordPress 7.1 and WASM PHP 8.1.34. Its 72 assertions cover once-decoded new/existing-account context for English, Spanish (Spain/Mexico) and an unsupported locale; omitted unsafe hints; activation notice capability and one-view lifetime; native form nonce; local existing-ID path; disclosure order; unchanged saved values through activation/deactivation/reactivation; and the actual emitted production widget/API configuration. Lower-role rendering and sanitizer writes are rejected. Actual forged form POST enforcement is still a browser check.

The isolated checker command is:

```sh
node .playground/tools/node_modules/@wp-playground/cli/cli.js php --php=8.1 --wp=7.1 \
  --mount=.:/wordpress/wp-content/plugins/andy-chat -- -r \
  'require "/internal/shared/auto_prepend_file.php"; try { require "/wordpress/wp-content/plugins/andy-chat/tests/connection.php"; } catch (Throwable $error) { echo $error->getMessage(), "\n"; exit(1); }'
```

The CLI `-r` path requires Playground's prepend explicitly so it loads the normal SQLite integration. The tools are pinned in the disposable ignored worktree hydration; no PHP/runtime dependency is shipped. Official WP-CLI 2.12.0 regenerated POT/MO/PHP catalogs with the CI command; gettext completeness/format checks cover all 64 Spanish messages. PHP 8.1 parses all seven source/test/catalog PHP files.

Final source ZIP/hash and actual EN/ES install/activate/settings/public-page QA remain required. Use the prior `docs/qa/` receipts as procedures, not current passes. Keep any local Andy endpoint substitution in a disposable owned MU fixture, assert production links first and use unchanged reviewed SDK bytes; no product testing toggle is added. Claim the shared browser only after its prior owner hands it back.
