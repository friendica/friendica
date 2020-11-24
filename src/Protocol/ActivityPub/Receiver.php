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

use Friendica\Content\Text\BBCode;
use Friendica\Database\DBA;
use Friendica\Content\Text\HTML;
use Friendica\Content\Text\Markdown;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Model\Contact;
use Friendica\Model\APContact;
use Friendica\Model\Item;
use Friendica\Model\User;
use Friendica\Protocol\Activity;
use Friendica\Protocol\ActivityPub;
use Friendica\Util\HTTPSignature;
use Friendica\Util\JsonLD;
use Friendica\Util\LDSignature;
use Friendica\Util\Strings;

/**
 * ActivityPub Receiver Protocol class
 *
 * To-Do:
 * @todo Undo Announce
 *
 * Check what this is meant to do:
 * - Add
 * - Block
 * - Flag
 * - Remove
 * - Undo Block
 */
class Receiver
{
	const PUBLIC_COLLECTION = 'as:Public';
	const ACCOUNT_TYPES = ['as:Person', 'as:Organization', 'as:Service', 'as:Group', 'as:Application'];
	const CONTENT_TYPES = ['as:Note', 'as:Article', 'as:Video', 'as:Image', 'as:Event', 'as:Audio'];
	const ACTIVITY_TYPES = ['as:Like', 'as:Dislike', 'as:Accept', 'as:Reject', 'as:TentativeAccept'];

	const TARGET_UNKNOWN = 0;
	const TARGET_TO = 1;
	const TARGET_CC = 2;
	const TARGET_BTO = 3;
	const TARGET_BCC = 4;
	const TARGET_FOLLOWER = 5;
	const TARGET_ANSWER = 6;
	const TARGET_GLOBAL = 7;

	/**
	 * Checks if the web request is done for the AP protocol
	 *
	 * @return bool is it AP?
	 */
	public static function isRequest()
	{
		return stristr($_SERVER['HTTP_ACCEPT'] ?? '', 'application/activity+json') ||
			stristr($_SERVER['HTTP_ACCEPT'] ?? '', 'application/ld+json');
	}

	/**
	 * Checks incoming message from the inbox
	 *
	 * @param         $body
	 * @param         $header
	 * @param integer $uid User ID
	 * @throws \Exception
	 */
	public static function processInbox($body, $header, $uid)
	{
		$activity = json_decode($body, true);
		if (empty($activity)) {
			Logger::warning('Invalid body.');
			return;
		}

		$ldactivity = JsonLD::compact($activity);

		$actor = JsonLD::fetchElement($ldactivity, 'as:actor', '@id');

		$apcontact = APContact::getByURL($actor);
		if (!empty($apcontact) && ($apcontact['type'] == 'Application') && ($apcontact['nick'] == 'relay')) {
			self::processRelayPost($ldactivity, $actor);
			return;
		}

		if (!empty($apcontact)) {
			APContact::unMarkForArchival($apcontact);
		}

		$http_signer = HTTPSignature::getSigner($body, $header);
		if (empty($http_signer)) {
			Logger::warning('Invalid HTTP signature, message will be discarded.');
			return;
		} else {
			Logger::info('Valid HTTP signature', ['signer' => $http_signer]);
		}

		$signer = [$http_signer];

		Logger::info('Message for user ' . $uid . ' is from actor ' . $actor);

		if (LDSignature::isSigned($activity)) {
			$ld_signer = LDSignature::getSigner($activity);
			if (empty($ld_signer)) {
				Logger::log('Invalid JSON-LD signature from ' . $actor, Logger::DEBUG);
			} elseif ($ld_signer != $http_signer) {
				$signer[] = $ld_signer;
			}
			if (!empty($ld_signer && ($actor == $http_signer))) {
				Logger::log('The HTTP and the JSON-LD signature belong to ' . $ld_signer, Logger::DEBUG);
				$trust_source = true;
			} elseif (!empty($ld_signer)) {
				Logger::log('JSON-LD signature is signed by ' . $ld_signer, Logger::DEBUG);
				$trust_source = true;
			} elseif ($actor == $http_signer) {
				Logger::log('Bad JSON-LD signature, but HTTP signer fits the actor.', Logger::DEBUG);
				$trust_source = true;
			} else {
				Logger::log('Invalid JSON-LD signature and the HTTP signer is different.', Logger::DEBUG);
				$trust_source = false;
			}
		} elseif ($actor == $http_signer) {
			Logger::log('Trusting post without JSON-LD signature, The actor fits the HTTP signer.', Logger::DEBUG);
			$trust_source = true;
		} else {
			Logger::log('No JSON-LD signature, different actor.', Logger::DEBUG);
			$trust_source = false;
		}

		self::processActivity($ldactivity, $body, $uid, $trust_source, true, $signer);
	}

	/**
	 * Process incoming posts from relays
	 *
	 * @param array  $activity
	 * @param string $actor
	 * @return void
	 */
	private static function processRelayPost(array $activity, string $actor)
	{
		$type = JsonLD::fetchElement($activity, '@type');
		if (!$type) {
			Logger::info('Empty type', ['activity' => $activity]);
			return;
		}

		if ($type != 'as:Announce') {
			Logger::info('Not an announcement', ['activity' => $activity]);
			return;
		}

		$object_id = JsonLD::fetchElement($activity, 'as:object', '@id');
		if (empty($object_id)) {
			Logger::info('No object id found', ['activity' => $activity]);
			return;
		}

		$contact = Contact::getByURL($actor);
		if (empty($contact)) {
			Logger::info('Relay contact not found', ['actor' => $actor]);
			return;
		}

		if (!in_array($contact['rel'], [Contact::SHARING, Contact::FRIEND])) {
			Logger::notice('Relay is no sharer', ['actor' => $actor]);
			return;
		}

		Logger::info('Got relayed message id', ['id' => $object_id]);

		$item_id = Item::searchByLink($object_id);
		if ($item_id) {
			Logger::info('Relayed message already exists', ['id' => $object_id, 'item' => $item_id]);
			return;
		}

		$id = Processor::fetchMissingActivity($object_id, [], $actor);
		if (empty($id)) {
			Logger::notice('Relayed message had not been fetched', ['id' => $object_id]);
			return;
		}

		$item_id = Item::searchByLink($object_id);
		if ($item_id) {
			Logger::info('Relayed message had been fetched and stored', ['id' => $object_id, 'item' => $item_id]);
		} else {
			Logger::notice('Relayed message had not been stored', ['id' => $object_id]);
		}
	}

