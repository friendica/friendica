<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Protocol\ActivityPub;

use Friendica\Content\Text\BBCode;
use Friendica\Content\Text\HTML;
use Friendica\Content\Text\Markdown;
use Friendica\Core\Cache\Enum\Duration;
use Friendica\Core\Logger;
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
		$lang = [];
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
		$body = strtr($body,
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

		$data = ['uri-id' => $uriid];
		$data['type'] = Post\Media::UNKNOWN;
		$data['url'] = $attachment['url'];
		$data['mimetype'] = $attachment['mediaType'] ?? null;
		$data['height'] = $attachment['height'] ?? null;
		$data['width'] = $attachment['width'] ?? null;
		$data['size'] = $attachment['size'] ?? null;
		$data['preview'] = $attachment['image'] ?? null;
		$data['description'] = $attachment['name'] ?? null;

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

		Logger::debug('Storing incoming question', ['type' => $activity['type'], 'uri-id' => $item['uri-id'], 'question' => $activity['question']]);
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
			Logger::notice('No existing item, item will be created', ['uri' => $activity['id']]);
			$item = self::createItem($activity, false);
			if (empty($item)) {
				Queue::remove($activity);
				return;
			}

			self::postItem($activity, $item);
			return;
		}

		$item['changed'] = DateTimeFormat::utcNow();
		$item['edited'] = DateTimeFormat::utc($activity['updated']);

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

		$event['edited']   = DateTimeFormat::utc($activity['updated']);
		$event['summary']  = HTML::toBBCode($activity['name']);
		$event['desc']     = HTML::toBBCode($activity['content']);
		if (!empty($activity['start-time'])) {
			$event['start']  = DateTimeFormat::utc($activity['start-time']);
		}
		if (!empty($activity['end-time'])) {
			$event['finish'] = DateTimeFormat::utc($activity['end-time']);
		}
		$event['nofinish'] = empty($event['finish']);
		$event['location'] = $activity['location'];

		Logger::info('Updating event', ['uri' => $activity['id'], 'id' => $event_id]);
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
		$item = [];
		$item['verb'] = Activity::POST;
		$item['thr-parent'] = $activity['reply-to-id'];

		if ($activity['reply-to-id'] == $activity['id']) {
			$item['gravity'] = Item::GRAVITY_PARENT;
			$item['object-type'] = Activity\ObjectType::NOTE;
		} else {
			$item['gravity'] = Item::GRAVITY_COMMENT;
			$item['object-type'] = Activity\ObjectType::COMMENT;
		}

		if (!empty($activity['conversation'])) {
			$item['conversation'] = $activity['conversation'];
		} elseif (!empty($activity['context'])) {
			$item['conversation'] = $activity['context'];
		}

		if (!empty($item['conversation'])) {
			$conversation = Post::selectFirstThread(['uri'], ['conversation' => $item['conversation']]);
			if (!empty($conversation)) {
				Logger::debug('Got conversation', ['conversation' => $item['conversation'], 'parent' => $conversation]);
				$item['parent-uri'] = $conversation['uri'];
				$item['parent-uri-id'] = ItemURI::getIdByURI($item['parent-uri']);
			}
		} else {
			$conversation = [];
		}

		Logger::debug('Create Item', ['id' => $activity['id'], 'conversation' => $item['conversation'] ?? '']);
		if (empty($activity['author']) && empty($activity['actor'])) {
			Logger::notice('Missing author and actor. We quit here.', ['activity' => $activity]);
			Queue::remove($activity);
			return [];
		}

		if (!in_array(0, $activity['receiver']) || !DI::config()->get('system', 'fetch_parents')) {
			$fetch_parents = false;
		}

		if ($fetch_parents && empty($activity['directmessage']) && ($activity['id'] != $activity['reply-to-id']) && !Post::exists(['uri' => $activity['reply-to-id']])) {
			$result = self::fetchParent($activity, !empty($conversation));
			if (!empty($result)) {
				if (($item['thr-parent'] != $result) && Post::exists(['uri' => $result])) {
					$item['thr-parent'] = $result;
				}
			} elseif (empty($conversation)) {
				return [];
			}
		}

		$item['diaspora_signed_text'] = $activity['diaspora:comment'] ?? '';

		if (empty($conversation) && empty($activity['directmessage']) && ($item['gravity'] != Item::GRAVITY_PARENT) && !Post::exists(['uri' => $item['thr-parent']])) {
			Logger::notice('Parent not found, message will be discarded.', ['thr-parent' => $item['thr-parent']]);
			if (!$fetch_parents) {
				Queue::remove($activity);
			}
			return [];
		}

		$item['network'] = Protocol::ACTIVITYPUB;
		$item['author-link'] = $activity['author'];
		$item['author-id'] = Contact::getIdForURL($activity['author']);
		$item['owner-link'] = $activity['actor'];
		$item['owner-id'] = Contact::getIdForURL($activity['actor']);

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

		$item['isForum'] = false;

		if (!empty($activity['thread-completion'])) {
			if ($activity['thread-completion'] != $item['owner-id']) {
				$actor = Contact::getById($activity['thread-completion'], ['url']);
				$item['causer-link'] = $actor['url'];
				$item['causer-id'] = $activity['thread-completion'];
				Logger::info('Use inherited actor as causer.', ['id' => $item['owner-id'], 'activity' => $activity['thread-completion'], 'owner' => $item['owner-link'], 'actor' => $actor['url']]);
			} else {
				// Store the original actor in the "causer" fields to enable the check for ignored or blocked contacts
				$item['causer-link'] = $item['owner-link'];
				$item['causer-id']   = $item['owner-id'];
				Logger::info('Use actor as causer.', ['id' => $item['owner-id'], 'actor' => $item['owner-link']]);
			}

			$item['owner-link'] = $item['author-link'];
			$item['owner-id'] = $item['author-id'];
		} else {
			$actor = APContact::getByURL($item['owner-link'], false);
			$item['isForum'] = ($actor['type'] ?? 'Person') == 'Group';
		}

		$item['uri'] = $activity['id'];

		if (empty($activity['published']) || empty($activity['updated'])) {
			DI::logger()->notice('published or updated keys are empty for activity', ['activity' => $activity, 'callstack' => System::callstack(10)]);
		}

		$item['created'] = DateTimeFormat::utc($activity['published'] ?? 'now');
		$item['edited'] = DateTimeFormat::utc($activity['updated'] ?? 'now');
		$guid = $activity['sc:identifier'] ?: self::getGUIDByURL($item['uri']);
		$item['guid'] = $activity['diaspora:guid'] ?: $guid;

		$item['uri-id'] = ItemURI::insert(['uri' => $item['uri'], 'guid' => $item['guid']]);
		if (empty($item['uri-id'])) {
			Logger::warning('Unable to get a uri-id for an item uri', ['uri' => $item['uri'], 'guid' => $item['guid']]);
			return [];
		}

		$item['thr-parent-id'] = ItemURI::getIdByURI($item['thr-parent']);

		$item = self::processContent($activity, $item);
		if (empty($item)) {
			Logger::info('Message was not processed');
			Queue::remove($activity);
			return [];
		}

		$item['plink'] = $activity['alternate-url'] ?? $item['uri'];

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
		if (self::isFetched($activity['reply-to-id'])) {
			Logger::info('Id is already fetched', ['id' => $activity['reply-to-id']]);
			return '';
		}

		self::addActivityId($activity['reply-to-id']);

		if (!DI::config()->get('system', 'fetch_by_worker')) {
			$in_background = false;
		}

		$recursion_depth = $activity['recursion-depth'] ?? 0;

		if (!$in_background && ($recursion_depth < DI::config()->get('system', 'max_recursion_depth'))) {
			Logger::info('Parent not found. Try to refetch it.', ['parent' => $activity['reply-to-id'], 'recursion-depth' => $recursion_depth]);
			$result = self::fetchMissingActivity($activity['reply-to-id'], $activity, '', Receiver::COMPLETION_AUTO);
			if (empty($result) && self::isActivityGone($activity['reply-to-id'])) {
				Logger::notice('The activity is gone, the queue entry will be deleted', ['parent' => $activity['reply-to-id']]);
				if (!empty($activity['entry-id'])) {
					Queue::deleteById($activity['entry-id']);
				}
				return '';
			} elseif (!empty($result)) {
				$exists = Post::exists(['uri' => [$result, $activity['reply-to-id']]]);
				if ($exists) {
					Logger::info('The activity has been fetched and created.', ['parent' => $result]);
					return $result;
				} elseif (DI::config()->get('system', 'fetch_by_worker') || DI::config()->get('system', 'decoupled_receiver')) {
					Logger::info('The activity has been fetched and will hopefully be created later.', ['parent' => $result]);
				} else {
					Logger::notice('The activity exists but has not been created, the queue entry will be deleted.', ['parent' => $result]);
					if (!empty($activity['entry-id'])) {
						Queue::deleteById($activity['entry-id']);
					}
				}
				return '';
			}
			if (empty($result) && !DI::config()->get('system', 'fetch_by_worker')) {
				return '';
			}
		} elseif (self::isActivityGone($activity['reply-to-id'])) {
			Logger::notice('The activity is gone. We will not spawn a worker. The queue entry will be deleted', ['parent' => $activity['reply-to-id']]);
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
			Logger::notice('Fetching is done in the background.', ['parent' => $activity['reply-to-id']]);
		} else {
			Logger::notice('Recursion level is too high.', ['parent' => $activity['reply-to-id'], 'recursion-depth' => $recursion_depth]);
		}

		if (!Fetch::hasWorker($activity['reply-to-id'])) {
			Logger::notice('Fetching is done by worker.', ['parent' => $activity['reply-to-id'], 'recursion-depth' => $recursion_depth]);
			Fetch::add($activity['reply-to-id']);
			$activity['recursion-depth'] = 0;
			$wid = Worker::add(Worker::PRIORITY_HIGH, 'FetchMissingActivity', $activity['reply-to-id'], $activity, '', Receiver::COMPLETION_AUTO);
			Fetch::setWorkerId($activity['reply-to-id'], $wid);
		} else {
			Logger::debug('Activity will already be fetched via a worker.', ['url' => $activity['reply-to-id']]);
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
		try {
			$curlResult = HTTPSignature::fetchRaw($url, 0);
		} catch (\Exception $exception) {
			Logger::notice('Error fetching url', ['url' => $url, 'exception' => $exception]);
			return true;
		}

		if (Network::isUrlBlocked($url)) {
			return true;
		}

		// @todo To ensure that the remote system is working correctly, we can check if the "Content-Type" contains JSON
		if (in_array($curlResult->getReturnCode(), [401, 404])) {
			return true;
		}

		if ($curlResult->isSuccess()) {
			$object = json_decode($curlResult->getBody(), true);
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

		Logger::info('Deleting item', ['object' => $activity['object_id'], 'owner'  => $owner]);
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
				Logger::info('Not origin, not from the author, skipping update', ['id' => $item['id'], 'author' => $item['author-link'], 'actor' => $activity['actor']]);
				continue;
			}

			Tag::store($item['uri-id'], Tag::HASHTAG, $activity['object_content'], $activity['object_id']);
			Logger::info('Tagged item', ['id' => $item['id'], 'tag' => $activity['object_content'], 'uri' => $activity['target_id'], 'actor' => $activity['actor']]);
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
		$item = self::createItem($activity, false);
		if (empty($item)) {
			Logger::debug('Activity was not prepared', ['id' => $activity['object_id']]);
			return;
		}

		$item['verb'] = $verb;
		$item['thr-parent'] = $activity['object_id'];
		$item['gravity'] = Item::GRAVITY_ACTIVITY;
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
			$parent;
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
		if (empty($post)) {
			return;
		}

		Logger::debug('Add post to featured collection', ['post' => $post]);

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
			return;
		}

		Logger::debug('Remove post from featured collection', ['post' => $post]);

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
		$event['summary']   = HTML::toBBCode($activity['name'] ?: $activity['summary']);
		$event['desc']      = HTML::toBBCode($activity['content'] ?? '');
		if (!empty($activity['start-time'])) {
			$event['start']  = DateTimeFormat::utc($activity['start-time']);
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

		Logger::info('Event was stored', ['id' => $event_id]);

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
			$content = Markdown::toBBCode($activity['content']);
		} elseif (!empty($activity['mediatype']) && ($activity['mediatype'] == 'text/bbcode')) {
			$item['title'] = $activity['name'];
			$content = $activity['content'];
		} else {
			// By default assume "text/html"
			$item['title'] = HTML::toBBCode($activity['name'] ?? '');
			$content = HTML::toBBCode($activity['content'] ?? '');
		}

		$item['title'] = trim(BBCode::toPlaintext($item['title']));

		if (!empty($activity['languages'])) {
			$item['language'] = self::processLanguages($activity['languages']);
		}

		if (!empty($activity['emojis'])) {
			$content = self::replaceEmojis($item['uri-id'], $content, $activity['emojis']);
		}

		$content = self::addMentionLinks($content, $activity['tags']);

		if (!empty($activity['quote-url'])) {
			$id = Item::fetchByLink($activity['quote-url']);
			if ($id) {
				$shared_item = Post::selectFirst(['uri-id'], ['id' => $id]);
				$item['quote-uri-id'] = $shared_item['uri-id'];
			} else {
				Logger::info('Quote was not fetched', ['guid' => $item['guid'], 'uri-id' => $item['uri-id'], 'quote' => $activity['quote-url']]);
			}
		}

		if (!empty($activity['source'])) {
			$item['body'] = $activity['source'];
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
					Logger::warning('Unknown parent item.', ['uri' => $parent_uri]);
					return false;
				}
				$content = self::removeImplicitMentionsFromBody($content, $parent);
			}
			$item['content-warning'] = HTML::toBBCode($activity['summary'] ?? '');
			$item['raw-body'] = $item['body'] = $content;
		}

		self::storeFromBody($item);
		self::storeTags($item['uri-id'], $activity['tags']);

		self::storeReceivers($item['uri-id'], $activity['receiver_urls'] ?? []);

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

		return $host_hash . '-'. hash('fnv164', $path) . '-'. hash('joaat', $path);
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
			Logger::debug('Message has got several receivers - accepted', ['uri-id' => $item['uri-id'], 'guid' => $item['guid'], 'url' => $item['uri']]);
			return true;
		}

		if ($item['private'] == Item::PRIVATE) {
			// We only look at public posts here. Private posts are expected to be intentionally posted to the single receiver.
			Logger::debug('Message is private - accepted', ['uri-id' => $item['uri-id'], 'guid' => $item['guid'], 'url' => $item['uri']]);
			return true;
		}

		if (!empty($activity['from-relay'])) {
			// We check relay posts at another place. When it arrived here, the message is already checked.
			Logger::debug('Message is a relay post that is already checked - accepted', ['uri-id' => $item['uri-id'], 'guid' => $item['guid'], 'url' => $item['uri']]);
			return true;
		}

		if (in_array($activity['completion-mode'] ?? Receiver::COMPLETION_NONE, [Receiver::COMPLETION_MANUAL, Receiver::COMPLETION_ANNOUNCE])) {
			// Manual completions and completions caused by reshares are allowed without any further checks.
			Logger::debug('Message is in completion mode - accepted', ['mode' => $activity['completion-mode'], 'uri-id' => $item['uri-id'], 'guid' => $item['guid'], 'url' => $item['uri']]);
			return true;
		}

		if ($item['gravity'] != Item::GRAVITY_PARENT) {
			// We cannot reliably check at this point if a comment or activity belongs to an accepted post or needs to be fetched
			// This can possibly be improved in the future.
			Logger::debug('Message is no parent - accepted', ['uri-id' => $item['uri-id'], 'guid' => $item['guid'], 'url' => $item['uri']]);
			return true;
		}

		$tags = array_column(Tag::getByURIId($item['uri-id'], [Tag::HASHTAG]), 'name');
		if (Relay::isSolicitedPost($tags, $item['body'], $item['author-id'], $item['uri'], Protocol::ACTIVITYPUB, $activity['thread-completion'] ?? 0)) {
			Logger::debug('Post is accepted because of the relay settings', ['uri-id' => $item['uri-id'], 'guid' => $item['guid'], 'url' => $item['uri']]);
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

		$stored = false;
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
			switch($type) {
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
				Logger::debug('Relay post for follower', ['receiver' => $receiver, 'guid' => $item['guid'], 'relay' => $activity['from-relay']]);
				$item['causer-id'] = ($item['gravity'] == Item::GRAVITY_PARENT) ? $item['owner-id'] : $item['author-id'];
			}

			if ($item['isForum'] ?? false) {
				$item['contact-id'] = Contact::getIdForURL($activity['actor'], $receiver);
			} else {
				$item['contact-id'] = Contact::getIdForURL($activity['author'], $receiver);
			}

			if (($receiver != 0) && empty($item['contact-id'])) {
				$item['contact-id'] = Contact::getIdForURL($activity['author']);
			}

			if (!empty($activity['directmessage'])) {
				self::postMail($activity, $item);
				continue;
			}

			if (($receiver != 0) && ($item['gravity'] == Item::GRAVITY_PARENT) && !in_array($item['post-reason'], [Item::PR_FOLLOWER, Item::PR_TAG, item::PR_TO, Item::PR_CC])) {
				if (!($item['isForum'] ?? false)) {
					if ($item['post-reason'] == Item::PR_BCC) {
						Logger::info('Top level post via BCC from a non sharer, ignoring', ['uid' => $receiver, 'contact' => $item['contact-id'], 'url' => $item['uri']]);
						continue;
					}

					if ((DI::pConfig()->get($receiver, 'system', 'accept_only_sharer') != Item::COMPLETION_LIKE)
						&& in_array($activity['thread-children-type'] ?? '', Receiver::ACTIVITY_TYPES)) {
						Logger::info('Top level post from thread completion from a non sharer had been initiated via an activity, ignoring',
							['type' => $activity['thread-children-type'], 'user' => $item['uid'], 'causer' => $item['causer-link'], 'author' => $activity['author'], 'url' => $item['uri']]);
						continue;
					}
				}

				$is_forum = false;
				$user = User::getById($receiver, ['account-type']);
				if (!empty($user['account-type'])) {
					$is_forum = ($user['account-type'] == User::ACCOUNT_TYPE_COMMUNITY);
				}

				if ((DI::pConfig()->get($receiver, 'system', 'accept_only_sharer') == Item::COMPLETION_NONE)
					&& ((!$is_forum && !($item['isForum'] ?? false) && ($activity['type'] != 'as:Announce'))
					|| !Contact::isSharingByURL($activity['actor'], $receiver))) {
					Logger::info('Actor is a non sharer, is no forum or it is no announce', ['uid' => $receiver, 'actor' => $activity['actor'], 'url' => $item['uri'], 'type' => $activity['type']]);
					continue;
				}

				Logger::info('Accepting post', ['uid' => $receiver, 'url' => $item['uri']]);
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
				Logger::info('Item insertion successful', ['user' => $item['uid'], 'item_id' => $item_id]);
				$success = true;
			} else {
				Logger::notice('Item insertion aborted', ['uri' => $item['uri'], 'uid' => $item['uid']]);
				if (($item['uid'] == 0) && (count($activity['receiver']) > 1)) {
					Logger::info('Public item was aborted. We skip for all users.', ['uri' => $item['uri']]);
					break;
				}
			}

			if ($item['uid'] == 0) {
				$stored = $item_id;
			}
		}

		Queue::remove($activity);

		if ($success && Queue::hasChildren($item['uri']) && Post::exists(['uri' => $item['uri']])) {
			Queue::processReplyByUri($item['uri']);
		}

		// Store send a follow request for every reshare - but only when the item had been stored
		if ($stored && ($item['private'] != Item::PRIVATE) && ($item['gravity'] == Item::GRAVITY_PARENT) && !empty($item['author-link']) && ($item['author-link'] != $item['owner-link'])) {
			$author = APContact::getByURL($item['owner-link'], false);
			// We send automatic follow requests for reshared messages. (We don't need though for forum posts)
			if ($author['type'] != 'Group') {
				Logger::info('Send follow request', ['uri' => $item['uri'], 'stored' => $stored, 'to' => $item['author-link']]);
				ActivityPub\Transmitter::sendFollowObject($item['uri'], $item['author-link']);
			}
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

		if (!empty($item['parent-uri-id'])) {
			if (Post::exists(['uri-id' => $item['parent-uri-id'], 'uid' => $receiver])) {
				$has_parents = true;
			} elseif ($add_parent && Post::exists(['uri-id' => $item['parent-uri-id'], 'uid' => 0])) {
				$stored = Item::storeForUserByUriId($item['parent-uri-id'], $receiver, $fields);
				$has_parents = (bool)$stored;
				if ($stored) {
					Logger::notice('Inserted missing parent post', ['stored' => $stored, 'uid' => $receiver, 'parent' => $item['parent-uri']]);
				} else {
					Logger::notice('Parent could not be added.', ['uid' => $receiver, 'uri' => $item['uri'], 'parent' => $item['parent-uri']]);
					return false;
				}
			} elseif ($add_parent) {
				Logger::debug('Parent does not exist.', ['uid' => $receiver, 'uri' => $item['uri'], 'parent' => $item['parent-uri']]);
			} else {
				Logger::debug('Parent should not be added.', ['uid' => $receiver, 'gravity' => $item['gravity'], 'verb' => $item['verb'], 'guid' => $item['guid'], 'uri' => $item['uri'], 'parent' => $item['parent-uri']]);
			}
		}

		if (empty($item['parent-uri-id']) || ($item['thr-parent-id'] != $item['parent-uri-id'])) {
			if (Post::exists(['uri-id' => $item['thr-parent-id'], 'uid' => $receiver])) {
				$has_parents = true;
			} elseif (($has_parents || $add_parent) && Post::exists(['uri-id' => $item['thr-parent-id'], 'uid' => 0])) {
				$stored = Item::storeForUserByUriId($item['thr-parent-id'], $receiver, $fields);
				$has_parents = $has_parents || (bool)$stored;
				if ($stored) {
					Logger::notice('Inserted missing thread parent post', ['stored' => $stored, 'uid' => $receiver, 'thread-parent' => $item['thr-parent']]);
				} else {
					Logger::notice('Thread parent could not be added.', ['uid' => $receiver, 'uri' => $item['uri'], 'thread-parent' => $item['thr-parent']]);
				}
			} elseif ($add_parent) {
				Logger::debug('Thread parent does not exist.', ['uid' => $receiver, 'uri' => $item['uri'], 'thread-parent' => $item['thr-parent']]);
			} else {
				Logger::debug('Thread parent should not be added.', ['uid' => $receiver, 'gravity' => $item['gravity'], 'verb' => $item['verb'], 'guid' => $item['guid'], 'uri' => $item['uri'], 'thread-parent' => $item['thr-parent']]);
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
		foreach (['as:to' => Tag::TO, 'as:cc' => Tag::CC, 'as:bto' => Tag::BTO, 'as:bcc' => Tag::BCC] as $element => $type) {
			if (!empty($receivers[$element])) {
				foreach ($receivers[$element] as $receiver) {
					if ($receiver == ActivityPub::PUBLIC_COLLECTION) {
						$name = Receiver::PUBLIC_COLLECTION;
					} elseif ($path = parse_url($receiver, PHP_URL_PATH)) {
						$name = trim($path, '/');
					} elseif ($host = parse_url($receiver, PHP_URL_HOST)) {
						$name = $host;
					} else {
						Logger::warning('Unable to coerce name from receiver', ['element' => $element, 'type' => $type, 'receiver' => $receiver]);
						$name = '';
					}

					$target = Tag::getTargetType($receiver);
					Logger::debug('Got target type', ['type' => $target, 'url' => $receiver]);
					Tag::store($uriid, $type, $name, $receiver, $target);
				}
			}
		}
	}

	/**
	 * Creates an mail post
	 *
	 * @param array $activity Activity data
	 * @param array $item     item array
	 * @return int|bool New mail table row id or false on error
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function postMail(array $activity, array $item)
	{
		if (($item['gravity'] != Item::GRAVITY_PARENT) && !DBA::exists('mail', ['uri' => $item['thr-parent'], 'uid' => $item['uid']])) {
			Logger::info('Parent not found, mail will be discarded.', ['uid' => $item['uid'], 'uri' => $item['thr-parent']]);
			return false;
		}

		Logger::info('Direct Message', $item);

		$msg = [];
		$msg['uid'] = $item['uid'];

		$msg['contact-id'] = $item['contact-id'];

		$contact = Contact::getById($item['contact-id'], ['name', 'url', 'photo']);
		$msg['from-name'] = $contact['name'];
		$msg['from-url'] = $contact['url'];
		$msg['from-photo'] = $contact['photo'];

		$msg['uri'] = $item['uri'];
		$msg['created'] = $item['created'];

		$parent = DBA::selectFirst('mail', ['parent-uri', 'title'], ['uri' => $item['thr-parent']]);
		if (DBA::isResult($parent)) {
			$msg['parent-uri'] = $parent['parent-uri'];
			$msg['title'] = $parent['title'];
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
		Logger::info('Fetch featured posts', ['contact' => $url]);

		$apcontact = APContact::getByURL($url);
		if (empty($apcontact['featured'])) {
			Logger::info('Contact does not have a featured collection', ['contact' => $url]);
			return;
		}

		$pcid = Contact::getIdForURL($url, 0, false);
		if (empty($pcid)) {
			Logger::notice('Contact not found', ['contact' => $url]);
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
			Logger::info('Contact does not have featured posts', ['contact' => $url]);

			foreach ($old_featured as $uri_id) {
				Post\Collection::remove($uri_id, Post\Collection::FEATURED);
				Logger::debug('Removed no longer featured post', ['uri-id' => $uri_id, 'contact' => $url]);
			}
			return;
		}

		$new = 0;
		$old = 0;

		foreach ($featured as $post) {
			if (empty($post['id'])) {
				continue;
			}
			$id = Item::fetchByLink($post['id']);
			if (!empty($id)) {
				$item = Post::selectFirst(['uri-id', 'featured', 'author-id'], ['id' => $id]);
				if (!empty($item['uri-id'])) {
					if (!$item['featured']) {
						Post\Collection::add($item['uri-id'], Post\Collection::FEATURED, $item['author-id']);
						Logger::debug('Added featured post', ['uri-id' => $item['uri-id'], 'contact' => $url]);
						$new++;
					} else {
						Logger::debug('Post already had been featured', ['uri-id' => $item['uri-id'], 'contact' => $url]);
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
			Logger::debug('Removed no longer featured post', ['uri-id' => $uri_id, 'contact' => $url]);
		}

		Logger::info('Fetched featured posts', ['new' => $new, 'old' => $old, 'contact' => $url]);
	}

	public static function fetchCachedActivity(string $url, int $uid): array
	{
		$cachekey = self::CACHEKEY_FETCH_ACTIVITY . $uid . ':' . hash('sha256', $url);
		$object = DI::cache()->get($cachekey);

		if (!is_null($object)) {
			if (!empty($object)) {
				Logger::debug('Fetch from cache', ['url' => $url, 'uid' => $uid]);
			} else {
				Logger::debug('Fetch from negative cache', ['url' => $url, 'uid' => $uid]);
			}
			return $object;
		}

		$object = ActivityPub::fetchContent($url, $uid);
		if (empty($object)) {
			Logger::notice('Activity was not fetchable, aborting.', ['url' => $url, 'uid' => $uid]);
			// We perform negative caching.
			DI::cache()->set($cachekey, [], Duration::FIVE_MINUTES);
			return [];
		}

		if (empty($object['id'])) {
			Logger::notice('Activity has got not id, aborting. ', ['url' => $url, 'object' => $object]);
			return [];
		}
		DI::cache()->set($cachekey, $object, Duration::FIVE_MINUTES);

		Logger::debug('Activity was fetched successfully', ['url' => $url, 'uid' => $uid]);

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
	 * @return string fetched message URL
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function fetchMissingActivity(string $url, array $child = [], string $relay_actor = '', int $completion = Receiver::COMPLETION_MANUAL, int $uid = 0): string
	{
		$object = self::fetchCachedActivity($url, $uid);
		if (empty($object)) {
			return '';
		}

		$signer = [];

		if (!empty($object['attributedTo'])) {
			$attributed_to = $object['attributedTo'];
			if (is_array($attributed_to)) {
				$compacted = JsonLD::compact($object);
				$attributed_to = JsonLD::fetchElement($compacted, 'as:attributedTo', '@id');
			}
			$signer[] = $attributed_to;
		}

		if (!empty($object['actor'])) {
			$object_actor = $object['actor'];
		} elseif (!empty($attributed_to)) {
			$object_actor = $attributed_to;
		} else {
			// Shouldn't happen
			$object_actor = '';
		}

		$signer[] = $object_actor;

		if (!empty($child['author'])) {
			$actor = $child['author'];
			$signer[] = $actor;
		} else {
			$actor = $object_actor;
		}

		if (!empty($object['published'])) {
			$published = $object['published'];
		} elseif (!empty($child['published'])) {
			$published = $child['published'];
		} else {
			$published = DateTimeFormat::utcNow();
		}

		$activity = [];
		$activity['@context'] = $object['@context'] ?? ActivityPub::CONTEXT;
		unset($object['@context']);
		$activity['id'] = $object['id'];
		$activity['to'] = $object['to'] ?? [];
		$activity['cc'] = $object['cc'] ?? [];
		$activity['actor'] = $actor;
		$activity['object'] = $object;
		$activity['published'] = $published;
		$activity['type'] = 'Create';

		$ldactivity = JsonLD::compact($activity);

		$ldactivity['recursion-depth'] = !empty($child['recursion-depth']) ? $child['recursion-depth'] + 1 : 0;

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
			if (!self::acceptIncomingMessage($ldactivity, $object['id'])) {
				return '';
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
			Logger::info('Activity has already been queued.', ['url' => $url, 'object' => $activity['id']]);
		} elseif (ActivityPub\Receiver::processActivity($ldactivity, json_encode($activity), $uid, true, false, $signer, '', $completion)) {
			Logger::info('Activity had been fetched and processed.', ['url' => $url, 'entry' => $child['entry-id'] ?? 0, 'completion' => $completion, 'object' => $activity['id']]);
		} else {
			Logger::info('Activity had been fetched and will be processed later.', ['url' => $url, 'entry' => $child['entry-id'] ?? 0, 'completion' => $completion, 'object' => $activity['id']]);
		}

		return $activity['id'];
	}

	/**
	 * Test if incoming relay messages should be accepted
	 *
	 * @param array $activity activity array
	 * @param string $id      object ID
	 * @return boolean true if message is accepted
	 */
	private static function acceptIncomingMessage(array $activity, string $id): bool
	{
		if (empty($activity['as:object'])) {
			Logger::info('No object field in activity - accepted', ['id' => $id]);
			return true;
		}

		$replyto = JsonLD::fetchElement($activity['as:object'], 'as:inReplyTo', '@id');
		$uriid = ItemURI::getIdByURI($replyto ?? '');
		if (Post::exists(['uri-id' => $uriid])) {
			Logger::info('Post is a reply to an existing post - accepted', ['id' => $id, 'uri-id' => $uriid, 'replyto' => $replyto]);
			return true;
		}

		$attributed_to = JsonLD::fetchElement($activity['as:object'], 'as:attributedTo', '@id');
		$authorid = Contact::getIdForURL($attributed_to);

		$body = HTML::toBBCode(JsonLD::fetchElement($activity['as:object'], 'as:content', '@value') ?? '');

		$messageTags = [];
		$tags = Receiver::processTags(JsonLD::fetchElementArray($activity['as:object'], 'as:tag') ?? []);
		if (!empty($tags)) {
			foreach ($tags as $tag) {
				if ($tag['type'] != 'Hashtag') {
					continue;
				}
				$messageTags[] = ltrim(mb_strtolower($tag['name']), '#');
			}
		}

		return Relay::isSolicitedPost($messageTags, $body, $authorid, $id, Protocol::ACTIVITYPUB, $activity['thread-completion'] ?? 0);
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
			'author-id' => Contact::getIdForURL($activity['actor']),
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

		if (empty($contact)) {
			Contact::update(['hub-verify' => $activity['id'], 'protocol' => Protocol::ACTIVITYPUB], ['id' => $cid]);
		}
		Logger::notice('Follow user ' . $uid . ' from contact ' . $cid . ' with id ' . $activity['id']);
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
		$inbox = $account['ap-sharedinbox'] ?: $account['ap-inbox'];

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

		Logger::info('Updating profile', ['object' => $activity['object_id']]);
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
			Logger::info('Empty object id or actor.');
			Queue::remove($activity);
			return;
		}

		if ($activity['object_id'] != $activity['actor']) {
			Logger::info('Object id does not match actor.');
			Queue::remove($activity);
			return;
		}

		$contacts = DBA::select('contact', ['id'], ['nurl' => Strings::normaliseLink($activity['object_id'])]);
		while ($contact = DBA::fetch($contacts)) {
			Contact::remove($contact['id']);
		}
		DBA::close($contacts);

		Logger::info('Deleted contact', ['object' => $activity['object_id']]);
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
			Logger::notice('Object is not the actor', ['activity' => $activity]);
			Queue::remove($activity);
			return;
		}

		$from = Contact::getByURL($activity['object_id'], false, ['uri-id']);
		if (empty($from['uri-id'])) {
			Logger::info('Object not found', ['activity' => $activity]);
			Queue::remove($activity);
			return;
		}

		$contacts = DBA::select('contact', ['uid', 'url'], ["`uri-id` = ? AND `uid` != ? AND `rel` IN (?, ?)", $from['uri-id'], 0, Contact::FRIEND, Contact::SHARING]);
		while ($from_contact = DBA::fetch($contacts)) {
			$result = Contact::createFromProbeForUser($from_contact['uid'], $activity['target_id']);
			Logger::debug('Follower added', ['from' => $from_contact, 'result' => $result]);
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

		Logger::info('Contact blocked user', ['contact' => $cid, 'user' => $uid]);
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

		Logger::info('Contact unblocked user', ['contact' => $cid, 'user' => $uid]);
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
		$account_id = Contact::getIdForURL($activity['object_id']);
		if (empty($account_id)) {
			Logger::info('Unknown account', ['activity' => $activity]);
			Queue::remove($activity);
			return;
		}

		$reporter_id = Contact::getIdForURL($activity['actor']);
		if (empty($reporter_id)) {
			Logger::info('Unknown actor', ['activity' => $activity]);
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

		$report = DI::reportFactory()->createFromReportsRequest($reporter_id, $account_id, $activity['content'], null, '', false, $uri_ids);
		DI::report()->save($report);

		Logger::info('Stored report', ['reporter' => $reporter_id, 'account_id' => $account_id, 'comment' => $activity['content'], 'object_ids' => $activity['object_ids']]);
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
		if (!empty($activity['object_actor'])) {
			$uid      = User::getIdForURL($activity['object_actor']);
			$check_id = false;
		} elseif (!empty($activity['receiver']) && (count($activity['receiver']) == 1)) {
			$uid      = array_shift($activity['receiver']);
			$check_id = true;
		}

		if (empty($uid)) {
			Logger::notice('User could not be detected', ['activity' => $activity]);
			Queue::remove($activity);
			return;
		}

		$cid = Contact::getIdForURL($activity['actor'], $uid);
		if (empty($cid)) {
			Logger::notice('No contact found', ['actor' => $activity['actor']]);
			Queue::remove($activity);
			return;
		}

		$id = Transmitter::activityIDFromContact($cid);
		if ($id == $activity['object_id']) {
			Logger::info('Successful id check', ['uid' => $uid, 'cid' => $cid]);
		} else {
			Logger::info('Unsuccessful id check', ['uid' => $uid, 'cid' => $cid, 'id' => $id, 'object_id' => $activity['object_id']]);
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
		Logger::info('Accept contact request', ['contact' => $cid, 'user' => $uid]);
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
			Logger::info('No contact found', ['actor' => $activity['actor']]);
			return;
		}

		self::switchContact($cid);

		$contact = Contact::getById($cid, ['rel']);
		if ($contact['rel'] == Contact::SHARING) {
			Contact::remove($cid);
			Logger::info('Rejected contact request - contact removed', ['contact' => $cid, 'user' => $uid]);
		} elseif ($contact['rel'] == Contact::FRIEND) {
			Contact::update(['rel' => Contact::FOLLOWER], ['id' => $cid]);
		} else {
			Logger::info('Rejected contact request', ['contact' => $cid, 'user' => $uid]);
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
			Logger::info('No contact found', ['actor' => $activity['actor']]);
			return;
		}

		self::switchContact($cid);

		$contact = DBA::selectFirst('contact', [], ['id' => $cid]);
		if (!DBA::isResult($contact)) {
			return;
		}

		Contact::removeFollower($contact);
		Logger::info('Undo following request', ['contact' => $cid, 'user' => $uid]);
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

		Logger::info('Change existing contact', ['cid' => $cid, 'previous' => $contact['network']]);
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
			Logger::notice('Author public contact unknown.', ['author-link' => $parent['author-link'], 'parent-id' => $parent['id']]);
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
		while(preg_match('#^(@\[url=([^\]]+)].*?\[\/url]\s)(.*)#is', $body, $matches)) {
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
}
