<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Model;

use Friendica\App\BaseURL;
use Friendica\Content\Item as ItemContent;
use Friendica\Core\Protocol;
use Friendica\Database\Database;
use Friendica\Protocol\Activity;
use Friendica\Util\DateTimeFormat;
use Psr\Log\LoggerInterface;

/**
 * A helper class for handling an Item Model
 *
 * @internal ONLY for use in Friendica\Content\Item class
 *
 * @see Item::insert()
 */
final class ItemHelper
{
	private ItemContent $itemContent;

	private Activity $activity;

	private LoggerInterface $logger;

	private Database $database;

	private string $baseUrl;

	public function __construct(
		ItemContent $itemContent,
		Activity $activity,
		LoggerInterface $logger,
		Database $database,
		BaseURL $baseURL
	) {
		$this->itemContent = $itemContent;
		$this->activity    = $activity;
		$this->logger      = $logger;
		$this->database    = $database;
		$this->baseUrl     = $baseURL->__toString();
	}

	public function prepareOriginPost(array $item): array
	{
		$item = $this->itemContent->initializePost($item);
		$item = $this->itemContent->finalizePost($item, false);

		return $item;
	}

	public function prepareItemData(array $item, bool $notify): array
	{
		$item['guid'] = Item::guid($item, $notify);
		$item['uri']  = substr(trim($item['uri'] ?? '') ?: Item::newURI($item['guid']), 0, 255);

		// Store URI data
		$item['uri-id'] = ItemURI::insert(['uri' => $item['uri'], 'guid' => $item['guid']]);

		// Backward compatibility: parent-uri used to be the direct parent uri.
		// If it is provided without a thr-parent, it probably is the old behavior.
		if (empty($item['thr-parent']) || empty($item['parent-uri'])) {
			$item['thr-parent'] = trim($item['thr-parent'] ?? $item['parent-uri'] ?? $item['uri']);
			$item['parent-uri'] = $item['thr-parent'];
		}

		$item['thr-parent-id'] = ItemURI::getIdByURI($item['thr-parent']);
		$item['parent-uri-id'] = ItemURI::getIdByURI($item['parent-uri']);

		return $item;
	}

	/**
	 * Return the id of the given item array if it has been stored before
	 *
	 * @param array $item Item record
	 * @return integer Item id or zero on error
	 */
	public function getDuplicateID(array $item): int
	{
		if (empty($item['network']) || in_array($item['network'], Protocol::FEDERATED)) {
			$condition = [
				'`uri-id` = ? AND `uid` = ? AND `network` IN (?, ?, ?)',
				$item['uri-id'],
				$item['uid'],
				Protocol::ACTIVITYPUB,
				Protocol::DIASPORA,
				Protocol::DFRN
			];

			$existing = Post::selectFirst(['id', 'network'], $condition);

			if ($this->database->isResult($existing)) {
				// We only log the entries with a different user id than 0. Otherwise we would have too many false positives
				if ($item['uid'] != 0) {
					$this->logger->notice('Item already existed for user', [
						'uri-id'           => $item['uri-id'],
						'uid'              => $item['uid'],
						'network'          => $item['network'],
						'existing_id'      => $existing['id'],
						'existing_network' => $existing['network']
					]);
				}

				return $existing['id'];
			}
		}
		return 0;
	}

	/**
	 * Check if the item array is a duplicate
	 *
	 * @private
	 *
	 * @param array $item Item record
	 * @return boolean is it a duplicate?
	 */
	public function isDuplicate(array $item): bool
	{
		// Checking if there is already an item with the same guid
		$condition = ['guid' => $item['guid'], 'network' => $item['network'], 'uid' => $item['uid']];
		if (Post::exists($condition)) {
			$this->logger->notice('Found already existing item', $condition);
			return true;
		}

		$condition = [
			'uri-id'  => $item['uri-id'], 'uid' => $item['uid'],
			'network' => [$item['network'], Protocol::DFRN]
		];
		if (Post::exists($condition)) {
			$this->logger->notice('duplicated item with the same uri found.', $condition);
			return true;
		}

		// On Friendica and Diaspora the GUID is unique
		if (in_array($item['network'], [Protocol::DFRN, Protocol::DIASPORA])) {
			$condition = ['guid' => $item['guid'], 'uid' => $item['uid']];
			if (Post::exists($condition)) {
				$this->logger->notice('duplicated item with the same guid found.', $condition);
				return true;
			}
		}

		/*
		 * Check for already added items.
		 * There is a timing issue here that sometimes creates double postings.
		 * An unique index would help - but the limitations of MySQL (maximum size of index values) prevent this.
		 */
		if (($item['uid'] == 0) && Post::exists(['uri-id' => $item['uri-id'], 'uid' => 0])) {
			$this->logger->notice('Global item already stored.', ['uri-id' => $item['uri-id'], 'network' => $item['network']]);
			return true;
		}

		return false;
	}

