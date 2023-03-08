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

namespace Friendica\Module\Profile;

use Friendica\App;
use Friendica\Content\Feature;
use Friendica\Content\ForumManager;
use Friendica\Content\Nav;
use Friendica\Content\Text\BBCode;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\System;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Model\Profile as ProfileModel;
use Friendica\Model\Tag;
use Friendica\Model\User;
use Friendica\Module\BaseProfile;
use Friendica\Module\Response;
use Friendica\Module\Security\Login;
use Friendica\Network\HTTPException;
use Friendica\Profile\ProfileField\Repository\ProfileField;
use Friendica\Protocol\ActivityPub;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Profiler;
use Friendica\Util\Strings;
use Friendica\Util\Temporal;
use Psr\Log\LoggerInterface;

class Profile extends BaseProfile
{
	/** @var Database */
	private $database;
	/** @var App */
	private $app;
	/** @var IHandleUserSessions */
	private $session;
	/** @var IManageConfigValues */
	private $config;
	/** @var App\Page */
	private $page;
	/** @var ProfileField */
	private $profileField;

	public function __construct(ProfileField $profileField, App\Page $page, IManageConfigValues $config, IHandleUserSessions $session, App $app, Database $database, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->database     = $database;
		$this->app          = $app;
		$this->session      = $session;
		$this->config       = $config;
		$this->page         = $page;
		$this->profileField = $profileField;
	}

	protected function rawContent(array $request = [])
	{
		if (ActivityPub::isRequest()) {
			$user = $this->database->selectFirst('user', ['uid'], ['nickname' => $this->parameters['nickname'] ?? '', 'account_removed' => false]);
			if ($user) {
				try {
					$data = ActivityPub\Transmitter::getProfile($user['uid']);
					header('Access-Control-Allow-Origin: *');
					header('Cache-Control: max-age=23200, stale-while-revalidate=23200');
					System::jsonExit($data, 'application/activity+json');
				} catch (HTTPException\NotFoundException $e) {
					System::jsonError(404, ['error' => 'Record not found']);
				}
			}

			if ($this->database->exists('userd', ['username' => $this->parameters['nickname']])) {
				// Known deleted user
				$data = ActivityPub\Transmitter::getDeletedUser($this->parameters['nickname']);

				System::jsonError(410, $data);
			} else {
				// Any other case (unknown, blocked, nverified, expired, no profile, no self contact)
				System::jsonError(404, []);
			}
		}
	}

