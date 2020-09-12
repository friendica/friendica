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

namespace Friendica\Domain\Repository;

use Friendica\Domain\BaseModel;
use Friendica\Domain\BaseRepository;
use Friendica\Core\L10n;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Domain\Collection\ProfileFields;
use Friendica\Domain\Model\ProfileField as ProfileFieldModel;
use Friendica\Model;
use Friendica\Util\ACLFormatter;
use Friendica\Util\DateTimeFormat;
use Psr\Log\LoggerInterface;

class ProfileField extends BaseRepository
{
	protected static $table_name = 'profile_field';

	protected static $model_class = ProfileFieldModel::class;

	protected static $collection_class = ProfileFields::class;

	/** @var PermissionSet */
	private $permissionSet;
	/** @var ACLFormatter */
	private $aclFormatter;
	/** @var L10n */
	private $l10n;

	public function __construct(Database $dba, LoggerInterface $logger, PermissionSet $permissionSet, ACLFormatter $aclFormatter, L10n $l10n)
	{
		parent::__construct($dba, $logger);

		$this->permissionSet = $permissionSet;
		$this->aclFormatter = $aclFormatter;
		$this->l10n = $l10n;
	}

	/**
	 * @param array $data
	 *
	 * @return ProfileFieldModel
	 */
	protected function create(array $data)
	{
		return new ProfileFieldModel($this->dba, $this->logger, $this->permissionSet, $data);
	}

	/**
	 * @param array $condition
	 *
	 * @return ProfileFieldModel
	 * @throws \Friendica\Network\HTTPException\NotFoundException
	 */
	public function selectFirst(array $condition)
	{
		return parent::selectFirst($condition);
	}

	/**
	 * @param array $condition
	 * @param array $params
	 *
	 * @return ProfileFields
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
	 *
	 * @return ProfileFields
	 * @throws \Exception
	 */
	public function selectByBoundaries(array $condition = [], array $params = [], int $max_id = null, int $since_id = null, int $limit = self::LIMIT)
	{
		return parent::selectByBoundaries($condition, $params, $max_id, $since_id, $limit);
	}

	/**
	 * @param int $uid Field owner user Id
	 *
	 * @return ProfileFields
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
	 *
	 * @return ProfileFields
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
	 *
	 * @return ProfileFieldModel|bool
	 * @throws \Exception
	 */
	public function insert(array $fields)
	{
		$fields['created'] = DateTimeFormat::utcNow();
		$fields['edited']  = DateTimeFormat::utcNow();

		return parent::insert($fields);
	}

	/**
	 * @param ProfileFieldModel $model
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function update(BaseModel $model)
	{
		$model->edited = DateTimeFormat::utcNow();

		return parent::update($model);
	}
	
	/**
	 * @param int                                        $uid                User Id
	 * @param ProfileFields $profileFields      Collection of existing profile fields
	 * @param array                                      $profileFieldInputs Array of profile field form inputs indexed by profile field id
	 * @param array                                      $profileFieldOrder  List of profile field id in order
	 *
	 * @return ProfileFields
	 * @throws \Exception
	 */
	public function updateCollectionFromForm(int $uid, ProfileFields $profileFields, array $profileFieldInputs, array $profileFieldOrder)
	{
		// Returns an associative array of id => order values
		$profileFieldOrder = array_flip($profileFieldOrder);

		// Creation of the new field
		if (!empty($profileFieldInputs['new']['label'])) {
			$psid = $this->permissionSet->getIdFromACL(
				$uid,
				$this->aclFormatter->toString($profileFieldInputs['new']['contact_allow'] ?? ''),
				$this->aclFormatter->toString($profileFieldInputs['new']['group_allow'] ?? ''),
				$this->aclFormatter->toString($profileFieldInputs['new']['contact_deny'] ?? ''),
				$this->aclFormatter->toString($profileFieldInputs['new']['group_deny'] ?? '')
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
		$profileFields = $profileFields->filter(function (ProfileFieldModel $profileField) use (&$profileFieldInputs, &$profileFieldOrder) {
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
		$profileFields = $profileFields->map(function (ProfileFieldModel $profileField) use ($uid, &$profileFieldInputs, &$profileFieldOrder) {
			if (isset($profileFieldInputs[$profileField->id]) && isset($profileFieldOrder[$profileField->id])) {
				$psid = $this->permissionSet->getIdFromACL(
					$uid,
					$this->aclFormatter->toString($profileFieldInputs[$profileField->id]['contact_allow'] ?? ''),
					$this->aclFormatter->toString($profileFieldInputs[$profileField->id]['group_allow'] ?? ''),
					$this->aclFormatter->toString($profileFieldInputs[$profileField->id]['contact_deny'] ?? ''),
					$this->aclFormatter->toString($profileFieldInputs[$profileField->id]['group_deny'] ?? '')
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
	public function migrateFromLegacyProfile(array $profile)
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

			$allow_cid = $this->aclFormatter->toString(array_column($contacts, 'id'));
		}

		$psid = $this->permissionSet->getIdFromACL($profile['uid'], $allow_cid ?? '');

		$order = 1;

		$custom_fields = [
			'hometown'  => $this->l10n->t('Hometown:'),
			'marital'   => $this->l10n->t('Marital Status:'),
			'with'      => $this->l10n->t('With:'),
			'howlong'   => $this->l10n->t('Since:'),
			'sexual'    => $this->l10n->t('Sexual Preference:'),
			'politic'   => $this->l10n->t('Political Views:'),
			'religion'  => $this->l10n->t('Religious Views:'),
			'likes'     => $this->l10n->t('Likes:'),
			'dislikes'  => $this->l10n->t('Dislikes:'),
			'pdesc'     => $this->l10n->t('Title/Description:'),
			'summary'   => $this->l10n->t('Summary'),
			'music'     => $this->l10n->t('Musical interests'),
			'book'      => $this->l10n->t('Books, literature'),
			'tv'        => $this->l10n->t('Television'),
			'film'      => $this->l10n->t('Film/dance/culture/entertainment'),
			'interest'  => $this->l10n->t('Hobbies/Interests'),
			'romance'   => $this->l10n->t('Love/romance'),
			'work'      => $this->l10n->t('Work/employment'),
			'education' => $this->l10n->t('School/education'),
			'contact'   => $this->l10n->t('Contact information and Social Networks'),
		];

		foreach ($custom_fields as $field => $label) {
			if (!empty($profile[$field]) && $profile[$field] > DBA::NULL_DATE && $profile[$field] > DBA::NULL_DATETIME) {
				$this->insert([
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
			$this->dba->update('profile', $profile, ['id' => $profile['id']]);
		} elseif (!empty($profile['id'])) {
			$this->dba->delete('profile', ['id' => $profile['id']]);
		}
	}
}
