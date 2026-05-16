<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Item;

use Friendica\App;
use Friendica\AppHelper;
use Friendica\BaseModule;
use Friendica\Content\Conversation;
use Friendica\Content\Item as ContentItem;
use Friendica\Content\Text\BBCode;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\Profile;
use Friendica\Model\User;
use Friendica\Module\Response;
use Friendica\Module\Special\DisplayNotFound;
use Friendica\Navigation\Notifications\Repository\Notification;
use Friendica\Navigation\Notifications\Repository\Notify;
use Friendica\Protocol\ActivityPub;
use Friendica\Util\Profiler;
use Friendica\Network\HTTPException;
use Friendica\Content\Widget;
use Friendica\Core\System;
use Friendica\DI;
use Psr\Log\LoggerInterface;

/**
 * Controller to display one item and its conversation
 */
class Display extends BaseModule
{
	/** @var App\Page */
	protected $page;
	/** @var IManageConfigValues */
	protected $config;
	/** @var IManagePersonalConfigValues */
	protected $pConfig;
	/** @var IHandleUserSessions */
	protected $session;
	/** @var AppHelper */
	protected $appHelper;
	/** @var ContentItem */
	protected $contentItem;
	/** @var Conversation */
	protected $conversation;
	/** @var Notification */
	protected $notification;
	/** @var Notify */
	protected $notify;

	public function __construct(L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, IManageConfigValues $config, IManagePersonalConfigValues $pConfig, IHandleUserSessions $session, AppHelper $appHelper, App\Page $page, ContentItem $contentItem, Conversation $conversation, Notification $notification, Notify $notify, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->page         = $page;
		$this->config       = $config;
		$this->pConfig      = $pConfig;
		$this->session      = $session;
		$this->appHelper    = $appHelper;
		$this->contentItem  = $contentItem;
		$this->conversation = $conversation;
		$this->notification = $notification;
		$this->notify       = $notify;
	}

	protected function content(array $request = []): string
	{
		if (ActivityPub::isRequest()) {
			$this->baseUrl->redirect(str_replace('display/', 'objects/', $this->args->getQueryString()));
		}

		if ($this->config->get('system', 'block_public') && !$this->session->isAuthenticated()) {
			throw new HTTPException\UnauthorizedException($this->t('Access denied.'));
		}

		$guid = $this->parameters['guid'] ?? 0;

		$item    = null;
		$itemUid = $this->session->getLocalUserId();

		$fields = ['id', 'uri-id', 'parent-uri-id', 'author-id', 'author-link', 'contact-id', 'contact-contact-type', 'body', 'uid', 'guid', 'gravity',
			'plink', 'origin', 'uri', 'post-reason', 'owner-contact-type', 'owner-network', 'owner-id', 'guid',
			'author-network', 'author-alias', 'private', 'network'];

		// Does the local user have this item?
		if ($this->session->getLocalUserId()) {
			$item = Post::selectFirstForUser($this->session->getLocalUserId(), $fields, [
				'guid' => $guid,
				'uid'  => $this->session->getLocalUserId(),
			]);
		}

		// Is this item private but could be visible to the remove visitor?
		if (empty($item) && $this->session->getRemoteUserId()) {
			$item = Post::selectFirst($fields, ['guid' => $guid, 'private' => Item::PRIVATE, 'origin' => true]);
			if (!empty($item)) {
				if (!Contact::isFollower($this->session->getRemoteUserId(), $item['uid'])) {
					$item = null;
				} else {
					$itemUid = $item['uid'];
				}
			}
		}

		// Is it an item with uid = 0?
		if (empty($item)) {
			$item = Post::selectFirstForUser($this->session->getLocalUserId(), $fields, [
				'guid'    => $guid,
				'private' => [Item::PUBLIC, Item::UNLISTED],
				'uid'     => 0,
			]);
		}

		if (empty($item)) {
			$this->page['aside'] = '';
			$displayNotFound     = new DisplayNotFound($this->l10n, $this->baseUrl, $this->args, $this->logger, $this->profiler, $this->response, $this->server, $this->parameters);
			return $displayNotFound->content();
		}

		$plink = Item::getPlink($item);

		if (!$this->session->getLocalUserId() && isset($plink['href']) && !DI::baseUrl()->isLocalUrl($plink['href'])) {
			System::externalRedirect($plink['href']);
		}

		if ($item['gravity'] != Item::GRAVITY_PARENT) {
			$parent = Post::selectFirst($fields, [
				'uid'    => [0, $itemUid],
				'uri-id' => $item['parent-uri-id'],
			], ['order' => ['uid' => true]]);

			$item = $parent ?: $item;
		}

		if (!$this->pConfig->get($this->session->getLocalUserId(), 'system', 'detailed_notif')) {
			$this->notification->setAllSeenForUser($this->session->getLocalUserId(), ['parent-uri-id' => $item['parent-uri-id']]);
			$this->notify->setAllSeenForUser($this->session->getLocalUserId(), ['parent-uri-id' => $item['parent-uri-id']]);
		}

		if ($this->session->getLocalUserId() != 0) {
			$this->contentItem->setViewed($item['uri-id'], $this->session->getLocalUserId());
		}

		$this->displaySidebar($item);
		$this->displayHead($item['uri-id'], $item['parent-uri-id']);

		$output = '';

		// add the uri-id to the update_display parameter
		if ($this->session->getLocalUserId()) {
			$output .= "<script> var netargs = '?uri_id=" . $item['uri-id'] . "'; </script>";
		}

		$output .= $this->getDisplayData($item);

		$author              = Contact::getByURLForUser($item['author-link'], $this->session->getLocalUserId());
		$this->page['title'] = $this->l10n->t("Post by %s", $author['name']);

		return $output;
	}

