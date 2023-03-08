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

namespace Friendica\Module\Settings\Profile;

use Friendica\Core\ACL;
use Friendica\Core\Hook;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Core\Theme;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Profile;
use Friendica\Profile\ProfileField\Collection\ProfileFields;
use Friendica\Profile\ProfileField\Entity\ProfileField;
use Friendica\Model\User;
use Friendica\Module\BaseSettings;
use Friendica\Module\Security\Login;
use Friendica\Network\HTTPException;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Temporal;
use Friendica\Core\Worker;

class Index extends BaseSettings
{
	protected function post(array $request = [])
	{
		if (!DI::userSession()->getLocalUserId()) {
			return;
		}

		$profile = Profile::getByUID(DI::userSession()->getLocalUserId());
		if (!DBA::isResult($profile)) {
			return;
		}

		self::checkFormSecurityTokenRedirectOnError('/settings/profile', 'settings_profile');

		Hook::callAll('profile_post', $_POST);

		$dob = trim($_POST['dob'] ?? '');

		if ($dob && !in_array($dob, ['0000-00-00', DBA::NULL_DATE])) {
			$y = substr($dob, 0, 4);
			if ((!ctype_digit($y)) || ($y < 1900)) {
				$ignore_year = true;
			} else {
				$ignore_year = false;
			}

			if (strpos($dob, '0000-') === 0 || strpos($dob, '0001-') === 0) {
				$ignore_year = true;
				$dob = substr($dob, 5);
			}

			if ($ignore_year) {
				$dob = '0000-' . DateTimeFormat::utc('1900-' . $dob, 'm-d');
			} else {
				$dob = DateTimeFormat::utc($dob, 'Y-m-d');
			}
		}

		$name = trim($_POST['name'] ?? '');
		if (!strlen($name)) {
			DI::sysmsg()->addNotice(DI::l10n()->t('Profile Name is required.'));
			return;
		}

		$about = trim($_POST['about']);
		$address = trim($_POST['address']);
		$locality = trim($_POST['locality']);
		$region = trim($_POST['region']);
		$postal_code = trim($_POST['postal_code']);
		$country_name = trim($_POST['country_name']);
		$pub_keywords = self::cleanKeywords(trim($_POST['pub_keywords']));
		$prv_keywords = self::cleanKeywords(trim($_POST['prv_keywords']));
		$xmpp = trim($_POST['xmpp']);
		$matrix = trim($_POST['matrix']);
		$homepage = trim($_POST['homepage']);
		if ((strpos($homepage, 'http') !== 0) && (strlen($homepage))) {
			// neither http nor https in URL, add them
			$homepage = 'http://' . $homepage;
		}

		$profileFieldsNew = self::getProfileFieldsFromInput(
			DI::userSession()->getLocalUserId(),
			$_REQUEST['profile_field'],
			$_REQUEST['profile_field_order']
		);

		DI::profileField()->saveCollectionForUser(DI::userSession()->getLocalUserId(), $profileFieldsNew);

		$result = Profile::update(
			[
				'name'         => $name,
				'about'        => $about,
				'dob'          => $dob,
				'address'      => $address,
				'locality'     => $locality,
				'region'       => $region,
				'postal-code'  => $postal_code,
				'country-name' => $country_name,
				'xmpp'         => $xmpp,
				'matrix'       => $matrix,
				'homepage'     => $homepage,
				'pub_keywords' => $pub_keywords,
				'prv_keywords' => $prv_keywords,
			],
			DI::userSession()->getLocalUserId()
		);

		Worker::add(Worker::PRIORITY_MEDIUM, 'CheckRelMeProfileLink', DI::userSession()->getLocalUserId());

		if (!$result) {
			DI::sysmsg()->addNotice(DI::l10n()->t('Profile couldn\'t be updated.'));
			return;
		}

		DI::baseUrl()->redirect('settings/profile');
	}

