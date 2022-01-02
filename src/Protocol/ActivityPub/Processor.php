<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\System;
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
use Friendica\Protocol\Relay;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\JsonLD;
use Friendica\Util\Strings;

/**
 * ActivityPub Processor Protocol class
 */
class Processor
{
	/**
	 * Extracts the tag character (#, @, !) from mention links
	 *
	 * @param string $body
	 * @return string
	 */
	protected static function normalizeMentionLinks(string $body): string
	{
		return preg_replace('%\[url=([^\[\]]*)]([#@!])(.*?)\[/url]%ism', '$2[url=$1]$3[/url]', $body);
	}

	/**
	 * Convert the language array into a language JSON
	 *
	 * @param array $languages
	 * @return string language JSON
	 */
	private static function processLanguages(array $languages)
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
	 * @param array $emojis
	 * @param string $body
	 *
	 * @return string with replaced emojis
	 */
	private static function replaceEmojis(int $uri_id, $body, array $emojis)
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
		$data['mimetype'] = $attachment['mediaType'];
		$data['height'] = $attachment['height'] ?? null;
		$data['width'] = $attachment['width'] ?? null;
		$data['size'] = $attachment['size'] ?? null;
		$data['preview'] = $attachment['image'] ?? null;
		$data['description'] = $attachment['name'] ?? null;

