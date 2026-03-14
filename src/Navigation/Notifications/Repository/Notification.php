<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Navigation\Notifications\Repository;

use Exception;
use Friendica\BaseCollection;
use Friendica\BaseRepository;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Model\Post\UserNotification;
use Friendica\Model\Verb;
use Friendica\Navigation\Notifications\Collection\Notifications as NotificationsCollection;
use Friendica\Navigation\Notifications\Entity\Notification as NotificationEntity;
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
	 * @throws NotFoundException
	 */
	private function selectOne(array $condition, array $params = []): NotificationEntity
	{
		$fields = $this->_selectFirstRowAsArray($condition, $params);

		return $this->factory->createFromTableRow($fields);
	}

	private function select(array $condition, array $params = []): NotificationsCollection
	{
		return new NotificationsCollection(parent::_select($condition, $params)->getArrayCopy());
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
	 * @throws NotFoundException
	 */
	public function selectOneById(int $id): NotificationEntity
	{
		return $this->selectOne(['id' => $id]);
	}

	public function selectOneForUser(int $uid, array $condition, array $params = []): NotificationEntity
	{
		$condition = DBA::mergeConditions($condition, ['uid' => $uid]);

		return $this->selectOne($condition, $params);
	}

	public function selectForUser(int $uid, array $condition = [], array $params = []): NotificationsCollection
	{
		$condition = DBA::mergeConditions($condition, ['uid' => $uid]);

		return $this->select($condition, $params);
	}


	/**
	 * Returns only the most recent notifications for the same conversation or contact
	 *
	 * @throws Exception
	 */
	public function selectDetailedForUser(int $uid): NotificationsCollection
	{
		$notify_type = $this->pconfig->get($uid, 'system', 'notify_type');
		if (!is_null($notify_type)) {
			$condition = ["`type` & ? != 0", $notify_type | UserNotification::TYPE_SHARED | UserNotification::TYPE_FOLLOW];
		} else {
			$condition = [];
		}

		if (!$this->pconfig->get($uid, 'system', 'notify_like')) {
			$condition = DBA::mergeConditions($condition, ['NOT `vid` IN (?, ?)', Verb::getID(Activity::LIKE), Verb::getID(Activity::DISLIKE)]);
		}

		if (!$this->pconfig->get($uid, 'system', 'notify_announce')) {
			$condition = DBA::mergeConditions($condition, ['`vid` != ?', Verb::getID(Activity::ANNOUNCE)]);
		}

		return $this->selectForUser($uid, $condition, ['limit' => 50, 'order' => ['id' => true]]);
	}

	/**
	 * Returns only the most recent notifications for the same conversation or contact
	 *
	 * @throws Exception
	 */
	public function selectDigestForUser(int $uid): NotificationsCollection
	{
		$values = [$uid];

		$type_condition = '';
		$notify_type    = $this->pconfig->get($uid, 'system', 'notify_type');
		if (!is_null($notify_type)) {
			$type_condition = 'AND `type` & ? != 0';
			$values[]       = $notify_type | UserNotification::TYPE_SHARED | UserNotification::TYPE_FOLLOW;
		}

		$like_condition = '';
		if (!$this->pconfig->get($uid, 'system', 'notify_like')) {
			$like_condition = 'AND NOT `vid` IN (?, ?)';
			$values[]       = Verb::getID(Activity::LIKE);
			$values[]       = Verb::getID(Activity::DISLIKE);
		}

		$announce_condition = '';
		if (!$this->pconfig->get($uid, 'system', 'notify_announce')) {
			$announce_condition = 'AND vid != ?';
			$values[]           = Verb::getID(Activity::ANNOUNCE);
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

		$entities = new NotificationsCollection();

		if (!is_iterable($rows)) {
			return $entities;
		}

		foreach ($rows as $fields) {
			$entities[] = $this->factory->createFromTableRow($fields);
		}

		return $entities;
	}

	public function selectAllForUser(int $uid): NotificationsCollection
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

		return new NotificationsCollection($BaseCollection->getArrayCopy(), $BaseCollection->getTotalCount());
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
	 * @throws Exception
	 */
	public function save(NotificationEntity $Notification): NotificationEntity
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
			'vid'           => Verb::getID(Activity::POST),
			'target-uri-id' => $itemUriId,
		];

		$conditionParent = [
			'vid'           => Verb::getID(Activity::POST),
			'parent-uri-id' => $itemUriId,
		];

		$this->logger->info('deleteForItem', ['conditionTarget' => $conditionTarget, 'conditionParent' => $conditionParent]);

		return
			$this->db->delete(self::$table_name, $conditionTarget)
			&& $this->db->delete(self::$table_name, $conditionParent);
	}
}
