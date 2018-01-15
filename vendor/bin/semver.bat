@ECHO OFF
setlocal DISABLEDELAYEDEXPANSION
SET BIN_TARGET=%~dp0/../vierbergenlars/php-semver/bin/semver
php "%BIN_TARGET%" %*
