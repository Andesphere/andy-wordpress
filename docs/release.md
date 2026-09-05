# Releasing Andy Chat to WordPress.org

This is the release runbook for the `andy-chat` plugin. It covers what the repository can do on its
own (build and validate a package, publish a tagged version once credentials exist) and what needs a
person with the Andesphere WordPress.org account.

## Where things stand (2026-09-05)

| Item | State | Owner and tracker |
| --- | --- | --- |
| Release candidate ZIP, CI validation, this runbook | done in the repository | Matias, [#4](https://github.com/Andesphere/andy-wordpress/issues/4) |
| WordPress.org account for Andesphere | pending | Jorge, [andyChat#1954](https://github.com/Andesphere/andyChat/issues/1954), human step [matias#596](https://github.com/JorgeMenaDev/matias/issues/596) |
| Initial plugin submission and directory review | not started, needs the account | Jorge submits, Matias records evidence in #4 |
| SVN credentials in GitHub Actions | not configured | Jorge, after approval |
| `WPORG_DEPLOY_APPROVED` repository variable | not set | Jorge, after approval |
| First publication (`v0.1.0` tag) | not done | Jorge orders it, see "Publishing a version" |

Nothing in this repository publishes anything today. Pushing a version tag before approval builds and
validates the package and then fails the deploy job with a message naming what is missing.

## Package facts

| Field | Value | Where it lives |
| --- | --- | --- |
| Name | Andy Chat | `andy-chat.php` header, `readme.txt` title |
| Slug and text domain | `andy-chat` | ZIP folder name, `SLUG` in the deploy workflow |
| License | GPLv2 or later | header, readme, `LICENSE` |
| Version | 0.1.0 | header, `ANDY_CHAT_VERSION`, `Stable tag`, changelog |
| Requires at least | WordPress 7.1 | header and readme |
| Tested up to | WordPress 7.1 | readme |
| Requires PHP | 8.1 | header and readme |
| Contributors | `andesphere` | readme |

`bin/check-release-version.sh` fails CI when the four version locations disagree, and fails the deploy
workflow when the tag disagrees with them.

Checked against primary sources on 2026-09-05:

- WordPress 7.1 is the current release. `api.wordpress.org/core/version-check/1.7/` offers 7.1 and
  `core/stable-check/1.0/` marks it `latest`. Requiring 7.1 means only up-to-date sites can install
  the plugin. That is the approved baseline from the spec, unchanged here.
- PHP 8.1 left security support on 2025-12-31 (php.net supported versions). 8.1 is a floor, not a
  target, so this is not a conflict. `api.wordpress.org/stats/php/1.0/` still reports about 11% of
  sites on 8.1.
- The slug is free. `wordpress.org/plugins/andy-chat/` redirects to search and the plugin info API
  answers "Plugin not found." The directory assigns the final slug from the plugin name at review
  time; if it differs from `andy-chat`, the `SLUG` value in `.github/workflows/deploy.yml` and the ZIP
  folder name in `bin/build-zip.sh` must change before the first tag.
- The readme validator (`wordpress.org/plugins/developers/readme-validator/`) reports no errors. Its one
  warning is that the contributor `andesphere` is not a WordPress.org user yet. The `Contributors`
  line must equal the account's actual username once it exists. It also notes the optional
  `Upgrade Notice`, `Screenshots` and donate link sections are absent, which is intended.
- Every link in `readme.txt` and the settings page answers HTTP 200: the sign-up page, the privacy,
  terms and data-deletion policies, `widget.js` and the plugin URI.
- `https://app.andypartner.com/widget.js` is still the 95502-byte bundle with `PERSIST_SESSION` false
  that the disclosure describes.

## What ships

`bin/build-zip.sh` copies tracked files minus `.distignore` into `dist/andy-chat.zip`. The archive holds
one `andy-chat/` folder with the plugin file, `includes/`, `assets/access-check.js`, `languages/`,
`readme.txt`, `uninstall.php` and `LICENSE`. Kept out: `.github`, `bin`, `docs`, dotfiles, editor and
package-manager files, and anything untracked. The build is reproducible with Info-ZIP zip 3.0, the
`zip` on macOS and on `ubuntu-latest`: entries carry the HEAD commit time in UTC and fixed 755/644
modes, so the same commit produces the same SHA-256 locally and in CI regardless of timezone or umask
(verified at umask 022, 002 and 077). Compare the `sha256sum` line in the CI log with
`shasum -a 256 dist/andy-chat.zip` at home. Because the timestamp is the HEAD commit's, a commit that
touches only `bin/`, `docs/` or `.github/` changes the archive hash while the extracted files stay
identical; `diff -r` of the two unpacked trees is the check in that case.

CI runs on every push and pull request: PHP lint at 8.1, translation catalogs regenerated and diffed,
version agreement, ZIP build, unpack, and `WordPress/plugin-check-action` against WordPress 7.1 on the
unpacked ZIP. The ZIP is uploaded as the `andy-chat` artifact of the run. That artifact is the release
candidate.

## First submission (manual, needs the account)

1. Jorge logs in to wordpress.org with the Andesphere account from andyChat#1954.
2. Download the `andy-chat` artifact from the CI run of the commit being submitted, or run
   `bin/build-zip.sh` on that commit and confirm the SHA-256 matches CI.
3. Upload it at <https://wordpress.org/plugins/developers/add/>.
4. Record in #4: commit, artifact SHA-256, submission date and the confirmation email.
5. Wait for the plugin review team. They email the account; replies and requested changes go through
   that thread. Keep #4 open until they approve.
6. On approval they name the slug and grant SVN access at
   `https://plugins.svn.wordpress.org/<slug>/`. Nothing is public until the first SVN commit.

## Turning the deploy workflow on (after approval)

All three are Jorge's, and none should exist before the approval email:

1. In the WordPress.org profile, set an SVN-specific password (profile → Edit → SVN password).
   Do not use the login password.
2. In this repository, Settings → Environments → `wordpress-org` (GitHub creates it on the first tag
   run, or create it by hand): add secrets `SVN_USERNAME` and `SVN_PASSWORD`. Optionally add a
   required reviewer so every publication waits for a click.
3. Settings → Secrets and variables → Actions → Variables: set `WPORG_DEPLOY_APPROVED` to `true`.

The deploy job's first step runs `bin/release-guard.sh`. It lists every missing item and exits 1
without printing any value. The action passes the password to `svn commit` on the command line;
GitHub masks registered secrets in logs, so never enable `ACTIONS_STEP_DEBUG` on this repository.

## Publishing a version

1. Open a PR that bumps `Version`, `ANDY_CHAT_VERSION`, `Stable tag`, adds a `= x.y.z =` changelog
   entry, and updates `Tested up to` only when that WordPress version was actually exercised. CI
   refuses a partial bump.
2. Merge to `main`. Releases come from `main` only.
3. When Jorge orders the release, tag the merge commit and push the tag:

   ```
   git tag v0.1.0 <sha>
   git push origin v0.1.0
   ```

4. `.github/workflows/deploy.yml` runs two jobs:
   - `package` calls `ci.yml` as a reusable workflow: the same lint, catalog, ZIP and Plugin Check
     steps as every push, uploading the ZIP as the `andy-chat` artifact.
   - `deploy` runs the release guard, checks that the tag equals the plugin version, downloads that
     exact artifact, unpacks it and hands the folder to `10up/action-wordpress-plugin-deploy` (pinned
     to the 2.3.0 commit) with `BUILD_DIR`, so what reaches SVN is byte-for-byte what Plugin Check
     validated. The action commits to `trunk` and copies it to `tags/<version>` in one SVN commit.
     `.distignore` is not consulted again.
5. There is no manual trigger and no way to skip the guard. A rehearsal without an SVN commit is
   possible by opening a PR that adds `with: dry-run: true` to the deploy step, tagging, then
   reverting. It is only possible after approval: the guard blocks the job first, and the action
   checks out `https://plugins.svn.wordpress.org/andy-chat/` before it reads `dry-run`, which only
   skips the final `svn commit`. So a rehearsal needs the approved slug, the stored credentials and
   `WPORG_DEPLOY_APPROVED=true`, exactly like a real release.

Smoke, after the run goes green:

- <https://wordpress.org/plugins/andy-chat/> shows the new version and the readme text.
- `https://downloads.wordpress.org/plugin/andy-chat.<version>.zip` unpacks to the same file list as the
  artifact (the directory re-zips SVN, so the archive bytes differ, the files should not).
- A test site running the previous version offers the update in Dashboard → Updates, and after
  updating, Settings → Andy Chat still shows the saved embed id and toggle.

## Rollback

A published version cannot be withdrawn from sites that already updated. Two options:

- Fix forward: bump to the next patch version and tag again. This is the normal path.
- Emergency: with the SVN credentials, edit `trunk/readme.txt` so `Stable tag` points at the previous
  tag and commit. The directory then serves the previous version to new installs and stops offering
  the broken one as an update. Jorge does this by hand; the workflow has no rollback step.

If the plugin must go away entirely, email plugins@wordpress.org from the account and ask for closure.

## What has and has not been exercised

Exercised on 2026-09-05: the local ZIP build and its reproducible hash, an upload-and-activate install
of that ZIP into a synthetic WordPress 7.1 / PHP 8.1 Playground site, CI on the branch, both scripts
against pass and fail inputs (see `docs/qa/2026-09-05-ticket-4-release-prep.md`).

Not exercised, by design: any SVN commit, the 10up action against the real directory, a tag push,
the GitHub environment and its secrets, and directory review itself. The first real tag after approval
is the first end-to-end run. Treat it as one and watch the log.
