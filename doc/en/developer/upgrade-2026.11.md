Upgrade from 2026.08 to 2026.11
===============================

All notable changes to **Friendica** will be documented in this file.
As an *Addon or Theme maintainer* you can inform yourself about all breaking changes and deprecations.

This project [promises Backward Compatibility](help/developer/index#backward-compatibility).

Optional (Deprecations)
-----------------------

This section contains deprecation notices. This changes will become mandatory in a future release.

- Deprecated `\Friendica\App::VERSION` will be removed in a future release. Use `\Friendica\App::version()` instead.

   The `VERSION` file is the only source of the version now.
   `App::VERSION` is no longer updated and keeps the value `2026.11-dev`.
   In templates use `{{$VERSION}}` instead of `{{constant('\Friendica\App::VERSION')}}`.
