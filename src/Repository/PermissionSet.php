<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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

namespace Friendica\Repository;

use Friendica\BaseRepository;
use Friendica\Collection;
use Friendica\Database\Database;
use Friendica\Model;
use Friendica\Model\Group;
use Friendica\Network\HTTPException;
use Friendica\Util\ACLFormatter;
use Psr\Log\LoggerInterface;

class PermissionSet extends BaseRepository
{
	/** @var int Virtual permission set id for public permission */
	const PUBLIC = 0;

	protected static $table_name = 'permissionset';

	protected static $model_class = Model\PermissionSet::class;

	protected static $collection_class = Collection\PermissionSets::class;

	/** @var ACLFormatter */
	private $aclFormatter;

	public function __construct(Database $dba, LoggerInterface $logger, ACLFormatter $aclFormatter)
	{
		parent::__construct($dba, $logger);

		$this->aclFormatter = $aclFormatter;
	}

	/**
	 * @param array $data
	 * @return Model\PermissionSet
	 */
	protected function create(array $data)
	{
		return new Model\PermissionSet($this->dba, $this->logger, $data);
	}

	/**
	 * @param array $condition
	 * @return Model\PermissionSet
	 * @throws \Friendica\Network\HTTPException\NotFoundException
	 */
	public function selectFirst(array $condition)
	{
		if (isset($condition['id']) && !$condition['id']) {
			return $this->create([
				'id' => self::PUBLIC,
				'uid' => $condition['uid'] ?? 0,
				'allow_cid' => '',
				'allow_gid' => '',
				'deny_cid' => '',
				'deny_gid' => '',
			]);
		}

		return parent::selectFirst($condition);
	}

	/**
	 * @param array $condition
	 * @param array $params
	 * @return Collection\PermissionSets
	 * @throws \Exception
	 */
	public function select(array $condition = [], array $params = [])
	{
		return parent::select($condition, $params);
	}

	/**
	 * @param array $condition
	 * @param array $params
	 * @param int|null $max_id
	 * @param int|null $since_id
	 * @param int $limit
	 * @return Collection\PermissionSets
	 * @throws \Exception
	 */
	public function selectByBoundaries(array $condition = [], array $params = [], int $max_id = null, int $since_id = null, int $limit = self::LIMIT)
	{
		return parent::selectByBoundaries($condition, $params, $max_id, $since_id, $limit);
	}

	/**
	 * Fetch the id of a given permission set. Generate a new one when needed
	 *
	 * @param int         $uid
	 * @param string|null $allow_cid       Allowed contact IDs    - empty = everyone
	 * @param string|null $allow_gid       Allowed group IDs      - empty = everyone
	 * @param string|null $deny_cid        Disallowed contact IDs - empty = no one
	 * @param string|null $deny_gid        Disallowed group IDs   - empty = no one
	 * @param bool        $public_constant Return the "self::PUBLIC" constant for public ACL
	 * @return int id
	 * @throws \Exception
	 */
	public function getIdFromACL(
		int $uid,
		string $allow_cid = null,
		string $allow_gid = null,
		string $deny_cid = null,
		string $deny_gid = null,
		bool $public_constant = true
	) {
		$allow_cid = $this->aclFormatter->sanitize($allow_cid);
		$allow_gid = $this->aclFormatter->sanitize($allow_gid);
		$deny_cid = $this->aclFormatter->sanitize($deny_cid);
		$deny_gid = $this->aclFormatter->sanitize($deny_gid);

		// Public permission
		if ($public_constant && !$allow_cid && !$allow_gid && !$deny_cid && !$deny_gid) {
			return self::PUBLIC;
		}

		$condition = [
			'uid' => $uid,
			'allow_cid' => $allow_cid,
			'allow_gid' => $allow_gid,
			'deny_cid'  => $deny_cid,
			'deny_gid'  => $deny_gid
		];

		try {
			$permissionset = $this->selectFirst($condition);
		} catch(HTTPException\NotFoundException $exception) {
			$permissionset = $this->insert($condition);
		}

		return $permissionset->id;
	}

	/**
	 * Returns a permission set collection for a given contact
	 *
	 * @param integer $contact_id Contact id of the visitor
	 * @param integer $uid        User id whom the items belong, used for ownership check.
	 *
	 * @return Collection\PermissionSets
	 * @throws \Exception
	 */
	public function selectByContactId($contact_id, $uid)
	{
		$groups = [];
		if ($this->dba->exists('contact', ['id' => $contact_id, 'uid' => $uid, 'blocked' => false])) {
			$groups = Group::getIdsByContactId($contact_id);
		}

		$group_str = '<<>>'; // should be impossible to match
		foreach ($groups as $group_id) {
			$group_str .= '|<' . preg_quote($group_id) . '>';
		}

		$contact_str = '<' . $contact_id . '>';

		$condition = ["`uid` = ? AND (NOT (`deny_cid` REGEXP ? OR deny_gid REGEXP ?)
			AND (allow_cid REGEXP ? OR allow_gid REGEXP ? OR (allow_cid = '' AND allow_gid = '')))",
			$uid, $contact_str, $group_str, $contact_str, $group_str];

		return $this->select($condition);
	}
}
