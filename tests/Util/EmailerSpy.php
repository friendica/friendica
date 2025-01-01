<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\Util;

use Friendica\Util\Emailer;

class EmailerSpy extends Emailer
{
	public static $MAIL_DATA;

	/**
	 * Wrapper around the mail() method (mainly used to overwrite for tests)
	 * @see mail()
	 *
	 * @param string $to         Recipient of this mail
	 * @param string $subject    Subject of this mail
	 * @param string $body       Message body of this mail
	 * @param string $headers    Headers of this mail
	 * @param string $parameters Additional (sendmail) parameters of this mail
	 *
	 * @return bool true if the mail was successfully accepted for delivery, false otherwise.
	 */
	protected function mail(string $to, string $subject, string $body, string $headers, string $parameters)
	{
		self::$MAIL_DATA = [
			'to'         => $to,
			'subject'    => $subject,
			'body'       => $body,
			'headers'    => $headers,
			'parameters' => $parameters,
		];

		return true;
	}
}
