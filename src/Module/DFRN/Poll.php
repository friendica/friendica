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

namespace Friendica\Module\DFRN;

use Friendica\BaseModule;
use Friendica\Core\System;
use Friendica\Model\User;
use Friendica\Module\Response;
use Friendica\Network\HTTPException;
use Friendica\Protocol\OStatus;

/**
 * DFRN Poll
 */
class Poll extends BaseModule
{
	protected function rawContent(array $request = [])
	{
		$owner = User::getByNickname(
			$this->parameters['nickname'] ?? '',
			['nickname', 'blocked', 'account_expired', 'account_removed', 'hidewall']
		);
		if (!$owner || $owner['account_expired'] || $owner['account_removed']) {
			throw new HTTPException\NotFoundException($this->t('User not found.'));
		}

		if ($owner['blocked'] || $owner['hidewall']) {
			throw new HTTPException\UnauthorizedException($this->t('Access to this profile has been restricted.'));
		}

		$last_update = $request['last_update'] ?? '';
		System::httpExit(OStatus::feed($owner['nickname'], $last_update, 10) ?? '', Response::TYPE_ATOM);
	}
}
