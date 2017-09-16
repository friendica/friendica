@echo off
pushd .
cd %~dp0
cd "../pear-pear.php.net/Text_Highlighter/bin"
set BIN_TARGET=%CD%\generate
popd
composer-php "%BIN_TARGET%" %*
