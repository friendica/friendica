<?php

namespace Friendica\Module\Profile;

use Friendica\BaseModule;
use Friendica\Content\Feature;
use Friendica\Content\ForumManager;
use Friendica\Content\Nav;
use Friendica\Content\Text\BBCode;
use Friendica\Content\Text\HTML;
use Friendica\Core\Config;
use Friendica\Core\Hook;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Core\Session;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Profile;
use Friendica\Model\Term;
use Friendica\Model\User;
use Friendica\Module\Security\Login;
use Friendica\Network\HTTPException;
use Friendica\Protocol\ActivityPub;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Temporal;

require_once 'boot.php';

class Index extends BaseModule
{
	public static function rawContent(array $parameters = [])
	{
		if (ActivityPub::isRequest()) {
			$user = DBA::selectFirst('user', ['uid'], ['nickname' => $parameters['nickname']]);
			if (DBA::isResult($user)) {
				// The function returns an empty array when the account is removed, expired or blocked
				$data = ActivityPub\Transmitter::getProfile($user['uid']);
				if (!empty($data)) {
					System::jsonExit($data, 'application/activity+json');
				}
			}

			if (DBA::exists('userd', ['username' => $parameters['nickname']])) {
				// Known deleted user
				$data = ActivityPub\Transmitter::getDeletedUser($parameters['nickname']);

				System::jsonError(410, $data);
			} else {
				// Any other case (unknown, blocked, nverified, expired, no profile, no self contact)
				System::jsonError(404, []);
			}
		}
	}

