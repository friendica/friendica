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

namespace Friendica\Domain\Model;

use Friendica\Domain\BaseModel;
use Friendica\Database\Database;
use Friendica\Domain\Repository\PermissionSet as PermissionSetRepo;
use Psr\Log\LoggerInterface;

/**
 * Custom profile field model class.
 *
 * Custom profile fields are user-created arbitrary profile fields that can be assigned a permission set to restrict its
 * display to specific Friendica contacts as it requires magic authentication to work.
 *
 * @property int    uid
 * @property int    order
 * @property int    psid
 * @property string label
 * @property string value
 * @property string created
 * @property string edited
 * @property PermissionSet permissionset
 */
class ProfileField extends BaseModel
{
	/** @var PermissionSet */
	private $permissionset;

	/** @var PermissionSetRepo */
	private $permissionSetRepository;

	public function __construct(Database $dba, LoggerInterface $logger, PermissionSetRepo $permissionSetRepository, array $data = [])
	{
		parent::__construct($dba, $logger, $data);

		$this->permissionSetRepository = $permissionSetRepository;
	}

	public function __get($name)
	{
		$this->checkValid();

		switch ($name) {
			case 'permissionset':
				$this->permissionset =
					$this->permissionset ??
						$this->permissionSetRepository->selectFirst(['id' => $this->psid, 'uid' => $this->uid]);

				$return = $this->permissionset;
				break;
			default:
				$return = parent::__get($name);
				break;
		}

		return $return;
	}
}
