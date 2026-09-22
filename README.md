# andy-wordpress
Andy Partner for WordPress: your Andy Partner AI agent on your site. Published on WordPress.org as Andy Partner, slug andy-chat (the slug is permanent).

## Development

The widget-only v1 is specified in [the approved spec](https://github.com/Andesphere/andy-wordpress/issues/1). Implementation tracks [widget installation](https://github.com/Andesphere/andy-wordpress/issues/2), [Agent access checks](https://github.com/Andesphere/andy-wordpress/issues/3), and [directory publication](https://github.com/Andesphere/andy-wordpress/issues/4).

Releases: `bin/build-zip.sh` builds the WordPress.org package, CI validates it on every push, and a
`vX.Y.Z` tag publishes it once the directory has approved the plugin and SVN credentials exist. The
runbook, ownership and current state are in [docs/release.md](docs/release.md).
