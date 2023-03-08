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

namespace Friendica\Security\PermissionSet\Factory;

use Friendica\BaseFactory;
use Friendica\Capabilities\ICanCreateFromTableRow;
use Friendica\Security\PermissionSet\Entity;
use Friendica\Util\ACLFormatter;
use Psr\Log\LoggerInterface;

class PermissionSet extends BaseFactory implements ICanCreateFromTableRow
{
	/** @var ACLFormatter */
	protected $formatter;

	public function __construct(LoggerInterface $logger, ACLFormatter $formatter)
	{
		parent::__construct($logger);

		$this->formatter = $formatter;
	}

	/**
	 * @inheritDoc
	 */
	public function createFromTableRow(array $row): Entity\PermissionSet
	{
		return new Entity\PermissionSet(
			$row['uid'],
			$this->formatter->expand($row['allow_cid'] ?? ''),
			$this->formatter->expand($row['allow_gid'] ?? ''),
			$this->formatter->expand($row['deny_cid'] ?? ''),
			$this->formatter->expand($row['deny_gid'] ?? ''),
			$row['id'] ?? null
		);
	}

	/**
	 * Creates a new PermissionSet based on it's fields
	 *
	 * @param int    $uid
	 * @param string $allow_cid
	 * @param string $allow_gid
	 * @param string $deny_cid
	 * @param string $deny_gid
	 *
	 * @return Entity\PermissionSet
	 */
	public function createFromString(
		int $uid,
		string $allow_cid = '',
		string $allow_gid = '',
		string $deny_cid = '',
		string $deny_gid = '',
		int $id = null): Entity\PermissionSet
	{
		return $this->createFromTableRow([
			'uid'       => $uid,
			'allow_cid' => $allow_cid,
			'allow_gid' => $allow_gid,
			'deny_cid'  => $deny_cid,
			'deny_gid'  => $deny_gid,
			'id'        => $id,
		]);
	}
}
