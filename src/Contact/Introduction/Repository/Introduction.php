<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Contact\Introduction\Repository;

use Friendica\BaseRepository;
use Friendica\Contact\Introduction\Exception\IntroductionNotFoundException;
use Friendica\Contact\Introduction\Exception\IntroductionPersistenceException;
use Friendica\Contact\Introduction\Collection\Introductions as IntroductionsCollection;
use Friendica\Contact\Introduction\Entity\Introduction as IntroductionEntity;
use Friendica\Contact\Introduction\Factory\Introduction as IntroductionFactory;
use Friendica\Database\Database;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Util\DateTimeFormat;
use Psr\Log\LoggerInterface;

class Introduction extends BaseRepository
{
	/** @var IntroductionFactory */
	protected $factory;

	protected static $table_name = 'intro';

	public function __construct(Database $database, LoggerInterface $logger, IntroductionFactory $factory)
	{
		parent::__construct($database, $logger, $factory);
	}

	/**
	 * @throws NotFoundException the underlying exception if there's no Introduction with the given conditions
	 */
	private function selectOne(array $condition, array $params = []): IntroductionEntity
	{
		$fields = $this->_selectFirstRowAsArray($condition, $params);

		return $this->factory->createFromTableRow($fields);
	}

	/**
	 * Converts a given Introduction into a DB compatible row array
	 */
	protected function convertToTableRow(IntroductionEntity $introduction): array
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
	 * @throws IntroductionNotFoundException in case there is no Introduction with this id
	 */
	public function selectOneById(int $id, int $uid): IntroductionEntity
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
	 */
	public function selectForUser(int $uid, ?int $min_id = null, ?int $max_id = null, int $limit = self::LIMIT): IntroductionsCollection
	{
		try {
			$BaseCollection = parent::_selectByBoundaries(
				['`uid` = ? AND NOT `ignore`',$uid],
				['order' => ['id' => 'DESC']],
				$min_id,
				$max_id,
				$limit,
			);
		} catch (\Exception $e) {
			throw new IntroductionPersistenceException(sprintf('Cannot select Introductions for used %d', $uid), $e);
		}

		return new IntroductionsCollection($BaseCollection->getArrayCopy(), $BaseCollection->getTotalCount());
	}

	/**
	 * Selects the introduction for a given contact
	 *
	 * @throws IntroductionNotFoundException in case there is not Introduction for this contact
	 */
	public function selectForContact(int $cid): IntroductionEntity
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
	 * @throws IntroductionPersistenceException in case the underlying storage cannot delete the Introduction
	 */
	public function delete(IntroductionEntity $introduction): bool
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
	 * @throws IntroductionPersistenceException In case the underlying storage cannot save the Introduction
	 */
	public function save(IntroductionEntity $introduction): IntroductionEntity
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
