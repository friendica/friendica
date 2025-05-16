<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\User\Settings\Repository;

use Exception;
use Friendica\BaseRepository;
use Friendica\Content\Pager;
use Friendica\Database\Database;
use Friendica\Federation\Repository\GServer;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\User\Settings\Collection\UserGServers as UserGServersCollection;
use Friendica\User\Settings\Entity\UserGServer as UserGServerEntity;
use Friendica\User\Settings\Factory\UserGServer as UserGServerFactory;
use Psr\Log\LoggerInterface;

class UserGServer extends BaseRepository
{
	protected static $table_name = 'user-gserver';

	/** @var UserGServerFactory */
	protected $factory;
	/** @var GServer */
	protected $gserverRepository;

	public function __construct(GServer $gserverRepository, Database $database, LoggerInterface $logger, UserGServerFactory $factory)
	{
		parent::__construct($database, $logger, $factory);

		$this->gserverRepository = $gserverRepository;
	}

	/**
	 * Returns an existing UserGServer entity or create one on the fly
	 *
	 * @param bool $hydrate Populate the related GServer entity
	 */
	public function getOneByUserAndServer(int $uid, int $gsid, bool $hydrate = true): UserGServerEntity
	{
		try {
			return $this->selectOneByUserAndServer($uid, $gsid, $hydrate);
		} catch (NotFoundException $e) {
			return $this->factory->createFromUserAndServer($uid, $gsid, $hydrate ? $this->gserverRepository->selectOneById($gsid) : null);
		}
	}

	/**
	 * @param bool $hydrate Populate the related GServer entity
	 * @throws NotFoundException
	 */
	public function selectOneByUserAndServer(int $uid, int $gsid, bool $hydrate = true): UserGServerEntity
	{
		return $this->_selectOne(['uid' => $uid, 'gsid' => $gsid], [], $hydrate);
	}

	public function save(UserGServerEntity $userGServer): UserGServerEntity
	{
		$fields = [
			'uid'     => $userGServer->uid,
			'gsid'    => $userGServer->gsid,
			'ignored' => $userGServer->ignored,
		];

		$this->db->insert(static::$table_name, $fields, Database::INSERT_UPDATE);

		return $userGServer;
	}

	public function selectByUserWithPagination(int $uid, Pager $pager): UserGServersCollection
	{
		return $this->_select(['uid' => $uid], ['limit' => [$pager->getStart(), $pager->getItemsPerPage()]]);
	}

	public function countByUser(int $uid): int
	{
		return $this->count(['uid' => $uid]);
	}

	public function isIgnoredByUser(int $uid, int $gsid): bool
	{
		return $this->exists(['uid' => $uid, 'gsid' => $gsid, 'ignored' => 1]);
	}

	/**
	 * @throws InternalServerErrorException in case the underlying storage cannot delete the record
	 */
	public function delete(UserGServerEntity $userGServer): bool
	{
		try {
			return $this->db->delete(self::$table_name, ['uid' => $userGServer->uid, 'gsid' => $userGServer->gsid]);
		} catch (Exception $exception) {
			throw new InternalServerErrorException('Cannot delete the UserGServer', $exception);
		}
	}

	protected function _selectOne(array $condition, array $params = [], bool $hydrate = true): UserGServerEntity
	{
		$fields = $this->db->selectFirst(static::$table_name, [], $condition, $params);
		if (!$this->db->isResult($fields)) {
			throw new NotFoundException();
		}

		return $this->factory->createFromTableRow($fields, $hydrate ? $this->gserverRepository->selectOneById($fields['gsid']) : null);
	}

	/**
	 * @throws Exception
	 */
	protected function _select(array $condition, array $params = [], bool $hydrate = true): UserGServersCollection
	{
		$rows = $this->db->selectToArray(static::$table_name, [], $condition, $params);

		$Entities = new UserGServersCollection();
		foreach ($rows as $fields) {
			$Entities[] = $this->factory->createFromTableRow($fields, $hydrate ? $this->gserverRepository->selectOneById($fields['gsid']) : null);
		}

		return $Entities;
	}

	public function listIgnoredByUser(int $uid): UserGServersCollection
	{
		return $this->_select(['uid' => $uid, 'ignored' => 1], [], false);
	}
}
