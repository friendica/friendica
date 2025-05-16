<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Security\PermissionSet\Repository;

use Exception;
use Friendica\BaseRepository;
use Friendica\Database\Database;
use Friendica\Model\Contact;
use Friendica\Model\Circle;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Security\PermissionSet\Exception\PermissionSetNotFoundException;
use Friendica\Security\PermissionSet\Exception\PermissionSetPersistenceException;
use Friendica\Security\PermissionSet\Factory\PermissionSet as PermissionSetFactory;
use Friendica\Security\PermissionSet\Collection\PermissionSets as PermissionSetsCollection;
use Friendica\Security\PermissionSet\Entity\PermissionSet as PermissionSetEntity;
use Friendica\Util\ACLFormatter;
use Psr\Log\LoggerInterface;

class PermissionSet extends BaseRepository
{
	/** @var int Virtual permission set id for public permission */
	const PUBLIC = 0;

	/** @var PermissionSetFactory */
	protected $factory;

	protected static $table_name = 'permissionset';

	/** @var ACLFormatter */
	private $aclFormatter;

	public function __construct(Database $database, LoggerInterface $logger, PermissionSetFactory $factory, ACLFormatter $aclFormatter)
	{
		parent::__construct($database, $logger, $factory);

		$this->aclFormatter = $aclFormatter;
	}

	/**
	 * @throws NotFoundException
	 * @throws Exception
	 */
	private function selectOne(array $condition, array $params = []): PermissionSetEntity
	{
		$fields = parent::_selectFirstRowAsArray($condition, $params);

		return $this->factory->createFromTableRow($fields);
	}

	/**
	 * @throws Exception
	 */
	private function select(array $condition, array $params = []): PermissionSetsCollection
	{
		return new PermissionSetsCollection(parent::_select($condition, $params)->getArrayCopy());
	}

	/**
	 * Converts a given PermissionSet into a DB compatible row array
	 */
	protected function convertToTableRow(PermissionSetEntity $permissionSet): array
	{
		return [
			'uid'       => $permissionSet->uid,
			'allow_cid' => $this->aclFormatter->toString($permissionSet->allow_cid),
			'allow_gid' => $this->aclFormatter->toString($permissionSet->allow_gid),
			'deny_cid'  => $this->aclFormatter->toString($permissionSet->deny_cid),
			'deny_gid'  => $this->aclFormatter->toString($permissionSet->deny_gid),
		];
	}

	/**
	 * @param int $id  A PermissionSet table row id or self::PUBLIC
	 * @param int $uid The owner of the PermissionSet
	 *
	 * @throws PermissionSetNotFoundException
	 * @throws PermissionSetPersistenceException
	 */
	public function selectOneById(int $id, int $uid): PermissionSetEntity
	{
		if ($id === self::PUBLIC) {
			return $this->factory->createFromString($uid);
		}

		try {
			return $this->selectOne(['id' => $id, 'uid' => $uid]);
		} catch (NotFoundException $exception) {
			throw new PermissionSetNotFoundException(sprintf('PermissionSet with id %d for user %u doesn\'t exist.', $id, $uid), $exception);
		} catch (Exception $exception) {
			throw new PermissionSetPersistenceException(sprintf('Cannot select PermissionSet %d for user %d', $id, $uid), $exception);
		}
	}