	protected function content(array $request = []): string
	{
		if (!DI::userSession()->getLocalUserId()) {
			DI::sysmsg()->addNotice(DI::l10n()->t('You must be logged in to use this module'));
			return Login::form();
		}

		parent::content();

		$o = '';

		$profile = User::getOwnerDataById(DI::userSession()->getLocalUserId());
		if (!DBA::isResult($profile)) {
			throw new HTTPException\NotFoundException();
		}

		$a = DI::app();

		DI::page()->registerFooterScript('view/asset/es-jquery-sortable/source/js/jquery-sortable-min.js');
		DI::page()->registerFooterScript(Theme::getPathForFile('js/module/settings/profile/index.js'));

		$custom_fields = [];

		$profileFields = DI::profileField()->selectByUserId(DI::userSession()->getLocalUserId());
		foreach ($profileFields as $profileField) {
			/** @var ProfileField $profileField */
			$defaultPermissions = $profileField->permissionSet->withAllowedContacts(
				Contact::pruneUnavailable($profileField->permissionSet->allow_cid)
			);

			$custom_fields[] = [
				'id' => $profileField->id,
				'legend' => $profileField->label,
				'fields' => [
					'label' => ['profile_field[' . $profileField->id . '][label]', DI::l10n()->t('Label:'), $profileField->label],
					'value' => ['profile_field[' . $profileField->id . '][value]', DI::l10n()->t('Value:'), $profileField->value],
					'acl' => ACL::getFullSelectorHTML(
						DI::page(),
						$a->getLoggedInUserId(),
						false,
						$defaultPermissions->toArray(),
						['network' => Protocol::DFRN],
						'profile_field[' . $profileField->id . ']'
					),
				],
				'permissions' => DI::l10n()->t('Field Permissions'),
				'permdesc' => DI::l10n()->t("(click to open/close)"),
			];
		};

		$custom_fields[] = [
			'id' => 'new',
			'legend' => DI::l10n()->t('Add a new profile field'),
			'fields' => [
				'label' => ['profile_field[new][label]', DI::l10n()->t('Label:')],
				'value' => ['profile_field[new][value]', DI::l10n()->t('Value:')],
				'acl' => ACL::getFullSelectorHTML(
					DI::page(),
					$a->getLoggedInUserId(),
					false,
					['allow_cid' => []],
					['network' => Protocol::DFRN],
					'profile_field[new]'
				),
			],
			'permissions' => DI::l10n()->t('Field Permissions'),
			'permdesc' => DI::l10n()->t("(click to open/close)"),
		];

		DI::page()['htmlhead'] .= Renderer::replaceMacros(Renderer::getMarkupTemplate('settings/profile/index_head.tpl'), [
		]);

		$personal_account = ($profile['account-type'] != User::ACCOUNT_TYPE_COMMUNITY);

		if ($profile['homepage_verified']) {
			$homepage_help_text = DI::l10n()->t('The homepage is verified. A rel="me" link back to your Friendica profile page was found on the homepage.');
		} else {
			$homepage_help_text = DI::l10n()->t('To verify your homepage, add a rel="me" link to it, pointing to your profile URL (%s).', $profile['url']);
		}

		$tpl = Renderer::getMarkupTemplate('settings/profile/index.tpl');
		$o .= Renderer::replaceMacros($tpl, [
			'$personal_account' => $personal_account,

			'$form_security_token' => self::getFormSecurityToken('settings_profile'),
			'$form_security_token_photo' => self::getFormSecurityToken('settings_profile_photo'),

			'$profile_action' => DI::l10n()->t('Profile Actions'),
			'$banner' => DI::l10n()->t('Edit Profile Details'),
			'$submit' => DI::l10n()->t('Submit'),
			'$profpic' => DI::l10n()->t('Change Profile Photo'),
			'$profpiclink' => '/profile/' . $profile['nickname'] . '/photos',
			'$viewprof' => DI::l10n()->t('View Profile'),

			'$lbl_personal_section' => DI::l10n()->t('Personal'),
			'$lbl_picture_section' => DI::l10n()->t('Profile picture'),
			'$lbl_location_section' => DI::l10n()->t('Location'),
			'$lbl_miscellaneous_section' => DI::l10n()->t('Miscellaneous'),
			'$lbl_custom_fields_section' => DI::l10n()->t('Custom Profile Fields'),

			'$lbl_profile_photo' => DI::l10n()->t('Upload Profile Photo'),

			'$baseurl' => DI::baseUrl(),
			'$nickname' => $profile['nickname'],
			'$name' => ['name', DI::l10n()->t('Display name:'), $profile['name']],
			'$about' => ['about', DI::l10n()->t('Description:'), $profile['about']],
			'$dob' => Temporal::getDateofBirthField($profile['dob'], $profile['timezone']),
			'$address' => ['address', DI::l10n()->t('Street Address:'), $profile['address']],
			'$locality' => ['locality', DI::l10n()->t('Locality/City:'), $profile['locality']],
			'$region' => ['region', DI::l10n()->t('Region/State:'), $profile['region']],
			'$postal_code' => ['postal_code', DI::l10n()->t('Postal/Zip Code:'), $profile['postal-code']],
			'$country_name' => ['country_name', DI::l10n()->t('Country:'), $profile['country-name']],
			'$age' => ((intval($profile['dob'])) ? '(' . DI::l10n()->t('Age: ') . DI::l10n()->tt('%d year old', '%d years old', Temporal::getAgeByTimezone($profile['dob'], $profile['timezone'])) . ')' : ''),
			'$xmpp' => ['xmpp', DI::l10n()->t('XMPP (Jabber) address:'), $profile['xmpp'], DI::l10n()->t('The XMPP address will be published so that people can follow you there.')],
			'$matrix' => ['matrix', DI::l10n()->t('Matrix (Element) address:'), $profile['matrix'], DI::l10n()->t('The Matrix address will be published so that people can follow you there.')],
			'$homepage' => ['homepage', DI::l10n()->t('Homepage URL:'), $profile['homepage'], $homepage_help_text],
			'$pub_keywords' => ['pub_keywords', DI::l10n()->t('Public Keywords:'), $profile['pub_keywords'], DI::l10n()->t('(Used for suggesting potential friends, can be seen by others)')],
			'$prv_keywords' => ['prv_keywords', DI::l10n()->t('Private Keywords:'), $profile['prv_keywords'], DI::l10n()->t('(Used for searching profiles, never shown to others)')],
			'$custom_fields_description' => DI::l10n()->t("<p>Custom fields appear on <a href=\"%s\">your profile page</a>.</p>
				<p>You can use BBCodes in the field values.</p>
				<p>Reorder by dragging the field title.</p>
				<p>Empty the label field to remove a custom field.</p>
				<p>Non-public fields can only be seen by the selected Friendica contacts or the Friendica contacts in the selected groups.</p>",
				'profile/' . $profile['nickname'] . '/profile'
			),
			'$custom_fields' => $custom_fields,
		]);

		$arr = ['profile' => $profile, 'entry' => $o];
		Hook::callAll('profile_edit', $arr);

		return $o;
	}

