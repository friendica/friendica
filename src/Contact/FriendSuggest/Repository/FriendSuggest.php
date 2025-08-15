<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Contact\FriendSuggest\Repository;

use Friendica\BaseRepository;
use Friendica\Contact\FriendSuggest\Collection\FriendSuggests as FriendSuggestsCollection;
use Friendica\Contact\FriendSuggest\Entity\FriendSuggest as FriendSuggestEntity;
use Friendica\Contact\FriendSuggest\Exception\FriendSuggestNotFoundException;
use Friendica\Contact\FriendSuggest\Exception\FriendSuggestPersistenceException;
use Friendica\Contact\FriendSuggest\Factory\FriendSuggest as FriendSuggestFactory;
use Friendica\Database\Database;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Util\DateTimeFormat;
use Psr\Log\LoggerInterface;

class FriendSuggest extends BaseRepository
{
	/** @var FriendSuggestFactory */
	protected $factory;

	protected static $table_name = 'fsuggest';

	public function __construct(Database $database, LoggerInterface $logger, FriendSuggestFactory $factory)
	{
		parent::__construct($database, $logger, $factory);
	}

	private function convertToTableRow(FriendSuggestEntity $fsuggest): array
	{
		return [
			'uid'     => $fsuggest->uid,
			'cid'     => $fsuggest->cid,
			'name'    => $fsuggest->name,
			'url'     => $fsuggest->url,
			'request' => $fsuggest->request,
			'photo'   => $fsuggest->photo,
			'note'    => $fsuggest->note,
			'created' => $fsuggest->created->format(DateTimeFormat::MYSQL),
		];
	}

	/**
	 * @throws NotFoundException The underlying exception if there's no FriendSuggest with the given conditions
	 */
	private function selectOne(array $condition, array $params = []): FriendSuggestEntity
	{
		$fields = $this->_selectFirstRowAsArray($condition, $params);

		return $this->factory->createFromTableRow($fields);
	}

	/**
	 * @throws \Exception
	 */
	private function select(array $condition, array $params = []): FriendSuggestsCollection
	{
		return new FriendSuggestsCollection(parent::_select($condition, $params)->getArrayCopy());
	}

	/**
	 * @throws FriendSuggestNotFoundException in case there's no suggestion for this id
	 */
	public function selectOneById(int $id): FriendSuggestEntity
	{
		try {
			return $this->selectOne(['id' => $id]);
		} catch (NotFoundException $e) {
			throw new FriendSuggestNotFoundException(sprintf('No FriendSuggest found for id %d', $id));
		}
	}

	/**
	 * @throws FriendSuggestPersistenceException In case the underlying storage cannot select the suggestion
	 */
	public function selectForContact(int $cid): FriendSuggestsCollection
	{
		try {
			return $this->select(['cid' => $cid]);
		} catch (\Exception $e) {
			throw new FriendSuggestPersistenceException(sprintf('Cannot select FriendSuggestion for contact %d', $cid));
		}
	}

	/**
	 * @throws FriendSuggestNotFoundException in case the underlying storage cannot save the suggestion
	 */
	public function save(FriendSuggestEntity $fsuggest): FriendSuggestEntity
	{
		try {
			$fields = $this->convertToTableRow($fsuggest);

			if ($fsuggest->id) {
				$this->db->update(self::$table_name, $fields, ['id' => $fsuggest->id]);
				return $this->selectOneById($fsuggest->id);
			} else {
				$this->db->insert(self::$table_name, $fields);
				return $this->selectOneById($this->db->lastInsertId());
			}
		} catch (\Exception $exception) {
			throw new FriendSuggestNotFoundException(sprintf('Cannot insert/update the FriendSuggestion %d for user %d', $fsuggest->id, $fsuggest->uid), $exception);
		}
	}

	/**
	 * @throws FriendSuggestNotFoundException in case the underlying storage cannot delete the suggestion
	 */
	public function delete(FriendSuggestsCollection $fsuggests): bool
	{
		try {
			$ids = $fsuggests->column('id');
			return $this->db->delete(self::$table_name, ['id' => $ids]);
		} catch (\Exception $exception) {
			throw new FriendSuggestNotFoundException('Cannot delete the FriendSuggestions', $exception);
		}
	}
}
