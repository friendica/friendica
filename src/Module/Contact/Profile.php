<?php
/**
 * @copyright Copyright (C) 2010-2024, the Friendica project
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

namespace Friendica\Module\Contact;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Contact\LocalRelationship;
use Friendica\Content\ContactSelector;
use Friendica\Content\Nav;
use Friendica\Content\Text\BBCode;
use Friendica\Content\Widget;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Model\Circle;
use Friendica\Model\Contact;
use Friendica\Module;
use Friendica\Module\Response;
use Friendica\Navigation\SystemMessages;
use Friendica\Network\HTTPException;
use Friendica\User\Settings;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 *  Show a contact profile
 */
class Profile extends BaseModule
{
	/** @var LocalRelationship\Repository\LocalRelationship */
	private $localRelationship;
	/** @var App\Page */
	private $page;
	/** @var IManageConfigValues */
	private $config;
	/** @var IHandleUserSessions */
	private $session;
	/** @var SystemMessages */
	private $systemMessages;
	/** @var Database */
	private $db;
	/** @var Settings\Repository\UserGServer */
	private $userGServer;

	public function __construct(Settings\Repository\UserGServer $userGServer, Database $db, SystemMessages $systemMessages, IHandleUserSessions $session, L10n $l10n, LocalRelationship\Repository\LocalRelationship $localRelationship, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, App\Page $page, IManageConfigValues $config, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->localRelationship = $localRelationship;
		$this->page              = $page;
		$this->config            = $config;
		$this->session           = $session;
		$this->systemMessages    = $systemMessages;
		$this->db                = $db;
		$this->userGServer       = $userGServer;
	}

	protected function post(array $request = [])
	{
		if (!$this->session->getLocalUserId()) {
			return;
		}

		$contact_id = $this->parameters['id'];

		// Backward compatibility: The update still needs a user-specific contact ID
		// Change to user-contact table check by version 2022.03
		$cdata = Contact::getPublicAndUserContactID($contact_id, $this->session->getLocalUserId());
		if (empty($cdata['user']) || !$this->db->exists('contact', ['id' => $cdata['user'], 'deleted' => false])) {
			return;
		}

		Hook::callAll('contact_edit_post', $_POST);

		$fields = [];

		if (isset($_POST['hidden'])) {
			$fields['hidden'] = !empty($_POST['hidden']);
		}

		if (isset($_POST['notify_new_posts'])) {
			$fields['notify_new_posts'] = !empty($_POST['notify_new_posts']);
		}

		if (isset($_POST['fetch_further_information'])) {
			$fields['fetch_further_information'] = intval($_POST['fetch_further_information']);
		}

		if (isset($_POST['remote_self'])) {
			$fields['remote_self'] = intval($_POST['remote_self']);
		}

		if (isset($_POST['ffi_keyword_denylist'])) {
			$fields['ffi_keyword_denylist'] = $_POST['ffi_keyword_denylist'];
		}

		if (isset($_POST['poll'])) {
			$priority = intval($_POST['poll']);
			if ($priority > 5 || $priority < 0) {
				$priority = 0;
			}

			$fields['priority'] = $priority;
		}

		if (isset($_POST['info'])) {
			$fields['info'] = $_POST['info'];
		}

		if (isset($_POST['channel_frequency'])) {
			Contact\User::setChannelFrequency($cdata['user'], $this->session->getLocalUserId(), $_POST['channel_frequency']);
		}

		if (!Contact::update($fields, ['id' => $cdata['user'], 'uid' => $this->session->getLocalUserId()])) {
			$this->systemMessages->addNotice($this->t('Failed to update contact record.'));
		}
	}

