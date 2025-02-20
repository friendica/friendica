<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Model;

use Friendica\Contact\LocalRelationship\Entity\LocalRelationship;
use Friendica\Content\ContactSelector;
use Friendica\Content\Image;
use Friendica\Content\Post\Collection\PostMedias;
use Friendica\Content\Post\Entity\PostMedia;
use Friendica\Content\Text\BBCode;
use Friendica\Content\Text\HTML;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Post\Category;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;
use Friendica\Network\HTTPClient\Client\HttpClientOptions;
use Friendica\Network\HTTPClient\Client\HttpClientRequest;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Network\HTTPException\ServiceUnavailableException;
use Friendica\Protocol\Activity;
use Friendica\Protocol\ActivityPub;
use Friendica\Protocol\Delivery;
use Friendica\Protocol\Diaspora;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\HTTPSignature;
use Friendica\Util\Map;
use Friendica\Util\Network;
use Friendica\Util\Proxy;
use Friendica\Util\Strings;
use Friendica\Util\Temporal;
use GuzzleHttp\Psr7\Uri;
use LanguageDetection\Language;

class Item
{
	// Posting types, inspired by https://www.w3.org/TR/activitystreams-vocabulary/#object-types
	const PT_ARTICLE       = 0;
	const PT_NOTE          = 1;
	const PT_PAGE          = 2;
	const PT_IMAGE         = 16;
	const PT_AUDIO         = 17;
	const PT_VIDEO         = 18;
	const PT_DOCUMENT      = 19;
	const PT_EVENT         = 32;
	const PT_POLL          = 33;
	const PT_PERSONAL_NOTE = 128;

	// Posting reasons (Why had a post been stored for a user?)
	const PR_NONE         = 0;
	const PR_TAG          = 64;
	const PR_TO           = 65;
	const PR_CC           = 66;
	const PR_BTO          = 67;
	const PR_BCC          = 68;
	const PR_FOLLOWER     = 69;
	const PR_ANNOUNCEMENT = 70;
	const PR_COMMENT      = 71;
	const PR_STORED       = 72;
	const PR_GLOBAL       = 73;
	const PR_RELAY        = 74;
	const PR_FETCHED      = 75;
	const PR_COMPLETION   = 76;
	const PR_DIRECT       = 77;
	const PR_ACTIVITY     = 78;
	const PR_DISTRIBUTE   = 79;
	const PR_PUSHED       = 80;
	const PR_LOCAL        = 81;
	const PR_AUDIENCE     = 82;

	// system.accept_only_sharer setting values
	const COMPLETION_NONE    = 1;
	const COMPLETION_COMMENT = 0;
	const COMPLETION_LIKE    = 2;

	// Field list that is used to display the items
	const DISPLAY_FIELDLIST = [
		'uid', 'id', 'parent', 'guid', 'network', 'protocol', 'gravity',
		'uri-id', 'uri', 'thr-parent-id', 'thr-parent', 'parent-uri-id', 'parent-uri', 'conversation',
		'commented', 'created', 'edited', 'received', 'verb', 'object-type', 'postopts', 'plink',
		'wall', 'private', 'starred', 'origin', 'parent-origin', 'title', 'body', 'language', 'sensitive',
		'content-warning', 'location', 'coord', 'app', 'rendered-hash', 'rendered-html', 'object',
		'quote-uri', 'quote-uri-id', 'allow_cid', 'allow_gid', 'deny_cid', 'deny_gid', 'mention', 'global',
		'author-id', 'author-link', 'author-alias', 'author-name', 'author-avatar', 'author-network', 'author-updated', 'author-gsid', 'author-baseurl', 'author-addr', 'author-uri-id',
		'owner-id', 'owner-link', 'owner-alias', 'owner-name', 'owner-avatar', 'owner-network', 'owner-contact-type', 'owner-updated', 'owner-gsid',
		'causer-id', 'causer-link', 'causer-alias', 'causer-name', 'causer-avatar', 'causer-contact-type', 'causer-network', 'causer-gsid',
		'contact-id', 'contact-uid', 'contact-link', 'contact-name', 'contact-avatar',
		'writable', 'restrictions', 'self', 'cid', 'alias', 'post-reason',
		'event-created', 'event-edited', 'event-start', 'event-finish',
		'event-summary', 'event-desc', 'event-location', 'event-type',
		'event-nofinish', 'event-ignore', 'event-id',
		'question-id', 'question-multiple', 'question-voters', 'question-end-time',
		'has-categories', 'has-media',
		'delivery_queue_count', 'delivery_queue_done', 'delivery_queue_failed'
	];

	// Field list that is used to deliver items via the protocols
	const DELIVER_FIELDLIST = [
		'uid', 'id', 'parent', 'uri-id', 'uri', 'thr-parent', 'parent-uri', 'guid',
		'parent-guid', 'conversation', 'received', 'created', 'edited', 'verb', 'object-type', 'object', 'target',
		'private', 'title', 'content-warning', 'body', 'raw-body', 'language', 'location', 'coord', 'app', 'sensitive',
		'inform', 'deleted', 'extid', 'post-type', 'post-reason', 'gravity',
		'allow_cid', 'allow_gid', 'deny_cid', 'deny_gid',
		'author-id', 'author-addr', 'author-link', 'author-name', 'author-avatar', 'owner-id', 'owner-link', 'contact-uid',
		'signed_text', 'network', 'wall', 'contact-id', 'plink', 'origin',
		'thr-parent-id', 'parent-uri-id', 'quote-uri', 'quote-uri-id', 'postopts', 'pubmail',
		'event-created', 'event-edited', 'event-start', 'event-finish',
		'event-summary', 'event-desc', 'event-location', 'event-type',
		'event-nofinish', 'event-ignore', 'event-id'
	];

	// All fields in the item table
	const ITEM_FIELDLIST = [
		'id', 'uid', 'parent', 'uri', 'parent-uri', 'thr-parent',
		'guid', 'uri-id', 'parent-uri-id', 'thr-parent-id', 'conversation', 'vid',
		'quote-uri', 'quote-uri-id', 'contact-id', 'wall', 'gravity', 'extid', 'psid',
		'created', 'edited', 'commented', 'received', 'changed', 'verb',
		'postopts', 'plink', 'resource-id', 'event-id', 'inform',
		'allow_cid', 'allow_gid', 'deny_cid', 'deny_gid', 'post-type', 'post-reason',
		'private', 'pubmail', 'visible', 'starred',
		'unseen', 'deleted', 'origin', 'mention', 'global', 'network',
		'title', 'content-warning', 'body', 'language', 'location', 'coord', 'app',
		'rendered-hash', 'rendered-html', 'object-type', 'object', 'target-type', 'target',
		'author-id', 'author-link', 'author-name', 'author-avatar', 'author-network',
		'owner-id', 'owner-link', 'owner-name', 'owner-avatar', 'causer-id'
	];

	// List of all verbs that don't need additional content data.
	// Never reorder or remove entries from this list. Just add new ones at the end, if needed.
	const ACTIVITIES = [
		Activity::LIKE, Activity::DISLIKE,
		Activity::ATTEND, Activity::ATTENDNO, Activity::ATTENDMAYBE,
		Activity::FOLLOW,
		Activity::ANNOUNCE
	];

	// Privacy levels
	const PUBLIC   = 0;
	const PRIVATE  = 1;
	const UNLISTED = 2;

	// Item weight for query ordering
	const GRAVITY_PARENT   = 0;
	const GRAVITY_ACTIVITY = 3;
	const GRAVITY_COMMENT  = 6;
	const GRAVITY_UNKNOWN  = 9;

	// Restrictions
	const CANT_REPLY    = 1;
	const CANT_LIKE     = 2;
	const CANT_ANNOUNCE = 4;

	/**
	 * Update existing item entries
	 *
	 * @param array $fields    The fields that are to be changed
	 * @param array $condition The condition for finding the item entries
	 *
	 * In the future we may have to change permissions as well.
	 * Then we had to add the user id as third parameter.
	 *
	 * A return value of "0" doesn't mean an error - but that 0 rows had been changed.
	 *
	 * @return integer|boolean number of affected rows - or "false" if there was an error
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function update(array $fields, array $condition)
	{
		if (empty($condition) || empty($fields)) {
			return false;
		}

		if (isset($fields['extid'])) {
			$fields['external-id'] = ItemURI::getIdByURI($fields['extid']);
		}

		if (!empty($fields['replies'])) {
			$fields['replies-id'] = ItemURI::getIdByURI($fields['replies']);
		}

		if (!empty($fields['verb'])) {
			$fields['vid'] = Verb::getID($fields['verb']);
		}

		$previous = [];
		if (!empty($fields['edited'])) {
			$previous = Post::selectFirst(['edited'], $condition);
		}

		if (!empty($fields['body'])) {
			$fields['body'] = self::setHashtags($fields['body']);
		}

		$rows = Post::update($fields, $condition);
		if (is_bool($rows)) {
			return $rows;
		}

		// We only need to call the line by line update for specific fields
		if (
			empty($fields['body']) && empty($fields['file']) &&
			empty($fields['attach']) && empty($fields['edited'])
		) {
			return $rows;
		}

		DI::logger()->info('Updating per single row method', ['fields' => $fields, 'condition' => $condition]);

		$items = Post::select(['id', 'origin', 'uri-id', 'uid', 'author-network', 'quote-uri-id'], $condition);

		$notify_items = [];

		while ($item = DBA::fetch($items)) {
			if (!empty($fields['body'])) {
				if (!empty($item['quote-uri-id'])) {
					$fields['body'] = BBCode::removeSharedData($fields['body']);

					if (!empty($fields['raw-body'])) {
						$fields['raw-body'] = BBCode::removeSharedData($fields['raw-body']);
					}
				}

				$content_fields = ['raw-body' => trim($fields['raw-body'] ?? $fields['body'])];

				if ($item['origin'] && empty($item['quote-uri-id'])) {
					$quote_id = Post\Media::getActivityUriId($item['uri-id']);
					if (!empty($quote_id)) {
						DI::logger()->notice('Found attached post', ['id' => $quote_id, 'guid' => $item['guid'], 'uri-id' => $item['uri-id']]);
						$content_fields['quote-uri-id'] = $quote_id;
					}
				}

				// Remove all media attachments from the body and store them in the post-media table
				// @todo On shared postings (Diaspora style and commented reshare) don't fetch content from the shared part
				$content_fields['raw-body'] = Post\Media::insertFromBody($item['uri-id'], $content_fields['raw-body']);
				$content_fields['raw-body'] = self::setHashtags($content_fields['raw-body']);

				Post\Media::insertFromRelevantUrl($item['uri-id'], $content_fields['raw-body'], $fields['body'], $item['author-network']);

				Post\Media::insertFromAttachmentData($item['uri-id'], $fields['body']);
				$content_fields['raw-body'] = BBCode::removeAttachment($content_fields['raw-body']);

				Post\Content::update($item['uri-id'], $content_fields);

				$searchtext = Post\Engagement::getSearchTextForUriId($item['uri-id'], true);
				DBA::update('post-engagement', ['searchtext' => $searchtext], ['uri-id' => $item['uri-id']]);
				Post\SearchIndex::update($item['uri-id']);
			}

			if (!empty($fields['file'])) {
				Post\Category::storeTextByURIId($item['uri-id'], $item['uid'], $fields['file']);
			}

			if (!empty($fields['attach'])) {
				Post\Media::insertFromAttachment($item['uri-id'], $fields['attach']);
			}

			// We only need to notify others when it is an original entry from us.
			// Only call the notifier when the item had been edited and records had been changed.
			if ($item['origin'] && !empty($fields['edited']) && ($previous['edited'] != $fields['edited'])) {
				$notify_items[] = $item['id'];
			}
		}

		DBA::close($items);

		foreach ($notify_items as $notify_item) {
			$post = Post::selectFirst([], ['id' => $notify_item]);

			if ($post['gravity'] != self::GRAVITY_PARENT) {
				$signed = Diaspora::createCommentSignature($post);
				if (!empty($signed)) {
					DBA::replace('diaspora-interaction', ['uri-id' => $post['uri-id'], 'interaction' => json_encode($signed)]);
				}
			}

			Worker::add(Worker::PRIORITY_HIGH, 'Notifier', Delivery::POST, (int)$post['uri-id'], (int)$post['uid']);
		}

		return $rows;
	}

	/**
	 * Delete an item and notify others about it - if it was ours
	 *
	 * @param array   $condition The condition for finding the item entries
	 * @param integer $priority  Priority for the notification
	 * @return void
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function markForDeletion(array $condition, int $priority = Worker::PRIORITY_HIGH)
	{
		$items = Post::select(['id'], $condition);
		while ($item = Post::fetch($items)) {
			self::markForDeletionById($item['id'], $priority);
		}
		DBA::close($items);
	}

	/**
	 * Delete an item for an user and notify others about it - if it was ours
	 *
	 * @param array   $condition The condition for finding the item entries
	 * @param integer $uid       User who wants to delete this item
	 * @return void
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function deleteForUser(array $condition, int $uid)
	{
		if ($uid == 0) {
			return;
		}

		$items = Post::select(['id', 'uid', 'uri-id'], $condition);
		while ($item = Post::fetch($items)) {
			if (in_array($item['uid'], [$uid, 0])) {
				Post\User::update($item['uri-id'], $uid, ['hidden' => true], true);
				Post\ThreadUser::update($item['uri-id'], $uid, ['hidden' => true], true);
			}

			if ($item['uid'] == $uid) {
				self::markForDeletionById($item['id'], Worker::PRIORITY_HIGH);
			} elseif ($item['uid'] != 0) {
				DI::logger()->warning('Wrong ownership. Not deleting item', ['id' => $item['id']]);
			}
		}
		DBA::close($items);
	}

	/**
	 * Mark an item for deletion, delete related data and notify others about it - if it was ours
	 *
	 * @param integer $item_id
	 * @param integer $priority Priority for the notification
	 * @return boolean success
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function markForDeletionById(int $item_id, int $priority = Worker::PRIORITY_HIGH): bool
	{
		DI::logger()->info('Mark item for deletion by id', ['id' => $item_id]);
		// locate item to be deleted
		$fields = [
			'id', 'uri', 'uri-id', 'uid', 'parent', 'parent-uri-id', 'origin',
			'thr-parent-id', 'deleted', 'resource-id', 'event-id', 'vid', 'body',
			'verb', 'object-type', 'object', 'target', 'contact-id', 'psid', 'gravity'
		];
		$item = Post::selectFirst($fields, ['id' => $item_id]);
		if (!DBA::isResult($item)) {
			DI::logger()->info('Item not found.', ['id' => $item_id]);
			return false;
		}

		if ($item['deleted']) {
			DI::logger()->info('Item has already been marked for deletion.', ['id' => $item_id]);
			return false;
		}

		$parent = Post::selectFirst(['origin'], ['id' => $item['parent']]);
		if (!DBA::isResult($parent)) {
			$parent = ['origin' => false];
		}

		// clean up categories and tags so they don't end up as orphans
		Post\Category::deleteByURIId($item['uri-id'], $item['uid']);

		/*
		 * If item is a link to a photo resource, nuke all the associated photos
		 * (visitors will not have photo resources)
		 * This only applies to photos uploaded from the photos page. Photos inserted into a post do not
		 * generate a resource-id and therefore aren't intimately linked to the item.
		 */
		/// @TODO: this should first check if photo is used elsewhere
		if ($item['resource-id']) {
			Photo::delete(['resource-id' => $item['resource-id'], 'uid' => $item['uid']]);
		}

		// If item is a link to an event, delete the event.
		if (intval($item['event-id'])) {
			Event::delete($item['event-id']);
		}

		// Set the item to "deleted"
		$item_fields = ['deleted' => true, 'edited' => DateTimeFormat::utcNow(), 'changed' => DateTimeFormat::utcNow()];
		Post::update($item_fields, ['id' => $item['id']]);

		Post\Category::storeTextByURIId($item['uri-id'], $item['uid'], '');

		if (!Post::exists(["`uri-id` = ? AND `uid` != 0 AND NOT `deleted`", $item['uri-id']])) {
			self::markForDeletion(['uri-id' => $item['uri-id'], 'uid' => 0, 'deleted' => false], $priority);
		}

		Post\DeliveryData::delete($item['uri-id']);

		// If it's the parent of a comment thread, kill all the kids
		if ($item['gravity'] == self::GRAVITY_PARENT) {
			self::markForDeletion(['parent' => $item['parent'], 'deleted' => false], $priority);
		}

		if ($item['uid'] == 0 && $item['gravity'] == self::GRAVITY_PARENT) {
			$posts = DI::keyValue()->get('nodeinfo_total_posts') ?? 0;
			DI::keyValue()->set('nodeinfo_total_posts', $posts - 1);
		} elseif ($item['uid'] == 0 && $item['gravity'] == self::GRAVITY_COMMENT) {
			$comments = DI::keyValue()->get('nodeinfo_total_comments') ?? 0;
			DI::keyValue()->set('nodeinfo_total_comments', $comments - 1);
		}

		// Is it our comment and/or our thread?
		if (($item['origin'] || $parent['origin']) && ($item['uid'] != 0)) {
			if ($item['origin'] && $item['gravity'] == self::GRAVITY_PARENT) {
				$posts = DI::keyValue()->get('nodeinfo_local_posts') ?? 0;
				DI::keyValue()->set('nodeinfo_local_posts', $posts - 1);
			} elseif ($item['origin'] && $item['gravity'] == self::GRAVITY_COMMENT) {
				$comments = DI::keyValue()->get('nodeinfo_local_comments') ?? 0;
				DI::keyValue()->set('nodeinfo_local_comments', $comments - 1);
			}

			// When we delete the original post we will delete all existing copies on the server as well
			self::markForDeletion(['uri-id' => $item['uri-id'], 'deleted' => false], $priority);

			// send the notification upstream/downstream
			if ($priority) {
				Worker::add(['priority' => $priority, 'dont_fork' => true], 'Notifier', Delivery::DELETION, (int)$item['uri-id'], (int)$item['uid']);
			}
		} elseif ($item['uid'] != 0) {
			Post\User::update($item['uri-id'], $item['uid'], ['hidden' => true]);
			Post\ThreadUser::update($item['uri-id'], $item['uid'], ['hidden' => true]);
		}

		DI::notify()->deleteForItem($item['uri-id']);
		DI::notification()->deleteForItem($item['uri-id']);

		if (in_array($item['gravity'], [self::GRAVITY_ACTIVITY, self::GRAVITY_COMMENT])) {
			Post\Counts::update($item['thr-parent-id'], $item['parent-uri-id'], $item['vid'], $item['verb'], $item['body']);
		}

		DI::logger()->info('Item has been marked for deletion.', ['id' => $item_id]);

