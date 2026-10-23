#!/bin/sh
# SPDX-FileCopyrightText: 2010-2026 the Friendica project
# SPDX-License-Identifier: AGPL-3.0-or-later

# Prepares the branch "merge-<rc>-into-develop": develop with <rc> merged in,
# while keeping the version of develop.
#
# Usage: bin/dev/sync-rc-into-develop.sh <rc-branch> [<remote>]
#
# Expects <remote>/develop and <remote>/<rc-branch> to be fetched.
# Exit codes: 0 = branch prepared, 3 = develop already contains <rc-branch>, 1 = error

set -eu

RC_BRANCH="${1:?Usage: $0 <rc-branch> [<remote>]}"
REMOTE="${2:-origin}"
SYNC_BRANCH="merge-${RC_BRANCH}-into-develop"
VERSION_FILES="VERSION database.sql src/App.php view/lang/C/messages.po"

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
		if ! is_version_file "${FILE}"; then
			echo "Merge conflict in ${FILE}, resolve the sync manually"
			git merge --abort
			exit 1
		fi
		git show ":1:${FILE}" > "${TMP}/base"
		git show ":2:${FILE}" > "${TMP}/ours"
		git show ":3:${FILE}" > "${TMP}/theirs"
		set_version "${FILE}" "${TMP}/base"
		set_version "${FILE}" "${TMP}/theirs"
		if ! git merge-file "${TMP}/ours" "${TMP}/base" "${TMP}/theirs"; then
			echo "Merge conflict in ${FILE} beyond the version line, resolve the sync manually"
			git merge --abort
			exit 1
		fi
		cp "${TMP}/ours" "${FILE}"
	done
	rm -rf "${TMP}"
fi

for FILE in ${VERSION_FILES}; do
	set_version "${FILE}" "${FILE}"
	git add "${FILE}"
done

git commit -q -m "Merge ${RC_BRANCH} into develop"
echo "Prepared ${SYNC_BRANCH} with version ${DEV_VERSION}"
