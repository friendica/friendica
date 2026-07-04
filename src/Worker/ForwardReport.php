<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\Network\HTTPException;
use Friendica\Protocol\ActivityPub\Transmitter;

class ForwardReport
{
	/**
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \Exception
	 */
	public static function execute(int $reportId): void
	{
		Transmitter::sendModerationReport($reportId);
	}
}