	/**
	 * Returns a permission set collection for a given contact
	 *
	 * @param int $cid Contact id of the visitor
	 * @param int $uid User id whom the items belong, used for ownership check.
	 *
	 * @throws PermissionSetPersistenceException
	 */
	public function selectByContactId(int $cid, int $uid): PermissionSetsCollection
	{
		try {
			$cdata = Contact::getPublicAndUserContactID($cid, $uid);
			if (!empty($cdata)) {
				$public_contact_str = $this->aclFormatter->toString($cdata['public']);
				$user_contact_str   = $this->aclFormatter->toString($cdata['user']);
				$cid                = $cdata['user'];
			} else {
				$public_contact_str = $this->aclFormatter->toString($cid);
				$user_contact_str   = '';
			}

			$circle_ids = [];
			if (!empty($user_contact_str) && $this->db->exists('contact', [
				'id'      => $cid,
				'uid'     => $uid,
				'blocked' => false
			])) {
				$circle_ids = Circle::getIdsByContactId($cid);
			}

			$circle_str = '<<>>'; // should be impossible to match
			foreach ($circle_ids as $circle_id) {
				$circle_str .= '|<' . preg_quote($circle_id) . '>';
			}

			if (!empty($user_contact_str)) {
				$condition = ["`uid` = ? AND (NOT (LOCATE(?, `deny_cid`) OR LOCATE(?, `deny_cid`) OR deny_gid REGEXP ?)
				AND (LOCATE(?, allow_cid) OR LOCATE(?, allow_cid) OR allow_gid REGEXP ? OR (allow_cid = '' AND allow_gid = '')))",
					$uid, $user_contact_str, $public_contact_str, $circle_str,
					$user_contact_str, $public_contact_str, $circle_str];
			} else {
				$condition = ["`uid` = ? AND (NOT (LOCATE(?, `deny_cid`) OR deny_gid REGEXP ?)
				AND (LOCATE(?, allow_cid) OR allow_gid REGEXP ? OR (allow_cid = '' AND allow_gid = '')))",
					$uid, $public_contact_str, $circle_str, $public_contact_str, $circle_str];
			}

			return $this->select($condition);
		} catch (Exception $exception) {
			throw new PermissionSetPersistenceException(sprintf('Cannot select PermissionSet for contact %d and user %d', $cid, $uid), $exception);
		}
	}

	/**
	 * Fetch the default PermissionSet for a given user, create it if it doesn't exist
	 *
	 * @param int $uid
	 *
	 * @throws PermissionSetPersistenceException
	 */
	public function selectDefaultForUser(int $uid): PermissionSetEntity
	{
		try {
			$self_contact = Contact::selectFirst(['id'], ['uid' => $uid, 'self' => true]);
		} catch (Exception $exception) {
			throw new PermissionSetPersistenceException(sprintf('Cannot select Contact for user %d', $uid), $exception);
		}

		if (!$this->db->isResult($self_contact)) {
			throw new PermissionSetPersistenceException(sprintf('No "self" contact found for user %d', $uid));
		}

		return $this->selectOrCreate($this->factory->createFromString(
			$uid,
			$this->aclFormatter->toString($self_contact['id'])
		));
	}

	/**
	 * Fetch the public PermissionSet
	 *
	 * @param int $uid
	 */
	public function selectPublicForUser(int $uid): PermissionSetEntity
	{
		return $this->factory->createFromString($uid, '', '', '', '', self::PUBLIC);
	}

	/**
	 * Selects or creates a PermissionSet based on its fields
	 *
	 * @throws PermissionSetPersistenceException
	 */
	public function selectOrCreate(PermissionSetEntity $permissionSet): PermissionSetEntity
	{
		if ($permissionSet->id) {
			return $permissionSet;
		}

		// Don't select/update Public permission sets
		if ($permissionSet->isPublic()) {
			return $this->selectPublicForUser($permissionSet->uid);
		}

		try {
			return $this->selectOne($this->convertToTableRow($permissionSet));
		} catch (NotFoundException $exception) {
			return $this->save($permissionSet);
		} catch (Exception $exception) {
			throw new PermissionSetPersistenceException(sprintf('Cannot select PermissionSet %d', $permissionSet->id ?? 0), $exception);
		}
	}

	/**
	 * @throws PermissionSetPersistenceException
	 */
	public function save(PermissionSetEntity $permissionSet): PermissionSetEntity
	{
		// Don't save/update the common public PermissionSet
		if ($permissionSet->isPublic()) {
			return $this->selectPublicForUser($permissionSet->uid);
		}

		$fields = $this->convertToTableRow($permissionSet);

		try {
			if ($permissionSet->id) {
				$this->db->update(self::$table_name, $fields, ['id' => $permissionSet->id]);
			} else {
				$this->db->insert(self::$table_name, $fields);

				$permissionSet = $this->selectOneById($this->db->lastInsertId(), $permissionSet->uid);
			}
		} catch (Exception $exception) {
			throw new PermissionSetPersistenceException(sprintf('Cannot save PermissionSet %d', $permissionSet->id ?? 0), $exception);
		}

		return $permissionSet;
	}
}