	public static function content(array $parameters = [])
	{
		$a = DI::app();

		Profile::load($a, $parameters['nickname']);

		if (!$a->profile) {
			throw new HTTPException\NotFoundException(DI::l10n()->t('Profile not found.'));
		}

		$remote_contact_id = Session::getRemoteContactID($a->profile_uid);

		if (Config::get('system', 'block_public') && !local_user() && !$remote_contact_id) {
			return Login::form();
		}

		$is_owner = local_user() == $a->profile_uid;

		$o = '';
		if (!empty($a->profile['hidewall']) && !$is_owner && !$remote_contact_id) {
			throw new HTTPException\ForbiddenException(DI::l10n()->t('Access to this profile has been restricted.'));
		}

		DI::page()['htmlhead'] .= self::buildHtmlHead($a->profile, $parameters['nickname'], $remote_contact_id);

		Nav::setSelected('home');

		$is_owner = local_user() == $a->profile['uid'];
		$o = Profile::getTabs($a, 'profile', $is_owner, $a->profile['nickname']);

		if (!empty($a->profile['hidewall']) && !$is_owner && !$remote_contact_id) {
			notice(DI::l10n()->t('Access to this profile has been restricted.'));
			return '';
		}

		$view_as_contacts = [];
		$view_as_contact_id = 0;
		if ($is_owner) {
			$view_as_contact_id = intval($_GET['viewas'] ?? 0);

			$view_as_contacts = Contact::selectToArray(['id', 'name'], [
				'uid' => local_user(),
				'rel' => [Contact::FOLLOWER, Contact::SHARING, Contact::FRIEND],
				'network' => Protocol::DFRN,
				'blocked' => false,
			]);

			// User manually provided a contact ID they aren't privy to, silently defaulting to their own view
			if (!in_array($view_as_contact_id, array_column($view_as_contacts, 'id'))) {
				$view_as_contact_id = 0;
			}
		}

		$basic_fields = [];

		$basic_fields += self::buildField('fullname', DI::l10n()->t('Full Name:'), $a->profile['name']);

		if (Feature::isEnabled($a->profile_uid, 'profile_membersince')) {
			$basic_fields += self::buildField(
				'membersince',
				DI::l10n()->t('Member since:'),
				DateTimeFormat::local($a->profile['register_date'])
			);
		}

		if (!empty($a->profile['dob']) && $a->profile['dob'] > DBA::NULL_DATE) {
			$year_bd_format = DI::l10n()->t('j F, Y');
			$short_bd_format = DI::l10n()->t('j F');

			$dob = DI::l10n()->getDay(
				intval($a->profile['dob']) ?
					DateTimeFormat::utc($a->profile['dob'] . ' 00:00 +00:00', $year_bd_format)
					: DateTimeFormat::utc('2001-' . substr($a->profile['dob'], 5) . ' 00:00 +00:00', $short_bd_format)
			);

			$basic_fields += self::buildField('dob', DI::l10n()->t('Birthday:'), $dob);

			if ($age = Temporal::getAgeByTimezone($a->profile['dob'], $a->profile['timezone'], '')) {
				$basic_fields += self::buildField('age', DI::l10n()->t('Age:'), $age);
			}
		}

		if ($a->profile['pdesc']) {
			$basic_fields += self::buildField('pdesc', DI::l10n()->t('Description:'), HTML::toLink($a->profile['pdesc']));
		}

		if ($a->profile['xmpp']) {
			$basic_fields += self::buildField('xmpp', DI::l10n()->t('XMPP:'), $a->profile['xmpp']);
		}

		if ($a->profile['homepage']) {
			$basic_fields += self::buildField('homepage', DI::l10n()->t('Homepage:'), HTML::toLink($a->profile['homepage']));
		}

		$o .= Profile::getTabs($a, 'profile', $is_owner, $a->profile['nickname']);
		if (
			$a->profile['address']
			|| $a->profile['locality']
			|| $a->profile['postal-code']
			|| $a->profile['region']
			|| $a->profile['country-name']
		) {
			$basic_fields += self::buildField('location', DI::l10n()->t('Location:'), Profile::formatLocation($a->profile));
		}

		if ($a->profile['pub_keywords']) {
			$tags = [];
			foreach (explode(',', $a->profile['pub_keywords']) as $tag_label) {
				$tags[] = [
					'url' => '/search?tag=' . $tag_label,
					'label' => Term::TAG_CHARACTER[Term::HASHTAG] . $tag_label,
				];
			}

			$basic_fields += self::buildField('pub_keywords', DI::l10n()->t('Tags:'), $tags);
		}

		$custom_fields = [];

		// Defaults to the current logged in user self contact id to show self-only fields
		$contact_id = $view_as_contact_id ?: $remote_contact_id ?: 0;

		if ($is_owner && $contact_id === 0) {
			$profile_fields = DI::profileField()->selectByUserId($a->profile_uid);
		} else {
			$profile_fields = DI::profileField()->selectByContactId($contact_id, $a->profile_uid);
		}

		foreach ($profile_fields as $profile_field) {
			$custom_fields += self::buildField(
				'custom_' . $profile_field->order,
				$profile_field->label,
				BBCode::convert($profile_field->value),
				'aprofile custom'
			);
		};

		//show subcribed forum if it is enabled in the usersettings
		if (Feature::isEnabled($a->profile_uid, 'forumlist_profile')) {
			$custom_fields += self::buildField(
				'forumlist',
				DI::l10n()->t('Forums:'),
				ForumManager::profileAdvanced($a->profile_uid)
			);
		}

		$tpl = Renderer::getMarkupTemplate('profile/index.tpl');
		$o .= Renderer::replaceMacros($tpl, [
			'$title' => DI::l10n()->t('Profile'),
			'$view_as_contacts' => $view_as_contacts,
			'$view_as_contact_id' => $view_as_contact_id,
			'$view_as' => DI::l10n()->t('View profile as:'),
			'$basic' => DI::l10n()->t('Basic'),
			'$advanced' => DI::l10n()->t('Advanced'),
			'$is_owner' => $a->profile_uid == local_user(),
			'$query_string' => DI::args()->getQueryString(),
			'$basic_fields' => $basic_fields,
			'$custom_fields' => $custom_fields,
			'$profile' => $a->profile,
			'$edit_link' => [
				'url' => DI::baseUrl() . '/settings/profile', DI::l10n()->t('Edit profile'),
				'title' => '',
				'label' => DI::l10n()->t('Edit profile')
			],
		]);

		Hook::callAll('profile_advanced', $o);

		return $o;
	}

