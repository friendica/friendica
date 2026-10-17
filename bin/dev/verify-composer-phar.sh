#!/bin/sh

# SPDX-FileCopyrightText: 2010-2026 the Friendica project
#
# SPDX-License-Identifier: CC0-1.0

# Verifies bin/composer.phar against the official release (byte compare + GPG signature).
# The phar is only read, never executed.
# Usage: bin/dev/verify-composer-phar.sh [path/to/composer.phar]

set -eu

# Packagist Conductors <contact@packagist.com>
FINGERPRINT="161DFBE342889F01DDAC4E61CBB3D576F2A0946F"

DIR="$(cd "$(dirname "$0")" && pwd)"
PHAR="$(realpath "${1:-$DIR/../composer.phar}")"
TMP="$(mktemp -d /tmp/composer-verify.XXXXXX)"
trap 'rm -rf "$TMP"' EXIT

VERSION="$(php -d display_errors=0 -r 'echo preg_match("/const VERSION = \x27([^\x27]+)\x27/", @file_get_contents("phar://" . $argv[1] . "/src/Composer/Composer.php"), $m) ? $m[1] : "";' "$PHAR")"
case "$VERSION" in
	[0-9]*.[0-9]*.[0-9]*) ;;
	*) echo "Cannot read a release version from $PHAR (got: '$VERSION')" >&2; exit 1 ;;
esac
echo "Version in phar: $VERSION"

BASE="https://getcomposer.org/download/$VERSION"
curl -fsSL -o "$TMP/composer.phar" "$BASE/composer.phar"
curl -fsSL -o "$TMP/composer.phar.asc" "$BASE/composer.phar.asc"

cmp "$PHAR" "$TMP/composer.phar" || { echo "FAIL: differs from official $VERSION" >&2; exit 1; }
echo "OK: byte-identical to $BASE/composer.phar"

export GNUPGHOME="$TMP/gnupg"
mkdir -m 700 "$GNUPGHOME"
gpg --batch --quiet --import "$DIR/composer-signing-key.asc"
gpg --batch --status-fd 1 --verify "$TMP/composer.phar.asc" "$PHAR" 2>/dev/null \
	| grep -q "^\[GNUPG:\] VALIDSIG .* $FINGERPRINT\$" \
	|| { echo "FAIL: no valid signature from $FINGERPRINT" >&2; exit 1; }
echo "OK: valid signature from $FINGERPRINT"