	/**
	 * Fetches the object type for a given object id
	 *
	 * @param array   $activity
	 * @param string  $object_id Object ID of the the provided object
	 * @param integer $uid       User ID
	 *
	 * @return string with object type
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function fetchObjectType($activity, $object_id, $uid = 0)
	{
		if (!empty($activity['as:object'])) {
			$object_type = JsonLD::fetchElement($activity['as:object'], '@type');
			if (!empty($object_type)) {
				return $object_type;
			}
		}

		if (Item::exists(['uri' => $object_id, 'gravity' => [GRAVITY_PARENT, GRAVITY_COMMENT]])) {
			// We just assume "note" since it doesn't make a difference for the further processing
			return 'as:Note';
		}

		$profile = APContact::getByURL($object_id);
		if (!empty($profile['type'])) {
			APContact::unMarkForArchival($profile);
			return 'as:' . $profile['type'];
		}

		$data = ActivityPub::fetchContent($object_id, $uid);
		if (!empty($data)) {
			$object = JsonLD::compact($data);
			$type = JsonLD::fetchElement($object, '@type');
			if (!empty($type)) {
				return $type;
			}
		}

		return null;
	}

	/**
	 * Prepare the object array
	 *
	 * @param array   $activity     Array with activity data
	 * @param integer $uid          User ID
	 * @param boolean $push         Message had been pushed to our system
	 * @param boolean $trust_source Do we trust the source?
	 *
	 * @return array with object data
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function prepareObjectData($activity, $uid, $push, &$trust_source)
	{
		$id = JsonLD::fetchElement($activity, '@id');
		if (!empty($id) && !$trust_source) {
			$fetched_activity = ActivityPub::fetchContent($id, $uid ?? 0);
			if (!empty($fetched_activity)) {
				$object = JsonLD::compact($fetched_activity);
				$fetched_id = JsonLD::fetchElement($object, '@id');
				if ($fetched_id == $id) {
					Logger::info('Activity had been fetched successfully', ['id' => $id]);
					$trust_source = true;
					$activity = $object;
				} else {
					Logger::info('Activity id is not equal', ['id' => $id, 'fetched' => $fetched_id]);
				}
			} else {
				Logger::info('Activity could not been fetched', ['id' => $id]);
			}
		}

		$actor = JsonLD::fetchElement($activity, 'as:actor', '@id');
		if (empty($actor)) {
			Logger::info('Empty actor', ['activity' => $activity]);
			return [];
		}

		$type = JsonLD::fetchElement($activity, '@type');

		// Fetch all receivers from to, cc, bto and bcc
		$receiverdata = self::getReceivers($activity, $actor);
		$receivers = $reception_types = [];
		foreach ($receiverdata as $key => $data) {
			$receivers[$key] = $data['uid'];
			$reception_types[$data['uid']] = $data['type'] ?? self::TARGET_UNKNOWN;
		}

		// When it is a delivery to a personal inbox we add that user to the receivers
		if (!empty($uid)) {
			$additional = [$uid => $uid];
			$receivers = array_replace($receivers, $additional);
			if (empty($activity['thread-completion']) && (empty($reception_types[$uid]) || in_array($reception_types[$uid], [self::TARGET_UNKNOWN, self::TARGET_FOLLOWER, self::TARGET_ANSWER, self::TARGET_GLOBAL]))) {
				$reception_types[$uid] = self::TARGET_BCC;
			}
		} else {
			// We possibly need some user to fetch private content,
			// so we fetch the first out ot the list.
			$uid = self::getFirstUserFromReceivers($receivers);
		}

		$object_id = JsonLD::fetchElement($activity, 'as:object', '@id');
		if (empty($object_id)) {
			Logger::log('No object found', Logger::DEBUG);
			return [];
		}

		if (!is_string($object_id)) {
			Logger::info('Invalid object id', ['object' => $object_id]);
			return [];
		}

		$object_type = self::fetchObjectType($activity, $object_id, $uid);

		// Fetch the content only on activities where this matters
		if (in_array($type, ['as:Create', 'as:Update', 'as:Announce'])) {
			// Always fetch on "Announce"
			$object_data = self::fetchObject($object_id, $activity['as:object'], $trust_source && ($type != 'as:Announce'), $uid);
			if (empty($object_data)) {
				Logger::log("Object data couldn't be processed", Logger::DEBUG);
				return [];
			}

			$object_data['object_id'] = $object_id;

			if ($type == 'as:Announce') {
				$object_data['push'] = false;
			} else {
				$object_data['push'] = $push;
			}

			// Test if it is an answer to a mail
			if (DBA::exists('mail', ['uri' => $object_data['reply-to-id']])) {
				$object_data['directmessage'] = true;
			} else {
				$object_data['directmessage'] = JsonLD::fetchElement($activity, 'litepub:directMessage');
			}
		} elseif (in_array($type, array_merge(self::ACTIVITY_TYPES, ['as:Follow'])) && in_array($object_type, self::CONTENT_TYPES)) {
			// Create a mostly empty array out of the activity data (instead of the object).
			// This way we later don't have to check for the existence of ech individual array element.
			$object_data = self::processObject($activity);
			$object_data['name'] = $type;
			$object_data['author'] = JsonLD::fetchElement($activity, 'as:actor', '@id');
			$object_data['object_id'] = $object_id;
			$object_data['object_type'] = ''; // Since we don't fetch the object, we don't know the type
		} elseif (in_array($type, ['as:Add'])) {
			$object_data = [];
			$object_data['id'] = JsonLD::fetchElement($activity, '@id');
			$object_data['target_id'] = JsonLD::fetchElement($activity, 'as:target', '@id');
			$object_data['object_id'] = JsonLD::fetchElement($activity, 'as:object', '@id');
			$object_data['object_type'] = JsonLD::fetchElement($activity['as:object'], '@type');
			$object_data['object_content'] = JsonLD::fetchElement($activity['as:object'], 'as:content', '@type');
		} else {
			$object_data = [];
			$object_data['id'] = JsonLD::fetchElement($activity, '@id');
			$object_data['object_id'] = JsonLD::fetchElement($activity, 'as:object', '@id');
			$object_data['object_actor'] = JsonLD::fetchElement($activity['as:object'], 'as:actor', '@id');
			$object_data['object_object'] = JsonLD::fetchElement($activity['as:object'], 'as:object');
			$object_data['object_type'] = JsonLD::fetchElement($activity['as:object'], '@type');

			// An Undo is done on the object of an object, so we need that type as well
			if (($type == 'as:Undo') && !empty($object_data['object_object'])) {
				$object_data['object_object_type'] = self::fetchObjectType([], $object_data['object_object'], $uid);
			}
		}

		$object_data = self::addActivityFields($object_data, $activity);

		if (empty($object_data['object_type'])) {
			$object_data['object_type'] = $object_type;
		}

		$object_data['type'] = $type;
		$object_data['actor'] = $actor;
		$object_data['item_receiver'] = $receivers;
		$object_data['receiver'] = array_replace($object_data['receiver'] ?? [], $receivers);
		$object_data['reception_type'] = array_replace($object_data['reception_type'] ?? [], $reception_types);

		$author = $object_data['author'] ?? $actor;
		if (!empty($author) && !empty($object_data['id'])) {
			$author_host = parse_url($author, PHP_URL_HOST);
			$id_host = parse_url($object_data['id'], PHP_URL_HOST);
			if ($author_host == $id_host) {
				Logger::info('Valid hosts', ['type' => $type, 'host' => $id_host]);
			} else {
				Logger::notice('Differing hosts on author and id', ['type' => $type, 'author' => $author_host, 'id' => $id_host]);
				$trust_source = false;
			}
		}

		Logger::log('Processing ' . $object_data['type'] . ' ' . $object_data['object_type'] . ' ' . $object_data['id'], Logger::DEBUG);

		return $object_data;
	}

	/**
	 * Fetches the first user id from the receiver array
	 *
	 * @param array $receivers Array with receivers
	 * @return integer user id;
	 */
	public static function getFirstUserFromReceivers($receivers)
	{
		foreach ($receivers as $receiver) {
			if (!empty($receiver)) {
				return $receiver;
			}
		}
		return 0;
	}

