<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\App\Mode;
use Friendica\BaseModule;
use Friendica\Content\Conversation\StatusEditor;
use Friendica\Content\GroupManager;
use Friendica\Content\Nav;
use Friendica\Content\Pager;
use Friendica\Content\Text\BBCode;
use Friendica\Content\Text\Plaintext;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\User;
use Friendica\Module\Security\Login;
use Friendica\Navigation\SystemMessages;
use Friendica\Network\HTTPException\ForbiddenException;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Protocol\ActivityPub;
use Friendica\Util\Profiler;
use Friendica\Util\Proxy;
use Psr\Log\LoggerInterface;

/**
 * Overview of the threads of a single group
 */
class Group extends BaseModule
{
	public function __construct(
		private readonly IHandleUserSessions $session,
		private readonly Database $database,
		private readonly IManageConfigValues $config,
		private readonly IManagePersonalConfigValues $pConfig,
		private readonly Mode $mode,
		private readonly StatusEditor $statusEditor,
		private readonly GroupManager $groupManager,
		private readonly SystemMessages $systemMessages,
		L10n $l10n,
		BaseURL $baseUrl,
		Arguments $args,
		LoggerInterface $logger,
		Profiler $profiler,
		Response $response,
		array $server,
		array $parameters = [],
	) {
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
	}

	protected function post(array $request = [])
	{
		$uid = $this->session->getLocalUserId();
		if (!$uid) {
			throw new ForbiddenException($this->t('Permission denied.'));
		}

		$return = 'group/' . rawurlencode((string) $this->parameters['id']);

		self::checkFormSecurityTokenRedirectOnError($return, 'group');

		$contact = $this->getGroup($uid);

		if (!empty($request['fetch'])) {
			if ($this->canFetch($contact, $uid)) {
				Worker::add(Worker::PRIORITY_MEDIUM, 'FetchOutbox', $contact['id'], 0);
				$this->systemMessages->addInfo($this->t('The latest posts of the group will be fetched shortly.'));
			}
		} else {
			$this->groupManager->markSeen($uid, $contact['id']);
		}

		$this->baseUrl->redirect($return);
	}

	protected function content(array $request = []): string
	{
		$uid     = $this->session->getLocalUserId();
		$contact = $this->getGroup($uid);
		$pcid    = $contact['id'];

		if (ActivityPub::isRequest()) {
			System::externalRedirect($contact['url']);
		}

		// Visitors can only see local groups, remote groups are shown on their own server
		if (!$uid) {
			if ($this->config->get('system', 'block_public') && !$this->session->isAuthenticated()) {
				return Login::form();
			}

			$owner = $this->getLocalOwner($pcid);
			if (empty($owner)) {
				System::externalRedirect(Contact::getProfileLink($contact));
			}

			if ($owner['hidewall'] && !$this->session->isAuthenticated()) {
				$this->baseUrl->redirect('profile/' . $owner['nickname'] . '/restricted');
			}
		}

		Nav::setSelected('groups');

		// Without a subscription the public posts of the group are shown read-only
		$readonly = !Contact::isSharing($pcid, $uid, true);

		$editor = '';
		if (!$readonly && !$contact['ap-posting-restricted']) {
			$this->statusEditor->registerAssets();
			$editor = $this->statusEditor->renderEditor([
				'group_cid'            => Contact::getUserContactId($pcid, $uid),
				'contact_account_type' => $contact['contact-type'],
			]);
		}

		if ($this->mode->isMobile()) {
			$itemsPerPage = $this->pConfig->get($uid, 'system', 'itemspage_mobile_network', $this->config->get('system', 'itemspage_network_mobile'));
		} else {
			$itemsPerPage = $this->pConfig->get($uid, 'system', 'itemspage_network', $this->config->get('system', 'itemspage_network'));
		}

		$pager = new Pager($this->l10n, $this->args->getQueryString(), $itemsPerPage);

		$groupStats = $this->groupManager->getGroupStats($uid, [$pcid])[$pcid] ?? ['threads' => 0, 'unread' => 0];
		$uriIds     = $this->groupManager->getThreadIds($uid, $pcid, $pager->getStart(), $pager->getItemsPerPage());

		$parents = [];

		$posts = Post::selectForUser(
			$uid,
			['uri-id', 'guid', 'title', 'body', 'author-id', 'author-name', 'author-link', 'author-updated', 'created'],
			DBA::mergeConditions(["(`uid` = 0 OR (`uid` = ? AND NOT `global`))", $uid], ['uri-id' => $uriIds]),
		);
		while ($post = Post::fetch($posts)) {
			$parents[$post['uri-id']] = $post;
		}
		$this->database->close($posts);

		$unseen = $this->groupManager->getUnseen($uid, $uriIds);
		$stats  = $this->groupManager->getThreadStats($uid, $uriIds, [Item::GRAVITY_COMMENT]);
		$latest = $this->groupManager->getLatestPosts($uid, $stats, [Item::GRAVITY_COMMENT], ['guid', 'title', 'body', 'author-id', 'author-name', 'author-link', 'author-updated', 'unseen']);

		$threads = [];
		foreach ($uriIds as $uriId) {
			if (empty($parents[$uriId])) {
				continue;
			}

			$parent = $parents[$uriId];

			$thread = [
				'guid'     => $parent['guid'],
				'thumb'    => Contact::getAvatarUrlForId($parent['author-id'], Proxy::SIZE_THUMB, $parent['author-updated']),
				'author'   => $parent['author-name'],
				'link'     => Contact::magicLink($parent['author-link']),
				'created'  => $this->l10n->relativeDateTime($parent['created']),
				'title'    => $parent['title'] ?: Plaintext::shorten(BBCode::toPlaintext($parent['body'], false), 200),
				'unseen'   => in_array($uriId, $unseen),
				'comments' => $stats[$uriId]['posts']  ?? 0,
				'unread'   => $stats[$uriId]['unread'] ?? 0,
				'latest'   => [],
			];

			if (!empty($latest[$uriId])) {
				$thread['latest'] = $this->groupManager->getPostSummary($latest[$uriId]);
			}

			$threads[] = $thread;
		}

		$tpl = Renderer::getMarkupTemplate('group.tpl');
		return Renderer::replaceMacros($tpl, [
			'$back'          => $this->t('Back'),
			'$back_link'     => $uid ? ($readonly ? 'groups/discover' : 'groups') : '',
			'$readonly'      => $readonly,
			'$join'          => $this->t('Join'),
			'$follow'        => $uid ? 'contact/follow?binurl=' . bin2hex((string) $contact['url']) : 'profile/' . $owner['nickname'] . '/remote_follow',
			'$register'      => !$uid && Register::getPolicy() === Register::OPEN ? $this->t('Register') : '',
			'$fetch'         => $uid && $this->canFetch($contact, $uid) ? $this->t('Fetch latest posts') : '',
			'$mark_seen'     => $this->t('Mark all as read'),
			'$editor'        => $editor,
			'$form_token'    => self::getFormSecurityToken('group'),
			'$title'         => $contact['name'],
			'$profile'       => Contact::magicLinkByContact($contact),
			'$thumb'         => Contact::getThumb($contact),
			'$about'         => BBCode::convertForUriId($contact['uri-id'], $contact['about'], BBCode::EXTERNAL),
			'$id'            => rawurlencode((string) $this->parameters['id']),
			'$threads_label' => $this->t('Threads'),
			'$threads_count' => $groupStats['threads'],
			'$unread_count'  => $groupStats['unread'],
			'$comments'      => $this->t('Comments'),
			'$unread'        => $this->t('Unread'),
			'$no_comments'   => $this->t('No comments yet'),
			'$no_threads'    => $this->t('There are no threads in this group.'),
			'$threads'       => $threads,
			'$paginate'      => $pager->renderFull($groupStats['threads']),
		]);
	}