	/**
	 * Loads the content for the sidebar of the display page
	 *
	 * @param array $item The current item
	 *
	 * @return void
	 * @throws HTTPException\InternalServerErrorException
	 * @throws HTTPException\NotFoundException
	 * @throws \ImagickException
	 */
	protected function displaySidebar(array $item)
	{
		$author = [];
		$shared = $this->contentItem->getSharedPost($item, ['author-link']);
		if (array_key_exists('comment', $shared) && strval($shared['comment']) === '') {
			$author = Contact::getByURLForUser($shared['post']['author-link'], $this->session->getLocalUserId());
		}

		if ($author === []) {
			if ($item['contact-contact-type'] == Contact::TYPE_COMMUNITY) {
				$author = Contact::getById($item['contact-id']);
			} else {
				$author = Contact::getById($item['author-id']);
			}
		}

		if ($this->baseUrl->isLocalUrl($author['url'])) {
			Profile::load($this->appHelper, $author['nick'], false);
		} else {
			$this->page['aside'] = Widget\VCard::getHTML($author);
		}

		$this->appHelper->setProfileOwner($item['uid']);
	}

	protected function getDisplayData(array $item, bool $update = false, int $updateUid = 0, bool $force = false): string
	{
		$itemUid = $this->session->getLocalUserId();

		$parent = null;
		if (!$this->session->getLocalUserId() && !empty($item['parent-uri-id'])) {
			$parent = Post::selectFirst(['uid'], ['uri-id' => $item['parent-uri-id'], 'wall' => true]);
		}

		if (!empty($parent)) {
			$pageUid = $parent['uid'];
			if ($this->session->getRemoteContactID($pageUid)) {
				$itemUid = $parent['uid'];
			}
		} else {
			$pageUid = $item['uid'];
		}

		if (!empty($pageUid) && ($pageUid != $this->session->getLocalUserId())) {
			$page_user = User::getById($pageUid, ['nickname', 'hidewall']);
		}

		if (!empty($page_user['hidewall']) && !$this->session->isAuthenticated()) {
			$this->baseUrl->redirect('profile/' . $page_user['nickname'] . '/restricted');
		}

		$sql_extra = Item::getPermissionsSQLByUserId($pageUid);

		if ($this->session->getLocalUserId() && ($this->session->getLocalUserId() == $pageUid)) {
			$unseen = Post::exists([
				'parent-uri-id' => $item['parent-uri-id'],
				'uid'           => $this->session->getLocalUserId(),
				'unseen'        => true,
			]);
		} else {
			$unseen = false;
		}

		if ($update && !$unseen && !$force) {
			return '';
		}

		$condition = ["`uri-id` = ? AND `uid` IN (0, ?) " . $sql_extra, $item['uri-id'], $itemUid];
		$fields    = [
			'parent-uri-id', 'body', 'title', 'author-name', 'author-avatar', 'plink', 'author-id',
			'owner-id', 'contact-id',
		];

		$item = Post::selectFirstForUser($pageUid, $fields, $condition);

		if (empty($item)) {
			$this->page['aside'] = '';
			$displayNotFound     = new DisplayNotFound($this->l10n, $this->baseUrl, $this->args, $this->logger, $this->profiler, $this->response, $this->server, $this->parameters);
			return $displayNotFound->content();
		}

		$item['uri-id'] = $item['parent-uri-id'];

		if ($unseen) {
			$condition = [
				'parent-uri-id' => $item['parent-uri-id'],
				'uid'           => $this->session->getLocalUserId(),
				'unseen'        => true,
			];
			Item::update(['unseen' => false], $condition);
		}

		$this->addMetaTags($item);

		$output = '';

		$is_owner = $this->session->getLocalUserId() && (in_array($pageUid, [$this->session->getLocalUserId(), 0]));

		// We need the editor here to be able to reshare an item.
		if ($is_owner && !$update) {
			$output .= $this->conversation->statusEditor([], 0, true);
		}

		$output .= $this->conversation->render([$item], Conversation::MODE_DISPLAY, $updateUid, false, 'commented', $itemUid);

		return $output;
	}