		return true;
	}

	/**
	 * Get guid from given item record
	 *
	 * @param array $item Item record
	 * @param bool $notify Whether to notify (?)
	 * @return string Guid
	 */
	public static function guid(array $item, bool $notify): string
	{
		if (!empty($item['guid'])) {
			return trim($item['guid']);
		}

		if ($notify) {
			// We have to avoid duplicates. So we create the GUID in form of a hash of the plink or uri.
			// We add the hash of our own host because our host is the original creator of the post.
			$prefix_host = DI::baseUrl()->getHost();
		} else {
			$prefix_host = '';

			// We are only storing the post so we create a GUID from the original hostname.
			if (!empty($item['author-link'])) {
				$parsed = parse_url($item['author-link']);
				if (!empty($parsed['host'])) {
					$prefix_host = $parsed['host'];
				}
			}

			if (empty($prefix_host) && !empty($item['plink'])) {
				$parsed = parse_url($item['plink']);
				if (!empty($parsed['host'])) {
					$prefix_host = $parsed['host'];
				}
			}

			if (empty($prefix_host) && !empty($item['uri'])) {
				$parsed = parse_url($item['uri']);
				if (!empty($parsed['host'])) {
					$prefix_host = $parsed['host'];
				}
			}

			// Is it in the format data@host.tld? - Used for mail contacts
			if (empty($prefix_host) && !empty($item['author-link']) && strstr($item['author-link'], '@')) {
				$mailparts   = explode('@', $item['author-link']);
				$prefix_host = array_pop($mailparts);
			}
		}

		if (!empty($item['plink'])) {
			$guid = self::guidFromUri($item['plink'], $prefix_host);
		} elseif (!empty($item['uri'])) {
			$guid = self::guidFromUri($item['uri'], $prefix_host);
		} else {
			$guid = System::createUUID(hash('crc32', $prefix_host));
		}

		return $guid;
	}

	/**
	 * Returns contact id from given item record
	 *
	 * @param array $item Item record
	 * @return int Contact id
	 */
	private static function contactId(array $item): int
	{
		if ($item['uid'] == 0) {
			return $item['owner-id'];
		}

		if ($item['origin']) {
			$owner = User::getOwnerDataById($item['uid']);
			return $owner['id'];
		}

		$contact_id      = 0;
		$user_contact_id = 0;
		foreach (['group-link', 'causer-link', 'owner-link', 'author-link'] as $field) {
			if (empty($item[$field])) {
				continue;
			}
			if (!$user_contact_id && Contact::isSharingByURL($item[$field], $item['uid'], true)) {
				$user_contact_id = Contact::getIdForURL($item[$field], $item['uid']);
			} elseif (!$contact_id) {
				$contact_id = Contact::getIdForURL($item[$field]);
			}
		}

		if ($user_contact_id) {
			return $user_contact_id;
		}

		if (!empty($item['causer-id']) && Contact::isSharing($item['causer-id'], $item['uid'], true)) {
			$ucid = Contact::getUserContactId($item['causer-id'], $item['uid']);
			if ($ucid) {
				return $ucid;
			}
		}

		if ($contact_id) {
			return $contact_id;
		}

		DI::logger()->warning('contact-id could not be fetched, using self contact instead.', ['uid' => $item['uid'], 'item' => $item]);
		$self = Contact::selectFirst(['id'], ['self' => true, 'uid' => $item['uid']]);
		return $self['id'];
	}

	/**
	 * Write an item array into a spool file to be inserted later.
	 * This command is called whenever there are issues storing an item.
	 *
	 * @param array $item The item fields that are to be inserted
	 * @throws \Exception
	 */
	private static function spool(array $item)
	{
		// Now we store the data in the spool directory
		// We use "microtime" to keep the arrival order and "mt_rand" to avoid duplicates
		$file = 'item-' . round(microtime(true) * 10000) . '-' . mt_rand() . '.msg';

		$spoolpath = System::getSpoolPath();
		if ($spoolpath != '') {
			$spool = $spoolpath . '/' . $file;

			file_put_contents($spool, json_encode($item));
			DI::logger()->warning("Item wasn't stored - Item was spooled into file", ['file' => $file]);
		}
	}

	/**
	 * Check if the item array is valid
	 *
	 * @param array $item Item record
	 * @return boolean item is valid
	 */
	public static function isValid(array $item): bool
	{
		// When there is no content then we don't post it
		if (($item['body'] . $item['title'] == '') && empty($item['quote-uri-id']) && empty($item['attachments']) && (empty($item['uri-id']) || !Post\Media::existsByURIId($item['uri-id']))) {
			DI::logger()->notice('No body, no title.');
			return false;
		}

		if (!empty($item['uid'])) {
			$owner = User::getOwnerDataById($item['uid'], false);
			if (!$owner) {
				DI::logger()->warning('Missing item user owner data', ['uid' => $item['uid']]);
				return false;
			}

			if ($owner['account_expired'] || $owner['account_removed']) {
				DI::logger()->notice('Item user has been deleted/expired/removed', ['uid' => $item['uid'], 'deleted' => $owner['deleted'], 'account_expired' => $owner['account_expired'], 'account_removed' => $owner['account_removed']]);
				return false;
			}
		}

		if (!empty($item['author-id']) && Contact::isBlocked($item['author-id'])) {
			DI::logger()->notice('Author is blocked node-wide', ['author-link' => $item['author-link'], 'item-uri' => $item['uri']]);
			return false;
		}

		if (!empty($item['author-link']) && Network::isUrlBlocked($item['author-link'])) {
			DI::logger()->notice('Author server is blocked', ['author-link' => $item['author-link'], 'item-uri' => $item['uri']]);
			return false;
		}

		if (!empty($item['owner-id']) && Contact::isBlocked($item['owner-id'])) {
			DI::logger()->notice('Owner is blocked node-wide', ['owner-link' => $item['owner-link'], 'item-uri' => $item['uri']]);
			return false;
		}

		if (!empty($item['owner-link']) && Network::isUrlBlocked($item['owner-link'])) {
			DI::logger()->notice('Owner server is blocked', ['owner-link' => $item['owner-link'], 'item-uri' => $item['uri']]);
			return false;
		}

		if ($item['verb'] == Activity::FOLLOW) {
			if (!$item['origin'] && ($item['author-id'] == Contact::getPublicIdByUserId($item['uid']))) {
				// Our own follow request can be relayed to us. We don't store it to avoid notification chaos.
				DI::logger()->info("Follow: Don't store not origin follow request", ['parent-uri' => $item['parent-uri']]);
				return false;
			}

			$condition = [
				'verb'       => Activity::FOLLOW, 'uid' => $item['uid'],
				'parent-uri' => $item['parent-uri'], 'author-id' => $item['author-id']
			];
			if (Post::exists($condition)) {
				// It happens that we receive multiple follow requests by the same author - we only store one.
				DI::logger()->info('Follow: Found existing follow request from author', ['author-id' => $item['author-id'], 'parent-uri' => $item['parent-uri']]);
				return false;
			}
		}

		return true;
	}

	/**
	 * Fetch the uri-id of the parent for the given uri-id
	 *
	 * @param integer $uriid
	 * @return integer
	 */
	public static function getParent(int $uriid): int
	{
		$thread_parent = Post::selectFirstPost(['thr-parent-id', 'gravity'], ['uri-id' => $uriid]);
		if (empty($thread_parent)) {
			return 0;
		}

		if ($thread_parent['gravity'] == Item::GRAVITY_PARENT) {
			return $uriid;
		}

		return self::getParent($thread_parent['thr-parent-id']);
	}

	/**
	 * Inserts item record
	 *
	 * @param array $item Item array to be inserted
	 * @param int   $notify Notification (type?)
	 * @param bool  $post_local (???)
	 * @return int Zero means error, otherwise primary key (id) is being returned
	 */
	public static function insert(array $item, int $notify = 0, bool $post_local = true): int
	{
		$itemHelper = new ItemHelper(
			DI::contentItem(),
			DI::activity(),
			DI::logger(),
			DI::dba(),
			DI::baseUrl(),
		);

		$orig_item = $item;

		$priority = Worker::PRIORITY_HIGH;

		$copy_permissions = false;

		// If it is a posting where users should get notifications, then define it as wall posting
		if ($notify) {
			$item = $itemHelper->prepareOriginPost($item);

			if (is_int($notify) && in_array($notify, Worker::PRIORITIES)) {
				$priority = $notify;
			}

			// Mastodon style API visibility
			$copy_permissions = ($item['visibility'] ?? 'private') == 'private';
			unset($item['visibility']);
		} else {
			$item['network'] = trim(($item['network'] ?? '') ?: Protocol::PHANTOM);
		}

		$item = $itemHelper->prepareItemData($item, (bool) $notify);

		// Store conversation data
		$source = $item['source'] ?? '';
		unset($item['conversation-uri']);
		unset($item['conversation-href']);
		unset($item['source']);

		/*
		 * Do we already have this item?
		 * We have to check several networks since Friendica posts could be repeated
		 * via Diaspora.
		 */
		$duplicate = $itemHelper->getDuplicateID($item);
		if ($duplicate) {
			return $duplicate;
		}

		// Additional duplicate checks
		/// @todo Check why the first duplication check returns the item number and the second a 0
		if ($itemHelper->isDuplicate($item)) {
			return 0;
		}

		if (!isset($item['post-type'])) {
			$item['post-type'] = empty($item['title']) ? self::PT_NOTE : self::PT_ARTICLE;
		}

		$defined_permissions = isset($item['allow_cid']) && isset($item['allow_gid']) && isset($item['deny_cid']) && isset($item['deny_gid']) && isset($item['private']);

		$uid = intval($item['uid']);

		// Communities aren't working with the Diaspora protocol
		if (($uid != 0) && ($item['network'] == Protocol::DIASPORA)) {
			$user = User::getById($uid, ['account-type']);
			if ($user['account-type'] == Contact::TYPE_COMMUNITY) {
				DI::logger()->info('Community posts are not supported via Diaspora');
				return 0;
			}
		}

		$item = $itemHelper->validateItemData($item);

		// Ensure that there is an avatar cache
		Contact::checkAvatarCache($item['author-id']);
		Contact::checkAvatarCache($item['owner-id']);

		$item['contact-id'] = self::contactId($item);

		if (
			!empty($item['direction']) && in_array($item['direction'], [Conversation::PUSH, Conversation::RELAY]) &&
			empty($item['origin']) && DI::contentItem()->isTooOld($item['created'], $item['uid'])
		) {
			DI::logger()->info('Item is too old', ['item' => $item]);
			return 0;
		}

		if (!self::isValid($item)) {
			return 0;
		}

		if ($item['gravity'] !== self::GRAVITY_PARENT) {
			$toplevel_parent = $itemHelper->getTopLevelParent($item);
			if (empty($toplevel_parent)) {
				return 0;
			}

			$parent_id     = (int) $toplevel_parent['id'];
			$item          = $itemHelper->handleToplevelParent($item, $toplevel_parent, $defined_permissions);
			$parent_origin = $toplevel_parent['origin'];
		} else {
			$parent_id     = 0;
			$parent_origin = $item['origin'];

			if ($item['wall'] && empty($item['context'])) {
				$item['context'] = $item['parent-uri'] . '#context';
			}

			if ($item['wall'] && empty($item['conversation'])) {
				$item['conversation'] = $item['parent-uri'] . '#context';
			}
		}

		if ($item['origin']) {
			if (
				Photo::setPermissionFromBody($item['body'], $item['uid'], $item['contact-id'], $item['allow_cid'], $item['allow_gid'], $item['deny_cid'], $item['deny_gid'])
				&& ($item['object-type'] != Activity\ObjectType::EVENT)
			) {
				$item['object-type'] = Activity\ObjectType::IMAGE;
			}
		}

		$item['parent-uri-id'] = ItemURI::getIdByURI($item['parent-uri']);
		$item['thr-parent-id'] = ItemURI::getIdByURI($item['thr-parent']);

		if (!empty($item['conversation']) && empty($item['conversation-id'])) {
			$item['conversation-id'] = ItemURI::getIdByURI($item['conversation']);
		}

		if (!empty($item['context']) && empty($item['context-id'])) {
			$item['context-id'] = ItemURI::getIdByURI($item['context']);
		}

		// Is this item available in the global items (with uid=0)?
		if ($item['uid'] == 0) {
			$item['global'] = true;

			// Set the global flag on all items if this was a global item entry
			Post::update(['global' => true], ['uri-id' => $item['uri-id']]);
		} else {
			$item['global'] = Post::exists(['uid' => 0, 'uri-id' => $item['uri-id']]);
		}

		// ACL settings
		if (!$defined_permissions && !empty($item['allow_cid'] . $item['allow_gid'] . $item['deny_cid'] . $item['deny_gid'])) {
			$item['private'] = self::PRIVATE;
		}

		if ($notify && $post_local) {
			$item['edit']   = false;
			$item['parent'] = $parent_id;

			// Trigger automatic reactions for addons
			if (!isset($item['api_source'])) {
				$item['api_source'] = true;
			}

			// We have to tell the hooks who we are - this really should be improved
			if (!DI::userSession()->getLocalUserId()) {
				$_SESSION['authenticated'] = true;
				$_SESSION['uid']           = $uid;
				$dummy_session             = true;
			} else {
				$dummy_session = false;
			}

			Hook::callAll('post_local', $item);

			if ($dummy_session) {
				unset($_SESSION['authenticated']);
				unset($_SESSION['uid']);
			}
		} elseif (!$notify) {
			Hook::callAll('post_remote', $item);
		}

		if (!empty($item['cancel'])) {
			DI::logger()->notice('post cancelled by addon.');
			return 0;
		}

		if (empty($item['vid']) && !empty($item['verb'])) {
			$item['vid'] = Verb::getID($item['verb']);
		}

		// Creates or assigns the permission set
		$item['psid'] = DI::permissionSet()->selectOrCreate(
			DI::permissionSetFactory()->createFromString(
				$item['uid'],
				$item['allow_cid'],
				$item['allow_gid'],
				$item['deny_cid'],
				$item['deny_gid']
			)
		)->id;

		if (!empty($item['extid'])) {
			$item['external-id'] = ItemURI::getIdByURI($item['extid']);
		}

		if (!empty($item['replies'])) {
			$item['replies-id'] = ItemURI::getIdByURI($item['replies']);
		}

		if ($item['verb'] == Activity::ANNOUNCE) {
			self::setOwnerforResharedItem($item);
		}

		if (isset($item['attachments'])) {
			foreach ($item['attachments'] as $attachment) {
				$attachment['uri-id'] = $item['uri-id'];
				Post\Media::insert($attachment);
			}
			unset($item['attachments']);
		}

		if (empty($item['quote-uri-id'])) {
			$quote_id = self::getQuoteUriId($item['body']);
			if (!empty($quote_id)) {
				// This is one of these "should not happen" situations.
				// The protocol implementations should already have done this job.
				DI::logger()->notice('Quote-uri-id detected in post', ['id' => $quote_id, 'guid' => $item['guid'], 'uri-id' => $item['uri-id']]);
				$item['quote-uri-id'] = $quote_id;
			}
		}

		if (!empty($item['quote-uri-id']) && ($item['quote-uri-id'] == $item['uri-id'])) {
			DI::logger()->info('Quote-Uri-Id is identical to Uri-Id', ['uri-id' => $item['uri-id'], 'guid' => $item['guid']]);
			unset($item['quote-uri-id']);
		}

		if (!empty($item['quote-uri-id'])) {
			$item['raw-body'] = BBCode::removeSharedData($item['raw-body']);
			$item['body']     = BBCode::removeSharedData($item['body']);
		}

		// Remove all media attachments from the body and store them in the post-media table
		$item['raw-body'] = Post\Media::insertFromBody($item['uri-id'], $item['raw-body']);
		$item['raw-body'] = self::setHashtags($item['raw-body']);

		$author = Contact::getById($item['author-id'], ['network']);
		Post\Media::insertFromRelevantUrl($item['uri-id'], $item['raw-body'], $item['body'], $author['network'] ?? '');

		Post\Media::insertFromAttachmentData($item['uri-id'], $item['body']);
		$item['body']     = BBCode::removeAttachment($item['body']);
		$item['raw-body'] = BBCode::removeAttachment($item['raw-body']);

		// Check for hashtags in the body and repair or add hashtag links
		$item['body'] = self::setHashtags($item['body']);

		$notify_type = Delivery::POST;

		// Filling item related side tables
		if (!empty($item['attach'])) {
			Post\Media::insertFromAttachment($item['uri-id'], $item['attach']);
		}

		if ($item['origin'] && empty($item['quote-uri-id'])) {
			$quote_id = Post\Media::getActivityUriId($item['uri-id']);
			if (!empty($quote_id)) {
				DI::logger()->notice('Found attached post', ['id' => $quote_id, 'guid' => $item['guid'], 'uri-id' => $item['uri-id']]);
				$item['quote-uri-id'] = $quote_id;
			}
		}

		if (empty($item['event-id'])) {
			unset($item['event-id']);

			$ev = Event::fromBBCode($item['body']);
			if ((!empty($ev['desc']) || !empty($ev['summary'])) && !empty($ev['start'])) {
				DI::logger()->info('Event found.');
				$ev['cid']       = $item['contact-id'];
				$ev['uid']       = $item['uid'];
				$ev['uri']       = $item['uri'];
				$ev['edited']    = $item['edited'];
				$ev['private']   = $item['private'];
				$ev['guid']      = $item['guid'];
				$ev['plink']     = $item['plink'];
				$ev['network']   = $item['network'];
				$ev['protocol']  = $item['protocol']  ?? Conversation::PARCEL_UNKNOWN;
				$ev['direction'] = $item['direction'] ?? Conversation::UNKNOWN;
				$ev['source']    = $item['source']    ?? '';

				$event = DBA::selectFirst('event', ['id'], ['uri' => $item['uri'], 'uid' => $item['uid']]);
				if (DBA::isResult($event)) {
					$ev['id'] = $event['id'];
				}

				$event_id = Event::store($ev);
				$item     = Event::getItemArrayForImportedId($event_id, $item);

				DI::logger()->info('Event was stored', ['id' => $event_id]);
			}
		}

		if (empty($item['causer-id'])) {
			unset($item['causer-id']);
		}

		if (in_array($item['network'], [Protocol::ACTIVITYPUB, Protocol::DFRN])) {
			$content_warning = BBCode::getAbstract($item['body'], Protocol::ACTIVITYPUB);
			if (!empty($content_warning) && empty($item['content-warning'])) {
				$item['content-warning'] = BBCode::toPlaintext($content_warning);
			}
		}

		// Store tags from the body if this hadn't been handled previously in the protocol classes
		if (!Tag::existsForPost($item['uri-id'])) {
			Tag::storeFromBody($item['uri-id'], $item['body']);
		}

		if (in_array($item['gravity'], [self::GRAVITY_PARENT, self::GRAVITY_COMMENT]) && (!isset($item['sensitive']) || is_null($item['sensitive']))) {
			$item['sensitive'] = Tag::existsTagForPost($item['uri-id'], 'nsfw');
		}

		$item['language'] = self::getLanguage($item);

		$inserted = Post::insert($item['uri-id'], $item);

		if ($item['gravity'] == self::GRAVITY_PARENT) {
			Post\Thread::insert($item['uri-id'], $item);
		}

		// The content of activities normally doesn't matter - except for likes from Misskey
		if (!in_array($item['verb'], self::ACTIVITIES) || in_array($item['verb'], [Activity::LIKE, Activity::DISLIKE]) && !empty($item['body']) && (mb_strlen($item['body']) == 1)) {
			Post\Content::insert($item['uri-id'], $item);
		}

		$item['parent'] = $parent_id;

		// Create Diaspora signature
		if ($item['origin'] && empty($item['diaspora_signed_text']) && ($item['gravity'] != self::GRAVITY_PARENT)) {
			$signed = Diaspora::createCommentSignature($item);
			if (!empty($signed)) {
				$item['diaspora_signed_text'] = json_encode($signed);
			}
		}

		if (!empty($item['diaspora_signed_text'])) {
			DBA::replace('diaspora-interaction', ['uri-id' => $item['uri-id'], 'interaction' => $item['diaspora_signed_text']]);
		}

		// Attached file links
		if (!empty($item['file'])) {
			Post\Category::storeTextByURIId($item['uri-id'], $item['uid'], $item['file']);
		}

		// Delivery relevant data
		$delivery_data = Post\DeliveryData::extractFields($item);

		if (!empty($item['origin']) || !empty($item['wall']) || !empty($delivery_data['postopts']) || !empty($delivery_data['inform'])) {
			Post\DeliveryData::insert($item['uri-id'], $delivery_data);
		}

		$condition = ['uri-id' => $item['uri-id'], 'uid' => $item['uid']];
		if (Post::exists($condition)) {
			DI::logger()->notice('Item is already inserted - aborting', $condition);
			return 0;
		}

		$post_user_id = Post\User::insert($item['uri-id'], $item['uid'], $item);
		if (!$post_user_id) {
			DI::logger()->notice('Post-User is already inserted - aborting', ['uid' => $item['uid'], 'uri-id' => $item['uri-id']]);
			return 0;
		}

		if ($item['gravity'] == self::GRAVITY_PARENT) {
			$item['post-user-id'] = $post_user_id;
			Post\ThreadUser::insert($item['uri-id'], $item['uid'], $item);
		}

		DI::logger()->notice('created item', ['post-id' => $post_user_id, 'uid' => $item['uid'], 'network' => $item['network'], 'uri-id' => $item['uri-id'], 'guid' => $item['guid']]);

		return self::handleCreatedItem($orig_item, $post_user_id, $uid, $notify, $copy_permissions, $parent_origin, $priority, $notify_type, $inserted, $source);
	}

	private static function handleCreatedItem(array $orig_item, int $post_user_id, int $uid, int $notify, bool $copy_permissions, $parent_origin, int $priority, string $notify_type, bool $inserted, $source): int
	{
		$posted_item = Post::selectFirst(self::ITEM_FIELDLIST, ['post-user-id' => $post_user_id]);
		if (!DBA::isResult($posted_item)) {
			// On failure store the data into a spool file so that the "SpoolPost" worker can try again later.
			DI::logger()->warning('Could not store item. it will be spooled', ['id' => $post_user_id]);
			self::spool($orig_item);
			return 0;
		}

		if ($posted_item['origin'] && $posted_item['gravity'] == self::GRAVITY_PARENT) {
			$posts = (int)(DI::keyValue()->get('nodeinfo_local_posts') ?? 0);
			DI::keyValue()->set('nodeinfo_local_posts', $posts + 1);
		} elseif ($posted_item['origin'] && $posted_item['gravity'] == self::GRAVITY_COMMENT) {
			$comments = (int)(DI::keyValue()->get('nodeinfo_local_comments') ?? 0);
			DI::keyValue()->set('nodeinfo_local_comments', $comments + 1);
		}

		Post\Origin::insert($posted_item);

		// update the commented timestamp on the parent
		if (DI::config()->get('system', 'like_no_comment')) {
			// Update when it is a comment
			$update_commented = in_array($posted_item['gravity'], [self::GRAVITY_PARENT, self::GRAVITY_COMMENT]);
		} else {
			// Update when it isn't a follow or tag verb
			$update_commented = !in_array($posted_item['verb'], [Activity::FOLLOW, Activity::TAG]);
		}

		if ($update_commented) {
			$fields = ['commented' => DateTimeFormat::utcNow(), 'changed' => DateTimeFormat::utcNow()];
		} else {
			$fields = ['changed' => DateTimeFormat::utcNow()];
		}

		Post::update($fields, ['uri-id' => $posted_item['parent-uri-id'], 'uid' => $posted_item['uid']]);

		// In that function we check if this is a group post. Additionally we delete the item under certain circumstances
		if (self::tagDeliver($posted_item['uid'], $post_user_id)) {
			// Get the user information for the logging
			$user = User::getById($uid);

			DI::logger()->notice('Item had been deleted', ['id' => $post_user_id, 'user' => $uid, 'account-type' => $user['account-type']]);
			return 0;
		}

		if ($notify) {
			DI::contentItem()->postProcessPost($posted_item);
			if ($copy_permissions && ($posted_item['thr-parent-id'] != $posted_item['uri-id']) && ($posted_item['private'] == self::PRIVATE)) {
				DI::contentItem()->copyPermissions($posted_item['thr-parent-id'], $posted_item['uri-id'], $posted_item['parent-uri-id']);
			}
		} else {
			Hook::callAll('post_remote_end', $posted_item);
		}

		if ($posted_item['gravity'] === self::GRAVITY_PARENT) {
			self::addShadow($post_user_id);
		} else {
			self::addShadowPost($post_user_id);
		}

		self::updateContact($posted_item);

		Post\UserNotification::setNotification($posted_item['uri-id'], $posted_item['uid']);

		// Distribute items to users who subscribed to their tags
		self::distributeByTags($posted_item);

		// Automatically reshare the item if the "remote_self" option is selected
		self::autoReshare($posted_item);

		$transmit = $notify || ($posted_item['visible'] && ($parent_origin || $posted_item['origin']));

		if ($transmit) {
			if ($posted_item['uid'] && Contact\User::isBlocked($posted_item['author-id'], $posted_item['uid'])) {
				DI::logger()->info('Message from blocked author will not be relayed', ['item' => $posted_item['id'], 'uri' => $posted_item['uri'], 'cid' => $posted_item['author-id']]);
				$transmit = false;
			}
			if ($transmit && $posted_item['uid'] && Contact\User::isBlocked($posted_item['owner-id'], $posted_item['uid'])) {
				DI::logger()->info('Message from blocked owner will not be relayed', ['item' => $posted_item['id'], 'uri' => $posted_item['uri'], 'cid' => $posted_item['owner-id']]);
				$transmit = false;
			}
			if ($transmit && !empty($posted_item['causer-id']) && $posted_item['uid'] && Contact\User::isBlocked($posted_item['causer-id'], $posted_item['uid'])) {
				DI::logger()->info('Message from blocked causer will not be relayed', ['item' => $posted_item['id'], 'uri' => $posted_item['uri'], 'cid' => $posted_item['causer-id']]);
				$transmit = false;
			}

			// Don't relay participation messages
			if (($posted_item['verb'] == Activity::FOLLOW) &&
				(!$posted_item['origin'] || ($posted_item['author-id'] != Contact::getPublicIdByUserId($uid)))
			) {
				DI::logger()->info('Participation messages will not be relayed', ['item' => $posted_item['id'], 'uri' => $posted_item['uri'], 'verb' => $posted_item['verb']]);
				$transmit = false;
			}
		}

		if (!empty($source) && ($transmit || DI::config()->get('debug', 'store_source'))) {
			Post\Activity::insert($posted_item['uri-id'], $source);
		}

		if ($transmit) {
			ActivityPub\Transmitter::storeReceiversForItem($posted_item);

			Worker::add(['priority' => $priority, 'dont_fork' => true], 'Notifier', $notify_type, (int)$posted_item['uri-id'], (int)$posted_item['uid']);
		}

		if ($inserted) {
			if ($posted_item['gravity'] == self::GRAVITY_PARENT) {
				$posts = (int)(DI::keyValue()->get('nodeinfo_total_posts') ?? 0);
				DI::keyValue()->set('nodeinfo_total_posts', $posts + 1);
			} elseif ($posted_item['gravity'] == self::GRAVITY_COMMENT) {
				$comments = (int)(DI::keyValue()->get('nodeinfo_total_comments') ?? 0);
				DI::keyValue()->set('nodeinfo_total_comments', $comments + 1);
			}

			// Fill the cache with the rendered content.
			if (in_array($posted_item['gravity'], [self::GRAVITY_PARENT, self::GRAVITY_COMMENT])) {
				self::updateDisplayCache($posted_item['uri-id']);
			}

			if (in_array($posted_item['gravity'], [self::GRAVITY_ACTIVITY, self::GRAVITY_COMMENT])) {
				Post\Counts::update($posted_item['thr-parent-id'], $posted_item['parent-uri-id'], $posted_item['vid'], $posted_item['verb'], $posted_item['body']);
			}

			$engagement_uri_id = Post\Engagement::storeFromItem($posted_item);

			if (in_array($posted_item['gravity'], [self::GRAVITY_PARENT, self::GRAVITY_COMMENT])) {
				Post\SearchIndex::insert($posted_item['uri-id'], $posted_item['created']);
			} elseif ($posted_item['verb'] == Activity::ANNOUNCE) {
				Post\SearchIndex::update($posted_item['thr-parent-id']);
			}

			if (($posted_item['gravity'] == self::GRAVITY_ACTIVITY) && ($posted_item['verb'] == Activity::ANNOUNCE) && ($posted_item['parent-uri-id'] == $posted_item['thr-parent-id'])) {
				self::reshareChannelPost($posted_item['thr-parent-id'], $posted_item['author-id']);
			} elseif ($engagement_uri_id) {
				self::reshareChannelPost($engagement_uri_id);
			}
		}

		return $post_user_id;
	}

	private static function reshareChannelPost(int $uri_id, int $reshare_id = 0)
	{
		if (!DI::config()->get('system', 'allow_relay_channels')) {
			return;
		}

		$item = Post::selectFirst(['id', 'private', 'network', 'language', 'owner-id'], ['uri-id' => $uri_id, 'uid' => 0]);
		if (empty($item['id'])) {
			DI::logger()->debug('Post not found', ['uri-id' => $uri_id]);
			return;
		}

		if (($item['private'] != self::PUBLIC) || !in_array($item['network'], [Protocol::ACTIVITYPUB, Protocol::DFRN])) {
			DI::logger()->debug('Not a public post or no AP or DFRN post', ['uri-id' => $uri_id]);
			return;
		}

		$engagement = DBA::selectFirst('post-engagement', ['searchtext', 'media-type'], ['uri-id' => $uri_id]);
		if (empty($engagement['searchtext'])) {
			DI::logger()->debug('No engagement found', ['uri-id' => $uri_id]);
			return;
		}

		$language = !empty($item['language']) ? array_key_first(json_decode($item['language'], true)) : '';
		$tags     = array_column(Tag::getByURIId($uri_id, [Tag::HASHTAG]), 'name');

		DI::logger()->debug('Prepare check', ['uri-id' => $uri_id, 'language' => $language, 'tags' => $tags, 'searchtext' => $engagement['searchtext'], 'media_type' => $engagement['media-type'], 'owner' => $item['owner-id'], 'reshare' => $reshare_id]);

		$count = 0;
		foreach (DI::userDefinedChannel()->getMatchingChannelUsers($engagement['searchtext'], $language, $tags, $engagement['media-type'], $item['owner-id'], $reshare_id) as $uid) {
			$condition = [
				'verb'      => Activity::ANNOUNCE, 'deleted' => false, 'gravity' => self::GRAVITY_ACTIVITY,
				'author-id' => Contact::getPublicIdByUserId($uid), 'uid' => $uid, 'thr-parent-id' => $uri_id
			];
			if (!Post::exists($condition)) {
				DI::logger()->debug('Reshare post', ['uid' => $uid, 'uri-id' => $uri_id]);
				$allow_cid = '';
				$allow_gid = '<' . Circle::FOLLOWERS . '>';
				$deny_cid  = '';
				$deny_gid  = '';
				self::performActivity($item['id'], 'announce', $uid, $allow_cid, $allow_gid, $deny_cid, $deny_gid);
			} else {
				DI::logger()->debug('Reshare already exists', ['uid' => $uid, 'uri-id' => $uri_id]);
			}
			$count++;
		}

		DI::logger()->debug('Check done', ['uri-id' => $uri_id, 'count' => $count]);
	}

	/**
	 * Fetch the post reason for a given item array
	 *
	 * @param array $item
	 *
	 * @return integer
	 */
	public static function getPostReason(array $item): int
	{
		$actor = ($item['gravity'] == self::GRAVITY_PARENT) ? $item['owner-id'] : $item['author-id'];
		if (empty($item['origin']) && ($item['uid'] != 0) && Contact::isSharing($actor, $item['uid'])) {
			return self::PR_FOLLOWER;
		}

		if (!empty($item['origin']) && empty($item['post-reason'])) {
			return self::PR_LOCAL;
		}

		return $item['post-reason'] ?? self::PR_NONE;
	}

	/**
	 * Update the display cache
	 *
	 * @param integer $uri_id
	 * @return void
	 * @throws InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function updateDisplayCache(int $uri_id)
	{
		$item = Post::selectFirst(self::DISPLAY_FIELDLIST, ['uri-id' => $uri_id]);
		if (!$item) {
			return;
		}

		self::prepareBody($item, false, false, true);
	}

	/**
	 * Change the owner of a parent item if it had been shared by a group
	 *
	 * (public) group posts in the new format consist of the regular post by the author
	 * followed by an announce message sent from the group account.
	 * Changing the owner helps in grouping group posts.
	 *
	 * @param array $item
	 * @return void
	 */
	private static function setOwnerforResharedItem(array $item)
	{
		if ($item['uid'] == 0) {
			return;
		}

		$parent = Post::selectFirst(
			['id', 'causer-id', 'owner-id', 'author-id', 'author-link', 'origin', 'post-reason'],
			['uri-id' => $item['thr-parent-id'], 'uid' => $item['uid']]
		);
		if (!DBA::isResult($parent)) {
			DI::logger()->error('Parent not found', ['uri-id' => $item['thr-parent-id'], 'uid' => $item['uid']]);
			return;
		}

		$author = Contact::selectFirst(['url', 'contact-type', 'network'], ['id' => $item['author-id']]);
		if (!DBA::isResult($author)) {
			DI::logger()->error('Author not found', ['id' => $item['author-id']]);
			return;
		}

		$self_contact = Contact::selectFirst(['id'], ['uid' => $item['uid'], 'self' => true]);
		$self         = !empty($self_contact) ? $self_contact['id'] : 0;

		$cid = Contact::getIdForURL($author['url'], $item['uid']);
		if (empty($cid) || (!Contact::isSharing($cid, $item['uid']) && ($cid != $self))) {
			DI::logger()->info('The resharer is not a following contact: quit', ['resharer' => $author['url'], 'uid' => $item['uid'], 'cid' => $cid]);
			return;
		}

		if ($author['contact-type'] != Contact::TYPE_COMMUNITY) {
			if ($parent['post-reason'] == self::PR_ANNOUNCEMENT) {
				DI::logger()->info('The parent is already marked as announced: quit', ['causer' => $parent['causer-id'], 'owner' => $parent['owner-id'], 'author' => $parent['author-id'], 'uid' => $item['uid']]);
				return;
			}

			if (Contact::isSharing($parent['owner-id'], $item['uid'])) {
				DI::logger()->info('The resharer is no group: quit', ['resharer' => $item['author-id'], 'owner' => $parent['owner-id'], 'author' => $parent['author-id'], 'uid' => $item['uid']]);
				return;
			}
		}

		self::update(['post-reason' => self::PR_ANNOUNCEMENT, 'causer-id' => $item['author-id']], ['id' => $parent['id']]);
		DI::logger()->info('Set announcement post-reason', ['uri-id' => $item['uri-id'], 'thr-parent-id' => $item['thr-parent-id'], 'uid' => $item['uid']]);
	}

	/**
	 * Distribute the given item to users who subscribed to their tags
	 *
	 * @param array $item     Processed item
	 */
	private static function distributeByTags(array $item)
	{
		if (($item['uid'] != 0) || ($item['gravity'] != self::GRAVITY_PARENT) || !in_array($item['network'], Protocol::FEDERATED)) {
			return;
		}

		$languages = $item['language'] ? array_keys(json_decode($item['language'], true)) : [];

		foreach (Tag::getUIDListByURIId($item['uri-id']) as $uid => $tags) {
			if (!empty($languages)) {
				$keep           = false;
				$user_languages = User::getWantedLanguages($uid);
				foreach ($user_languages as $language) {
					if (in_array($language, $languages)) {
						$keep = true;
					}
				}
				if ($keep) {
					DI::logger()->debug('Wanted languages found', ['uid' => $uid, 'user-languages' => $user_languages, 'item-languages' => $languages]);
				} else {
					DI::logger()->debug('No wanted languages found', ['uid' => $uid, 'user-languages' => $user_languages, 'item-languages' => $languages]);
					continue;
				}
			}

			$stored = self::storeForUserByUriId($item['uri-id'], $uid, ['post-reason' => self::PR_TAG]);
			DI::logger()->info('Stored item for users', ['uri-id' => $item['uri-id'], 'uid' => $uid, 'stored' => $stored]);
			foreach ($tags as $tag) {
				$stored = Category::storeFileByURIId($item['uri-id'], $uid, Category::SUBCRIPTION, $tag);
				DI::logger()->debug('Stored tag subscription for user', ['uri-id' => $item['uri-id'], 'uid' => $uid, $tag, 'stored' => $stored]);
			}
		}
	}

	/**
	 * Distributes public items to the receivers
	 *
	 * @param integer $itemid      Item ID that should be added
	 * @param string  $signed_text Original text (for Diaspora signatures), JSON encoded.
	 * @throws \Exception
	 */
	public static function distribute(int $itemid, string $signed_text = '')
	{
		$condition = ["`id` IN (SELECT `parent` FROM `post-user-view` WHERE `id` = ?)", $itemid];
		$parent    = Post::selectFirst(['owner-id'], $condition);
		if (!DBA::isResult($parent)) {
			DI::logger()->warning('Item not found', ['condition' => $condition]);
			return;
		}

		// Only distribute public items from native networks
		$condition = [
			'id'      => $itemid, 'uid' => 0,
			'network' => array_merge(Protocol::FEDERATED, ['']),
			'visible' => true, 'deleted' => false, 'private' => [self::PUBLIC, self::UNLISTED]
		];
		$item = Post::selectFirst(array_merge(self::ITEM_FIELDLIST, ['protocol']), $condition);
		if (!DBA::isResult($item)) {
			DI::logger()->warning('Item not found', ['condition' => $condition]);
			return;
		}

		$origin = $item['origin'];

		$users = [];

		/// @todo add a field "pcid" in the contact table that refers to the public contact id.
		$owner = DBA::selectFirst('contact', ['url', 'nurl', 'alias'], ['id' => $parent['owner-id']]);
		if (!DBA::isResult($owner)) {
			return;
		}

		$condition = ['nurl' => $owner['nurl'], 'rel' => [Contact::SHARING, Contact::FRIEND]];
		$contacts  = DBA::select('contact', ['uid'], $condition);
		while ($contact = DBA::fetch($contacts)) {
			if ($contact['uid'] == 0) {
				continue;
			}

			$users[$contact['uid']] = $contact['uid'];
		}
		DBA::close($contacts);

		$condition = ['alias' => $owner['url'], 'rel' => [Contact::SHARING, Contact::FRIEND]];
		$contacts  = DBA::select('contact', ['uid'], $condition);
		while ($contact = DBA::fetch($contacts)) {
			if ($contact['uid'] == 0) {
				continue;
			}

			$users[$contact['uid']] = $contact['uid'];
		}
		DBA::close($contacts);

		if (!empty($owner['alias'])) {
			$condition = ['nurl' => Strings::normaliseLink($owner['alias']), 'rel' => [Contact::SHARING, Contact::FRIEND]];
			$contacts  = DBA::select('contact', ['uid'], $condition);
			while ($contact = DBA::fetch($contacts)) {
				if ($contact['uid'] == 0) {
					continue;
				}

				$users[$contact['uid']] = $contact['uid'];
			}
			DBA::close($contacts);
		}

		$origin_uid = 0;

		if ($item['uri-id'] != $item['parent-uri-id']) {
			$parents = Post::select(['uid', 'origin'], ["`uri-id` = ? AND `uid` != 0", $item['parent-uri-id']]);
			while ($parent = Post::fetch($parents)) {
				$users[$parent['uid']] = $parent['uid'];
				if ($parent['origin'] && !$origin) {
					$origin_uid = $parent['uid'];
				}
			}
			DBA::close($parents);
		}

		foreach ($users as $uid) {
			if ($origin_uid == $uid) {
				$item['diaspora_signed_text'] = $signed_text;
			}
			$item['post-reason'] = self::PR_DISTRIBUTE;
			self::storeForUser($item, $uid);
		}
	}

	/**
	 * Store a public item defined by their URI-ID for the given users
	 *
	 * @param integer $uri_id     URI-ID of the given item
	 * @param integer $uid        The user that will receive the item entry
	 * @param array   $fields     Additional fields to be stored
	 * @param integer $source_uid User id of the source post
	 * @return integer stored item id
	 */
	public static function storeForUserByUriId(int $uri_id, int $uid, array $fields = [], int $source_uid = 0): int
	{
		if ($uid == $source_uid) {
			DI::logger()->warning('target UID must not be be equal to the source UID', ['uri-id' => $uri_id, 'uid' => $uid]);
			return 0;
		}

		$item = Post::selectFirst(array_merge(self::ITEM_FIELDLIST, ['protocol']), ['uri-id' => $uri_id, 'uid' => $source_uid]);
		if (!DBA::isResult($item)) {
			DI::logger()->warning('Item could not be fetched', ['uri-id' => $uri_id, 'uid' => $source_uid]);
			return 0;
		}

		if (($uid != 0) && ($item['gravity'] == self::GRAVITY_PARENT)) {
			$owner = User::getOwnerDataById($uid);
			if (($owner['contact-type'] == User::ACCOUNT_TYPE_COMMUNITY) && !Tag::isMentioned($uri_id, $owner['url'])) {
				DI::logger()->info('Target user is a group but is not mentioned here, thread will not be stored', ['uid' => $uid, 'uri-id' => $uri_id]);
				return 0;
			}
		}

		if (($source_uid == 0) && (($item['private'] == self::PRIVATE) || !in_array($item['network'], array_merge(Protocol::FEDERATED, [Protocol::BLUESKY])))) {
			DI::logger()->notice('Item is private or not from a federated network. It will not be stored for the user.', ['uri-id' => $uri_id, 'uid' => $uid, 'private' => $item['private'], 'network' => $item['network']]);
			return 0;
		}

		$item['post-reason'] = self::PR_STORED;

		$item = array_merge($item, $fields);

		if (($uid != 0) && Contact::isSharing(($item['gravity'] == Item::GRAVITY_PARENT) ? $item['owner-id'] : $item['author-id'], $uid)) {
			$item['post-reason'] = self::PR_FOLLOWER;
		}

		$is_reshare = ($item['gravity'] == self::GRAVITY_ACTIVITY) && ($item['verb'] == Activity::ANNOUNCE);

		if (($uid != 0) && (($item['gravity'] == self::GRAVITY_PARENT) || $is_reshare) &&
			DI::pConfig()->get($uid, 'system', 'accept_only_sharer') == self::COMPLETION_NONE &&
			!in_array($item['post-reason'], [self::PR_FOLLOWER, self::PR_TAG, self::PR_TO, self::PR_CC, self::PR_ACTIVITY, self::PR_AUDIENCE])
		) {
			DI::logger()->info('Contact is not a follower, thread will not be stored', ['author' => $item['author-link'], 'uid' => $uid, 'uri-id' => $uri_id, 'post-reason' => $item['post-reason']]);
			return 0;
		}

		$causer = $item['causer-id'] ?: $item['author-id'];

		if (($uri_id != $item['parent-uri-id']) && ($item['gravity'] == self::GRAVITY_COMMENT) && !Post::exists(['uri-id' => $item['parent-uri-id'], 'uid' => $uid])) {
			if (!self::fetchParent($item['parent-uri-id'], $uid, $causer)) {
				DI::logger()->info('Parent post had not been added', ['uri-id' => $item['parent-uri-id'], 'uid' => $uid, 'causer' => $causer]);
				return 0;
			}
			DI::logger()->info('Fetched parent post', ['uri-id' => $item['parent-uri-id'], 'uid' => $uid, 'causer' => $causer]);
		} elseif (($uri_id != $item['thr-parent-id']) && $is_reshare && !Post::exists(['uri-id' => $item['thr-parent-id'], 'uid' => $uid])) {
			if (!self::fetchParent($item['thr-parent-id'], $uid, $causer)) {
				DI::logger()->info('Thread parent had not been added', ['uri-id' => $item['thr-parent-id'], 'uid' => $uid, 'causer' => $causer]);
				return 0;
			}
			DI::logger()->info('Fetched thread parent', ['uri-id' => $item['thr-parent-id'], 'uid' => $uid, 'causer' => $causer]);
		}

		$stored = self::storeForUser($item, $uid);
		DI::logger()->info('Item stored for user', ['uri-id' => $item['uri-id'], 'uid' => $uid, 'causer' => $causer, 'source-uid' => $source_uid, 'stored' => $stored]);
		return $stored;
	}

	/**
	 * Fetch the parent with the given uri-id
	 *
	 * @param integer $uri_id
	 * @param integer $uid
	 * @param integer $causer
	 *
	 * @return integer
	 */
	private static function fetchParent(int $uri_id, int $uid, int $causer): int
	{
		// Fetch the origin user for the post
		$origin_uid = self::GetOriginUidForUriId($uri_id, $uid);
		if (is_null($origin_uid)) {
			DI::logger()->info('Origin item was not found', ['uid' => $uid, 'uri-id' => $uri_id]);
			return 0;
		}

		return self::storeForUserByUriId($uri_id, $uid, ['causer-id' => $causer, 'post-reason' => self::PR_FETCHED], $origin_uid);
	}

	/**
	 * Returns the origin uid of a post if the given user is allowed to see it.
	 *
	 * @param int $uriid
	 * @param int $uid
	 * @return int|null
	 */
	private static function GetOriginUidForUriId(int $uriid, int $uid)
	{
		if (Post::exists(['uri-id' => $uriid, 'uid' => $uid])) {
			return $uid;
		}

		$post = Post::selectFirst(['uid', 'allow_cid', 'allow_gid', 'deny_cid', 'deny_gid', 'private'], ['uri-id' => $uriid, 'origin' => true]);
		if (!empty($post)) {
			if (in_array($post['private'], [Item::PUBLIC, Item::UNLISTED])) {
				return $post['uid'];
			}

			$pcid = Contact::getPublicIdByUserId($uid);
			if (empty($pcid)) {
				return null;
			}

			foreach (Item::enumeratePermissions($post, true) as $receiver) {
				if ($receiver == $pcid) {
					return $post['uid'];
				}
			}

			return null;
		}

		if (Post::exists(['uri-id' => $uriid, 'uid' => 0])) {
			return 0;
		}

		// When the post belongs to a a group then all group users are allowed to access it
		foreach (Tag::getByURIId($uriid, [Tag::MENTION, Tag::EXCLUSIVE_MENTION]) as $tag) {
			if (DBA::exists('contact', ['uid' => $uid, 'nurl' => Strings::normaliseLink($tag['url']), 'contact-type' => Contact::TYPE_COMMUNITY])) {
				$target_uid = User::getIdForURL($tag['url']);
				if (!empty($target_uid)) {
					return $target_uid;
				}
			}
		}

		return null;
	}

	/**
	 * Store a public item array for the given users
	 *
	 * @param array   $item   The item entry that will be stored
	 * @param integer $uid    The user that will receive the item entry
	 * @return integer stored item id
	 * @throws \Exception
	 */
	private static function storeForUser(array $item, int $uid): int
	{
		$post = Post::selectFirst(['id'], ['uri-id' => $item['uri-id'], 'uid' => $uid]);
		if (!empty($post['id'])) {
			if (!empty($item['event-id'])) {
				$event_post = Post::selectFirst(['event-id'], ['uri-id' => $item['uri-id'], 'uid' => $uid]);
				if (!empty($event_post['event-id'])) {
					$event = DBA::selectFirst('event', ['edited', 'start', 'finish', 'summary', 'desc', 'location', 'nofinish'], ['id' => $item['event-id']]);
					if (!empty($event)) {
						// We aren't using "Event::store" here, since we don't want to trigger any further action
						$ret = DBA::update('event', $event, ['id' => $event_post['event-id']]);
						DI::logger()->info('Event updated', ['uid' => $uid, 'source-event' => $item['event-id'], 'target-event' => $event_post['event-id'], 'ret' => $ret]);
					}
				}
			}
			DI::logger()->info('Item already exists', ['uri-id' => $item['uri-id'], 'uid' => $uid, 'id' => $post['id']]);
			return $post['id'];
		}

		// Data from the "post-user" table
		unset($item['id']);
		unset($item['mention']);
		unset($item['starred']);
		unset($item['unseen']);
		unset($item['psid']);
		unset($item['pinned']);
		unset($item['ignored']);
		unset($item['pubmail']);
		unset($item['event-id']);
		unset($item['hidden']);
		unset($item['notification-type']);

		// Data from the "post-delivery-data" table
		unset($item['postopts']);
		unset($item['inform']);

		$item['uid']    = $uid;
		$item['origin'] = 0;
		$item['wall']   = 0;

		$notify = false;
		if ($item['gravity'] == self::GRAVITY_PARENT) {
			$contact = DBA::selectFirst('contact', [], ['id' => $item['contact-id'], 'self' => false]);
			if (DBA::isResult($contact)) {
				$notify       = self::isRemoteSelf($contact, $item);
				$item['wall'] = (bool)$notify;
			}
		}

		$item['contact-id'] = self::contactId($item);
		$distributed        = self::insert($item, $notify);

		if (!$distributed) {
			DI::logger()->info("Distributed item wasn't stored", ['uri-id' => $item['uri-id'], 'user' => $uid]);
		} else {
			DI::logger()->info('Distributed item was stored', ['uri-id' => $item['uri-id'], 'user' => $uid, 'stored' => $distributed]);
		}
		return $distributed;
	}

	/**
	 * Add a shadow entry for a given item id that is a thread starter
	 *
	 * We store every public item entry additionally with the user id "0".
	 * This is used for the community page and for the search.
	 * It is planned that in the future we will store public item entries only once.
	 *
	 * @param integer $itemid Item ID that should be added
	 * @throws \Exception
	 */
	private static function addShadow(int $itemid)
	{
		$fields    = ['uid', 'private', 'visible', 'deleted', 'network', 'uri-id'];
		$condition = ['id' => $itemid, 'gravity' => self::GRAVITY_PARENT];
		$item      = Post::selectFirst($fields, $condition);

		if (!DBA::isResult($item)) {
			return;
		}

		// is it already a copy?
		if (($itemid == 0) || ($item['uid'] == 0)) {
			return;
		}

		// Is it a visible public post?
		if (!$item["visible"] || $item["deleted"] || ($item["private"] == self::PRIVATE)) {
			return;
		}

		// is it an entry from a connector? Only add an entry for natively connected networks
		if (!in_array($item["network"], array_merge(Protocol::FEDERATED, ['']))) {
			return;
		}

		if (Post::exists(['uri-id' => $item['uri-id'], 'uid' => 0])) {
			return;
		}

		$item = Post::selectFirst(array_merge(self::ITEM_FIELDLIST, ['protocol']), ['id' => $itemid]);

		if (DBA::isResult($item)) {
			// Preparing public shadow (removing user specific data)
			$item['uid'] = 0;
			unset($item['id']);
			unset($item['parent']);
			unset($item['wall']);
			unset($item['mention']);
			unset($item['origin']);
			unset($item['starred']);
			unset($item['postopts']);
			unset($item['inform']);
			unset($item['post-reason']);
			if ($item['uri-id'] == $item['parent-uri-id']) {
				$item['contact-id'] = $item['owner-id'];
			} else {
				$item['contact-id'] = $item['author-id'];
			}

			$public_shadow = self::insert($item);

			DI::logger()->info('Stored public shadow', ['thread' => $itemid, 'id' => $public_shadow]);
		}
	}

	/**
	 * Add a shadow entry for a given item id that is a comment
	 *
	 * This function does the same like the function above - but for comments
	 *
	 * @param integer $itemid Item ID that should be added
	 * @throws \Exception
	 */
	private static function addShadowPost(int $itemid)
	{
		$item = Post::selectFirst(array_merge(self::ITEM_FIELDLIST, ['protocol']), ['id' => $itemid]);
		if (!DBA::isResult($item)) {
			return;
		}

		// Is it a toplevel post?
		if ($item['gravity'] == self::GRAVITY_PARENT) {
			self::addShadow($itemid);
			return;
		}

		// Is this a shadow entry?
		if ($item['uid'] == 0) {
			return;
		}

		// Is there a shadow parent?
		if (!Post::exists(['uri-id' => $item['parent-uri-id'], 'uid' => 0])) {
			return;
		}

		// Is there already a shadow entry?
		if (Post::exists(['uri-id' => $item['uri-id'], 'uid' => 0])) {
			return;
		}

		// Save "origin" and "parent" state
		$origin = $item['origin'];
		$parent = $item['parent'];

		// Preparing public shadow (removing user specific data)
		$item['uid'] = 0;
		unset($item['id']);
		unset($item['parent']);
		unset($item['wall']);
		unset($item['mention']);
		unset($item['origin']);
		unset($item['starred']);
		unset($item['postopts']);
		unset($item['inform']);
		unset($item['post-reason']);
		$item['contact-id'] = Contact::getIdForURL($item['author-link']);

		$public_shadow = self::insert($item);

		DI::logger()->info('Stored public shadow', ['uri-id' => $item['uri-id'], 'id' => $public_shadow]);

		// If this was a comment to a Diaspora post we don't get our comment back.
		// This means that we have to distribute the comment by ourselves.
		if ($origin && Post::exists(['id' => $parent, 'network' => Protocol::DIASPORA])) {
			self::distribute($public_shadow);
		}
	}

	/**
	 * Adds a language specification in a "language" element of given $arr.
	 * Expects "body" element to exist in $arr.
	 *
	 * @param array $item
	 * @return string detected language
	 */
	private static function getLanguage(array $item): ?string
	{
		if (!empty($item['language'])) {
			return $item['language'];
		}

		$transmitted = [];
		foreach ($item['transmitted-languages'] ?? [] as $language) {
			$transmitted[$language] = 0;
		}

		if (!in_array($item['gravity'], [self::GRAVITY_PARENT, self::GRAVITY_COMMENT])) {
			return !empty($transmitted) ? json_encode($transmitted) : null;
		}

		$content = trim(($item['title'] ?? '') . ' ' . ($item['content-warning'] ?? '') . ' ' . ($item['body'] ?? ''));

		if (empty($content) && !empty($item['quote-uri-id'])) {
			$quoted = Post::selectFirstPost(['language'], ['uri-id' => $item['quote-uri-id']]);
			if (!empty($quoted['language'])) {
				return $quoted['language'];
			}
		}

		$languages = self::getLanguageArray($content, 3, $item['uri-id'], $item['author-id'], $transmitted);

		if (!empty($transmitted)) {
			$languages = array_merge($transmitted, $languages);
			arsort($languages);
		}

		return json_encode($languages);
	}

	/**
	 * Get a language array from a given text
	 *
	 * @param string  $body
	 * @param integer $count
	 * @param integer $uri_id
	 * @param integer $author_id
	 * @param array   $default
	 * @return array
	 */
	public static function getLanguageArray(string $body, int $count, int $uri_id = 0, int $author_id = 0, array $default = []): array
	{
		$default = $default ?: [L10n::UNDETERMINED_LANGUAGE => 1];

		$searchtext = BBCode::toSearchText($body, $uri_id);

		if ((count(explode(' ', $searchtext)) < 10) && (mb_strlen($searchtext) < 30) && $author_id) {
			$author = Contact::selectFirst(['about'], ['id' => $author_id]);
			if (!empty($author['about'])) {
				$about = BBCode::toSearchText($author['about'], 0);
				DI::logger()->debug('About field added', ['author' => $author_id, 'body' => $searchtext, 'about' => $about]);
				$searchtext .= ' ' . $about;
			}
		}

		if (empty($searchtext)) {
			return $default;
		}

		$ld = new Language(DI::l10n()->getDetectableLanguages());

		$result = [];

		foreach (self::splitByBlocks($searchtext) as $block) {
			$languages = $ld->detect($block)->close() ?: [];

			$data = [
				'text'      => $block,
				'detected'  => $languages,
				'uri-id'    => $uri_id,
				'author-id' => $author_id,
			];
			Hook::callAll('detect_languages', $data);

			foreach ($data['detected'] as $language => $quality) {
				$result[$language] = max($result[$language] ?? 0, $quality * (strlen($block) / strlen($searchtext)));
			}
		}

		$result = self::compactLanguages($result);
		if (empty($result)) {
			return $default;
		}

		arsort($result);
		return array_slice($result, 0, $count);
	}

	/**
	 * Concert the language code in the detection result to ISO 639-1.
	 * On duplicates the system uses the higher quality value.
	 *
	 * @param array $result
	 * @return array
	 */
	private static function compactLanguages(array $result): array
	{
		$languages = [];
		foreach ($result as $language => $quality) {
			if ($quality == 0) {
				continue;
			}
			$code = DI::l10n()->toISO6391($language);
			if (empty($languages[$code]) || ($languages[$code] < $quality)) {
				$languages[$code] = $quality;
			}
		}
		return $languages;
	}

	/**
	 * Split a string into different unicode blocks
	 * Currently the text is split into the latin and the non latin part.
	 *
	 * @param string $body
	 * @return array
	 */
	private static function splitByBlocks(string $body): array
	{
		if (!class_exists('IntlChar')) {
			return [$body];
		}

		$blocks         = [];
		$previous_block = 0;

		for ($i = 0; $i < mb_strlen($body); $i++) {
			$character = mb_substr($body, $i, 1);
			$previous  = ($i > 0) ? mb_substr($body, $i - 1, 1) : '';
			$next      = ($i < mb_strlen($body)) ? mb_substr($body, $i + 1, 1) : '';

			if (!\IntlChar::isalpha($character)) {
				if (($previous != '') && (\IntlChar::isalpha($previous))) {
					$previous_block = self::getBlockCode($previous);
				}

				$block          = (($next != '') && \IntlChar::isalpha($next)) ? self::getBlockCode($next) : $previous_block;
				$blocks[$block] = ($blocks[$block] ?? '') . $character;
			} else {
				$block          = self::getBlockCode($character);
				$blocks[$block] = ($blocks[$block] ?? '') . $character;
			}
		}

		foreach (array_keys($blocks) as $key) {
			$blocks[$key] = trim($blocks[$key]);
			if (empty($blocks[$key])) {
				unset($blocks[$key]);
			}
		}

		return array_values($blocks);
	}

	/**
	 * returns the block code for the given character
	 *
	 * @param string $character
	 * @return integer 0 = no alpha character (blank, signs, emojis, ...), 1 = latin character, 2 = character in every other language
	 */
	private static function getBlockCode(string $character): int
	{
		if (!\IntlChar::isalpha($character)) {
			return 0;
		}
		return self::isLatin($character) ? 1 : 2;
	}

	/**
	 * Checks if the given character is in one of the latin code blocks
	 *
	 * @param string $character
	 * @return boolean
	 */
	private static function isLatin(string $character): bool
	{
		return in_array(\IntlChar::getBlockCode($character), [
			\IntlChar::BLOCK_CODE_BASIC_LATIN, \IntlChar::BLOCK_CODE_LATIN_1_SUPPLEMENT,
			\IntlChar::BLOCK_CODE_LATIN_EXTENDED_A, \IntlChar::BLOCK_CODE_LATIN_EXTENDED_B,
			\IntlChar::BLOCK_CODE_LATIN_EXTENDED_C, \IntlChar::BLOCK_CODE_LATIN_EXTENDED_D,
			\IntlChar::BLOCK_CODE_LATIN_EXTENDED_E, \IntlChar::BLOCK_CODE_LATIN_EXTENDED_ADDITIONAL
		]);
	}

	public static function getLanguageMessage(array $item): string
	{
		$iso639 = new \Matriphe\ISO639\ISO639();

		$used_languages = '';
		foreach (json_decode($item['language'], true) as $language => $reliability) {
			$code = DI::l10n()->toISO6391($language);

			if ($code == L10n::UNDETERMINED_LANGUAGE) {
				$native = $language = DI::l10n()->t('Undetermined');
			} else {
				$native   = $iso639->nativeByCode1($code);
				$language = $iso639->languageByCode1($code);
			}

			if ($native != $language) {
				$used_languages .= DI::l10n()->t('%s (%s - %s): %s', $native, $language, $code, number_format($reliability, 5)) . "\n";
			} else {
				$used_languages .= DI::l10n()->t('%s (%s): %s', $native, $code, number_format($reliability, 5)) . "\n";
			}
		}
		$used_languages = DI::l10n()->t("Detected languages in this post:\n%s", $used_languages);
		return $used_languages;
	}

	/**
	 * Creates an unique guid out of a given uri.
	 * This function is used for messages outside the fediverse (Connector posts, feeds, Mails, ...)
	 * Posts that are created on this system are using System::createUUID.
	 * Received ActivityPub posts are using Processor::getGUIDByURL.
	 *
	 * @param string      $uri  uri of an item entry
	 * @param string|null $host hostname for the GUID prefix
	 * @return string Unique guid
	 * @throws \Exception
	 */
	public static function guidFromUri(string $uri, string $host = null): string
	{
		// Our regular guid routine is using this kind of prefix as well
		// We have to avoid that different routines could accidentally create the same value
		$parsed = parse_url($uri);

		// Remove the scheme to make sure that "https" and "http" doesn't make a difference
		unset($parsed['scheme']);

		$hostPart = $host ?: $parsed['host'] ?? '';
		if (!$hostPart) {
			DI::logger()->warning('Empty host GUID part', ['uri' => $uri, 'host' => $host, 'parsed' => $parsed]);
		}

		// Glue it together to be able to make a hash from it
		if (!empty($parsed)) {
			$host_id = implode('/', (array)$parsed);
		} else {
			$host_id = $uri;
		}

		// Use a mixture of several hashes to provide some GUID like experience
		return hash('crc32', $hostPart) . '-' . hash('joaat', $host_id) . '-' . hash('fnv164', $host_id);
	}

	/**
	 * generate an unique URI
	 *
	 * @param string $guid An existing GUID (Otherwise it will be generated)
	 *
	 * @return string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function newURI(string $guid = ''): string
	{
		if ($guid == '') {
			$guid = System::createUUID();
		}

		return DI::baseUrl() . '/objects/' . $guid;
	}

	/**
	 * Set "success_update" and "last-item" to the date of the last time we heard from this contact
	 *
	 * This can be used to filter for inactive contacts.
	 * Only do this for public postings to avoid privacy problems, since poco data is public.
	 * Don't set this value if it isn't from the owner (could be an author that we don't know)
	 *
	 * @param array $arr Contains the just posted item record
	 * @throws \Exception
	 */
	private static function updateContact(array $arr)
	{
		// Unarchive the author
		$contact = DBA::selectFirst('contact', [], ['id' => $arr["author-id"]]);
		if (DBA::isResult($contact)) {
			Contact::unmarkForArchival($contact);
		}

		// Unarchive the contact if it's not our own contact
		$contact = DBA::selectFirst('contact', [], ['id' => $arr["contact-id"], 'self' => false]);
		if (DBA::isResult($contact)) {
			Contact::unmarkForArchival($contact);
		}

		/// @todo On private posts we could obfuscate the date
		$update = ($arr['private'] != self::PRIVATE) || in_array($arr['network'], Protocol::FEDERATED);

		// Is it a group? Then we don't care about the rules from above
		if (!$update && in_array($arr["network"], [Protocol::ACTIVITYPUB, Protocol::DFRN]) && ($arr["parent-uri-id"] === $arr["uri-id"])) {
			if (DBA::exists('contact', ['id' => $arr['contact-id'], 'forum' => true])) {
				$update = true;
			}
		}

		if ($update) {
			// The "self" contact id is used (for example in the connectors) when the contact is unknown
			// So we have to ensure to only update the last item when it had been our own post,
			// or it had been done by a "regular" contact.
			if (!empty($arr['wall'])) {
				$condition = ['id' => $arr['contact-id']];
			} else {
				$condition = ['id' => $arr['contact-id'], 'self' => false];
			}
			Contact::update(['failed' => false, 'local-data' => true, 'success_update' => $arr['received'], 'last-item' => $arr['received']], $condition);
		}
		// Now do the same for the system wide contacts with uid=0
		if ($arr['private'] != self::PRIVATE) {
			Contact::update(
				['failed' => false, 'local-data' => true, 'success_update' => $arr['received'], 'last-item' => $arr['received']],
				['id' => $arr['owner-id']]
			);

			if ($arr['owner-id'] != $arr['author-id']) {
				Contact::update(
					['failed' => false, 'local-data' => true, 'success_update' => $arr['received'], 'last-item' => $arr['received']],
					['id' => $arr['author-id']]
				);
			}
		}
	}

	public static function setHashtags(string $body): string
	{
		$body = BBCode::performWithEscapedTags($body, ['noparse', 'pre', 'code', 'img'], function ($body) {
			$tags = BBCode::getTags($body);

			// No hashtags?
			if (!count($tags)) {
				return $body;
			}

			// This sorting is important when there are hashtags that are part of other hashtags
			// Otherwise there could be problems with hashtags like #test and #test2
			// Because of this we are sorting from the longest to the shortest tag.
			usort($tags, function ($a, $b) {
				return strlen($b) <=> strlen($a);
			});

			$URLSearchString = "^\[\]";

			// All hashtags should point to the home server if "local_tags" is activated
			if (DI::config()->get('system', 'local_tags')) {
				$body = preg_replace(
					"/#\[url\=([$URLSearchString]*)\](.*?)\[\/url\]/ism",
					"#[url=" . DI::baseUrl() . "/search?tag=$2]$2[/url]",
					$body
				);
			}

			// mask hashtags inside of url, bookmarks and attachments to avoid urls in urls
			$body = preg_replace_callback(
				"/\[url\=([$URLSearchString]*)\](.*?)\[\/url\]/ism",
				function ($match) {
					return ("[url=" . str_replace("#", "&num;", $match[1]) . "]" . str_replace("#", "&num;", $match[2]) . "[/url]");
				},
				$body
			);

			$body = preg_replace_callback(
				"/\[bookmark\=([$URLSearchString]*)\](.*?)\[\/bookmark\]/ism",
				function ($match) {
					return ("[bookmark=" . str_replace("#", "&num;", $match[1]) . "]" . str_replace("#", "&num;", $match[2]) . "[/bookmark]");
				},
				$body
			);

			$body = preg_replace_callback(
				"/\[attachment (.*?)\](.*?)\[\/attachment\]/ism",
				function ($match) {
					return ("[attachment " . str_replace("#", "&num;", $match[1]) . "]" . $match[2] . "[/attachment]");
				},
				$body
			);

			// Repair recursive urls
			$body = preg_replace(
				"/&num;\[url\=([$URLSearchString]*)\](.*?)\[\/url\]/ism",
				"&num;$2",
				$body
			);

			foreach ($tags as $tag) {
				if ((strpos($tag, '#') !== 0) || strpos($tag, '[url=') || strlen($tag) < 2 || $tag[1] == '#') {
					continue;
				}

				$basetag = str_replace('_', ' ', substr($tag, 1));
				$newtag  = '#[url=' . DI::baseUrl() . '/search?tag=' . urlencode($basetag) . ']' . $basetag . '[/url]';

				$body = str_replace($tag, $newtag, $body);
			}

			// Convert back the masked hashtags
			$body = str_replace("&num;", "#", $body);

			return $body;
		});

		return $body;
	}

	/**
	 * look for mention tags and setup a second delivery chain for group/community posts if appropriate
	 *
	 * @param int $uid
	 * @param int $item_id
	 * @return boolean true if item was deleted, else false
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function tagDeliver(int $uid, int $item_id): bool
	{
		$owner = User::getOwnerDataById($uid);
		if (!DBA::isResult($owner)) {
			DI::logger()->warning('User not found, quitting here.', ['uid' => $uid]);
			return false;
		}

		if ($owner['contact-type'] != User::ACCOUNT_TYPE_COMMUNITY) {
			DI::logger()->debug('Owner is no community, quitting here.', ['uid' => $uid, 'id' => $item_id]);
			return false;
		}

		$item = Post::selectFirst(self::ITEM_FIELDLIST, ['id' => $item_id, 'gravity' => [self::GRAVITY_PARENT, self::GRAVITY_COMMENT], 'origin' => false]);
		if (!DBA::isResult($item)) {
			DI::logger()->debug('Post is an activity or origin or not found at all, quitting here.', ['id' => $item_id]);
			return false;
		}

		if ($item['gravity'] == self::GRAVITY_PARENT) {
			if (Tag::isMentioned($item['uri-id'], $owner['url'])) {
				DI::logger()->info('Mention found in tag.', ['uri' => $item['uri'], 'uid' => $uid, 'id' => $item_id, 'uri-id' => $item['uri-id'], 'guid' => $item['guid']]);
			} else {
				DI::logger()->info('Top-level post without mention is deleted.', ['uri' => $item['uri'], $uid, 'id' => $item_id, 'uri-id' => $item['uri-id'], 'guid' => $item['guid']]);
				Post\User::delete(['uri-id' => $item['uri-id'], 'uid' => $item['uid']]);
				return true;
			}

			$arr = ['item' => $item, 'user' => $owner];

			Hook::callAll('tagged', $arr);
		} else {
			if (Tag::isMentioned($item['parent-uri-id'], $owner['url'])) {
				DI::logger()->info('Mention found in parent tag.', ['uri' => $item['uri'], 'uid' => $uid, 'id' => $item_id, 'uri-id' => $item['uri-id'], 'guid' => $item['guid']]);
			} else {
				DI::logger()->debug('No mentions found in parent, quitting here.', ['id' => $item_id, 'uri-id' => $item['uri-id'], 'guid' => $item['guid']]);
				return false;
			}
		}

		DI::logger()->info('Community post will be distributed', ['uri' => $item['uri'], 'uid' => $uid, 'id' => $item_id, 'uri-id' => $item['uri-id'], 'guid' => $item['guid']]);

		if ($owner['page-flags'] == User::PAGE_FLAGS_PRVGROUP) {
			$allow_cid = '';
			$allow_gid = '<' . Circle::FOLLOWERS . '>';
			$deny_cid  = '';
			$deny_gid  = '';
			self::performActivity($item['id'], 'announce', $uid, $allow_cid, $allow_gid, $deny_cid, $deny_gid);
		} else {
			self::performActivity($item['id'], 'announce', $uid);
		}

		DI::logger()->info('Community post had been distributed', ['uri' => $item['uri'], 'uid' => $uid, 'id' => $item_id, 'uri-id' => $item['uri-id'], 'guid' => $item['guid']]);
		return false;
	}

	/**
	 * Automatically reshare the item if the "remote_self" option is selected
	 *
	 * @param array $item
	 * @return void
	 */
	private static function autoReshare(array $item)
	{
		if ($item['gravity'] != self::GRAVITY_PARENT) {
			return;
		}

		$ucid = Contact::getUserContactId($item['author-id'], $item['uid']);
		if (!$ucid || ($ucid != $item['contact-id'])) {
			return;
		}

		if (!DBA::exists('contact', ['id' => $ucid, 'remote_self' => LocalRelationship::MIRROR_NATIVE_RESHARE])) {
			return;
		}

		if (!in_array($item['network'], [Protocol::ACTIVITYPUB, Protocol::DFRN])) {
			return;
		}

		if (User::getById($item['uid'], ['blocked'])['blocked'] ?? false) {
			return;
		}

		DI::logger()->info('Automatically reshare item', ['uid' => $item['uid'], 'id' => $item['id'], 'guid' => $item['guid'], 'uri-id' => $item['uri-id']]);

		self::performActivity($item['id'], 'announce', $item['uid']);
	}

	public static function isRemoteSelf(array $contact, array &$datarray): bool
	{
		if ($contact['remote_self'] != LocalRelationship::MIRROR_OWN_POST) {
			return false;
		}

		// Prevent the forwarding of posts that are forwarded
		if (!empty($datarray['extid']) && ($datarray['extid'] == Protocol::DFRN)) {
			DI::logger()->info('Already forwarded');
			return false;
		}

		// Prevent to forward already forwarded posts
		if ($datarray['app'] == DI::baseUrl()->getHost()) {
			DI::logger()->info('Already forwarded (second test)');
			return false;
		}

		// Only forward posts
		if ($datarray['verb'] != Activity::POST) {
			DI::logger()->info('No post');
			return false;
		}

		if (($contact['network'] != Protocol::FEED) && ($datarray['private'] == self::PRIVATE)) {
			DI::logger()->info('Not public');
			return false;
		}

		if (User::getById($contact['uid'], ['blocked'])['blocked'] ?? false) {
			DI::logger()->info('User is blocked', ['contact' => $contact]);
			return false;
		}

		$datarray2 = $datarray;
		DI::logger()->info('remote-self start', ['contact' => $contact['url'], 'remote_self' => $contact['remote_self'], 'item' => $datarray]);

		$self = DBA::selectFirst(
			'contact',
			['id', 'name', 'url', 'thumb'],
			['uid' => $contact['uid'], 'self' => true]
		);
		if (!DBA::isResult($self)) {
			DI::logger()->error('Self contact not found', ['uid' => $contact['uid']]);
			return false;
		}

		$datarray['contact-id'] = $self['id'];

		$datarray['author-name']   = $datarray['owner-name'] = $self['name'];
		$datarray['author-link']   = $datarray['owner-link'] = $self['url'];
		$datarray['author-avatar'] = $datarray['owner-avatar'] = $self['thumb'];

		unset($datarray['edited']);

		unset($datarray['network']);
		unset($datarray['owner-id']);
		unset($datarray['author-id']);

		if ($contact['network'] != Protocol::FEED) {
			$old_uri_id       = $datarray['uri-id'] ?? 0;
			$datarray['guid'] = System::createUUID();
			unset($datarray['plink']);
			$datarray['uri']    = self::newURI($datarray['guid']);
			$datarray['uri-id'] = ItemURI::getIdByURI($datarray['uri']);
			$datarray['extid']  = Protocol::DFRN;
			$urlpart            = parse_url($datarray2['author-link']);
			$datarray['app']    = $urlpart['host'];
			if (!empty($old_uri_id)) {
				Post\Media::copy($old_uri_id, $datarray['uri-id']);
			}

			unset($datarray['parent-uri']);
			unset($datarray['thr-parent']);

			// Store the original post
			$result = self::insert($datarray2);
			DI::logger()->info('remote-self post original item', ['contact' => $contact['url'], 'result' => $result, 'item' => $datarray2]);
		} else {
			$datarray['app'] = 'Feed';
			$result          = true;
		}

		if ($result) {
			unset($datarray['private']);
		}

		return (bool)$result;
	}

	/**
	 *
	 * @param string $s
	 * @param int    $uid
	 * @param array  $item
	 * @param int    $cid
	 * @return string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function fixPrivatePhotos(string $s, int $uid, array $item = null, int $cid = 0): string
	{
		if (DI::config()->get('system', 'disable_embedded')) {
			return $s;
		}

		DI::logger()->info('check for photos');
		$site = substr(DI::baseUrl(), strpos(DI::baseUrl(), '://'));

		$orig_body = $s;
		$new_body  = '';

		$img_start    = strpos($orig_body, '[img');
		$img_st_close = ($img_start !== false ? strpos(substr($orig_body, $img_start), ']') : false);
		$img_len      = ($img_start !== false ? strpos(substr($orig_body, $img_start + $img_st_close + 1), '[/img]') : false);

		while (($img_st_close !== false) && ($img_len !== false)) {
			$img_st_close++; // make it point to AFTER the closing bracket
			$image = substr($orig_body, $img_start + $img_st_close, $img_len);

			DI::logger()->info('found photo', ['image' => $image]);

			if (stristr($image, $site . '/photo/')) {
				// Only embed locally hosted photos
				$replace = false;
				$i       = basename($image);
				$i       = str_replace(['.jpg', '.png', '.gif'], ['', '', ''], $i);
				$x       = strpos($i, '-');

				if ($x) {
					$res   = substr($i, $x + 1);
					$i     = substr($i, 0, $x);
					$photo = Photo::getPhotoForUser($uid, $i, $res);
					if (DBA::isResult($photo)) {
						/*
						 * Check to see if we should replace this photo link with an embedded image
						 * 1. No need to do so if the photo is public
						 * 2. If there's a contact-id provided, see if they're in the access list
						 *    for the photo. If so, embed it.
						 * 3. Otherwise, if we have an item, see if the item permissions match the photo
						 *    permissions, regardless of order but first check to see if they're an exact
						 *    match to save some processing overhead.
						 */
						if (self::hasPermissions($photo)) {
							if ($cid) {
								$recips = self::enumeratePermissions($photo);
								if (in_array($cid, $recips)) {
									$replace = true;
								}
							} elseif ($item) {
								if (self::samePermissions($uid, $item, $photo)) {
									$replace = true;
								}
							}
						}
						if ($replace) {
							$photo_img = Photo::getImageForPhoto($photo);
							// If a custom width and height were specified, apply before embedding
							if (preg_match("/\[img\=([0-9]*)x([0-9]*)\]/is", substr($orig_body, $img_start, $img_st_close), $match)) {
								DI::logger()->info('scaling photo');

								$width  = intval($match[1]);
								$height = intval($match[2]);

								$photo_img->scaleDown(max($width, $height));
							}

							$data = $photo_img->asString();
							$type = $photo_img->getType();

							DI::logger()->info('replacing photo');
							$image = 'data:' . $type . ';base64,' . base64_encode($data);
							DI::logger()->debug('replaced', ['image' => $image]);
						}
					}
				}
			}

			$new_body  = $new_body . substr($orig_body, 0, $img_start + $img_st_close) . $image . '[/img]';
			$orig_body = substr($orig_body, $img_start + $img_st_close + $img_len + strlen('[/img]'));
			if ($orig_body === false) {
				$orig_body = '';
			}

			$img_start    = strpos($orig_body, '[img');
			$img_st_close = ($img_start !== false ? strpos(substr($orig_body, $img_start), ']') : false);
			$img_len      = ($img_start !== false ? strpos(substr($orig_body, $img_start + $img_st_close + 1), '[/img]') : false);
		}

		$new_body = $new_body . $orig_body;

		return $new_body;
	}

	private static function hasPermissions(array $obj)
	{
		return !empty($obj['allow_cid']) || !empty($obj['allow_gid']) ||
			!empty($obj['deny_cid']) || !empty($obj['deny_gid']);
	}

	// @TODO $uid is unused parameter
	private static function samePermissions($uid, array $obj1, array $obj2): bool
	{
		// first part is easy. Check that these are exactly the same.
		if (($obj1['allow_cid'] == $obj2['allow_cid'])
			&& ($obj1['allow_gid'] == $obj2['allow_gid'])
			&& ($obj1['deny_cid'] == $obj2['deny_cid'])
			&& ($obj1['deny_gid'] == $obj2['deny_gid'])
		) {
			return true;
		}

		// This is harder. Parse all the permissions and compare the resulting set.
		$recipients1 = self::enumeratePermissions($obj1);
		$recipients2 = self::enumeratePermissions($obj2);
		sort($recipients1);
		sort($recipients2);

		/// @TODO Comparison of arrays, maybe use array_diff_assoc() here?
		return ($recipients1 == $recipients2);
	}

	/**
	 * Returns an array of contact-ids that are allowed to see this object
	 *
	 * @param array $obj              Item array with at least uid, allow_cid, allow_gid, deny_cid and deny_gid
	 * @param bool  $check_dead       Prunes unavailable contacts from the result
	 * @param bool  $expand_followers Expand the list of followers
	 * @return array
	 * @throws \Exception
	 */
	public static function enumeratePermissions(array $obj, bool $check_dead = false, bool $expand_followers = true): array
	{
		$aclFormatter = DI::aclFormatter();

		if (!$expand_followers && (!empty($obj['deny_cid']) || !empty($obj['deny_gid']))) {
			$expand_followers = true;
		}

		$allow_people  = $aclFormatter->expand($obj['allow_cid']);
		$allow_circles = Circle::expand($obj['uid'], $aclFormatter->expand($obj['allow_gid']), $check_dead, $expand_followers);
		$deny_people   = $aclFormatter->expand($obj['deny_cid']);
		$deny_circles  = Circle::expand($obj['uid'], $aclFormatter->expand($obj['deny_gid']), $check_dead);
		$recipients    = array_unique(array_merge($allow_people, $allow_circles));
		$deny          = array_unique(array_merge($deny_people, $deny_circles));
		$recipients    = array_diff($recipients, $deny);
		return $recipients;
	}

	public static function expire(int $uid, int $days, string $network = "", bool $force = false): int
	{
		if (!$uid || ($days < 1)) {
			return 0;
		}

		$condition = [
			"`uid` = ? AND NOT `deleted` AND `gravity` = ?",
			$uid, self::GRAVITY_PARENT
		];

		/*
		 * $expire_network_only = save your own wall posts
		 * and just expire conversations started by others
		 */
		$expire_network_only = DI::pConfig()->get($uid, 'expire', 'network_only', false);

		if ($expire_network_only) {
			$condition[0] .= " AND NOT `wall`";
		}

		if ($network != "") {
			$condition[0] .= " AND `network` = ?";
			$condition[] = $network;
		}

		$condition[0] .= " AND `received` < ?";
		$condition[] = DateTimeFormat::utc('now - ' . $days . ' day');

		$items = Post::select(['resource-id', 'starred', 'id', 'post-type', 'uid', 'uri-id'], $condition);

		if (!DBA::isResult($items)) {
			return 0;
		}

		$expire_items = (bool)DI::pConfig()->get($uid, 'expire', 'items', true);

		// Forcing expiring of items - but not notes and marked items
		if ($force) {
			$expire_items = true;
		}

		$expire_notes   = (bool)DI::pConfig()->get($uid, 'expire', 'notes', true);
		$expire_starred = (bool)DI::pConfig()->get($uid, 'expire', 'starred', true);
		$expire_photos  = (bool)DI::pConfig()->get($uid, 'expire', 'photos', false);

		$expired = 0;

		$priority = DI::config()->get('system', 'expire-notify-priority');

		while ($item = Post::fetch($items)) {
			// don't expire filed items
			if (DBA::exists('post-category', ['uri-id' => $item['uri-id'], 'uid' => $item['uid'], 'type' => Post\Category::FILE])) {
				continue;
			}

			// Only expire posts, not photos and photo comments

			if (!$expire_photos && !empty($item['resource-id'])) {
				continue;
			} elseif (!$expire_starred && intval($item['starred'])) {
				continue;
			} elseif (!$expire_notes && ($item['post-type'] == self::PT_PERSONAL_NOTE)) {
				continue;
			} elseif (!$expire_items && ($item['post-type'] != self::PT_PERSONAL_NOTE)) {
				continue;
			}

			self::markForDeletionById($item['id'], $priority);

			++$expired;
		}
		DBA::close($items);
		DI::logger()->notice('Expired', ['user' => $uid, 'days' => $days, 'network' => $network, 'force' => $force, 'expired' => $expired, 'expire items' => $expire_items, 'expire notes' => $expire_notes, 'expire starred' => $expire_starred, 'expire photos' => $expire_photos, 'condition' => $condition]);
		return $expired;
	}

	public static function firstPostDate(int $uid, bool $wall = false)
	{
		$user = User::getById($uid, ['register_date']);
		if (empty($user)) {
			return false;
		}

		$condition = [
			"`uid` = ? AND `wall` = ? AND NOT `deleted` AND `visible` AND `received` >= ?",
			$uid, $wall, $user['register_date']
		];
		$params = ['order' => ['received' => false]];
		$thread = Post::selectFirstThread(['received'], $condition, $params);
		if (DBA::isResult($thread)) {
			$postdate = substr(DateTimeFormat::local($thread['received']), 0, 10);
			return $postdate;
		}
		return false;
	}

	/**
	 * add/remove activity to an item
	 *
	 * Toggle activities as like,dislike,attend of an item
	 *
	 * @param int    $item_id
	 * @param string $verb
	 *            Activity verb. One of
	 *            like, unlike, dislike, undislike, attendyes, unattendyes,
	 *            attendno, unattendno, attendmaybe, unattendmaybe,
	 *            announce, unannounce
	 * @param int    $uid
	 * @param string $allow_cid
	 * @param string $allow_gid
	 * @param string $deny_cid
	 * @param string $deny_gid
	 * @return bool
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 * @hook  'post_local_end'
	 *            array $arr
	 *            'post_id' => ID of posted item
	 */
	public static function performActivity(int $item_id, string $verb, int $uid, string $allow_cid = null, string $allow_gid = null, string $deny_cid = null, string $deny_gid = null): bool
	{
		if (empty($uid)) {
			return false;
		}

		DI::logger()->notice('Start create activity', ['verb' => $verb, 'item' => $item_id, 'user' => $uid]);

		$item = Post::selectFirst(self::ITEM_FIELDLIST, ['id' => $item_id]);
		if (!DBA::isResult($item)) {
			DI::logger()->warning('Post had not been fetched', ['id' => $item_id]);
			return false;
		}

		$uri_id = $item['uri-id'];

		if (!in_array($item['uid'], [0, $uid])) {
			return false;
		}

		if (!Post::exists(['uri-id' => $item['parent-uri-id'], 'uid' => $uid])) {
			$stored = self::storeForUserByUriId($item['parent-uri-id'], $uid, ['post-reason' => Item::PR_ACTIVITY]);
			if (($item['parent-uri-id'] == $item['uri-id']) && !empty($stored)) {
				$item = Post::selectFirst(self::ITEM_FIELDLIST, ['id' => $stored]);
				if (!DBA::isResult($item)) {
					DI::logger()->info('Could not fetch just created item - should not happen', ['stored' => $stored, 'uid' => $uid, 'uri-id' => $uri_id]);
					return false;
				}
			}
		}

		// Retrieves the local post owner
		$owner = User::getOwnerDataById($uid);
		if (empty($owner)) {
			DI::logger()->info('Empty owner for user', ['uid' => $uid]);
			return false;
		}

		// Retrieve the current logged in user's public contact
		$author_id = Contact::getPublicIdByUserId($uid);
		if (empty($author_id)) {
			DI::logger()->info('Empty public contact');
			return false;
		}

		$activity = null;
		switch ($verb) {
			case 'like':
			case 'unlike':
				$activity = Activity::LIKE;
				break;
			case 'dislike':
			case 'undislike':
				$activity = Activity::DISLIKE;
				break;
			case 'attendyes':
			case 'unattendyes':
				$activity = Activity::ATTEND;
				break;
			case 'attendno':
			case 'unattendno':
				$activity = Activity::ATTENDNO;
				break;
			case 'attendmaybe':
			case 'unattendmaybe':
				$activity = Activity::ATTENDMAYBE;
				break;
			case 'follow':
			case 'unfollow':
				$activity = Activity::FOLLOW;
				break;
			case 'announce':
			case 'unannounce':
				$activity = Activity::ANNOUNCE;
				break;
			default:
				DI::logger()->warning('unknown verb', ['verb' => $verb, 'item' => $item_id]);
				return false;
		}

		$mode = Strings::startsWith($verb, 'un') ? 'delete' : 'create';

		// Enable activity toggling instead of on/off
		$event_verb_flag = $activity === Activity::ATTEND || $activity === Activity::ATTENDNO || $activity === Activity::ATTENDMAYBE;

		// Look for an existing verb row
		// Event participation activities are mutually exclusive, only one of them can exist at all times.
		if ($event_verb_flag) {
			$verbs = [Activity::ATTEND, Activity::ATTENDNO, Activity::ATTENDMAYBE];

			// Translate to the index based activity index
			$vids = [];
			foreach ($verbs as $verb) {
				$vids[] = Verb::getID($verb);
			}
		} else {
			$vids = Verb::getID($activity);
		}

		$condition = [
			'vid'       => $vids, 'deleted' => false, 'gravity' => self::GRAVITY_ACTIVITY,
			'author-id' => $author_id, 'uid' => $uid, 'thr-parent-id' => $uri_id
		];
		$like_item = Post::selectFirst(['id', 'guid', 'verb'], $condition);

		if (DBA::isResult($like_item)) {
			/**
			 * Truth table for existing activities
			 *
			 * |          Inputs            ||      Outputs      |
			 * |----------------------------||-------------------|
			 * |  Mode  | Event | Same verb || Delete? | Return? |
			 * |--------|-------|-----------||---------|---------|
			 * | create |  Yes  |    Yes    ||   No    |   Yes   |
			 * | create |  Yes  |    No     ||   Yes   |   No    |
			 * | create |  No   |    Yes    ||   No    |   Yes   |
			 * | create |  No   |    No     ||        N/A†       |
			 * | delete |  Yes  |    Yes    ||   Yes   |   N/A‡  |
			 * | delete |  Yes  |    No     ||   No    |   N/A‡  |
			 * | delete |  No   |    Yes    ||   Yes   |   N/A‡  |
			 * | delete |  No   |    No     ||        N/A†       |
			 * |--------|-------|-----------||---------|---------|
			 * |   A    |   B   |     C     || A xor C | !B or C |
			 *
			 * † Can't happen: It's impossible to find an existing non-event activity without
			 *                 the same verb because we are only looking for this single verb.
			 *
			 * ‡ The "mode = delete" is returning early whether an existing activity was found or not.
			 */
			if ($mode == 'create' xor $like_item['verb'] == $activity) {
				self::markForDeletionById($like_item['id']);
			}

			if (!$event_verb_flag || $like_item['verb'] == $activity) {
				return true;
			}
		}

		// No need to go further if we aren't creating anything
		if ($mode == 'delete') {
			return true;
		}

		$objtype = $item['resource-id'] ? Activity\ObjectType::IMAGE : Activity\ObjectType::NOTE;

		$new_item = [
			'guid'        => System::createUUID(),
			'uri'         => self::newURI(),
			'uid'         => $uid,
			'contact-id'  => $owner['id'],
			'wall'        => $item['wall'],
			'origin'      => 1,
			'network'     => Protocol::DFRN,
			'protocol'    => Conversation::PARCEL_DIRECT,
			'direction'   => Conversation::PUSH,
			'gravity'     => self::GRAVITY_ACTIVITY,
			'parent'      => $item['id'],
			'thr-parent'  => $item['uri'],
			'owner-id'    => $author_id,
			'author-id'   => $author_id,
			'body'        => $activity,
			'verb'        => $activity,
			'object-type' => $objtype,
			'allow_cid'   => $allow_cid ?? $item['allow_cid'],
			'allow_gid'   => $allow_gid ?? $item['allow_gid'],
			'deny_cid'    => $deny_cid  ?? $item['deny_cid'],
			'deny_gid'    => $deny_gid  ?? $item['deny_gid'],
			'visible'     => 1,
			'unseen'      => 1,
		];

		if (in_array($activity, [Activity::LIKE, Activity::DISLIKE])) {
			$signed = Diaspora::createLikeSignature($uid, $new_item);
			if (!empty($signed)) {
				$new_item['diaspora_signed_text'] = json_encode($signed);
			}
		}

		self::insert($new_item, true);

		// If the parent item isn't visible then set it to visible
		// @todo Check if this is still needed
		if (!$item['visible']) {
			self::update(['visible' => true], ['id' => $item['id']]);
		}
		return true;
	}

	/**
	 * Fetch the SQL condition for the given user id
	 *
	 * @param integer $owner_id User ID for which the permissions should be fetched
	 * @return array condition
	 */
	public static function getPermissionsConditionArrayByUserId(int $owner_id): array
	{
		$local_user  = DI::userSession()->getLocalUserId();
		$remote_user = DI::userSession()->getRemoteContactID($owner_id);

		// default permissions - anonymous user
		$condition = ["`private` != ?", self::PRIVATE];

		if ($local_user && ($local_user == $owner_id)) {
			// Profile owner - everything is visible
			$condition = [];
		} elseif ($remote_user) {
			// Authenticated visitor - fetch the matching permissionsets
			$permissionSets = DI::permissionSet()->selectByContactId($remote_user, $owner_id);
			if (count($permissionSets) > 0) {
				$condition = [
					"(`private` != ? OR (`private` = ? AND `wall`
					AND `psid` IN (" . implode(', ', array_fill(0, count($permissionSets), '?')) . ")))",
					self::PRIVATE, self::PRIVATE
				];
				$condition = array_merge($condition, $permissionSets->column('id'));
			}
		}

		return $condition;
	}

	/**
	 * Get a permission SQL string for the given user
	 *
	 * @param int $owner_id
	 * @param string $table
	 * @return string
	 */
	public static function getPermissionsSQLByUserId(int $owner_id, string $table = ''): string
	{
		$local_user  = DI::userSession()->getLocalUserId();
		$remote_user = DI::userSession()->getRemoteContactID($owner_id);

		if (!empty($table)) {
			$table = DBA::quoteIdentifier($table) . '.';
		}

		// Profile owner - everything is visible
		if ($local_user && ($local_user == $owner_id)) {
			return '';
		}

		if ($remote_user) {
			/*
			 * Authenticated visitor. Unless pre-verified,
			 * check that the contact belongs to this $owner_id
			 * and load the circles the visitor belongs to.
			 * If pre-verified, the caller is expected to have already
			 * done this and passed the circles into this function.
			 */
			$permissionSets = DI::permissionSet()->selectByContactId($remote_user, $owner_id);

			$sql_set = '';

			if (count($permissionSets) > 0) {
				$sql_set = sprintf(" OR (" . $table . "`private` = %d AND " . $table . "`wall` AND " . $table . "`psid` IN (", self::PRIVATE) . implode(',', $permissionSets->column('id')) . "))";
			}

			return sprintf(" AND (" . $table . "`private` != %d", self::PRIVATE) . $sql_set . ")";
		}

		/*
		 * Construct permissions
		 *
		 * default permissions - anonymous user
		 */
		return sprintf(" AND " . $table . "`private` != %d", self::PRIVATE);
	}

	/**
	 * get translated item type
	 *
	 * @param array                $item
	 * @param \Friendica\Core\L10n $l10n
	 * @return string
	 */
	public static function postType(array $item, \Friendica\Core\L10n $l10n): string
	{
		if (!empty($item['event-id'])) {
			return $l10n->t('event');
		} elseif (!empty($item['resource-id'])) {
			return $l10n->t('photo');
		} elseif ($item['gravity'] == self::GRAVITY_ACTIVITY) {
			return $l10n->t('activity');
		} elseif ($item['gravity'] == self::GRAVITY_COMMENT) {
			return $l10n->t('comment');
		}

		return $l10n->t('post');
	}

	/**
	 * Sets the "rendered-html" field of the provided item
	 *
	 * Body is preserved to avoid side-effects as we modify it just-in-time for spoilers and private image links
	 *
	 * @param array $item
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @todo Remove reference, simply return "rendered-html" and "rendered-hash"
	 */
	private static function putInCache(&$item)
	{
		// Save original body to prevent addons to modify it
		$body = $item['body'];

		$rendered_hash = $item['rendered-hash'] ?? '';
		$rendered_html = $item['rendered-html'] ?? '';

		if (
			$rendered_hash == ''
			|| $rendered_html == ''
			|| $rendered_hash != hash('md5', BBCode::VERSION . '::' . $body)
			|| DI::config()->get('system', 'ignore_cache')
		) {
			$item['rendered-html'] = BBCode::convertForUriId($item['uri-id'], $item['body']);
			$item['rendered-hash'] = hash('md5', BBCode::VERSION . '::' . $body);

			$hook_data = ['item' => $item, 'rendered-html' => $item['rendered-html'], 'rendered-hash' => $item['rendered-hash']];
			Hook::callAll('put_item_in_cache', $hook_data);
			$item['rendered-html'] = $hook_data['rendered-html'];
			$item['rendered-hash'] = $hook_data['rendered-hash'];
			unset($hook_data);

			// Update if the generated values differ from the existing ones
			if ((($rendered_hash != $item['rendered-hash']) || ($rendered_html != $item['rendered-html'])) && !empty($item['id'])) {
				self::update(
					[
						'rendered-html' => $item['rendered-html'],
						'rendered-hash' => $item['rendered-hash']
					],
					['id' => $item['id']]
				);
			}
		}

		$item['body'] = $body;
	}

	/**
	 * Given an item array, convert the body element from bbcode to html and add smilie icons.
	 * If attach is true, also add icons for item attachments.
	 *
	 * @param array   $item Record from item table
	 * @param boolean $attach If true, add icons for item attachments as well
	 * @param boolean $is_preview Whether this is a preview
	 * @param boolean $only_cache Whether only cached HTML should be updated
	 * @return string item body html
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 * @hook  prepare_body_init item array before any work
	 * @hook  prepare_body_content_filter ('item'=>item array, 'filter_reasons'=>string array) before first bbcode to html
	 * @hook  prepare_body ('item'=>item array, 'html'=>body string, 'is_preview'=>boolean, 'filter_reasons'=>string array) after first bbcode to html
	 * @hook  prepare_body_final ('item'=>item array, 'html'=>body string) after attach icons and blockquote special case handling (spoiler, author)
	 */
	public static function prepareBody(array &$item, bool $attach = false, bool $is_preview = false, bool $only_cache = false): string
	{
		$appHelper = DI::appHelper();
		$uid       = DI::userSession()->getLocalUserId();
		Hook::callAll('prepare_body_init', $item);

		// In order to provide theme developers more possibilities, event items
		// are treated differently.
		if ($item['object-type'] === Activity\ObjectType::EVENT && isset($item['event-id'])) {
			$ev = Event::getItemHTML($item);
			return $ev;
		}

		$tags = Tag::populateFromItem($item);

		$item['tags']     = $tags['tags'];
		$item['hashtags'] = $tags['hashtags'];
		$item['mentions'] = $tags['mentions'];

		if (!$is_preview) {
			$item['body'] = preg_replace("#\s*\[attachment .*?].*?\[/attachment]\s*#ism", "\n", $item['body']);
			$item['body'] = Post\Media::removeFromEndOfBody($item['body'] ?? '');
			$item['body'] = Post\Media::replaceImage($item['body']);
		}

		$body = $item['body'];
		if ($is_preview) {
			$item['body'] = preg_replace("#\s*\[attachment .*?].*?\[/attachment]\s*#ism", "\n", $item['body']);
		}

		$fields = ['uri-id', 'uri', 'body', 'title', 'author-name', 'author-link', 'author-avatar', 'author-gsid', 'guid', 'created', 'plink', 'network', 'has-media', 'quote-uri-id', 'post-type'];

		$shared_uri_id      = 0;
		$shared_links       = [];
		$quote_shared_links = [];
		$shared_item        = [];

		$shared = DI::contentItem()->getSharedPost($item, $fields);
		if (!empty($shared['post'])) {
			$shared_item         = $shared['post'];
			$shared_item['body'] = Post\Media::removeFromEndOfBody($shared_item['body']);
			$shared_item['body'] = Post\Media::replaceImage($shared_item['body']);
			$quote_uri_id        = $shared['post']['uri-id'];
			$shared_links[]      = strtolower($shared['post']['uri']);
			$item['body']        = BBCode::removeSharedData($item['body']);
		} elseif (empty($shared_item['uri-id']) && empty($item['quote-uri-id']) && ($item['network'] != Protocol::DIASPORA)) {
			$media = Post\Media::getByURIId($item['uri-id'], [Post\Media::ACTIVITY]);
			if (!empty($media) && ($media[0]['media-uri-id'] != $item['uri-id'])) {
				$shared_item = Post::selectFirst($fields, ['uri-id' => $media[0]['media-uri-id'], 'uid' => [$item['uid'], 0]]);
				if (empty($shared_item['uri-id'])) {
					$shared_item = Post::selectFirst($fields, ['plink' => $media[0]['url'], 'uid' => [$item['uid'], 0]]);
				} elseif (!in_array(strtolower($media[0]['url']), $shared_links)) {
					$shared_links[] = strtolower($media[0]['url']);
				}

				if (empty($shared_item['uri-id'])) {
					$shared_item    = Post::selectFirst($fields, ['uri' => $media[0]['url'], 'uid' => [$item['uid'], 0]]);
					$shared_links[] = strtolower($media[0]['url']);
				}

				if (!empty($shared_item['uri-id'])) {
					$data = BBCode::getAttachmentData($shared_item['body']);
					if (!empty($data['url'])) {
						$quote_shared_links[] = $data['url'];
					}

					$quote_uri_id = $shared_item['uri-id'];
				}
			}
		}

		if (!empty($quote_uri_id)) {
			if (isset($shared_item['plink'])) {
				$item['body'] .= "\n" . DI::contentItem()->createSharedBlockByArray($shared_item, false, true);
			} else {
				DI::logger()->warning('Missing plink in shared item', ['item' => $item, 'shared' => $shared, 'quote_uri_id' => $quote_uri_id, 'shared_item' => $shared_item]);
			}
		}

		$sharedSplitAttachments = [];

		if (!empty($shared_item['uri-id'])) {
			$shared_uri_id          = $shared_item['uri-id'];
			$shared_links[]         = strtolower($shared_item['plink']);
			$sharedSplitAttachments = DI::postMediaRepository()->splitAttachments($shared_uri_id, [], $shared_item['has-media']);
			$shared_links           = array_merge($shared_links, $sharedSplitAttachments['visual']->column('url'));
			$shared_links           = array_merge($shared_links, $sharedSplitAttachments['link']->column('url'));
			$shared_links           = array_merge($shared_links, $sharedSplitAttachments['additional']->column('url'));
			$item['body']           = self::replaceVisualAttachments($sharedSplitAttachments['visual'], $item['body']);
		}

		$itemSplitAttachments = DI::postMediaRepository()->splitAttachments($item['uri-id'], $shared_links, $item['has-media'] ?? false);
		$item['body']         = self::replaceVisualAttachments($itemSplitAttachments['visual'], $item['body'] ?? '');

		self::putInCache($item);
		$item['body'] = $body;
		$s            = $item["rendered-html"];

		if ($only_cache) {
			return '';
		}

		// Compile eventual content filter reasons
		$filter_reasons = [];
		if (!$is_preview && DI::userSession()->getPublicContactId() != $item['author-id']) {
			if (!empty($item['user-blocked-author']) || !empty($item['user-blocked-owner'])) {
				$filter_reasons[] = DI::l10n()->t('%s is blocked', $item['author-name']);
			} elseif (!empty($item['user-ignored-author']) || !empty($item['user-ignored-owner'])) {
				$filter_reasons[] = DI::l10n()->t('%s is ignored', $item['author-name']);
			} elseif (!empty($item['user-collapsed-author']) || !empty($item['user-collapsed-owner'])) {
				$filter_reasons[] = DI::l10n()->t('Content from %s is collapsed', $item['author-name']);
			}

			if ($item['sensitive'] && (!$uid || !DI::pConfig()->get($uid, 'system', 'disable_cw', false))) {
				$filter_reasons[] = DI::l10n()->t('Sensitive content');
			}

			$item['attachments'] = $itemSplitAttachments;

			$hook_data = [
				'item'           => $item,
				'filter_reasons' => $filter_reasons
			];
			Hook::callAll('prepare_body_content_filter', $hook_data);
			$filter_reasons = $hook_data['filter_reasons'];
			unset($hook_data);
		}

		if (!empty($shared_item['uri-id'])) {
			if (!$s) {
				DI::logger()->notice('Unexpected empty item HTML', ['item' => $item]);
			}

			$s = self::replacePlatformIcon($s, $shared_item, $uid);
		}

		$hook_data = [
			'item'           => $item,
			'html'           => $s,
			'preview'        => $is_preview,
			'filter_reasons' => $filter_reasons
		];
		Hook::callAll('prepare_body', $hook_data);
		$s = $hook_data['html'];

		unset($hook_data);

		if (!$attach) {
			// Replace the blockquotes with quotes that are used in mails.
			$mailquote = '<blockquote type="cite" class="gmail_quote" style="margin:0 0 0 .8ex;border-left:1px #ccc solid;padding-left:1ex;">';
			$s         = str_replace(['<blockquote>', '<blockquote class="spoiler">', '<blockquote class="author">'], [$mailquote, $mailquote, $mailquote], $s);
			return $s;
		}

		if (!empty($sharedSplitAttachments)) {
			$s    = self::addGallery($s, $sharedSplitAttachments['visual']);
			$s    = self::addVisualAttachments($sharedSplitAttachments['visual'], $shared_item, $s, true);
			$s    = self::addLinkAttachment($shared_uri_id ?: $item['uri-id'], $sharedSplitAttachments, $body, $s, true, $quote_shared_links);
			$s    = self::addNonVisualAttachments($sharedSplitAttachments['additional'], $item, $s);
			$body = BBCode::removeSharedData($body);
		}

		$pos = strpos($s, BBCode::SHARED_ANCHOR);
		if ($pos) {
			$shared_html = substr($s, $pos + strlen(BBCode::SHARED_ANCHOR));
			$s           = substr($s, 0, $pos);
		}

		$s = self::addGallery($s, $itemSplitAttachments['visual']);
		$s = self::addVisualAttachments($itemSplitAttachments['visual'], $item, $s, false);
		$s = self::addLinkAttachment($item['uri-id'], $itemSplitAttachments, $body, $s, false, $shared_links);
		$s = self::addNonVisualAttachments($itemSplitAttachments['additional'], $item, $s);
		$s = self::addQuestions($item, $s);

		// Map.
		if (strpos($s, '<div class="map">') !== false && !empty($item['coord'])) {
			$x = Map::byCoordinates(trim($item['coord']));
			if ($x) {
				$s = preg_replace('/\<div class\=\"map\"\>/', '$0' . $x, $s);
			}
		}

		// Replace friendica image url size with theme preference.
		if (!empty($appHelper->getThemeInfoValue('item_image_size'))) {
			$ps = $appHelper->getThemeInfoValue('item_image_size');
			$s  = preg_replace('|(<img[^>]+src="[^"]+/photo/[0-9a-f]+)-[0-9]|', "$1-" . $ps, $s);
		}

		if (!empty($shared_html)) {
			$s .= $shared_html;
		}

		$s = HTML::applyContentFilter($s, $filter_reasons);

		$hook_data = ['item' => $item, 'html' => $s];
		Hook::callAll('prepare_body_final', $hook_data);
		return $hook_data['html'];
	}

	/**
	 * Replace the platform icon with the icon in the style selected by the user
	 *
	 * @param string $html
	 * @param array $item
	 * @param integer $uid
	 * @return string
	 */
	private static function replacePlatformIcon(string $html, array $item, int $uid): string
	{
		if ($html === '') {
			return $html;
		}

		$dom = new \DOMDocument();
		if (!@$dom->loadHTML($html)) {
			return $html;
		}

		$svg = ContactSelector::networkToSVG($item['network'], $item['author-gsid'], '', $uid);
		if (empty($svg)) {
			return $html;
		}

		$xpath = new \DOMXPath($dom);
		/** @var \DOMElement $element */
		foreach ($xpath->query("//img[@class='network-svg']") as $element) {
			$src = $element->getAttributeNode('src')->nodeValue;
			if ($src == $svg) {
				continue;
			}
			$element_html = $element->ownerDocument->saveHTML($element);
			$html         = str_replace($element_html, str_replace($src, $svg, $element_html), $html);
		}
		return $html;
	}

	/**
	 * Modify links to pictures to links for the "Fancybox" gallery
	 *
	 * @param string     $s
	 * @param PostMedias $PostMedias
	 * @return string
	 */
	private static function addGallery(string $s, PostMedias $PostMedias): string
	{
		foreach ($PostMedias as $PostMedia) {
			if (!$PostMedia->preview || ($PostMedia->type !== Post\Media::IMAGE)) {
				continue;
			}

			if ($PostMedia->hasDimensions()) {
				$pattern = '#<a href="' . preg_quote($PostMedia->url) . '">(.*?)"></a>#';

				$s = preg_replace_callback($pattern, function () use ($PostMedia) {
					return Renderer::replaceMacros(Renderer::getMarkupTemplate('content/image/single_with_height_allocation.tpl'), [
						'$image'               => $PostMedia,
						'$allocated_height'    => $PostMedia->getAllocatedHeight(),
						'$allocated_max_width' => ($PostMedia->previewWidth ?? $PostMedia->width) . 'px',
					]);
				}, $s);
			} else {
				$s = str_replace('<a href="' . $PostMedia->url . '"', '<a data-fancybox="uri-id-' . $PostMedia->uriId . '" href="' . $PostMedia->url . '"', $s);
			}
		}

		return $s;
	}

	/**
	 * Check if the body contains a link
	 *
	 * @param string $body
	 * @param string $url
	 * @param int    $type
	 * @return bool
	 */
	public static function containsLink(string $body, string $url, int $type = 0): bool
	{
		// Make sure that for example site parameters aren't used when testing if the link is contained in the body
		$urlparts = parse_url($url);
		if (empty($urlparts)) {
			return false;
		}

		unset($urlparts['query']);
		unset($urlparts['fragment']);

		try {
			$url = (string)Uri::fromParts((array)$urlparts);
		} catch (\InvalidArgumentException $e) {
			DI::logger()->notice('Invalid URL', ['$url' => $url, '$urlparts' => $urlparts]);
			/* See https://github.com/friendica/friendica/issues/12113
			 * Malformed URLs will result in a Fatal Error
			 */
			return false;
		}

		// Remove media links to only search in embedded content
		// @todo Check images for image link, audio for audio links, ...
		if (in_array($type, [Post\Media::AUDIO, Post\Media::VIDEO, Post\Media::IMAGE])) {
			$body = preg_replace("/\[url=[^\[\]]*\](.*)\[\/url\]/Usi", ' $1 ', $body);
		}

		if (strpos($body, $url)) {
			return true;
		}

		foreach ([0, 1, 2] as $size) {
			if (
				preg_match('#/photo/.*-' . $size . '\.#ism', $url) &&
				strpos(preg_replace('#(/photo/.*)-[012]\.#ism', '$1-' . $size . '.', $body), $url)
			) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Replace visual attachments in the body
	 *
	 * @param PostMedias $PostMedias
	 * @param string     $body
	 * @return string modified body
	 */
	private static function replaceVisualAttachments(PostMedias $PostMedias, string $body): string
	{
		DI::profiler()->startRecording('rendering');

		foreach ($PostMedias as $PostMedia) {
			if ($PostMedia->preview) {
				if (DI::baseUrl()->isLocalUri($PostMedia->preview)) {
					continue;
				}
				$proxy   = DI::baseUrl() . $PostMedia->getPreviewPath(Proxy::SIZE_LARGE);
				$search  = ['[img=' . $PostMedia->preview . ']', ']' . $PostMedia->preview . '[/img]'];
				$replace = ['[img=' . $proxy . ']', ']' . $proxy . '[/img]'];

				$body = str_replace($search, $replace, $body);
			} elseif ($PostMedia->mimetype->type == 'image') {
				if (DI::baseUrl()->isLocalUri($PostMedia->url)) {
					continue;
				}
				$proxy   = DI::baseUrl() . $PostMedia->getPreviewPath(Proxy::SIZE_LARGE);
				$search  = ['[img=' . $PostMedia->url . ']', ']' . $PostMedia->url . '[/img]'];
				$replace = ['[img=' . $proxy . ']', ']' . $proxy . '[/img]'];

				$body = str_replace($search, $replace, $body);
			}
		}
		DI::profiler()->stopRecording();
		return $body;
	}

	/**
	 * Add visual attachments to the content
	 *
	 * @param PostMedias $PostMedias
	 * @param array      $item
	 * @param string     $content
	 * @param bool       $shared
	 * @return string modified content
	 * @throws ServiceUnavailableException
	 */
	private static function addVisualAttachments(PostMedias $PostMedias, array $item, string $content, bool $shared): string
	{
		DI::profiler()->startRecording('rendering');
		$leading  = '';
		$trailing = '';
		$images   = new PostMedias();

		// @todo In the future we should make a single for the template engine with all media in it. This allows more flexibilty.
		foreach ($PostMedias as $PostMedia) {
			if (self::containsLink($item['body'], $PostMedia->preview ?? $PostMedia->url, $PostMedia->type)) {
				continue;
			}

			if ($PostMedia->mimetype->type == 'image' || $PostMedia->preview) {
				$preview_size = Proxy::SIZE_MEDIUM;
				$preview_url  = DI::baseUrl() . $PostMedia->getPreviewPath($preview_size);
			} else {
				$preview_size = 0;
				$preview_url  = '';
			}

			if ($preview_url && self::containsLink($item['body'], $preview_url)) {
				continue;
			}

			if (($PostMedia->mimetype->type == 'video') || ($PostMedia->type == Post\Media::HLS)) {
				if (($PostMedia->height ?? 0) > ($PostMedia->width ?? 0)) {
					$height = min(DI::config()->get('system', 'max_video_height') ?: '100%', $PostMedia->height);
					$width  = 'auto';
				} else {
					$height = 'auto';
					$width  = '100%';
				}
				/// @todo Move the template to /content as well
				$media = Renderer::replaceMacros(Renderer::getMarkupTemplate($PostMedia->type == Post\Media::HLS ? 'hls_top.tpl' : 'video_top.tpl'), [
					'$video' => [
						'id'          => $PostMedia->id,
						'src'         => (string)$PostMedia->url,
						'name'        => $PostMedia->name ?: $PostMedia->url,
						'preview'     => $preview_url,
						'mime'        => (string)$PostMedia->mimetype,
						'height'      => $height,
						'width'       => $width,
						'description' => $PostMedia->description,
					],
				]);
				if (($item['post-type'] ?? null) == Item::PT_VIDEO) {
					$leading .= $media;
				} else {
					$trailing .= $media;
				}
			} elseif ($PostMedia->mimetype->type == 'audio') {
				$media = Renderer::replaceMacros(Renderer::getMarkupTemplate('content/audio.tpl'), [
					'$audio' => [
						'id'   => $PostMedia->id,
						'src'  => (string)$PostMedia->url,
						'name' => $PostMedia->name ?: $PostMedia->url,
						'mime' => (string)$PostMedia->mimetype,
					],
				]);
				if (($item['post-type'] ?? null) == Item::PT_AUDIO) {
					$leading .= $media;
				} else {
					$trailing .= $media;
				}
			} elseif ($PostMedia->mimetype->type == 'image') {
				$src_url = DI::baseUrl() . $PostMedia->getPhotoPath();
				if (self::containsLink($item['body'], $src_url)) {
					continue;
				}

				if (empty($PostMedia->description) && DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'accessibility', 'hide_empty_descriptions')) {
					continue;
				}
				$images[] = $PostMedia->withUrl(new Uri($src_url))->withPreview(new Uri($preview_url), $preview_size);
			}
		}

		$media = Image::getBodyAttachHtml($images);

		// On Diaspora posts the attached pictures are leading
		if ($item['network'] == Protocol::DIASPORA) {
			$leading .= $media;
		} else {
			$trailing .= $media;
		}

		if ($shared) {
			$content = str_replace(BBCode::TOP_ANCHOR, '<div class="body-attach">' . $leading . '</div>' . BBCode::TOP_ANCHOR, $content);
			$content = str_replace(BBCode::BOTTOM_ANCHOR, '<div class="body-attach">' . $trailing . '</div>' . BBCode::BOTTOM_ANCHOR, $content);
		} else {
			if ($leading != '') {
				$content = '<div class="body-attach">' . $leading . '</div>' . $content;
			}

			if ($trailing != '') {
				$content .= '<div class="body-attach">' . $trailing . '</div>';
			}
		}

		DI::profiler()->stopRecording();
		return $content;
	}

	/**
	 * Add link attachment to the content
	 *
	 * @param int          $uriid
	 * @param PostMedias[] $attachments
	 * @param string       $body
	 * @param string       $content
	 * @param bool         $shared
	 * @param array        $ignore_links A list of URLs to ignore
	 * @return string modified content
	 * @throws InternalServerErrorException
	 * @throws ServiceUnavailableException
	 */
	private static function addLinkAttachment(int $uriid, array $attachments, string $body, string $content, bool $shared, array $ignore_links): string
	{
		DI::profiler()->startRecording('rendering');
		// Don't show a preview when there is a visual attachment (audio or video)
		$types   = $attachments['visual']->column('type');
		$preview = !in_array(PostMedia::TYPE_IMAGE, $types) && !in_array(PostMedia::TYPE_VIDEO, $types);

		/** @var ?PostMedia $attachment */
		$attachment = null;
		foreach ($attachments['link'] as $PostMedia) {
			$found = false;
			foreach ($ignore_links as $ignore_link) {
				if (Strings::compareLink($PostMedia->url, $ignore_link)) {
					$found = true;
				}
			}
			// @todo Judge between the links to use the one with most information
			if (!$found && (empty($attachment) || $PostMedia->authorName ||
				(!$attachment->name && $PostMedia->name) ||
				(!$attachment->description && $PostMedia->description) ||
				(!$attachment->preview && $PostMedia->preview))) {
				$attachment = $PostMedia;
			}
		}

		if (!empty($attachment)) {
			$data = [
				'after'         => '',
				'author_name'   => $attachment->authorName ?? '',
				'author_url'    => (string)($attachment->authorUrl ?? ''),
				'description'   => $attachment->description ?? '',
				'image'         => '',
				'preview'       => '',
				'provider_name' => $attachment->publisherName ?? '',
				'provider_url'  => (string)($attachment->publisherUrl ?? ''),
				'text'          => '',
				'title'         => $attachment->name ?? '',
				'type'          => 'link',
				'url'           => (string)$attachment->url,
			];

			if ($preview && $attachment->preview) {
				if ($attachment->previewWidth >= 500) {
					$data['image'] = DI::baseUrl() . $attachment->getPreviewPath(Proxy::SIZE_MEDIUM);
				} else {
					$data['preview'] = DI::baseUrl() . $attachment->getPreviewPath(Proxy::SIZE_MEDIUM);
				}
			}

			if (!empty($data['description']) && !empty($content)) {
				similar_text($data['description'], $content, $percent);
			} else {
				$percent = 0;
			}

			if (!empty($data['description']) && (($data['title'] == $data['description']) || ($percent > 95) || (strpos($content, $data['description']) !== false))) {
				$data['description'] = '';
			}

			if (($data['author_name'] ?? '') == ($data['provider_name'] ?? '')) {
				$data['author_name'] = '';
			}

			if (($data['author_url'] ?? '') == ($data['provider_url'] ?? '')) {
				$data['author_url'] = '';
			}
		} elseif (preg_match("/.*(\[attachment.*?\].*?\[\/attachment\]).*/ism", $body, $match)) {
			$data = BBCode::getAttachmentData($match[1]);
		}

		DI::profiler()->stopRecording();

		if (isset($data['url']) && !in_array(strtolower($data['url']), $ignore_links)) {
			if (!empty($data['description']) || !empty($data['image']) || !empty($data['preview']) || (!empty($data['title']) && !Strings::compareLink($data['title'], $data['url']))) {
				$parts = parse_url($data['url']);
				if (!empty($parts['scheme']) && !empty($parts['host'])) {
					if (empty($data['provider_name'])) {
						$data['provider_name'] = $parts['host'];
					}
					if (empty($data['provider_url']) || empty(parse_url($data['provider_url'], PHP_URL_SCHEME))) {
						$data['provider_url'] = $parts['scheme'] . '://' . $parts['host'];

						if (!empty($parts['port'])) {
							$data['provider_url'] .= ':' . $parts['port'];
						}
					}
				}

				// @todo Use a template
				$preview_mode = DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'system', 'preview_mode', BBCode::PREVIEW_LARGE);
				if ($preview_mode != BBCode::PREVIEW_NONE) {
					$rendered = BBCode::convertAttachment('', BBCode::INTERNAL, false, $data, $uriid, $preview_mode);
				} elseif (!self::containsLink($content, $data['url'], Post\Media::HTML)) {
					$rendered = Renderer::replaceMacros(Renderer::getMarkupTemplate('content/link.tpl'), [
						'$url'   => $data['url'],
						'$title' => $data['title'],
					]);
				} else {
					$rendered = '';
				}
			} elseif (!self::containsLink($content, $data['url'], Post\Media::HTML)) {
				$rendered = Renderer::replaceMacros(Renderer::getMarkupTemplate('content/link.tpl'), [
					'$url'   => $data['url'],
					'$title' => $data['title'],
				]);
			} else {
				return $content;
			}

			if ($shared) {
				return str_replace(BBCode::BOTTOM_ANCHOR, BBCode::BOTTOM_ANCHOR . $rendered, $content);
			} else {
				return $content . $rendered;
			}
		}
		return $content;
	}

	/**
	 * Add non-visual attachments to the content
	 *
	 * @param PostMedias $PostMedias
	 * @param array      $item
	 * @param string     $content
	 * @return string modified content
	 * @throws InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function addNonVisualAttachments(PostMedias $PostMedias, array $item, string $content): string
	{
		DI::profiler()->startRecording('rendering');
		$trailing = '';
		foreach ($PostMedias as $PostMedia) {
			if (strpos($item['body'], (string)$PostMedia->url)) {
				continue;
			}

			$author = [
				'uid'     => 0,
				'id'      => $item['author-id'],
				'network' => $item['author-network'],
				'url'     => $item['author-link'],
				'alias'   => $item['author-alias']
			];
			$the_url = Contact::magicLinkByContact($author, $PostMedia->url);

			$title = Strings::escapeHtml(trim($PostMedia->description ?? '' ?: $PostMedia->url));

			if ($PostMedia->size) {
				$title .= ' ' . $PostMedia->size . ' ' . DI::l10n()->t('bytes');
			}

			/// @todo Use a template
			$icon = '<div class="attachtype icon s22 type-' . $PostMedia->mimetype->type . ' subtype-' . $PostMedia->mimetype->subtype . '"></div>';
			$trailing .= '<a href="' . strip_tags($the_url) . '" title="' . $title . '" class="attachlink" target="_blank" rel="noopener noreferrer" >' . $icon . '</a>';
		}

		if ($trailing != '') {
			$content .= '<div class="body-attach">' . $trailing . '</div>';
		}

		DI::profiler()->stopRecording();
		return $content;
	}

	private static function addQuestions(array $item, string $content): string
	{
		DI::profiler()->startRecording('rendering');
		if (!empty($item['question-id'])) {
			$question = [
				'id'       => $item['question-id'],
				'multiple' => $item['question-multiple'],
				'voters'   => $item['question-voters'],
				'endtime'  => $item['question-end-time']
			];

			$options = Post\QuestionOption::getByURIId($item['uri-id']);
			foreach ($options as $key => $option) {
				if ($question['voters'] > 0) {
					$percent               = $option['replies'] / $question['voters'] * 100;
					$options[$key]['vote'] = DI::l10n()->tt('%2$s (%3$d%%, %1$d vote)', '%2$s (%3$d%%, %1$d votes)', $option['replies'] ?? 0, $option['name'], round($percent, 1));
				} else {
					$options[$key]['vote'] = DI::l10n()->tt('%2$s (%1$d vote)', '%2$s (%1$d votes)', $option['replies'] ?? 0, $option['name']);
				}
			}

			if (!empty($question['voters']) && !empty($question['endtime'])) {
				$summary = DI::l10n()->tt('%d voter. Poll end: %s', '%d voters. Poll end: %s', $question['voters'] ?? 0, Temporal::getRelativeDate($question['endtime']));
			} elseif (!empty($question['voters'])) {
				$summary = DI::l10n()->tt('%d voter.', '%d voters.', $question['voters'] ?? 0);
			} elseif (!empty($question['endtime'])) {
				$summary = DI::l10n()->t('Poll end: %s', Temporal::getRelativeDate($question['endtime']));
			} else {
				$summary = '';
			}

			$content .= Renderer::replaceMacros(Renderer::getMarkupTemplate('content/question.tpl'), [
				'$question' => $question,
				'$options'  => $options,
				'$summary'  => $summary,
			]);
		}
		DI::profiler()->stopRecording();
		return $content;
	}

	/**
	 * get private link for item
	 *
	 * @param array $item
	 * @return boolean|array False if item has not plink, otherwise array('href'=>plink url, 'title'=>translated title)
	 * @throws \Exception
	 */
	public static function getPlink(array $item)
	{
		if (!empty($item['plink']) && Network::isValidHttpUrl($item['plink'])) {
			$plink = $item['plink'];
		} elseif (!empty($item['uri']) && Network::isValidHttpUrl($item['uri']) && !DI::baseUrl()->isLocalUrl($item['uri'])) {
			$plink = $item['uri'];
		}

		if (($item['post-reason'] == self::PR_ANNOUNCEMENT) && ($item['owner-contact-type'] == Contact::TYPE_COMMUNITY) && ($item['owner-network'] == Protocol::DFRN)) {
			$contact = Contact::getById($item['owner-id'], ['baseurl']);
			if (!empty($contact['baseurl'])) {
				$plink = $contact['baseurl'] . '/display/' . $item['guid'];
			}
		}

		if (DI::userSession()->getLocalUserId()) {
			$ret = [
				'href'       => "display/" . $item['guid'],
				'orig'       => "display/" . $item['guid'],
				'title'      => DI::l10n()->t('View on separate page'),
				'orig_title' => DI::l10n()->t('View on separate page'),
			];

			if (!empty($plink) && ($item['private'] == self::PRIVATE)) {
				$author = [
					'uid'     => 0,
					'id'      => $item['author-id'],
					'network' => $item['author-network'],
					'url'     => $item['author-link'],
					'alias'   => $item['author-alias'],
				];
				$plink = Contact::magicLinkByContact($author, $plink);
			}

			if (!empty($plink)) {
				$ret['href']  = DI::baseUrl()->remove($plink);
				$ret['title'] = DI::l10n()->t('Link to source');
			}
		} elseif (!empty($plink) && ($item['private'] != self::PRIVATE)) {
			$ret = [
				'href'       => $plink,
				'orig'       => $plink,
				'title'      => DI::l10n()->t('Link to source'),
				'orig_title' => DI::l10n()->t('Link to source'),
			];
		} else {
			$ret = [];
		}

		return $ret;
	}

	/**
	 * Does the given uri-id belongs to a post that is sent as starting post to a group?
	 * This does apply to posts that are sent via ! and not in parallel to a group via @
	 *
	 * @param int $uri_id
	 *
	 * @return boolean "true" when it is a group post
	 */
	public static function isGroupPost(int $uri_id): bool
	{
		if (Post::exists(['private' => Item::PUBLIC, 'uri-id' => $uri_id])) {
			return false;
		}

		foreach (Tag::getByURIId($uri_id, [Tag::EXCLUSIVE_MENTION, Tag::AUDIENCE]) as $tag) {
			// @todo Possibly check for a public audience in the future, see https://socialhub.activitypub.rocks/t/fep-1b12-group-federation/2724
			// and https://codeberg.org/fediverse/fep/src/branch/main/feps/fep-1b12.md
			if (DBA::exists('contact', ['uid' => 0, 'nurl' => Strings::normaliseLink($tag['url']), 'contact-type' => Contact::TYPE_COMMUNITY])) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Search item id for given URI or plink
	 *
	 * @param string $uri
	 * @param integer $uid
	 *
	 * @return integer item id
	 */
	public static function searchByLink(string $uri, int $uid = 0): int
	{
		$ssl_uri = str_replace('http://', 'https://', $uri);
		$uris    = [$uri, $ssl_uri, Strings::normaliseLink($uri)];

		$item = Post::selectFirst(['id'], ['uri' => $uris, 'uid' => $uid]);
		if (DBA::isResult($item)) {
			return $item['id'];
		}

		$item = Post::selectFirst(['id'], ['plink' => $uris, 'uid' => $uid]);
		if (DBA::isResult($item)) {
			return $item['id'];
		}

		return 0;
	}

	/**
	 * Return the URI for a link to the post
	 *
	 * @param string $uri URI or link to post
	 *
	 * @return string URI
	 */
	public static function getURIByLink(string $uri): string
	{
		$ssl_uri = str_replace('http://', 'https://', $uri);
		$uris    = [$uri, $ssl_uri, Strings::normaliseLink($uri)];

		$item = Post::selectFirst(['uri'], ['uri' => $uris]);
		if (DBA::isResult($item)) {
			return $item['uri'];
		}

		$item = Post::selectFirst(['uri'], ['plink' => $uris]);
		if (DBA::isResult($item)) {
			return $item['uri'];
		}

		return '';
	}

	/**
	 * Fetches item for given URI or plink
	 *
	 * @param string $uri
	 * @param int    $uid
	 * @param int    $completion
	 * @param string $mimetype
	 *
	 * @return integer item id
	 */
	public static function fetchByLink(string $uri, int $uid = 0, int $completion = ActivityPub\Receiver::COMPLETION_MANUAL, string $mimetype = ''): int
	{
		DI::logger()->info('Trying to fetch link', ['uid' => $uid, 'uri' => $uri]);
		$item_id = self::searchByLink($uri, $uid);
		if (!empty($item_id)) {
			DI::logger()->info('Link found', ['uid' => $uid, 'uri' => $uri, 'id' => $item_id]);
			return $item_id;
		}

		if (ActivityPub\Processor::alreadyKnown($uri, '')) {
			return 0;
		}

		$hookData = [
			'uri'     => $uri,
			'uid'     => $uid,
			'item_id' => null,
		];

		Hook::callAll('item_by_link', $hookData);

		if (isset($hookData['item_id'])) {
			DI::logger()->info('Hook link fetched', ['uid' => $uid, 'uri' => $uri, 'id' => $hookData['item_id']]);
			return is_numeric($hookData['item_id']) ? $hookData['item_id'] : 0;
		}

		if (!$mimetype) {
			try {
				$curlResult = DI::httpClient()->head($uri, [HttpClientOptions::ACCEPT_CONTENT => HttpClientAccept::JSON_AS, HttpClientOptions::REQUEST => HttpClientRequest::ACTIVITYPUB]);
				$mimetype   = $curlResult->getContentType();
			} catch (\Throwable $th) {
				DI::logger()->info('Error while fetching HTTP link via HEAD', ['uid' => $uid, 'uri' => $uri, 'code' => $th->getCode(), 'message' => $th->getMessage()]);
				return 0;
			}
		}

		if (!HTTPSignature::isValidContentType($mimetype, $uri) && (current(explode(';', $mimetype)) == 'application/json')) {
			try {
				// Issue 14126: Workaround for Mastodon servers that return "application/json" on a "head" request.
				$curlResult = HTTPSignature::fetchRaw($uri, $uid);
				$mimetype   = $curlResult->getContentType();
			} catch (\Throwable $th) {
				DI::logger()->info('Error while fetching HTTP link via signed GET', ['uid' => $uid, 'uri' => $uri, 'code' => $th->getCode(), 'message' => $th->getMessage()]);
				return 0;
			}
		}

		if (HTTPSignature::isValidContentType($mimetype, $uri)) {
			$fetched_uri = ActivityPub\Processor::fetchMissingActivity($uri, [], '', $completion, $uid);
			if (!empty($fetched_uri)) {
				$item_id = self::searchByLink($fetched_uri, $uid);
				if ($item_id) {
					DI::logger()->info('ActivityPub link fetched', ['uid' => $uid, 'uri' => $uri, 'id' => $item_id]);
					return $item_id;
				}
			}
		}

		$item_id = Diaspora::fetchByURL($uri);
		if ($item_id) {
			DI::logger()->info('Diaspora link fetched', ['uid' => $uid, 'uri' => $uri, 'id' => $item_id]);
			return $item_id;
		}

		DI::logger()->info('This is not an item link', ['uid' => $uid, 'uri' => $uri]);
		return 0;
	}

	/**
	 * Fetch the uri-id of a quoted post by searching for data in the body or attached media
	 *
	 * @param string $body   The body of the
	 * @param int    $uid    The id of the user
	 * @return integer
	 */
	public static function getQuoteUriId(string $body, int $uid = 0): int
	{
		$shared = BBCode::fetchShareAttributes($body);
		if (empty($shared['guid']) && empty($shared['message_id'])) {
			return 0;
		}

		if (empty($shared['link']) && empty($shared['message_id'])) {
			DI::logger()->notice('Invalid share block.', ['share' => $shared]);
			return 0;
		}

		if (!empty($shared['guid'])) {
			$shared_item = Post::selectFirst(['uri-id'], ['guid' => $shared['guid'], 'uid' => [0, $uid]]);
			if (!empty($shared_item['uri-id'])) {
				DI::logger()->debug('Found post by guid', ['guid' => $shared['guid'], 'uid' => $uid]);
				return $shared_item['uri-id'];
			}
		}

		if (!empty($shared['message_id'])) {
			$shared_item = Post::selectFirst(['uri-id'], ['uri' => $shared['message_id'], 'uid' => [0, $uid]]);
			if (!empty($shared_item['uri-id'])) {
				DI::logger()->debug('Found post by message_id', ['message_id' => $shared['message_id'], 'uid' => $uid]);
				return $shared_item['uri-id'];
			}
		}

		if (!empty($shared['link'])) {
			$shared_item = Post::selectFirst(['uri-id'], ['plink' => $shared['link'], 'uid' => [0, $uid]]);
			if (!empty($shared_item['uri-id'])) {
				DI::logger()->debug('Found post by link', ['link' => $shared['link'], 'uid' => $uid]);
				return $shared_item['uri-id'];
			}
		}

		$url = $shared['message_id'] ?: $shared['link'];
		$id  = self::fetchByLink($url, 0, ActivityPub\Receiver::COMPLETION_ASYNC);
		if (!$id) {
			DI::logger()->notice('Post could not be fetched.', ['url' => $url, 'uid' => $uid]);
			return 0;
		}

		$shared_item = Post::selectFirst(['uri-id'], ['id' => $id]);
		if (!empty($shared_item['uri-id'])) {
			DI::logger()->debug('Fetched shared post', ['id' => $id, 'url' => $url, 'uid' => $uid]);
			return $shared_item['uri-id'];
		}

		DI::logger()->warning('Post does not exist although it was supposed to had been fetched.', ['id' => $id, 'url' => $url, 'uid' => $uid]);
		return 0;
	}

	public static function incrementInbound(string $network)
	{
		$packets = (int)(DI::keyValue()->get('stats_packets_inbound_' . $network) ?? 0);
		if ($packets >= PHP_INT_MAX) {
			$packets = 0;
		}
		DI::keyValue()->set('stats_packets_inbound_' . $network, $packets + 1);
	}

	public static function incrementOutbound(string $network)
	{
		$packets = (int)(DI::keyValue()->get('stats_packets_outbound_' . $network) ?? 0);
		if ($packets >= PHP_INT_MAX) {
			$packets = 0;
		}
		DI::keyValue()->set('stats_packets_outbound_' . $network, $packets + 1);
	}
}
