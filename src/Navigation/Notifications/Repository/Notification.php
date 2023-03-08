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

namespace Friendica\Navigation\Notifications\Repository;

use Exception;
use Friendica\BaseCollection;
use Friendica\BaseRepository;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Model\Post\UserNotification;
use Friendica\Model\Verb;
use Friendica\Navigation\Notifications\Collection;
use Friendica\Navigation\Notifications\Entity;
use Friendica\Navigation\Notifications\Factory;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Protocol\Activity;
use Friendica\Util\DateTimeFormat;
use Psr\Log\LoggerInterface;

class Notification extends BaseRepository
{
	/** @var Factory\Notification  */
	protected $factory;

	protected static $table_name = 'notification';

	/** @var IManagePersonalConfigValues */
	private $pconfig;

	public function __construct(IManagePersonalConfigValues $pconfig, Database $database, LoggerInterface $logger, Factory\Notification $factory)
	{
		parent::__construct($database, $logger, $factory);

		$this->pconfig = $pconfig;
	}

	/**
	 * @param array $condition
	 * @param array $params
	 * @return Entity\Notification
	 * @throws NotFoundException
	 */
	private function selectOne(array $condition, array $params = []): Entity\Notification
	{
		return parent::_selectOne($condition, $params);
	}

	private function select(array $condition, array $params = []): Collection\Notifications
	{
		return new Collection\Notifications(parent::_select($condition, $params)->getArrayCopy());
	}

	public function countForUser($uid, array $condition, array $params = []): int
	{
		$condition = DBA::mergeConditions($condition, ['uid' => $uid]);

		return $this->count($condition, $params);
	}

	public function existsForUser($uid, array $condition): bool
	{
		$condition = DBA::mergeConditions($condition, ['uid' => $uid]);

		return $this->exists($condition);
	}

	/**
	 * @param int $id
	 * @return Entity\Notification
	 * @throws NotFoundException
	 */
	public function selectOneById(int $id): Entity\Notification
	{
		return $this->selectOne(['id' => $id]);
	}

	public function selectOneForUser(int $uid, array $condition, array $params = []): Entity\Notification
	{
		$condition = DBA::mergeConditions($condition, ['uid' => $uid]);

		return $this->selectOne($condition, $params);
	}

	public function selectForUser(int $uid, array $condition = [], array $params = []): Collection\Notifications
	{
		$condition = DBA::mergeConditions($condition, ['uid' => $uid]);

		return $this->select($condition, $params);
	}


	/**
	 * Returns only the most recent notifications for the same conversation or contact
	 *
	 * @param int $uid
	 *
	 * @return Collection\Notifications
	 * @throws Exception
	 */
	public function selectDetailedForUser(int $uid): Collection\Notifications
	{
		$notify_type = $this->pconfig->get($uid, 'system', 'notify_type');
		if (!is_null($notify_type)) {
			$condition = ["`type` & ? != 0", $notify_type | UserNotification::TYPE_SHARED | UserNotification::TYPE_FOLLOW];
		} else {
			$condition = [];
		}

		if (!$this->pconfig->get($uid, 'system', 'notify_like')) {
			$condition = DBA::mergeConditions($condition, ['NOT `vid` IN (?, ?)', Verb::getID(\Friendica\Protocol\Activity::LIKE), Verb::getID(\Friendica\Protocol\Activity::DISLIKE)]);
		}

		if (!$this->pconfig->get($uid, 'system', 'notify_announce')) {
			$condition = DBA::mergeConditions($condition, ['`vid` != ?', Verb::getID(\Friendica\Protocol\Activity::ANNOUNCE)]);
		}

		return $this->selectForUser($uid, $condition, ['limit' => 50, 'order' => ['id' => true]]);
	}

