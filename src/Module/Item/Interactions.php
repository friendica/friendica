<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Module\Item;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Content\Conversation\ConversationRenderer;
use Friendica\Content\Conversation\PostInteractions;
use Friendica\Content\Pager;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Module;
use Friendica\Module\Response;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * List of the contacts that reposted, quoted, liked, ... a post
 */
class Interactions extends BaseModule
{
	public function __construct(
		private readonly IManageConfigValues $config,
		private readonly IHandleUserSessions $session,
		private readonly App\Page $page,
		private readonly PostInteractions $postInteractions,
		private readonly ConversationRenderer $conversationRenderer,
		EventDispatcherInterface $eventDispatcher,
		L10n $l10n,
		App\BaseURL $baseUrl,
		App\Arguments $args,
		LoggerInterface $logger,
		Profiler $profiler,
		Response $response,
		array $server,
		array $parameters = [],
	) {
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters, $eventDispatcher);
	}

	protected function content(array $request = []): string
	{
		if ($this->config->get('system', 'block_public') && !$this->session->isAuthenticated()) {
			throw new HTTPException\UnauthorizedException($this->t('Access denied.'));
		}

		$uid  = (int) $this->session->getLocalUserId();
		$type = $this->parameters['type'] ?? '';

		if (!$this->postInteractions->isAvailable($type, $uid)) {
			throw new HTTPException\NotFoundException($this->t('Page not found.'));
		}

		$item = $this->fetchItem($this->parameters['guid'] ?? '', $uid);
		if (empty($item)) {
			throw new HTTPException\NotFoundException($this->t('Item not found.'));
		}

		$plink = Item::getPlink($item);
		if (!$uid && isset($plink['href']) && !$this->baseUrl->isLocalUrl($plink['href'])) {
			System::externalRedirect($plink['href']);
		}

		// The interactions of a private post of a local user are stored with the uid of that user
		$itemUid = $item['view-uid'];

		$this->page['title'] = $this->postInteractions->getTitle($type);

		$total = $this->postInteractions->count($type, $item['uri-id'], $itemUid);
		$pager = new Pager($this->l10n, $this->args->getQueryString(), 50);

		if ($type === PostInteractions::QUOTES) {
			return $this->renderQuotes($item['uri-id'], $itemUid, $uid, $pager, $total);
		}

		$actors   = $this->postInteractions->getActorIds($type, $item['uri-id'], $itemUid, $pager->getStart(), $pager->getItemsPerPage());
		$contacts = [];
		foreach ($this->getContacts(array_keys($actors), $uid) as $actorId => $contact) {
			$contacts[] = Module\Contact::getContactTemplateVars($contact) + ['emoji' => $actors[$actorId]];
		}

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('profile/contacts.tpl'), [
			'$title'          => $this->postInteractions->getTitle($type),
			'$desc'           => '',
			'$tabs'           => [],
			'$noresult_label' => $this->t('No contacts.'),
			'$contacts'       => $contacts,
			'$paginate'       => $pager->renderFull($total),
		]);
	}

	/**
	 * Fetch the contact entries that are shown to the viewer (the personal contact, if there is one)
	 *
	 * @param int[] $publicIds Public contact ids
	 * @return array<int, array> Contact by public contact id, in the order of the given ids
	 */
	private function getContacts(array $publicIds, int $uid): array
	{
		if (empty($publicIds)) {
			return [];
		}

		$uriIds = [];
		foreach (Contact::selectToArray(['id', 'uri-id'], ['id' => $publicIds]) as $public) {
			$uriIds[$public['id']] = $public['uri-id'];
		}

		$byUriId = [];
		foreach (Contact::selectToArray([], ['uri-id' => array_values($uriIds), 'uid' => [0, $uid]], ['order' => ['uid' => true]]) as $contact) {
			$byUriId[$contact['uri-id']] ??= $contact;
		}

		$contacts = [];
		foreach ($publicIds as $publicId) {
			if (isset($uriIds[$publicId], $byUriId[$uriIds[$publicId]])) {
				$contacts[$publicId] = $byUriId[$uriIds[$publicId]];
			}
		}

		return $contacts;
	}

	/**
	 * Show the quoting posts themselves, not only their authors
	 */
	private function renderQuotes(int $uriId, int $itemUid, int $uid, Pager $pager, int $total): string
	{
		$uriIds = $this->postInteractions->getQuoteUriIds($uriId, $itemUid, $pager->getStart(), $pager->getItemsPerPage());
		$items  = [];
		if ($uriIds) {
			// Only the posts of the public timeline and the ones of the viewer, not the copies of other users
			$condition = DBA::mergeConditions(["(`uid` = ? OR (`uid` = ? AND NOT `global`))", 0, $uid], ['uri-id' => $uriIds]);
			$items     = Post::toArray(Post::selectForUser($uid, Item::DISPLAY_FIELDLIST, $condition, ['order' => ['received' => true]]));
		}

		$o = Renderer::replaceMacros(Renderer::getMarkupTemplate('section_title.tpl'), [
			'$title' => $this->postInteractions->getTitle(PostInteractions::QUOTES),
		]);

		if (empty($items)) {
			return $o . '<div class="alert alert-info" role="alert">' . $this->t('No posts.') . '</div>';
		}

		return $o
			. $this->conversationRenderer->renderFlat($items, ConversationRenderer::MODE_SEARCH, false, $uid)
			. $pager->renderFull($total);
	}

	/**
	 * Fetch the item with the same visibility rules as the display page
	 *
	 * The result contains the user id under which the interactions of the item are stored as "view-uid".
	 *
	 * @see Display::content()
	 */
	private function fetchItem(string $guid, int $uid): ?array
	{
		$fields = ['uri-id', 'uid', 'private', 'guid', 'plink', 'uri', 'network', 'author-id', 'author-link', 'author-network', 'author-alias', 'owner-id', 'owner-contact-type', 'owner-network'];

		if ($uid) {
			$item = Post::selectFirstForUser($uid, $fields, ['guid' => $guid, 'uid' => $uid]);
			if (!empty($item)) {
				return $item + ['view-uid' => $uid];
			}
		}

		if ($this->session->getRemoteUserId()) {
			$item = Post::selectFirst($fields, ['guid' => $guid, 'private' => Item::PRIVATE, 'origin' => true]);
			if (!empty($item) && Contact::isFollower($this->session->getRemoteUserId(), $item['uid'])) {
				return $item + ['view-uid' => $item['uid']];
			}
		}

		$item = Post::selectFirstForUser($uid, $fields, ['guid' => $guid, 'private' => [Item::PUBLIC, Item::UNLISTED], 'uid' => 0]);

		return $item ? $item + ['view-uid' => $uid] : null;
	}
}
