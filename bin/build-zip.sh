#!/usr/bin/env bash
# Builds dist/andy-chat.zip, the exact package uploaded to WordPress.org.
# Only tracked files ship; .distignore lists what stays out.
set -euo pipefail

root="$(cd "$(dirname "$0")/.." && pwd)"
slug="andy-chat"
out="$root/dist"
stage="$out/$slug"

skip() {
	local file=$1 pattern
	while IFS= read -r pattern; do
		[[ -z $pattern ]] && continue
		pattern=${pattern%/}
		# shellcheck disable=SC2053 -- pattern globbing is intended
		if [[ $file == $pattern || $file == $pattern/* ]]; then
			return 0
		fi
	done <"$root/.distignore"
	return 1
}

rm -rf "$out"
mkdir -p "$stage"

git -C "$root" ls-files -z | while IFS= read -r -d '' file; do
	skip "$file" && continue
	mkdir -p "$stage/$(dirname "$file")"
	cp "$root/$file" "$stage/$file"
done

(cd "$out" && zip -qr "$slug.zip" "$slug" -x '*.DS_Store')
rm -rf "$stage"
echo "built $out/$slug.zip"
unzip -l "$out/$slug.zip"