	/**
	 * Processes the activity object
	 *
	 * @param array   $activity     Array with activity data
	 * @param string  $body
	 * @param integer $uid          User ID
	 * @param boolean $trust_source Do we trust the source?
	 * @param boolean $push         Message had been pushed to our system
	 * @throws \Exception
	 */
	public static function processActivity($activity, string $body = '', int $uid = null, bool $trust_source = false, bool $push = false, array $signer = [])
	{
		$type = JsonLD::fetchElement($activity, '@type');
		if (!$type) {
			Logger::info('Empty type', ['activity' => $activity]);
			return;
		}

		if (!JsonLD::fetchElement($activity, 'as:object', '@id')) {
			Logger::info('Empty object', ['activity' => $activity]);
			return;
		}

		$actor = JsonLD::fetchElement($activity, 'as:actor', '@id');
		if (empty($actor)) {
			Logger::info('Empty actor', ['activity' => $activity]);
			return;
		}

		if (is_array($activity['as:object'])) {
			$attributed_to = JsonLD::fetchElement($activity['as:object'], 'as:attributedTo', '@id');
		} else {
			$attributed_to = '';
		}

		// Test the provided signatures against the actor and "attributedTo"
		if ($trust_source) {
			if (!empty($attributed_to) && !empty($actor)) {
				$trust_source = (in_array($actor, $signer) && in_array($attributed_to, $signer));
			} else {
				$trust_source = in_array($actor, $signer);
			}
		}

		// $trust_source is called by reference and is set to true if the content was retrieved successfully
		$object_data = self::prepareObjectData($activity, $uid, $push, $trust_source);
		if (empty($object_data)) {
			Logger::info('No object data found', ['activity' => $activity]);
			return;
		}

		if (!$trust_source) {
			Logger::info('Activity trust could not be achieved.',  ['id' => $object_data['object_id'], 'type' => $type, 'signer' => $signer, 'actor' => $actor, 'attributedTo' => $attributed_to]);
			return;
		}

		if (!empty($body) && empty($object_data['raw'])) {
			$object_data['raw'] = $body;
		}

		// Internal flag for thread completion. See Processor.php
		if (!empty($activity['thread-completion'])) {
			$object_data['thread-completion'] = $activity['thread-completion'];
		}

		// Internal flag for posts that arrived via relay
		if (!empty($activity['from-relay'])) {
			$object_data['from-relay'] = $activity['from-relay'];
		}
		
		switch ($type) {
			case 'as:Create':
				if (in_array($object_data['object_type'], self::CONTENT_TYPES)) {
					$item = ActivityPub\Processor::createItem($object_data);
					ActivityPub\Processor::postItem($object_data, $item);
				}
				break;

			case 'as:Add':
				if ($object_data['object_type'] == 'as:tag') {
					ActivityPub\Processor::addTag($object_data);
				}
				break;

			case 'as:Announce':
				if (in_array($object_data['object_type'], self::CONTENT_TYPES)) {
					$object_data['thread-completion'] = Contact::getIdForURL($actor);

					$item = ActivityPub\Processor::createItem($object_data);
					if (empty($item)) {
						return;
					}

					$item['post-type'] = Item::PT_ANNOUNCEMENT;
					ActivityPub\Processor::postItem($object_data, $item);

					$announce_object_data = self::processObject($activity);
					$announce_object_data['name'] = $type;
					$announce_object_data['author'] = JsonLD::fetchElement($activity, 'as:actor', '@id');
					$announce_object_data['object_id'] = $object_data['object_id'];
					$announce_object_data['object_type'] = $object_data['object_type'];
					$announce_object_data['push'] = $push;

					if (!empty($body)) {
						$announce_object_data['raw'] = $body;
					}

					ActivityPub\Processor::createActivity($announce_object_data, Activity::ANNOUNCE);
				}
				break;

			case 'as:Like':
				if (in_array($object_data['object_type'], self::CONTENT_TYPES)) {
					ActivityPub\Processor::createActivity($object_data, Activity::LIKE);
				}
				break;

			case 'as:Dislike':
				if (in_array($object_data['object_type'], self::CONTENT_TYPES)) {
					ActivityPub\Processor::createActivity($object_data, Activity::DISLIKE);
				}
				break;

			case 'as:TentativeAccept':
				if (in_array($object_data['object_type'], self::CONTENT_TYPES)) {
					ActivityPub\Processor::createActivity($object_data, Activity::ATTENDMAYBE);
				}
				break;

			case 'as:Update':
				if (in_array($object_data['object_type'], self::CONTENT_TYPES)) {
					ActivityPub\Processor::updateItem($object_data);
				} elseif (in_array($object_data['object_type'], self::ACCOUNT_TYPES)) {
					ActivityPub\Processor::updatePerson($object_data);
				}
				break;

			case 'as:Delete':
				if ($object_data['object_type'] == 'as:Tombstone') {
					ActivityPub\Processor::deleteItem($object_data);
				} elseif (in_array($object_data['object_type'], self::ACCOUNT_TYPES)) {
					ActivityPub\Processor::deletePerson($object_data);
				}
				break;

			case 'as:Follow':
				if (in_array($object_data['object_type'], self::ACCOUNT_TYPES)) {
					ActivityPub\Processor::followUser($object_data);
				} elseif (in_array($object_data['object_type'], self::CONTENT_TYPES)) {
					$object_data['reply-to-id'] = $object_data['object_id'];
					ActivityPub\Processor::createActivity($object_data, Activity::FOLLOW);
				}
				break;

			case 'as:Accept':
				if ($object_data['object_type'] == 'as:Follow') {
					ActivityPub\Processor::acceptFollowUser($object_data);
				} elseif (in_array($object_data['object_type'], self::CONTENT_TYPES)) {
					ActivityPub\Processor::createActivity($object_data, Activity::ATTEND);
				}
				break;

			case 'as:Reject':
				if ($object_data['object_type'] == 'as:Follow') {
					ActivityPub\Processor::rejectFollowUser($object_data);
				} elseif (in_array($object_data['object_type'], self::CONTENT_TYPES)) {
					ActivityPub\Processor::createActivity($object_data, Activity::ATTENDNO);
				}
				break;

			case 'as:Undo':
				if (($object_data['object_type'] == 'as:Follow') &&
					in_array($object_data['object_object_type'], self::ACCOUNT_TYPES)) {
					ActivityPub\Processor::undoFollowUser($object_data);
				} elseif (($object_data['object_type'] == 'as:Accept') &&
					in_array($object_data['object_object_type'], self::ACCOUNT_TYPES)) {
					ActivityPub\Processor::rejectFollowUser($object_data);
				} elseif (in_array($object_data['object_type'], self::ACTIVITY_TYPES) &&
					in_array($object_data['object_object_type'], self::CONTENT_TYPES)) {
					ActivityPub\Processor::undoActivity($object_data);
				}
				break;

			default:
				Logger::log('Unknown activity: ' . $type . ' ' . $object_data['object_type'], Logger::DEBUG);
				break;
		}
	}