		Post\Media::insert($data);
	}

	/**
	 * Stire attachment data
	 *
	 * @param array   $activity
	 * @param array   $item
	 */
	private static function storeAttachments($activity, $item)
	{
		if (empty($activity['attachments'])) {
			return;
		}

		foreach ($activity['attachments'] as $attach) {
			self::storeAttachmentAsMedia($item['uri-id'], $attach);
		}
	}

	/**
	 * Updates a message
	 *
	 * @param array $activity Activity array
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function updateItem($activity)
	{
		$item = Post::selectFirst(['uri', 'uri-id', 'thr-parent', 'gravity', 'post-type'], ['uri' => $activity['id']]);
		if (!DBA::isResult($item)) {
			Logger::warning('No existing item, item will be created', ['uri' => $activity['id']]);
			$item = self::createItem($activity);
			self::postItem($activity, $item);
			return;
		}

		$item['changed'] = DateTimeFormat::utcNow();
		$item['edited'] = DateTimeFormat::utc($activity['updated']);

		$item = self::processContent($activity, $item);

		self::storeAttachments($activity, $item);

		if (empty($item)) {
			return;
		}

		Item::update($item, ['uri' => $activity['id']]);
	}

	/**
	 * Prepares data for a message
	 *
	 * @param array $activity Activity array
	 * @return array Internal item
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function createItem($activity)
	{
		$item = [];
		$item['verb'] = Activity::POST;
		$item['thr-parent'] = $activity['reply-to-id'];

		if ($activity['reply-to-id'] == $activity['id']) {
			$item['gravity'] = GRAVITY_PARENT;
			$item['object-type'] = Activity\ObjectType::NOTE;
		} else {
			$item['gravity'] = GRAVITY_COMMENT;
			$item['object-type'] = Activity\ObjectType::COMMENT;
		}

		if (empty($activity['directmessage']) && ($activity['id'] != $activity['reply-to-id']) && !Post::exists(['uri' => $activity['reply-to-id']])) {
			Logger::notice('Parent not found. Try to refetch it.', ['parent' => $activity['reply-to-id']]);
			self::fetchMissingActivity($activity['reply-to-id'], $activity);
		}

		$item['diaspora_signed_text'] = $activity['diaspora:comment'] ?? '';

		/// @todo What to do with $activity['context']?
		if (empty($activity['directmessage']) && ($item['gravity'] != GRAVITY_PARENT) && !Post::exists(['uri' => $item['thr-parent']])) {
			Logger::info('Parent not found, message will be discarded.', ['thr-parent' => $item['thr-parent']]);
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
			$item['protocol'] = Conversation::PARCEL_ACTIVITYPUB;
			$item['conversation-href'] = $activity['context'] ?? '';
			$item['conversation-uri'] = $activity['conversation'] ?? '';

			if (isset($activity['push'])) {
				$item['direction'] = $activity['push'] ? Conversation::PUSH : Conversation::PULL;
			}
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
				$item['causer-id'] = $item['owner-id'];
				Logger::info('Use actor as causer.', ['id' => $item['owner-id'], 'actor' => $item['owner-link']]);
			}

			$item['owner-link'] = $item['author-link'];
			$item['owner-id'] = $item['author-id'];
		} else {
			$actor = APContact::getByURL($item['owner-link'], false);
			$item['isForum'] = ($actor['type'] == 'Group');
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

		$item = self::processContent($activity, $item);
		if (empty($item)) {
			Logger::info('Message was not processed');
			return [];
		}

		$item['plink'] = $activity['alternate-url'] ?? $item['uri'];

		self::storeAttachments($activity, $item);

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
	 * Delete items
	 *
	 * @param array $activity
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function deleteItem($activity)
	{
		$owner = Contact::getIdForURL($activity['actor']);

		Logger::info('Deleting item', ['object' => $activity['object_id'], 'owner'  => $owner]);
		Item::markForDeletion(['uri' => $activity['object_id'], 'owner-id' => $owner]);
	}

	/**
	 * Prepare the item array for an activity
	 *
	 * @param array $activity Activity array
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function addTag($activity)
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
	 * @param array  $activity Activity array
	 * @param string $verb     Activity verb
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function createActivity($activity, $verb)
	{
		$item = self::createItem($activity);
		$item['verb'] = $verb;
		$item['thr-parent'] = $activity['object_id'];
		$item['gravity'] = GRAVITY_ACTIVITY;
		unset($item['post-type']);
		$item['object-type'] = Activity\ObjectType::NOTE;

		$item['diaspora_signed_text'] = $activity['diaspora:like'] ?? '';

		self::postItem($activity, $item);
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
	public static function createEvent($activity, $item)
	{
		$event['summary']   = HTML::toBBCode($activity['name']);
		$event['desc']      = HTML::toBBCode($activity['content']);
		$event['start']     = $activity['start-time'];
		$event['finish']    = $activity['end-time'];
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
	private static function processContent($activity, $item)
	{
		if (!empty($activity['mediatype']) && ($activity['mediatype'] == 'text/markdown')) {
			$item['title'] = Markdown::toBBCode($activity['name']);
			$content = Markdown::toBBCode($activity['content']);
		} elseif (!empty($activity['mediatype']) && ($activity['mediatype'] == 'text/bbcode')) {
			$item['title'] = $activity['name'];
			$content = $activity['content'];
		} else {
			// By default assume "text/html"
			$item['title'] = HTML::toBBCode($activity['name']);
			$content = HTML::toBBCode($activity['content']);
		}

		if (!empty($activity['languages'])) {
			$item['language'] = self::processLanguages($activity['languages']);
		}

		if (!empty($activity['emojis'])) {
			$content = self::replaceEmojis($item['uri-id'], $content, $activity['emojis']);
		}

		$content = self::addMentionLinks($content, $activity['tags']);

		if (!empty($activity['source'])) {
			$item['body'] = $activity['source'];
			$item['raw-body'] = $content;
			$item['body'] = Item::improveSharedDataInBody($item);
		} else {
			if (empty($activity['directmessage']) && ($item['thr-parent'] != $item['uri']) && ($item['gravity'] == GRAVITY_COMMENT)) {
				$item_private = !in_array(0, $activity['item_receiver']);
				$parent = Post::selectFirst(['id', 'uri-id', 'private', 'author-link', 'alias'], ['uri' => $item['thr-parent']]);
				if (!DBA::isResult($parent)) {
					Logger::warning('Unknown parent item.', ['uri' => $item['thr-parent']]);
					return false;
				}
				if ($item_private && ($parent['private'] != Item::PRIVATE)) {
					Logger::warning('Item is private but the parent is not. Dropping.', ['item-uri' => $item['uri'], 'thr-parent' => $item['thr-parent']]);
					return false;
				}

				$content = self::removeImplicitMentionsFromBody($content, $parent);
			}
			$item['content-warning'] = HTML::toBBCode($activity['summary']);
			$item['raw-body'] = $item['body'] = $content;
		}

		self::storeFromBody($item);
		self::storeTags($item['uri-id'], $activity['tags']);

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
	 * Generate a GUID out of an URL
	 *
	 * @param string $url message URL
	 * @return string with GUID
	 */
	private static function getGUIDByURL(string $url)
	{
		$parsed = parse_url($url);

		$host_hash = hash('crc32', $parsed['host']);

		unset($parsed["scheme"]);
		unset($parsed["host"]);

		$path = implode("/", $parsed);

		return $host_hash . '-'. hash('fnv164', $path) . '-'. hash('joaat', $path);
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
		ksort($activity['receiver']);

		foreach ($activity['receiver'] as $receiver) {
			if ($receiver == -1) {
				continue;
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

			if (!empty($activity['from-relay'])) {
				$item['post-reason'] = Item::PR_RELAY;
			} elseif (!empty($activity['thread-completion'])) {
				$item['post-reason'] = Item::PR_FETCHED;
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

			if (!($item['isForum'] ?? false) && ($receiver != 0) && ($item['gravity'] == GRAVITY_PARENT) &&
				($item['post-reason'] == Item::PR_BCC) && !Contact::isSharingByURL($activity['author'], $receiver)) {
				Logger::info('Top level post via BCC from a non sharer, ignoring', ['uid' => $receiver, 'contact' => $item['contact-id']]);
				continue;
			}

			if (DI::pConfig()->get($receiver, 'system', 'accept_only_sharer', false) && ($receiver != 0) && ($item['gravity'] == GRAVITY_PARENT)) {
				$skip = !Contact::isSharingByURL($activity['author'], $receiver);

				if ($skip && (($activity['type'] == 'as:Announce') || ($item['isForum'] ?? false))) {
					$skip = !Contact::isSharingByURL($activity['actor'], $receiver);
				}

				if ($skip) {
					Logger::info('Skipping post', ['uid' => $receiver, 'url' => $item['uri']]);
					continue;
				}

				Logger::info('Accepting post', ['uid' => $receiver, 'url' => $item['uri']]);
			}

			if (($item['gravity'] != GRAVITY_ACTIVITY) && ($activity['object_type'] == 'as:Event')) {
				$event_id = self::createEvent($activity, $item);

				$item = Event::getItemArrayForImportedId($event_id, $item);
			}

			$item_id = Item::insert($item);
			if ($item_id) {
				Logger::info('Item insertion successful', ['user' => $item['uid'], 'item_id' => $item_id]);
			} else {
				Logger::notice('Item insertion aborted', ['user' => $item['uid']]);
			}

			if ($item['uid'] == 0) {
				$stored = $item_id;
			}
		}

		// Store send a follow request for every reshare - but only when the item had been stored
		if ($stored && ($item['private'] != Item::PRIVATE) && ($item['gravity'] == GRAVITY_PARENT) && ($item['author-link'] != $item['owner-link'])) {
			$author = APContact::getByURL($item['owner-link'], false);
			// We send automatic follow requests for reshared messages. (We don't need though for forum posts)
			if ($author['type'] != 'Group') {
				Logger::info('Send follow request', ['uri' => $item['uri'], 'stored' => $stored, 'to' => $item['author-link']]);
				ActivityPub\Transmitter::sendFollowObject($item['uri'], $item['author-link']);
			}
		}
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

	/**
	 * Creates an mail post
	 *
	 * @param array $activity Activity data
	 * @param array $item     item array
	 * @return int|bool New mail table row id or false on error
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function postMail($activity, $item)
	{
		if (($item['gravity'] != GRAVITY_PARENT) && !DBA::exists('mail', ['uri' => $item['thr-parent'], 'uid' => $item['uid']])) {
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
	 * Fetches missing posts
	 *
	 * @param string $url         message URL
	 * @param array  $child       activity array with the child of this message
	 * @param string $relay_actor Relay actor
	 * @return string fetched message URL
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function fetchMissingActivity(string $url, array $child = [], string $relay_actor = '')
	{
		if (!empty($child['receiver'])) {
			$uid = ActivityPub\Receiver::getFirstUserFromReceivers($child['receiver']);
		} else {
			$uid = 0;
		}

		$object = ActivityPub::fetchContent($url, $uid);
		if (empty($object)) {
			Logger::notice('Activity was not fetchable, aborting.', ['url' => $url]);
			return '';
		}

		if (empty($object['id'])) {
			Logger::notice('Activity has got not id, aborting. ', ['url' => $url, 'object' => $object]);
			return '';
		}

		if (!empty($object['actor'])) {
			$object_actor = $object['actor'];
		} elseif (!empty($object['attributedTo'])) {
			$object_actor = $object['attributedTo'];
			if (is_array($object_actor)) {
				$compacted = JsonLD::compact($object);
				$object_actor = JsonLD::fetchElement($compacted, 'as:attributedTo', '@id');
			}
		} else {
			// Shouldn't happen
			$object_actor = '';
		}

		$signer = [$object_actor];

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

		if (!empty($relay_actor)) {
			$ldactivity['thread-completion'] = $ldactivity['from-relay'] = Contact::getIdForURL($relay_actor);
		} elseif (!empty($child['thread-completion'])) {
			$ldactivity['thread-completion'] = $child['thread-completion'];
		} else {
			$ldactivity['thread-completion'] = Contact::getIdForURL($actor);
		}

		if (!empty($relay_actor) && !self::acceptIncomingMessage($ldactivity, $object['id'])) {
			return '';
		}

		ActivityPub\Receiver::processActivity($ldactivity, json_encode($activity), $uid, true, false, $signer);

		Logger::notice('Activity had been fetched and processed.', ['url' => $url, 'object' => $activity['id']]);

		return $activity['id'];
	}

	/**
	 * Test if incoming relay messages should be accepted
	 *
	 * @param array $activity activity array
	 * @param string $id      object ID
	 * @return boolean true if message is accepted
	 */
	private static function acceptIncomingMessage(array $activity, string $id)
	{
		if (empty($activity['as:object'])) {
			Logger::info('No object field in activity - accepted', ['id' => $id]);
			return true;
		}

		$replyto = JsonLD::fetchElement($activity['as:object'], 'as:inReplyTo', '@id');
		$uriid = ItemURI::getIdByURI($replyto);
		if (Post::exists(['uri-id' => $uriid])) {
			Logger::info('Post is a reply to an existing post - accepted', ['id' => $id, 'uri-id' => $uriid, 'replyto' => $replyto]);
			return true;
		}

		$attributed_to = JsonLD::fetchElement($activity['as:object'], 'as:attributedTo', '@id');
		$authorid = Contact::getIdForURL($attributed_to);

		$body = HTML::toBBCode(JsonLD::fetchElement($activity['as:object'], 'as:content', '@value'));

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

		return Relay::isSolicitedPost($messageTags, $body, $authorid, $id, Protocol::ACTIVITYPUB);
	}

	/**
	 * perform a "follow" request
	 *
	 * @param array $activity
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function followUser($activity)
	{
		$uid = User::getIdForURL($activity['object_id']);
		if (empty($uid)) {
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

		$item = ['author-id' => Contact::getIdForURL($activity['actor']),
			'author-link' => $activity['actor']];

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

		if (empty($contact)) {
			Contact::update(['hub-verify' => $activity['id'], 'protocol' => Protocol::ACTIVITYPUB], ['id' => $cid]);
		}

		Logger::notice('Follow user ' . $uid . ' from contact ' . $cid . ' with id ' . $activity['id']);
	}

	/**
	 * Update the given profile
	 *
	 * @param array $activity
	 * @throws \Exception
	 */
	public static function updatePerson($activity)
	{
		if (empty($activity['object_id'])) {
			return;
		}

		Logger::info('Updating profile', ['object' => $activity['object_id']]);
		Contact::updateFromProbeByURL($activity['object_id']);
	}

	/**
	 * Delete the given profile
	 *
	 * @param array $activity
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function deletePerson($activity)
	{
		if (empty($activity['object_id']) || empty($activity['actor'])) {
			Logger::info('Empty object id or actor.');
			return;
		}

		if ($activity['object_id'] != $activity['actor']) {
			Logger::info('Object id does not match actor.');
			return;
		}

		$contacts = DBA::select('contact', ['id'], ['nurl' => Strings::normaliseLink($activity['object_id'])]);
		while ($contact = DBA::fetch($contacts)) {
			Contact::remove($contact['id']);
		}
		DBA::close($contacts);

		Logger::info('Deleted contact', ['object' => $activity['object_id']]);
	}

	/**
	 * Accept a follow request
	 *
	 * @param array $activity
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function acceptFollowUser($activity)
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

		$fields = ['pending' => false];

		$contact = DBA::selectFirst('contact', ['rel'], ['id' => $cid]);
		if ($contact['rel'] == Contact::FOLLOWER) {
			$fields['rel'] = Contact::FRIEND;
		}

		$condition = ['id' => $cid];
		Contact::update($fields, $condition);
		Logger::info('Accept contact request', ['contact' => $cid, 'user' => $uid]);
	}

	/**
	 * Reject a follow request
	 *
	 * @param array $activity
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function rejectFollowUser($activity)
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
	}

	/**
	 * Undo activity like "like" or "dislike"
	 *
	 * @param array $activity
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function undoActivity($activity)
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

		Item::markForDeletion(['uri' => $activity['object_id'], 'author-id' => $author_id, 'gravity' => GRAVITY_ACTIVITY]);
	}

	/**
	 * Activity to remove a follower
	 *
	 * @param array $activity
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function undoFollowUser($activity)
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
	}

	/**
	 * Switches a contact to AP if needed
	 *
	 * @param integer $cid Contact ID
	 * @throws \Exception
	 */
	private static function switchContact($cid)
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
	private static function getImplicitMentionList(array $parent)
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
	private static function removeImplicitMentionsFromBody(string $body, array $parent)
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

				$body = str_replace($tag['name'], $hash . '[url=' . $tag['href'] . ']' . $name . '[/url]', $body);
			}

			return $body;
		});

		return $body;
	}
}
