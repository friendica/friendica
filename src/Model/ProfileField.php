<?php

namespace Friendica\Model;

use Friendica\BaseObject;
use Friendica\Core\L10n;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Util\ACLFormatter;
use Friendica\Util\Temporal;

/**
 * Custom profile field model class.
 *
 * Custom profile fields are user-created arbitrary profile fields that can be assigned a permission set to restrict its
 * display to specific Friendica contacts as it requires magic authentication to work.
 */
class ProfileField
{
	/** @var Database */
	protected $database;

	public function __construct(Database $database)
	{
		$this->database = $database;
	}

	/**
	 * @param int $uid Field owner user Id
	 * @return array
	 * @throws \Exception
	 */
	public function getListByUserId(int $uid)
	{
		$fields = $this->database->selectToArray(
			'profile_field',
			[],
			['uid' => $uid],
			['order' => ['order']]
		);

		return $fields;
	}

	/**
	 * Retrieve all custom profile field a given contact is able to access to, including public profile fields.
	 *
	 * @param int $cid Private contact id, must be owned by $uid
	 * @param int $uid Field owner user id
	 * @return array
	 * @throws \Exception
	 */
	public function getListByContactId(int $cid, int $uid)
	{
		$psids = PermissionSet::get($uid, $cid);

		// Includes public custom fields
		$psids[] = 0;

		$fields = $this->database->selectToArray(
			'profile_field',
			['label', 'value', 'order'],
			['uid' => $uid, 'psid' => $psids],
			['order' => ['order']]
		);

		return $fields;
	}

	public function importFromMultipleProfiles($uid)
	{
		$basic_fields = [
			'name'         => L10n::t('Full Name:'),
			'dob'          => L10n::t('Birthday:'),
			'pdesc'        => L10n::t('Description:'),
			'address'      => L10n::t('Street Address:'),
			'locality'     => L10n::t('Locality/City:'),
			'region'       => L10n::t('Region/State:'),
			'postal-code'  => L10n::t('Postal/Zip Code:'),
			'country-name' => L10n::t('Country:'),
			'homepage'     => L10n::t('Homepage URL:'),
			'xmpp'         => L10n::t('XMPP (Jabber) address:'),
			'pub_keywords' => L10n::t('Public Keywords:'),
		];

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

		$profiles = Profile::getListByUser($uid);
		foreach ($profiles as $profile) {
			if ($profile['is-default']) {
				$psid = 0;

				$fields = $custom_fields;
			} else {
				$contacts = Contact::selectToArray(['id'], ['uid' => $uid, 'profile-id' => $profile['id']]);
				if (!count($contacts)) {
					// No contact visibility selected defaults to user-only permission
					$contacts = Contact::selectToArray(['id'], ['uid' => $uid, 'self' => true]);
				}

				/** @var ACLFormatter $ACLFormatter */
				$ACLFormatter = BaseObject::getClass(ACLFormatter::class);

				$allow_cid = $ACLFormatter->toString(array_column($contacts, 'id'));

				$psid = PermissionSet::getIdFromACL($uid, $allow_cid);

				$fields = $basic_fields + $custom_fields;
			}

			$order = 1;

			foreach ($fields as $field => $label) {
				if (!empty($profile[$field]) && $profile[$field] > DBA::NULL_DATE && $profile[$field] > DBA::NULL_DATETIME) {
					$profile_field = [
						'uid' => $uid,
						'psid' => $psid,
						'order' => $order++,
						'label' => $label,
						'value' => $profile[$field],
					];

					$this->database->insert('profile_field', $profile_field);

					$profile[$field] = '';
				}
			}

			//$this->database->update('profile', $profile, ['id' => $profile['id']]);
		}
	}
}