	/**
	 * Creates a profile field structure to be used in the profile template
	 *
	 * @param string $name  Arbitrary name of the field
	 * @param string $label Display label of the field
	 * @param mixed  $value Display value of the field
	 * @param string $class Optional CSS class to apply to the field
	 * @return array
	 */
	private static function buildField(string $name, string $label, $value, string $class = 'aprofile')
	{
		return [$name => [
			'id' => 'aprofile-' . $name,
			'class' => $class,
			'label' => $label,
			'value' => $value,
		]];
	}

	private static function buildHtmlHead(array $profile, string $nickname, int $remote_contact_id)
	{
		$baseUrl = DI::baseUrl();

		$htmlhead = "\n";

		if (!empty($profile['page-flags']) && $profile['page-flags'] == User::PAGE_FLAGS_COMMUNITY) {
			$htmlhead .= '<meta name="friendica.community" content="true" />' . "\n";
		}

		if (!empty($profile['openidserver'])) {
			$htmlhead .= '<link rel="openid.server" href="' . $profile['openidserver'] . '" />' . "\n";
		}

		if (!empty($profile['openid'])) {
			$delegate = strstr($profile['openid'], '://') ? $profile['openid'] : 'https://' . $profile['openid'];
			$htmlhead .= '<link rel="openid.delegate" href="' . $delegate . '" />' . "\n";
		}

		// site block
		$blocked   = !local_user() && !$remote_contact_id && Config::get('system', 'block_public');
		$userblock = !local_user() && !$remote_contact_id && $profile['hidewall'];
		if (!$blocked && !$userblock) {
			$keywords = str_replace(['#', ',', ' ', ',,'], ['', ' ', ',', ','], $profile['pub_keywords'] ?? '');
			if (strlen($keywords)) {
				$htmlhead .= '<meta name="keywords" content="' . $keywords . '" />' . "\n";
			}
		}

		$htmlhead .= '<meta name="dfrn-global-visibility" content="' . ($profile['net-publish'] ? 'true' : 'false') . '" />' . "\n";

		if (!$profile['net-publish'] || $profile['hidewall']) {
			$htmlhead .= '<meta content="noindex, noarchive" name="robots" />' . "\n";
		}

		$htmlhead .= '<link rel="alternate" type="application/atom+xml" href="' . $baseUrl . '/dfrn_poll/' . $nickname . '" title="DFRN: ' . DI::l10n()->t('%s\'s timeline', $profile['username']) . '"/>' . "\n";
		$htmlhead .= '<link rel="alternate" type="application/atom+xml" href="' . $baseUrl . '/feed/' . $nickname . '/" title="' . DI::l10n()->t('%s\'s posts', $profile['username']) . '"/>' . "\n";
		$htmlhead .= '<link rel="alternate" type="application/atom+xml" href="' . $baseUrl . '/feed/' . $nickname . '/comments" title="' . DI::l10n()->t('%s\'s comments', $profile['username']) . '"/>' . "\n";
		$htmlhead .= '<link rel="alternate" type="application/atom+xml" href="' . $baseUrl . '/feed/' . $nickname . '/activity" title="' . DI::l10n()->t('%s\'s timeline', $profile['username']) . '"/>' . "\n";
		$uri = urlencode('acct:' . $profile['nickname'] . '@' . $baseUrl->getHostname() . ($baseUrl->getUrlPath() ? '/' . $baseUrl->getUrlPath() : ''));
		$htmlhead .= '<link rel="lrdd" type="application/xrd+xml" href="' . $baseUrl . '/xrd/?uri=' . $uri . '" />' . "\n";
		header('Link: <' . $baseUrl . '/xrd/?uri=' . $uri . '>; rel="lrdd"; type="application/xrd+xml"', false);

		$dfrn_pages = ['request', 'confirm', 'notify', 'poll'];
		foreach ($dfrn_pages as $dfrn) {
			$htmlhead .= '<link rel="dfrn-' . $dfrn . '" href="' . $baseUrl . '/dfrn_' . $dfrn . '/' . $nickname . '" />' . "\n";
		}
		$htmlhead .= '<link rel="dfrn-poco" href="' . $baseUrl . '/poco/' . $nickname . '" />' . "\n";

		return $htmlhead;
	}
}
