#!/bin/bash
# SPDX-FileCopyrightText: 2010-2025 the Friendica project
#
# SPDX-License-Identifier: AGPL-3.0-or-later
#
# this script checks or fixes php-files, based on the php-cs rules
#
# You can use the following variables:
# COMMAND ... the php-cs command to execute (default is "check --diff")
# TARGET_BRANCH ... set the target branch for the current branch to create a diff between them
#
##

COMMAND=${COMMAND:-"check --diff"}

if [ -n "${TARGET_BRANCH}" ]; then
	CHANGED_FILES="$(git diff --name-only --diff-filter=ACMRTUXB "$(git ls-remote -q | grep refs/heads/"${TARGET_BRANCH}"$ | awk '{print $1}' | xargs git rev-parse )".."$(git rev-parse HEAD)")";
else
	CHANGED_FILES="$(git diff --name-only --diff-filter=ACMRTUXB "$(git rev-parse HEAD)")";
fi

EXTRA_ARGS=$(printf -- '--path-mode=intersection\n--\n%s' "${CHANGED_FILES}");

./vendor/bin/php-cs-fixer ${COMMAND} --config=.php-cs-fixer.dist.php -v --using-cache=no ${EXTRA_ARGS}
