<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Settings\Profile;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\App\Page;
use Friendica\Core\ACL;
use Friendica\Core\L10n;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\Theme;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\Event\ArrayFilterEvent;
use Friendica\Model\Contact;
use Friendica\Model\Profile;
use Friendica\Module\Response;
use Friendica\Model\User;
use Friendica\Module\BaseSettings;
use Friendica\Module\Security\Login;
use Friendica\Navigation\SystemMessages;
use Friendica\Network\HTTPException;
use Friendica\Profile\ProfileField;
use Friendica\Security\PermissionSet;
use Friendica\Util\ACLFormatter;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Profiler;
use Friendica\Util\Temporal;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

class Index extends BaseSettings
{
	/** @var ProfileField\Repository\ProfileField */
	private $profileFieldRepo;
	/** @var ProfileField\Factory\ProfileField */
	private $profileFieldFactory;
	/** @var SystemMessages */
	private $systemMessages;
	/** @var PermissionSet\Repository\PermissionSet */
	private $permissionSetRepo;
	/** @var PermissionSet\Factory\PermissionSet */
	private $permissionSetFactory;
	/** @var ACLFormatter */
	private $aclFormatter;
	private EventDispatcherInterface $eventDispatcher;

	public function __construct(
		ACLFormatter $aclFormatter,
		PermissionSet\Factory\PermissionSet $permissionSetFactory,
		PermissionSet\Repository\PermissionSet $permissionSetRepo,
		SystemMessages $systemMessages,
		ProfileField\Factory\ProfileField $profileFieldFactory,
		ProfileField\Repository\ProfileField $profileFieldRepo,
		EventDispatcherInterface $eventDispatcher,
		IHandleUserSessions $session,
		Page $page,
		L10n $l10n,
		BaseURL $baseUrl,
		Arguments $args,
		LoggerInterface $logger,
		Profiler $profiler,
		Response $response,
		array $server,
		array $parameters = []
	) {
		parent::__construct($session, $page, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->profileFieldRepo     = $profileFieldRepo;
		$this->profileFieldFactory  = $profileFieldFactory;
		$this->systemMessages       = $systemMessages;
		$this->permissionSetRepo    = $permissionSetRepo;
		$this->permissionSetFactory = $permissionSetFactory;
		$this->aclFormatter         = $aclFormatter;
		$this->eventDispatcher      = $eventDispatcher;
	}

	protected function post(array $request = [])
	{
		if (!$this->session->getLocalUserId()) {
			return;
		}

		$profile = Profile::getByUID($this->session->getLocalUserId());
		if (!$profile) {
			return;
		}

		self::checkFormSecurityTokenRedirectOnError('/settings/profile', 'settings_profile');

		$request = $this->eventDispatcher->dispatch(
			new ArrayFilterEvent(ArrayFilterEvent::PROFILE_SETTINGS_POST, $request),
		)->getArray();

		$dob = $this->cleanInput($request['dob'] ?? '');

		if ($dob && !in_array($dob, ['0000-00-00', DBA::NULL_DATE])) {
			$y = substr($dob, 0, 4);
			if ((!ctype_digit($y)) || ($y < 1900)) {
				$ignore_year = true;
			} else {
				$ignore_year = false;
			}

			if (strpos($dob, '0000-') === 0 || strpos($dob, '0001-') === 0) {
				$ignore_year = true;
				$dob         = substr($dob, 5);
			}

			if ($ignore_year) {
				$dob = '0000-' . DateTimeFormat::utc('1904-' . $dob, 'm-d');
			} else {
				$dob = DateTimeFormat::utc($dob, 'Y-m-d');
			}
		}

		$username = $this->cleanInputText($request['username'] ?? '');
		if (!$username) {
			$this->systemMessages->addNotice($this->t('Display Name is required.'));
			return;
		}

		$about        = $this->cleanInputText($request['about']);
		$address      = $this->cleanInputText($request['address']);
		$locality     = $this->cleanInputText($request['locality']);
		$region       = $this->cleanInputText($request['region']);
		$postal_code  = $this->cleanInputText($request['postal_code']);
		$country_name = $this->cleanInputText($request['country_name']);
		$pub_keywords = self::cleanKeywords(trim($request['pub_keywords']));
		$prv_keywords = self::cleanKeywords(trim($request['prv_keywords']));
		$xmpp         = $this->cleanInput(trim($request['xmpp']));
		$matrix       = $this->cleanInput(trim($request['matrix']));
		$homepage     = $this->cleanInput(trim($request['homepage']));
		if ((strpos($homepage, 'http') !== 0) && (strlen($homepage))) {
			// neither http nor https in URL, add them
			$homepage = 'http://' . $homepage;
		}

		$user  = User::getById($this->session->getLocalUserId());
		$about = Profile::addResponsibleRelayContact($about, $user['parent-uid'], $user['account-type'], $user['language']);

		$profileFieldsNew = $this->getProfileFieldsFromInput(
			$this->session->getLocalUserId(),
			(array) $request['profile_field'],
			(array) $request['profile_field_order'],
		);

		$this->profileFieldRepo->saveCollectionForUser($this->session->getLocalUserId(), $profileFieldsNew);

		User::update(['username' => $username], $this->session->getLocalUserId());

		$result = Profile::update(
			[
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
			$this->session->getLocalUserId(),
		);

		Worker::add(Worker::PRIORITY_MEDIUM, 'CheckRelMeProfileLink', $this->session->getLocalUserId());

		if (!$result) {
			$this->systemMessages->addNotice($this->t("Profile couldn't be updated."));
			return;
		}

		$this->baseUrl->redirect('settings/profile');
	}

	protected function content(array $request = []): string
	{
		if (!$this->session->getLocalUserId()) {
			$this->systemMessages->addNotice($this->t('You must be logged in to use this module'));
			return Login::form();
		}

		parent::content();

		$o = '';

		$owner = User::getOwnerDataById($this->session->getLocalUserId());
		if (!$owner) {
			throw new HTTPException\NotFoundException();
		}

		$owner['about'] = Profile::addResponsibleRelayContact($owner['about'], $owner['parent-uid'], $owner['account-type'], $owner['language']);

		$this->page->registerFooterScript('view/asset/es-jquery-sortable/source/js/jquery-sortable-min.js');
		$this->page->registerFooterScript(Theme::getPathForFile('js/module/settings/profile/index.js'));

		$custom_fields = [];

		$profileFields = $this->profileFieldRepo->selectByUserId($this->session->getLocalUserId());
		foreach ($profileFields as $profileField) {
			$defaultPermissions = $profileField->permissionSet->withAllowedContacts(
				Contact::pruneUnavailable($profileField->permissionSet->allow_cid),
			);

			$custom_fields[] = [
				'id'     => $profileField->id,
				'legend' => $profileField->label,
				'fields' => [
					'label' => ['profile_field[' . $profileField->id . '][label]', $this->t('Label:'), $profileField->label],
					'value' => ['profile_field[' . $profileField->id . '][value]', $this->t('Value:'), $profileField->value],
					'acl'   => ACL::getFullSelectorHTML(
						$this->page,
						$this->session->getLocalUserId(),
						false,
						$defaultPermissions->toArray(),
						['network' => Protocol::DFRN],
						'profile_field[' . $profileField->id . ']',
					),
				],

				'permissions' => $this->t('Field Permissions'),
				'permdesc'    => $this->t("(click to open/close)"),
			];
		}

		$custom_fields[] = [
			'id'     => 'new',
			'legend' => $this->t('Add a new profile field'),
			'fields' => [
				'label' => ['profile_field[new][label]', $this->t('Label:')],
				'value' => ['profile_field[new][value]', $this->t('Value:')],
				'acl'   => ACL::getFullSelectorHTML(
					$this->page,
					$this->session->getLocalUserId(),
					false,
					['allow_cid' => []],
					['network'   => Protocol::DFRN],
					'profile_field[new]',
				),
			],

			'permissions' => $this->t('Field Permissions'),
			'permdesc'    => $this->t("(click to open/close)"),
		];

		$this->page['htmlhead'] .= Renderer::replaceMacros(Renderer::getMarkupTemplate('settings/profile/index_head.tpl'));

		$personal_account = ($owner['account-type'] != User::ACCOUNT_TYPE_COMMUNITY);

		if ($owner['homepage_verified']) {
			$homepage_help_text = $this->t('The homepage is verified. A rel="me" link back to your Friendica profile page was found on the homepage.');
		} else {
			$homepage_help_text = $this->t('To verify your homepage, add a rel="me" link to it, pointing to your profile URL (%s).', $owner['url']);
		}

		$tpl = Renderer::getMarkupTemplate('settings/profile/index.tpl');
		$o .= Renderer::replaceMacros($tpl, [
			'$l10n' => [
				'profile_action'            => $this->t('Profile Actions'),
				'banner'                    => $this->t('Edit Profile Details'),
				'submit'                    => $this->t('Save Settings'),
				'profpic_header'            => $this->t('Change profile picture'),
				'profpic_intro'             => $this->t('To change your profile picture, you can either upload a new picture here, or click to visit your photos to pick among your existing pictures.'),
				'profpic_upload_new_header' => $this->t('Upload new picture'),
				'profpic_upload_submit'     => $this->t('Upload selected picture'),
				'profpic_existing_header'   => $this->t('Pick existing picture from photos'),
				'yourphotos'                => $this->t('Go to my photos'),
				'viewprof'                  => $this->t('View Profile'),
				'personal_section'          => $this->t('Personal'),
				'picture_section'           => $this->t('Profile picture'),
				'location_section'          => $this->t('Location'),
				'miscellaneous_section'     => $this->t('Miscellaneous'),
				'custom_fields_section'     => $this->t('Custom Profile Fields'),
				'custom_fields_description' => $this->t(
					'<p>Custom fields appear on <a href="%s">your profile page</a>.</p>
				<p>You can use BBCodes in the field values.</p>
				<p>Reorder by dragging the field title.</p>
				<p>Empty the label field to remove a custom field.</p>
				<p>Non-public fields can only be seen by the selected Friendica contacts or the Friendica contacts in the selected circles.</p>',
					'profile/' . $owner['nickname'] . '/profile',
				),
			],

			'$personal_account'       => $personal_account,
			'$change_profile_picture' => isset($_GET['profilepicture']),

			'$form_security_token'       => self::getFormSecurityToken('settings_profile'),
			'$form_security_token_photo' => self::getFormSecurityToken('settings_profile_photo'),

			'$profpiclink' => '/profile/' . $owner['nickname'] . '/photos',

			'$nickname'      => $owner['nickname'],
			'$username'      => ['username', $this->t('Display name:'), $owner['name']],
			'$about'         => ['about', $this->t('Description:'), $owner['about']],
			'$dob'           => Temporal::getDateofBirthField($owner['dob'], $owner['timezone']),
			'$address'       => ['address', $this->t('Street Address:'), $owner['address']],
			'$locality'      => ['locality', $this->t('Locality/City:'), $owner['locality']],
			'$region'        => ['region', $this->t('Region/State:'), $owner['region']],
			'$postal_code'   => ['postal_code', $this->t('Postal/Zip Code:'), $owner['postal-code']],
			'$country_name'  => ['country_name', $this->t('Country:'), $owner['country-name']],
			'$age'           => ((intval($owner['dob'])) ? '(' . $this->t('Age: ') . $this->tt('%d year old', '%d years old', Temporal::getAgeByTimezone($owner['dob'], $owner['timezone'])) . ')' : ''),
			'$xmpp'          => ['xmpp', $this->t('XMPP (Jabber) address:'), $owner['xmpp'], $this->t('The XMPP address will be published so that people can follow you there.')],
			'$matrix'        => ['matrix', $this->t('Matrix (Element) address:'), $owner['matrix'], $this->t('The Matrix address will be published so that people can follow you there.')],
			'$homepage'      => ['homepage', $this->t('Homepage URL:'), $owner['homepage'], $homepage_help_text],
			'$pub_keywords'  => ['pub_keywords', $this->t('Public Keywords:'), $owner['pub_keywords'], $this->t('Used for suggesting potential friends, can be seen by others.')],
			'$prv_keywords'  => ['prv_keywords', $this->t('Private Keywords:'), $owner['prv_keywords'], $this->t('Used for searching profiles, never shown to others.')],
			'$custom_fields' => $custom_fields,
		]);

		$hook_data = [
			'profile' => $owner,
			'entry'   => $o,
		];

		$hook_data = $this->eventDispatcher->dispatch(
			new ArrayFilterEvent(ArrayFilterEvent::PROFILE_SETTINGS_FORM, $hook_data),
		)->getArray();

		$o = $hook_data['entry'] ?? $o;

		return $o;
	}

	private function getProfileFieldsFromInput(int $uid, array $profileFieldInputs, array $profileFieldOrder): ProfileField\Collection\ProfileFields
	{
		$profileFields = new ProfileField\Collection\ProfileFields();

		// Returns an associative array of id => order values
		$profileFieldOrder = array_flip($profileFieldOrder);

		// Creation of the new field
		if (!empty($profileFieldInputs['new']['label'])) {
			$permissionSet = $this->permissionSetRepo->selectOrCreate($this->permissionSetFactory->createFromString(
				$uid,
				$this->aclFormatter->toString($profileFieldInputs['new']['contact_allow'] ?? ''),
				$this->aclFormatter->toString($profileFieldInputs['new']['circle_allow'] ?? ''),
				$this->aclFormatter->toString($profileFieldInputs['new']['contact_deny'] ?? ''),
				$this->aclFormatter->toString($profileFieldInputs['new']['circle_deny'] ?? ''),
			));

			$profileFields->append($this->profileFieldFactory->createFromValues(
				$uid,
				$profileFieldOrder['new'],
				$profileFieldInputs['new']['label'],
				$profileFieldInputs['new']['value'],
				$permissionSet,
			));
		}

		unset($profileFieldInputs['new']);
		unset($profileFieldOrder['new']);

		foreach ($profileFieldInputs as $id => $profileFieldInput) {
			// Skip fields with empty labels - they will be deleted by saveCollectionForUser
			if (empty($profileFieldInput['label'])) {
				continue;
			}

			$permissionSet = $this->permissionSetRepo->selectOrCreate($this->permissionSetFactory->createFromString(
				$uid,
				$this->aclFormatter->toString($profileFieldInput['contact_allow'] ?? ''),
				$this->aclFormatter->toString($profileFieldInput['circle_allow'] ?? ''),
				$this->aclFormatter->toString($profileFieldInput['contact_deny'] ?? ''),
				$this->aclFormatter->toString($profileFieldInput['circle_deny'] ?? ''),
			));

			$profileFields->append($this->profileFieldFactory->createFromValues(
				$uid,
				$profileFieldOrder[$id],
				$profileFieldInput['label'],
				$profileFieldInput['value'],
				$permissionSet,
			));
		}

		return $profileFields;
	}

	private function cleanInputText(string $input): string
	{
		return trim(strip_tags($input));
	}

	private function cleanInput(string $input): string
	{
		return str_replace(['<', '>', '"', "'", ' '], '', $input);
	}

	private static function cleanKeywords($keywords): string
	{
		$keywords = str_replace(',', ' ', $keywords);
		$keywords = explode(' ', $keywords);

		$cleaned = [];
		foreach ($keywords as $keyword) {
			$keyword = trim(str_replace(['<', '>', '"', "'"], '', $keyword));
			$keyword = trim($keyword, '#');
			if ($keyword != '') {
				$cleaned[] = $keyword;
			}
		}

		return implode(', ', $cleaned);
	}
}
