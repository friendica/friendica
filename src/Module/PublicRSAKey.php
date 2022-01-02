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

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\DI;
use Friendica\Model\User;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Util\Crypto;
use Friendica\Util\Strings;

/**
 * prints the public RSA key of a user
 */
class PublicRSAKey extends BaseModule
{
	protected function rawContent(array $request = [])
	{
		if (empty($this->parameters['nick'])) {
			throw new BadRequestException();
		}

		$nick = $this->parameters['nick'];

		$user = User::getByNickname($nick, ['spubkey']);
		if (empty($user) || empty($user['spubkey'])) {
			throw new BadRequestException();
		}

		Crypto::pemToMe($user['spubkey'], $modulus, $exponent);

		header('Content-type: application/magic-public-key');
		echo 'RSA' . '.' . Strings::base64UrlEncode($modulus, true) . '.' . Strings::base64UrlEncode($exponent, true);

		exit();
	}
}
