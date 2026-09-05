#!/usr/bin/env bash
# Builds dist/andy-chat.zip, the exact package uploaded to WordPress.org.
# Only tracked files ship; .distignore lists what stays out. The build is reproducible with Info-ZIP
# zip 3.0 (macOS and ubuntu-latest): every entry carries the HEAD commit time in UTC, modes are fixed
# to 755/644 so the operator's umask does not leak into the archive, and entries are added in sorted
# order, so the same commit gives the same SHA-256 locally and in CI.
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

export TZ=UTC
commit_time="$(git -C "$root" log -1 --format=%cd --date=format-local:%Y%m%d%H%M.%S HEAD)"
find "$stage" -exec touch -t "$commit_time" {} +
find "$stage" -type d -exec chmod 755 {} +
find "$stage" -type f -exec chmod 644 {} +

(cd "$out" && find "$slug" -not -name '.DS_Store' | LC_ALL=C sort | zip -qX -@ "$slug.zip")
rm -rf "$stage"
echo "built $out/$slug.zip"
unzip -l "$out/$slug.zip"
