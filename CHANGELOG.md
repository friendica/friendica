# Changelog

All notable changes to the **Friendica Core** will be documented in this file.
As an Addon maintainer or Friendica Developer you can inform yourself about all deprecations or breaking changes.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project [promises Backward Compatibility](doc/Developers-Intro.md#backward-compatibility).

## [Version 2025.02](https://github.com/friendica/friendica/compare/2024.12-1...develop) - (unreleased)

### Added

- New composer script `bin/composer.phar run install:prod` to install all dependencies except the dev-libraries, but with autoloader optimization for production servers.
- New interface `Friendica\AppHelper` as replacement for `Friendica\App` added.
- New method `Friendica\DI::appHelper()` added to get the implementation of the `AppHelper`.
- New interface `Friendica\Core\Addon\AddonHelper` added as replacement for the `Friendica\Core\Addon` class.
- New method `Friendica\DI::addonHelper()` added to get the implementation of the `Friendica\Core\Addon\AddonHelper`.
- New class `Friendica\Core\Addon\AddonInfo` added as an value object for the header information about an addon.
- New interface `Friendica\Core\Logger\Factory\LoggerFactory` added so addons can provide a custom `Psr\Log\LoggerInterface` implementation.

### Changed

- **BREAKING**: The class `Friendica\App` was completely refactored and marked as internal, work with `Friendica\AppHelper` instead.
- **BREAKING**: The `contact_block_end` hook provides a HTML string instead of an array.
- The `bin/composer.phar install` command no longer optimizes the autoloader file to avoid various problems when adding/removing classes in dev. Run `bin/composer.phar install -o` if you want autoloader optimization.
- Downgrade shebang from `bin/bash` to `bin/sh` in `bin/console`.
- Uncaught exceptions are logged as CRITICAL instead as ERROR.

### Fixed

- The command `bin/console.php addon list enabled` shows a list of enabled addons instead of all addons, by @Art4 in [#14687](https://github.com/friendica/friendica/pull/14687).
- The command `bin/console.php addon list disabled` shows a list of disabled addons instead of an empty list, by @Art4 in [#14687](https://github.com/friendica/friendica/pull/14687).
- The `contact_block_end` hook has been fixed and can now change the content of the contact widget.

### Deprecated

- `bin/daemon.php` is deprecated in favor of `bin/console daemon` by @nupplaphil in [#14642](https://github.com/friendica/friendica/pull/14642)
- `bin/jetstream.php` is deprecated in favor of `bin/console jetstream` by @nupplaphil in [#14655](https://github.com/friendica/friendica/pull/14655)
- `bin/worker.php` is deprecated in favor of `bin/console worker` by @nupplaphil in [#14659](https://github.com/friendica/friendica/pull/14659)
- Providing strategies via `strategies.config.php` file in addons is deprecated and will stop working in 5 months, please use PHP hooks instead and remove the `strategies.config.php` file in your addon.
- Class `Friendica\Core\Logger` is deprecated, use constructor injection or `Friendica\Di::logger()` instead.
- Class `Friendica\Core\Logger\Factory\AbstractLoggerTypeFactory` is deprecated and will be removed after 5 months, implement `\Friendica\Core\Logger\Factory\LoggerFactory` instead.
- Class `Friendica\Core\Logger\Factory\Logger` is deprecated and will be removed after 5 months, implement `\Friendica\Core\Logger\Factory\LoggerFactory` instead.
- Class `Friendica\Core\Logger\Factory\StreamLogger` is deprecated and will be removed after 5 months, implement `\Friendica\Core\Logger\Factory\LoggerFactory` instead.
- Class `Friendica\Core\Logger\Factory\SyslogLogger` is deprecated and will be removed after 5 months, implement `\Friendica\Core\Logger\Factory\LoggerFactory` instead.
- The method `\Friendica\BaseRepository::_selectOne()` is deprecated, use `\Friendica\BaseRepository::_selectFirstRowAsArray()` instead.

### Removed

- **BREAKING**: `Friendica\DI::app()` was removed, use `Friendica\DI::appHelper()` instead.
- **BREAKING**: `Friendica\Core\Logger::enableWorker()` and `Friendica\Core\Logger::disableWorker()` were removed.
