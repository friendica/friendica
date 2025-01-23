<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Protocol\ActivityPub;

use Friendica\Content\Text\BBCode;
use Friendica\Content\Text\HTML;
use Friendica\Content\Text\Markdown;
use Friendica\Core\Cache\Enum\Duration;
use Friendica\Core\Protocol;
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\APContact;
use Friendica\Model\Contact;
use Friendica\Model\Conversation;
use Friendica\Model\Event;
use Friendica\Model\GServer;
use Friendica\Model\Item;
use Friendica\Model\ItemURI;
use Friendica\Model\Mail;
use Friendica\Model\Tag;
use Friendica\Model\User;
use Friendica\Model\Post;
use Friendica\Model\Post\Engagement;
use Friendica\Protocol\Activity;
use Friendica\Protocol\ActivityPub;
use Friendica\Protocol\Delivery;
use Friendica\Protocol\Relay;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\HTTPSignature;
use Friendica\Util\JsonLD;
use Friendica\Util\Network;
use Friendica\Util\Strings;

/**
 * ActivityPub Processor Protocol class
 */
class Processor
{
	const CACHEKEY_FETCH_ACTIVITY = 'processor:fetchMissingActivity:';
	const CACHEKEY_JUST_FETCHED   = 'processor:isJustFetched:';

	/**
	 * Add an object id to the list of processed ids
	 *
	 * @param string $id
	 *
	 * @return void
	 */
	private static function addActivityId(string $id)
	{
		DBA::delete('fetched-activity', ["`received` < ?", DateTimeFormat::utc('now - 5 minutes')]);
		DBA::insert('fetched-activity', ['object-id' => $id, 'received' => DateTimeFormat::utcNow()]);
	}

	/**
	 * Checks if the given object id has just been fetched
	 *
	 * @param string $id
	 *
	 * @return boolean
	 */
	private static function isFetched(string $id): bool
	{
		return DBA::exists('fetched-activity', ['object-id' => $id]);
	}

	/**
	 * Extracts the tag character (#, @, !) from mention links
	 *
	 * @param string $body
	 * @return string
	 */
	public static function normalizeMentionLinks(string $body): string
	{
		$body = preg_replace('%\[url=([^\[\]]*)]([#@!])(.*?)\[/url]%ism', '$2[url=$1]$3[/url]', $body);
		$body = preg_replace('%([#@!])\[zrl=([^\[\]]*)](.*?)\[/zrl]%ism', '$1[url=$2]$3[/url]', $body);
		return $body;
	}

	/**
	 * Convert the language array into a language JSON
	 *
	 * @param array $languages
	 * @return string language JSON
	 */
	private static function processLanguages(array $languages): string
	{
		$codes = array_keys($languages);
		$lang  = [];
		foreach ($codes as $code) {
			$lang[$code] = 1;
		}

		if (empty($lang)) {
			return '';
		}

		return json_encode($lang);
	}
	/**
	 * Replaces emojis in the body
	 *
	 * @param int $uri_id
	 * @param string $body
	 * @param array $emojis
	 *
	 * @return string with replaced emojis
	 */
	private static function replaceEmojis(int $uri_id, string $body, array $emojis): string
	{
		$body = strtr(
			$body,
			array_combine(
				array_column($emojis, 'name'),
				array_map(function ($emoji) {
					return '[emoji=' . $emoji['href'] . ']' . $emoji['name'] . '[/emoji]';
				}, $emojis)
			)
		);

		// We store the emoji here to be able to avoid storing it in the media
		foreach ($emojis as $emoji) {
			Post\Link::getByLink($uri_id, $emoji['href']);
		}
		return $body;
	}

	/**
	 * Store attached media files in the post-media table
	 *
	 * @param int $uriid
	 * @param array $attachment
	 * @return void
	 */
	private static function storeAttachmentAsMedia(int $uriid, array $attachment)
	{
		if (empty($attachment['url'])) {
			return;
		}

		$data                = ['uri-id' => $uriid];
		$data['type']        = Post\Media::UNKNOWN;
		$data['url']         = $attachment['url'];
		$data['mimetype']    = $attachment['mediaType'] ?? null;
		$data['height']      = $attachment['height']    ?? null;
		$data['width']       = $attachment['width']     ?? null;
		$data['size']        = $attachment['size']      ?? null;
		$data['preview']     = $attachment['image']     ?? null;
		$data['description'] = $attachment['name']      ?? null;

		Post\Media::insert($data);
	}

	/**
	 * Store attachment data
	 *
	 * @param array   $activity
	 * @param array   $item
	 */
	private static function storeAttachments(array $activity, array $item)
	{
		if (empty($activity['attachments'])) {
			return;
		}

		foreach ($activity['attachments'] as $attach) {
			self::storeAttachmentAsMedia($item['uri-id'], $attach);
		}
	}

	/**
	 * Store question data
	 *
	 * @param array   $activity
	 * @param array   $item
	 */
	private static function storeQuestion(array $activity, array $item)
	{
		if (empty($activity['question'])) {
			return;
		}
		$question = ['multiple' => $activity['question']['multiple']];

		if (!empty($activity['question']['voters'])) {
			$question['voters'] = $activity['question']['voters'];
		}

		if (!empty($activity['question']['end-time'])) {
			$question['end-time'] = DateTimeFormat::utc($activity['question']['end-time']);
		}

		Post\Question::update($item['uri-id'], $question);

		foreach ($activity['question']['options'] as $key => $option) {
			$option = ['name' => $option['name'], 'replies' => $option['replies']];
			Post\QuestionOption::update($item['uri-id'], $key, $option);
		}

		DI::logger()->debug('Storing incoming question', ['type' => $activity['type'], 'uri-id' => $item['uri-id'], 'question' => $activity['question']]);
	}

	/**
	 * Updates a message
	 *
	 * @param array      $activity   Activity array
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function updateItem(array $activity)
	{
		$item = Post::selectFirst(['uri', 'uri-id', 'thr-parent', 'gravity', 'post-type', 'private'], ['uri' => $activity['id']]);
		if (!DBA::isResult($item)) {
			DI::logger()->notice('No existing item, item will be created', ['uri' => $activity['id']]);
			$item = self::createItem($activity, false);
			if (empty($item)) {
				Queue::remove($activity);
				return;
			}

			self::postItem($activity, $item);
			return;
		}

		$item['changed'] = DateTimeFormat::utcNow();
		$item['edited']  = DateTimeFormat::utc($activity['updated']);

		Post\Media::deleteByURIId($item['uri-id'], [Post\Media::AUDIO, Post\Media::VIDEO, Post\Media::IMAGE, Post\Media::HTML]);
		$item = self::processContent($activity, $item);
		if (empty($item)) {
			Queue::remove($activity);
			return;
		}

		self::storeAttachments($activity, $item);
		self::storeQuestion($activity, $item);

		Post\History::add($item['uri-id'], $item);
		Item::update($item, ['uri' => $activity['id']]);

		Queue::remove($activity);

		if ($activity['object_type'] == 'as:Event') {
			$posts = Post::select(['event-id', 'uid'], ["`uri` = ? AND `event-id` > ?", $activity['id'], 0]);
			while ($post = DBA::fetch($posts)) {
				self::updateEvent($post['event-id'], $activity);
			}
		}
		self::processReplies($activity, $item);
	}

	/**
	 * Update an existing event
	 *
	 * @param int $event_id
	 * @param array $activity
	 */
	private static function updateEvent(int $event_id, array $activity)
	{
		$event = DBA::selectFirst('event', [], ['id' => $event_id]);

		$event['edited']  = DateTimeFormat::utc($activity['updated']);
		$event['summary'] = HTML::toBBCode($activity['name']);
		$event['desc']    = HTML::toBBCode($activity['content']);
		if (!empty($activity['start-time'])) {
			$event['start'] = DateTimeFormat::utc($activity['start-time']);
		}
		if (!empty($activity['end-time'])) {
			$event['finish'] = DateTimeFormat::utc($activity['end-time']);
		}
		$event['nofinish'] = empty($event['finish']);
		$event['location'] = $activity['location'];

		DI::logger()->info('Updating event', ['uri' => $activity['id'], 'id' => $event_id]);
		Event::store($event);
	}