	/**
	 * Returns only the most recent notifications for the same conversation or contact
	 *
	 * @param int $uid
	 *
	 * @return Collection\Notifications
	 * @throws Exception
	 */
	public function selectDigestForUser(int $uid): Collection\Notifications
	{
		$values = [$uid];

		$type_condition = '';
		$notify_type = $this->pconfig->get($uid, 'system', 'notify_type');
		if (!is_null($notify_type)) {
			$type_condition = 'AND `type` & ? != 0';
			$values[] = $notify_type | UserNotification::TYPE_SHARED | UserNotification::TYPE_FOLLOW;
		}

		$like_condition = '';
		if (!$this->pconfig->get($uid, 'system', 'notify_like')) {
			$like_condition = 'AND NOT `vid` IN (?, ?)';
			$values[] = Verb::getID(\Friendica\Protocol\Activity::LIKE);
			$values[] = Verb::getID(\Friendica\Protocol\Activity::DISLIKE);
		}

		$announce_condition = '';
		if (!$this->pconfig->get($uid, 'system', 'notify_announce')) {
			$announce_condition = 'AND vid != ?';
			$values[] = Verb::getID(\Friendica\Protocol\Activity::ANNOUNCE);
		}

		$rows = $this->db->p("
		SELECT notification.*
		FROM notification
		WHERE `id` IN (
		    SELECT MAX(`id`)
		    FROM `notification`
		    WHERE `uid` = ?
			$type_condition
		    $like_condition
		    $announce_condition
		    GROUP BY IFNULL(`parent-uri-id`, `actor-id`)
		)
		ORDER BY `seen`, `id` DESC
		LIMIT 50
		", ...$values);

		$Entities = new Collection\Notifications();
		foreach ($rows as $fields) {
			$Entities[] = $this->factory->createFromTableRow($fields);
		}

		return $Entities;
	}

	public function selectAllForUser(int $uid): Collection\Notifications
	{
		return $this->selectForUser($uid);
	}

	/**
	 * @param array    $condition
	 * @param array    $params
	 * @param int|null $min_id Retrieve models with an id no fewer than this, as close to it as possible
	 * @param int|null $max_id Retrieve models with an id no greater than this, as close to it as possible
	 * @param int      $limit
	 *
	 * @return BaseCollection
	 * @throws Exception
	 * @see _selectByBoundaries
	 */
	public function selectByBoundaries(array $condition = [], array $params = [], int $min_id = null, int $max_id = null, int $limit = self::LIMIT)
	{
		$BaseCollection = parent::_selectByBoundaries($condition, $params, $min_id, $max_id, $limit);

		return new Collection\Notifications($BaseCollection->getArrayCopy(), $BaseCollection->getTotalCount());
	}

	public function setAllSeenForUser(int $uid, array $condition = []): bool
	{
		$condition = DBA::mergeConditions($condition, ['uid' => $uid]);

		return $this->db->update(self::$table_name, ['seen' => true], $condition);
	}

	public function setAllDismissedForUser(int $uid, array $condition = []): bool
	{
		$condition = DBA::mergeConditions($condition, ['uid' => $uid]);

		return $this->db->update(self::$table_name, ['dismissed' => true], $condition);
	}

	/**
	 * @param Entity\Notification $Notification
	 * @return Entity\Notification
	 * @throws Exception
	 */
	public function save(Entity\Notification $Notification): Entity\Notification
	{
		$fields = [
			'uid'           => $Notification->uid,
			'vid'           => Verb::getID($Notification->verb),
			'type'          => $Notification->type,
			'actor-id'      => $Notification->actorId,
			'target-uri-id' => $Notification->targetUriId,
			'parent-uri-id' => $Notification->parentUriId,
			'seen'          => $Notification->seen,
			'dismissed'     => $Notification->dismissed,
		];

		if ($Notification->id) {
			$this->db->update(self::$table_name, $fields, ['id' => $Notification->id]);
		} else {
			$fields['created'] = DateTimeFormat::utcNow();
			$this->db->insert(self::$table_name, $fields, Database::INSERT_IGNORE);

			$Notification = $this->selectOneById($this->db->lastInsertId());
		}

		return $Notification;
	}

	public function deleteForUserByVerb(int $uid, string $verb, array $condition = []): bool
	{
		$condition['uid'] = $uid;
		$condition['vid'] = Verb::getID($verb);

		$this->logger->notice('deleteForUserByVerb', ['condition' => $condition]);

		return $this->db->delete(self::$table_name, $condition);
	}

	public function deleteForItem(int $itemUriId): bool
	{
		$conditionTarget = [
			'vid' => Verb::getID(Activity::POST),
			'target-uri-id' => $itemUriId,
		];

		$conditionParent = [
			'vid' => Verb::getID(Activity::POST),
			'parent-uri-id' => $itemUriId,
		];

		$this->logger->info('deleteForItem', ['conditionTarget' => $conditionTarget, 'conditionParent' => $conditionParent]);

		return
			$this->db->delete(self::$table_name, $conditionTarget)
			&& $this->db->delete(self::$table_name, $conditionParent);
	}
}