	public function validateItemData(array $item): array
	{
		$item['wall']          = intval($item['wall'] ?? 0);
		$item['extid']         = trim($item['extid'] ?? '');
		$item['author-name']   = trim($item['author-name'] ?? '');
		$item['author-link']   = trim($item['author-link'] ?? '');
		$item['author-avatar'] = trim($item['author-avatar'] ?? '');
		$item['owner-name']    = trim($item['owner-name'] ?? '');
		$item['owner-link']    = trim($item['owner-link'] ?? '');
		$item['owner-avatar']  = trim($item['owner-avatar'] ?? '');
		$item['received']      = (isset($item['received'])  ? DateTimeFormat::utc($item['received'])  : DateTimeFormat::utcNow());
		$item['created']       = (isset($item['created'])   ? DateTimeFormat::utc($item['created'])   : $item['received']);
		$item['edited']        = (isset($item['edited'])    ? DateTimeFormat::utc($item['edited'])    : $item['created']);
		$item['changed']       = (isset($item['changed'])   ? DateTimeFormat::utc($item['changed'])   : $item['created']);
		$item['commented']     = (isset($item['commented']) ? DateTimeFormat::utc($item['commented']) : $item['created']);
		$item['title']         = substr(trim($item['title'] ?? ''), 0, 255);
		$item['location']      = trim($item['location'] ?? '');
		$item['coord']         = trim($item['coord'] ?? '');
		$item['visible']       = (isset($item['visible']) ? intval($item['visible']) : 1);
		$item['deleted']       = 0;
		$item['verb']          = trim($item['verb'] ?? '');
		$item['object-type']   = trim($item['object-type'] ?? '');
		$item['object']        = trim($item['object'] ?? '');
		$item['target-type']   = trim($item['target-type'] ?? '');
		$item['target']        = trim($item['target'] ?? '');
		$item['plink']         = substr(trim($item['plink'] ?? ''), 0, 255);
		$item['allow_cid']     = trim($item['allow_cid'] ?? '');
		$item['allow_gid']     = trim($item['allow_gid'] ?? '');
		$item['deny_cid']      = trim($item['deny_cid'] ?? '');
		$item['deny_gid']      = trim($item['deny_gid'] ?? '');
		$item['private']       = intval($item['private'] ?? Item::PUBLIC);
		$item['body']          = trim($item['body'] ?? '');
		$item['raw-body']      = trim($item['raw-body'] ?? $item['body']);
		$item['app']           = trim($item['app'] ?? '');
		$item['origin']        = intval($item['origin'] ?? 0);
		$item['postopts']      = trim($item['postopts'] ?? '');
		$item['resource-id']   = trim($item['resource-id'] ?? '');
		$item['event-id']      = intval($item['event-id'] ?? 0);
		$item['inform']        = trim($item['inform'] ?? '');
		$item['file']          = trim($item['file'] ?? '');

		// Items cannot be stored before they happen ...
		if ($item['created'] > DateTimeFormat::utcNow()) {
			$item['created'] = DateTimeFormat::utcNow();
		}

		// We haven't invented time travel by now.
		if ($item['edited'] > DateTimeFormat::utcNow()) {
			$item['edited'] = DateTimeFormat::utcNow();
		}

		$item['plink'] = ($item['plink'] ?? '') ?: $this->baseUrl . '/display/' . urlencode($item['guid']);

		$item['gravity'] = $this->getGravity($item);

		if ($item['gravity'] === Item::GRAVITY_UNKNOWN) {
			$this->logger->info('Unknown gravity for verb', ['verb' => $item['verb']]);
		}

		$default = [
			'url'   => $item['author-link'], 'name' => $item['author-name'],
			'photo' => $item['author-avatar'], 'network' => $item['network']
		];
		$item['author-id'] = ($item['author-id'] ?? 0) ?: Contact::getIdForURL($item['author-link'], 0, null, $default);

		$default = [
			'url'   => $item['owner-link'], 'name' => $item['owner-name'],
			'photo' => $item['owner-avatar'], 'network' => $item['network']
		];
		$item['owner-id'] = ($item['owner-id'] ?? 0) ?: Contact::getIdForURL($item['owner-link'], 0, null, $default);

		$item['post-reason'] = Item::getPostReason($item);

		return $item;
	}

