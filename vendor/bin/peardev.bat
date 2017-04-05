@echo off
pushd .
cd %~dp0
cd "../pear-pear.php.net/PEAR/bin"
set BIN_TARGET=%CD%\peardev.bat
popd
call "%BIN_TARGET%" %*
