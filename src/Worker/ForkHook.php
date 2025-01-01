<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\Core\Hook;

class ForkHook
{
	public static function execute($name, $hook, $data)
	{
		Hook::callSingle($name, $hook, $data);
	}
}
