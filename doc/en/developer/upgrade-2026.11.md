Upgrade from 2026.08 to 2026.11
===============================

All notable changes to **Friendica** will be documented in this file.
As an *Addon or Theme maintainer* you can inform yourself about all breaking changes and deprecations.

This project [promises Backward Compatibility](help/developer/index#backward-compatibility).

Mandatory (Breaking changes)
----------------------------

This section contains backward compatibility breaks, make sure your code is compatible with these entries before upgrading.

- Removed `\Friendica\Security\ExAuth`, `\Friendica\Util\PidFile` and `\Friendica\App::processEjabberd()`.

   The ejabberd external auth now runs as `bin/console.php auth_ejabberd` (`\Friendica\Console\EJabberdAuthDaemon` with `\Friendica\Security\EJabberdAuth`).
   The config key `jabber.lockpath` is no longer used.

Optional (Deprecations)
-----------------------

This section contains deprecation notices. This changes will become mandatory in a future release.

- Deprecated `\Friendica\App::VERSION` will be removed in a future release. Use `\Friendica\App::version()` instead.

   The `VERSION` file is the only source of the version now.
   `App::VERSION` is no longer updated and keeps the value `2026.11-dev`.
   In templates use `{{$VERSION}}` instead of `{{constant('\Friendica\App::VERSION')}}`.

- Deprecated `bin/auth_ejabberd.php` will be removed in a future release. Use `bin/console.php auth_ejabberd` instead.