	/**
	 * Fetch the receiver list from an activity array
	 *
	 * @param array   $activity
	 * @param string  $actor
	 * @param array   $tags
	 * @param boolean $fetch_unlisted 
	 *
	 * @return array with receivers (user id)
	 * @throws \Exception
	 */
	private static function getReceivers($activity, $actor, $tags = [], $fetch_unlisted = false)
	{
		$reply = $receivers = [];

		// When it is an answer, we inherite the receivers from the parent
		$replyto = JsonLD::fetchElement($activity, 'as:inReplyTo', '@id');
		if (!empty($replyto)) {
			$reply = [$replyto];

			// Fix possibly wrong item URI (could be an answer to a plink uri)
			$fixedReplyTo = Item::getURIByLink($replyto);
			if (!empty($fixedReplyTo)) {
				$reply[] = $fixedReplyTo;
			}
		}

		// Fetch all posts that refer to the object id
		$object_id = JsonLD::fetchElement($activity, 'as:object', '@id');
		if (!empty($object_id)) {
			$reply[] = $object_id;
		}

		if (!empty($reply)) {
			$parents = Item::select(['uid'], ['uri' => $reply]);
			while ($parent = Item::fetch($parents)) {
				$receivers[$parent['uid']] = ['uid' => $parent['uid'], 'type' => self::TARGET_ANSWER];
			}
		}

		if (!empty($actor)) {
			$profile = APContact::getByURL($actor);
			$followers = $profile['followers'] ?? '';

			Logger::log('Actor: ' . $actor . ' - Followers: ' . $followers, Logger::DEBUG);
		} else {
			Logger::info('Empty actor', ['activity' => $activity]);
			$followers = '';
		}

		// We have to prevent false follower assumptions upon thread completions
		$follower_target = empty($activity['thread-completion']) ? self::TARGET_FOLLOWER : self::TARGET_UNKNOWN;

		foreach (['as:to', 'as:cc', 'as:bto', 'as:bcc'] as $element) {
			$receiver_list = JsonLD::fetchElementArray($activity, $element, '@id');
			if (empty($receiver_list)) {
				continue;
			}

			foreach ($receiver_list as $receiver) {
				if ($receiver == self::PUBLIC_COLLECTION) {
					$receivers[0] = ['uid' => 0, 'type' => self::TARGET_GLOBAL];
				}

				// Add receiver "-1" for unlisted posts 
				if ($fetch_unlisted && ($receiver == self::PUBLIC_COLLECTION) && ($element == 'as:cc')) {
					$receivers[-1] = ['uid' => -1, 'type' => self::TARGET_GLOBAL];
				}

				// Fetch the receivers for the public and the followers collection
				if (in_array($receiver, [$followers, self::PUBLIC_COLLECTION]) && !empty($actor)) {
					$receivers = self::getReceiverForActor($actor, $tags, $receivers, $follower_target);
					continue;
				}

				// Fetching all directly addressed receivers
				$condition = ['self' => true, 'nurl' => Strings::normaliseLink($receiver)];
				$contact = DBA::selectFirst('contact', ['uid', 'contact-type'], $condition);
				if (!DBA::isResult($contact)) {
					continue;
				}

				// Check if the potential receiver is following the actor
				// Exception: The receiver is targetted via "to" or this is a comment
				if ((($element != 'as:to') && empty($replyto)) || ($contact['contact-type'] == Contact::TYPE_COMMUNITY)) {
					$networks = Protocol::FEDERATED;
					$condition = ['nurl' => Strings::normaliseLink($actor), 'rel' => [Contact::SHARING, Contact::FRIEND],
						'network' => $networks, 'archive' => false, 'pending' => false, 'uid' => $contact['uid']];

					// Forum posts are only accepted from forum contacts
					if ($contact['contact-type'] == Contact::TYPE_COMMUNITY) {
						$condition['rel'] = [Contact::SHARING, Contact::FRIEND, Contact::FOLLOWER];
					}

					if (!DBA::exists('contact', $condition)) {
						continue;
					}
				}

				$type = $receivers[$contact['uid']]['type'] ?? self::TARGET_UNKNOWN;
				if (in_array($type, [self::TARGET_UNKNOWN, self::TARGET_FOLLOWER, self::TARGET_ANSWER, self::TARGET_GLOBAL])) {
					switch ($element) {
						case 'as:to':
							$type = self::TARGET_TO;
							break;
						case 'as:cc':
							$type = self::TARGET_CC;
							break;
						case 'as:bto':
							$type = self::TARGET_BTO;
							break;
						case 'as:bcc':
							$type = self::TARGET_BCC;
							break;
					}

					$receivers[$contact['uid']] = ['uid' => $contact['uid'], 'type' => $type];
				}
			}
		}

		self::switchContacts($receivers, $actor);

		return $receivers;
	}

