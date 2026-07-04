<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Content\Conversation;

use Friendica\AppHelper;
use Friendica\BaseModule;
use Friendica\Content\Conversation\Factory\Channel as ChannelFactory;
use Friendica\Content\Conversation\Repository\UserDefinedChannel;
use Friendica\Content\Item;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Protocol;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Database\DBA;
use Friendica\Event\ArrayFilterEvent;
use Friendica\Model\Contact;
use Friendica\Model\Item as ItemModel;
use Friendica\Model\Post;
use Friendica\Model\Post\Category;
use Friendica\Model\Verb;
use Friendica\Protocol\Activity;
use Friendica\User\Settings\Entity\UserGServer as UserGServerEntity;
use Friendica\User\Settings\Repository\UserGServer as UserGServerRepository;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Provides data for conversation rendering.
 * This class handles all database access and data processing for conversation threads.
 */
final readonly class ConversationDataProvider
{
	public function __construct(
		private UserGServerRepository $userGServer,
		private ChannelFactory $channel,
		private UserDefinedChannel $userDefinedChannel,
		private AppHelper $appHelper,
		private L10n $l10n,
		private Item $item,
		private IManageConfigValues $config,
		private IManagePersonalConfigValues $pConfig,
		private EventDispatcherInterface $eventDispatcher,
		private IHandleUserSessions $session,
		private PostTemplateBuilder $postTemplateBuilder,
		private Activity $activity,
	) {}

	/**
	 * Fetch the parent item if this is a comment.
	 * If the item is already a parent, return it unchanged.
	 *
	 * @param array $item The item array
	 * @param int $viewerUid The user ID of the viewer
	 * @return array|null The parent item, or null if not found
	 */
	public function fetchParentItem(array $item, int $viewerUid): ?array
	{
		if (empty($item)) {
			return null;
		}

		if (($item['gravity'] ?? null) === ItemModel::GRAVITY_PARENT) {
			return $item;
		}

		$parentUriId = (int) ($item['parent-uri-id'] ?? 0);
		if ($parentUriId <= 0) {
			return null;
		}

		$selected = array_merge(ItemModel::DISPLAY_FIELDLIST, ['featured', 'contact-uid', 'gravity', 'post-type', 'post-reason']);
		$params   = ['order' => ['uid' => true]];

		$parentItem = Post::selectFirstForUser($viewerUid, $selected, ['uri-id' => $parentUriId, 'uid' => [0, $viewerUid]], $params);
		if (empty($parentItem) && !$viewerUid) {
			$parentItem = Post::selectFirst($selected, ['uri-id' => $parentUriId, 'uid' => 0], $params);
		}

		return $parentItem ?: null;
	}

	/**
	 * Get the root template data for a thread from an existing item array.
	 *
	 * @param array $item The item array
	 * @param int $viewerUid The user ID of the viewer
	 * @param string $mode The rendering mode
	 * @param array $existing Existing comment URI IDs to exclude
	 * @param bool $pagedrop Whether to enable page drop functionality
	 * @return array<string, mixed>|null The root template data, or null if not found
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function getRootTemplateDataFromItem(array $item, int $viewerUid, string $mode = ConversationRenderer::MODE_DISPLAY, array $existing = [], bool $pagedrop = false): ?array
	{
		// Resolve to parent if this is a comment
		$resolvedItem = $this->fetchParentItem($item, $viewerUid);
		if ($resolvedItem === null) {
			return null;
		}

		// Set pagedrop for the resolved parent item
		$resolvedItem['pagedrop'] = $pagedrop;

		$renderUserId = $viewerUid ?: (int) $resolvedItem['uid'];

		if ($mode === ConversationRenderer::MODE_COMMENTS) {
			$sinceId   = $item['gravity'] !== ItemModel::GRAVITY_PARENT ? $item['uri-id'] : 0;
			$sinceDate = $item['gravity'] !== ItemModel::GRAVITY_PARENT ? $item['created'] : '';
		} else {
			$sinceId   = 0;
			$sinceDate = '';
		}

		$items = $this->populateThreadWithChildren([$resolvedItem], false, ConversationRenderer::ORDER_COMMENTED, $renderUserId, $mode, $sinceId, $sinceDate, $existing, $pagedrop);

		return $this->buildRootTemplateData($items, (int) $resolvedItem['uid'], $viewerUid, $mode, $pagedrop);
	}

	/**
	 * Get the root template data for multiple threads from existing item arrays.
	 * This is similar to getRootTemplateDataFromItem but works with multiple parent items.
	 *
	 * @param array<int, array> $items The parent item arrays
	 * @param int $viewerUid The user ID of the viewer
	 * @param string $mode The rendering mode
	 * @param string $order One of ConversationRenderer::ORDER_*
	 * @param bool $pagedrop Whether to enable page drop functionality
	 * @return array<int, array> The thread template data for all items
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function getRootTemplateDataFromItems(array $items, int $viewerUid, string $mode = ConversationRenderer::MODE_DISPLAY, string $order = ConversationRenderer::ORDER_COMMENTED, bool $pagedrop = false): array
	{
		if (empty($items)) {
			return [];
		}

		// Set pagedrop for parent items before passing to populateThreadWithChildren
		foreach ($items as &$item) {
			$item['pagedrop'] = $pagedrop;
		}
		unset($item);

		$renderUserId = $viewerUid ?: (int) ($items[0]['uid'] ?? 0);

		$itemsWithChildren = $this->populateThreadWithChildren($items, false, $order, $renderUserId, $mode, 0, '', [], $pagedrop);

		return $this->buildThreadTemplatesFromItems(
			$itemsWithChildren,
			$viewerUid,
			$mode,
			false,
			$pagedrop,
			BaseModule::getFormSecurityToken('contact_action'),
		);
	}

	/**
	 * Build thread template data from items.
	 *
	 * @param array<int, array> $items The items to build from
	 * @param int $uid The user ID of the viewer
	 * @param string $mode The rendering mode (e.g., ConversationRenderer::MODE_DISPLAY)
	 * @param bool $preview Whether to render in preview mode
	 * @param bool $pagedrop Whether to enable page drop functionality
	 * @param string $formSecurityToken The form security token
	 * @return array<int, array> The built thread template data
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function buildThreadTemplatesFromItems(array $items, int $uid, string $mode, bool $preview, bool $pagedrop, string $formSecurityToken): array
	{
		if (!$items) {
			return [];
		}

		$convResponses = $this->buildConversationResponses($uid);

		$pcid = Contact::getPublicIdByUserId($uid);

		if (in_array($mode, [ConversationRenderer::MODE_CHANNEL, ConversationRenderer::MODE_COMMUNITY, ConversationRenderer::MODE_CONTACTS, ConversationRenderer::MODE_PROFILE])) {
			$writable = true;
		} else {
			$writable = $items[0]['writable'] || (($items[0]['uid'] === 0) && in_array($items[0]['network'], Protocol::FEDERATED));
		}

		if (!$uid) {
			$writable = false;
		}

		$parentItems = [];
		foreach ($items as $item) {
			$this->processActivityReactions($item, $convResponses, $pcid);

			if ($item['network'] === Protocol::MAIL && $uid !== $item['uid']) {
				continue;
			}

			if (!$this->item->isVisibleActivity($item)) {
				continue;
			}

			$item['pagedrop'] = $pagedrop;
			if ($item['gravity'] === ItemModel::GRAVITY_PARENT) {
				$parentItems[] = $item;
			}
		}

		$threads = [];
		foreach ($parentItems as $item) {
			$templateData = $this->postTemplateBuilder->renderThreadRoot($item, $preview, $writable, $uid, $convResponses, $formSecurityToken, $this->session->get('remote_comment', null));
			if ($templateData !== null) {
				$threads[] = $templateData;
			}
		}

		return $threads;
	}

	/**
	 * Build the root template data from items.
	 *
	 * @param array<int, array> $items The items to build from
	 * @param int $profileOwner The ID of the profile owner
	 * @param int $viewerUid The user ID of the viewer
	 * @param string $mode The rendering mode
	 * @param bool $pagedrop Whether to enable page drop functionality
	 * @return array<string, mixed>|null The root template data, or null if not found
	 */
	private function buildRootTemplateData(array $items, int $profileOwner, int $viewerUid, string $mode, bool $pagedrop = false): ?array
	{
		$this->appHelper->setProfileOwner($profileOwner);

		$items = $this->dispatchConversationStart($items, $mode, false, false);
		if (empty($items)) {
			return null;
		}

		$threads = $this->buildThreadTemplatesFromItems(
			$items,
			$viewerUid,
			$mode,
			false,
			$pagedrop,
			BaseModule::getFormSecurityToken('contact_action'),
		);
		if (empty($threads[0])) {
			return null;
		}

		return $threads[0];
	}

	/**
	 * Dispatch the conversation start event.
	 *
	 * @param array<int, array> $items The items to dispatch
	 * @param string $mode The rendering mode
	 * @param bool $update Whether this is an AJAX update
	 * @param bool $preview Whether to render in preview mode
	 * @return array<int, array> The filtered items after event dispatch
	 */
	private function dispatchConversationStart(array $items, string $mode, bool $update, bool $preview): array
	{
		$cb = [
			'items'   => $items,
			'mode'    => $mode,
			'update'  => $update,
			'preview' => $preview,
		];

		return $this->eventDispatcher->dispatch(
			new ArrayFilterEvent(ArrayFilterEvent::CONVERSATION_START, $cb),
		)->getArray()['items'];
	}

	/**
	 * Populate thread items with their children.
	 *
	 * @param array<int, array> $parents The parent items
	 * @param bool $blockAuthors Whether to block hidden authors
	 * @param string $order The sorting order
	 * @param int $uid The user ID
	 * @param string $mode The rendering mode
	 * @param int $sinceId Only load comments with id > sinceId
	 * @param array $existing Existing comment URI IDs to exclude
	 * @param bool $pagedrop Whether to enable page drop functionality
	 * @return array<int, array> The items with children added
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private function populateThreadWithChildren(array $parents, bool $blockAuthors, string $order, int $uid, string $mode, int $sinceId = 0, string $sinceDate = '', array $existing = [], bool $pagedrop = false): array
	{
		$userGservers = $this->userGServer->listIgnoredByUser($uid);
		$ignoredGsids = array_map(static function (UserGServerEntity $userGServer) {
			return $userGServer->gsid;
		}, $userGservers->getArrayCopy());

		$maxComments = $mode === ConversationRenderer::MODE_DISPLAY ? $this->config->get('system', 'max_display_comments') : $this->config->get('system', 'max_comments');

		$self = $uid !== 0 ? (int) Contact::getPublicIdByUserId($uid) : 0;

		$activities    = [];
		$uriIds        = [];
		$parentAuthors = [];
		$postChannels  = [];
		$uriId         = 0;

		// Initialize items array with parent items, ensuring they have pagedrop set
		$items = [];
		foreach ($parents as $parent) {
			// Ensure parent has pagedrop set
			if (!isset($parent['pagedrop'])) {
				$parent['pagedrop'] = $pagedrop;
			}
			$items[$parent['uri-id']] = $parent;
			if (!empty($parent['thr-parent-id']) && !empty($parent['gravity']) && ($parent['gravity'] === ItemModel::GRAVITY_ACTIVITY)) {
				$uriId = $parent['thr-parent-id'];
				if (!empty($parent['author-id'])) {
					$activities[$uriId] = ['causer-id' => $parent['author-id']];
					foreach (['commented', 'received', 'created'] as $field) {
						if (!empty($parent[$field])) {
							$activities[$uriId][$field] = $parent[$field];
						}
					}
				}
			} else {
				$uriId = $parent['uri-id'];
			}

			$uriIds[]              = $uriId;
			$postChannels[$uriId]  = $parent['channel'] ?? '';
			$parentAuthors[$uriId] = $parent['author-id'];
		}

		$filterAuthors = array_values($parentAuthors);
		if ($self !== 0) {
			$filterAuthors[] = $self;
		}

		$condition = ['parent-uri-id' => $uriIds];
		if ($blockAuthors) {
			$condition['author-hidden'] = false;
		}

		$emojis      = $this->getEmojis($uriIds, $uid);
		$quoteshares = $this->getQuoteShares($uriIds);
		$counts      = $this->getCounts($uriIds);

		$compactTimeline = !in_array($mode, [ConversationRenderer::MODE_DISPLAY, ConversationRenderer::MODE_COMMENTS]) && $this->pConfig->get($uid, 'system', 'compact_timeline');
		$partialLoad     = $mode === ConversationRenderer::MODE_COMMENTS && $sinceId > 0;

		if (!$this->config->get('system', 'legacy_activities')) {
			$condition = DBA::mergeConditions($condition, ["(`gravity` != ? OR `origin`)", ItemModel::GRAVITY_ACTIVITY]);
		}

		if ($compactTimeline) {
			$condition = DBA::mergeConditions($condition, ['author-id' => $filterAuthors]);
		}

		$condition = DBA::mergeConditions(
			$condition,
			["`uid` IN (0, ?) AND (NOT `verb` IN (?, ?, ?) OR `verb` IS NULL)", $uid, Activity::FOLLOW, Activity::VIEW, Activity::READ],
		);
		$condition = DBA::mergeConditions($condition, ["(`uid` != ? OR `private` != ?)", 0, ItemModel::PRIVATE]);
		$condition = DBA::mergeConditions(
			$condition,
			[
				"`visible` AND NOT `deleted` AND NOT `author-blocked` AND NOT `owner-blocked`
			AND ((NOT `contact-pending` AND (`contact-rel` IN (?, ?))) OR `self` OR `contact-uid` = ?)",
				Contact::SHARING,
				Contact::FRIEND,
				0,
			],
		);

		$threadParents = Post::select(['uri-id', 'causer-id'], $condition, ['order' => ['uri-id' => false, 'uid']]);
		$thrParent     = [];
		while ($row = Post::fetch($threadParents)) {
			$thrParent[$row['uri-id']] = $row;
		}
		DBA::close($threadParents);

		if ($partialLoad) {
			$condition = DBA::mergeConditions($condition, ['(`uri-id` >= ? OR `uri-id` = ? OR `created` >= ?)', $sinceId, $uriId, $sinceDate]);
		}

		$params      = ['order' => ['uri-id' => !$partialLoad && !$compactTimeline]];
		$threadItems = Post::select(array_merge(ItemModel::DISPLAY_FIELDLIST, ['featured', 'contact-uid', 'gravity', 'post-type', 'post-reason']), $condition, $params);

		$channels = [];
		foreach ($this->userDefinedChannel->selectByUid($uid) as $userChannel) {
			$channels[$userChannel->code] = $userChannel;
		}
		foreach ($this->channel->getTimelines($uid) as $systemChannel) {
			$channels[$systemChannel->code] = $systemChannel;
		}

		if ($partialLoad) {
			$rows = $this->getRows($threadItems, $mode, $ignoredGsids, $maxComments);
			$rows = $this->filterCommentSubtree($rows, $sinceId);
		} elseif ($compactTimeline) {
			$rows = $this->filterThreadItemsByAuthorParents($threadItems, $parentAuthors, $self);
		} else {
			$rows = $this->getRows($threadItems, $mode, $ignoredGsids, $maxComments);
		}

		// Filter out already existing comments and their descendants when loading more
		if ($mode === ConversationRenderer::MODE_COMMENTS && !empty($existing)) {
			$rows = $this->filterExistingCommentsAndDescendants($rows, $existing);
		}

		// @todo is currently only needed in this mode, but could be helpful for the future to do it for all modes
		if ($compactTimeline || $partialLoad) {
			$answers    = $this->getAnswersPerThread($rows);
			$replyCount = $this->calculateMissingReplyCounts($rows, $emojis);
		} else {
			$answers    = [];
			$replyCount = [];
		}

		$quoteUriIds = [];
		$authors     = [];

		foreach ($rows as $row) {
			$authors[] = $row['author-id'];
			$authors[] = $row['owner-id'];

			if (in_array($row['gravity'], [ItemModel::GRAVITY_PARENT, ItemModel::GRAVITY_COMMENT])) {
				$quoteUriIds[$row['uri-id']] = [
					'uri-id'        => $row['uri-id'],
					'uri'           => $row['uri'],
					'parent-uri-id' => $row['parent-uri-id'],
					'parent-uri'    => $row['parent-uri'],
				];
			}

			$items[$row['uri-id']] = $this->addRowInformation($row, $activities[$row['uri-id']] ?? [], $thrParent[$row['thr-parent-id']] ?? [], $postChannels[$row['thr-parent-id']] ?? '', $uid, $channels);
		}

		$quotes = Post::select(array_merge(ItemModel::DISPLAY_FIELDLIST, ['featured', 'contact-uid', 'gravity', 'post-type', 'post-reason']), ['quote-uri-id' => array_column($quoteUriIds, 'uri-id'), 'body' => '', 'uid' => 0]);
		while ($quote = Post::fetch($quotes)) {
			$row                  = $quote;
			$row['uid']           = $uid;
			$row['verb']          = $row['body'] = $row['raw-body'] = Activity::ANNOUNCE;
			$row['gravity']       = ItemModel::GRAVITY_ACTIVITY;
			$row['object-type']   = Activity\ObjectType::NOTE;
			$row['parent-uri']    = $quoteUriIds[$quote['quote-uri-id']]['parent-uri'];
			$row['parent-uri-id'] = $quoteUriIds[$quote['quote-uri-id']]['parent-uri-id'];
			$row['thr-parent']    = $quoteUriIds[$quote['quote-uri-id']]['uri'];
			$row['thr-parent-id'] = $quoteUriIds[$quote['quote-uri-id']]['uri-id'];

			$authors[]             = $row['author-id'];
			$authors[]             = $row['owner-id'];
			$items[$row['uri-id']] = $this->addRowInformation($row, [], [], $postChannels[$row['thr-parent-id']] ?? '', $uid, $channels);
		}
		DBA::close($quotes);

		$authors   = array_unique($authors);
		$blocks    = [];
		$ignores   = [];
		$collapses = [];
		if (!empty($authors)) {
			$userContacts = DBA::select('user-contact', ['cid', 'blocked', 'ignored', 'collapsed'], ['uid' => $uid, 'cid' => $authors]);
			while ($userContact = DBA::fetch($userContacts)) {
				if ($userContact['blocked']) {
					$blocks[] = $userContact['cid'];
				}
				if ($userContact['ignored']) {
					$ignores[] = $userContact['cid'];
				}
				if ($userContact['collapsed']) {
					$collapses[] = $userContact['cid'];
				}
			}
			DBA::close($userContacts);
		}

		foreach ($items as $key => $row) {
			$items[$key]['emojis']      = $emojis[$key]      ?? [];
			$items[$key]['counts']      = $counts[$key]      ?? 0;
			$items[$key]['quoteshares'] = $quoteshares[$key] ?? [];
			$items[$key]['missing']     = $replyCount[$key]  ?? 0;
			$items[$key]['existing']    = $answers[$key]     ?? [];
			$items[$key]['pagedrop']    = $pagedrop;

			$alwaysDisplay                        = in_array($mode, [ConversationRenderer::MODE_CONTACTS, ConversationRenderer::MODE_CONTACT_POSTS]);
			$items[$key]['user-blocked-author']   = !$alwaysDisplay && in_array($row['author-id'], $blocks);
			$items[$key]['user-ignored-author']   = !$alwaysDisplay && in_array($row['author-id'], $ignores);
			$items[$key]['user-blocked-owner']    = !$alwaysDisplay && in_array($row['owner-id'], $blocks);
			$items[$key]['user-ignored-owner']    = !$alwaysDisplay && in_array($row['owner-id'], $ignores);
			$items[$key]['user-collapsed-author'] = !$alwaysDisplay && in_array($row['author-id'], $collapses);
			$items[$key]['user-collapsed-owner']  = !$alwaysDisplay && in_array($row['owner-id'], $collapses);

			if (in_array($mode, [ConversationRenderer::MODE_CHANNEL, ConversationRenderer::MODE_COMMUNITY, ConversationRenderer::MODE_NETWORK])
				&& (in_array($row['author-id'], $blocks) || in_array($row['owner-id'], $blocks) || in_array($row['author-id'], $ignores) || in_array($row['owner-id'], $ignores))
			) {
				unset($items[$key]);
			}
		}

		$items = $this->sortConversationItems($items, $order, $uid);

		return $items;
	}

	/**
	 * Get emoji reactions for the given URI IDs.
	 *
	 * @param array<int, int> $uriIds The URI IDs to get emojis for
	 * @param int $uid The user ID of the viewer
	 * @return array<int, array> The emoji reactions data
	 */
	private function getEmojis(array $uriIds, int $uid): array
	{
		$emojis = [];
		foreach (Post\Counts::get(['parent-uri-id' => $uriIds]) as $count) {
			$emojis[$count['uri-id']][$count['reaction']]['emoji'] = $count['reaction'];
			$emojis[$count['uri-id']][$count['reaction']]['verb']  = Verb::getByID($count['vid']);
			$emojis[$count['uri-id']][$count['reaction']]['total'] = $count['count'];
			$emojis[$count['uri-id']][$count['reaction']]['count'] = 0;
			$emojis[$count['uri-id']][$count['reaction']]['title'] = [];
		}

		$activityVerbs = [
			Activity::LIKE,
			Activity::DISLIKE,
			Activity::ATTEND,
			Activity::ATTENDMAYBE,
			Activity::ATTENDNO,
			Activity::ANNOUNCE,
			Activity::VIEW,
			Activity::READ,
		];
		$verbs     = array_merge($activityVerbs, [Activity::EMOJIREACT, Activity::POST]);
		$condition = DBA::mergeConditions(['parent-uri-id' => $uriIds, 'gravity' => [ItemModel::GRAVITY_ACTIVITY, ItemModel::GRAVITY_COMMENT], 'verb' => $verbs], ["NOT `deleted`"]);
		$condition = DBA::mergeConditions($condition, ["((`uid` = ? AND `global`) OR (`uid` = ? AND NOT `global`))", 0, $uid]);
		$separator = chr(255) . chr(255) . chr(255);
		$sql       = "SELECT `parent-uri-id`, `thr-parent-id`, `body`, `verb`, `gravity`, `private`, GROUP_CONCAT(REPLACE(`author-name`, '" . $separator . "', ' ') SEPARATOR '" . $separator . "' LIMIT 50) AS `title` FROM `post-user-view` WHERE " . array_shift($condition) . " GROUP BY `parent-uri-id`, `thr-parent-id`, `verb`, `body`, `gravity`, `private`";

		$rows = DBA::p($sql, $condition);
		while ($row = DBA::fetch($rows)) {
			$emoji = $row['gravity'] === ItemModel::GRAVITY_ACTIVITY ? ($row['body'] ?: $row['verb']) : '';
			if (!isset($emojis[$row['thr-parent-id']][$emoji]['title'])) {
				continue;
			}

			if (($emoji === Activity::VIEW) && ($row['private'] === ItemModel::PRIVATE)) {
				continue;
			}

			$names                                          = explode($separator, (string) $row['title']);
			$emojis[$row['thr-parent-id']][$emoji]['title'] = array_unique(array_merge($emojis[$row['thr-parent-id']][$emoji]['title'], $names));
			if ($row['private'] === ItemModel::PRIVATE) {
				$emojis[$row['thr-parent-id']][$emoji]['total'] += count($names);
			}
			$emojis[$row['thr-parent-id']][$emoji]['count'] += count($names);
		}
		DBA::close($rows);

		foreach ($emojis as $uriId => $row) {
			foreach ($row as $emoji => $value) {
				if (($value['count'] < $value['total']) && ($value['count'] < 50)) {
					$emojis[$uriId][$emoji]['total'] = $value['count'];
				}
				if ($emojis[$uriId][$emoji]['total'] === 0) {
					unset($emojis[$uriId][$emoji]);
				}
			}
		}

		return $emojis;
	}

	/**
	 * Get quote shares for the given URI IDs.
	 *
	 * @param array<int, int> $uriIds The URI IDs to get quote shares for
	 * @return array<int, array> The quote shares data
	 */
	private function getQuoteShares(array $uriIds): array
	{
		$condition = DBA::mergeConditions(['quote-uri-id' => $uriIds], ["NOT `quote-uri-id` IS NULL"]);
		$separator = chr(255) . chr(255) . chr(255);
		$sql       = "SELECT `quote-uri-id`, COUNT(*) AS `total`, GROUP_CONCAT(REPLACE(`name`, '" . $separator . "', ' ') SEPARATOR '" . $separator . "' LIMIT 50) AS `title` FROM `post-quote` INNER JOIN `post` ON `post`.`uri-id` = `post-quote`.`uri-id` INNER JOIN `contact` ON `post`.`author-id` = `contact`.`id` WHERE " . array_shift($condition) . " GROUP BY `quote-uri-id`";
		$quotes    = [];

		$rows = DBA::p($sql, $condition);
		while ($row = DBA::fetch($rows)) {
			$quotes[$row['quote-uri-id']]['total'] = $row['total'];
			$quotes[$row['quote-uri-id']]['title'] = array_unique(explode($separator, (string) $row['title']));
		}
		DBA::close($rows);

		return $quotes;
	}

	/**
	 * Get comment counts for the given URI IDs.
	 *
	 * @param array<int, int> $uriIds The URI IDs to get counts for
	 * @return array<int, int> The comment counts per URI ID
	 */
	private function getCounts(array $uriIds): array
	{
		$counts = [];
		foreach (Post\Counts::get(['parent-uri-id' => $uriIds, 'vid' => Verb::getID(Activity::POST)]) as $count) {
			$counts[$count['parent-uri-id']] = ($counts[$count['parent-uri-id']] ?? 0) + $count['count'];
		}

		return $counts;
	}

	/**
	 * Fetch child URI IDs for a parent URI ID recursively.
	 *
	 * @param array<int, array> $rows The rows with uri-id and thr-parent-id
	 * @param int $startUriId The starting URI ID
	 * @return array<int> The child URI IDs
	 */
	private function fetchChildUriIds(array $rows, int $startUriId): array
	{
		$children = [];
		foreach ($rows as $row) {
			if ($row['thr-parent-id'] === $startUriId) {
				$children[] = $row['uri-id'];
				unset($row['uri-id']);
			}
		}

		$grandchildren = [];
		foreach ($children as $child) {
			$grandchild    = $this->fetchChildUriIds($rows, $child);
			$grandchildren = array_merge($grandchildren, $grandchild);
		}
		return array_merge($children, $grandchildren);
	}

	/**
	 * Collect all descendant URI IDs from a starting URI ID using BFS.
	 *
	 * @param array<int, array> $rows All rows with uri-id and thr-parent-id
	 * @param int $startUriId The starting URI ID
	 * @return array<int> All descendant URI IDs
	 */
	private function collectDescendants(array $rows, int $startUriId): array
	{
		$descendants    = [];
		$uriToThrParent = [];

		// Build a map from uri-id to thr-parent-id
		foreach ($rows as $row) {
			if (!empty($row['uri-id']) && !empty($row['thr-parent-id'])) {
				$uriToThrParent[$row['uri-id']] = $row['thr-parent-id'];
			}
		}

		// Find all items that are descendants of $startUriId using BFS
		$queue = [$startUriId];
		while (!empty($queue)) {
			$current = array_shift($queue);
			foreach ($uriToThrParent as $uriId => $parentId) {
				if ($parentId === $current) {
					$descendants[] = $uriId;
					$queue[]       = $uriId;
				}
			}
		}

		return $descendants;
	}

	/**
	 * Filter comment rows to include the subtree starting from a specific comment.
	 *
	 * @param array<int|string, array> $rows All comment rows indexed by uri-id
	 * @param int $sinceId The item ID to start the subtree from
	 * @return array<int|string, array> The starting comment and its descendants, indexed by uri-id
	 */
	private function filterCommentSubtree(array $rows, int $sinceId): array
	{
		if ($sinceId <= 0) {
			return $rows;
		}

		// Collect all descendant URI IDs
		$descendantUriIds = $this->fetchChildUriIds($rows, $sinceId);

		// Add the parent row and the "since" row as well. We need it to create the HTML
		$descendantUriIds[] = $sinceId;
		$descendantUriIds[] = reset($rows)['parent-uri-id'];

		$filteredRows = [];
		foreach ($rows as $uriId => $row) {
			if (in_array($uriId, $descendantUriIds)) {
				$filteredRows[$uriId] = $row;
			}
		}

		return $filteredRows;
	}

	/**
	 * Filter out existing comments and all their descendants from the rows.
	 *
	 * @param array<int, array> $rows All comment rows
	 * @param array<int> $existing Array of URI IDs of existing comments
	 * @return array<int, array> Filtered rows without existing comments and their descendants
	 */
	private function filterExistingCommentsAndDescendants(array $rows, array $existing): array
	{
		// Collect all descendants of existing comments
		$existingWithDescendants = $existing;
		foreach ($existing as $uriId) {
			$descendants             = $this->collectDescendants($rows, $uriId);
			$existingWithDescendants = array_merge($existingWithDescendants, $descendants);
		}
		// Filter out existing comments and their descendants
		$filteredRows = array_filter($rows, function ($row) use ($existingWithDescendants) {
			return !in_array($row['uri-id'], $existingWithDescendants);
		});
		// Reindex the array
		return array_values($filteredRows);
	}

	/**
	 * Filter thread items and keeps only entries whose parents are in the author list.
	 * Recursively removes URI IDs whose parent is not in the list, and filters the rows accordingly.
	 *
	 * @param object $threadItems The result set of thread items
	 * @param array $parentAuthors Array of parent authors (uri-id => author-id)
	 * @param int $self The own public contact ID
	 * @return array Thread items that only contain the author or the current user
	 *
	 * @todo Future improvements could be keeping all comments by the $self user and
	 * fetching all comments the $self user commented on.
	 * Additionally the last x comments by any contact could be fetched and have to be marked as "keep".
	 */
	private function filterThreadItemsByAuthorParents(object $threadItems, array $parentAuthors, int $self): array
	{
		$authorUriIds = [];
		$rows         = [];
		$thr          = [];
		while ($row = Post::fetch($threadItems)) {
			if (in_array($row['author-id'], [$parentAuthors[$row['parent-uri-id']], $self])) {
				$authorUriIds[]      = $row['uri-id'];
				$rows[]              = $row;
				$thr[$row['uri-id']] = $row['thr-parent-id'];
			}
		}

		if ($authorUriIds && $thr) {
			$authorUriSet = array_flip($authorUriIds);
			do {
				$oldCount = count($authorUriSet);
				foreach (array_keys($authorUriSet) as $uriId) {
					if (isset($thr[$uriId]) && !isset($authorUriSet[$thr[$uriId]])) {
						unset($authorUriSet[$uriId]);
					}
				}
			} while (count($authorUriSet) < $oldCount);
			$authorUriIds = array_keys($authorUriSet);

			// Filter $rows: keep only entries whose uri-id is in $authorUriIds
			$rows = array_filter($rows, function ($row) use ($authorUriIds) {
				return in_array($row['uri-id'], $authorUriIds);
			});
		}
		return $this->indexRowsByUriId($rows);
	}

	/**
	 * Fetch rows from the thread items, applying filters for ignored GSIDs and comment limits.
	 *
	 * @param object $threadItems The result set of thread items
	 * @param string $mode The rendering mode
	 * @param array $ignoredGsids Array of ignored GSIDs
	 * @param int $maxComments Maximum number of comments to include per parent
	 * @return array Filtered rows indexed by uri-id
	 */
	private function getRows(object $threadItems, string $mode, array $ignoredGsids, int $maxComments): array
	{
		$rows            = [];
		$commentCounter  = [];
		$activityCounter = [];
		while ($row = Post::fetch($threadItems)) {
			if (!empty($rows[$row['uri-id']]) && ($row['uid'] === 0)) {
				continue;
			}

			if (in_array($row['author-gsid'], $ignoredGsids)
				|| in_array($row['owner-gsid'], $ignoredGsids)
				|| in_array($row['causer-gsid'], $ignoredGsids)
			) {
				continue;
			}

			if (($mode !== ConversationRenderer::MODE_CONTACTS) && !$row['origin']) {
				$row['featured'] = false;
			}

			if ($maxComments > 0) {
				if (!isset($commentCounter[$row['parent-uri-id']])) {
					$commentCounter[$row['parent-uri-id']] = 0;
				}
				if (($row['gravity'] === ItemModel::GRAVITY_COMMENT) && (++$commentCounter[$row['parent-uri-id']] > $maxComments)) {
					continue;
				}
				if (!isset($activityCounter[$row['parent-uri-id']])) {
					$activityCounter[$row['parent-uri-id']] = 0;
				}
				if (($row['gravity'] === ItemModel::GRAVITY_ACTIVITY) && (++$activityCounter[$row['parent-uri-id']] > $maxComments)) {
					continue;
				}
			}
			$rows[$row['uri-id']] = $row;
		}
		return $rows;
	}

	/**
	 * Index rows by uri-id, keeping only entries with uid > 0 when duplicates exist.
	 * Also prefers rows with gravity=parent.
	 *
	 * @param array $rows Array of post rows with 'uri-id' and 'uid' keys
	 * @return array Associative array with uri-id as keys
	 */
	private function indexRowsByUriId(array $rows): array
	{
		$indexed = [];
		foreach ($rows as $row) {
			if (!empty($indexed[$row['uri-id']]) && ($row['uid'] === 0)) {
				continue;
			}
			$indexed[$row['uri-id']] = $row;
		}
		return $indexed;
	}

	/**
	 * Add additional information to a row.
	 *
	 * @param array<string, mixed> $row The row data to enhance
	 * @param array<string, mixed> $activity The activity data
	 * @param array<string, mixed> $thrParent The thread parent data
	 * @param string $channel The channel code
	 * @param int $uid The user ID
	 * @param array<string, \Friendica\Content\Conversation\Entity\Channel> $channels The available channels
	 * @return array<string, mixed> The enhanced row data
	 */
	private function addRowInformation(array $row, array $activity, array $thrParent, string $channel, int $uid, array $channels): array
	{
		if (!$row['writable']) {
			$row['writable'] = in_array($row['network'], Protocol::FEDERATED);
		}

		if (!empty($activity)) {
			if ($row['gravity'] === ItemModel::GRAVITY_PARENT) {
				$row['post-reason']   = ItemModel::PR_ANNOUNCEMENT;
				$row                  = array_merge($row, $activity);
				$contact              = Contact::getById($activity['causer-id'], ['url', 'name', 'thumb']);
				$row['causer-link']   = $contact['url'];
				$row['causer-avatar'] = $contact['thumb'];
				$row['causer-name']   = $contact['name'];
			} elseif (($row['gravity'] === ItemModel::GRAVITY_ACTIVITY) && ($row['verb'] === Activity::ANNOUNCE) && ($row['author-id'] === $activity['causer-id'])) {
				return $row;
			}
		}

		if ($channel) {
			$row['channel']     = $channel;
			$row['post-reason'] = ItemModel::PR_CHANNEL;
		}

		switch ($row['post-reason']) {
			case ItemModel::PR_TO:
				$row['direction'] = ['direction' => 7, 'title' => $this->l10n->t('You had been addressed (%s).', 'to')];
				break;
			case ItemModel::PR_CC:
				$row['direction'] = ['direction' => 7, 'title' => $this->l10n->t('You had been addressed (%s).', 'cc')];
				break;
			case ItemModel::PR_BTO:
				$row['direction'] = ['direction' => 7, 'title' => $this->l10n->t('You had been addressed (%s).', 'bto')];
				break;
			case ItemModel::PR_BCC:
				$row['direction'] = ['direction' => 7, 'title' => $this->l10n->t('You had been addressed (%s).', 'bcc')];
				break;
			case ItemModel::PR_AUDIENCE:
				$row['direction'] = ['direction' => 7, 'title' => $this->l10n->t('You had been addressed (%s).', 'audience')];
				break;
			case ItemModel::PR_FOLLOWER:
				$row['direction'] = ['direction' => 6, 'title' => $this->l10n->t('You are following %s.', $row['causer-name'] ?: $row['author-name'])];
				break;
			case ItemModel::PR_TAG:
				$tags             = Category::getArrayByURIId($row['uri-id'], $row['uid'], Category::SUBCRIPTION);
				$row['direction'] = ['direction' => 4, 'title' => empty($tags) ? $this->l10n->t('You subscribed to one or more tags in this post.') : $this->l10n->t('You subscribed to %s.', implode(', ', $tags))];
				break;
			case ItemModel::PR_ANNOUNCEMENT:
				if (!empty($row['causer-id']) && $this->pConfig->get($uid, 'system', 'display_resharer')) {
					$row['owner-id']     = $row['causer-id'];
					$row['owner-link']   = $row['causer-link'];
					$row['owner-avatar'] = $row['causer-avatar'];
					$row['owner-name']   = $row['causer-name'];
				}

				if (in_array($row['gravity'], [ItemModel::GRAVITY_PARENT, ItemModel::GRAVITY_COMMENT]) && !empty($row['causer-id'])) {
					$causer = [
						'uid'     => 0,
						'id'      => $row['causer-id'],
						'network' => $row['causer-network'],
						'url'     => $row['causer-link'],
						'alias'   => $row['causer-alias'],
					];
					$row['reshared'] = $this->l10n->t('%s reshared this.', '<a href="' . htmlentities(Contact::magicLinkByContact($causer)) . '">' . htmlentities((string) $row['causer-name']) . '</a>');
				}
				$row['direction'] = ['direction' => 3, 'title' => empty($row['causer-id']) ? $this->l10n->t('Reshared') : $this->l10n->t('Reshared by %s <%s>', $row['causer-name'], $row['causer-link'])];
				break;
			case ItemModel::PR_COMMENT:
				$row['direction'] = ['direction' => 5, 'title' => $this->l10n->t('%s is participating in this thread.', $row['author-name'])];
				break;
			case ItemModel::PR_STORED:
				$row['direction'] = ['direction' => 8, 'title' => $this->l10n->t('Stored for general reasons')];
				break;
			case ItemModel::PR_GLOBAL:
				$row['direction'] = ['direction' => 9, 'title' => $this->l10n->t('Global post')];
				break;
			case ItemModel::PR_RELAY:
				$row['direction'] = ['direction' => 10, 'title' => empty($row['causer-id']) ? $this->l10n->t('Sent via an relay server') : $this->l10n->t('Sent via the relay server %s <%s>', $row['causer-name'], $row['causer-link'])];
				break;
			case ItemModel::PR_FETCHED:
				$row['direction'] = ['direction' => 2, 'title' => empty($row['causer-id']) ? $this->l10n->t('Fetched') : $this->l10n->t('Fetched because of %s <%s>', $row['causer-name'], $row['causer-link'])];
				break;
			case ItemModel::PR_COMPLETION:
				$row['direction'] = ['direction' => 2, 'title' => $this->l10n->t('Stored because of a child post to complete this thread.')];
				break;
			case ItemModel::PR_DIRECT:
				$row['direction'] = ['direction' => 6, 'title' => $this->l10n->t('Local delivery')];
				break;
			case ItemModel::PR_ACTIVITY:
				$row['direction'] = ['direction' => 2, 'title' => $this->l10n->t('Stored because of your activity (like, comment, bookmark, ...)')];
				break;
			case ItemModel::PR_DISTRIBUTE:
				$row['direction'] = ['direction' => 6, 'title' => $this->l10n->t('Distributed')];
				break;
			case ItemModel::PR_PUSHED:
				$row['direction'] = ['direction' => 1, 'title' => $this->l10n->t('Pushed to us')];
				break;
			case ItemModel::PR_CHANNEL:
				$title            = $channels[$channel]->label       ?? $channel;
				$description      = $channels[$channel]->description ?? '';
				$row['direction'] = ['direction' => 11, 'title' => $description ? $this->l10n->t('Channel "%s": %s', $title, $description) : $this->l10n->t('Channel "%s"', $title)];
				break;
		}

		$row['thr-parent-row'] = $thrParent;

		return $row;
	}

	/**
	 * Fetch child items for a parent item.
	 *
	 * @param array<int, array> $itemList The list of all items (passed by reference, modified)
	 * @param array<string, mixed> $parent The parent item
	 * @param bool $recursive Whether to recursively fetch children
	 * @return array<int, array> The child items
	 */
	private function fetchChildItems(array &$itemList, array $parent, bool $recursive = true): array
	{
		$children = [];
		foreach ($itemList as $index => $item) {
			if ($item['gravity'] !== ItemModel::GRAVITY_PARENT) {
				if ($recursive) {
					$thrParent = $item['thr-parent-id'];
					if ($thrParent === '') {
						$thrParent = $item['parent-uri-id'];
					}

					if ($thrParent === $parent['uri-id']) {
						$item['children'] = $this->fetchChildItems($itemList, $item);
						$children[]       = $item;
						unset($itemList[$index]);
					}
				} elseif ($item['parent-uri-id'] === $parent['uri-id']) {
					$children[] = $item;
					unset($itemList[$index]);
				}
			}
		}

		return $children;
	}

	/**
	 * Sort item children.
	 *
	 * @param array<int, array> $items The items to sort
	 * @return array<int, array> The sorted items
	 */
	private function sortItemChildren(array $items): array
	{
		$result = $items;
		usort($result, $this->sortThreadsByReceivedRev(...));
		foreach ($result as $key => $item) {
			if (isset($result[$key]['children'])) {
				$result[$key]['children'] = $this->sortItemChildren($result[$key]['children']);
			}
		}

		return $result;
	}

	/**
	 * Add children to the item list.
	 *
	 * @param array<int, array> $children The children to add
	 * @param array<int, array> $itemList The item list to add to (passed by reference)
	 * @return void
	 */
	private function addChildrenToList(array $children, array &$itemList): void
	{
		foreach ($children as $child) {
			$itemList[] = $child;
			if (isset($child['children'])) {
				$this->addChildrenToList($child['children'], $itemList);
			}
		}
	}

	/**
	 * Smart flatten conversation structure.
	 * Flattens nested conversation structures for better readability.
	 *
	 * @param array<string, mixed> $parent The parent conversation item
	 * @return array<string, mixed> The flattened conversation structure
	 */
	private function smartFlattenConversation(array $parent): array
	{
		if (!isset($parent['children']) || count($parent['children']) === 0) {
			return $parent;
		}

		for ($index = 0; $index < count($parent['children']); $index++) {
			$child = $parent['children'][$index];
			if (isset($child['children']) && count($child['children'])) {
				$countPostClosure = function ($var) {
					return $var['verb'] === Activity::POST;
				};

				$childPostCount     = count(array_filter($child['children'], $countPostClosure));
				$remainingPostCount = count(array_filter(array_slice($parent['children'], $index), $countPostClosure));
				if ($childPostCount === 1 && $remainingPostCount === 1) {
					$childIndex = 0;
					while (($childIndex < count($child['children'])) && ($child['children'][$childIndex]['verb'] !== Activity::POST)) {
						$childIndex++;
					}
					if (isset($child['children'][$childIndex])) {
						$movedItem = $child['children'][$childIndex];
						unset($parent['children'][$index]['children'][$childIndex]);
						$parent['children'][] = $movedItem;
					}
				} else {
					$parent['children'][$index] = $this->smartFlattenConversation($child);
				}
			}
		}

		return $parent;
	}

	/**
	 * Sort conversation items.
	 *
	 * @param array<int, array> $itemList The items to sort
	 * @param string $order One of ConversationRenderer::ORDER_*
	 * @param int $uid The user ID of the viewer
	 * @return array<int, array> The sorted conversation items
	 */
	private function sortConversationItems(array $itemList, string $order, int $uid): array
	{
		$parents = [];
		if (count($itemList) === 0) {
			return $parents;
		}

		$itemArray = [];
		foreach ($itemList as $item) {
			$itemArray[$item['uri-id']] = $item;
		}

		foreach ($itemArray as $item) {
			if ($item['gravity'] === ItemModel::GRAVITY_PARENT) {
				$parents[] = $item;
			}
		}

		if (str_contains($order, ConversationRenderer::ORDER_PINNED_RECEIVED)) {
			usort($parents, $this->sortThreadsByFeaturedReceived(...));
		} elseif (str_contains($order, ConversationRenderer::ORDER_PINNED_COMMENTED)) {
			usort($parents, $this->sortThreadsByFeaturedCommented(...));
		} elseif (str_contains($order, ConversationRenderer::ORDER_PINNED_CREATED)) {
			usort($parents, $this->sortThreadsByFeaturedCreated(...));
		} elseif (str_contains($order, ConversationRenderer::ORDER_RECEIVED)) {
			usort($parents, $this->sortThreadsByReceived(...));
		} elseif (str_contains($order, ConversationRenderer::ORDER_COMMENTED)) {
			usort($parents, $this->sortThreadsByCommented(...));
		} elseif (str_contains($order, ConversationRenderer::ORDER_CREATED)) {
			usort($parents, $this->sortThreadsByCreated(...));
		}

		foreach ($parents as $index => $parent) {
			$parents[$index]['children'] = array_merge(
				$this->fetchChildItems($itemArray, $parent, true),
				$this->fetchChildItems($itemArray, $parent, false),
			);
		}
		foreach ($parents as $index => $parent) {
			$parents[$index]['children'] = $this->sortItemChildren($parents[$index]['children']);
		}

		if (!$this->pConfig->get($uid, 'system', 'no_smart_threading', 0)) {
			foreach ($parents as $index => $parent) {
				$parents[$index] = $this->smartFlattenConversation($parent);
			}
		}

		foreach ($parents as $parent) {
			if (count($parent['children'])) {
				$this->addChildrenToList($parent['children'], $parents);
			}
		}

		return $parents;
	}

	/**
	 * Sort threads by featured and received date.
	 *
	 * @param array<string, mixed> $a First thread to compare
	 * @param array<string, mixed> $b Second thread to compare
	 * @return int Comparison result (-1, 0, 1)
	 */
	private function sortThreadsByFeaturedReceived(array $a, array $b): int
	{
		if ($b['featured'] && !$a['featured']) {
			return 1;
		} elseif (!$b['featured'] && $a['featured']) {
			return -1;
		}

		return strcmp((string) $b['received'], (string) $a['received']);
	}

	/**
	 * Sort threads by featured and commented date.
	 *
	 * @param array<string, mixed> $a First thread to compare
	 * @param array<string, mixed> $b Second thread to compare
	 * @return int Comparison result (-1, 0, 1)
	 */
	private function sortThreadsByFeaturedCommented(array $a, array $b): int
	{
		if ($b['featured'] && !$a['featured']) {
			return 1;
		} elseif (!$b['featured'] && $a['featured']) {
			return -1;
		}

		return strcmp((string) $b['commented'], (string) $a['commented']);
	}

	/**
	 * Sort threads by featured and created date.
	 *
	 * @param array<string, mixed> $a First thread to compare
	 * @param array<string, mixed> $b Second thread to compare
	 * @return int Comparison result (-1, 0, 1)
	 */
	private function sortThreadsByFeaturedCreated(array $a, array $b): int
	{
		if ($b['featured'] && !$a['featured']) {
			return 1;
		} elseif (!$b['featured'] && $a['featured']) {
			return -1;
		}

		return strcmp((string) $b['created'], (string) $a['created']);
	}

	/**
	 * Sort threads by received date descending.
	 *
	 * @param array<string, mixed> $a First thread to compare
	 * @param array<string, mixed> $b Second thread to compare
	 * @return int Comparison result (-1, 0, 1)
	 */
	private function sortThreadsByReceived(array $a, array $b): int
	{
		return strcmp((string) $b['received'], (string) $a['received']);
	}

	/**
	 * Sort threads by received date ascending (reversed).
	 *
	 * @param array<string, mixed> $a First thread to compare
	 * @param array<string, mixed> $b Second thread to compare
	 * @return int Comparison result (-1, 0, 1)
	 */
	private function sortThreadsByReceivedRev(array $a, array $b): int
	{
		return strcmp((string) $a['received'], (string) $b['received']);
	}

	/**
	 * Sort threads by commented date descending.
	 *
	 * @param array<string, mixed> $a First thread to compare
	 * @param array<string, mixed> $b Second thread to compare
	 * @return int Comparison result (-1, 0, 1)
	 */
	private function sortThreadsByCommented(array $a, array $b): int
	{
		return strcmp((string) $b['commented'], (string) $a['commented']);
	}

	/**
	 * Sort threads by created date descending.
	 *
	 * @param array<string, mixed> $a First thread to compare
	 * @param array<string, mixed> $b Second thread to compare
	 * @return int Comparison result (-1, 0, 1)
	 */
	private function sortThreadsByCreated(array $a, array $b): int
	{
		return strcmp((string) $b['created'], (string) $a['created']);
	}

	/**
	 * Get answers per thread.
	 *
	 * @param array $rows Array of row data
	 * @return array<int, int[]> Associative array mapping thread parent IDs to their answer URIs
	 */
	private function getAnswersPerThread(array $rows): array
	{
		$threadUriIds = [];
		$answers      = [];
		foreach ($rows as $row) {
			if (in_array($row['uri-id'], $threadUriIds) || $row['thr-parent-id'] === $row['uri-id']) {
				continue;
			}
			$threadUriIds[]                   = $row['uri-id'];
			$answers[$row['thr-parent-id']][] = $row['uri-id'];
		}
		return $answers;
	}

	/**
	 * Counts the number of answers per thread parent ID from the given rows.
	 *
	 * @param array $rows Array of row data with 'uri-id' and 'thr-parent-id' keys
	 * @return array<int, int> Associative array mapping thread parent IDs to their answer counts
	 */
	private function countAnswersPerThread(array $rows): array
	{
		$threadUriIds = [];
		$answerCounts = [];
		foreach ($rows as $row) {
			if (in_array($row['uri-id'], $threadUriIds) || $row['thr-parent-id'] === $row['uri-id']) {
				continue;
			}
			$threadUriIds[]                      = $row['uri-id'];
			$answerCounts[$row['thr-parent-id']] = !isset($answerCounts[$row['thr-parent-id']]) ? 1 : ++$answerCounts[$row['thr-parent-id']];
		}
		return $answerCounts;
	}

	/**
	 * Extracts post totals from emoji array for entries with verb 'http://activitystrea.ms/schema/1.0/post'.
	 *
	 * @param array $emojis Array of emoji data with structure [uriId][verb][data]
	 * @return array<int, int> Associative array mapping URI IDs to their post totals
	 */
	private function getPostTotalsFromEmojis(array $emojis): array
	{
		$result = [];
		foreach ($emojis as $uriId => $verbData) {
			foreach ($verbData as $data) {
				if ($data['verb'] === Activity::POST) {
					$result[$uriId] = $data['total'];
					break;
				}
			}
		}
		return $result;
	}

	/**
	 * Calculates missing reply counts by subtracting visible answer counts from post totals.
	 *
	 * @param array $rows Array of row data with 'uri-id' and 'thr-parent-id' keys
	 * @param array $emojis Array of emoji data with structure [uriId][verb][data]
	 * @return array<int, int> Associative array mapping URI IDs to their reply counts
	 */
	private function calculateMissingReplyCounts(array $rows, array $emojis): array
	{
		$answerCounts = $this->countAnswersPerThread($rows);
		$postTotals   = $this->getPostTotalsFromEmojis($emojis);

		$replyCount = [];
		foreach ($postTotals as $key => $value) {
			$replyCount[$key] = $value - ($answerCounts[$key] ?? 0);
		}
		return $replyCount;
	}

	/**
	 * Build conversation responses array.
	 *
	 * @param int $viewerUid The user ID of the viewer
	 * @return array<string, array> The conversation responses array
	 */
	private function buildConversationResponses(int $viewerUid): array
	{
		$convResponses = [
			'like'        => [],
			'dislike'     => [],
			'attendyes'   => [],
			'attendno'    => [],
			'attendmaybe' => [],
			'announce'    => [],
		];

		if ($this->pConfig->get($viewerUid, 'system', 'hide_dislike')) {
			unset($convResponses['dislike']);
		}

		return $convResponses;
	}

	/**
	 * Process activity reactions and update conversation responses.
	 *
	 * @param array<string, mixed> $activity The activity data to process
	 * @param array<string, array> $convResponses The conversation responses array to update (by reference)
	 * @param int $pcid Public contact id of the current user
	 * @return void
	 */
	private function processActivityReactions(array $activity, array &$convResponses, int $pcid): void
	{
		$threadParent = $activity['thr-parent-row'] ?? [];

		foreach ($convResponses as $mode => $value) {
			switch ($mode) {
				case 'like':
					$verb = Activity::LIKE;
					break;
				case 'dislike':
					$verb = Activity::DISLIKE;
					break;
				case 'attendyes':
					$verb = Activity::ATTEND;
					break;
				case 'attendno':
					$verb = Activity::ATTENDNO;
					break;
				case 'attendmaybe':
					$verb = Activity::ATTENDMAYBE;
					break;
				case 'announce':
					$verb = Activity::ANNOUNCE;
					break;
				default:
					return;
			}

			if (!empty($activity['verb']) && $this->activity->match($activity['verb'], $verb) && ($activity['gravity'] !== ItemModel::GRAVITY_PARENT)) {
				$author = [
					'uid'     => 0,
					'id'      => $activity['author-id'],
					'network' => $activity['author-network'],
					'url'     => $activity['author-link'],
					'alias'   => $activity['author-alias'],
				];
				$url     = Contact::magicLinkByContact($author);
				$sparkle = str_starts_with($url, 'contact/redir/') ? ' class="sparkle" ' : '';
				$link    = '<a href="' . $url . '"' . $sparkle . '>' . htmlentities((string) $activity['author-name']) . '</a>';

				if (empty($activity['thr-parent-id'])) {
					$activity['thr-parent-id'] = $activity['parent-uri-id'];
				}

				if (($verb === Activity::ANNOUNCE) && !empty($threadParent['causer-id']) && ($threadParent['causer-id'] === $activity['author-id'])) {
					continue;
				}

				if (!isset($convResponses[$mode][$activity['thr-parent-id']])) {
					$convResponses[$mode][$activity['thr-parent-id']] = [
						'links' => [],
						'self'  => 0,
					];
				} elseif (in_array($link, $convResponses[$mode][$activity['thr-parent-id']]['links'])) {
					continue;
				}

				if ($pcid === $activity['author-id']) {
					$convResponses[$mode][$activity['thr-parent-id']]['self'] = 1;
				}

				$convResponses[$mode][$activity['thr-parent-id']]['links'][] = $link;
				return;
			}
		}
	}
}