	// We are displaying an "alternate" link if that post was public. See issue 2864
	protected function displayHead(string $uriId, string $parentUriId)
	{
		if (Post::exists(['uri-id' => $uriId, 'private' => [Item::PUBLIC, Item::UNLISTED]])) {
			// For the atom feed the nickname doesn't matter at all, we only need the item id.
			$this->page['htmlhead'] .= Renderer::replaceMacros(Renderer::getMarkupTemplate('display-head.tpl'), [
				'$alternate'    => sprintf('display/feed-item/%s.atom', $uriId),
				'$conversation' => sprintf('display/feed-item/%s/conversation.atom', $parentUriId),
			]);
		}
	}

	/**
	 * Adds <meta> tags to the HTML output based on an item
	 *
	 * @param array $item The item with the information for the <meta> tags
	 *
	 * @return void
	 * @throws \Exception
	 */
	protected function addMetaTags(array $item)
	{
		// Preparing the meta header
		$description = trim(BBCode::toPlaintext($item['body']));
		$title       = trim(BBCode::toPlaintext($item['title'] ?? ''));
		$author_name = $item['author-name'];

		$image = $this->baseUrl->remove($item['author-avatar']);

		if ($title === '') {
			$title = $author_name;
		}

		// Limit the description to 160 characters
		if (strlen($description) > 160) {
			$description = substr($description, 0, 157) . '...';
		}

		$description = htmlspecialchars($description, ENT_COMPAT, 'UTF-8', true); // allow double encoding here
		$title       = htmlspecialchars($title, ENT_COMPAT, 'UTF-8', true); // allow double encoding here
		$author_name = htmlspecialchars($author_name, ENT_COMPAT, 'UTF-8', true); // allow double encoding here

		$page = $this->page;

		if (Contact::exists([
			'unsearchable' => true, 'id' => [$item['contact-id'], $item['author-id'], $item['owner-id']],
		])) {
			$page['htmlhead'] .= "<meta content=\"noindex, noarchive\" name=\"robots\" />\n";
		}

		$page['htmlhead'] .= sprintf("<meta name=\"author\" content=\"%s\" />\n", $author_name);
		$page['htmlhead'] .= sprintf("<meta name=\"title\" content=\"%s\" />\n", $title);
		$page['htmlhead'] .= sprintf("<meta name=\"fulltitle\" content=\"%s\" />\n", $title);
		$page['htmlhead'] .= sprintf("<meta name=\"description\" content=\"%s\" />\n", $description);

		// Schema.org microdata
		$page['htmlhead'] .= sprintf("<meta itemprop=\"name\" content=\"%s\" />\n", $title);
		$page['htmlhead'] .= sprintf("<meta itemprop=\"description\" content=\"%s\" />\n", $description);
		$page['htmlhead'] .= sprintf("<meta itemprop=\"image\" content=\"%s\" />\n", $image);
		$page['htmlhead'] .= sprintf("<meta itemprop=\"author\" content=\"%s\" />\n", $author_name);

		// Twitter cards
		$page['htmlhead'] .= "<meta name=\"twitter:card\" content=\"summary\" />\n";
		$page['htmlhead'] .= sprintf("<meta name=\"twitter:title\" content=\"%s\" />\n", $title);
		$page['htmlhead'] .= sprintf("<meta name=\"twitter:description\" content=\"%s\" />\n", $description);
		$page['htmlhead'] .= sprintf("<meta name=\"twitter:image\" content=\"%s/%s\" />\n", $this->baseUrl, $image);
		$page['htmlhead'] .= sprintf("<meta name=\"twitter:url\" content=\"%s\" />\n", $item["plink"]);

		// Dublin Core
		$page['htmlhead'] .= sprintf("<meta name=\"DC.title\" content=\"%s\" />\n", $title);
		$page['htmlhead'] .= sprintf("<meta name=\"DC.description\" content=\"%s\" />\n", $description);

		// Open Graph
		$page['htmlhead'] .= "<meta property=\"og:type\" content=\"website\" />\n";
		$page['htmlhead'] .= sprintf("<meta property=\"og:title\" content=\"%s\" />\n", $title);
		$page['htmlhead'] .= sprintf("<meta property=\"og:image\" content=\"%s/%s\" />\n", $this->baseUrl, $image);
		$page['htmlhead'] .= sprintf("<meta property=\"og:url\" content=\"%s\" />\n", $item["plink"]);
		$page['htmlhead'] .= sprintf("<meta property=\"og:description\" content=\"%s\" />\n", $description);
		$page['htmlhead'] .= sprintf("<meta name=\"og:article:author\" content=\"%s\" />\n", $author_name);
		// article:tag
	}
}