	/**
	 * Fetching the outbox is only offered for groups that the user doesn't subscribe to,
	 * since it is done automatically on subscription.
	 *
	 * @param array $contact public contact of the group
	 * @param int   $uid     User id
	 *
	 * @return bool
	 */
	private function canFetch(array $contact, int $uid): bool
	{
		return in_array($contact['network'], [Protocol::ACTIVITYPUB, Protocol::DFRN]) && !Contact::isSharing($contact['id'], $uid, true);
	}

	/**
	 * The group can be addressed by the contact id, by its address or by the nickname of a local group
	 *
	 * @param int $uid User id
	 *
	 * @return array public contact of the group
	 * @throws NotFoundException
	 */
	private function getGroup(int $uid): array
	{
		if (is_numeric($this->parameters['id'])) {
			$cid = (int) $this->parameters['id'];
		} elseif ($user = User::getByNickname($this->parameters['id'], ['uid'])) {
			$cid = (int) Contact::getPublicIdByUserId($user['uid']);
		} else {
			$cid = Contact::getIdForURL($this->parameters['id'], 0, false);
		}

		$pcid = Contact::getPublicContactId($cid, $uid);
		if (!$pcid) {
			throw new NotFoundException($this->t('Contact not found.'));
		}

		$contact = Contact::getAccountById($pcid);
		if (empty($contact) || $contact['deleted'] || $contact['network'] === Protocol::PHANTOM || $contact['contact-type'] != Contact::TYPE_COMMUNITY) {
			throw new NotFoundException($this->t('Contact not found.'));
		}

		return $contact;
	}

	/**
	 * Fetches the local account of the group
	 *
	 * @param int $pcid Public contact id of the group
	 *
	 * @return array user record, empty for remote groups
	 * @throws \Exception
	 */
	private function getLocalOwner(int $pcid): array
	{
		$self = Contact::selectFirstAccountUser(['uid'], ['pid' => $pcid, 'self' => true]);
		if (empty($self['uid'])) {
			return [];
		}

		return $this->database->selectFirst('user', ['nickname', 'hidewall'], ['uid' => $self['uid'], 'verified' => true, 'blocked' => false, 'account_removed' => false, 'account_expired' => false]) ?: [];
	}
}
