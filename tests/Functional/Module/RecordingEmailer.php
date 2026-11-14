<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Functional\Module;

use Friendica\Object\EMail\IEmail;
use Friendica\Util\Emailer;

/**
 * Records mails instead of sending them.
 */
final class RecordingEmailer extends Emailer
{
	/** @var IEmail[] */
	public array $sent = [];

	/** Thrown instead of recording the mail, if set */
	public ?\Exception $failure = null;

	public function send(IEmail $email): bool
	{
		if ($this->failure !== null) {
			throw $this->failure;
		}

		$this->sent[] = $email;

		return true;
	}
}
