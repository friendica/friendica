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

namespace Friendica\Profile\ProfileField\Factory;

use Friendica\BaseFactory;
use Friendica\Profile\ProfileField\Exception\UnexpectedPermissionSetException;
use Friendica\Security\PermissionSet\Factory\PermissionSet as PermissionSetFactory;
use Friendica\Profile\ProfileField\Entity;
use Friendica\Capabilities\ICanCreateFromTableRow;
use Friendica\Security\PermissionSet\Entity\PermissionSet;
use Psr\Log\LoggerInterface;

class ProfileField extends BaseFactory implements ICanCreateFromTableRow
{
	/** @var PermissionSetFactory */
	private $permissionSetFactory;

	public function __construct(LoggerInterface $logger, PermissionSetFactory $permissionSetFactory)
	{
		parent::__construct($logger);

		$this->permissionSetFactory = $permissionSetFactory;
	}

	/**
	 * @inheritDoc
	 *
	 * @throws UnexpectedPermissionSetException
	 */
	public function createFromTableRow(array $row, PermissionSet $permissionSet = null): Entity\ProfileField
	{
		if (empty($permissionSet) &&
			(!array_key_exists('psid', $row) || !array_key_exists('allow_cid', $row) || !array_key_exists('allow_gid', $row) || !array_key_exists('deny_cid', $row) || !array_key_exists('deny_gid', $row))
		) {
			throw new UnexpectedPermissionSetException('Either set the PermissionSet fields (join) or the PermissionSet itself');
		}

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
				$row['psid']
			),
			$row['id'] ?? null
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
		int $id = null
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