	protected function content(array $request = []): string
	{
		if (!$this->session->getLocalUserId()) {
			return Module\Security\Login::form($_SERVER['REQUEST_URI']);
		}

		// Backward compatibility: Ensure to use the public contact when the user contact is provided
		// Remove by version 2022.03
		$data = Contact::getPublicAndUserContactID(intval($this->parameters['id']), $this->session->getLocalUserId());
		if (empty($data)) {
			throw new HTTPException\NotFoundException($this->t('Contact not found.'));
		}

		$account = Contact::selectFirstAccount([], ['id' => $data['public']]);
		if (!$this->db->isResult($account)) {
			throw new HTTPException\NotFoundException($this->t('Contact not found.'));
		}

		// Don't display contacts that are about to be deleted
		if ($this->db->isResult($account) && (!empty($account['deleted']) || !empty($account['network']) && $account['network'] == Protocol::PHANTOM)) {
			throw new HTTPException\NotFoundException($this->t('Contact not found.'));
		}

		$localRelationship = $this->localRelationship->getForUserContact($this->session->getLocalUserId(), $account['id']);

		if ($localRelationship->rel === Contact::SELF) {
			$this->baseUrl->redirect('profile/' . $account['nick'] . '/profile');
		}

		if (isset($this->parameters['action'])) {
			self::checkFormSecurityTokenRedirectOnError('contact/' . $account['id'], 'contact_action', 't');

			$cmd = $this->parameters['action'];
			if ($cmd === 'update' && $localRelationship->rel !== Contact::NOTHING) {
				Module\Contact::updateContactFromPoll($account['id']);
			}

			if ($cmd === 'updateprofile') {
				$this->updateContactFromProbe($account['id']);
			}

			if ($cmd === 'block') {
				if ($localRelationship->blocked) {
					// @TODO Backward compatibility, replace with $localRelationship->unblock()
					Contact\User::setBlocked($account['id'], $this->session->getLocalUserId(), false);

					$message = $this->t('Contact has been unblocked');
				} else {
					// @TODO Backward compatibility, replace with $localRelationship->block()
					Contact\User::setBlocked($account['id'], $this->session->getLocalUserId(), true);
					$message = $this->t('Contact has been blocked');
				}

				// @TODO: add $this->localRelationship->save($localRelationship);
				$this->systemMessages->addInfo($message);
			}

			if ($cmd === 'ignore') {
				if ($localRelationship->ignored) {
					// @TODO Backward compatibility, replace with $localRelationship->unblock()
					Contact\User::setIgnored($account['id'], $this->session->getLocalUserId(), false);

					$message = $this->t('Contact has been unignored');
				} else {
					// @TODO Backward compatibility, replace with $localRelationship->block()
					Contact\User::setIgnored($account['id'], $this->session->getLocalUserId(), true);
					$message = $this->t('Contact has been ignored');
				}

				// @TODO: add $this->localRelationship->save($localRelationship);
				$this->systemMessages->addInfo($message);
			}

			if ($cmd === 'collapse') {
				if ($localRelationship->collapsed) {
					// @TODO Backward compatibility, replace with $localRelationship->unblock()
					Contact\User::setCollapsed($account['id'], $this->session->getLocalUserId(), false);

					$message = $this->t('Contact has been uncollapsed');
				} else {
					// @TODO Backward compatibility, replace with $localRelationship->block()
					Contact\User::setCollapsed($account['id'], $this->session->getLocalUserId(), true);
					$message = $this->t('Contact has been collapsed');
				}

				// @TODO: add $this->localRelationship->save($localRelationship);
				$this->systemMessages->addInfo($message);
			}

			$this->baseUrl->redirect('contact/' . $account['id']);
		}

		$vcard_widget  = Widget\VCard::getHTML($account);
		$circles_widget = '';

		if (!in_array($localRelationship->rel, [Contact::NOTHING, Contact::SELF])) {
			$circles_widget = Circle::sidebarWidget('contact', 'circle', 'full', 'everyone', $data['user']);
		}

		$this->page['aside'] .= $vcard_widget . $circles_widget;

		$o = '';
		Nav::setSelected('contact');

		$_SESSION['return_path'] = $this->args->getQueryString();

		$this->page['htmlhead'] .= Renderer::replaceMacros(Renderer::getMarkupTemplate('contact_head.tpl'), [
		]);

		switch ($localRelationship->rel) {
			case Contact::FRIEND:   $relation_text = $this->t('You are mutual friends with %s', $account['name']); break;
			case Contact::FOLLOWER:	$relation_text = $this->t('You are sharing with %s', $account['name']); break;
			case Contact::SHARING:  $relation_text = $this->t('%s is sharing with you', $account['name']); break;
			default:
				$relation_text = '';
		}

		if (!Protocol::supportsFollow($account['network'])) {
			$relation_text = '';
		}

		$url = Contact::magicLinkByContact($account);
		if (strpos($url, 'contact/redir/') === 0) {
			$sparkle = ' class="sparkle" ';
		} else {
			$sparkle = '';
		}

		$insecure = $this->t('Private communications are not available for this contact.');

		// @TODO: Figure out why gsid can be empty
		if (empty($account['gsid'])) {
			$this->logger->notice('Empty gsid for contact', ['contact' => $account]);
		}

		$serverIgnored =
			$account['gsid'] &&
			$this->userGServer->isIgnoredByUser($this->session->getLocalUserId(), $account['gsid']) ?
				$this->t('This contact is on a server you ignored.')
				: '';

		$last_update = (($account['last-update'] <= DBA::NULL_DATETIME) ? $this->t('Never') : DateTimeFormat::local($account['last-update'], 'D, j M Y, g:i A'));

		if ($account['last-update'] > DBA::NULL_DATETIME) {
			$last_update .= ' ' . ($account['failed'] ? $this->t('(Update was not successful)') : $this->t('(Update was successful)'));
		}
		$lblsuggest = (($account['network'] === Protocol::DFRN) ? $this->t('Suggest friends') : '');

		$poll_enabled = in_array($account['network'], [Protocol::DFRN, Protocol::OSTATUS, Protocol::FEED, Protocol::MAIL]);

		$nettype = $this->t('Network type: %s', ContactSelector::networkToName($account['network'], $account['url'], $account['protocol'], $account['gsid']));

		// tabs
		$tab_str = Module\Contact::getTabsHTML($account, Module\Contact::TAB_PROFILE);

		$lost_contact = (($account['archive'] && $account['term-date'] > DBA::NULL_DATETIME && $account['term-date'] < DateTimeFormat::utcNow()) ? $this->t('Communications lost with this contact!') : '');

		$fetch_further_information = null;
		if ($account['network'] == Protocol::FEED) {
			$fetch_further_information = [
				'fetch_further_information',
				$this->t('Fetch further information for feeds'),
				$localRelationship->fetchFurtherInformation,
				$this->t('Fetch information like preview pictures, title and teaser from the feed item. You can activate this if the feed doesn\'t contain much text. Keywords are taken from the meta header in the feed item and are posted as hash tags.'),
				[
					LocalRelationship\Entity\LocalRelationship::FFI_NONE        => $this->t('Disabled'),
					LocalRelationship\Entity\LocalRelationship::FFI_INFORMATION => $this->t('Fetch information'),
					LocalRelationship\Entity\LocalRelationship::FFI_KEYWORD     => $this->t('Fetch keywords'),
					LocalRelationship\Entity\LocalRelationship::FFI_BOTH        => $this->t('Fetch information and keywords')
				]
			];
		}

		$allow_remote_self = in_array($account['network'], [Protocol::ACTIVITYPUB, Protocol::FEED, Protocol::DFRN, Protocol::DIASPORA, Protocol::TWITTER])
			&& $this->config->get('system', 'allow_users_remote_self');

		if ($account['network'] == Protocol::FEED) {
			$remote_self_options = [
				Contact::MIRROR_DEACTIVATED => $this->t('No mirroring'),
				Contact::MIRROR_OWN_POST    => $this->t('Mirror as my own posting')
			];
		} elseif ($account['network'] == Protocol::ACTIVITYPUB) {
			$remote_self_options = [
				Contact::MIRROR_DEACTIVATED    => $this->t('No mirroring'),
				Contact::MIRROR_NATIVE_RESHARE => $this->t('Native reshare')
			];
		} elseif ($account['network'] == Protocol::DFRN) {
			$remote_self_options = [
				Contact::MIRROR_DEACTIVATED    => $this->t('No mirroring'),
				Contact::MIRROR_OWN_POST       => $this->t('Mirror as my own posting'),
				Contact::MIRROR_NATIVE_RESHARE => $this->t('Native reshare')
			];
		} else {
			$remote_self_options = [
				Contact::MIRROR_DEACTIVATED => $this->t('No mirroring'),
				Contact::MIRROR_OWN_POST    => $this->t('Mirror as my own posting')
			];
		}

		$channel_frequency     = Contact\User::getChannelFrequency($account['id'], $this->session->getLocalUserId());

		$poll_interval = null;
		if ((($account['network'] == Protocol::FEED) && !$this->config->get('system', 'adjust_poll_frequency')) || ($account['network'] == Protocol::MAIL)) {
			$poll_interval = ContactSelector::pollInterval($localRelationship->priority, !$poll_enabled);
		}

		$contact_actions = $this->getContactActions($account, $localRelationship);

		if ($localRelationship->rel !== Contact::NOTHING) {
			$lbl_info1              = $this->t('Contact Information / Notes');
			$contact_settings_label = $this->t('Contact Settings');
		} else {
			$lbl_info1              = null;
			$contact_settings_label = null;
		}

		$tpl = Renderer::getMarkupTemplate('contact_edit.tpl');
		$o .= Renderer::replaceMacros($tpl, [
			'$header'                    => $this->t('Contact'),
			'$tab_str'                   => $tab_str,
			'$submit'                    => $this->t('Submit'),
			'$lbl_info1'                 => $lbl_info1,
			'$lbl_info2'                 => $this->t('Their personal note'),
			'$reason'                    => trim($account['reason'] ?? ''),
			'$infedit'                   => $this->t('Edit contact notes'),
			'$common_link'               => 'contact/' . $account['id'] . '/contacts/common',
			'$relation_text'             => $relation_text,
			'$visit'                     => $this->t('Visit %s\'s profile [%s]', $account['name'], $account['url']),
			'$blockunblock'              => $this->t('Block/Unblock contact'),
			'$ignorecont'                => $this->t('Ignore contact'),
			'$lblrecent'                 => $this->t('View conversations'),
			'$lblsuggest'                => $lblsuggest,
			'$nettype'                   => $nettype,
			'$poll_interval'             => $poll_interval,
			'$poll_enabled'              => $poll_enabled,
			'$lastupdtext'               => $this->t('Last update:'),
			'$lost_contact'              => $lost_contact,
			'$updpub'                    => $this->t('Update public posts'),
			'$last_update'               => $last_update,
			'$udnow'                     => $this->t('Update now'),
			'$contact_id'                => $account['id'],
			'$pending'                   => $localRelationship->pending   ? $this->t('Awaiting connection acknowledge') : '',
			'$blocked'                   => $localRelationship->blocked   ? $this->t('Currently blocked') : '',
			'$ignored'                   => $localRelationship->ignored   ? $this->t('Currently ignored') : '',
			'$collapsed'                 => $localRelationship->collapsed ? $this->t('Currently collapsed') : '',
			'$archived'                  => ($account['archive'] ? $this->t('Currently archived') : ''),
			'$insecure'                  => (in_array($account['network'], [Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::MAIL, Protocol::DIASPORA]) ? '' : $insecure),
			'$serverIgnored'             => $serverIgnored,
			'$manageServers'             => $this->t('Manage remote servers'),
			'$cinfo'                     => ['info', '', $localRelationship->info, ''],
			'$hidden'                    => ['hidden', $this->t('Hide this contact from others'), $localRelationship->hidden, $this->t('Replies/likes to your public posts <strong>may</strong> still be visible')],
			'$notify_new_posts'          => ['notify_new_posts', $this->t('Notification for new posts'), ($localRelationship->notifyNewPosts), $this->t('Send a notification of every new post of this contact')],
			'$fetch_further_information' => $fetch_further_information,
			'$ffi_keyword_denylist'      => ['ffi_keyword_denylist', $this->t('Keyword Deny List'), $localRelationship->ffiKeywordDenylist, $this->t('Comma separated list of keywords that should not be converted to hashtags, when "Fetch information and keywords" is selected')],
			'$photo'                     => Contact::getPhoto($account),
			'$name'                      => $account['name'],
			'$sparkle'                   => $sparkle,
			'$url'                       => $url,
			'$profileurllabel'           => $this->t('Profile URL'),
			'$profileurl'                => $account['url'],
			'$account_type'              => Contact::getAccountType($account['contact-type']),
			'$location'                  => BBCode::convertForUriId($account['uri-id'] ?? 0, $account['location']),
			'$location_label'            => $this->t('Location:'),
			'$xmpp'                      => BBCode::convertForUriId($account['uri-id'] ?? 0, $account['xmpp']),
			'$xmpp_label'                => $this->t('XMPP:'),
			'$matrix'                    => BBCode::convertForUriId($account['uri-id'] ?? 0, $account['matrix']),
			'$matrix_label'              => $this->t('Matrix:'),
			'$about'                     => BBCode::convertForUriId($account['uri-id'] ?? 0, $account['about'], BBCode::EXTERNAL),
			'$about_label'               => $this->t('About:'),
			'$keywords'                  => $account['keywords'],
			'$keywords_label'            => $this->t('Tags:'),
			'$contact_action_button'     => $this->t('Actions'),
			'$contact_actions'           => $contact_actions,
			'$contact_status'            => $this->t('Status'),
			'$contact_settings_label'    => $contact_settings_label,
			'$contact_profile_label'     => $this->t('Profile'),
			'$allow_remote_self'         => $allow_remote_self,
			'$remote_self'               => [
				'remote_self',
				$this->t('Mirror postings from this contact'),
				$localRelationship->remoteSelf,
				$this->t('Mark this contact as remote_self, this will cause friendica to repost new entries from this contact.'),
				$remote_self_options
			],
			'$channel_settings_label' => $this->t('Channel Settings'),
			'$frequency_label'        => $this->t('Frequency of this contact in relevant channels'),
			'$frequency_description'  => $this->t("Depending on the type of the channel not all posts from this contact are displayed. By default, posts need to have a minimum amount of interactions (comments, likes) to show in your channels. On the other hand there can be contacts who flood the channel, so you might want to see only some of their posts. Or you don't want to see their content at all, but you don't want to block or hide the contact completely."),
			'$frequency_default'      => ['channel_frequency', $this->t('Default frequency'), Contact\User::FREQUENCY_DEFAULT, $this->t('Posts by this contact are displayed in the "for you" channel if you interact often with this contact or if a post reached some level of interaction.'), $channel_frequency == Contact\User::FREQUENCY_DEFAULT],
			'$frequency_always'       => ['channel_frequency', $this->t('Display all posts of this contact'), Contact\User::FREQUENCY_ALWAYS, $this->t('All posts from this contact will appear on the "for you" channel'), $channel_frequency == Contact\User::FREQUENCY_ALWAYS],
			'$frequency_reduced'      => ['channel_frequency', $this->t('Display only few posts'), Contact\User::FREQUENCY_REDUCED, $this->t('When a contact creates a lot of posts in a short period, this setting reduces the number of displayed posts in every channel.'), $channel_frequency == Contact\User::FREQUENCY_REDUCED],
			'$frequency_never'        => ['channel_frequency', $this->t('Never display posts'), Contact\User::FREQUENCY_NEVER, $this->t('Posts from this contact will never be displayed in any channel'), $channel_frequency == Contact\User::FREQUENCY_NEVER],
		]);

		$arr = ['contact' => $account, 'output' => $o];

		Hook::callAll('contact_edit', $arr);

		return $arr['output'];
	}

