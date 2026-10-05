<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

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
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Module;
use Friendica\Module\Response;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * List of the contacts that reposted, quoted, liked, ... a post
 */
class Interactions extends BaseModule
{
	public function __construct(
		private readonly IManageConfigValues $config,
		private readonly IHandleUserSessions $session,
		private readonly PostInteractions $postInteractions,
		private readonly ConversationRenderer $conversationRenderer,
		L10n $l10n,
		App\BaseURL $baseUrl,
		App\Arguments $args,
		LoggerInterface $logger,
		Profiler $profiler,
		Response $response,
		array $server,
		array $parameters = [],
	) {
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
	}

	protected function content(array $request = []): string
	{
		if ($this->config->get('system', 'block_public') && !$this->session->isAuthenticated()) {
			throw new HTTPException\UnauthorizedException($this->t('Access denied.'));
		}

		$uid  = $this->session->getLocalUserId();
		$type = $this->parameters['type'] ?? '';

		if (!$this->postInteractions->isAvailable($type, $uid)) {
			throw new HTTPException\NotFoundException($this->t('Page not found.'));
		}

		$item = $this->fetchItem($this->parameters['guid'] ?? '', $uid);
		if (empty($item)) {
			throw new HTTPException\NotFoundException($this->t('Item not found.'));
		}

		$total = $this->postInteractions->count($type, $item['uri-id'], $uid);
		$pager = new Pager($this->l10n, $this->args->getQueryString(), 50);

		if ($type === PostInteractions::QUOTES) {
			return $this->renderQuotes($item['uri-id'], $uid, $pager, $total);
		}

		$contacts = [];
		foreach ($this->postInteractions->getActorIds($type, $item['uri-id'], $uid, $pager->getStart(), $pager->getItemsPerPage()) as $actorId => $emoji) {
			$public = Contact::getById($actorId, ['uri-id']);
			if (empty($public)) {
				continue;
			}

			$contact = Contact::selectFirst([], ['uri-id' => $public['uri-id'], 'uid' => [0, $uid]], ['order' => ['uid' => 'DESC']]);
			if (!empty($contact)) {
				$contacts[] = Module\Contact::getContactTemplateVars($contact) + ['emoji' => $emoji];
			}
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
	 * Show the quoting posts themselves, not only their authors
	 */
	private function renderQuotes(int $uriId, int $uid, Pager $pager, int $total): string
	{
		$uriIds = $this->postInteractions->getQuoteUriIds($uriId, $pager->getStart(), $pager->getItemsPerPage());
		$items  = $uriIds ? Post::toArray(Post::selectForUser($uid, Item::DISPLAY_FIELDLIST, ['uri-id' => $uriIds], ['order' => ['uri-id' => true]])) : [];

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
	 * @see Display::content()
	 */
	private function fetchItem(string $guid, int $uid): ?array
	{
		$fields = ['uri-id', 'uid', 'private'];

		if ($uid) {
			$item = Post::selectFirstForUser($uid, $fields, ['guid' => $guid, 'uid' => $uid]);
			if (!empty($item)) {
				return $item;
			}
		}

		if ($this->session->getRemoteUserId()) {
			$item = Post::selectFirst($fields, ['guid' => $guid, 'private' => Item::PRIVATE, 'origin' => true]);
			if (!empty($item) && Contact::isFollower($this->session->getRemoteUserId(), $item['uid'])) {
				return $item;
			}
		}

		return Post::selectFirstForUser($uid, $fields, ['guid' => $guid, 'private' => [Item::PUBLIC, Item::UNLISTED], 'uid' => 0]) ?: null;
	}
}
