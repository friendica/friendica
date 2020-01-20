<?php

namespace Friendica\Repository;

use Friendica\BaseModel;
use Friendica\BaseRepository;
use Friendica\Collection;
use Friendica\Model;
use Friendica\Model\PermissionSet;
use Friendica\Util\DateTimeFormat;

class ProfileField extends BaseRepository
{
	protected static $table_name = 'profile_field';

	protected static $model_class = Model\ProfileField::class;

	protected static $collection_class = Collection\ProfileFields::class;

	/**
	 * @param array $data
	 * @return Model\ProfileField
	 */
	protected function create(array $data)
	{
		return new Model\ProfileField($this->dba, $this->logger, $data);
	}

	/**
	 * @param array $condition
	 * @return Model\ProfileField
	 * @throws \Friendica\Network\HTTPException\NotFoundException
	 */
	public function selectFirst(array $condition)
	{
		return parent::selectFirst($condition);
	}

	/**
	 * @param array $condition
	 * @param array $params
	 * @return Collection\ProfileFields
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
	 * @return Collection\ProfileFields
	 * @throws \Exception
	 */
	public function selectByBoundaries(array $condition = [], array $params = [], int $max_id = null, int $since_id = null, int $limit = self::LIMIT)
	{
		return parent::selectByBoundaries($condition, $params, $max_id, $since_id, $limit);
	}

	/**
	 * @param int $uid Field owner user Id
	 * @return Collection\ProfileFields
	 * @throws \Exception
	 */
	public function selectByUserId(int $uid)
	{
		return $this->select(
			['uid' => $uid],
			['order' => ['order']]
		);
	}

	/**
	 * Retrieve all custom profile field a given contact is able to access to, including public profile fields.
	 *
	 * @param int $cid Private contact id, must be owned by $uid
	 * @param int $uid Field owner user id
	 * @return Collection\ProfileFields
	 * @throws \Exception
	 */
	public function selectByContactId(int $cid, int $uid)
	{
		$psids = PermissionSet::get($uid, $cid);

		// Includes public custom fields
		$psids[] = 0;

		return $this->select(
			['uid' => $uid, 'psid' => $psids],
			['order' => ['order']]
		);
	}

	/**
	 * @param array $fields
	 * @return Model\ProfileField|bool
	 * @throws \Exception
	 */
	public function insert(array $fields)
	{
		$fields['created'] = DateTimeFormat::utcNow();
		$fields['edited']  = DateTimeFormat::utcNow();

		return parent::insert($fields);
	}

	/**
	 * @param Model\ProfileField $model
	 * @return bool
	 * @throws \Exception
	 */
	public function update(BaseModel $model)
	{
		$model->edited = DateTimeFormat::utcNow();

		return parent::update($model);
	}
}