	private static function getProfileFieldsFromInput(int $uid, array $profileFieldInputs, array $profileFieldOrder): ProfileFields
	{
		$profileFields = new ProfileFields();

		// Returns an associative array of id => order values
		$profileFieldOrder = array_flip($profileFieldOrder);

		// Creation of the new field
		if (!empty($profileFieldInputs['new']['label'])) {
			$permissionSet = DI::permissionSet()->selectOrCreate(DI::permissionSetFactory()->createFromString(
				$uid,
				DI::aclFormatter()->toString($profileFieldInputs['new']['contact_allow'] ?? ''),
				DI::aclFormatter()->toString($profileFieldInputs['new']['group_allow'] ?? ''),
				DI::aclFormatter()->toString($profileFieldInputs['new']['contact_deny'] ?? ''),
				DI::aclFormatter()->toString($profileFieldInputs['new']['group_deny'] ?? '')
			));

			$profileFields->append(DI::profileFieldFactory()->createFromValues(
				$uid,
				$profileFieldOrder['new'],
				$profileFieldInputs['new']['label'],
				$profileFieldInputs['new']['value'],
				$permissionSet
			));
		}

		unset($profileFieldInputs['new']);
		unset($profileFieldOrder['new']);

		foreach ($profileFieldInputs as $id => $profileFieldInput) {
			$permissionSet = DI::permissionSet()->selectOrCreate(DI::permissionSetFactory()->createFromString(
				$uid,
				DI::aclFormatter()->toString($profileFieldInput['contact_allow'] ?? ''),
				DI::aclFormatter()->toString($profileFieldInput['group_allow'] ?? ''),
				DI::aclFormatter()->toString($profileFieldInput['contact_deny'] ?? ''),
				DI::aclFormatter()->toString($profileFieldInput['group_deny'] ?? '')
			));

			$profileFields->append(DI::profileFieldFactory()->createFromValues(
				$uid,
				$profileFieldOrder[$id],
				$profileFieldInput['label'],
				$profileFieldInput['value'],
				$permissionSet
			));
		}

		return $profileFields;
	}

	private static function cleanKeywords($keywords)
	{
		$keywords = str_replace(',', ' ', $keywords);
		$keywords = explode(' ', $keywords);

		$cleaned = [];
		foreach ($keywords as $keyword) {
			$keyword = trim(strtolower($keyword));
			$keyword = trim($keyword, '#');
			if ($keyword != '') {
				$cleaned[] = $keyword;
			}
		}

		$keywords = implode(', ', $cleaned);

		return $keywords;
	}
}
