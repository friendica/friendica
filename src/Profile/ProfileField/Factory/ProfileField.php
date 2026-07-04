<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Profile\ProfileField\Factory;

use Friendica\BaseFactory;
use Friendica\Profile\ProfileField\Exception\UnexpectedPermissionSetException;
use Friendica\Security\PermissionSet\Factory\PermissionSet as PermissionSetFactory;
use Friendica\Profile\ProfileField\Entity;
use Friendica\Capabilities\ICanCreateFromTableRow;
use Friendica\Model\User;
use Friendica\Security\PermissionSet\Entity\PermissionSet;
use Psr\Log\LoggerInterface;

class ProfileField extends BaseFactory implements ICanCreateFromTableRow
{
	public function __construct(
		LoggerInterface $logger,
		private readonly PermissionSetFactory $permissionSetFactory,
	) {
		parent::__construct($logger);
	}

	/**
	 * @inheritDoc
	 *
	 * @throws UnexpectedPermissionSetException
	 */
	public function createFromTableRow(array $row, ?PermissionSet $permissionSet = null): Entity\ProfileField
	{
		if (empty($permissionSet)
			&& (!array_key_exists('psid', $row) || !array_key_exists('allow_cid', $row) || !array_key_exists('allow_gid', $row) || !array_key_exists('deny_cid', $row) || !array_key_exists('deny_gid', $row))
		) {
			throw new UnexpectedPermissionSetException('Either set the PermissionSet fields (join) or the PermissionSet itself');
		}

		$owner = User::getOwnerDataById($row['uid']);

		return new Entity\ProfileField(
			$row['uid'],
			$row['order'],
			$row['label'],
			$row['value'],
			new \DateTime($row['created'] ?? 'now', new \DateTimeZone('UTC')),
			new \DateTime($row['edited'] ?? 'now', new \DateTimeZone('UTC')),
			$permissionSet ?? $this->permissionSetFactory->createFromString(
				$row['uid'],
				$row['allow_cid'],
				$row['allow_gid'],
				$row['deny_cid'],
				$row['deny_gid'],
				$row['psid'],
			),
			$row['id']       ?? null,
			$owner['uri-id'] ?? null,
		);
	}

	/**
	 * Creates a ProfileField instance based on it's values
	 *
	 * @param int           $uid
	 * @param int           $order
	 * @param string        $label
	 * @param string        $value
	 * @param PermissionSet $permissionSet
	 * @param int|null      $id
	 *
	 * @return Entity\ProfileField
	 * @throws UnexpectedPermissionSetException
	 */
	public function createFromValues(
		int $uid,
		int $order,
		string $label,
		string $value,
		PermissionSet $permissionSet,
		?int $id = null,
	): Entity\ProfileField {
		return $this->createFromTableRow([
			'uid'   => $uid,
			'order' => $order,
			'psid'  => $permissionSet->id,
			'label' => $label,
			'value' => $value,
			'id'    => $id,
		], $permissionSet);
	}
}
