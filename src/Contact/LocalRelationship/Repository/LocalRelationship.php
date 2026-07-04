<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Contact\LocalRelationship\Repository;

use Exception;
use Friendica\BaseRepository;
use Friendica\Contact\LocalRelationship\Entity\LocalRelationship as LocalRelationshipEntity;
use Friendica\Contact\LocalRelationship\Exception\LocalRelationshipPersistenceException;
use Friendica\Contact\LocalRelationship\Factory\LocalRelationship as LocalRelationshipFactory;
use Friendica\Database\Database;
use Friendica\Network\HTTPException\NotFoundException;
use Psr\Log\LoggerInterface;

class LocalRelationship extends BaseRepository
{
	protected static $table_name = 'user-contact';

	public function __construct(
		Database $database,
		LoggerInterface $logger,
		private readonly LocalRelationshipFactory $entityFactory,
	) {
		parent::__construct($database, $logger, $entityFactory);
	}

	/**
	 * @throws NotFoundException
	 */
	public function selectForUserContact(int $uid, int $cid): LocalRelationshipEntity
	{
		$fields = $this->_selectFirstRowAsArray(['uid' => $uid, 'cid' => $cid]);

		return $this->getFactory()->createFromTableRow($fields);
	}

	/**
	 * Returns the existing local relationship between a user and a public contact or a default
	 * relationship if it doesn't.
	 *
	 * @throws NotFoundException
	 */
	public function getForUserContact(int $uid, int $cid): LocalRelationshipEntity
	{
		try {
			return $this->selectForUserContact($uid, $cid);
		} catch (NotFoundException) {
			return $this->getFactory()->createFromTableRow(['uid' => $uid, 'cid' => $cid]);
		}
	}

	/**
	 * @throws Exception
	 */
	public function existsForUserContact(int $uid, int $cid): bool
	{
		return $this->exists(['uid' => $uid, 'cid' => $cid]);
	}

	protected function getFactory(): LocalRelationshipFactory
	{
		return $this->entityFactory;
	}

	/**
	 * Converts a given local relationship into a DB compatible row array
	 */
	protected function convertToTableRow(LocalRelationshipEntity $localRelationship): array
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
			'remote_self'               => $localRelationship->remoteSelf,
			'fetch_further_information' => $localRelationship->fetchFurtherInformation,
			'ffi_keyword_denylist'      => $localRelationship->ffiKeywordDenylist,
			'hub-verify'                => $localRelationship->hubVerify,
			'protocol'                  => $localRelationship->protocol,
			'rating'                    => $localRelationship->rating,
			'priority'                  => $localRelationship->priority,
		];
	}

	/**
	 * @throws LocalRelationshipPersistenceException In case the underlying storage cannot save the LocalRelationship
	 */
	public function save(LocalRelationshipEntity $localRelationship): LocalRelationshipEntity
	{
		try {
			$fields = $this->convertToTableRow($localRelationship);

			$this->db->insert(self::$table_name, $fields, Database::INSERT_UPDATE);

			return $localRelationship;
		} catch (Exception $exception) {
			throw new LocalRelationshipPersistenceException(sprintf('Cannot insert/update the local relationship %d for user %d', $localRelationship->contactId, $localRelationship->userId), $exception);
		}
	}
}
