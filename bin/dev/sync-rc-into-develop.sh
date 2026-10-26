#!/bin/sh
# SPDX-FileCopyrightText: 2010-2026 the Friendica project
# SPDX-License-Identifier: AGPL-3.0-or-later

# Prepares the branch "merge-<rc>-into-develop": develop with <rc> merged in,
# while keeping the version of develop.
#
# Usage: bin/dev/sync-rc-into-develop.sh <rc-branch> [<remote>]
#
# Expects <remote>/develop and <remote>/<rc-branch> to be fetched.
# Needs xgettext when view/lang/C/messages.po conflicts, PHP when database.sql or its docs conflict.
# Exit codes: 0 = branch prepared, 3 = develop already contains <rc-branch>, 1 = error

set -eu

RC_BRANCH="${1:?Usage: $0 <rc-branch> [<remote>]}"
REMOTE="${2:-origin}"
SYNC_BRANCH="merge-${RC_BRANCH}-into-develop"
MESSAGES_PO="view/lang/C/messages.po"
DATABASE_DOC="doc/en/spec/database"
VERSION_FILES="VERSION database.sql src/App.php ${MESSAGES_PO}"
REBUILD_MESSAGES=0
REBUILD_DATABASE=0

# Rewrites the version in a copy of one of the VERSION_FILES
# $1 = path the content belongs to, $2 = file to rewrite
set_version() {
	case "$1" in
		VERSION)
			sed -i "1s/.*/${DEV_VERSION}/" "$2" ;;
		database.sql)
			sed -i "2s/^-- Friendica [^ ]*/-- Friendica ${DEV_VERSION}/" "$2" ;;
		src/App.php)
			sed -i "s/^\([[:space:]]*public const VERSION[[:space:]]*= '\)[^']*'/\1${DEV_VERSION}'/" "$2" ;;
		view/lang/C/messages.po)
			sed -i "s/^\(\"Project-Id-Version: \)[^\\\\\"]*/\1${DEV_VERSION}/" "$2" ;;
	esac
}

# Marks a generated file for rebuild, fails for all other files
mark_rebuild() {
	case "$1" in
		"${MESSAGES_PO}") REBUILD_MESSAGES=1 ;;
		database.sql | "${DATABASE_DOC}"/*) REBUILD_DATABASE=1 ;;
		*) return 1 ;;
	esac
}

is_version_file() {
	case " ${VERSION_FILES} " in
		*" $1 "*) return 0 ;;
		*) return 1 ;;
	esac
}

if git merge-base --is-ancestor "${REMOTE}/${RC_BRANCH}" "${REMOTE}/develop"; then
	echo "develop already contains ${RC_BRANCH}"
	exit 3
fi

git checkout -q -B "${SYNC_BRANCH}" "${REMOTE}/develop"
DEV_VERSION="$(cat VERSION)"

if ! git merge -q --no-ff --no-commit "${REMOTE}/${RC_BRANCH}"; then
	# Version lines conflict whenever both branches changed their version since the last sync.
	# Rewrite base and rc side to the develop version, so only real changes are merged.
	TMP="$(mktemp -d)"
	for FILE in $(git diff --name-only --diff-filter=U); do
		if is_version_file "${FILE}"; then
			git show ":1:${FILE}" > "${TMP}/base"
			git show ":2:${FILE}" > "${TMP}/ours"
			git show ":3:${FILE}" > "${TMP}/theirs"
			set_version "${FILE}" "${TMP}/base"
			set_version "${FILE}" "${TMP}/theirs"
			if git merge-file "${TMP}/ours" "${TMP}/base" "${TMP}/theirs"; then
				cp "${TMP}/ours" "${FILE}"
				continue
			fi
		fi
		# Generated files conflict on unrelated changes (e.g. line references), so they are rebuilt after the loop
		if ! mark_rebuild "${FILE}"; then
			echo "Merge conflict in ${FILE}, resolve the sync manually"
			git merge --abort
			exit 1
		fi
	done
	rm -rf "${TMP}"
fi

if [ "${REBUILD_MESSAGES}" -eq 1 ]; then
	bin/run_xgettext.sh > /dev/null
fi

if [ "${REBUILD_DATABASE}" -eq 1 ]; then
	if [ ! -d vendor ]; then
		# The autoloader scans addon/, composer fails without it
		mkdir -p addon
		bin/composer.phar install -q --no-dev --prefer-dist
	fi
	php bin/console.php dbstructure dumpsql > database.sql
	git add "${DATABASE_DOC}"
fi

for FILE in ${VERSION_FILES}; do
	set_version "${FILE}" "${FILE}"
	git add "${FILE}"
done

git commit -q -m "Merge ${RC_BRANCH} into develop"
echo "Prepared ${SYNC_BRANCH} with version ${DEV_VERSION}"
