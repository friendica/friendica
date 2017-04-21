@ECHO OFF
setlocal DISABLEDELAYEDEXPANSION
SET BIN_TARGET=%~dp0/../league/html-to-markdown/bin/html-to-markdown
php "%BIN_TARGET%" %*
