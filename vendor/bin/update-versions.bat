@ECHO OFF
setlocal DISABLEDELAYEDEXPANSION
SET BIN_TARGET=%~dp0/../vierbergenlars/php-semver/bin/update-versions
php "%BIN_TARGET%" %*
