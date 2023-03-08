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

namespace Friendica\Contact\LocalRelationship\Repository;

use Friendica\Contact\LocalRelationship\Entity;
use Friendica\Contact\LocalRelationship\Exception;
use Friendica\Contact\LocalRelationship\Factory;
use Friendica\Database\Database;
use Friendica\Network\HTTPException;
use Psr\Log\LoggerInterface;

class LocalRelationship extends \Friendica\BaseRepository
{
	protected static $table_name = 'user-contact';

	/** @var Factory\LocalRelationship */
	protected $factory;

	public function __construct(Database $database, LoggerInterface $logger, Factory\LocalRelationship $factory)
	{
		parent::__construct($database, $logger, $factory);
	}

	/**
	 * @param int $uid
	 * @param int $cid
	 * @return Entity\LocalRelationship
	 * @throws HTTPException\NotFoundException
	 */
	public function selectForUserContact(int $uid, int $cid): Entity\LocalRelationship
	{
		return $this->_selectOne(['uid' => $uid, 'cid' => $cid]);
	}

	/**
	 * Returns the existing local relationship between a user and a public contact or a default
	 * relationship if it doesn't.
	 *
	 * @param int $uid
	 * @param int $cid
	 * @return Entity\LocalRelationship
	 * @throws HTTPException\NotFoundException
	 */
	public function getForUserContact(int $uid, int $cid): Entity\LocalRelationship
	{
		try {
			return $this->selectForUserContact($uid, $cid);
		} catch (HTTPException\NotFoundException $e) {
			return $this->factory->createFromTableRow(['uid' => $uid, 'cid' => $cid]);
		}
	}

	/**
	 * @param int $uid
	 * @param int $cid
	 * @return bool
	 * @throws \Exception
	 */
	public function existsForUserContact(int $uid, int $cid): bool
	{
		return $this->exists(['uid' => $uid, 'cid' => $cid]);
	}

	/**
	 * Converts a given local relationship into a DB compatible row array
	 *
	 * @param Entity\LocalRelationship $localRelationship
	 *
	 * @return array
	 */
	protected function convertToTableRow(Entity\LocalRelationship $localRelationship): array
	{
		return [
			'uid'                       => $localRelationship->userId,
			'cid'                       => $localRelationship->contactId,
			'uri-id'                    => $localRelationship->uriId,
			'blocked'                   => $localRelationship->blocked,
			'ignored'                   => $localRelationship->ignored,
			'collapsed'                 => $localRelationship->collapsed,
			'pending'                   => $localRelationship->pending,
			'rel'                       => $localRelationship->rel,
			'info'                      => $localRelationship->info,
			'notify_new_posts'          => $localRelationship->notifyNewPosts,
			'remote_self'               => $localRelationship->isRemoteSelf,
			'fetch_further_information' => $localRelationship->fetchFurtherInformation,
			'ffi_keyword_denylist'      => $localRelationship->ffiKeywordDenylist,
			'subhub'                    => $localRelationship->subhub,
			'hub-verify'                => $localRelationship->hubVerify,
			'protocol'                  => $localRelationship->protocol,
			'rating'                    => $localRelationship->rating,
			'priority'                  => $localRelationship->priority,
		];
	}

	/**
	 * @param Entity\LocalRelationship $localRelationship
	 *
	 * @return Entity\LocalRelationship
	 *
	 * @throws Exception\LocalRelationshipPersistenceException In case the underlying storage cannot save the LocalRelationship
	 */
	public function save(Entity\LocalRelationship $localRelationship): Entity\LocalRelationship
	{
		try {
			$fields = $this->convertToTableRow($localRelationship);

			$this->db->insert(self::$table_name, $fields, Database::INSERT_UPDATE);

			return $localRelationship;
		} catch (\Exception $exception) {
			throw new Exception\LocalRelationshipPersistenceException(sprintf('Cannot insert/update the local relationship %d for user %d', $localRelationship->contactId, $localRelationship->userId), $exception);
		}
	}
}
