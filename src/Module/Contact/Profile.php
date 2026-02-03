<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Contact;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\App\Page;
use Friendica\BaseModule;
use Friendica\Contact\LocalRelationship\Entity\LocalRelationship as LocalRelationshipEntity;
use Friendica\Contact\LocalRelationship\Repository\LocalRelationship as LocalRelationshipRepository;
use Friendica\Content\ContactSelector;
use Friendica\Content\Nav;
use Friendica\Content\Text\BBCode;
use Friendica\Content\Widget;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\Worker;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Event\ArrayFilterEvent;
use Friendica\Model\Circle;
use Friendica\Model\Contact as ContactModel;
use Friendica\Model\Contact\User as UserContact;
use Friendica\Module\Contact as ContactModule;
use Friendica\Module\Response;
use Friendica\Module\Security\Login;
use Friendica\Navigation\SystemMessages;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\User\Settings\Repository\UserGServer as UserGServerRepository;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Profiler;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 *  Show a contact profile
 */
class Profile extends BaseModule
{
	/** @var LocalRelationshipRepository */
	private $localRelationship;
	/** @var Page */
	private $page;
	/** @var IManageConfigValues */
	private $config;
	/** @var IHandleUserSessions */
	private $session;
	/** @var SystemMessages */
	private $systemMessages;
	/** @var Database */
	private $db;
	/** @var UserGServerRepository */
	private $userGServer;
	private EventDispatcherInterface $eventDispatcher;

	public function __construct(
		UserGServerRepository $userGServer,
		EventDispatcherInterface $eventDispatcher,
		Database $db,
		SystemMessages $systemMessages,
		IHandleUserSessions $session,
		L10n $l10n,
		LocalRelationshipRepository $localRelationship,
		BaseURL $baseUrl,
		Arguments $args,
		LoggerInterface $logger,
		Profiler $profiler,
		Response $response,
		Page $page,
		IManageConfigValues $config,
		array $server,
		array $parameters = []
	) {
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->localRelationship = $localRelationship;
		$this->page              = $page;
		$this->config            = $config;
		$this->session           = $session;
		$this->systemMessages    = $systemMessages;
		$this->db                = $db;
		$this->userGServer       = $userGServer;
		$this->eventDispatcher   = $eventDispatcher;
	}

	protected function post(array $request = [])
	{
		if (!$this->session->getLocalUserId()) {
			return;
		}

		$contact_id = $this->parameters['id'];

		// Backward compatibility: The update still needs a user-specific contact ID
		// Change to user-contact table check by version 2022.03
		$ucid = ContactModel::getUserContactId($contact_id, $this->session->getLocalUserId());
		if (!$ucid || !$this->db->exists('contact', ['id' => $ucid, 'deleted' => false])) {
			return;
		}

		$request = $this->eventDispatcher->dispatch(
			new ArrayFilterEvent(ArrayFilterEvent::EDIT_CONTACT_POST, $request),
		)->getArray();

		$fields = [];

		if (isset($request['hidden'])) {
			$fields['hidden'] = !empty($request['hidden']);
		}

		if (isset($request['notify_new_posts'])) {
			$fields['notify_new_posts'] = !empty($request['notify_new_posts']);
		}

		if (isset($request['fetch_further_information'])) {
			$fields['fetch_further_information'] = intval($request['fetch_further_information']);
		}

		if (isset($request['remote_self'])) {
			$fields['remote_self'] = intval($request['remote_self']);
		}

		if (isset($request['ffi_keyword_denylist'])) {
			$fields['ffi_keyword_denylist'] = $request['ffi_keyword_denylist'];
		}

		if (isset($request['poll'])) {
			$priority = intval($request['poll']);
			if ($priority > 5 || $priority < 0) {
				$priority = 0;
			}

			$fields['priority'] = $priority;
		}

		if (isset($request['info'])) {
			$fields['info'] = $request['info'];
		}

		if (isset($request['channel_frequency'])) {
			UserContact::setChannelFrequency($ucid, $this->session->getLocalUserId(), $request['channel_frequency']);
		}

		if (isset($request['channel_only'])) {
			UserContact::setChannelOnly($ucid, $this->session->getLocalUserId(), $request['channel_only']);
		}

		if (!ContactModel::update($fields, ['id' => $ucid, 'uid' => $this->session->getLocalUserId()])) {
			$this->systemMessages->addNotice($this->t('Failed to update contact record.'));
		}
		$this->baseUrl->redirect('contact/' . $contact_id);
	}

