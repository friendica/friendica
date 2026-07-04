<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\Protocol\Relay;

class UpdateTagRelaySubscriptions
{
	public static function execute()
	{
		Relay::updateTagRelaySubscriptions();
	}
}
