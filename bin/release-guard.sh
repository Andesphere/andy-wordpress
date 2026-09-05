#!/usr/bin/env bash
# Refuses a WordPress.org deployment until the directory has approved the plugin and the SVN
# credentials exist. Reads WPORG_DEPLOY_APPROVED, SVN_USERNAME and SVN_PASSWORD from the
# environment, reports only whether each is set, and never prints a value.
#
# Every condition is checked before exiting so one run lists everything that is still missing.
set -euo pipefail

missing=0

if [[ ${WPORG_DEPLOY_APPROVED:-} != "true" ]]; then
	echo "blocked: repository variable WPORG_DEPLOY_APPROVED is not 'true'." >&2
	echo "         Set it only after WordPress.org has approved the andy-chat slug and granted SVN access." >&2
	missing=1
fi

for name in SVN_USERNAME SVN_PASSWORD; do
	if [[ -z ${!name:-} ]]; then
		echo "blocked: secret $name is not set." >&2
		missing=1
	fi
done

if (( missing )); then
	echo "WordPress.org deployment stays off. See docs/release.md for who sets what." >&2
	exit 1
fi

echo "release guard passed: directory approval recorded and SVN credentials present."
