<?php

/*
 * Copyright (C) 2010-2024, the Friendica project
 * SPDX-FileCopyrightText: 2010-2024 the Friendica project
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * The site template for pure content (e.g. (modals)
 *
 * This template is used e.g for bs modals. So outputs
 * only the pure content
 */

if(!empty($page['content'])) {
	echo $page['content'];
}
