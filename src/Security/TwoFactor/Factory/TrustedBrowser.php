<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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

namespace Friendica\Security\TwoFactor\Factory;

use Friendica\BaseFactory;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Strings;

class TrustedBrowser extends BaseFactory
{
	public function createForUserWithUserAgent($uid, $userAgent): \Friendica\Security\TwoFactor\Model\TrustedBrowser
	{
		$trustedHash = Strings::getRandomHex();

		return new \Friendica\Security\TwoFactor\Model\TrustedBrowser(
			$trustedHash,
			$uid,
			$userAgent,
			DateTimeFormat::utcNow()
		);
	}

	public function createFromTableRow(array $row): \Friendica\Security\TwoFactor\Model\TrustedBrowser
	{
		return new \Friendica\Security\TwoFactor\Model\TrustedBrowser(
			$row['cookie_hash'],
			$row['uid'],
			$row['user_agent'],
			$row['created'],
			$row['last_used']
		);
	}
}