	/**
	 * Prepares data for a message
	 *
	 * @param array $activity      Activity array
	 * @param bool  $fetch_parents
	 *
	 * @return array Internal item
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function createItem(array $activity, bool $fetch_parents): array
	{
		$item               = [];
		$item['verb']       = Activity::POST;
		$item['thr-parent'] = $activity['reply-to-id'];

		if ($activity['reply-to-id'] == $activity['id']) {
			$item['gravity']     = Item::GRAVITY_PARENT;
			$item['object-type'] = Activity\ObjectType::NOTE;
		} else {
			$item['gravity']     = Item::GRAVITY_COMMENT;
			$item['object-type'] = Activity\ObjectType::COMMENT;
		}

		if (!empty($activity['context'])) {
			$item['context'] = $activity['context'];
		}

		if (!empty($activity['conversation'])) {
			$item['conversation'] = $activity['conversation'];
		}

		if (!empty($item['context'])) {
			$conversation = Post::selectFirstThread(['uri'], ['context' => $item['context']]);
			if (!empty($conversation)) {
				DI::logger()->debug('Got context', ['context' => $item['context'], 'parent' => $conversation]);
				$item['parent-uri']    = $conversation['uri'];
				$item['parent-uri-id'] = ItemURI::getIdByURI($item['parent-uri']);
			}
		} elseif (!empty($item['conversation'])) {
			$conversation = Post::selectFirstThread(['uri'], ['conversation' => $item['conversation']]);
			if (!empty($conversation)) {
				DI::logger()->debug('Got conversation', ['conversation' => $item['conversation'], 'parent' => $conversation]);
				$item['parent-uri']    = $conversation['uri'];
				$item['parent-uri-id'] = ItemURI::getIdByURI($item['parent-uri']);
			}
		} else {
			$conversation = [];
		}

		DI::logger()->debug('Create Item', ['id' => $activity['id'], 'conversation' => $item['conversation'] ?? '']);
		if (empty($activity['author']) && empty($activity['actor'])) {
			DI::logger()->notice('Missing author and actor. We quit here.', ['activity' => $activity]);
			Queue::remove($activity);
			return [];
		}

		if (!in_array(0, $activity['receiver']) || !DI::config()->get('system', 'fetch_parents')) {
			$fetch_parents = false;
		}

		if ($fetch_parents && empty($activity['directmessage']) && ($activity['id'] != $activity['reply-to-id']) && !Post::exists(['uri' => $activity['reply-to-id']])) {
			$result = self::fetchParent($activity, !empty($conversation));
			if (!empty($result) && ($item['thr-parent'] != $result) && Post::exists(['uri' => $result])) {
				$item['thr-parent'] = $result;
			}
		}

		$item['diaspora_signed_text'] = $activity['diaspora:comment'] ?? '';

		if (empty($conversation) && empty($activity['directmessage']) && ($item['gravity'] != Item::GRAVITY_PARENT) && !Post::exists(['uri' => $item['thr-parent']])) {
			DI::logger()->notice('Parent not found, message will be discarded.', ['thr-parent' => $item['thr-parent']]);
			if (!$fetch_parents) {
				Queue::remove($activity);
			}
			return [];
		}

		$item['network']     = Protocol::ACTIVITYPUB;
		$item['author-link'] = $activity['author'];
		$item['author-id']   = Contact::getIdForURL($activity['author']);
		$item['owner-link']  = $activity['actor'];
		$item['owner-id']    = Contact::getIdForURL($activity['actor']);

		if (in_array(0, $activity['receiver']) && !empty($activity['unlisted'])) {
			$item['private'] = Item::UNLISTED;
		} elseif (in_array(0, $activity['receiver'])) {
			$item['private'] = Item::PUBLIC;
		} else {
			$item['private'] = Item::PRIVATE;
		}

		if (!empty($activity['raw'])) {
			$item['source'] = $activity['raw'];
		}

		$item['protocol'] = Conversation::PARCEL_ACTIVITYPUB;

		if (isset($activity['push'])) {
			$item['direction'] = $activity['push'] ? Conversation::PUSH : Conversation::PULL;
		}

		if (!empty($activity['from-relay'])) {
			$item['direction'] = Conversation::RELAY;
		}

		if ($activity['object_type'] == 'as:Article') {
			$item['post-type'] = Item::PT_ARTICLE;
		} elseif ($activity['object_type'] == 'as:Audio') {
			$item['post-type'] = Item::PT_AUDIO;
		} elseif ($activity['object_type'] == 'as:Document') {
			$item['post-type'] = Item::PT_DOCUMENT;
		} elseif ($activity['object_type'] == 'as:Event') {
			$item['post-type'] = Item::PT_EVENT;
		} elseif ($activity['object_type'] == 'as:Image') {
			$item['post-type'] = Item::PT_IMAGE;
		} elseif ($activity['object_type'] == 'as:Page') {
			$item['post-type'] = Item::PT_PAGE;
		} elseif ($activity['object_type'] == 'as:Question') {
			$item['post-type'] = Item::PT_POLL;
		} elseif ($activity['object_type'] == 'as:Video') {
			$item['post-type'] = Item::PT_VIDEO;
		} else {
			$item['post-type'] = Item::PT_NOTE;
		}

		$item['isGroup'] = false;

		if (!empty($activity['thread-completion'])) {
			if ($activity['thread-completion'] != $item['owner-id']) {
				$actor               = Contact::getById($activity['thread-completion'], ['url']);
				$item['causer-link'] = $actor['url'];
				$item['causer-id']   = $activity['thread-completion'];
				DI::logger()->info('Use inherited actor as causer.', ['id' => $item['owner-id'], 'activity' => $activity['thread-completion'], 'owner' => $item['owner-link'], 'actor' => $actor['url']]);
			} else {
				// Store the original actor in the "causer" fields to enable the check for ignored or blocked contacts
				$item['causer-link'] = $item['owner-link'];
				$item['causer-id']   = $item['owner-id'];
				DI::logger()->info('Use actor as causer.', ['id' => $item['owner-id'], 'actor' => $item['owner-link']]);
			}

			$item['owner-link'] = $item['author-link'];
			$item['owner-id']   = $item['author-id'];
		}

		if (!$item['isGroup'] && !empty($activity['receiver_urls']['as:audience'])) {
			foreach ($activity['receiver_urls']['as:audience'] as $audience) {
				$actor = APContact::getByURL($audience, false);
				if (($actor['type'] ?? 'Person') == 'Group') {
					DI::logger()->debug('Group post detected via audience.', ['audience' => $audience, 'actor' => $activity['actor'], 'author' => $activity['author']]);
					$item['isGroup']    = true;
					$item['group-link'] = $item['owner-link'] = $audience;
					$item['owner-id']   = Contact::getIdForURL($audience);
					break;
				}
			}
		} else {
			$owner = APContact::getByURL($item['owner-link'], false);
		}

		if (!$item['isGroup'] && (($owner['type'] ?? 'Person') == 'Group')) {
			DI::logger()->debug('Group post detected via owner.', ['actor' => $activity['actor'], 'author' => $activity['author']]);
			$item['isGroup']    = true;
			$item['group-link'] = $item['owner-link'];
		} elseif (!empty($item['causer-link'])) {
			$causer = APContact::getByURL($item['causer-link'], false);
		}

		if (!$item['isGroup'] && (($causer['type'] ?? 'Person') == 'Group')) {
			DI::logger()->debug('Group post detected via causer.', ['actor' => $activity['actor'], 'author' => $activity['author'], 'causer' => $item['causer-link']]);
			$item['isGroup']    = true;
			$item['group-link'] = $item['causer-link'];
		}

		if (!empty($item['group-link']) && empty($item['causer-link'])) {
			$item['causer-link'] = $item['group-link'];
			$item['causer-id']   = Contact::getIdForURL($item['causer-link']);
		}

		$item['uri']       = $activity['id'];
		$item['sensitive'] = $activity['sensitive'];

		if (empty($activity['published']) || empty($activity['updated'])) {
			DI::logger()->notice('published or updated keys are empty for activity', ['activity' => $activity]);
		}

		$item['created'] = DateTimeFormat::utc($activity['published'] ?? 'now');
		$item['edited']  = DateTimeFormat::utc($activity['updated'] ?? 'now');
		$guid            = $activity['sc:identifier'] ?: self::getGUIDByURL($item['uri']);
		$item['guid']    = $activity['diaspora:guid'] ?: $guid;

		$item['uri-id'] = ItemURI::insert(['uri' => $item['uri'], 'guid' => $item['guid']]);
		if (empty($item['uri-id'])) {
			DI::logger()->warning('Unable to get a uri-id for an item uri', ['uri' => $item['uri'], 'guid' => $item['guid']]);
			return [];
		}

		$item['thr-parent-id'] = ItemURI::getIdByURI($item['thr-parent']);

		$item = self::processContent($activity, $item);
		if (empty($item)) {
			DI::logger()->info('Message was not processed');
			Queue::remove($activity);
			return [];
		}

		$item['plink'] = $activity['alternate-url'] ?? $item['uri'];

		if (!empty($activity['replies'])) {
			$item['replies'] = $activity['replies'];
		}

		self::storeAttachments($activity, $item);
		self::storeQuestion($activity, $item);

		// We received the post via AP, so we set the protocol of the server to AP
		$contact = Contact::getById($item['author-id'], ['gsid']);
		if (!empty($contact['gsid'])) {
			GServer::setProtocol($contact['gsid'], Post\DeliveryData::ACTIVITYPUB);
		}

		if ($item['author-id'] != $item['owner-id']) {
			$contact = Contact::getById($item['owner-id'], ['gsid']);
			if (!empty($contact['gsid'])) {
				GServer::setProtocol($contact['gsid'], Post\DeliveryData::ACTIVITYPUB);
			}
		}

		return $item;
	}

	private static function processReplies(array $activity, array $item)
	{
		// @todo fetch replies not only in the decoupled mode
		if (!DI::config()->get('system', 'decoupled_receiver')) {
			return;
		}

		$replies = [$item['thr-parent']];
		if (!empty($item['parent-uri'])) {
			$replies[] = $item['parent-uri'];
		}
		$condition = DBA::mergeConditions(['uri' => $replies], ["`replies-id` IS NOT NULL"]);
		$posts     = Post::select(['replies', 'replies-id'], $condition);
		while ($post = Post::fetch($posts)) {
			$cachekey = 'Processor-CreateItem-Replies-' . $post['replies-id'];
			if (!DI::cache()->get($cachekey)) {
				self::fetchReplies($post['replies'], $activity);
				DI::cache()->set($cachekey, true);
			}
		}
		DBA::close($replies);

		if (!empty($item['replies'])) {
			self::fetchReplies($item['replies'], $activity);
		}
	}

	/**
	 * Fetch and process parent posts for the given activity
	 *
	 * @param array $activity
	 * @param bool  $in_background
	 *
	 * @return string
	 */
	private static function fetchParent(array $activity, bool $in_background = false): string
	{
		$activity['callstack'] = self::addToCallstack($activity['callstack'] ?? []);

		if (self::isFetched($activity['reply-to-id'])) {
			DI::logger()->info('Id is already fetched', ['id' => $activity['reply-to-id']]);
			return '';
		}

		if (in_array($activity['reply-to-id'], $activity['children'] ?? [])) {
			DI::logger()->notice('reply-to-id is already in the list of children', ['id' => $activity['reply-to-id'], 'children' => $activity['children'], 'depth' => count($activity['children'])]);
			return '';
		}

		self::addActivityId($activity['reply-to-id']);

		$completion = $activity['completion-mode'] ?? Receiver::COMPLETION_NONE;

		if (DI::config()->get('system', 'decoupled_receiver') && ($completion != Receiver::COMPLETION_MANUAL)) {
			$in_background = true;
		}

		$recursion_depth = $activity['recursion-depth'] ?? 0;

		if (!$in_background && ($recursion_depth < DI::config()->get('system', 'max_recursion_depth'))) {
			DI::logger()->info('Parent not found. Try to refetch it.', ['completion' => $completion, 'recursion-depth' => $recursion_depth, 'parent' => $activity['reply-to-id']]);
			$result = self::fetchMissingActivity($activity['reply-to-id'], $activity, '', Receiver::COMPLETION_AUTO);
			if (empty($result) && self::isActivityGone($activity['reply-to-id'])) {
				DI::logger()->notice('The activity is gone, the queue entry will be deleted', ['parent' => $activity['reply-to-id']]);
				if (!empty($activity['entry-id'])) {
					Queue::deleteById($activity['entry-id']);
				}
			} elseif (!empty($result)) {
				$post = Post::selectFirstPost(['uri'], ['uri' => [$result, $activity['reply-to-id']]]);
				if (!empty($post['uri'])) {
					DI::logger()->info('The activity has been fetched and created.', ['result' => $result, 'uri' => $post['uri']]);
					return $post['uri'];
				} else {
					DI::logger()->notice('The activity exists but has not been created, the queue entry will be deleted.', ['parent' => $result]);
					if (!empty($activity['entry-id'])) {
						Queue::deleteById($activity['entry-id']);
					}
				}
			}
			return '';
		} elseif (self::isActivityGone($activity['reply-to-id'])) {
			DI::logger()->notice('The activity is gone. We will not spawn a worker. The queue entry will be deleted', ['parent' => $activity['reply-to-id']]);
			if ($in_background) {
				// fetching in background is done for all activities where we have got the conversation
				// There we only delete the single activity and not the whole thread since we can store the
				// other posts in the thread even with missing posts.
				Queue::remove($activity);
			} elseif (!empty($activity['entry-id'])) {
				Queue::deleteById($activity['entry-id']);
			}
			return '';
		} elseif ($in_background) {
			DI::logger()->notice('Fetching is done in the background.', ['parent' => $activity['reply-to-id']]);
		} else {
			DI::logger()->notice('Recursion level is too high.', ['parent' => $activity['reply-to-id'], 'recursion-depth' => $recursion_depth]);
		}

		if (!Fetch::hasWorker($activity['reply-to-id'])) {
			DI::logger()->notice('Fetching is done by worker.', ['parent' => $activity['reply-to-id'], 'recursion-depth' => $recursion_depth]);
			Fetch::add($activity['reply-to-id']);
			$activity['recursion-depth'] = 0;
			$wid                         = Worker::add(Worker::PRIORITY_HIGH, 'FetchMissingActivity', $activity['reply-to-id'], $activity, '', Receiver::COMPLETION_ASYNC);
			Fetch::setWorkerId($activity['reply-to-id'], $wid);
		} else {
			DI::logger()->debug('Activity will already be fetched via a worker.', ['url' => $activity['reply-to-id']]);
		}

		return '';
	}

