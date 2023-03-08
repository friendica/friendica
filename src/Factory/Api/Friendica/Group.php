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

namespace Friendica\Factory\Api\Friendica;

use Friendica\BaseFactory;
use Friendica\Database\Database;
use Friendica\Network\HTTPException;
use Psr\Log\LoggerInterface;
use Friendica\Factory\Api\Twitter\User as TwitterUser;

class Group extends BaseFactory
{
	/** @var twitterUser entity */
	private $twitterUser;
	/** @var Database */
	private $dba;

	public function __construct(LoggerInterface $logger, TwitterUser $twitteruser, Database $dba)
	{
		parent::__construct($logger);

		$this->twitterUser = $twitteruser;
		$this->dba         = $dba;
	}

	/**
	 * @param int $id id of the group
	 * @return array
	 * @throws HTTPException\InternalServerErrorException
	 */
	public function createFromId(int $id): array
	{
		$group = $this->dba->selectFirst('group', [], ['id' => $id, 'deleted' => false]);
		if (empty($group)) {
			return [];
		}

		$user   = $this->twitterUser->createFromUserId($group['uid'])->toArray();
		$object = new \Friendica\Object\Api\Friendica\Group($group, $user);

		return $object->toArray();
	}
}
