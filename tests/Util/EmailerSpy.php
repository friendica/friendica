<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

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
			'to' => $to,
			'subject' => $subject,
			'body' => $body,
			'headers' => $headers,
			'parameters' => $parameters,
		];

		return true;
	}
}