	protected function content(array $request = []): string
	{
		if (!$this->session->getLocalUserId()) {
			return Login::form($_SERVER['REQUEST_URI']);
		}

		// Backward compatibility: Ensure to use the public contact when the user contact is provided
		// Remove by version 2022.03
		$data = ContactModel::getPublicAndUserContactID(intval($this->parameters['id']), $this->session->getLocalUserId());
		if (empty($data)) {
			throw new NotFoundException($this->t('Contact not found.'));
		}

		$contact = ContactModel::getById($data['public']);
		if (!$this->db->isResult($contact)) {
			throw new NotFoundException($this->t('Contact not found.'));
		}

		// Fetch the protocol from the user's contact.
		if ($data['user']) {
			$usercontact = ContactModel::getById($data['user'], ['network', 'protocol']);
			if ($this->db->isResult($usercontact)) {
				$contact['network']  = $usercontact['network'];
				$contact['protocol'] = $usercontact['protocol'];
			}
		}

		if (empty($contact['network']) && ContactModel::isLocal($contact['url']) ) {
			$contact['network']  = Protocol::DFRN;
			$contact['protocol'] = Protocol::ACTIVITYPUB;
		}

		// Don't display contacts that are about to be deleted
		if ($contact['deleted'] || $contact['network'] == Protocol::PHANTOM) {
			throw new NotFoundException($this->t('Contact not found.'));
		}

		$localRelationship = $this->localRelationship->getForUserContact($this->session->getLocalUserId(), $contact['id']);

		if ($localRelationship->rel === ContactModel::SELF) {
			$this->baseUrl->redirect('profile/' . $contact['nick'] . '/profile');
		}

		if (isset($this->parameters['action'])) {
			self::checkFormSecurityTokenRedirectOnError('contact/' . $contact['id'], 'contact_action', 't');

			$cmd = $this->parameters['action'];
			if ($cmd === 'update' && $localRelationship->rel !== ContactModel::NOTHING) {
				ContactModule::updateContactFromPoll($contact['id']);
			}

			if ($cmd === 'updateprofile') {
				$this->updateContactFromProbe($contact['id']);
			}

			if ($cmd === 'fetchoutbox') {
				Worker::add(Worker::PRIORITY_MEDIUM, 'FetchOutbox', $contact['id'], 0);
			}

			if ($cmd === 'block') {
				if ($localRelationship->blocked) {
					// @TODO Backward compatibility, replace with $localRelationship->unblock()
					UserContact::setBlocked($contact['id'], $this->session->getLocalUserId(), false);

					$message = $this->t('Contact has been unblocked');
				} else {
					// @TODO Backward compatibility, replace with $localRelationship->block()
					UserContact::setBlocked($contact['id'], $this->session->getLocalUserId(), true);
					$message = $this->t('Contact has been blocked');
				}

				// @TODO: add $this->localRelationship->save($localRelationship);
				$this->systemMessages->addInfo($message);
			}

			if ($cmd === 'ignore') {
				if ($localRelationship->ignored) {
					// @TODO Backward compatibility, replace with $localRelationship->unblock()
					UserContact::setIgnored($contact['id'], $this->session->getLocalUserId(), false);

					$message = $this->t('Contact has been unignored');
				} else {
					// @TODO Backward compatibility, replace with $localRelationship->block()
					UserContact::setIgnored($contact['id'], $this->session->getLocalUserId(), true);
					$message = $this->t('Contact has been ignored');
				}

				// @TODO: add $this->localRelationship->save($localRelationship);
				$this->systemMessages->addInfo($message);
			}

			if ($cmd === 'collapse') {
				if ($localRelationship->collapsed) {
					// @TODO Backward compatibility, replace with $localRelationship->unblock()
					UserContact::setCollapsed($contact['id'], $this->session->getLocalUserId(), false);

					$message = $this->t('Contact has been uncollapsed');
				} else {
					// @TODO Backward compatibility, replace with $localRelationship->block()
					UserContact::setCollapsed($contact['id'], $this->session->getLocalUserId(), true);
					$message = $this->t('Contact has been collapsed');
				}

				// @TODO: add $this->localRelationship->save($localRelationship);
				$this->systemMessages->addInfo($message);
			}

			$this->baseUrl->redirect('contact/' . $contact['id']);
		}

		$vcard_widget   = Widget\VCard::getHTML($contact);
		$circles_widget = '';

		if (!in_array($localRelationship->rel, [ContactModel::NOTHING, ContactModel::SELF])) {
			$circles_widget = Circle::sidebarWidget('contact', 'circle', 'full', 'everyone', $data['user']);
		}

		$this->page['aside'] .= $vcard_widget . $circles_widget;

		$o = '';
		Nav::setSelected('contact');

		$_SESSION['return_path'] = $this->args->getQueryString();

		$this->page['htmlhead'] .= Renderer::replaceMacros(Renderer::getMarkupTemplate('contact_head.tpl'), [
		]);

		$con = $this->t('Connection:');
		switch ($localRelationship->rel) {
			case ContactModel::FRIEND:
				$relation_text = $this->t('Friend');
				break;
			case ContactModel::FOLLOWER:
				$relation_text = $this->t('Follows you');
				break;
			case ContactModel::SHARING:
				$relation_text = $this->t('You follow');
				break;
			default:
				$relation_text = '';
		}

		if (!Protocol::supportsFollow($contact['network'])) {
			$relation_text = '';
		}

		$url = ContactModel::magicLinkByContact($contact);
		if (strpos($url, 'contact/redir/') === 0) {
			$sparkle = ' class="sparkle" ';
		} else {
			$sparkle = '';
		}

		$insecure = $this->t('Private communications are not available for this contact.');

		// @TODO: Figure out why gsid can be empty
		if (empty($contact['gsid'])) {
			$this->logger->notice('Empty gsid for contact', ['contact' => $contact]);
		}

		$serverIgnored = $contact['gsid'] &&
			$this->userGServer->isIgnoredByUser($this->session->getLocalUserId(), $contact['gsid']) ?
				$this->t('This contact is on a server you ignored.')
				: '';

		$last_update = (($contact['last-update'] <= DBA::NULL_DATETIME) ? $this->t('Never') : DateTimeFormat::local($contact['last-update'], 'D, j M Y, g:i A'));

		if ($contact['last-update'] > DBA::NULL_DATETIME) {
			$last_update .= ' ' . ($contact['failed'] ? $this->t('(Update was not successful)') : $this->t('(Update was successful)'));
		}
		$lblsuggest = (($contact['network'] === Protocol::DFRN) ? $this->t('Suggest friends') : '');

		$poll_enabled = in_array($contact['network'], [Protocol::DFRN, Protocol::FEED, Protocol::MAIL]);

		$nettype = $this->t('Network type: %s', ContactSelector::networkToName($contact['network'], $contact['protocol'], $contact['gsid']));

		ContactModule::setPageTitle($contact);

		// tabs
		$tab_str = ContactModule::getTabsHTML($contact, ContactModule::TAB_PROFILE);

		$lost_contact = (($contact['archive'] && $contact['term-date'] > DBA::NULL_DATETIME && $contact['term-date'] < DateTimeFormat::utcNow()) ? $this->t('Communications lost with this contact!') : '');

		$fetch_further_information = null;
		if ($contact['network'] == Protocol::FEED) {
			$fetch_further_information = [
				'fetch_further_information',
				$this->t('Fetch further information for feeds'),
				$localRelationship->fetchFurtherInformation,
				$this->t('Fetch information like preview pictures, title and teaser from the feed item. You can activate this if the feed doesn\'t contain much text. Keywords are taken from the meta header in the feed item and are posted as hash tags.'),
				[
					LocalRelationshipEntity::FFI_NONE        => $this->t('Disabled'),
					LocalRelationshipEntity::FFI_INFORMATION => $this->t('Fetch information'),
					LocalRelationshipEntity::FFI_KEYWORD     => $this->t('Fetch keywords'),
					LocalRelationshipEntity::FFI_BOTH        => $this->t('Fetch information and keywords')
				]
			];
		}

		$allow_remote_self = in_array($contact['network'], [Protocol::ACTIVITYPUB, Protocol::FEED, Protocol::DFRN, Protocol::DIASPORA, Protocol::TWITTER])
			&& $this->config->get('system', 'allow_users_remote_self');

		if ($contact['network'] == Protocol::FEED) {
			$remote_self_options = [
				LocalRelationshipEntity::MIRROR_DEACTIVATED => $this->t('No mirroring'),
				LocalRelationshipEntity::MIRROR_OWN_POST    => $this->t('Mirror as my own posting')
			];
		} elseif ($contact['network'] == Protocol::ACTIVITYPUB) {
			$remote_self_options = [
				LocalRelationshipEntity::MIRROR_DEACTIVATED    => $this->t('No mirroring'),
				LocalRelationshipEntity::MIRROR_NATIVE_RESHARE => $this->t('Native reshare')
			];
		} elseif ($contact['network'] == Protocol::DFRN) {
			$remote_self_options = [
				LocalRelationshipEntity::MIRROR_DEACTIVATED    => $this->t('No mirroring'),
				LocalRelationshipEntity::MIRROR_OWN_POST       => $this->t('Mirror as my own posting'),
				LocalRelationshipEntity::MIRROR_NATIVE_RESHARE => $this->t('Native reshare')
			];
		} else {
			$remote_self_options = [
				LocalRelationshipEntity::MIRROR_DEACTIVATED => $this->t('No mirroring'),
				LocalRelationshipEntity::MIRROR_OWN_POST    => $this->t('Mirror as my own posting')
			];
		}

		$channel_frequency = UserContact::getChannelFrequency($contact['id'], $this->session->getLocalUserId());
		$channel_only      = UserContact::getChannelOnly($contact['id'], $this->session->getLocalUserId());

		$poll_interval = null;
		if ((($contact['network'] == Protocol::FEED) && !$this->config->get('system', 'adjust_poll_frequency')) || ($contact['network'] == Protocol::MAIL)) {
			$poll_interval = ContactSelector::pollInterval($localRelationship->priority, !$poll_enabled);
		}

		$contact_actions = $this->getContactActions($contact, $localRelationship);

		if (UserContact::isIsBlocked($contact['id'], $this->session->getLocalUserId())) {
			$relation_text = $this->t('%s has blocked you', $contact['name'] ?: $contact['nick']);
			unset($contact_actions['follow']);
		}

		if ($localRelationship->rel !== ContactModel::NOTHING) {
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
			'$reason'                    => trim($contact['reason'] ?? ''),
			'$infedit'                   => $this->t('Edit contact notes'),
			'$common_link'               => 'contact/' . $contact['id'] . '/contacts/common',
			'$con'                       => $con,
			'$relation_text'             => $relation_text,
			'$visit'                     => $this->t('Visit %s\'s profile [%s]', $contact['name'], $contact['url']),
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
			'$contact_id'                => $contact['id'],
			'$pending'                   => $localRelationship->pending   ? $this->t('Awaiting connection acknowledge') : '',
			'$blocked'                   => $localRelationship->blocked   ? $this->t('Currently blocked') : '',
			'$ignored'                   => $localRelationship->ignored   ? $this->t('Currently ignored') : '',
			'$collapsed'                 => $localRelationship->collapsed ? $this->t('Currently collapsed') : '',
			'$archived'                  => ($contact['archive'] ? $this->t('Currently archived') : ''),
			'$insecure'                  => (in_array($contact['network'], [Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::MAIL, Protocol::DIASPORA]) ? '' : $insecure),
			'$serverIgnored'             => $serverIgnored,
			'$manageServers'             => $this->t('Manage remote servers'),
			'$cinfo'                     => ['info', '', $localRelationship->info, ''],
			'$hidden'                    => ['hidden', $this->t('Hide this contact from others'), $localRelationship->hidden, $this->t('Replies/likes to your public posts <strong>may</strong> still be visible')],
			'$notify_new_posts'          => ['notify_new_posts', $this->t('Notification for new posts'), ($localRelationship->notifyNewPosts), $this->t('Send a notification of every new post of this contact')],
			'$fetch_further_information' => $fetch_further_information,
			'$ffi_keyword_denylist'      => ['ffi_keyword_denylist', $this->t('Keyword Deny List'), $localRelationship->ffiKeywordDenylist, $this->t('Comma separated list of keywords that should not be converted to hashtags, when "Fetch information and keywords" is selected')],
			'$photo'                     => ContactModel::getPhoto($contact),
			'$name'                      => $contact['name'],
			'$sparkle'                   => $sparkle,
			'$url'                       => $url,
			'$profileurllabel'           => $this->t('Profile URL'),
			'$profileurl'                => $contact['alias'] ?: $contact['url'],
			'$account_type'              => ContactModel::getAccountType($contact['contact-type']),
			'$location'                  => BBCode::convertForUriId($contact['uri-id'] ?? 0, $contact['location']),
			'$location_label'            => $this->t('Location:'),
			'$xmpp'                      => BBCode::convertForUriId($contact['uri-id'] ?? 0, $contact['xmpp']),
			'$xmpp_label'                => $this->t('XMPP:'),
			'$matrix'                    => BBCode::convertForUriId($contact['uri-id'] ?? 0, $contact['matrix']),
			'$matrix_label'              => $this->t('Matrix:'),
			'$about'                     => BBCode::convertForUriId($contact['uri-id'] ?? 0, $contact['about'], BBCode::EXTERNAL),
			'$about_label'               => $this->t('About:'),
			'$keywords'                  => $contact['keywords'],
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
			'$frequency_default'      => ['channel_frequency', $this->t('Default frequency'), UserContact::FREQUENCY_DEFAULT, $this->t('Posts by this contact are displayed in the "for you" channel if you interact often with this contact or if a post reached some level of interaction.'), $channel_frequency == UserContact::FREQUENCY_DEFAULT],
			'$frequency_always'       => ['channel_frequency', $this->t('Display all posts of this contact'), UserContact::FREQUENCY_ALWAYS, $this->t('All posts from this contact will appear on the "for you" channel'), $channel_frequency == UserContact::FREQUENCY_ALWAYS],
			'$frequency_reduced'      => ['channel_frequency', $this->t('Display only few posts'), UserContact::FREQUENCY_REDUCED, $this->t('When a contact creates a lot of posts in a short period, this setting reduces the number of displayed posts in every channel.'), $channel_frequency == UserContact::FREQUENCY_REDUCED],
			'$frequency_never'        => ['channel_frequency', $this->t('Never display posts'), UserContact::FREQUENCY_NEVER, $this->t('Posts from this contact will never be displayed in any channel'), $channel_frequency == UserContact::FREQUENCY_NEVER],
			'$channel_only'           => ['channel_only', $this->t('Channel Only'), $channel_only, $this->t('If enabled, posts from this contact will only appear in channels and network streams in circles, but not in the general network stream.')],
		]);