	/**
	 * Returns the list of available actions that can performed on the provided contact
	 *
	 * This includes actions like e.g. 'block', 'hide', 'delete' and others
	 *
	 * @param array                    $contact           Public contact row
	 * @param LocalRelationship\Entity\LocalRelationship $localRelationship
	 * @return array with contact related actions
	 * @throws HTTPException\InternalServerErrorException
	 */
	private function getContactActions(array $contact, LocalRelationship\Entity\LocalRelationship $localRelationship): array
	{
		$poll_enabled    = in_array($contact['network'], [Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::OSTATUS, Protocol::FEED, Protocol::MAIL]);
		$contact_actions = [];

		$formSecurityToken = self::getFormSecurityToken('contact_action');

		if ($localRelationship->rel & Contact::SHARING) {
			$contact_actions['unfollow'] = [
				'label' => $this->t('Unfollow'),
				'url'   => 'contact/unfollow?url=' . urlencode($contact['url']) . '&auto=1',
				'title' => '',
				'sel'   => '',
				'id'    => 'unfollow',
			];
		} else {
			$contact_actions['follow'] = [
				'label' => $this->t('Follow'),
				'url'   => 'contact/follow?url=' . urlencode($contact['url']) . '&auto=1',
				'title' => '',
				'sel'   => '',
				'id'    => 'follow',
			];
		}

		// Provide friend suggestion only for Friendica contacts
		if ($contact['network'] === Protocol::DFRN) {
			$contact_actions['suggest'] = [
				'label' => $this->t('Suggest friends'),
				'url'   => 'fsuggest/' . $contact['id'],
				'title' => '',
				'sel'   => '',
				'id'    => 'suggest',
			];
		}

		if ($poll_enabled) {
			$contact_actions['update'] = [
				'label' => $this->t('Update now'),
				'url'   => 'contact/' . $contact['id'] . '/update?t=' . $formSecurityToken,
				'title' => '',
				'sel'   => '',
				'id'    => 'update',
			];
		}

		if (Protocol::supportsProbe($contact['network'])) {
			$contact_actions['updateprofile'] = [
				'label' => $this->t('Refetch contact data'),
				'url'   => 'contact/' . $contact['id'] . '/updateprofile?t=' . $formSecurityToken,
				'title' => '',
				'sel'   => '',
				'id'    => 'updateprofile',
			];
		}

		$contact_actions['block'] = [
			'label' => $localRelationship->blocked ? $this->t('Unblock') : $this->t('Block'),
			'url'   => 'contact/' . $contact['id'] . '/block?t=' . $formSecurityToken,
			'title' => $this->t('Toggle Blocked status'),
			'sel'   => $localRelationship->blocked ? 'active' : '',
			'id'    => 'toggle-block',
		];

		$contact_actions['ignore'] = [
			'label' => $localRelationship->ignored ? $this->t('Unignore') : $this->t('Ignore'),
			'url'   => 'contact/' . $contact['id'] . '/ignore?t=' . $formSecurityToken,
			'title' => $this->t('Toggle Ignored status'),
			'sel'   => $localRelationship->ignored ? 'active' : '',
			'id'    => 'toggle-ignore',
		];

		$contact_actions['collapse'] = [
			'label' => $localRelationship->collapsed ? $this->t('Uncollapse') : $this->t('Collapse'),
			'url'   => 'contact/' . $contact['id'] . '/collapse?t=' . $formSecurityToken,
			'title' => $this->t('Toggle Collapsed status'),
			'sel'   => $localRelationship->collapsed ? 'active' : '',
			'id'    => 'toggle-collapse',
		];

		if (Protocol::supportsRevokeFollow($contact['network']) && in_array($localRelationship->rel, [Contact::FOLLOWER, Contact::FRIEND])) {
			$contact_actions['revoke_follow'] = [
				'label' => $this->t('Revoke Follow'),
				'url'   => 'contact/' . $contact['id'] . '/revoke',
				'title' => $this->t('Revoke the follow from this contact'),
				'sel'   => '',
				'id'    => 'revoke_follow',
			];
		}

		return $contact_actions;
	}

	/**
	 * Updates contact from probing
	 *
	 * @param int $contact_id Id of the contact with uid != 0
	 * @return void
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private function updateContactFromProbe(int $contact_id)
	{
		if (!$this->db->exists('contact', ['id' => $contact_id, 'uid' => [0, $this->session->getLocalUserId()], 'deleted' => false])) {
			return;
		}

		// Update the entry in the contact table
		Contact::updateFromProbe($contact_id);
	}
}
