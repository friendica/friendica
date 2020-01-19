<?php

namespace Friendica\Repository;

use Friendica\BaseModel;
use Friendica\BaseRepository;
use Friendica\Collection;
use Friendica\Core\L10n;
use Friendica\Database\Database;
use Friendica\Database\DBA;
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

	/**
	 * Migrates a legacy profile to the new slimmer profile with extra custom fields.
	 * Multi profiles are converted to ACl-protected custom fields and deleted.
	 *
	 * @param array $profile Profile table row
	 * @throws \Exception
	 */
	public function migrateFromProfile(array $profile)
	{
		// Already processed, aborting
		if ($profile['is-default'] === null) {
			return;
		}

		if (!$profile['is-default']) {
			$contacts = Model\Contact::selectToArray(['id'], ['uid' => $profile['uid'], 'profile-id' => $profile['id']]);
			if (!count($contacts)) {
				// No contact visibility selected defaults to user-only permission
				$contacts = Model\Contact::selectToArray(['id'], ['uid' => $profile['uid'], 'self' => true]);
			}

			$allow_cid = DI::aclFormatter()->toString(array_column($contacts, 'id'));
		}

		$psid = DI::permissionSet()->getIdFromACL($profile['uid'], $allow_cid ?? '');

		$order = 1;

		$custom_fields = [
			'hometown'  => L10n::t('Hometown:'),
			'gender'    => L10n::t('Gender:'),
			'marital'   => L10n::t('Marital Status:'),
			'with'      => L10n::t('With:'),
			'howlong'   => L10n::t('Since:'),
			'sexual'    => L10n::t('Sexual Preference:'),
			'politic'   => L10n::t('Political Views:'),
			'religion'  => L10n::t('Religious Views:'),
			'likes'     => L10n::t('Likes:'),
			'dislikes'  => L10n::t('Dislikes:'),
			'about'     => L10n::t('About:'),
			'summary'   => L10n::t('Summary'),
			'music'     => L10n::t('Musical interests'),
			'book'      => L10n::t('Books, literature'),
			'tv'        => L10n::t('Television'),
			'film'      => L10n::t('Film/dance/culture/entertainment'),
			'interest'  => L10n::t('Hobbies/Interests'),
			'romance'   => L10n::t('Love/romance'),
			'work'      => L10n::t('Work/employment'),
			'education' => L10n::t('School/education'),
			'contact'   => L10n::t('Contact information and Social Networks'),
		];

		foreach ($custom_fields as $field => $label) {
			if (!empty($profile[$field]) && $profile[$field] > DBA::NULL_DATE && $profile[$field] > DBA::NULL_DATETIME) {
				DI::profileField()->insert([
					'uid' => $profile['uid'],
					'psid' => $psid,
					'order' => $order++,
					'label' => trim($label, ':'),
					'value' => $profile[$field],
				]);
			}

			$profile[$field] = null;
		}

		if ($profile['is-default']) {
			$profile['profile-name'] = null;
			$profile['is-default'] = null;
			DI::dba()->update('profile', $profile, ['id' => $profile['id']]);
		} else {
			DI::dba()->delete('profile', ['id' => $profile['id']]);
		}
	}
}
