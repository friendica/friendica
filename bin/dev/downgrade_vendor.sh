#!/bin/bash

# Print array helpers (https://stackoverflow.com/a/17841619)
function join_by { local d=$1; shift; local f=$1; shift; printf %s "$f" "${@/#/$d}"; }

# Fail fast
set -e

package_paths=()
rootPackage=$(./bin/composer.phar info -s -N)

why_not_version="7.0"
# Switch to production, to calculate the packages
./bin/composer.phar install --no-dev --no-progress --ansi
PACKAGES=$(./bin/composer.phar why-not php "$why_not_version" --no-interaction | grep -o "\S*\/\S*" | grep -v "$rootPackage")
# Switch to dev again
./bin/composer.phar install --no-progress --ansi

if [ -n "$PACKAGES" ]; then
	for package in $PACKAGES
	do
		path=$(./bin/composer.phar info "$package" --path | cut -d' ' -f2-)

		package_paths+=($path)
		echo "[Package to downgrade] $package (under '$path')"
	done

fi

paths=$(join_by " " "${package_paths[@]}")

if [ -n "$paths" ]; then
	./vendor/bin/rector process "$paths" --ansi
else
	echo "Nothing to downgrade"
fi
