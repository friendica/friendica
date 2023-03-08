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

namespace Friendica\Contact\Introduction\Repository;

use Friendica\BaseRepository;
use Friendica\Contact\Introduction\Exception\IntroductionNotFoundException;
use Friendica\Contact\Introduction\Exception\IntroductionPersistenceException;
use Friendica\Contact\Introduction\Collection;
use Friendica\Contact\Introduction\Entity;
use Friendica\Contact\Introduction\Factory;
use Friendica\Database\Database;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Util\DateTimeFormat;
use Psr\Log\LoggerInterface;

class Introduction extends BaseRepository
{
	/** @var Factory\Introduction */
	protected $factory;

	protected static $table_name = 'intro';

	public function __construct(Database $database, LoggerInterface $logger, Factory\Introduction $factory)
	{
		parent::__construct($database, $logger, $factory);
	}

	/**
	 * @param array $condition
	 * @param array $params
	 *
	 * @return Entity\Introduction
	 *
	 * @throws NotFoundException the underlying exception if there's no Introduction with the given conditions
	 */
	private function selectOne(array $condition, array $params = []): Entity\Introduction
	{
		return parent::_selectOne($condition, $params);
	}

	/**
	 * Converts a given Introduction into a DB compatible row array
	 *
	 * @param Entity\Introduction $introduction
	 *
	 * @return array
	 */
	protected function convertToTableRow(Entity\Introduction $introduction): array
	{
		return [
			'uid'         => $introduction->uid,
			'contact-id'  => $introduction->cid,
			'suggest-cid' => $introduction->sid,
			'knowyou'     => $introduction->knowyou ? 1 : 0,
			'note'        => $introduction->note,
			'hash'        => $introduction->hash,
			'ignore'      => $introduction->ignore ? 1 : 0,
			'datetime'    => $introduction->datetime->format(DateTimeFormat::MYSQL),
		];
	}

	/**
	 * @param int $id
	 * @param int $uid
	 *
	 * @return Entity\Introduction
	 *
	 * @throws IntroductionNotFoundException in case there is no Introduction with this id
	 */
	public function selectOneById(int $id, int $uid): Entity\Introduction
	{
		try {
			return $this->selectOne(['id' => $id, 'uid' => $uid]);
		} catch (NotFoundException $exception) {
			throw new IntroductionNotFoundException(sprintf('There is no Introduction with the ID %d for the user %d', $id, $uid), $exception);
		}
	}

	/**
	 * Selects introductions for a given user
	 *
	 * @param int      $uid
	 * @param int|null $min_id
	 * @param int|null $max_id
	 * @param int      $limit
	 *
	 * @return Collection\Introductions
	 */
	public function selectForUser(int $uid, int $min_id = null, int $max_id = null, int $limit = self::LIMIT): Collection\Introductions
	{
		try {
			$BaseCollection = parent::_selectByBoundaries(
				['`uid` = ? AND NOT `ignore`',$uid],
				['order' => ['id' => 'DESC']],
				$min_id, $max_id, $limit);
		} catch (\Exception $e) {
			throw new IntroductionPersistenceException(sprintf('Cannot select Introductions for used %d', $uid), $e);
		}

		return new Collection\Introductions($BaseCollection->getArrayCopy(), $BaseCollection->getTotalCount());
	}

	/**
	 * Selects the introduction for a given contact
	 *
	 * @param int $cid
	 *
	 * @return Entity\Introduction
	 *
	 * @throws IntroductionNotFoundException in case there is not Introduction for this contact
	 */
	public function selectForContact(int $cid): Entity\Introduction
	{
		try {
			return $this->selectOne(['contact-id' => $cid]);
		} catch (NotFoundException $exception) {
			throw new IntroductionNotFoundException(sprintf('There is no Introduction for the contact %d', $cid), $exception);
		}
	}

	public function countActiveForUser($uid, array $params = []): int
	{
		try {
			return $this->count(['ignore' => false, 'uid' => $uid], $params);
		} catch (\Exception $e) {
			throw new IntroductionPersistenceException(sprintf('Cannot count Introductions for used %d', $uid), $e);
		}
	}

	/**
	 * Checks, if the suggested contact already exists for the user
	 *
	 * @param int $sid
	 * @param int $uid
	 *
	 * @return bool
	 */
	public function suggestionExistsForUser(int $sid, int $uid): bool
	{
		try {
			return $this->exists(['uid' => $uid, 'suggest-cid' => $sid]);
		} catch (\Exception $e) {
			throw new IntroductionPersistenceException(sprintf('Cannot check suggested Introduction for contact %d and user %d', $sid, $uid), $e);
		}
	}

	/**
	 * @param Entity\Introduction $introduction
	 *
	 * @return bool
	 *
	 * @throws IntroductionPersistenceException in case the underlying storage cannot delete the Introduction
	 */
	public function delete(Entity\Introduction $introduction): bool
	{
		if (!$introduction->id) {
			return false;
		}

		try {
			return $this->db->delete(self::$table_name, ['id' => $introduction->id]);
		} catch (\Exception $e) {
			throw new IntroductionPersistenceException(sprintf('Cannot delete Introduction with id %d', $introduction->id), $e);
		}
	}

	/**
	 * @param Entity\Introduction $introduction
	 *
	 * @return Entity\Introduction
	 *
	 * @throws IntroductionPersistenceException In case the underlying storage cannot save the Introduction
	 */
	public function save(Entity\Introduction $introduction): Entity\Introduction
	{
		try {
			$fields = $this->convertToTableRow($introduction);

			if ($introduction->id) {
				$this->db->update(self::$table_name, $fields, ['id' => $introduction->id]);
				return $this->factory->createFromTableRow($fields);
			} else {
				$this->db->insert(self::$table_name, $fields);
				return $this->selectOneById($this->db->lastInsertId(), $introduction->uid);
			}
		} catch (\Exception $exception) {
			throw new IntroductionPersistenceException(sprintf('Cannot insert/update the Introduction %d for user %d', $introduction->id, $introduction->uid), $exception);
		}
	}
}