	/**
	 * Fetch top-level parent data for the given item array
	 *
	 * @param array $item
	 * @return array item array with parent data
	 * @throws \Exception
	 */
	public function getTopLevelParent(array $item): array
	{
		$fields = [
			'uid', 'uri', 'parent-uri', 'id', 'deleted',
			'uri-id', 'parent-uri-id', 'restrictions', 'verb',
			'allow_cid', 'allow_gid', 'deny_cid', 'deny_gid',
			'wall', 'private', 'origin', 'author-id'
		];
		$condition = ['uri-id' => [$item['thr-parent-id'], $item['parent-uri-id']], 'uid' => $item['uid']];
		$params    = ['order' => ['id' => false]];
		$parent    = Post::selectFirst($fields, $condition, $params);

		if (!$this->database->isResult($parent) && Post::exists(['uri-id' => [$item['thr-parent-id'], $item['parent-uri-id']], 'uid' => 0])) {
			$stored = Item::storeForUserByUriId($item['thr-parent-id'], $item['uid'], ['post-reason' => Item::PR_COMPLETION]);
			if (!$stored && ($item['thr-parent-id'] != $item['parent-uri-id'])) {
				$stored = Item::storeForUserByUriId($item['parent-uri-id'], $item['uid'], ['post-reason' => Item::PR_COMPLETION]);
			}
			if ($stored) {
				$this->logger->info('Stored thread parent item for user', ['uri-id' => $item['thr-parent-id'], 'uid' => $item['uid'], 'stored' => $stored]);
				$parent = Post::selectFirst($fields, $condition, $params);
			}
		}

		if (!$this->database->isResult($parent)) {
			$this->logger->notice('item parent was not found - ignoring item', ['uri-id' => $item['uri-id'], 'thr-parent-id' => $item['thr-parent-id'], 'uid' => $item['uid']]);
			return [];
		}

		if ($this->hasRestrictions($item, $parent['author-id'], $parent['restrictions'])) {
			$this->logger->notice('Restrictions apply - ignoring item', ['restrictions' => $parent['restrictions'], 'verb' => $parent['verb'], 'uri-id' => $item['uri-id'], 'thr-parent-id' => $item['thr-parent-id'], 'uid' => $item['uid']]);
			return [];
		}

		if ($parent['uri-id'] == $parent['parent-uri-id']) {
			return $parent;
		}

		$condition = [
			'uri-id'        => $parent['parent-uri-id'],
			'parent-uri-id' => $parent['parent-uri-id'],
			'uid'           => $parent['uid']
		];
		$params          = ['order' => ['id' => false]];
		$toplevel_parent = Post::selectFirst($fields, $condition, $params);

		if (!$this->database->isResult($toplevel_parent) && $item['origin']) {
			$stored = Item::storeForUserByUriId($item['parent-uri-id'], $item['uid'], ['post-reason' => Item::PR_COMPLETION]);
			$this->logger->info('Stored parent item for user', ['uri-id' => $item['parent-uri-id'], 'uid' => $item['uid'], 'stored' => $stored]);
			$toplevel_parent = Post::selectFirst($fields, $condition, $params);
		}

		if (!$this->database->isResult($toplevel_parent)) {
			$this->logger->notice('item top level parent was not found - ignoring item', ['parent-uri-id' => $parent['parent-uri-id'], 'uid' => $parent['uid']]);
			return [];
		}

		return $toplevel_parent;
	}