	/**
	 * Fetch the receiver list of a given actor
	 *
	 * @param string  $actor
	 * @param array   $tags
	 * @param array   $receivers
	 * @param integer $target_type
	 *
	 * @return array with receivers (user id)
	 * @throws \Exception
	 */
	private static function getReceiverForActor($actor, $tags, $receivers, $target_type)
	{
		$basecondition = ['rel' => [Contact::SHARING, Contact::FRIEND, Contact::FOLLOWER],
			'network' => Protocol::FEDERATED, 'archive' => false, 'pending' => false];

		$condition = DBA::mergeConditions($basecondition, ["`nurl` = ? AND `uid` != ?", Strings::normaliseLink($actor), 0]);
		$contacts = DBA::select('contact', ['uid', 'rel'], $condition);
		while ($contact = DBA::fetch($contacts)) {
			if (empty($receivers[$contact['uid']]) && self::isValidReceiverForActor($contact, $tags)) {
				$receivers[$contact['uid']] = ['uid' => $contact['uid'], 'type' => $target_type];
			}
		}
		DBA::close($contacts);

		// The queries are split because of performance issues
		$condition = DBA::mergeConditions($basecondition, ["`alias` IN (?, ?) AND `uid` != ?", Strings::normaliseLink($actor), $actor, 0]);
		$contacts = DBA::select('contact', ['uid', 'rel'], $condition);
		while ($contact = DBA::fetch($contacts)) {
			if (empty($receivers[$contact['uid']]) && self::isValidReceiverForActor($contact, $tags)) {
				$receivers[$contact['uid']] = ['uid' => $contact['uid'], 'type' => $target_type];
			}
		}
		DBA::close($contacts);
		return $receivers;
	}

