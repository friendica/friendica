<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Content\Conversation;

use Friendica\Core\L10n;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Event\ArrayFilterEvent;
use Friendica\Model\Item as ItemModel;
use Friendica\Util\Profiler;
use ImagickException;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Facade class for conversation rendering.
 * This is the main entry point for rendering conversations and threads.
 * It delegates to ConversationDataProvider and PostTemplateBuilder for the actual work.
 */
final readonly class ConversationRenderer
{
	public const MODE_CHANNEL       = 'channel';
	public const MODE_COMMENTS      = 'comments';
	public const MODE_COMMUNITY     = 'community';
	public const MODE_CONTACTS      = 'contact';
	public const MODE_CONTACT_POSTS = 'contact-posts';
	public const MODE_DISPLAY       = 'display';
	public const MODE_FILED         = 'filed';
	public const MODE_NETWORK       = 'network';
	public const MODE_NOTES         = 'notes';
	public const MODE_SEARCH        = 'search';
	public const MODE_PROFILE       = 'profile';

	public const ORDER_COMMENTED        = 'commented';
	public const ORDER_RECEIVED         = 'received';
	public const ORDER_CREATED          = 'created';
	public const ORDER_PINNED_COMMENTED = 'pinned_commented';
	public const ORDER_PINNED_RECEIVED  = 'pinned_received';
	public const ORDER_PINNED_CREATED   = 'pinned_created';

	public const COMMENTS_MODE_ALL     = 0;
	public const COMMENTS_MODE_COMPACT = 1;
	public const COMMENTS_MODE_HIDDEN  = 2;

	/**
	 * Map a stored comments mode to one of the COMMENTS_MODE_* constants.
	 *
	 * @param mixed $mode The stored value
	 * @return int
	 */
	public static function normalizeCommentsMode(mixed $mode): int
	{
		$mode = (int) $mode;

		return in_array($mode, [self::COMMENTS_MODE_COMPACT, self::COMMENTS_MODE_HIDDEN], true) ? $mode : self::COMMENTS_MODE_ALL;
	}

	public function __construct(
		private L10n $l10n,
		private IManagePersonalConfigValues $pConfig,
		private EventDispatcherInterface $eventDispatcher,
		private IHandleUserSessions $session,
		private ConversationDataProvider $dataProvider,
		private Profiler $profiler,
		private \Friendica\App\Arguments $args,
		private StatusEditor $statusEditor,
		private PostInteractions $postInteractions,
	) {}

	/**
	 * Render a conversation or list of items for HTML display with threaded structure.
	 * This is the main entry point for rendering, delegating to specific render methods.
	 *
	 * @param array $items An array of Posts
	 * @param string $mode One of self::MODE_*
	 * @param bool $update Asynchronous update rendering
	 * @param string $order One of self::ORDER_*
	 * @param int $uid
	 * @param array $request
	 * @return string
	 * @throws ImagickException
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function renderThreaded(array $items, string $mode, bool $update = false, string $order = self::ORDER_COMMENTED, int $uid = 0, array $request = []): string
	{
		$this->profiler->startRecording('rendering');
		$this->statusEditor->registerAssets();

		$viewerUid = $this->resolveViewerUid($uid);

		$live_update_div = $this->getLiveUpdateHtml($mode, $update, $viewerUid, $request);

		if (!$update) {
			$_SESSION['return_path'] = $this->args->getQueryString();
		}

		$cb = ['items' => $items, 'mode' => $mode, 'update' => $update, 'preview' => false];

		$cb = $this->eventDispatcher->dispatch(
			new ArrayFilterEvent(ArrayFilterEvent::CONVERSATION_START, $cb),
		)->getArray();

		$items = $cb['items'];

		$timelineHtml = $this->renderTimelineByItems($items, $viewerUid, $mode, $order, $update);
		$html         = $live_update_div . $timelineHtml;

		if (!$update) {
			$html .= "<div id=\"conversation-end\"></div>\n\n";
		}

		$this->profiler->stopRecording();
		return $html;
	}

	/**
	 * Render all comments for the thread identified by the given uri-id.
	 *
	 * @param int $uriId The URI ID of the thread
	 * @param int $uid The user ID of the viewer, or null for public view
	 * @param array $existing Existing comment URI IDs to exclude
	 * @param bool $displayPage Whether the request comes from a display page, where clicking a post must not open it again
	 * @return string The rendered HTML of all comments
	 * @throws ImagickException
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function renderCommentsByUriId(int $uriId, int $uid, array $existing = [], bool $displayPage = false): string
	{
		$this->profiler->startRecording('rendering');
		$this->statusEditor->registerAssets();

		$viewerUid = $this->resolveViewerUid($uid);

		$item = $this->dataProvider->fetchItemByUriId($uriId, $viewerUid);
		if (empty($item)) {
			$this->profiler->stopRecording();
			return '';
		}

		$page_dropping = $viewerUid && $this->pConfig->get($viewerUid, 'system', 'show_page_drop', true);
		$root          = $this->dataProvider->getRootTemplateDataFromItem($item, $viewerUid, self::MODE_COMMENTS, $existing, $page_dropping);
		if (empty($root['children'])) {
			$this->profiler->stopRecording();
			return '';
		}

		$clickToDisplay = !$displayPage && $this->isClickToDisplayEnabled($viewerUid);

		$html = '';
		foreach ($root['children'] as $child) {
			// wall_thread.tpl contains a different treating for MODE_DISPLAY that we use here
			$html .= $this->renderItemHtml($child, self::MODE_DISPLAY, $clickToDisplay);
		}

		$this->profiler->stopRecording();
		return $html;
	}

	/**
	 * Render a freshly posted reply together with the subtree of its immediate
	 * parent comment, so the client can replace that one subtree instead of
	 * rebuilding the whole thread (which drops loaded and among-strangers
	 * comments in the compact timeline).
	 *
	 * For a reply to the thread starter the thread root is rendered with the new
	 * reply as its only child, so the client can pick the reply out of it.
	 *
	 * Returns an empty string for anything that should go through the regular
	 * thread update: a missing item, a non-comment, or a reply that isn't part
	 * of the rendered thread.
	 *
	 * @param int $commentUriId The URI ID of the freshly posted comment
	 * @param int $uid The user ID of the viewer, or null for public view
	 * @param bool $displayPage Whether the request comes from a display page, where clicking a post must not open it again
	 * @return string The rendered HTML of the immediate parent's subtree, or of the thread root for a reply to the thread starter
	 * @throws ImagickException
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function renderCommentByUriId(int $commentUriId, int $uid, bool $displayPage = false): string
	{
		$this->profiler->startRecording('rendering');
		$this->statusEditor->registerAssets();

		$viewerUid = $this->resolveViewerUid($uid);

		$comment = $this->dataProvider->fetchItemByUriId($commentUriId, $viewerUid);
		if (empty($comment) || ($comment['gravity'] !== ItemModel::GRAVITY_COMMENT)) {
			$this->profiler->stopRecording();
			return '';
		}

		// Match the surrounding conversation: compact/hidden timelines never flatten,
		// so a flattened subtree would move the new reply out of its parent.
		$smartThreading = self::normalizeCommentsMode($this->pConfig->get($viewerUid, 'system', 'compact_timeline')) === self::COMMENTS_MODE_ALL;

		$page_dropping = $viewerUid && $this->pConfig->get($viewerUid, 'system', 'show_page_drop', true);
		$root          = $this->dataProvider->getRootTemplateDataFromItem($comment, $viewerUid, self::MODE_DISPLAY, [], $page_dropping, $smartThreading);

		$parent = $root ? $this->selectInsertNode($root, $comment) : null;

		$html = $parent ? $this->renderItemHtml($parent, self::MODE_DISPLAY, !$displayPage && $this->isClickToDisplayEnabled($viewerUid)) : '';

		$this->profiler->stopRecording();

		return $html;
	}

	/**
	 * Render a single conversation item (post or comment) without its replies,
	 * so the client can refresh it in place after an activity (like, dislike,
	 * announce, attendance) instead of rebuilding (and thereby collapsing) the
	 * whole thread.
	 *
	 * @param int $uriId The URI ID of the item to render
	 * @param int $uid The user ID of the viewer, or null for public view
	 * @param bool $displayPage Whether the request comes from a display page, where clicking a post must not open it again
	 * @return string The rendered HTML of the single item node
	 * @throws ImagickException
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function renderItemByUriId(int $uriId, int $uid, bool $displayPage = false): string
	{
		$this->profiler->startRecording('rendering');
		$this->statusEditor->registerAssets();

		$viewerUid = $this->resolveViewerUid($uid);

		$item = $this->dataProvider->fetchItemByUriId($uriId, $viewerUid);
		if (empty($item)) {
			$this->profiler->stopRecording();
			return '';
		}

		$smartThreading = self::normalizeCommentsMode($this->pConfig->get($viewerUid, 'system', 'compact_timeline')) === self::COMMENTS_MODE_ALL;

		$page_dropping = $viewerUid && $this->pConfig->get($viewerUid, 'system', 'show_page_drop', true);
		$root          = $this->dataProvider->getRootTemplateDataFromItem($item, $viewerUid, self::MODE_DISPLAY, [], $page_dropping, $smartThreading);

		$node = $root ? $this->findNodeByUriId($root, $uriId) : null;
		if ($node !== null) {
			$node['children'] = [];
			$this->addInteractions($node, $uriId, $viewerUid);
		}

		$html = $node !== null ? $this->renderItemHtml($node, self::MODE_DISPLAY, !$displayPage && $this->isClickToDisplayEnabled($viewerUid)) : '';

		$this->profiler->stopRecording();

		return $html;
	}

	/**
	 * Select the node to render for a freshly posted comment.
	 *
	 * A reply to the thread starter has no parent subtree to swap, so it is
	 * isolated below the root. Any other reply is rendered as its parent's subtree.
	 *
	 * @param array $root The thread root
	 * @param array $comment The freshly posted comment
	 * @return array|null The node to render, or null when it isn't in the tree
	 */
	private function selectInsertNode(array $root, array $comment): ?array
	{
		if ($comment['thr-parent-id'] == $comment['parent-uri-id']) {
			return $this->isolateReply($root, (int) $comment['uri-id']);
		}

		return $this->findNodeByUriId($root, (int) $comment['thr-parent-id']);
	}

	/**
	 * Reduce a thread to its root and one reply, which is stripped of its own replies.
	 *
	 * @param array $root The thread root
	 * @param int $uriId The URI ID of the reply to keep
	 * @return array|null The reduced thread root, or null when the reply isn't in the tree
	 */
	private function isolateReply(array $root, int $uriId): ?array
	{
		$node = $this->findNodeByUriId($root, $uriId);
		if ($node === null) {
			return null;
		}

		$node['children'] = [];
		$root['children'] = [$node];

		return $root;
	}

	/**
	 * Add the interaction summary (reshares, quotes, likes, ...) to the nodes that should show it.
	 *
	 * @param array $node The node to start from (thread root or any child)
	 * @param int $targetUriId The URI ID of the highlighted post
	 * @param int $viewerUid The user ID of the viewer
	 */
	private function addInteractions(array &$node, int $targetUriId, int $viewerUid): void
	{
		if ($this->showInteractions($node, $targetUriId, $viewerUid)) {
			$node['interactions'] = $this->postInteractions->getSummary((int) $node['uriid'], (string) $node['guid'], $viewerUid);
		}

		foreach ($node['children'] ?? [] as $index => $child) {
			$this->addInteractions($node['children'][$index], $targetUriId, $viewerUid);
		}
	}

	/**
	 * Decide for a single node whether it shows the interaction summary.
	 * This is the only place to extend for showing it on more posts, e.g. with a user setting.
	 */
	private function showInteractions(array $node, int $targetUriId, int $viewerUid): bool
	{
		return (int) ($node['uriid'] ?? 0) === $targetUriId;
	}

	/**
	 * Recursively look for a thread node by its URI ID.
	 *
	 * @param array $node The node to start from (thread root or any child)
	 * @param int $uriId The URI ID to look for
	 * @return array|null The matching node, or null when it isn't in the tree
	 */
	private function findNodeByUriId(array $node, int $uriId): ?array
	{
		if ((int) ($node['uriid'] ?? 0) === $uriId) {
			return $node;
		}

		foreach ($node['children'] ?? [] as $child) {
			$found = $this->findNodeByUriId($child, $uriId);
			if ($found !== null) {
				return $found;
			}
		}

		return null;
	}

	/**
	 * Render the complete thread for the given item array.
	 * This avoids loading the item from the database when it's already available.
	 *
	 * @param array $item The item array
	 * @param bool $update Whether this is an update
	 * @param int $uid The user ID of the viewer, or null for public view
	 * @param string $mode The rendering mode (e.g., self::MODE_DISPLAY)
	 * @param int $highlightUriId The URI ID of the post that was requested in the thread (display mode only), 0 for the thread root
	 * @return string The rendered HTML of the complete thread
	 * @throws ImagickException
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function renderThreadByItem(array $item, bool $update, int $uid, string $mode, int $highlightUriId = 0): string
	{
		$this->profiler->startRecording('rendering');
		$this->statusEditor->registerAssets();

		$viewerUid = $this->resolveViewerUid($uid);

		$live_update_div = $this->getLiveUpdateHtml($mode, $update, $viewerUid, []);

		$page_dropping = $viewerUid && $this->pConfig->get($viewerUid, 'system', 'show_page_drop', true);
		$root          = $this->dataProvider->getRootTemplateDataFromItem($item, $viewerUid, $mode, [], $page_dropping);
		if (empty($root)) {
			$this->profiler->stopRecording();
			return '';
		}

		if ($mode === self::MODE_DISPLAY) {
			$targetUriId = $highlightUriId && $this->findNodeByUriId($root, $highlightUriId) !== null ? $highlightUriId : (int) $root['uriid'];
			$this->addInteractions($root, $targetUriId, $viewerUid);
		}

		$html = $this->renderThreadedTemplate([$root], $mode, $update, $page_dropping, $mode !== self::MODE_DISPLAY && $this->isClickToDisplayEnabled($viewerUid));
		$this->profiler->stopRecording();
		return $live_update_div . $html;
	}

	/**
	 * Render a flat list of items (without threading).
	 *
	 * @param array $items An array of Posts
	 * @param string $mode One of self::MODE_*
	 * @param bool $preview Post preview (no actual database record)
	 * @param int $uid The user ID of the viewer
	 * @param array $request The request parameters, used for the automatic update of the search
	 * @return string The rendered HTML
	 * @throws ImagickException
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function renderFlat(array $items, string $mode, bool $preview, int $uid = 0, array $request = []): string
	{
		$this->profiler->startRecording('rendering');
		$this->statusEditor->registerAssets();

		$viewerUid = $this->resolveViewerUid($uid);

		$live_update_div = '';

		// Only a search with a term can be updated
		if ($mode === self::MODE_SEARCH && $viewerUid && (isset($request['q']) || isset($request['tag']))) {
			$live_update_div = $this->getLiveUpdateHtml($mode, false, $viewerUid, $request);
		}

		$_SESSION['return_path'] = $this->args->getQueryString();

		$cb = ['items' => $items, 'mode' => $mode, 'update' => false, 'preview' => $preview];

		$cb = $this->eventDispatcher->dispatch(
			new ArrayFilterEvent(ArrayFilterEvent::CONVERSATION_START, $cb),
		)->getArray();

		$items = $cb['items'];

		$roots = $this->dataProvider->getFlatTemplateData($items, $viewerUid, $preview);
		$html  = $live_update_div . $this->renderThreadedTemplate($roots, $mode, false, false, !$preview && $this->isClickToDisplayEnabled($viewerUid, true));

		$this->profiler->stopRecording();
		return $html;
	}

	/**
	 * Build the netargs query string based on mode and request parameters.
	 *
	 * @param string $mode The rendering mode
	 * @param array $request The request parameters
	 * @return string The netargs query string
	 */
	private function getNetargsForMode(string $mode, array $request): string
	{
		$paramsByMode = [
			self::MODE_NETWORK   => ['contactid', 'search', 'star', 'mention', 'order', 'bmark', 'liked', 'conv', 'nets', 'cmin', 'cmax', 'file', 'channel', 'no_sharer', 'accounttype'],
			self::MODE_CHANNEL   => ['no_sharer', 'accounttype'],
			self::MODE_COMMUNITY => ['no_sharer', 'accounttype'],
			self::MODE_CONTACTS  => [],
			self::MODE_PROFILE   => [],
			self::MODE_NOTES     => [],
			self::MODE_SEARCH    => ['q', 'tag', 'search-option'],
		];

		// MODE_DISPLAY must receive an empty netargs string, all other modes must receive at least "?f="
		if (!isset($paramsByMode[$mode])) {
			return '';
		}

		$filteredRequest = array_intersect_key($request, array_flip($paramsByMode[$mode]));

		// Although the parameter "f" is always empty, it is crucial for the update mechanism.
		// Without it, the page reload doesn't work.
		$filteredRequest['f'] = '';

		$query = http_build_query($filteredRequest, '', '&', PHP_QUERY_RFC3986);

		return $this->getCommandAfterFirstSlash($mode) . '?' . $query;
	}

	/**
	 * Build the live update HTML div based on the mode and request parameters.
	 *
	 * @param string $mode The rendering mode
	 * @param bool $update Whether this is an update
	 * @param int $viewerUid The viewer user ID
	 * @param array $request The request parameters
	 * @return string The live update HTML
	 */
	private function getLiveUpdateHtml(string $mode, bool $update, int $viewerUid, array $request): string
	{
		if ($update) {
			return '';
		}

		// Check if this mode supports live updates
		$supportedModes = [
			self::MODE_NETWORK,
			self::MODE_PROFILE,
			self::MODE_NOTES,
			self::MODE_DISPLAY,
			self::MODE_CHANNEL,
			self::MODE_COMMUNITY,
			self::MODE_CONTACTS,
			self::MODE_SEARCH,
		];

		if (!in_array($mode, $supportedModes, true)) {
			return '';
		}

		return Renderer::replaceMacros(
			Renderer::getMarkupTemplate('live_update.tpl'),
			[
				'$mode'        => $mode,
				'$profile_uid' => $viewerUid,
				'$netargs'     => $this->getNetargsForMode($mode, $request),
			],
		);
	}

	/**
	 * Extracts the part of a path after the first slash.
	 *
	 * @param string $mode The rendering mode
	 * @return string The part after the first slash, or empty string if no slash exists
	 */
	private function getCommandAfterFirstSlash(string $mode): string
	{
		if (!in_array($mode, [self::MODE_NETWORK, self::MODE_CHANNEL, self::MODE_COMMUNITY, self::MODE_CONTACTS])) {
			return '';
		}

		$command = $this->args->getCommand();
		$pos     = strpos($command, '/');
		if ($pos === false) {
			return '';
		}
		return substr($command, $pos + 1);
	}

	/**
	 * Render a timeline from multiple item arrays.
	 * Similar to renderThreadByItem but works with multiple parent items.
	 *
	 * @param array<int, array> $items The parent items to render
	 * @param int $uid The user ID of the viewer
	 * @param string $mode The rendering mode (e.g., self::MODE_DISPLAY)
	 * @param string $order One of self::ORDER_*
	 * @return string The rendered HTML of the timeline
	 * @throws ImagickException
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private function renderTimelineByItems(array $items, int $uid, string $mode, string $order, bool $update): string
	{
		$page_dropping = $uid && $this->pConfig->get($uid, 'system', 'show_page_drop', true);
		$roots         = $this->dataProvider->getRootTemplateDataFromItems($items, $uid, $mode, $order, $page_dropping);
		if (empty($roots)) {
			return '';
		}

		return $this->renderThreadedTemplate($roots, $mode, $update, $page_dropping, $this->isClickToDisplayEnabled($uid));
	}

	/**
	 * Render the conversation template with the given threads.
	 *
	 * @param array<int, array> $threads The thread data to render
	 * @param string $mode The rendering mode (e.g., self::MODE_DISPLAY)
	 * @param bool $clickToDisplay Whether clicking a post opens its own page instead of the normal in-feed behavior
	 * @return string The rendered HTML of the conversation
	 */
	private function renderThreadedTemplate(array $threads, string $mode, bool $update, bool $pagedrop, bool $clickToDisplay = false): string
	{
		return Renderer::replaceMacros(Renderer::getMarkupTemplate('threaded_conversation.tpl'), [
			'$live_update'      => '',
			'$mode'             => $mode,
			'$update'           => $update,
			'$threads'          => $threads,
			'$dropping'         => ($pagedrop ? $this->l10n->t('Delete Selected Items') : false),
			'$click_to_display' => $clickToDisplay,
		]);
	}

	/**
	 * Whether clicking a post's body should open its own page instead of the
	 * usual in-feed behavior. Only takes effect together with the compact or
	 * hidden comments modes; with all comments shown, it has no purpose.
	 * Lists without any comment context (search, contact posts) always qualify.
	 *
	 * @param int $uid The user ID of the viewer
	 * @param bool $commentsAlwaysHidden Whether the list never shows any comments
	 * @return bool
	 */
	private function isClickToDisplayEnabled(int $uid, bool $commentsAlwaysHidden = false): bool
	{
		if (!$uid) {
			return false;
		}

		$commentsMode = self::normalizeCommentsMode($this->pConfig->get($uid, 'system', 'compact_timeline', self::COMMENTS_MODE_ALL));
		if (!$commentsAlwaysHidden && $commentsMode === self::COMMENTS_MODE_ALL) {
			return false;
		}

		return (bool) $this->pConfig->get($uid, 'system', 'click_to_display', false);
	}

	/**
	 * Render a single item as HTML.
	 *
	 * @param array<string, mixed> $item The item data to render
	 * @param string $mode The rendering mode (e.g., self::MODE_DISPLAY)
	 * @param bool $clickToDisplay Whether clicking the item opens its own page
	 * @return string The rendered HTML of the item
	 */
	private function renderItemHtml(array $item, string $mode, bool $clickToDisplay = false): string
	{
		return Renderer::replaceMacros(Renderer::getMarkupTemplate($item['template']), [
			'$item'             => $item,
			'$mode'             => $mode,
			'$remove'           => $this->l10n->t('remove'),
			'$click_to_display' => $clickToDisplay,
		]);
	}

	/**
	 * Resolve the viewer UID from the given UID or session.
	 *
	 * @param int|null $uid The optional user ID, or null to use session
	 * @return int The resolved viewer UID
	 */
	private function resolveViewerUid(?int $uid): int
	{
		return $uid ?? (int) $this->session->getLocalUserId();
	}
}
