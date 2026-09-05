#!/usr/bin/env bash
# Confirms that one version string appears everywhere WordPress and the deploy action read it:
# the "Version:" plugin header, the ANDY_CHAT_VERSION constant, "Stable tag:" in readme.txt and a
# readme changelog entry. With no argument it checks that the four agree with each other; with an
# argument (a version, or a "v"-prefixed tag) it also requires them to equal that value.
# Prints the version on success so callers can reuse it.
set -euo pipefail

root="$(cd "$(dirname "$0")/.." && pwd)"
expected="${1:-}"
expected="${expected#refs/tags/}"
expected="${expected#v}"

header="$(sed -n 's/^ \* Version: *\([0-9][^ ]*\) *$/\1/p' "$root/andy-chat.php" | head -n1)"
constant="$(sed -n "s/^define( 'ANDY_CHAT_VERSION', '\([^']*\)' );$/\1/p" "$root/andy-chat.php" | head -n1)"
stable="$(sed -n 's/^Stable tag: *\([^ ]*\) *$/\1/p' "$root/readme.txt" | head -n1)"

fail() {
	echo "release version check failed: $*" >&2
	exit 1
}

[[ -n $header ]] || fail "no Version header in andy-chat.php"
[[ -n $constant ]] || fail "no ANDY_CHAT_VERSION constant in andy-chat.php"
[[ -n $stable ]] || fail "no Stable tag in readme.txt"
[[ $header =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]] || fail "Version header '$header' is not MAJOR.MINOR.PATCH"
[[ $constant == "$header" ]] || fail "ANDY_CHAT_VERSION '$constant' differs from the Version header '$header'"
[[ $stable == "$header" ]] || fail "Stable tag '$stable' differs from the Version header '$header'"
grep -qx "= $header =" "$root/readme.txt" || fail "readme.txt has no '= $header =' changelog entry"

if [[ -n $expected && $expected != "$header" ]]; then
	fail "tag version '$expected' differs from the plugin version '$header'"
fi

echo "$header"