		$hook_data = [
			'contact' => $contact,
			'output'  => $o,
		];

		$hook_data = $this->eventDispatcher->dispatch(
			new ArrayFilterEvent(ArrayFilterEvent::EDIT_CONTACT_FORM, $hook_data),
		)->getArray();

		return $hook_data['output'] ?? $o;
	}

	/**
	 * Returns the list of available actions that can performed on the provided contact
	 *
	 * This includes actions like e.g. 'block', 'hide', 'delete' and others
	 *
	 * @param array                    $contact           Public contact row
	 *
	 * @return array with contact related actions
	 * @throws InternalServerErrorException
	 */
	private function getContactActions(array $contact, LocalRelationshipEntity $localRelationship): array
	{
		$poll_enabled    = in_array($contact['network'], [Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::FEED, Protocol::MAIL]);
		$contact_actions = [];

		$formSecurityToken = self::getFormSecurityToken('contact_action');

		if ($localRelationship->rel & ContactModel::SHARING) {
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
				'url'   => 'contact/follow?binurl=' . bin2hex($contact['url']) . '&auto=1',
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

		if (in_array($contact['network'], [Protocol::ACTIVITYPUB, Protocol::DFRN])) {
			$contact_actions['fetchoutbox'] = [
				'label' => $this->t('Fetch latest posts'),
				'url'   => 'contact/' . $contact['id'] . '/fetchoutbox?t=' . $formSecurityToken,
				'title' => '',
				'sel'   => '',
				'id'    => 'fetchoutbox',
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

		if (Protocol::supportsRevokeFollow($contact['network']) && in_array($localRelationship->rel, [ContactModel::FOLLOWER, ContactModel::FRIEND])) {
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
	 * @throws InternalServerErrorException
	 * @throws \ImagickException
	 */
	private function updateContactFromProbe(int $contact_id)
	{
		if (!$this->db->exists('contact', ['id' => $contact_id, 'uid' => [0, $this->session->getLocalUserId()], 'deleted' => false])) {
			return;
		}

		// Update the entry in the contact table
		ContactModel::updateFromProbe($contact_id);
	}
}
