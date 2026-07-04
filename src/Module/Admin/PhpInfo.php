<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Admin;

use Friendica\Core\System;
use Friendica\Module\BaseAdmin;

class PhpInfo extends BaseAdmin
{
	protected function rawContent(array $request = []): never
	{
		self::checkAdminAccess();

		self::checkFormSecurityTokenForbiddenOnError('phpinfo', 't');

		phpinfo();
		System::exit();
	}
}