	protected function content(array $request = []): string
	{
		$profile = ProfileModel::load($this->app, $this->parameters['nickname'] ?? '');
		if (!$profile) {
			throw new HTTPException\NotFoundException($this->t('Profile not found.'));
		}

		$remote_contact_id = $this->session->getRemoteContactID($profile['uid']);

		if ($this->config->get('system', 'block_public') && !$this->session->isAuthenticated()) {
			return Login::form();
		}

		if (!empty($profile['hidewall']) && !$this->session->isAuthenticated()) {
			$this->baseUrl->redirect('profile/' . $profile['nickname'] . '/restricted');
		}

		if (!empty($profile['page-flags']) && $profile['page-flags'] == User::PAGE_FLAGS_COMMUNITY) {
			$this->page['htmlhead'] .= '<meta name="friendica.community" content="true" />' . "\n";
		}

		$this->page['htmlhead'] .= $this->buildHtmlHead($profile, $this->parameters['nickname']);

		Nav::setSelected('home');

		$is_owner = $this->session->getLocalUserId() == $profile['uid'];
		$o        = self::getTabsHTML('profile', $is_owner, $profile['nickname'], $profile['hide-friends']);

		$view_as_contacts      = [];
		$view_as_contact_id    = 0;
		$view_as_contact_alert = '';
		if ($is_owner) {
			$view_as_contact_id = intval($request['viewas'] ?? 0);

			$view_as_contacts = Contact::selectToArray(['id', 'name'], [
				'uid'     => $this->session->getLocalUserId(),
				'rel'     => [Contact::FOLLOWER, Contact::SHARING, Contact::FRIEND],
				'network' => Protocol::DFRN,
				'blocked' => false,
			]);

			$view_as_contact_ids = array_column($view_as_contacts, 'id');

			// User manually provided a contact ID they aren't privy to, silently defaulting to their own view
			if (!in_array($view_as_contact_id, $view_as_contact_ids)) {
				$view_as_contact_id = 0;
			}

			if (($key = array_search($view_as_contact_id, $view_as_contact_ids)) !== false) {
				$view_as_contact_alert = $this->t(
					'You\'re currently viewing your profile as <b>%s</b> <a href="%s" class="btn btn-sm pull-right">Cancel</a>',
					htmlentities($view_as_contacts[$key]['name'], ENT_COMPAT, 'UTF-8'),
					'profile/' . $this->parameters['nickname'] . '/profile'
				);
			}
		}

		$basic_fields = [];

		$basic_fields += self::buildField('fullname', $this->t('Full Name:'), $profile['name']);

		if (Feature::isEnabled($profile['uid'], 'profile_membersince')) {
			$basic_fields += self::buildField(
				'membersince',
				$this->t('Member since:'),
				DateTimeFormat::local($profile['register_date'])
			);
		}

		if (!empty($profile['dob']) && $profile['dob'] > DBA::NULL_DATE) {
			$year_bd_format  = $this->t('j F, Y');
			$short_bd_format = $this->t('j F');

			$dob = $this->l10n->getDay(
				intval($profile['dob']) ?
					DateTimeFormat::utc($profile['dob'] . ' 00:00 +00:00', $year_bd_format)
					: DateTimeFormat::utc('2001-' . substr($profile['dob'], 5) . ' 00:00 +00:00', $short_bd_format)
			);

			$basic_fields += self::buildField('dob', $this->t('Birthday:'), $dob);

			if ($age = Temporal::getAgeByTimezone($profile['dob'], $profile['timezone'])) {
				$basic_fields += self::buildField('age', $this->t('Age: '), $this->tt('%d year old', '%d years old', $age));
			}
		}

		if ($profile['about']) {
			$basic_fields += self::buildField('about', $this->t('Description:'), BBCode::convertForUriId($profile['uri-id'], $profile['about']));
		}

		if ($profile['xmpp']) {
			$basic_fields += self::buildField('xmpp', $this->t('XMPP:'), $profile['xmpp']);
		}

		if ($profile['matrix']) {
			$basic_fields += self::buildField('matrix', $this->t('Matrix:'), $profile['matrix']);
		}

		if ($profile['homepage']) {
			$basic_fields += self::buildField(
				'homepage',
				$this->t('Homepage:'),
				$this->tryRelMe($profile['homepage']) ?: $profile['homepage']
			);
		}

		if (
			$profile['address']
			|| $profile['locality']
			|| $profile['postal-code']
			|| $profile['region']
			|| $profile['country-name']
		) {
			$basic_fields += self::buildField('location', $this->t('Location:'), ProfileModel::formatLocation($profile));
		}

		if ($profile['pub_keywords']) {
			$tags = [];
			// Separator is defined in Module\Settings\Profile\Index::cleanKeywords
			foreach (explode(', ', $profile['pub_keywords']) as $tag_label) {
				$tags[] = [
					'url'   => '/search?tag=' . $tag_label,
					'label' => Tag::TAG_CHARACTER[Tag::HASHTAG] . $tag_label,
				];
			}

			$basic_fields += self::buildField('pub_keywords', $this->t('Tags:'), $tags);
		}

		$custom_fields = [];

		// Defaults to the current logged in user self contact id to show self-only fields
		$contact_id = $view_as_contact_id ?: $remote_contact_id ?: 0;

		if ($is_owner && $contact_id === 0) {
			$profile_fields = $this->profileField->selectByUserId($profile['uid']);
		} else {
			$profile_fields = $this->profileField->selectByContactId($contact_id, $profile['uid']);
		}

		foreach ($profile_fields as $profile_field) {
			$custom_fields += self::buildField(
				'custom_' . $profile_field->order,
				$profile_field->label,
				$this->tryRelMe($profile_field->value) ?: BBCode::convertForUriId($profile['uri-id'], $profile_field->value),
				'aprofile custom'
			);
		}

		//show subcribed forum if it is enabled in the usersettings
		if (Feature::isEnabled($profile['uid'], 'forumlist_profile')) {
			$custom_fields += self::buildField(
				'forumlist',
				$this->t('Forums:'),
				ForumManager::profileAdvanced($profile['uid'])
			);
		}

		$tpl = Renderer::getMarkupTemplate('profile/profile.tpl');
		$o   .= Renderer::replaceMacros($tpl, [
			'$title'                 => $this->t('Profile'),
			'$yourself'              => $this->t('Yourself'),
			'$view_as_contacts'      => $view_as_contacts,
			'$view_as_contact_id'    => $view_as_contact_id,
			'$view_as_contact_alert' => $view_as_contact_alert,
			'$view_as'               => $this->t('View profile as:'),
			'$submit'                => $this->t('Submit'),
			'$basic'                 => $this->t('Basic'),
			'$advanced'              => $this->t('Advanced'),
			'$is_owner'              => $profile['uid'] == $this->session->getLocalUserId(),
			'$query_string'          => $this->args->getQueryString(),
			'$basic_fields'          => $basic_fields,
			'$custom_fields'         => $custom_fields,
			'$profile'               => $profile,
			'$edit_link'             => [
				'url'   => 'settings/profile', $this->t('Edit profile'),
				'title' => '',
				'label' => $this->t('Edit profile')
			],
			'$viewas_link'           => [
				'url'   => $this->args->getQueryString() . '#viewas',
				'title' => '',
				'label' => $this->t('View as')
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
	private static function buildField(string $name, string $label, $value, string $class = 'aprofile'): array
	{
		return [$name => [
			'id'    => 'aprofile-' . $name,
			'class' => $class,
			'label' => $label,
			'value' => $value,
		]];
	}

	private function buildHtmlHead(array $profile, string $nickname): string
	{
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
		$blocked   = !$this->session->isAuthenticated() && $this->config->get('system', 'block_public');
		$userblock = !$this->session->isAuthenticated() && $profile['hidewall'];
		if (!$blocked && !$userblock) {
			$keywords = str_replace(['#', ',', ' ', ',,'], ['', ' ', ',', ','], $profile['pub_keywords'] ?? '');
			if (strlen($keywords)) {
				$htmlhead .= '<meta name="keywords" content="' . $keywords . '" />' . "\n";
			}
		}

		$htmlhead .= '<meta name="dfrn-global-visibility" content="' . ($profile['net-publish'] ? 'true' : 'false') . '" />' . "\n";

		if (!$profile['net-publish']) {
			$htmlhead .= '<meta content="noindex, noarchive" name="robots" />' . "\n";
		}

		$htmlhead .= '<link rel="alternate" type="application/atom+xml" href="' . $this->baseUrl . '/dfrn_poll/' . $nickname . '" title="DFRN: ' . $this->t('%s\'s timeline', $profile['name']) . '"/>' . "\n";
		$htmlhead .= '<link rel="alternate" type="application/atom+xml" href="' . $this->baseUrl . '/feed/' . $nickname . '/" title="' . $this->t('%s\'s posts', $profile['name']) . '"/>' . "\n";
		$htmlhead .= '<link rel="alternate" type="application/atom+xml" href="' . $this->baseUrl . '/feed/' . $nickname . '/comments" title="' . $this->t('%s\'s comments', $profile['name']) . '"/>' . "\n";
		$htmlhead .= '<link rel="alternate" type="application/atom+xml" href="' . $this->baseUrl . '/feed/' . $nickname . '/activity" title="' . $this->t('%s\'s timeline', $profile['name']) . '"/>' . "\n";
		$uri      = urlencode('acct:' . $profile['nickname'] . '@' . $this->baseUrl->getHost() . ($this->baseUrl->getPath() ? '/' . $this->baseUrl->getPath() : ''));
		$htmlhead .= '<link rel="lrdd" type="application/xrd+xml" href="' . $this->baseUrl . '/xrd/?uri=' . $uri . '" />' . "\n";
		header('Link: <' . $this->baseUrl . '/xrd/?uri=' . $uri . '>; rel="lrdd"; type="application/xrd+xml"', false);

		$dfrn_pages = ['request', 'confirm', 'notify', 'poll'];
		foreach ($dfrn_pages as $dfrn) {
			$htmlhead .= '<link rel="dfrn-' . $dfrn . '" href="' . $this->baseUrl . '/dfrn_' . $dfrn . '/' . $nickname . '" />' . "\n";
		}

		return $htmlhead;
	}

	/**
	 * Check if the input is an HTTP(S) link and returns a rel="me" link if yes, empty string if not
	 *
	 * @param string $input
	 * @return string
	 */
	private function tryRelMe(string $input): string
	{
		if (preg_match(Strings::onlyLinkRegEx(), trim($input))) {
			return '<a href="' . trim($input) . '" target="_blank" rel="noopener noreferrer me">' . trim($input) . '</a>';
		}

		return '';
	}
}
