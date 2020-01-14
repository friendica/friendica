<?php

namespace Friendica\Repository;

use Friendica\BaseModel;
use Friendica\BaseRepository;
use Friendica\Collection;
use Friendica\Database\Database;
use Friendica\DI;
use Friendica\Model;
use Friendica\Util\DateTimeFormat;
use Psr\Log\LoggerInterface;

class ProfileField extends BaseRepository
{
	protected static $table_name = 'profile_field';

	protected static $model_class = Model\ProfileField::class;

	protected static $collection_class = Collection\ProfileFields::class;

	/** @var PermissionSet */
	private $permissionSet;

	public function __construct(Database $dba, LoggerInterface $logger, PermissionSet $permissionSet)
	{
		parent::__construct($dba, $logger);

		$this->permissionSet = $permissionSet;
	}

	/**
	 * @param array $data
	 * @return Model\ProfileField
	 */
	protected function create(array $data)
	{
		return new Model\ProfileField($this->dba, $this->logger, $this->permissionSet, $data);
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
		$permissionSets = $this->permissionSet->selectByContactId($cid, $uid);

		$psids = $permissionSets->column('id');

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
	
	/**
	 * @param int                      $uid                User Id
	 * @param Collection\ProfileFields $profileFields      Collection of existing profile fields
	 * @param array                    $profileFieldInputs Array of profile field form inputs indexed by profile field id
	 * @param array                    $profileFieldOrder  List of profile field id in order
	 * @return Collection\ProfileFields
	 * @throws \Exception
	 */
	public function updateCollectionFromForm(int $uid, Collection\ProfileFields $profileFields, array $profileFieldInputs, array $profileFieldOrder)
	{
		$aclFormatter = DI::aclFormatter();

		// Returns an associative array of id => order values
		$profileFieldOrder = array_flip($profileFieldOrder);

		// Creation of the new field
		if (!empty($profileFieldInputs['new']['label'])) {
			$psid = $this->permissionSet->getIdFromACL(
				$uid,
				$aclFormatter->toString($profileFieldInputs['new']['contact_allow'] ?? ''),
				$aclFormatter->toString($profileFieldInputs['new']['group_allow'] ?? ''),
				$aclFormatter->toString($profileFieldInputs['new']['contact_deny'] ?? ''),
				$aclFormatter->toString($profileFieldInputs['new']['group_deny'] ?? '')
			);

			$newProfileField = $this->insert([
				'uid' => $uid,
				'label' => $profileFieldInputs['new']['label'],
				'value' => $profileFieldInputs['new']['value'],
				'psid' => $psid,
				'order' => $profileFieldOrder['new'],
			]);

			$profileFieldInputs[$newProfileField->id] = $profileFieldInputs['new'];
			$profileFieldOrder[$newProfileField->id] = $profileFieldOrder['new'];

			$profileFields[] = $newProfileField;
		}

		unset($profileFieldInputs['new']);
		unset($profileFieldOrder['new']);

		// Prunes profile field whose label has been emptied
		$profileFields = $profileFields->filter(function (Model\ProfileField $profileField) use (&$profileFieldInputs, &$profileFieldOrder) {
			$keepModel = !isset($profileFieldInputs[$profileField->id]) || !empty($profileFieldInputs[$profileField->id]['label']);

			if (!$keepModel) {
				unset($profileFieldInputs[$profileField->id]);
				unset($profileFieldOrder[$profileField->id]);
				$this->delete($profileField);
			}

			return $keepModel;
		});

		// Regenerates the order values if items were deleted
		$profileFieldOrder = array_flip(array_keys($profileFieldOrder));

		// Update existing profile fields from form values
		$profileFields = $profileFields->map(function (Model\ProfileField $profileField) use ($uid, $aclFormatter, &$profileFieldInputs, &$profileFieldOrder) {
			if (isset($profileFieldInputs[$profileField->id]) && isset($profileFieldOrder[$profileField->id])) {
				$psid = $this->permissionSet->getIdFromACL(
					$uid,
					$aclFormatter->toString($profileFieldInputs[$profileField->id]['contact_allow'] ?? ''),
					$aclFormatter->toString($profileFieldInputs[$profileField->id]['group_allow'] ?? ''),
					$aclFormatter->toString($profileFieldInputs[$profileField->id]['contact_deny'] ?? ''),
					$aclFormatter->toString($profileFieldInputs[$profileField->id]['group_deny'] ?? '')
				);

				$profileField->psid  = $psid;
				$profileField->label = $profileFieldInputs[$profileField->id]['label'];
				$profileField->value = $profileFieldInputs[$profileField->id]['value'];
				$profileField->order = $profileFieldOrder[$profileField->id];

				unset($profileFieldInputs[$profileField->id]);
				unset($profileFieldOrder[$profileField->id]);
			}

			return $profileField;
		});

		return $profileFields;
	}
}
