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

namespace Friendica\Module\ActivityPub;

use Friendica\BaseModule;
use Friendica\Model\User;
use Friendica\Protocol\ActivityPub;
use Friendica\Util\HTTPSignature;

/**
 * ActivityPub Outbox
 */
class Outbox extends BaseModule
{
	protected function rawContent(array $request = [])
	{
		if (empty($this->parameters['nickname'])) {
			throw new \Friendica\Network\HTTPException\NotFoundException();
		}

		$owner = User::getOwnerDataByNick($this->parameters['nickname']);
		if (empty($owner)) {
			throw new \Friendica\Network\HTTPException\NotFoundException();
		}

		$page = $_REQUEST['page'] ?? null;

		$requester = HTTPSignature::getSigner('', $_SERVER);
		$outbox = ActivityPub\Transmitter::getOutbox($owner, $page, $requester);
		header('Content-Type: application/activity+json');
		echo json_encode($outbox);
		exit();
	}
}
