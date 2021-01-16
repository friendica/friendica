<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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

use Friendica\Content\PageInfo;
use Friendica\Content\Text\BBCode;
use Friendica\Content\Text\HTML;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\APContact;
use Friendica\Model\Contact;
use Friendica\Model\Conversation;
use Friendica\Model\Event;
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
	 * Converts mentions from Pleroma into the Friendica format
	 *
	 * @param string $body
	 *
	 * @return string converted body
	 */
	private static function convertMentions($body)
	{
		$URLSearchString = "^\[\]";
		$body = preg_replace("/\[url\=([$URLSearchString]*)\]([#@!])(.*?)\[\/url\]/ism", '$2[url=$1]$3[/url]', $body);

		return $body;
	}

	/**
	 * Replaces emojis in the body
	 *
	 * @param array $emojis
	 * @param string $body
	 *
	 * @return string with replaced emojis
	 */
	private static function replaceEmojis($body, array $emojis)
	{
		$body = strtr($body,
			array_combine(
				array_column($emojis, 'name'),
				array_map(function ($emoji) {
					return '[class=emoji mastodon][img=' . $emoji['href'] . ']' . $emoji['name'] . '[/img][/class]';
				}, $emojis)
			)
		);

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

		$filetype = strtolower(substr($attachment['mediaType'], 0, strpos($attachment['mediaType'], '/')));
		if ($filetype == 'image') {
			$data['type'] = Post\Media::IMAGE;
		} elseif ($filetype == 'video') {
			$data['type'] = Post\Media::VIDEO;
		} elseif ($filetype == 'audio') {
			$data['type'] = Post\Media::AUDIO;
		} elseif (in_array($attachment['mediaType'], ['application/x-bittorrent', 'application/x-bittorrent;x-scheme-handler/magnet'])) {
			$data['type'] = Post\Media::TORRENT;
		} else {
			Logger::info('Unknown type', ['attachment' => $attachment]);
			return;
		}

		$data['url'] = $attachment['url'];
		$data['mimetype'] = $attachment['mediaType'];
		$data['height'] = $attachment['height'] ?? null;
		$data['size'] = $attachment['size'] ?? null;
		$data['preview'] = $attachment['image'] ?? null;
		$data['description'] = $attachment['name'] ?? null;

		Post\Media::insert($data);
	}

	/**
	 * Add attachment data to the item array
	 *
	 * @param array   $activity
	 * @param array   $item
	 *
	 * @return array array
	 */
	private static function constructAttachList($activity, $item)
	{
		if (empty($activity['attachments'])) {
			return $item;
		}

		foreach ($activity['attachments'] as $attach) {
			switch ($attach['type']) {
				case 'link':
					$data = [
						'url'      => $attach['url'],
						'type'     => $attach['type'],
						'title'    => $attach['title'] ?? '',
						'text'     => $attach['desc']  ?? '',
						'image'    => $attach['image'] ?? '',
						'images'   => [],
						'keywords' => [],
					];
					$item['body'] = PageInfo::appendDataToBody($item['body'], $data);
					break;
				default:
					self::storeAttachmentAsMedia($item['uri-id'], $attach);

					$filetype = strtolower(substr($attach['mediaType'], 0, strpos($attach['mediaType'], '/')));
					if ($filetype == 'image') {
						if (!empty($activity['source'])) {
							foreach ([0, 1, 2] as $size) {
								if (preg_match('#/photo/.*-' . $size . '\.#ism', $attach['url']) && 
									strpos(preg_replace('#(/photo/.*)-[012]\.#ism', '$1-' . $size . '.', $activity['source']), $attach['url'])) {
									continue 3;
								}
							}
							if (strpos($activity['source'], $attach['url'])) {
								continue 2;
							}
						}

						$item['body'] .= "\n";

						// image is the preview/thumbnail URL
						if (!empty($attach['image'])) {
							$item['body'] .= '[url=' . $attach['url'] . ']';
							$attach['url'] = $attach['image'];
						}

						if (empty($attach['name'])) {
							$item['body'] .= '[img]' . $attach['url'] . '[/img]';
						} else {
							$item['body'] .= '[img=' . $attach['url'] . ']' . $attach['name'] . '[/img]';
						}

						if (!empty($attach['image'])) {
							$item['body'] .= '[/url]';
						}
					} elseif ($filetype == 'audio') {
						if (!empty($activity['source']) && strpos($activity['source'], $attach['url'])) {
							continue 2;
						}

						$item['body'] .= "\n[audio]" . $attach['url'] . '[/audio]';
					} elseif ($filetype == 'video') {
						if (!empty($activity['source']) && strpos($activity['source'], $attach['url'])) {
							continue 2;
						}

						$item['body'] .= "\n[video]" . $attach['url'] . '[/video]';
					}
			}
		}

		return $item;
	}

	/**
	 * Updates a message
	 *
	 * @param array $activity Activity array
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function updateItem($activity)
	{
		$item = Post::selectFirst(['uri', 'uri-id', 'thr-parent', 'gravity'], ['uri' => $activity['id']]);
		if (!DBA::isResult($item)) {
			Logger::warning('No existing item, item will be created', ['uri' => $activity['id']]);
			$item = self::createItem($activity);
			self::postItem($activity, $item);
			return;
		}

		$item['changed'] = DateTimeFormat::utcNow();
		$item['edited'] = DateTimeFormat::utc($activity['updated']);

		$item = self::processContent($activity, $item);

		$item = self::constructAttachList($activity, $item);

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

		$item['created'] = DateTimeFormat::utc($activity['published']);
		$item['edited'] = DateTimeFormat::utc($activity['updated']);
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

		$item = self::constructAttachList($activity, $item);

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
			$item = Post::selectFirst(['id', 'uri-id', 'tag', 'origin', 'author-link'], ['uri' => $activity['target_id'], 'uid' => $receiver]);
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
		$item['object-type'] = Activity\ObjectType::NOTE;

		$item['diaspora_signed_text'] = $activity['diaspora:like'] ?? '';

		self::postItem($activity, $item);
	}

	/**
	 * Create an event
	 *
	 * @param array $activity Activity array
	 * @param array $item
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
		$event['adjust']    = true;
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

		$condition = ['uri' => $item['uri'], 'uid' => $item['uid']];
		$ev = DBA::selectFirst('event', ['id'], $condition);
		if (DBA::isResult($ev)) {
			$event['id'] = $ev['id'];
		}

		$event_id = Event::store($event);
		Logger::info('Event was stored', ['id' => $event_id]);
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
		$item['title'] = HTML::toBBCode($activity['name']);

		$content = HTML::toBBCode($activity['content']);

		if (!empty($activity['emojis'])) {
			$content = self::replaceEmojis($content, $activity['emojis']);
		}

		$content = self::convertMentions($content);

		if (!empty($activity['source'])) {
			$item['body'] = $activity['source'];
			$item['raw-body'] = $content;
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
					$item['post-type'] = Item::PT_TO;
					break;
				case Receiver::TARGET_CC:
					$item['post-type'] = Item::PT_CC;
					break;
				case Receiver::TARGET_BTO:
					$item['post-type'] = Item::PT_BTO;
					break;
				case Receiver::TARGET_BCC:
					$item['post-type'] = Item::PT_BCC;
					break;
				case Receiver::TARGET_FOLLOWER:
					$item['post-type'] = Item::PT_FOLLOWER;
					break;
				case Receiver::TARGET_ANSWER:
					$item['post-type'] = Item::PT_COMMENT;
					break;
				case Receiver::TARGET_GLOBAL:
					$item['post-type'] = Item::PT_GLOBAL;
					break;
				default:
					$item['post-type'] = Item::PT_ARTICLE;
			}

			if (!empty($activity['from-relay'])) {
				$item['post-type'] = Item::PT_RELAY;
			} elseif (!empty($activity['thread-completion'])) {
				$item['post-type'] = Item::PT_FETCHED;
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
				self::createEvent($activity, $item);
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

				$title = trim(HTML::toPlaintext(BBCode::convert($title, false, BBCode::API, true), 0));

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
			Logger::log('Activity ' . $url . ' was not fetchable, aborting.');
			return '';
		}

		if (empty($object['id'])) {
			Logger::log('Activity ' . $url . ' has got not id, aborting. ' . json_encode($object));
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
		if (Post::exists(['uri' => $replyto])) {
			Logger::info('Post is a reply to an existing post - accepted', ['id' => $id, 'replyto' => $replyto]);
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
			DBA::update('contact', ['hub-verify' => $activity['id'], 'protocol' => Protocol::ACTIVITYPUB], ['id' => $cid]);
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
			DBA::update('contact', ['hub-verify' => $activity['id'], 'protocol' => Protocol::ACTIVITYPUB], ['id' => $cid]);
		}

		Logger::log('Follow user ' . $uid . ' from contact ' . $cid . ' with id ' . $activity['id']);
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
		DBA::update('contact', $fields, $condition);
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

		if (DBA::exists('contact', ['id' => $cid, 'rel' => Contact::SHARING])) {
			Contact::remove($cid);
			Logger::info('Rejected contact request - contact removed', ['contact' => $cid, 'user' => $uid]);
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

		Contact::removeFollower($owner, $contact);
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
			Logger::notice('Author public contact unknown.', ['author-link' => $parent['author-link'], 'item-id' => $parent['id']]);
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
}
