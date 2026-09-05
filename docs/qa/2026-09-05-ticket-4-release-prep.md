# Ticket #4 QA: release candidate package and deploy guard (account-independent part)

Tested commit: c7ee396 (PR #8 round 1) produced the release candidate,
`dist/andy-chat.zip` SHA-256 `44405314366ebf14ab8124d7f5b8914f0bbaaf86af74e236b22fdc6116194ae9`,
uploaded by CI run 33982483244 as artifact `andy-chat` and rebuilt locally with the same hash. The
review follow-up commit on top of it (mode normalization in `bin/build-zip.sh`, this document and
`docs/release.md`) changes no shipped file; its archive hash differs only because entries carry the
HEAD commit time. The PR names that head and its CI hash, and a `diff -r` of the two unpacked trees is
empty. Environment:
WordPress Playground CLI 3.1.52 (WASM PHP 8.1, WordPress 7.1, SQLite), port 18844, synthetic site
"Andy Chat RC QA (synthetic)", synthetic embed id `qa0synthetic0embed0id0000000000a`. No production
site, no Andy account, no real Agent, no WordPress.org account and no SVN repository were touched. No
tag was pushed. The only outbound requests were public HTTP reads of wordpress.org, php.net,
andypartner.com and GitHub.

Behavior of the widget, the settings screen and the access check is unchanged from #2 and #3 and their
QA documents still apply. This round only re-proves that the package built by `bin/build-zip.sh`
installs and behaves, and tests the new release scripts.

## Checklist

```gherkin
Feature: Release candidate package

  Scenario: ZIP holds only the plugin                                                          PASS
    When bin/build-zip.sh runs on the release commit
    Then dist/andy-chat.zip contains one andy-chat/ folder with andy-chat.php, readme.txt,
      uninstall.php, LICENSE, includes/ (3 files), assets/access-check.js, languages/ (4 files)
    And it contains no .github, bin, docs, dotfiles or *.zip
    And grep for SVN_PASSWORD, api key, private key or password finds nothing

  Scenario: Same commit, same bytes (Info-ZIP zip 3.0 on macOS and ubuntu-latest)                PASS
    Given the working tree at c7ee396 with the mode fix applied to bin/build-zip.sh
    When the ZIP is built at umask 022, 002 and 077, and at umask 002 with TZ=Asia/Tokyo
    Then all four runs print 44405314366ebf14ab8124d7f5b8914f0bbaaf86af74e236b22fdc6116194ae9
    And every directory entry is drwxr-xr-x and every file entry -rw-r--r--
    And the CI artifact of run 33982483244, downloaded and hashed, is the same value
    (Before the fix, review R1 measured three different hashes for the three umasks.)
    And at the follow-up head the push-run artifact is byte-identical to the local build at all
      three umasks, while the pull_request run's artifact differs only in the DOS timestamp of
      GitHub's temporary merge commit; diff -r of the unpacked c7ee396 and follow-up trees is empty

  Scenario: Upload, activate, configure, load, disable, reactivate                              PASS
    Given a fresh Playground site
    When the ZIP is uploaded through update.php?action=upload-plugin from an admin session
    Then "Plugin installed successfully"; the Plugins screen lists Andy Chat 0.1.0 inactive
    When it is activated from the Plugins screen
    Then the row is active with no "unexpected output" notice
    And Settings → Andy Chat renders the H1, the toggle and the access-check button
    When the synthetic id is saved with the toggle on
    Then "Settings saved. The Andy widget is on for every public page."
    And an anonymous visitor (Playground auto-login suppressed, no admin bar in the HTML) gets
      exactly one <script async id="andy-chat-widget-js" src="https://app.andypartner.com/widget.js">
      preceded by window.ANDY_CHATBOT_ID = "qa0synthetic0embed0id0000000000a" and
      window.ANDY_CHAT_API_URL = "https://app.andypartner.com/api"
    When the toggle is saved off
    Then the home page contains no reference to app.andypartner.com
    When the plugin is deactivated and reactivated
    Then the home page still has no widget and the settings page still shows the saved id

Feature: Release scripts

  Scenario: bin/check-release-version.sh                                                        PASS
    Given the release commit
    Then no argument, "v0.1.0" and "refs/tags/v0.1.0" all print 0.1.0 and exit 0
    And "v0.2.0" fails: tag version '0.2.0' differs from the plugin version '0.1.0'
    Given a scratch copy with ANDY_CHAT_VERSION 0.1.1     Then it fails naming the constant
    Given Stable tag 0.2.0                                 Then it fails naming the stable tag
    Given the "= 0.1.0 =" changelog line renamed          Then it fails naming the changelog
    Given Version header "0.1"                             Then it fails: not MAJOR.MINOR.PATCH

  Scenario: bin/release-guard.sh never publishes without approval and credentials              PASS
    Given no environment                       Then exit 1 listing the variable and both secrets
    Given only WPORG_DEPLOY_APPROVED=true      Then exit 1 listing both secrets
    Given WPORG_DEPLOY_APPROVED=yes plus both   Then exit 1: the variable must be exactly "true"
    Given WPORG_DEPLOY_APPROVED=true plus both  Then exit 0 "release guard passed"
    And no run printed a credential value

  Scenario: Workflows parse and CI runs on the branch                                          see PR
    Then both workflow files load as YAML; deploy.yml triggers only on tags v[0-9]+.[0-9]+.[0-9]+,
      has no workflow_dispatch, and its deploy job cannot start before the package job
    And the CI run for the PR head is linked from the PR with the artifact name and ZIP hash

Feature: Metadata against primary sources (2026-09-05)

  Scenario: Versions, slug, links, readme                                                       PASS
    Then api.wordpress.org version-check offers 7.1 and stable-check marks 7.1 "latest"
    And php.net lists 8.1 outside security support since 2025-12-31 (floor only, baseline kept)
    And wordpress.org/plugins/andy-chat/ redirects to search; the info API says "Plugin not found."
    And the readme validator reports no errors; one warning: contributor "andesphere" is not a
      WordPress.org user yet (expected until andyChat#1954 creates the account)
    And sign-up, privacy, terms, data-deletion, widget.js and the plugin URI all answer 200
    And widget.js is 95502 bytes with PERSIST_SESSION:!1, matching the disclosure
```

## Not covered here

- No SVN commit, no run of `10up/action-wordpress-plugin-deploy`, no GitHub environment, no tag push.
  The deploy job's steps after the guard are unexecuted until the first real release.
- The guard was run as a shell script with environment variables; the workflow wiring of `vars.*`
  and `secrets.*` to those variables is reviewed by reading, not by a run.
- The fresh-site "activation alone loads nothing" case was proven in the #2 QA. Here the equivalent
  proof is deactivate plus reactivate with the toggle off, which also shows no widget.
- No real Agent: the widget bubble did not render. That gap is unchanged from #2 and #3.
- Playground uses WASM PHP and SQLite. CI's Plugin Check runs in wp-env with MySQL.
