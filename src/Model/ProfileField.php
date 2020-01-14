<?php

namespace Friendica\Model;

use Friendica\BaseModel;
use Friendica\Database\Database;
use Friendica\Network\HTTPException;
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

	/** @var \Friendica\Repository\PermissionSet */
	private $permissionSetRepository;

	public function __construct(Database $dba, LoggerInterface $logger, \Friendica\Repository\PermissionSet $permissionSetRepository, array $data = [])
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
