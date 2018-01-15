@ECHO OFF
setlocal DISABLEDELAYEDEXPANSION
SET BIN_TARGET=%~dp0/../beelab/bowerphp/bin/bowerphp
php "%BIN_TARGET%" %*