	/**
	 * Tests if the contact is a valid receiver for this actor
	 *
	 * @param array  $contact
	 * @param string $actor
	 * @param array  $tags
	 *
	 * @return bool with receivers (user id)
	 * @throws \Exception
	 */
	private static function isValidReceiverForActor($contact, $tags)
	{
		// Are we following the contact? Then this is a valid receiver
		if (in_array($contact['rel'], [Contact::SHARING, Contact::FRIEND])) {
			return true;
		}

		// When the possible receiver isn't a community, then it is no valid receiver
		$owner = User::getOwnerDataById($contact['uid']);
		if (empty($owner) || ($owner['contact-type'] != Contact::TYPE_COMMUNITY)) {
			return false;
		}

		// Is the community account tagged?
		foreach ($tags as $tag) {
			if ($tag['type'] != 'Mention') {
				continue;
			}

			if (Strings::compareLink($tag['href'], $owner['url'])) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Switches existing contacts to ActivityPub
	 *
	 * @param integer $cid Contact ID
	 * @param integer $uid User ID
	 * @param string  $url Profile URL
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function switchContact($cid, $uid, $url)
	{
		if (DBA::exists('contact', ['id' => $cid, 'network' => Protocol::ACTIVITYPUB])) {
			Logger::info('Contact is already ActivityPub', ['id' => $cid, 'uid' => $uid, 'url' => $url]);
			return;
		}

		if (Contact::updateFromProbe($cid)) {
			Logger::info('Update was successful', ['id' => $cid, 'uid' => $uid, 'url' => $url]);
		}

		// Send a new follow request to be sure that the connection still exists
		if (($uid != 0) && DBA::exists('contact', ['id' => $cid, 'rel' => [Contact::SHARING, Contact::FRIEND], 'network' => Protocol::ACTIVITYPUB])) {
			Logger::info('Contact had been switched to ActivityPub. Sending a new follow request.', ['uid' => $uid, 'url' => $url]);
			ActivityPub\Transmitter::sendActivity('Follow', $url, $uid);
		}
	}

	/**
	 *
	 *
	 * @param $receivers
	 * @param $actor
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function switchContacts($receivers, $actor)
	{
		if (empty($actor)) {
			return;
		}

		foreach ($receivers as $receiver) {
			$contact = DBA::selectFirst('contact', ['id'], ['uid' => $receiver['uid'], 'network' => Protocol::OSTATUS, 'nurl' => Strings::normaliseLink($actor)]);
			if (DBA::isResult($contact)) {
				self::switchContact($contact['id'], $receiver['uid'], $actor);
			}

			$contact = DBA::selectFirst('contact', ['id'], ['uid' => $receiver['uid'], 'network' => Protocol::OSTATUS, 'alias' => [Strings::normaliseLink($actor), $actor]]);
			if (DBA::isResult($contact)) {
				self::switchContact($contact['id'], $receiver['uid'], $actor);
			}
		}
	}

	/**
	 *
	 *
	 * @param       $object_data
	 * @param array $activity
	 *
	 * @return mixed
	 */
	private static function addActivityFields($object_data, $activity)
	{
		if (!empty($activity['published']) && empty($object_data['published'])) {
			$object_data['published'] = JsonLD::fetchElement($activity, 'as:published', '@value');
		}

		if (!empty($activity['diaspora:guid']) && empty($object_data['diaspora:guid'])) {
			$object_data['diaspora:guid'] = JsonLD::fetchElement($activity, 'diaspora:guid', '@value');
		}

		$object_data['service'] = JsonLD::fetchElement($activity, 'as:instrument', 'as:name', '@type', 'as:Service');
		$object_data['service'] = JsonLD::fetchElement($object_data, 'service', '@value');

		if (!empty($object_data['object_id'])) {
			// Some systems (e.g. GNU Social) don't reply to the "id" field but the "uri" field.
			$objectId = Item::getURIByLink($object_data['object_id']);
			if (!empty($objectId) && ($object_data['object_id'] != $objectId)) {
				Logger::notice('Fix wrong object-id', ['received' => $object_data['object_id'], 'correct' => $objectId]);
				$object_data['object_id'] = $objectId;
			}
		}

		return $object_data;
	}

	/**
	 * Fetches the object data from external ressources if needed
	 *
	 * @param string  $object_id    Object ID of the the provided object
	 * @param array   $object       The provided object array
	 * @param boolean $trust_source Do we trust the provided object?
	 * @param integer $uid          User ID for the signature that we use to fetch data
	 *
	 * @return array|false with trusted and valid object data
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function fetchObject(string $object_id, array $object = [], bool $trust_source = false, int $uid = 0)
	{
		// By fetching the type we check if the object is complete.
		$type = JsonLD::fetchElement($object, '@type');

		if (!$trust_source || empty($type)) {
			$data = ActivityPub::fetchContent($object_id, $uid);
			if (!empty($data)) {
				$object = JsonLD::compact($data);
				Logger::log('Fetched content for ' . $object_id, Logger::DEBUG);
			} else {
				Logger::log('Empty content for ' . $object_id . ', check if content is available locally.', Logger::DEBUG);

				$item = Item::selectFirst([], ['uri' => $object_id]);
				if (!DBA::isResult($item)) {
					Logger::log('Object with url ' . $object_id . ' was not found locally.', Logger::DEBUG);
					return false;
				}
				Logger::log('Using already stored item for url ' . $object_id, Logger::DEBUG);
				$data = ActivityPub\Transmitter::createNote($item);
				$object = JsonLD::compact($data);
			}

			$id = JsonLD::fetchElement($object, '@id');
			if (empty($id)) {
				Logger::info('Empty id');
				return false;
			}
	
			if ($id != $object_id) {
				Logger::info('Fetched id differs from provided id', ['provided' => $object_id, 'fetched' => $id]);
				return false;
			}
		} else {
			Logger::log('Using original object for url ' . $object_id, Logger::DEBUG);
		}

		$type = JsonLD::fetchElement($object, '@type');
		if (empty($type)) {
			Logger::info('Empty type');
			return false;
		}

		// We currently don't handle 'pt:CacheFile', but with this step we avoid logging
		if (in_array($type, self::CONTENT_TYPES) || ($type == 'pt:CacheFile')) {
			$object_data = self::processObject($object);

			if (!empty($data)) {
				$object_data['raw'] = json_encode($data);
			}
			return $object_data;
		}

		if ($type == 'as:Announce') {
			$object_id = JsonLD::fetchElement($object, 'object', '@id');
			if (empty($object_id) || !is_string($object_id)) {
				return false;
			}
			return self::fetchObject($object_id, [], false, $uid);
		}

		Logger::log('Unhandled object type: ' . $type, Logger::DEBUG);
		return false;
	}

	/**
	 * Convert tags from JSON-LD format into a simplified format
	 *
	 * @param array $tags Tags in JSON-LD format
	 *
	 * @return array with tags in a simplified format
	 */
	public static function processTags(array $tags)
	{
		$taglist = [];

		foreach ($tags as $tag) {
			if (empty($tag)) {
				continue;
			}

			$element = ['type' => str_replace('as:', '', JsonLD::fetchElement($tag, '@type')),
				'href' => JsonLD::fetchElement($tag, 'as:href', '@id'),
				'name' => JsonLD::fetchElement($tag, 'as:name', '@value')];

			if (empty($element['type'])) {
				continue;
			}

			if (empty($element['href'])) {
				$element['href'] = $element['name'];
			}

			$taglist[] = $element;
		}
		return $taglist;
	}

	/**
	 * Convert emojis from JSON-LD format into a simplified format
	 *
	 * @param array $emojis
	 * @return array with emojis in a simplified format
	 */
	private static function processEmojis(array $emojis)
	{
		$emojilist = [];

		foreach ($emojis as $emoji) {
			if (empty($emoji) || (JsonLD::fetchElement($emoji, '@type') != 'toot:Emoji') || empty($emoji['as:icon'])) {
				continue;
			}

			$url = JsonLD::fetchElement($emoji['as:icon'], 'as:url', '@id');
			$element = ['name' => JsonLD::fetchElement($emoji, 'as:name', '@value'),
				'href' => $url];

			$emojilist[] = $element;
		}

		return $emojilist;
	}

	/**
	 * Convert attachments from JSON-LD format into a simplified format
	 *
	 * @param array $attachments Attachments in JSON-LD format
	 *
	 * @return array Attachments in a simplified format
	 */
	private static function processAttachments(array $attachments)
	{
		$attachlist = [];

		// Removes empty values
		$attachments = array_filter($attachments);

		foreach ($attachments as $attachment) {
			switch (JsonLD::fetchElement($attachment, '@type')) {
				case 'as:Page':
					$pageUrl = null;
					$pageImage = null;

					$urls = JsonLD::fetchElementArray($attachment, 'as:url');
					foreach ($urls as $url) {
						// Single scalar URL case
						if (is_string($url)) {
							$pageUrl = $url;
							continue;
						}

						$href = JsonLD::fetchElement($url, 'as:href', '@id');
						$mediaType = JsonLD::fetchElement($url, 'as:mediaType', '@value');
						if (Strings::startsWith($mediaType, 'image')) {
							$pageImage = $href;
						} else {
							$pageUrl = $href;
						}
					}

					$attachlist[] = [
						'type'  => 'link',
						'title' => JsonLD::fetchElement($attachment, 'as:name', '@value'),
						'desc'  => JsonLD::fetchElement($attachment, 'as:summary', '@value'),
						'url'   => $pageUrl,
						'image' => $pageImage,
					];
					break;
				case 'as:Link':
					$attachlist[] = [
						'type' => str_replace('as:', '', JsonLD::fetchElement($attachment, '@type')),
						'mediaType' => JsonLD::fetchElement($attachment, 'as:mediaType', '@value'),
						'name' => JsonLD::fetchElement($attachment, 'as:name', '@value'),
						'url' => JsonLD::fetchElement($attachment, 'as:href', '@id')
					];
					break;
				case 'as:Image':
					$mediaType = JsonLD::fetchElement($attachment, 'as:mediaType', '@value');
					$imageFullUrl = JsonLD::fetchElement($attachment, 'as:url', '@id');
					$imagePreviewUrl = null;
					// Multiple URLs?
					if (!$imageFullUrl && ($urls = JsonLD::fetchElementArray($attachment, 'as:url'))) {
						$imageVariants = [];
						$previewVariants = [];
						foreach ($urls as $url) {
							// Scalar URL, no discrimination possible
							if (is_string($url)) {
								$imageFullUrl = $url;
								continue;
							}

							// Not sure what to do with a different Link media type than the base Image, we skip
							if ($mediaType != JsonLD::fetchElement($url, 'as:mediaType', '@value')) {
								continue;
							}

							$href = JsonLD::fetchElement($url, 'as:href', '@id');

							// Default URL choice if no discriminating width is provided
							$imageFullUrl = $href ?? $imageFullUrl;

							$width = intval(JsonLD::fetchElement($url, 'as:width', '@value') ?? 1);

							if ($href && $width) {
								$imageVariants[$width] = $href;
								// 632 is the ideal width for full screen frio posts, we compute the absolute distance to it
								$previewVariants[abs(632 - $width)] = $href;
							}
						}

						if ($imageVariants) {
							// Taking the maximum size image
							ksort($imageVariants);
							$imageFullUrl = array_pop($imageVariants);

							// Taking the minimum number distance to the target distance
							ksort($previewVariants);
							$imagePreviewUrl = array_shift($previewVariants);
						}

						unset($imageVariants);
						unset($previewVariants);
					}

					$attachlist[] = [
						'type' => str_replace('as:', '', JsonLD::fetchElement($attachment, '@type')),
						'mediaType' => $mediaType,
						'name'  => JsonLD::fetchElement($attachment, 'as:name', '@value'),
						'url'   => $imageFullUrl,
						'image' => $imagePreviewUrl !== $imageFullUrl ? $imagePreviewUrl : null,
					];
					break;
				default:
					$attachlist[] = [
						'type' => str_replace('as:', '', JsonLD::fetchElement($attachment, '@type')),
						'mediaType' => JsonLD::fetchElement($attachment, 'as:mediaType', '@value'),
						'name' => JsonLD::fetchElement($attachment, 'as:name', '@value'),
						'url' => JsonLD::fetchElement($attachment, 'as:url', '@id')
					];
			}
		}

		return $attachlist;
	}

	/**
	 * Fetch the original source or content with the "language" Markdown or HTML
	 *
	 * @param array $object
	 * @param array $object_data
	 *
	 * @return array
	 * @throws \Exception
	 */
	private static function getSource($object, $object_data)
	{
		$object_data['source'] = JsonLD::fetchElement($object, 'as:source', 'as:content', 'as:mediaType', 'text/bbcode');
		$object_data['source'] = JsonLD::fetchElement($object_data, 'source', '@value');
		if (!empty($object_data['source'])) {
			return $object_data;
		}

		$object_data['source'] = JsonLD::fetchElement($object, 'as:source', 'as:content', 'as:mediaType', 'text/markdown');
		$object_data['source'] = JsonLD::fetchElement($object_data, 'source', '@value');
		if (!empty($object_data['source'])) {
			$object_data['source'] = Markdown::toBBCode($object_data['source']);
			return $object_data;
		}

		$object_data['source'] = JsonLD::fetchElement($object, 'as:source', 'as:content', 'as:mediaType', 'text/html');
		$object_data['source'] = JsonLD::fetchElement($object_data, 'source', '@value');
		if (!empty($object_data['source'])) {
			$object_data['source'] = HTML::toBBCode($object_data['source']);
			return $object_data;
		}

		return $object_data;
	}

	/**
	 * Check if the "as:url" element is an array with multiple links
	 * This is the case with audio and video posts.
	 * Then the links are added as attachments
	 *
	 * @param array $object      The raw object
	 * @param array $object_data The parsed object data for later processing
	 * @return array the object data
	 */
	private static function processAttachmentUrls(array $object, array $object_data) {
		// Check if this is some url with multiple links
		if (empty($object['as:url'])) {
			return $object_data;
		}
		
		$urls = $object['as:url'];
		$keys = array_keys($urls);
		if (!is_numeric(array_pop($keys))) {
			return $object_data;
		}

		$attachments = [];

		foreach ($urls as $url) {
			if (empty($url['@type']) || ($url['@type'] != 'as:Link')) {
				continue;
			}

			$href = JsonLD::fetchElement($url, 'as:href', '@id');
			if (empty($href)) {
				continue;
			}

			$mediatype = JsonLD::fetchElement($url, 'as:mediaType');
			if (empty($mediatype)) {
				continue;
			}

			if ($mediatype == 'text/html') {
				$object_data['alternate-url'] = $href;
			}

			$filetype = strtolower(substr($mediatype, 0, strpos($mediatype, '/')));

			if ($filetype == 'audio') {
				$attachments[$filetype] = ['type' => $mediatype, 'url' => $href, 'height' => null, 'size' => null];
			} elseif ($filetype == 'video') {
				$height = (int)JsonLD::fetchElement($url, 'as:height', '@value');
				$size = (int)JsonLD::fetchElement($url, 'pt:size', '@value');

				// We save bandwidth by using a moderate height (alt least 480 pixel height)
				// Peertube normally uses these heights: 240, 360, 480, 720, 1080
				if (!empty($attachments[$filetype]['height']) &&
					($height > $attachments[$filetype]['height']) && ($attachments[$filetype]['height'] >= 480)) {
					continue;
				}

				$attachments[$filetype] = ['type' => $mediatype, 'url' => $href, 'height' => $height, 'size' => $size];
			} elseif (in_array($mediatype, ['application/x-bittorrent', 'application/x-bittorrent;x-scheme-handler/magnet'])) {
				$height = (int)JsonLD::fetchElement($url, 'as:height', '@value');

				// For Torrent links we always store the highest resolution
				if (!empty($attachments[$mediatype]['height']) && ($height < $attachments[$mediatype]['height'])) {
					continue;
				}

				$attachments[$mediatype] = ['type' => $mediatype, 'url' => $href, 'height' => $height, 'size' => null];
			}
		}

		foreach ($attachments as $type => $attachment) {
			$object_data['attachments'][] = ['type' => $type,
				'mediaType' => $attachment['type'],
				'height' => $attachment['height'],
				'size' => $attachment['size'],
				'name' => '',
				'url' => $attachment['url']];
		}
		return $object_data;
	}

	/**
	 * Fetches data from the object part of an activity
	 *
	 * @param array $object
	 *
	 * @return array
	 * @throws \Exception
	 */
	private static function processObject($object)
	{
		if (!JsonLD::fetchElement($object, '@id')) {
			return false;
		}

		$object_data = [];
		$object_data['object_type'] = JsonLD::fetchElement($object, '@type');
		$object_data['id'] = JsonLD::fetchElement($object, '@id');
		$object_data['reply-to-id'] = JsonLD::fetchElement($object, 'as:inReplyTo', '@id');

		// An empty "id" field is translated to "./" by the compactor, so we have to check for this content
		if (empty($object_data['reply-to-id']) || ($object_data['reply-to-id'] == './')) {
			$object_data['reply-to-id'] = $object_data['id'];
		} else {
			// Some systems (e.g. GNU Social) don't reply to the "id" field but the "uri" field.
			$replyToId = Item::getURIByLink($object_data['reply-to-id']);
			if (!empty($replyToId) && ($object_data['reply-to-id'] != $replyToId)) {
				Logger::notice('Fix wrong reply-to', ['received' => $object_data['reply-to-id'], 'correct' => $replyToId]);
				$object_data['reply-to-id'] = $replyToId;
			}
		}

		$object_data['published'] = JsonLD::fetchElement($object, 'as:published', '@value');
		$object_data['updated'] = JsonLD::fetchElement($object, 'as:updated', '@value');

		if (empty($object_data['updated'])) {
			$object_data['updated'] = $object_data['published'];
		}

		if (empty($object_data['published']) && !empty($object_data['updated'])) {
			$object_data['published'] = $object_data['updated'];
		}

		$actor = JsonLD::fetchElement($object, 'as:attributedTo', '@id');
		if (empty($actor)) {
			$actor = JsonLD::fetchElement($object, 'as:actor', '@id');
		}

		$location = JsonLD::fetchElement($object, 'as:location', 'as:name', '@type', 'as:Place');
		$location = JsonLD::fetchElement($location, 'location', '@value');
		if ($location) {
			// Some AP software allow formatted text in post location, so we run all the text converters we have to boil
			// down to HTML and then finally format to plaintext.
			$location = Markdown::convert($location);
			$location = BBCode::convert($location);
			$location = HTML::toPlaintext($location);
		}

		$object_data['sc:identifier'] = JsonLD::fetchElement($object, 'sc:identifier', '@value');
		$object_data['diaspora:guid'] = JsonLD::fetchElement($object, 'diaspora:guid', '@value');
		$object_data['diaspora:comment'] = JsonLD::fetchElement($object, 'diaspora:comment', '@value');
		$object_data['diaspora:like'] = JsonLD::fetchElement($object, 'diaspora:like', '@value');
		$object_data['actor'] = $object_data['author'] = $actor;
		$object_data['context'] = JsonLD::fetchElement($object, 'as:context', '@id');
		$object_data['conversation'] = JsonLD::fetchElement($object, 'ostatus:conversation', '@id');
		$object_data['sensitive'] = JsonLD::fetchElement($object, 'as:sensitive');
		$object_data['name'] = JsonLD::fetchElement($object, 'as:name', '@value');
		$object_data['summary'] = JsonLD::fetchElement($object, 'as:summary', '@value');
		$object_data['content'] = JsonLD::fetchElement($object, 'as:content', '@value');
		$object_data = self::getSource($object, $object_data);
		$object_data['start-time'] = JsonLD::fetchElement($object, 'as:startTime', '@value');
		$object_data['end-time'] = JsonLD::fetchElement($object, 'as:endTime', '@value');
		$object_data['location'] = $location;
		$object_data['latitude'] = JsonLD::fetchElement($object, 'as:location', 'as:latitude', '@type', 'as:Place');
		$object_data['latitude'] = JsonLD::fetchElement($object_data, 'latitude', '@value');
		$object_data['longitude'] = JsonLD::fetchElement($object, 'as:location', 'as:longitude', '@type', 'as:Place');
		$object_data['longitude'] = JsonLD::fetchElement($object_data, 'longitude', '@value');
		$object_data['attachments'] = self::processAttachments(JsonLD::fetchElementArray($object, 'as:attachment') ?? []);
		$object_data['tags'] = self::processTags(JsonLD::fetchElementArray($object, 'as:tag') ?? []);
		$object_data['emojis'] = self::processEmojis(JsonLD::fetchElementArray($object, 'as:tag', null, '@type', 'toot:Emoji') ?? []);
		$object_data['generator'] = JsonLD::fetchElement($object, 'as:generator', 'as:name', '@type', 'as:Application');
		$object_data['generator'] = JsonLD::fetchElement($object_data, 'generator', '@value');
		$object_data['alternate-url'] = JsonLD::fetchElement($object, 'as:url', '@id');

		// Special treatment for Hubzilla links
		if (is_array($object_data['alternate-url'])) {
			$object_data['alternate-url'] = JsonLD::fetchElement($object_data['alternate-url'], 'as:href', '@id');

			if (!is_string($object_data['alternate-url'])) {
				$object_data['alternate-url'] = JsonLD::fetchElement($object['as:url'], 'as:href', '@id');
			}
		}

		if (in_array($object_data['object_type'], ['as:Audio', 'as:Video'])) {
			$object_data = self::processAttachmentUrls($object, $object_data);
		}

		$receiverdata = self::getReceivers($object, $object_data['actor'], $object_data['tags'], true);
		$receivers = $reception_types = [];
		foreach ($receiverdata as $key => $data) {
			$receivers[$key] = $data['uid'];
			$reception_types[$data['uid']] = $data['type'] ?? 0;
		}

		$object_data['receiver'] = $receivers;
		$object_data['reception_type'] = $reception_types;

		$object_data['unlisted'] = in_array(-1, $object_data['receiver']);
		unset($object_data['receiver'][-1]);
		unset($object_data['reception_type'][-1]);

		// Common object data:

		// Unhandled
		// @context, type, actor, signature, mediaType, duration, replies, icon

		// Also missing: (Defined in the standard, but currently unused)
		// audience, preview, endTime, startTime, image

		// Data in Notes:

		// Unhandled
		// contentMap, announcement_count, announcements, context_id, likes, like_count
		// inReplyToStatusId, shares, quoteUrl, statusnetConversationId

		// Data in video:

		// To-Do?
		// category, licence, language, commentsEnabled

		// Unhandled
		// views, waitTranscoding, state, support, subtitleLanguage
		// likes, dislikes, shares, comments

		return $object_data;
	}
}
