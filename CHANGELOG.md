# Changelog

All notable changes to the **Friendica Core** will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project [promises Backward Compatibility](doc/Developers-Intro.md#backward-compatibility).

## [Version 2025.02](https://github.com/friendica/friendica/compare/2024.12-1...develop) - (unreleased)

### Added

- New interface `Friendica\AppHelper` as replacement for `Friendica\App` added.

### Changed

- **BREAKING**: The class `Friendica\App` was completely refactored and marked as internal, work with `Friendica\AppHelper` instead.

### Deprecated

- `bin/daemon.php` is deprecated in favor of `bin/console daemon` by @nupplaphil in [#14642](https://github.com/friendica/friendica/pull/14642)
- `bin/jetstream.php` is deprecated in favor of `bin/console jetstream` by @nupplaphil in [#14655](https://github.com/friendica/friendica/pull/14655)
- `bin/worker.php` is deprecated in favor of `bin/console worker` by @nupplaphil in [#14659](https://github.com/friendica/friendica/pull/14659)
- `Friendica\Core\Logger` is deprecated, use constructor injection or `Friendica\Di::logger()` instead.

### Removed

- **BREAKING**: `Friendica\DI::app()` was removed, use `Friendica\DI::appHelper()` instead.
- **BREAKING**: `Friendica\Core\Logger::enableWorker()` and `Friendica\Core\Logger::disableWorker()` were removed.

## [Version 2024.12-1](https://github.com/friendica/friendica/compare/2024.12...2024.12-1) - 2025-01-01

### Fixed

- Fix CI releaser by @nupplaphil in [#14651](https://github.com/friendica/friendica/pull/14651)

## [Version 2024.12](https://github.com/friendica/friendica/compare/2024.08...2024.12) - 2024-12-31

### Added

- Added admin info to stats module @nupplaphil
- Added an option to exclude postings with images without ALT text by @annando
- Added an option to hide custom emojis by @annando
- Added support for HLS by @annando
- Added devcontainer for Friendica by @ne20002
- Added jetstream support for AT protocol by @annando
- Added native probe support for AT protocol by @annando

### Changed

- Updates to the translations AR, BG, CA, CS, DE, EO, ES, ET, FR, GD, HU, IS, IT, JA, NL, PL, RU, SV
- Updates to the documentation by @annando, @bmillwood and @tobiasd
- Updates to the themes (frio) by @haheute
- Friendica Core is now REUSE compliant by @tobiasd
- General code cleanup by @annando, @nupplaphil and @mexon
- Improved federation with Bluesky, Hubzilla, Peertube, threads, Wordpress by @annando
- Improved the API by @annando
- Improved display of contact connection state by @annando
- Improved handling of bad webfinger requests by @annando, @mexon and @zotanmew
- Improved the order of actions on the 2FA settings page by @tobiasd
- Improved server type detection by @annando
- Improved content negotiation by @annando
- Improved expiration by @annando
- Improved contact archiving by @annando
- Improved delivery of content by @annando
- Improved displayed project icons by @annando
- Improved splitting of long postings via connectors by @annando
- Improved contact import by @annando
- Improved URL detection in searches by @annando
- Improved handling of blocked users by @annando

### Fixed

- Fixed a bug in creating app specific passwords @nupplaphil
- Fixed a bug in importing some notes from Mastodon by @annando
- Fixed a bug with postings from buffer including images by @annando
- Fixed a apache2 problem with unsafe URLs by @annando
- Fixed a bug in the contact settings by @annando
- Fixed a bug with latin1 encoded databases by @annando
- Fixed a bug while uploading server blocklists by @ne20002
- Fixed a bug while parsing events by @annando
- Fixed a bug in the initial registry settings by @annando
- Fixed a bug in 0Auth with buffer by @annando
- Fixed a problem with rich HTML content by @annando
- Fixed a bug with private comments by @annando
- Fixed a bug in gettext by @tobiasd
- Fixed a bug in the installation process by @tobiasd
- Fixed schema.org issue by @annando

### Removed

- Removed custom emojis from contact names by @annando
- Removed OStatus support by @annando