	/**
	 * Check if a given activity is no longer available
	 *
	 * @param string $url
	 *
	 * @return boolean
	 */
	public static function isActivityGone(string $url): bool
	{
		if (Network::isUrlBlocked($url)) {
			return true;
		}

		try {
			$curlResult = HTTPSignature::fetchRaw($url, 0);
		} catch (\Exception $exception) {
			DI::logger()->notice('Error fetching url', ['url' => $url, 'exception' => $exception]);
			return true;
		}

		// @todo To ensure that the remote system is working correctly, we can check if the "Content-Type" contains JSON
		if (in_array($curlResult->getReturnCode(), [401, 404])) {
			return true;
		}

		if ($curlResult->isSuccess()) {
			$object = json_decode($curlResult->getBodyString(), true);
			if (!empty($object)) {
				$activity = JsonLD::compact($object);
				if (JsonLD::fetchElement($activity, '@type') == 'as:Tombstone') {
					return true;
				}
			}
		} elseif ($curlResult->getReturnCode() == 0) {
			$host = parse_url($url, PHP_URL_HOST);
			if (!(filter_var($host, FILTER_VALIDATE_IP) || @dns_get_record($host . '.', DNS_A + DNS_AAAA))) {
				return true;
			}
		}

		return false;
	}
	/**
	 * Delete items
	 *
	 * @param array $activity
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function deleteItem(array $activity)
	{
		$owner = Contact::getIdForURL($activity['actor']);

		DI::logger()->info('Deleting item', ['object' => $activity['object_id'], 'owner' => $owner]);
		Item::markForDeletion(['uri' => $activity['object_id'], 'owner-id' => $owner]);
		Queue::remove($activity);
	}

	/**
	 * Prepare the item array for an activity
	 *
	 * @param array $activity Activity array
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function addTag(array $activity)
	{
		if (empty($activity['object_content']) || empty($activity['object_id'])) {
			return;
		}

		foreach ($activity['receiver'] as $receiver) {
			$item = Post::selectFirst(['id', 'uri-id', 'origin', 'author-link'], ['uri' => $activity['target_id'], 'uid' => $receiver]);
			if (!DBA::isResult($item)) {
				// We don't fetch missing content for this purpose
				continue;
			}

			if (($item['author-link'] != $activity['actor']) && !$item['origin']) {
				DI::logger()->info('Not origin, not from the author, skipping update', ['id' => $item['id'], 'author' => $item['author-link'], 'actor' => $activity['actor']]);
				continue;
			}

			Tag::store($item['uri-id'], Tag::HASHTAG, $activity['object_content'], $activity['object_id']);
			DI::logger()->info('Tagged item', ['id' => $item['id'], 'tag' => $activity['object_content'], 'uri' => $activity['target_id'], 'actor' => $activity['actor']]);
		}
	}

	/**
	 * Prepare the item array for an activity
	 *
	 * @param array      $activity   Activity array
	 * @param string     $verb       Activity verb
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function createActivity(array $activity, string $verb)
	{
		$activity['reply-to-id'] = $activity['object_id'];
		$item                    = self::createItem($activity, false);
		if (empty($item)) {
			DI::logger()->debug('Activity was not prepared', ['id' => $activity['object_id']]);
			return;
		}

		$item['verb']       = $verb;
		$item['thr-parent'] = $activity['object_id'];
		$item['gravity']    = Item::GRAVITY_ACTIVITY;
		unset($item['post-type']);
		$item['object-type'] = Activity\ObjectType::NOTE;

		if (!empty($activity['content'])) {
			$item['body'] = HTML::toBBCode($activity['content']);
		}

		$item['diaspora_signed_text'] = $activity['diaspora:like'] ?? '';

		self::postItem($activity, $item);
	}

	/**
	 * Fetch the Uri-Id of a post for the "featured" collection
	 *
	 * @param array $activity
	 * @return null|array
	 */
	private static function getUriIdForFeaturedCollection(array $activity)
	{
		$activity['callstack'] = self::addToCallstack($activity['callstack'] ?? []);

		$actor = APContact::getByURL($activity['actor']);
		if (empty($actor)) {
			return null;
		}

		// Refetch the account when the "featured" collection is missing.
		// This can be removed in a future version (end of 2022 should be good).
		if (empty($actor['featured'])) {
			$actor = APContact::getByURL($activity['actor'], true);
			if (empty($actor)) {
				return null;
			}
		}

		$parent = Post::selectFirst(['uri-id', 'author-id'], ['uri' => $activity['object_id']]);
		if (empty($parent['uri-id'])) {
			if (self::fetchMissingActivity($activity['object_id'], $activity, '', Receiver::COMPLETION_AUTO)) {
				$parent = Post::selectFirst(['uri-id'], ['uri' => $activity['object_id']]);
			}
		}

		if (!empty($parent['uri-id'])) {
			return $parent;
		}

		return null;
	}

	/**
	 * Add a post to the "Featured" collection
	 *
	 * @param array $activity
	 */
	public static function addToFeaturedCollection(array $activity)
	{
		$post = self::getUriIdForFeaturedCollection($activity);
		if (empty($post) || empty($post['author-id'])) {
			Queue::remove($activity);
			return;
		}

		DI::logger()->debug('Add post to featured collection', ['post' => $post]);

		Post\Collection::add($post['uri-id'], Post\Collection::FEATURED, $post['author-id']);
		Queue::remove($activity);
	}

	/**
	 * Remove a post to the "Featured" collection
	 *
	 * @param array $activity
	 */
	public static function removeFromFeaturedCollection(array $activity)
	{
		$post = self::getUriIdForFeaturedCollection($activity);
		if (empty($post)) {
			Queue::remove($activity);
			return;
		}

		DI::logger()->debug('Remove post from featured collection', ['post' => $post]);

		Post\Collection::remove($post['uri-id'], Post\Collection::FEATURED);
		Queue::remove($activity);
	}

	/**
	 * Create an event
	 *
	 * @param array $activity Activity array
	 * @param array $item
	 *
	 * @return int event id
	 * @throws \Exception
	 */
	public static function createEvent(array $activity, array $item): int
	{
		$event['summary'] = HTML::toBBCode($activity['name'] ?: $activity['summary']);
		$event['desc']    = HTML::toBBCode($activity['content'] ?? '');
		if (!empty($activity['start-time'])) {
			$event['start'] = DateTimeFormat::utc($activity['start-time']);
		}
		if (!empty($activity['end-time'])) {
			$event['finish'] = DateTimeFormat::utc($activity['end-time']);
		}
		$event['nofinish']  = empty($event['finish']);
		$event['location']  = $activity['location'];
		$event['cid']       = $item['contact-id'];
		$event['uid']       = $item['uid'];
		$event['uri']       = $item['uri'];
		$event['edited']    = $item['edited'];
		$event['private']   = $item['private'];
		$event['guid']      = $item['guid'];
		$event['plink']     = $item['plink'];
		$event['network']   = $item['network'];
		$event['protocol']  = $item['protocol'];
		$event['direction'] = $item['direction'];
		$event['source']    = $item['source'];

		$ev = DBA::selectFirst('event', ['id'], ['uri' => $item['uri'], 'uid' => $item['uid']]);
		if (DBA::isResult($ev)) {
			$event['id'] = $ev['id'];
		}

		$event_id = Event::store($event);

		DI::logger()->info('Event was stored', ['id' => $event_id]);

		return $event_id;
	}

	/**
	 * Process the content
	 *
	 * @param array $activity Activity array
	 * @param array $item
	 * @return array|bool Returns the item array or false if there was an unexpected occurrence
	 * @throws \Exception
	 */
	private static function processContent(array $activity, array $item)
	{
		if (!empty($activity['mediatype']) && ($activity['mediatype'] == 'text/markdown')) {
			$item['title'] = strip_tags($activity['name'] ?? '');
			$content       = Markdown::toBBCode($activity['content'] ?? '');
		} elseif (!empty($activity['mediatype']) && ($activity['mediatype'] == 'text/bbcode')) {
			$item['title'] = $activity['name']    ?? '';
			$content       = $activity['content'] ?? '';
		} else {
			// By default assume "text/html"
			$item['title'] = HTML::toBBCode($activity['name'] ?? '');
			$content       = HTML::toBBCode($activity['content'] ?? '');
		}

		$item['title']           = trim(BBCode::toPlaintext($item['title']));
		$item['content-warning'] = HTML::toBBCode($activity['summary'] ?? '');

		if (!empty($activity['languages'])) {
			$item['language'] = self::processLanguages($activity['languages']);
		}

		$item['transmitted-languages'] = $activity['transmitted-languages'];

		if (!empty($activity['emojis'])) {
			$content = self::replaceEmojis($item['uri-id'], $content, $activity['emojis']);
		}

		$content = self::addMentionLinks($content, $activity['tags']);

		if (!empty($activity['quote-url'])) {
			$id = Item::fetchByLink($activity['quote-url'], 0, ActivityPub\Receiver::COMPLETION_ASYNC);
			if ($id) {
				$shared_item          = Post::selectFirst(['uri-id'], ['id' => $id]);
				$item['quote-uri-id'] = $shared_item['uri-id'];
				DI::logger()->debug('Quote is found', ['guid' => $item['guid'], 'uri-id' => $item['uri-id'], 'quote' => $activity['quote-url'], 'quote-uri-id' => $item['quote-uri-id']]);
			} elseif ($uri_id = ItemURI::getIdByURI($activity['quote-url'], false)) {
				DI::logger()->info('Quote was not fetched but the uri-id existed', ['guid' => $item['guid'], 'uri-id' => $item['uri-id'], 'quote' => $activity['quote-url'], 'quote-uri-id' => $uri_id]);
				$item['quote-uri-id'] = $uri_id;
			} elseif (Queue::exists($activity['quote-url'], 'as:Create')) {
				$item['quote-uri-id'] = ItemURI::getIdByURI($activity['quote-url']);
				DI::logger()->info('Quote is queued but not processed yet', ['guid' => $item['guid'], 'uri-id' => $item['uri-id'], 'quote' => $activity['quote-url'], 'quote-uri-id' => $item['quote-uri-id']]);
			} else {
				DI::logger()->notice('Quote was not fetched', ['guid' => $item['guid'], 'uri-id' => $item['uri-id'], 'quote' => $activity['quote-url']]);
			}
		}

		if (!empty($activity['source'])) {
			$item['body']     = $activity['source'];
			$item['raw-body'] = $content;

			$quote_uri_id = Item::getQuoteUriId($item['body']);
			if (empty($item['quote-uri-id']) && !empty($quote_uri_id)) {
				$item['quote-uri-id'] = $quote_uri_id;
			}

			$item['body'] = BBCode::removeSharedData($item['body']);
		} else {
			$parent_uri = $item['parent-uri'] ?? $item['thr-parent'];
			if (empty($activity['directmessage']) && ($parent_uri != $item['uri']) && ($item['gravity'] == Item::GRAVITY_COMMENT)) {
				$parent = Post::selectFirst(['id', 'uri-id', 'private', 'author-link', 'alias'], ['uri' => $parent_uri]);
				if (!DBA::isResult($parent)) {
					DI::logger()->warning('Unknown parent item.', ['uri' => $parent_uri]);
					return false;
				}
				$content = self::removeImplicitMentionsFromBody($content, $parent);
			}
			$item['raw-body'] = $item['body'] = $content;
		}

		if (!empty($item['author-id']) && ($item['author-id'] == $item['owner-id'])) {
			foreach (Tag::getFromBody($item['body'], Tag::TAG_CHARACTER[Tag::EXCLUSIVE_MENTION]) as $tag) {
				$actor = APContact::getByURL($tag[2], false);
				if (($actor['type'] ?? 'Person') == 'Group') {
					DI::logger()->debug('Group post detected via exclusive mention.', ['mention' => $actor['url'], 'actor' => $activity['actor'], 'author' => $activity['author']]);
					$item['isGroup']    = true;
					$item['group-link'] = $item['owner-link'] = $actor['url'];
					$item['owner-id']   = Contact::getIdForURL($actor['url']);
					break;
				}
			}
		}

		self::storeFromBody($item);
		self::storeTags($item['uri-id'], $activity['tags']);

		self::storeReceivers($item['uri-id'], $activity['receiver_urls'] ?? []);

		if (!empty($activity['capabilities'])) {
			$restrictions = self::storeCapabilities($item['uri-id'], $activity['capabilities']);
		} elseif (!is_null($activity['can-comment']) && !$activity['can-comment']) {
			$restrictions = [Tag::CAN_REPLY];
		} else {
			$restrictions = [];
		}

		$item['restrictions'] = null;
		foreach ($restrictions as $restriction) {
			if ($restriction == Tag::CAN_REPLY) {
				$item['restrictions'] = $item['restrictions'] | Item::CANT_REPLY;
			} elseif ($restriction == Tag::CAN_LIKE) {
				$item['restrictions'] = $item['restrictions'] | Item::CANT_LIKE;
			} elseif ($restriction == Tag::CAN_ANNOUNCE) {
				$item['restrictions'] = $item['restrictions'] | Item::CANT_ANNOUNCE;
			}
		}

		if (!empty($item['author-id'])) {
			$author = Contact::selectFirstAccount(['ap-posting-restricted'], ['id' => $item['author-id']]);
			if (!empty($author['ap-posting-restricted'])) {
				$item['restrictions'] = $item['restrictions'] | Item::CANT_REPLY;
			}
		}

		$item['location'] = $activity['location'];

		if (!empty($activity['latitude']) && !empty($activity['longitude'])) {
			$item['coord'] = $activity['latitude'] . ' ' . $activity['longitude'];
		}

		$item['app'] = $activity['generator'];

		return $item;
	}