	public function handleToplevelParent(array $item, array $toplevel_parent, bool $defined_permissions): array
	{
		$parent_id             = (int) $toplevel_parent['id'];
		$item['parent-uri']    = $toplevel_parent['uri'];
		$item['parent-uri-id'] = $toplevel_parent['uri-id'];
		$item['deleted']       = $toplevel_parent['deleted'];
		$item['wall']          = $toplevel_parent['wall'];

		// Reshares have to keep their permissions to allow groups to work
		if (!$defined_permissions && (!$item['origin'] || ($item['verb'] != Activity::ANNOUNCE))) {
			// Don't store the permissions on pure AP posts
			$store_permissions = ($item['network'] != Protocol::ACTIVITYPUB) || $item['origin'] || !empty($item['diaspora_signed_text']);
			$item['allow_cid'] = $store_permissions ? $toplevel_parent['allow_cid'] : '';
			$item['allow_gid'] = $store_permissions ? $toplevel_parent['allow_gid'] : '';
			$item['deny_cid']  = $store_permissions ? $toplevel_parent['deny_cid'] : '';
			$item['deny_gid']  = $store_permissions ? $toplevel_parent['deny_gid'] : '';
		}

		// Don't federate received participation messages
		if ($item['verb'] != Activity::FOLLOW) {
			$item['wall'] = $toplevel_parent['wall'];
		} else {
			$item['wall'] = false;
			// Participations are technical messages, so they are set to "seen" automatically
			$item['unseen'] = false;
		}

		/*
		 * If the parent is private, force privacy for the entire conversation
		 * This differs from the above settings as it subtly allows comments from
		 * email correspondents to be private even if the overall thread is not.
		 */
		if (!$defined_permissions && $toplevel_parent['private']) {
			$item['private'] = $toplevel_parent['private'];
		}

		// If its a post that originated here then tag the thread as "mention"
		if ($item['origin'] && $item['uid']) {
			$this->database->update('post-thread-user', ['mention' => true], ['uri-id' => $item['parent-uri-id'], 'uid' => $item['uid']]);
			$this->logger->info('tagged thread as mention', ['parent' => $parent_id, 'parent-uri-id' => $item['parent-uri-id'], 'uid' => $item['uid']]);
		}

		// Update the contact relations
		Contact\Relation::store($toplevel_parent['author-id'], $item['author-id'], $item['created']);

		return $item;
	}

	private function hasRestrictions(array $item, int $author_id, int $restrictions = null): bool
	{
		if (empty($restrictions) || ($author_id == $item['author-id'])) {
			return false;
		}

		// We only have to apply restrictions if the post originates from our server or is federated.
		// Every other time we can trust the remote system.
		if (!in_array($item['network'], Protocol::FEDERATED) && !$item['origin']) {
			return false;
		}

		if (($restrictions & Item::CANT_REPLY) && ($item['verb'] == Activity::POST)) {
			return true;
		}

		if (($restrictions & Item::CANT_ANNOUNCE) && ($item['verb'] == Activity::ANNOUNCE)) {
			return true;
		}

		if (($restrictions & Item::CANT_LIKE) && in_array($item['verb'], [Activity::LIKE, Activity::DISLIKE, Activity::ATTEND, Activity::ATTENDMAYBE, Activity::ATTENDNO])) {
			return true;
		}

		return false;
	}

	/**
	 * Get the gravity for the given item array
	 *
	 * @return int gravity
	 */
	private function getGravity(array $item): int
	{
		if (isset($item['gravity'])) {
			return intval($item['gravity']);
		} elseif ($item['parent-uri-id'] === $item['uri-id']) {
			return Item::GRAVITY_PARENT;
		} elseif ($this->activity->match($item['verb'], Activity::POST)) {
			return Item::GRAVITY_COMMENT;
		} elseif ($this->activity->match($item['verb'], Activity::FOLLOW)) {
			return Item::GRAVITY_ACTIVITY;
		} elseif ($this->activity->match($item['verb'], Activity::ANNOUNCE)) {
			return Item::GRAVITY_ACTIVITY;
		}

		return Item::GRAVITY_UNKNOWN;   // Should not happen
	}
}