	/**
	 * Store hashtags and mentions
	 *
	 * @param array $item
	 */
	private static function storeFromBody(array $item)
	{
		// Make sure to delete all existing tags (can happen when called via the update functionality)
		DBA::delete('post-tag', ['uri-id' => $item['uri-id']]);

		Tag::storeFromBody($item['uri-id'], $item['body'], '@!');
	}

	/**
	 * Generate a GUID out of an URL of an ActivityPub post.
	 *
	 * @param string $url message URL
	 * @return string with GUID
	 */
	private static function getGUIDByURL(string $url): string
	{
		$parsed = parse_url($url);

		$host_hash = hash('crc32', $parsed['host']);

		unset($parsed["scheme"]);
		unset($parsed["host"]);

		$path = implode("/", $parsed);

		return $host_hash . '-' . hash('fnv164', $path) . '-' . hash('joaat', $path);
	}

	/**
	 * Checks if an incoming message is wanted
	 *
	 * @param array $activity
	 * @param array $item
	 * @return boolean Is the message wanted?
	 */
	private static function isSolicitedMessage(array $activity, array $item): bool
	{
		// The checks are split to improve the support when searching why a message was accepted.
		if (count($activity['receiver']) != 1) {
			// The message has more than one receiver, so it is wanted.
			DI::logger()->debug('Message has got several receivers - accepted', ['uri-id' => $item['uri-id'], 'guid' => $item['guid'], 'url' => $item['uri']]);
			return true;
		}

		if ($item['private'] == Item::PRIVATE) {
			// We only look at public posts here. Private posts are expected to be intentionally posted to the single receiver.
			DI::logger()->debug('Message is private - accepted', ['uri-id' => $item['uri-id'], 'guid' => $item['guid'], 'url' => $item['uri']]);
			return true;
		}

		if (!empty($activity['from-relay'])) {
			// We check relay posts at another place. When it arrived here, the message is already checked.
			DI::logger()->debug('Message is a relay post that is already checked - accepted', ['uri-id' => $item['uri-id'], 'guid' => $item['guid'], 'url' => $item['uri']]);
			return true;
		}

		if (in_array($activity['completion-mode'] ?? Receiver::COMPLETION_NONE, [Receiver::COMPLETION_MANUAL, Receiver::COMPLETION_ANNOUNCE])) {
			// Manual completions and completions caused by reshares are allowed without any further checks.
			DI::logger()->debug('Message is in completion mode - accepted', ['mode' => $activity['completion-mode'], 'uri-id' => $item['uri-id'], 'guid' => $item['guid'], 'url' => $item['uri']]);
			return true;
		}

		if ($item['gravity'] != Item::GRAVITY_PARENT) {
			// We cannot reliably check at this point if a comment or activity belongs to an accepted post or needs to be fetched
			// This can possibly be improved in the future.
			DI::logger()->debug('Message is no parent - accepted', ['uri-id' => $item['uri-id'], 'guid' => $item['guid'], 'url' => $item['uri']]);
			return true;
		}

		$tags = array_column(Tag::getByURIId($item['uri-id'], [Tag::HASHTAG]), 'name');
		if (Relay::isSolicitedPost($tags, $item['title'] . ' ' . ($item['content-warning'] ?? '') . ' ' . $item['body'], $item['author-id'], $item['uri'], Protocol::ACTIVITYPUB, $activity['thread-completion'] ?? 0)) {
			DI::logger()->debug('Post is accepted because of the relay settings', ['uri-id' => $item['uri-id'], 'guid' => $item['guid'], 'url' => $item['uri']]);
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Creates an item post
	 *
	 * @param array $activity Activity data
	 * @param array $item     item array
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function postItem(array $activity, array $item)
	{
		if (empty($item)) {
			return;
		}

		$stored  = false;
		$success = false;
		ksort($activity['receiver']);

		if (!self::isSolicitedMessage($activity, $item)) {
			DBA::delete('item-uri', ['id' => $item['uri-id']]);
			if (!empty($activity['entry-id'])) {
				Queue::deleteById($activity['entry-id']);
			}
			return;
		}

		foreach ($activity['receiver'] as $receiver) {
			if ($receiver == -1) {
				continue;
			}

			if (($receiver != 0) && empty($item['parent-uri-id']) && !empty($item['thr-parent-id'])) {
				$parent = Post::selectFirst(['parent-uri-id', 'parent-uri'], ['uri-id' => $item['thr-parent-id'], 'uid' => [0, $receiver]]);
				if (!empty($parent['parent-uri-id'])) {
					$item['parent-uri-id'] = $parent['parent-uri-id'];
					$item['parent-uri']    = $parent['parent-uri'];
				}
			}

			$item['uid'] = $receiver;

			$type = $activity['reception_type'][$receiver] ?? Receiver::TARGET_UNKNOWN;
			switch ($type) {
				case Receiver::TARGET_TO:
					$item['post-reason'] = Item::PR_TO;
					break;
				case Receiver::TARGET_CC:
					$item['post-reason'] = Item::PR_CC;
					break;
				case Receiver::TARGET_BTO:
					$item['post-reason'] = Item::PR_BTO;
					break;
				case Receiver::TARGET_BCC:
					$item['post-reason'] = Item::PR_BCC;
					break;
				case Receiver::TARGET_AUDIENCE:
					$item['post-reason'] = Item::PR_AUDIENCE;
					break;
				case Receiver::TARGET_FOLLOWER:
					$item['post-reason'] = Item::PR_FOLLOWER;
					break;
				case Receiver::TARGET_ANSWER:
					$item['post-reason'] = Item::PR_COMMENT;
					break;
				case Receiver::TARGET_GLOBAL:
					$item['post-reason'] = Item::PR_GLOBAL;
					break;
				default:
					$item['post-reason'] = Item::PR_NONE;
			}

			$item['post-reason'] = Item::getPostReason($item);

			if (in_array($item['post-reason'], [Item::PR_GLOBAL, Item::PR_NONE])) {
				if (!empty($activity['from-relay'])) {
					$item['post-reason'] = Item::PR_RELAY;
				} elseif (!empty($activity['thread-completion'])) {
					$item['post-reason'] = Item::PR_FETCHED;
				} elseif (!empty($activity['push'])) {
					$item['post-reason'] = Item::PR_PUSHED;
				}
			} elseif (($item['post-reason'] == Item::PR_FOLLOWER) && !empty($activity['from-relay'])) {
				// When a post arrives via a relay and we follow the author, we have to override the causer.
				// Otherwise the system assumes that we follow the relay. (See "addRowInformation")
				DI::logger()->debug('Relay post for follower', ['receiver' => $receiver, 'guid' => $item['guid'], 'relay' => $activity['from-relay']]);
				$item['causer-id'] = ($item['gravity'] == Item::GRAVITY_PARENT) ? $item['owner-id'] : $item['author-id'];
			}

			if ($item['isGroup']) {
				$item['contact-id'] = Contact::getIdForURL($item['group-link'], $receiver);
			} else {
				$item['contact-id'] = Contact::getIdForURL($item['author-link'], $receiver);
			}

			if (($receiver != 0) && empty($item['contact-id'])) {
				$item['contact-id'] = Contact::getIdForURL($activity['author']);
			}

			if (!empty($activity['directmessage']) && self::postMail($item)) {
				if (!empty($item['source']) && DI::config()->get('debug', 'store_source')) {
					Post\Activity::insert($item['uri-id'], $item['source']);
				}

				continue;
			}

			if (($receiver != 0) && ($item['gravity'] == Item::GRAVITY_PARENT) && !in_array($item['post-reason'], [Item::PR_FOLLOWER, Item::PR_TAG, Item::PR_TO, Item::PR_CC, Item::PR_AUDIENCE])) {
				if (!$item['isGroup']) {
					if ($item['post-reason'] == Item::PR_BCC) {
						DI::logger()->info('Top level post via BCC from a non sharer, ignoring', ['uid' => $receiver, 'contact' => $item['contact-id'], 'url' => $item['uri']]);
						continue;
					}

					if ((DI::pConfig()->get($receiver, 'system', 'accept_only_sharer') != Item::COMPLETION_LIKE)
						&& in_array($activity['thread-children-type'] ?? '', Receiver::ACTIVITY_TYPES)) {
						DI::logger()->info(
							'Top level post from thread completion from a non sharer had been initiated via an activity, ignoring',
							['type' => $activity['thread-children-type'], 'user' => $item['uid'], 'causer' => $item['causer-link'], 'author' => $activity['author'], 'url' => $item['uri']]
						);
						continue;
					}
				}

				$isGroup = false;
				$user    = User::getById($receiver, ['account-type']);
				if (!empty($user['account-type'])) {
					$isGroup = ($user['account-type'] == User::ACCOUNT_TYPE_COMMUNITY);
				}

				if ((DI::pConfig()->get($receiver, 'system', 'accept_only_sharer') == Item::COMPLETION_NONE)
					&& ((!$isGroup && !$item['isGroup'] && ($activity['type'] != 'as:Announce'))
					|| !Contact::isSharingByURL($activity['actor'], $receiver))) {
					DI::logger()->info('Actor is a non sharer, is no group or it is no announce', ['uid' => $receiver, 'actor' => $activity['actor'], 'url' => $item['uri'], 'type' => $activity['type']]);
					continue;
				}

				DI::logger()->info('Accepting post', ['uid' => $receiver, 'url' => $item['uri']]);
			}

			if (!self::hasParents($item, $receiver)) {
				continue;
			}

			if (($item['gravity'] != Item::GRAVITY_ACTIVITY) && ($activity['object_type'] == 'as:Event')) {
				$event_id = self::createEvent($activity, $item);

				$item = Event::getItemArrayForImportedId($event_id, $item);
			}

			$item_id = Item::insert($item);
			if ($item_id) {
				DI::logger()->info('Item insertion successful', ['user' => $item['uid'], 'item_id' => $item_id]);
				$success = true;
			} else {
				DI::logger()->notice('Item insertion aborted', ['uri' => $item['uri'], 'uid' => $item['uid']]);
				if (($item['uid'] == 0) && (count($activity['receiver']) > 1)) {
					DI::logger()->info('Public item was aborted. We skip for all users.', ['uri' => $item['uri']]);
					break;
				}
			}

			if ($item['uid'] == 0) {
				$stored = $item_id;
			}
		}

		Queue::remove($activity);

		if ($success && Queue::hasChildren($item['uri']) && Post::exists(['uri' => $item['uri']])) {
			Queue::processReplyByUri($item['uri'], $activity);
		}

		// Store send a follow request for every reshare - but only when the item had been stored
		if ($stored && ($item['private'] != Item::PRIVATE) && ($item['gravity'] == Item::GRAVITY_PARENT) && !empty($item['author-link']) && ($item['author-link'] != $item['owner-link'])) {
			$author = APContact::getByURL($item['owner-link'], false);
			// We send automatic follow requests for reshared messages. (We don't need though for group posts)
			if ($author['type'] != 'Group') {
				DI::logger()->info('Send follow request', ['uri' => $item['uri'], 'stored' => $stored, 'to' => $item['author-link']]);
				ActivityPub\Transmitter::sendFollowObject($item['uri'], $item['author-link']);
			}
		}

		if ($success) {
			self::processReplies($activity, $item);
		}
	}

	/**
	 * Checks if there are parent posts for the given receiver.
	 * If not, then the system will try to add them.
	 *
	 * @param array $item
	 * @param integer $receiver
	 * @return boolean
	 */
	private static function hasParents(array $item, int $receiver)
	{
		if (($receiver == 0) || ($item['gravity'] == Item::GRAVITY_PARENT)) {
			return true;
		}

		$fields = ['causer-id' => $item['causer-id'] ?? $item['author-id'], 'post-reason' => Item::PR_FETCHED];

		$add_parent = true;

		if ($item['verb'] != Activity::ANNOUNCE) {
			switch (DI::pConfig()->get($receiver, 'system', 'accept_only_sharer')) {
				case Item::COMPLETION_COMMENT:
					$add_parent = ($item['gravity'] != Item::GRAVITY_ACTIVITY);
					break;

				case Item::COMPLETION_NONE:
					$add_parent = false;
					break;
			}
		}

		if ($add_parent) {
			$add_parent = Contact::isSharing($fields['causer-id'], $receiver);
			if (!$add_parent && ($item['author-id'] != $fields['causer-id'])) {
				$add_parent = Contact::isSharing($item['author-id'], $receiver);
			}
			if (!$add_parent && !in_array($item['owner-id'], [$fields['causer-id'], $item['author-id']])) {
				$add_parent = Contact::isSharing($item['owner-id'], $receiver);
			}
		}

		$has_parents = false;

		if (($item['private'] != Item::PRIVATE) && !empty($item['parent-uri-id'])) {
			if (Post::exists(['uri-id' => $item['parent-uri-id'], 'uid' => $receiver])) {
				$has_parents = true;
			} elseif ($add_parent && Post::exists(['uri-id' => $item['parent-uri-id'], 'uid' => 0])) {
				$stored      = Item::storeForUserByUriId($item['parent-uri-id'], $receiver, $fields);
				$has_parents = (bool)$stored;
				if ($stored) {
					DI::logger()->notice('Inserted missing parent post', ['stored' => $stored, 'uid' => $receiver, 'parent' => $item['parent-uri']]);
				} else {
					DI::logger()->notice('Parent could not be added.', ['uid' => $receiver, 'uri' => $item['uri'], 'parent' => $item['parent-uri']]);
					return false;
				}
			} elseif ($add_parent) {
				DI::logger()->debug('Parent does not exist.', ['uid' => $receiver, 'uri' => $item['uri'], 'parent' => $item['parent-uri']]);
			} else {
				DI::logger()->debug('Parent should not be added.', ['uid' => $receiver, 'gravity' => $item['gravity'], 'verb' => $item['verb'], 'guid' => $item['guid'], 'uri' => $item['uri'], 'parent' => $item['parent-uri']]);
			}
		}

		if (($item['private'] == Item::PRIVATE) || empty($item['parent-uri-id']) || ($item['thr-parent-id'] != $item['parent-uri-id'])) {
			if (Post::exists(['uri-id' => $item['thr-parent-id'], 'uid' => $receiver])) {
				$has_parents = true;
			} elseif (($has_parents || $add_parent) && Post::exists(['uri-id' => $item['thr-parent-id'], 'uid' => 0])) {
				$stored      = Item::storeForUserByUriId($item['thr-parent-id'], $receiver, $fields);
				$has_parents = $has_parents || (bool)$stored;
				if ($stored) {
					DI::logger()->notice('Inserted missing thread parent post', ['stored' => $stored, 'uid' => $receiver, 'thread-parent' => $item['thr-parent']]);
				} else {
					DI::logger()->notice('Thread parent could not be added.', ['uid' => $receiver, 'uri' => $item['uri'], 'thread-parent' => $item['thr-parent']]);
				}
			} elseif ($add_parent) {
				DI::logger()->debug('Thread parent does not exist.', ['uid' => $receiver, 'uri' => $item['uri'], 'thread-parent' => $item['thr-parent']]);
			} else {
				DI::logger()->debug('Thread parent should not be added.', ['uid' => $receiver, 'gravity' => $item['gravity'], 'verb' => $item['verb'], 'guid' => $item['guid'], 'uri' => $item['uri'], 'thread-parent' => $item['thr-parent']]);
			}
		}

		return $has_parents;
	}

	/**
	 * Store tags and mentions into the tag table
	 *
	 * @param integer $uriid
	 * @param array $tags
	 */
	private static function storeTags(int $uriid, array $tags = null)
	{
		foreach ($tags as $tag) {
			if (empty($tag['name']) || empty($tag['type']) || !in_array($tag['type'], ['Mention', 'Hashtag'])) {
				continue;
			}

			$hash = substr($tag['name'], 0, 1);
			$type = 0;

			if ($tag['type'] == 'Mention') {
				if (in_array($hash, [Tag::TAG_CHARACTER[Tag::MENTION],
					Tag::TAG_CHARACTER[Tag::EXCLUSIVE_MENTION],
					Tag::TAG_CHARACTER[Tag::IMPLICIT_MENTION]])) {
					$tag['name'] = substr($tag['name'], 1);
				}
				$type = Tag::IMPLICIT_MENTION;

				if (!empty($tag['href'])) {
					$apcontact = APContact::getByURL($tag['href']);
					if (!empty($apcontact['name']) || !empty($apcontact['nick'])) {
						$tag['name'] = $apcontact['name'] ?: $apcontact['nick'];
					}
				}
			} elseif ($tag['type'] == 'Hashtag') {
				if ($hash == Tag::TAG_CHARACTER[Tag::HASHTAG]) {
					$tag['name'] = substr($tag['name'], 1);
				}
				$type = Tag::HASHTAG;
			}

			if (empty($tag['name'])) {
				continue;
			}

			Tag::store($uriid, $type, $tag['name'], $tag['href']);
		}
	}

	public static function storeReceivers(int $uriid, array $receivers)
	{
		foreach (['as:to' => Tag::TO, 'as:cc' => Tag::CC, 'as:bto' => Tag::BTO, 'as:bcc' => Tag::BCC, 'as:audience' => Tag::AUDIENCE, 'as:attributedTo' => Tag::ATTRIBUTED] as $element => $type) {
			foreach ($receivers[$element] ?? [] as $receiver) {
				if ($receiver == ActivityPub::PUBLIC_COLLECTION) {
					$name = Receiver::PUBLIC_COLLECTION;
				} elseif ($path = parse_url($receiver, PHP_URL_PATH)) {
					$name = trim($path, '/');
				} elseif ($host = parse_url($receiver, PHP_URL_HOST)) {
					$name = $host;
				} else {
					DI::logger()->warning('Unable to coerce name from receiver', ['element' => $element, 'type' => $type, 'receiver' => $receiver]);
					$name = '';
				}

				$target = Tag::getTargetType($receiver);
				DI::logger()->debug('Got target type', ['type' => $target, 'url' => $receiver]);
				Tag::store($uriid, $type, $name, $receiver, $target);
			}
		}
	}

	private static function storeCapabilities(int $uriid, array $capabilities): array
	{
		$restrictions = [];
		foreach (['pixelfed:canAnnounce' => Tag::CAN_ANNOUNCE, 'pixelfed:canLike' => Tag::CAN_LIKE, 'pixelfed:canReply' => Tag::CAN_REPLY] as $element => $type) {
			$restricted = true;
			foreach ($capabilities[$element] ?? [] as $capability) {
				if ($capability == ActivityPub::PUBLIC_COLLECTION) {
					$name = Receiver::PUBLIC_COLLECTION;
				} elseif (empty($capability) || ($capability == '[]')) {
					continue;
				} elseif ($path = parse_url($capability, PHP_URL_PATH)) {
					$name = trim($path, '/');
				} elseif ($host = parse_url($capability, PHP_URL_HOST)) {
					$name = $host;
				} else {
					DI::logger()->warning('Unable to coerce name from capability', ['element' => $element, 'type' => $type, 'capability' => $capability]);
					$name = '';
				}
				$restricted = false;
				Tag::store($uriid, $type, $name, $capability);
			}
			if ($restricted) {
				$restrictions[] = $type;
			}
		}
		return $restrictions;
	}

	/**
	 * Creates an mail post
	 *
	 * @param array $item item array
	 * @return int|bool New mail table row id or false on error
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function postMail(array $item)
	{
		if (($item['gravity'] != Item::GRAVITY_PARENT) && !DBA::exists('mail', ['uri' => $item['thr-parent'], 'uid' => $item['uid']])) {
			DI::logger()->info('Parent not found, mail will be discarded.', ['uid' => $item['uid'], 'uri' => $item['thr-parent']]);
			return false;
		}

		if (!Contact::isFollower($item['contact-id'], $item['uid']) && !Contact::isSharing($item['contact-id'], $item['uid'])) {
			DI::logger()->info('Contact is not a sharer or follower, mail will be discarded.', ['item' => $item]);
			return false;
		}

		DI::logger()->info('Direct Message', $item);

		$msg        = [];
		$msg['uid'] = $item['uid'];

		$msg['contact-id'] = $item['contact-id'];

		$contact           = Contact::getById($item['contact-id'], ['name', 'url', 'photo']);
		$msg['from-name']  = $contact['name'];
		$msg['from-url']   = $contact['url'];
		$msg['from-photo'] = $contact['photo'];

		$msg['uri']     = $item['uri'];
		$msg['created'] = $item['created'];

		$parent = DBA::selectFirst('mail', ['parent-uri', 'title'], ['uri' => $item['thr-parent']]);
		if (DBA::isResult($parent)) {
			$msg['parent-uri'] = $parent['parent-uri'];
			$msg['title']      = $parent['title'];
		} else {
			$msg['parent-uri'] = $item['thr-parent'];

			if (!empty($item['title'])) {
				$msg['title'] = $item['title'];
			} elseif (!empty($item['content-warning'])) {
				$msg['title'] = $item['content-warning'];
			} else {
				// Trying to generate a title out of the body
				$title = $item['body'];

				while (preg_match('#^(@\[url=([^\]]+)].*?\[\/url]\s)(.*)#is', $title, $matches)) {
					$title = $matches[3];
				}

				$title = trim(BBCode::toPlaintext($title));

				if (strlen($title) > 20) {
					$title = substr($title, 0, 20) . '...';
				}

				$msg['title'] = $title;
			}
		}
		$msg['body'] = $item['body'];

		return Mail::insert($msg);
	}

	/**
	 * Fetch featured posts from a contact with the given url
	 *
	 * @param string $url
	 * @return void
	 */
	public static function fetchFeaturedPosts(string $url)
	{
		DI::logger()->info('Fetch featured posts', ['contact' => $url]);

		$apcontact = APContact::getByURL($url);
		if (empty($apcontact['featured'])) {
			DI::logger()->info('Contact does not have a featured collection', ['contact' => $url]);
			return;
		}

		$pcid = Contact::getIdForURL($url, 0, false);
		if (empty($pcid)) {
			DI::logger()->notice('Contact not found', ['contact' => $url]);
			return;
		}

		$posts = Post\Collection::selectToArrayForContact($pcid, Post\Collection::FEATURED);
		if (!empty($posts)) {
			$old_featured = array_column($posts, 'uri-id');
		} else {
			$old_featured = [];
		}

		$featured = ActivityPub::fetchItems($apcontact['featured']);
		if (empty($featured)) {
			DI::logger()->info('Contact does not have featured posts', ['contact' => $url]);

			foreach ($old_featured as $uri_id) {
				Post\Collection::remove($uri_id, Post\Collection::FEATURED);
				DI::logger()->debug('Removed no longer featured post', ['uri-id' => $uri_id, 'contact' => $url]);
			}
			return;
		}

		$new = 0;
		$old = 0;

		foreach ($featured as $post) {
			if (empty($post['id'])) {
				continue;
			}
			$id = Item::fetchByLink($post['id'], 0, ActivityPub\Receiver::COMPLETION_ASYNC);
			if (!empty($id)) {
				$item = Post::selectFirst(['uri-id', 'featured', 'author-id'], ['id' => $id]);
				if (!empty($item['uri-id'])) {
					if (!$item['featured']) {
						Post\Collection::add($item['uri-id'], Post\Collection::FEATURED, $item['author-id']);
						DI::logger()->debug('Added featured post', ['uri-id' => $item['uri-id'], 'contact' => $url]);
						$new++;
					} else {
						DI::logger()->debug('Post already had been featured', ['uri-id' => $item['uri-id'], 'contact' => $url]);
						$old++;
					}

					$index = array_search($item['uri-id'], $old_featured);
					if (!($index === false)) {
						unset($old_featured[$index]);
					}
				}
			}
		}

		foreach ($old_featured as $uri_id) {
			Post\Collection::remove($uri_id, Post\Collection::FEATURED);
			DI::logger()->debug('Removed no longer featured post', ['uri-id' => $uri_id, 'contact' => $url]);
		}

		DI::logger()->info('Fetched featured posts', ['new' => $new, 'old' => $old, 'contact' => $url]);
	}

	public static function fetchCachedActivity(string $url, int $uid): array
	{
		$cachekey = self::CACHEKEY_FETCH_ACTIVITY . $uid . ':' . hash('sha256', $url);
		$object   = DI::cache()->get($cachekey);

		if (!is_null($object)) {
			if (!empty($object)) {
				DI::logger()->debug('Fetch from cache', ['url' => $url, 'uid' => $uid]);
			} else {
				DI::logger()->debug('Fetch from negative cache', ['url' => $url, 'uid' => $uid]);
			}
			return $object;
		}

		$object = HTTPSignature::fetch($url, $uid);

		if (!empty($object)) {
			$object = self::refetchObjectOnHostDifference($object, $url);
		}

		if (empty($object)) {
			DI::logger()->notice('Activity was not fetchable, aborting.', ['url' => $url, 'uid' => $uid]);
			// We perform negative caching.
			DI::cache()->set($cachekey, [], Duration::FIVE_MINUTES);
			return [];
		}

		if (empty($object['id'])) {
			DI::logger()->notice('Activity has got not id, aborting. ', ['url' => $url, 'object' => $object]);
			return [];
		}

		if (!self::isValidObject($object)) {
			return [];
		}

		DI::cache()->set($cachekey, $object, Duration::FIVE_MINUTES);

		DI::logger()->debug('Activity was fetched successfully', ['url' => $url, 'uid' => $uid]);

		return $object;
	}

	/**
	 * Fetches missing posts
	 *
	 * @param string     $url         message URL
	 * @param array      $child       activity array with the child of this message
	 * @param string     $relay_actor Relay actor
	 * @param int        $completion  Completion mode, see Receiver::COMPLETION_*
	 * @param int        $uid         User id that is used to fetch the activity
	 * @return string fetched message URL. An empty string indicates a temporary error, null indicates a permament error,
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function fetchMissingActivity(string $url, array $child = [], string $relay_actor = '', int $completion = Receiver::COMPLETION_MANUAL, int $uid = 0): ?string
	{
		if (Network::isUrlBlocked($url)) {
			return null;
		}

		if (!empty($child['children']) && in_array($url, $child['children'])) {
			DI::logger()->notice('id is already in the list of children', ['depth' => count($child['children']), 'children' => $child['children'], 'id' => $url]);
			return null;
		}

		try {
			$curlResult = HTTPSignature::fetchRaw($url, $uid);
		} catch (\Exception $exception) {
			DI::logger()->notice('Error fetching url', ['url' => $url, 'exception' => $exception]);
			return '';
		}

		$body = $curlResult->getBodyString();
		if (!$curlResult->isSuccess() || empty($body)) {
			if (in_array($curlResult->getReturnCode(), [403, 404, 406, 410])) {
				return null;
			}
			return '';
		}

		$object = json_decode($body, true);

		if (!empty($object)) {
			$object = self::refetchObjectOnHostDifference($object, $url);
		}

		if (empty($object) || !is_array($object)) {
			DI::logger()->notice('Invalid JSON data', ['url' => $url, 'content-type' => $curlResult->getContentType()]);
			return null;
		}

		if (!self::isValidObject($object)) {
			return null;
		}

		if (!HTTPSignature::isValidContentType($curlResult->getContentType(), $url)) {
			return null;
		}

		return self::processActivity($object, $url, $child, $relay_actor, $completion, $uid);
	}

	private static function processActivity(array $object, string $url, array $child, string $relay_actor, int $completion, int $uid = 0): ?string
	{
		$ldobject = JsonLD::compact($object);

		$signer = [];

		$attributed_to = JsonLD::fetchElement($ldobject, 'as:attributedTo', '@id');
		if (!empty($attributed_to)) {
			$signer[] = $attributed_to;
		}

		$object_actor = JsonLD::fetchElement($ldobject, 'as:actor', '@id');
		if (!empty($attributed_to)) {
			$object_actor = $attributed_to;
		} else {
			// Shouldn't happen
			$object_actor = '';
		}

		$signer[] = $object_actor;

		if (!empty($child['author'])) {
			$actor    = $child['author'];
			$signer[] = $actor;
		} else {
			$actor = $object_actor;
		}

		$type      = JsonLD::fetchElement($ldobject, '@type');
		$object_id = JsonLD::fetchElement($ldobject, 'as:object', '@id');

		if (!in_array($type, Receiver::CONTENT_TYPES) && !empty($object_id)) {
			if (($type == 'as:Announce') && !empty($relay_actor) && ($completion = Receiver::COMPLETION_RELAY)) {
				if (Item::searchByLink($object_id)) {
					return $object_id;
				}
				DI::logger()->debug('Fetch announced activity', ['type' => $type, 'id' => $object_id, 'actor' => $relay_actor, 'signer' => $signer]);

				if (!self::alreadyKnown($object_id, $child['id'] ?? '')) {
					$child['callstack'] = self::addToCallstack($child['callstack'] ?? []);
					return self::fetchMissingActivity($object_id, $child, $relay_actor, $completion, $uid);
				}
			}
			$activity   = $object;
			$ldactivity = $ldobject;
		} elseif (!empty($object['id'])) {
			$activity   = self::getActivityForObject($object, $actor);
			$ldactivity = JsonLD::compact($activity);
		} else {
			return null;
		}

		$ldactivity['recursion-depth'] = !empty($child['recursion-depth']) ? $child['recursion-depth'] + 1 : 0;
		$ldactivity['children']        = $child['children']  ?? [];
		$ldactivity['callstack']       = $child['callstack'] ?? [];
		// This check is mostly superfluous, since there are similar checks before. This covers the case, when the fetched id doesn't match the url
		if (in_array($activity['id'], $ldactivity['children'])) {
			DI::logger()->notice('Fetched id is already in the list of children. It will not be processed.', ['id' => $activity['id'], 'children' => $ldactivity['children'], 'depth' => count($ldactivity['children'])]);
			return null;
		}
		if (!empty($child['id'])) {
			$ldactivity['children'][] = $child['id'];
		}


		if ($object_actor != $actor) {
			Contact::updateByUrlIfNeeded($object_actor);
		}

		Contact::updateByUrlIfNeeded($actor);

		if (!empty($child['thread-completion'])) {
			$ldactivity['thread-completion'] = $child['thread-completion'];
			$ldactivity['completion-mode']   = $child['completion-mode'] ?? Receiver::COMPLETION_NONE;
		} else {
			$ldactivity['thread-completion'] = Contact::getIdForURL($relay_actor ?: $actor);
			$ldactivity['completion-mode']   = $completion;
		}

		if ($completion == Receiver::COMPLETION_RELAY) {
			$ldactivity['from-relay'] = $ldactivity['thread-completion'];
			if (in_array($type, Receiver::CONTENT_TYPES) && !self::acceptIncomingMessage($ldactivity)) {
				return null;
			}
		}

		if (!empty($child['thread-children-type'])) {
			$ldactivity['thread-children-type'] = $child['thread-children-type'];
		} elseif (!empty($child['type'])) {
			$ldactivity['thread-children-type'] = $child['type'];
		} else {
			$ldactivity['thread-children-type'] = 'as:Create';
		}

		if (($completion == Receiver::COMPLETION_RELAY) && Queue::exists($url, 'as:Create')) {
			DI::logger()->info('Activity has already been queued.', ['url' => $url, 'object' => $activity['id']]);
		} elseif (ActivityPub\Receiver::processActivity($ldactivity, json_encode($activity), $uid, true, false, $signer, '', $completion)) {
			DI::logger()->info('Activity had been fetched and processed.', ['url' => $url, 'entry' => $child['entry-id'] ?? 0, 'completion' => $completion, 'object' => $activity['id']]);
		} else {
			DI::logger()->info('Activity had been fetched and will be processed later.', ['url' => $url, 'entry' => $child['entry-id'] ?? 0, 'completion' => $completion, 'object' => $activity['id']]);
		}

		return $activity['id'];
	}

	private static function fetchReplies(string $url, array $child)
	{
		$callstack_count = 0;
		foreach ($child['callstack'] ?? [] as $function) {
			if ($function == __FUNCTION__) {
				++$callstack_count;
			}
		}

		$callstack    = array_slice(array_column(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), 'function'), 1);
		$system_count = 0;
		foreach ($callstack as $function) {
			if ($function == __FUNCTION__) {
				++$system_count;
			}
		}

		$maximum_fetchreplies_depth = DI::config()->get('system', 'max_fetchreplies_depth');
		if (max($callstack_count, $system_count) == $maximum_fetchreplies_depth) {
			DI::logger()->notice('Maximum callstack depth reached', ['max' => $maximum_fetchreplies_depth, 'count' => $callstack_count, 'system-count' => $system_count, 'replies' => $url, 'callstack' => $child['callstack'] ?? [], 'system' => $callstack]);
			return;
		}

		$child['callstack'] = self::addToCallstack($child['callstack'] ?? []);

		$replies = ActivityPub::fetchItems($url);
		if (empty($replies)) {
			DI::logger()->notice('No replies', ['replies' => $url]);
			return;
		}
		DI::logger()->notice('Fetch replies - start', ['replies' => $url, 'callstack' => $child['callstack'], 'system' => $callstack]);
		$fetched = 0;
		foreach ($replies as $reply) {
			$id = '';

			if (is_array($reply)) {
				$ldobject = JsonLD::compact($reply);
				$id       = JsonLD::fetchElement($ldobject, '@id');
				if (Processor::alreadyKnown($id, $child['id'] ?? '')) {
					continue;
				}
				if (!empty($child['children']) && in_array($id, $child['children'])) {
					DI::logger()->debug('Replies id is already in the list of children', ['depth' => count($child['children']), 'children' => $child['children'], 'id' => $id]);
					continue;
				}
				if (parse_url($id, PHP_URL_HOST) == parse_url($url, PHP_URL_HOST)) {
					DI::logger()->debug('Incluced activity will be processed', ['replies' => $url, 'id' => $id]);
					self::processActivity($reply, $id, $child, '', Receiver::COMPLETION_REPLIES);
					++$fetched;
					continue;
				}
			} elseif (is_string($reply)) {
				$id = $reply;
			}
			if (!self::alreadyKnown($id, $child['id'] ?? '')) {
				self::fetchMissingActivity($id, $child, '', Receiver::COMPLETION_REPLIES);
				++$fetched;
			}
		}
		DI::logger()->notice('Fetch replies - done', ['fetched' => $fetched, 'total' => count($replies), 'replies' => $url]);
	}

	public static function alreadyKnown(string $id, string $child): bool
	{
		if ($id == $child) {
			DI::logger()->debug('Activity is currently processed', ['id' => $id, 'child' => $child]);
			return true;
		} elseif (Item::searchByLink($id)) {
			DI::logger()->debug('Activity already exists', ['id' => $id, 'child' => $child]);
			return true;
		} elseif (Queue::exists($id, 'as:Create')) {
			DI::logger()->debug('Activity is already queued', ['id' => $id, 'child' => $child]);
			return true;
		}
		DI::logger()->debug('Activity is unknown', ['id' => $id, 'child' => $child]);
		return false;
	}

	private static function refetchObjectOnHostDifference(array $object, string $url): array
	{
		$ldobject = JsonLD::compact($object);
		if (empty($ldobject)) {
			DI::logger()->info('Invalid object', ['url' => $url]);
			return $object;
		}

		$id = JsonLD::fetchElement($ldobject, '@id');
		if (empty($id)) {
			DI::logger()->info('No id found in object', ['url' => $url, 'object' => $object]);
			return $object;
		}

		$url_host = parse_url($url, PHP_URL_HOST);
		$id_host  = parse_url($id, PHP_URL_HOST);

		if ($id_host == $url_host) {
			return $object;
		}

		DI::logger()->notice('Refetch activity because of a host mismatch between requested and received id', ['url-host' => $url_host, 'id-host' => $id_host, 'url' => $url, 'id' => $id]);
		return HTTPSignature::fetch($id);
	}

	private static function isValidObject(array $object): bool
	{
		$ldobject = JsonLD::compact($object);
		if (empty($ldobject)) {
			DI::logger()->info('Invalid object');
			return false;
		}

		$id = JsonLD::fetchElement($ldobject, '@id');
		if (empty($id)) {
			DI::logger()->info('No id found in object');
			return false;
		}

		$type          = JsonLD::fetchElement($ldobject, '@type');
		$object_id     = JsonLD::fetchElement($ldobject, 'as:object', '@id');
		$object_type   = JsonLD::fetchElement($ldobject, 'as:object', '@type');
		$actor         = JsonLD::fetchElement($ldobject, 'as:actor', '@id');
		$attributed_to = JsonLD::fetchElement($ldobject, 'as:attributedTo', '@id');

		$id_host = parse_url($id, PHP_URL_HOST);

		if (!empty($actor) && !in_array($type, Receiver::CONTENT_TYPES) && !empty($object_id)) {
			$actor_host = parse_url($actor, PHP_URL_HOST);
			if ($actor_host != $id_host) {
				DI::logger()->notice('Host mismatch between received id and actor', ['id-host' => $id_host, 'actor-host' => $actor_host, 'id' => $id, 'type' => $type, 'object-id' => $object_id, 'object_type' => $object_type, 'actor' => $actor, 'attributed_to' => $attributed_to]);
				return false;
			}
			if (!empty($object_type)) {
				$object_attributed_to = JsonLD::fetchElement($ldobject['as:object'], 'as:attributedTo', '@id');
				$attributed_to_host   = parse_url($object_attributed_to, PHP_URL_HOST);
				$object_id_host       = parse_url($object_id, PHP_URL_HOST);
				if (!empty($attributed_to_host) && ($attributed_to_host != $object_id_host)) {
					DI::logger()->notice('Host mismatch between received object id and attributed actor', ['id-object-host' => $object_id_host, 'attributed-host' => $attributed_to_host, 'id' => $id, 'type' => $type, 'object-id' => $object_id, 'object_type' => $object_type, 'actor' => $actor, 'object_attributed_to' => $object_attributed_to]);
					return false;
				}
			}
		} elseif (!empty($attributed_to)) {
			$attributed_to_host = parse_url($attributed_to, PHP_URL_HOST);
			if ($attributed_to_host != $id_host) {
				DI::logger()->notice('Host mismatch between received id and attributed actor', ['id-host' => $id_host, 'attributed-host' => $attributed_to_host, 'id' => $id, 'type' => $type, 'object-id' => $object_id, 'object_type' => $object_type, 'actor' => $actor, 'attributed_to' => $attributed_to]);
				return false;
			}
		}

		return true;
	}

	private static function getActivityForObject(array $object, string $actor): array
	{
		if (!empty($object['published'])) {
			$published = $object['published'];
		} else {
			$published = DateTimeFormat::utcNow();
		}

		$activity             = [];
		$activity['@context'] = $object['@context'] ?? ActivityPub::CONTEXT;
		unset($object['@context']);
		$activity['id']        = $object['id'];
		$activity['to']        = $object['to']       ?? [];
		$activity['cc']        = $object['cc']       ?? [];
		$activity['audience']  = $object['audience'] ?? [];
		$activity['actor']     = $actor;
		$activity['object']    = $object;
		$activity['published'] = $published;
		$activity['type']      = 'Create';

		return $activity;
	}

	/**
	 * Test if incoming relay messages should be accepted
	 *
	 * @param array $activity activity array
	 * @return boolean true if message is accepted
	 */
	private static function acceptIncomingMessage(array $activity): bool
	{
		if (empty($activity['as:object'])) {
			$id = JsonLD::fetchElement($activity, '@id');
			DI::logger()->info('No object field in activity - accepted', ['id' => $id]);
			return true;
		}

		$id = JsonLD::fetchElement($activity, 'as:object', '@id');

		$replyto = JsonLD::fetchElement($activity['as:object'], 'as:inReplyTo', '@id');
		$uriid   = ItemURI::getIdByURI($replyto ?? '');
		if (Post::exists(['uri-id' => $uriid])) {
			DI::logger()->info('Post is a reply to an existing post - accepted', ['id' => $id, 'uri-id' => $uriid, 'replyto' => $replyto]);
			return true;
		}

		$attributed_to = JsonLD::fetchElement($activity['as:object'], 'as:attributedTo', '@id');
		$authorid      = Contact::getIdForURL($attributed_to);

		$content = JsonLD::fetchElement($activity['as:object'], 'as:name', '@value')           ?? '';
		$content .= ' ' . JsonLD::fetchElement($activity['as:object'], 'as:summary', '@value') ?? '';
		$content .= ' ' . HTML::toBBCode(JsonLD::fetchElement($activity['as:object'], 'as:content', '@value') ?? '');

		$attachments = JsonLD::fetchElementArray($activity['as:object'], 'as:attachment') ?? [];
		foreach ($attachments as $media) {
			if (!empty($media['as:summary'])) {
				$content .= ' ' . JsonLD::fetchElement($media, 'as:summary', '@value');
			}
			if (!empty($media['as:name'])) {
				$content .= ' ' . JsonLD::fetchElement($media, 'as:name', '@value');
			}
		}

		$messageTags = [];
		$tags        = Receiver::processTags(JsonLD::fetchElementArray($activity['as:object'], 'as:tag') ?? []);
		if (!empty($tags)) {
			foreach ($tags as $tag) {
				if (($tag['type'] != 'Hashtag') && !strpos($tag['type'], ':Hashtag') || empty($tag['name'])) {
					continue;
				}
				$messageTags[] = ltrim(mb_strtolower($tag['name']), '#');
			}
		}

		$languages = self::getPostLanguages($activity['as:object'] ?? '');

		$wanted = Relay::isSolicitedPost($messageTags, $content, $authorid, $id, Protocol::ACTIVITYPUB, $activity['from-relay'], $languages);
		if ($wanted) {
			return true;
		}

		$receivers = [];
		foreach (['as:to', 'as:cc', 'as:bto', 'as:bcc', 'as:audience'] as $element) {
			$receiver_list = JsonLD::fetchElementArray($activity, $element, '@id');
			if (empty($receiver_list)) {
				continue;
			}
			$receivers = array_merge($receivers, $receiver_list);
		}

		$searchtext = Engagement::getSearchTextForActivity($content, $authorid, $messageTags, $receivers);
		$languages  = Item::getLanguageArray($content, 1, 0, $authorid);
		$language   = !empty($languages) ? array_key_first($languages) : '';
		return DI::userDefinedChannel()->match($searchtext, $language);
	}

	/**
	 * Fetch the post language from the content
	 *
	 * @param array $activity
	 * @return array
	 */
	public static function getPostLanguages(array $activity): array
	{
		$content   = JsonLD::fetchElement($activity, 'as:content')                   ?? '';
		$languages = JsonLD::fetchElementArray($activity, 'as:content', '@language') ?? [];
		if (empty($languages)) {
			return [];
		}

		$iso639 = new \Matriphe\ISO639\ISO639();

		$result = [];
		foreach ($languages as $language) {
			if ($language == $content) {
				continue;
			}
			$language = DI::l10n()->toISO6391($language);
			if (!in_array($language, array_column($iso639->allLanguages(), 0))) {
				continue;
			}
			$result[] = $language;
		}
		return $result;
	}

	/**
	 * perform a "follow" request
	 *
	 * @param array $activity
	 * @return void
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function followUser(array $activity)
	{
		$uid = User::getIdForURL($activity['object_id']);
		if (empty($uid)) {
			Queue::remove($activity);
			return;
		}

		$owner = User::getOwnerDataById($uid);
		if (empty($owner)) {
			return;
		}

		$cid = Contact::getIdForURL($activity['actor'], $uid);
		if (!empty($cid)) {
			self::switchContact($cid);
			Contact::update(['hub-verify' => $activity['id'], 'protocol' => Protocol::ACTIVITYPUB], ['id' => $cid]);
		}

		$item = [
			'author-id'   => Contact::getIdForURL($activity['actor']),
			'author-link' => $activity['actor'],
		];

		// Ensure that the contact has got the right network type
		self::switchContact($item['author-id']);

		$result = Contact::addRelationship($owner, [], $item, false, $activity['content'] ?? '');
		if ($result === true) {
			ActivityPub\Transmitter::sendContactAccept($item['author-link'], $activity['id'], $owner['uid']);
		}

		$cid = Contact::getIdForURL($activity['actor'], $uid);
		if (empty($cid)) {
			return;
		}

		if ($result && DI::config()->get('system', 'transmit_pending_events') && ($owner['contact-type'] == Contact::TYPE_COMMUNITY)) {
			self::transmitPendingEvents($cid, $owner['uid']);
		}

		Contact::update(['hub-verify' => $activity['id'], 'protocol' => Protocol::ACTIVITYPUB], ['id' => $cid]);

		DI::logger()->notice('Follow user ' . $uid . ' from contact ' . $cid . ' with id ' . $activity['id']);
		Queue::remove($activity);
	}

	/**
	 * Transmit pending events to the new follower
	 *
	 * @param integer $cid Contact id
	 * @param integer $uid User id
	 * @return void
	 */
	private static function transmitPendingEvents(int $cid, int $uid)
	{
		$account = DBA::selectFirst('account-user-view', ['ap-inbox', 'ap-sharedinbox'], ['id' => $cid]);
		$inbox   = $account['ap-sharedinbox'] ?: $account['ap-inbox'];

		$events = DBA::select('event', ['id'], ["`uid` = ? AND `start` > ? AND `type` != ?", $uid, DateTimeFormat::utcNow(), 'birthday']);
		while ($event = DBA::fetch($events)) {
			$post = Post::selectFirst(['id', 'uri-id', 'created'], ['event-id' => $event['id']]);
			if (empty($post)) {
				continue;
			}
			if (DI::config()->get('system', 'bulk_delivery')) {
				Post\Delivery::add($post['uri-id'], $uid, $inbox, $post['created'], Delivery::POST, [$cid]);
				Worker::add(Worker::PRIORITY_HIGH, 'APDelivery', '', 0, $inbox, 0);
			} else {
				Worker::add(Worker::PRIORITY_HIGH, 'APDelivery', Delivery::POST, $post['id'], $inbox, $uid, [$cid], $post['uri-id']);
			}
		}
	}

	/**
	 * Update the given profile
	 *
	 * @param array $activity
	 * @throws \Exception
	 */
	public static function updatePerson(array $activity)
	{
		if (empty($activity['object_id'])) {
			return;
		}

		DI::logger()->info('Updating profile', ['object' => $activity['object_id']]);
		Contact::updateFromProbeByURL($activity['object_id']);
		Queue::remove($activity);
	}

	/**
	 * Delete the given profile
	 *
	 * @param array $activity
	 * @return void
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function deletePerson(array $activity)
	{
		if (empty($activity['object_id']) || empty($activity['actor'])) {
			DI::logger()->info('Empty object id or actor.');
			Queue::remove($activity);
			return;
		}

		if ($activity['object_id'] != $activity['actor']) {
			DI::logger()->info('Object id does not match actor.');
			Queue::remove($activity);
			return;
		}

		$contacts = DBA::select('contact', ['id'], ['nurl' => Strings::normaliseLink($activity['object_id'])]);
		while ($contact = DBA::fetch($contacts)) {
			Contact::remove($contact['id']);
		}
		DBA::close($contacts);

		DI::logger()->info('Deleted contact', ['object' => $activity['object_id']]);
		Queue::remove($activity);
	}

	/**
	 * Add moved contacts as followers for all subscribers of the old contact
	 *
	 * @param array $activity
	 * @return void
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function movePerson(array $activity)
	{
		if (empty($activity['target_id']) || empty($activity['object_id'])) {
			Queue::remove($activity);
			return;
		}

		if ($activity['object_id'] != $activity['actor']) {
			DI::logger()->notice('Object is not the actor', ['activity' => $activity]);
			Queue::remove($activity);
			return;
		}

		$from = Contact::getByURL($activity['object_id'], false, ['uri-id']);
		if (empty($from['uri-id'])) {
			DI::logger()->info('Object not found', ['activity' => $activity]);
			Queue::remove($activity);
			return;
		}

		$contacts = DBA::select('contact', ['uid', 'url'], ["`uri-id` = ? AND `uid` != ? AND `rel` IN (?, ?)", $from['uri-id'], 0, Contact::FRIEND, Contact::SHARING]);
		while ($from_contact = DBA::fetch($contacts)) {
			$result = Contact::createFromProbeForUser($from_contact['uid'], $activity['target_id']);
			DI::logger()->debug('Follower added', ['from' => $from_contact, 'result' => $result]);
		}
		DBA::close($contacts);
		Queue::remove($activity);
	}

	/**
	 * Blocks the user by the contact
	 *
	 * @param array $activity
	 * @return void
	 * @throws \Exception
	 */
	public static function blockAccount(array $activity)
	{
		$cid = Contact::getIdForURL($activity['actor']);
		if (empty($cid)) {
			return;
		}

		$uid = User::getIdForURL($activity['object_id']);
		if (empty($uid)) {
			return;
		}

		Contact\User::setIsBlocked($cid, $uid, true);

		DI::logger()->info('Contact blocked user', ['contact' => $cid, 'user' => $uid]);
		Queue::remove($activity);
	}

	/**
	 * Unblocks the user by the contact
	 *
	 * @param array $activity
	 * @return void
	 * @throws \Exception
	 */
	public static function unblockAccount(array $activity)
	{
		$cid = Contact::getIdForURL($activity['actor']);
		if (empty($cid)) {
			return;
		}

		$uid = User::getIdForURL($activity['object_object']);
		if (empty($uid)) {
			return;
		}

		Contact\User::setIsBlocked($cid, $uid, false);

		DI::logger()->info('Contact unblocked user', ['contact' => $cid, 'user' => $uid]);
		Queue::remove($activity);
	}

	/**
	 * Report a user
	 *
	 * @param array $activity
	 * @return void
	 * @throws \Exception
	 */
	public static function ReportAccount(array $activity)
	{
		$account = Contact::getByURL($activity['object_id'], null, ['id', 'gsid']);
		if (empty($account)) {
			DI::logger()->info('Unknown account', ['activity' => $activity]);
			Queue::remove($activity);
			return;
		}

		$reporter_id = Contact::getIdForURL($activity['actor']);
		if (empty($reporter_id)) {
			DI::logger()->info('Unknown actor', ['activity' => $activity]);
			Queue::remove($activity);
			return;
		}

		$uri_ids = [];
		foreach ($activity['object_ids'] as $status_id) {
			$post = Post::selectFirst(['uri-id'], ['uri' => $status_id]);
			if (!empty($post['uri-id'])) {
				$uri_ids[] = $post['uri-id'];
			}
		}

		$report = DI::reportFactory()->createFromReportsRequest(System::getRules(true), $reporter_id, $account['id'], $account['gsid'], $activity['content'], 'other', false, $uri_ids);
		DI::report()->save($report);

		DI::logger()->info('Stored report', ['reporter' => $reporter_id, 'account' => $account, 'comment' => $activity['content'], 'object_ids' => $activity['object_ids']]);
	}

	/**
	 * Accept a follow request
	 *
	 * @param array $activity
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function acceptFollowUser(array $activity)
	{
		$check_id = false;

		if (!empty($activity['object_actor'])) {
			$uid = User::getIdForURL($activity['object_actor']);
		} elseif (!empty($activity['receiver']) && (count($activity['receiver']) == 1)) {
			$uid      = array_shift($activity['receiver']);
			$check_id = true;
		}

		if (empty($uid)) {
			DI::logger()->notice('User could not be detected', ['activity' => $activity]);
			Queue::remove($activity);
			return;
		}

		$cid = Contact::getIdForURL($activity['actor'], $uid);
		if (empty($cid)) {
			DI::logger()->notice('No contact found', ['actor' => $activity['actor']]);
			Queue::remove($activity);
			return;
		}

		$id = Transmitter::activityIDFromContact($cid);
		if ($id == $activity['object_id']) {
			DI::logger()->info('Successful id check', ['uid' => $uid, 'cid' => $cid]);
		} else {
			DI::logger()->info('Unsuccessful id check', ['uid' => $uid, 'cid' => $cid, 'id' => $id, 'object_id' => $activity['object_id']]);
			if ($check_id) {
				Queue::remove($activity);
				return;
			}
		}

		self::switchContact($cid);

		$fields = ['pending' => false];

		$contact = DBA::selectFirst('contact', ['rel'], ['id' => $cid]);
		if ($contact['rel'] == Contact::FOLLOWER) {
			$fields['rel'] = Contact::FRIEND;
		}

		$condition = ['id' => $cid];
		Contact::update($fields, $condition);
		DI::logger()->info('Accept contact request', ['contact' => $cid, 'user' => $uid]);
		Queue::remove($activity);
	}

	/**
	 * Reject a follow request
	 *
	 * @param array $activity
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function rejectFollowUser(array $activity)
	{
		$uid = User::getIdForURL($activity['object_actor']);
		if (empty($uid)) {
			return;
		}

		$cid = Contact::getIdForURL($activity['actor'], $uid);
		if (empty($cid)) {
			DI::logger()->info('No contact found', ['actor' => $activity['actor']]);
			return;
		}

		self::switchContact($cid);

		$contact = Contact::getById($cid, ['rel']);
		if ($contact['rel'] == Contact::SHARING) {
			Contact::remove($cid);
			DI::logger()->info('Rejected contact request - contact removed', ['contact' => $cid, 'user' => $uid]);
		} elseif ($contact['rel'] == Contact::FRIEND) {
			Contact::update(['rel' => Contact::FOLLOWER], ['id' => $cid]);
		} else {
			DI::logger()->info('Rejected contact request', ['contact' => $cid, 'user' => $uid]);
		}
		Queue::remove($activity);
	}

	/**
	 * Undo activity like "like" or "dislike"
	 *
	 * @param array $activity
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function undoActivity(array $activity)
	{
		if (empty($activity['object_id'])) {
			return;
		}

		if (empty($activity['object_actor'])) {
			return;
		}

		$author_id = Contact::getIdForURL($activity['object_actor']);
		if (empty($author_id)) {
			return;
		}

		Item::markForDeletion(['uri' => $activity['object_id'], 'author-id' => $author_id, 'gravity' => Item::GRAVITY_ACTIVITY]);
		Queue::remove($activity);
	}

	/**
	 * Activity to remove a follower
	 *
	 * @param array $activity
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function undoFollowUser(array $activity)
	{
		$uid = User::getIdForURL($activity['object_object']);
		if (empty($uid)) {
			return;
		}

		$owner = User::getOwnerDataById($uid);
		if (empty($owner)) {
			return;
		}

		$cid = Contact::getIdForURL($activity['actor'], $uid);
		if (empty($cid)) {
			DI::logger()->info('No contact found', ['actor' => $activity['actor']]);
			return;
		}

		self::switchContact($cid);

		$contact = DBA::selectFirst('contact', [], ['id' => $cid]);
		if (!DBA::isResult($contact)) {
			return;
		}

		Contact::removeFollower($contact);
		DI::logger()->info('Undo following request', ['contact' => $cid, 'user' => $uid]);
		Queue::remove($activity);
	}

	/**
	 * Switches a contact to AP if needed
	 *
	 * @param integer $cid Contact ID
	 * @return void
	 * @throws \Exception
	 */
	private static function switchContact(int $cid)
	{
		$contact = DBA::selectFirst('contact', ['network', 'url'], ['id' => $cid]);
		if (!DBA::isResult($contact) || in_array($contact['network'], [Protocol::ACTIVITYPUB, Protocol::DFRN]) || Contact::isLocal($contact['url'])) {
			return;
		}

		DI::logger()->info('Change existing contact', ['cid' => $cid, 'previous' => $contact['network']]);
		Contact::updateFromProbe($cid);
	}

	/**
	 * Collects implicit mentions like:
	 * - the author of the parent item
	 * - all the mentioned conversants in the parent item
	 *
	 * @param array $parent Item array with at least ['id', 'author-link', 'alias']
	 * @return array
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function getImplicitMentionList(array $parent): array
	{
		$parent_terms = Tag::getByURIId($parent['uri-id'], [Tag::MENTION, Tag::IMPLICIT_MENTION, Tag::EXCLUSIVE_MENTION]);

		$parent_author = Contact::getByURL($parent['author-link'], false, ['url', 'nurl', 'alias']);

		$implicit_mentions = [];
		if (empty($parent_author['url'])) {
			DI::logger()->notice('Author public contact unknown.', ['author-link' => $parent['author-link'], 'parent-id' => $parent['id']]);
		} else {
			$implicit_mentions[] = $parent_author['url'];
			$implicit_mentions[] = $parent_author['nurl'];
			$implicit_mentions[] = $parent_author['alias'];
		}

		if (!empty($parent['alias'])) {
			$implicit_mentions[] = $parent['alias'];
		}

		foreach ($parent_terms as $term) {
			$contact = Contact::getByURL($term['url'], false, ['url', 'nurl', 'alias']);
			if (!empty($contact['url'])) {
				$implicit_mentions[] = $contact['url'];
				$implicit_mentions[] = $contact['nurl'];
				$implicit_mentions[] = $contact['alias'];
			}
		}

		return $implicit_mentions;
	}

	/**
	 * Strips from the body prepended implicit mentions
	 *
	 * @param string $body
	 * @param array $parent
	 * @return string
	 */
	private static function removeImplicitMentionsFromBody(string $body, array $parent): string
	{
		if (DI::config()->get('system', 'disable_implicit_mentions')) {
			return $body;
		}

		$potential_mentions = self::getImplicitMentionList($parent);

		$kept_mentions = [];

		// Extract one prepended mention at a time from the body
		while (preg_match('#^(@\[url=([^\]]+)].*?\[\/url]\s)(.*)#is', $body, $matches)) {
			if (!in_array($matches[2], $potential_mentions)) {
				$kept_mentions[] = $matches[1];
			}

			$body = $matches[3];
		}

		// Re-appending the kept mentions to the body after extraction
		$kept_mentions[] = $body;

		return implode('', $kept_mentions);
	}

	/**
	 * Adds links to string mentions
	 *
	 * @param string $body
	 * @param array  $tags
	 * @return string
	 */
	protected static function addMentionLinks(string $body, array $tags): string
	{
		// This prevents links to be added again to Pleroma-style mention links
		$body = self::normalizeMentionLinks($body);

		$body = BBCode::performWithEscapedTags($body, ['url'], function ($body) use ($tags) {
			foreach ($tags as $tag) {
				if (empty($tag['name']) || empty($tag['type']) || empty($tag['href']) || !in_array($tag['type'], ['Mention', 'Hashtag'])) {
					continue;
				}

				$hash = substr($tag['name'], 0, 1);
				$name = substr($tag['name'], 1);
				if (!in_array($hash, Tag::TAG_CHARACTER)) {
					$hash = '';
					$name = $tag['name'];
				}

				if (Network::isValidHttpUrl($tag['href'])) {
					$body = str_replace($tag['name'], $hash . '[url=' . $tag['href'] . ']' . $name . '[/url]', $body);
				}
			}

			return $body;
		});

		return $body;
	}

	/**
	 * Add the current function to the callstack
	 *
	 * @param array $callstack
	 * @return array
	 */
	public static function addToCallstack(array $callstack): array
	{
		$trace     = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		$functions = array_slice(array_column($trace, 'function'), 1);
		$function  = array_shift($functions);

		if (in_array($function, $callstack)) {
			DI::logger()->notice('Callstack already contains "' . $function . '"', ['callstack' => $callstack]);
		}

		$callstack[] = $function;

		return $callstack;
	}
}
