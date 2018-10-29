<?php
/**
 * @file src/Protocol/ActivityPub/Receiver.php
 */
namespace Friendica\Protocol\ActivityPub;

use Friendica\App;
use Friendica\Content\Text;
use Friendica\Database\DBA;
use Friendica\Util\HTTPSignature;
use Friendica\Core\Protocol;
use Friendica\Model\Contact;
use Friendica\Model\APContact;
use Friendica\Model\Item;
use Friendica\Model\User;
use Friendica\Util\JsonLD;
use Friendica\Util\LDSignature;
use Friendica\Protocol\ActivityPub;
use Friendica\Model\Conversation;
use Friendica\Util\DateTimeFormat;

/**
 * @brief ActivityPub Receiver Protocol class
 *
 * To-Do:
 * - Undo Announce
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
	const CONTENT_TYPES = ['as:Note', 'as:Article', 'as:Video', 'as:Image', 'as:Event'];
	const ACTIVITY_TYPES = ['as:Like', 'as:Dislike', 'as:Accept', 'as:Reject', 'as:TentativeAccept'];

	/**
	 * Checks if the web request is done for the AP protocol
	 *
	 * @return is it AP?
	 */
	public static function isRequest()
	{
		return stristr(defaults($_SERVER, 'HTTP_ACCEPT', ''), 'application/activity+json') ||
			stristr(defaults($_SERVER, 'HTTP_ACCEPT', ''), 'application/ld+json');
	}

	/**
	 * Checks incoming message from the inbox
	 *
	 * @param $body
	 * @param $header
	 * @param integer $uid User ID
	 */
	public static function processInbox($body, $header, $uid)
	{
		$http_signer = HTTPSignature::getSigner($body, $header);
		if (empty($http_signer)) {
			App::logger('Invalid HTTP signature, message will be discarded.', LOGGER_DEBUG);
			return;
		} else {
			App::logger('HTTP signature is signed by ' . $http_signer, LOGGER_DEBUG);
		}

		$activity = json_decode($body, true);

		if (empty($activity)) {
			App::logger('Invalid body.', LOGGER_DEBUG);
			return;
		}

		$ldactivity = JsonLD::compact($activity);

		$actor = JsonLD::fetchElement($ldactivity, 'as:actor');

		App::logger('Message for user ' . $uid . ' is from actor ' . $actor, LOGGER_DEBUG);

		if (LDSignature::isSigned($activity)) {
			$ld_signer = LDSignature::getSigner($activity);
			if (empty($ld_signer)) {
				App::logger('Invalid JSON-LD signature from ' . $actor, LOGGER_DEBUG);
			}
			if (!empty($ld_signer && ($actor == $http_signer))) {
				App::logger('The HTTP and the JSON-LD signature belong to ' . $ld_signer, LOGGER_DEBUG);
				$trust_source = true;
			} elseif (!empty($ld_signer)) {
				App::logger('JSON-LD signature is signed by ' . $ld_signer, LOGGER_DEBUG);
				$trust_source = true;
			} elseif ($actor == $http_signer) {
				App::logger('Bad JSON-LD signature, but HTTP signer fits the actor.', LOGGER_DEBUG);
				$trust_source = true;
			} else {
				App::logger('Invalid JSON-LD signature and the HTTP signer is different.', LOGGER_DEBUG);
				$trust_source = false;
			}
		} elseif ($actor == $http_signer) {
			App::logger('Trusting post without JSON-LD signature, The actor fits the HTTP signer.', LOGGER_DEBUG);
			$trust_source = true;
		} else {
			App::logger('No JSON-LD signature, different actor.', LOGGER_DEBUG);
			$trust_source = false;
		}

		self::processActivity($ldactivity, $body, $uid, $trust_source);
	}

	/**
	 * Fetches the object type for a given object id
	 *
	 * @param array  $activity
	 * @param string $object_id Object ID of the the provided object
	 *
	 * @return string with object type
	 */
	private static function fetchObjectType($activity, $object_id)
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
			return 'as:' . $profile['type'];
		}

		$data = ActivityPub::fetchContent($object_id);
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
	 * @param array $activity
	 * @param integer $uid User ID
	 * @param $trust_source
	 *
	 * @return array with object data
	 */
	private static function prepareObjectData($activity, $uid, &$trust_source)
	{
		$actor = JsonLD::fetchElement($activity, 'as:actor');
		if (empty($actor)) {
			App::logger('Empty actor', LOGGER_DEBUG);
			return [];
		}

		$type = JsonLD::fetchElement($activity, '@type');

		// Fetch all receivers from to, cc, bto and bcc
		$receivers = self::getReceivers($activity, $actor);

		// When it is a delivery to a personal inbox we add that user to the receivers
		if (!empty($uid)) {
			$owner = User::getOwnerDataById($uid);
			$additional = ['uid:' . $uid => $uid];
			$receivers = array_merge($receivers, $additional);
		}

		App::logger('Receivers: ' . json_encode($receivers), LOGGER_DEBUG);

		$object_id = JsonLD::fetchElement($activity, 'as:object');
		if (empty($object_id)) {
			App::logger('No object found', LOGGER_DEBUG);
			return [];
		}

		$object_type = self::fetchObjectType($activity, $object_id);

		// Fetch the content only on activities where this matters
		if (in_array($type, ['as:Create', 'as:Update', 'as:Announce'])) {
			if ($type == 'as:Announce') {
				$trust_source = false;
			}
			$object_data = self::fetchObject($object_id, $activity['as:object'], $trust_source);
			if (empty($object_data)) {
				App::logger("Object data couldn't be processed", LOGGER_DEBUG);
				return [];
			}
			// We had been able to retrieve the object data - so we can trust the source
			$trust_source = true;
		} elseif (in_array($type, ['as:Like', 'as:Dislike'])) {
			// Create a mostly empty array out of the activity data (instead of the object).
			// This way we later don't have to check for the existence of ech individual array element.
			$object_data = self::processObject($activity);
			$object_data['name'] = $type;
			$object_data['author'] = JsonLD::fetchElement($activity, 'as:actor');
			$object_data['object_id'] = $object_id;
			$object_data['object_type'] = ''; // Since we don't fetch the object, we don't know the type
		} else {
			$object_data = [];
			$object_data['id'] = JsonLD::fetchElement($activity, '@id');
			$object_data['object_id'] = JsonLD::fetchElement($activity, 'as:object');
			$object_data['object_actor'] = JsonLD::fetchElement($activity['as:object'], 'as:actor');
			$object_data['object_object'] = JsonLD::fetchElement($activity['as:object'], 'as:object');
			$object_data['object_type'] = JsonLD::fetchElement($activity['as:object'], '@type');

			// An Undo is done on the object of an object, so we need that type as well
			if ($type == 'as:Undo') {
				$object_data['object_object_type'] = self::fetchObjectType([], $object_data['object_object']);
			}
		}

		$object_data = self::addActivityFields($object_data, $activity);

		if (empty($object_data['object_type'])) {
			$object_data['object_type'] = $object_type;
		}

		$object_data['type'] = $type;
		$object_data['actor'] = $actor;
		$object_data['receiver'] = array_merge(defaults($object_data, 'receiver', []), $receivers);

		App::logger('Processing ' . $object_data['type'] . ' ' . $object_data['object_type'] . ' ' . $object_data['id'], LOGGER_DEBUG);

		return $object_data;
	}

	/**
	 * Store the unprocessed data into the conversation table
	 * This has to be done outside the regular function,
	 * since we store everything - not only item posts.
	 *
	 * @param array  $activity Array with activity data
	 * @param string $body     The raw message
	 */
	private static function storeConversation($activity, $body)
	{
		if (empty($body) || empty($activity['id'])) {
			return;
		}

		$conversation = [
			'protocol' => Conversation::PARCEL_ACTIVITYPUB,
			'item-uri' => $activity['id'],
			'reply-to-uri' => defaults($activity, 'reply-to-id', ''),
			'conversation-href' => defaults($activity, 'context', ''),
			'conversation-uri' => defaults($activity, 'conversation', ''),
			'source' => $body,
			'received' => DateTimeFormat::utcNow()];

		DBA::insert('conversation', $conversation, true);
	}

	/**
	 * Processes the activity object
	 *
	 * @param array   $activity     Array with activity data
	 * @param string  $body
	 * @param integer $uid          User ID
	 * @param boolean $trust_source Do we trust the source?
	 */
	public static function processActivity($activity, $body = '', $uid = null, $trust_source = false)
	{
		$type = JsonLD::fetchElement($activity, '@type');
		if (!$type) {
			App::logger('Empty type', LOGGER_DEBUG);
			return;
		}

		if (!JsonLD::fetchElement($activity, 'as:object')) {
			App::logger('Empty object', LOGGER_DEBUG);
			return;
		}

		if (!JsonLD::fetchElement($activity, 'as:actor')) {
			App::logger('Empty actor', LOGGER_DEBUG);
			return;

		}

		// $trust_source is called by reference and is set to true if the content was retrieved successfully
		$object_data = self::prepareObjectData($activity, $uid, $trust_source);
		if (empty($object_data)) {
			App::logger('No object data found', LOGGER_DEBUG);
			return;
		}

		if (!$trust_source) {
			App::logger('No trust for activity type "' . $type . '", so we quit now.', LOGGER_DEBUG);
			return;
		}

		self::storeConversation($object_data, $body);

		// Internal flag for thread completion. See Processor.php
		if (!empty($activity['thread-completion'])) {
			$object_data['thread-completion'] = $activity['thread-completion'];
		}

		switch ($type) {
			case 'as:Create':
			case 'as:Announce':
				if (in_array($object_data['object_type'], self::CONTENT_TYPES)) {
					ActivityPub\Processor::createItem($object_data);
				}
				break;

			case 'as:Like':
				if (in_array($object_data['object_type'], self::CONTENT_TYPES)) {
					ActivityPub\Processor::createActivity($object_data, ACTIVITY_LIKE);
				}
				break;

			case 'as:Dislike':
				if (in_array($object_data['object_type'], self::CONTENT_TYPES)) {
					ActivityPub\Processor::createActivity($object_data, ACTIVITY_DISLIKE);
				}
				break;

			case 'as:TentativeAccept':
				if (in_array($object_data['object_type'], self::CONTENT_TYPES)) {
					ActivityPub\Processor::createActivity($object_data, ACTIVITY_ATTENDMAYBE);
				}
				break;

			case 'as:Update':
				if (in_array($object_data['object_type'], self::CONTENT_TYPES)) {
					ActivityPub\Processor::updateItem($object_data);
				} elseif (in_array($object_data['object_type'], self::ACCOUNT_TYPES)) {
					ActivityPub\Processor::updatePerson($object_data, $body);
				}
				break;

			case 'as:Delete':
				if ($object_data['object_type'] == 'as:Tombstone') {
					ActivityPub\Processor::deleteItem($object_data, $body);
				} elseif (in_array($object_data['object_type'], self::ACCOUNT_TYPES)) {
					ActivityPub\Processor::deletePerson($object_data, $body);
				}
				break;

			case 'as:Follow':
				if (in_array($object_data['object_type'], self::ACCOUNT_TYPES)) {
					ActivityPub\Processor::followUser($object_data);
				}
				break;

			case 'as:Accept':
				if ($object_data['object_type'] == 'as:Follow') {
					ActivityPub\Processor::acceptFollowUser($object_data);
				} elseif (in_array($object_data['object_type'], self::CONTENT_TYPES)) {
					ActivityPub\Processor::createActivity($object_data, ACTIVITY_ATTEND);
				}
				break;

			case 'as:Reject':
				if ($object_data['object_type'] == 'as:Follow') {
					ActivityPub\Processor::rejectFollowUser($object_data);
				} elseif (in_array($object_data['object_type'], self::CONTENT_TYPES)) {
					ActivityPub\Processor::createActivity($object_data, ACTIVITY_ATTENDNO);
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
				App::logger('Unknown activity: ' . $type . ' ' . $object_data['object_type'], LOGGER_DEBUG);
				break;
		}
	}

	/**
	 * Fetch the receiver list from an activity array
	 *
	 * @param array $activity
	 * @param string $actor
	 *
	 * @return array with receivers (user id)
	 */
	private static function getReceivers($activity, $actor)
	{
		$receivers = [];

		// When it is an answer, we inherite the receivers from the parent
		$replyto = JsonLD::fetchElement($activity, 'as:inReplyTo');
		if (!empty($replyto)) {
			$parents = Item::select(['uid'], ['uri' => $replyto]);
			while ($parent = Item::fetch($parents)) {
				$receivers['uid:' . $parent['uid']] = $parent['uid'];
			}
		}

		if (!empty($actor)) {
			$profile = APContact::getByURL($actor);
			$followers = defaults($profile, 'followers', '');

			App::logger('Actor: ' . $actor . ' - Followers: ' . $followers, LOGGER_DEBUG);
		} else {
			App::logger('Empty actor', LOGGER_DEBUG);
			$followers = '';
		}

		foreach (['as:to', 'as:cc', 'as:bto', 'as:bcc'] as $element) {
			$receiver_list = JsonLD::fetchElementArray($activity, $element);
			if (empty($receiver_list)) {
				continue;
			}

			foreach ($receiver_list as $receiver) {
				if ($receiver == self::PUBLIC_COLLECTION) {
					$receivers['uid:0'] = 0;
				}

				if (($receiver == self::PUBLIC_COLLECTION) && !empty($actor)) {
					// This will most likely catch all OStatus connections to Mastodon
					$condition = ['alias' => [$actor, Text::normaliseLink($actor)], 'rel' => [Contact::SHARING, Contact::FRIEND]
						, 'archive' => false, 'pending' => false];
					$contacts = DBA::select('contact', ['uid'], $condition);
					while ($contact = DBA::fetch($contacts)) {
						if ($contact['uid'] != 0) {
							$receivers['uid:' . $contact['uid']] = $contact['uid'];
						}
					}
					DBA::close($contacts);
				}

				if (in_array($receiver, [$followers, self::PUBLIC_COLLECTION]) && !empty($actor)) {
					$networks = [Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::DIASPORA, Protocol::OSTATUS];
					$condition = ['nurl' => Text::normaliseLink($actor), 'rel' => [Contact::SHARING, Contact::FRIEND],
						'network' => $networks, 'archive' => false, 'pending' => false];
					$contacts = DBA::select('contact', ['uid'], $condition);
					while ($contact = DBA::fetch($contacts)) {
						if ($contact['uid'] != 0) {
							$receivers['uid:' . $contact['uid']] = $contact['uid'];
						}
					}
					DBA::close($contacts);
					continue;
				}

				$condition = ['self' => true, 'nurl' => Text::normaliseLink($receiver)];
				$contact = DBA::selectFirst('contact', ['uid'], $condition);
				if (!DBA::isResult($contact)) {
					continue;
				}
				$receivers['uid:' . $contact['uid']] = $contact['uid'];
			}
		}

		self::switchContacts($receivers, $actor);

		return $receivers;
	}

	/**
	 * Switches existing contacts to ActivityPub
	 *
	 * @param integer $cid Contact ID
	 * @param integer $uid User ID
	 * @param string $url Profile URL
	 */
	public static function switchContact($cid, $uid, $url)
	{
		$profile = ActivityPub::probeProfile($url);
		if (empty($profile)) {
			return;
		}

		App::logger('Switch contact ' . $cid . ' (' . $profile['url'] . ') for user ' . $uid . ' to ActivityPub');

		$photo = defaults($profile, 'photo', null);
		unset($profile['photo']);
		unset($profile['baseurl']);

		$profile['nurl'] = Text::normaliseLink($profile['url']);
		DBA::update('contact', $profile, ['id' => $cid]);

		Contact::updateAvatar($photo, $uid, $cid);

		// Send a new follow request to be sure that the connection still exists
		if (($uid != 0) && DBA::exists('contact', ['id' => $cid, 'rel' => [Contact::SHARING, Contact::FRIEND]])) {
			ActivityPub\Transmitter::sendActivity('Follow', $profile['url'], $uid);
			App::logger('Send a new follow request to ' . $profile['url'] . ' for user ' . $uid, LOGGER_DEBUG);
		}
	}

	/**
	 *
	 *
	 * @param $receivers
	 * @param $actor
	 */
	private static function switchContacts($receivers, $actor)
	{
		if (empty($actor)) {
			return;
		}

		foreach ($receivers as $receiver) {
			$contact = DBA::selectFirst('contact', ['id'], ['uid' => $receiver, 'network' => Protocol::OSTATUS, 'nurl' => Text::normaliseLink($actor)]);
			if (DBA::isResult($contact)) {
				self::switchContact($contact['id'], $receiver, $actor);
			}

			$contact = DBA::selectFirst('contact', ['id'], ['uid' => $receiver, 'network' => Protocol::OSTATUS, 'alias' => [Text::normaliseLink($actor), $actor]]);
			if (DBA::isResult($contact)) {
				self::switchContact($contact['id'], $receiver, $actor);
			}
		}
	}

	/**
	 *
	 *
	 * @param $object_data
	 * @param array $activity
	 *
	 * @return
	 */
	private static function addActivityFields($object_data, $activity)
	{
		if (!empty($activity['published']) && empty($object_data['published'])) {
			$object_data['published'] = JsonLD::fetchElement($activity, 'as:published', '@value');
		}

		if (!empty($activity['diaspora:guid']) && empty($object_data['diaspora:guid'])) {
			$object_data['diaspora:guid'] = JsonLD::fetchElement($activity, 'diaspora:guid');
		}

		$object_data['service'] = JsonLD::fetchElement($activity, 'as:instrument', 'as:name', '@type', 'as:Service');

		return $object_data;
	}

	/**
	 * Fetches the object data from external ressources if needed
	 *
	 * @param string  $object_id    Object ID of the the provided object
	 * @param array   $object       The provided object array
	 * @param boolean $trust_source Do we trust the provided object?
	 *
	 * @return array with trusted and valid object data
	 */
	private static function fetchObject($object_id, $object = [], $trust_source = false)
	{
		// By fetching the type we check if the object is complete.
		$type = JsonLD::fetchElement($object, '@type');

		if (!$trust_source || empty($type)) {
			$data = ActivityPub::fetchContent($object_id);
			if (!empty($data)) {
				$object = JsonLD::compact($data);
				App::logger('Fetched content for ' . $object_id, LOGGER_DEBUG);
			} else {
				App::logger('Empty content for ' . $object_id . ', check if content is available locally.', LOGGER_DEBUG);

				$item = Item::selectFirst([], ['uri' => $object_id]);
				if (!DBA::isResult($item)) {
					App::logger('Object with url ' . $object_id . ' was not found locally.', LOGGER_DEBUG);
					return false;
				}
				App::logger('Using already stored item for url ' . $object_id, LOGGER_DEBUG);
				$data = ActivityPub\Transmitter::createNote($item);
				$object = JsonLD::compact($data);
			}
		} else {
			App::logger('Using original object for url ' . $object_id, LOGGER_DEBUG);
		}

		$type = JsonLD::fetchElement($object, '@type');

		if (empty($type)) {
			App::logger('Empty type', LOGGER_DEBUG);
			return false;
		}

		if (in_array($type, self::CONTENT_TYPES)) {
			return self::processObject($object);
		}

		if ($type == 'as:Announce') {
			$object_id = JsonLD::fetchElement($object, 'object');
			if (empty($object_id)) {
				return false;
			}
			return self::fetchObject($object_id);
		}

		App::logger('Unhandled object type: ' . $type, LOGGER_DEBUG);
	}

	/**
	 * Convert tags from JSON-LD format into a simplified format
	 *
	 * @param array $tags Tags in JSON-LD format
	 *
	 * @return array with tags in a simplified format
	 */
	private static function processTags($tags)
	{
		$taglist = [];

		if (empty($tags)) {
			return [];
		}

		foreach ($tags as $tag) {
			if (empty($tag)) {
				continue;
			}

			$taglist[] = ['type' => str_replace('as:', '', JsonLD::fetchElement($tag, '@type')),
				'href' => JsonLD::fetchElement($tag, 'as:href'),
				'name' => JsonLD::fetchElement($tag, 'as:name')];
		}
		return $taglist;
	}

	/**
	 * Convert attachments from JSON-LD format into a simplified format
	 *
	 * @param array $attachments Attachments in JSON-LD format
	 *
	 * @return array with attachmants in a simplified format
	 */
	private static function processAttachments($attachments)
	{
		$attachlist = [];

		if (empty($attachments)) {
			return [];
		}

		foreach ($attachments as $attachment) {
			if (empty($attachment)) {
				continue;
			}

			$attachlist[] = ['type' => str_replace('as:', '', JsonLD::fetchElement($attachment, '@type')),
				'mediaType' => JsonLD::fetchElement($attachment, 'as:mediaType'),
				'name' => JsonLD::fetchElement($attachment, 'as:name'),
				'url' => JsonLD::fetchElement($attachment, 'as:url')];
		}
		return $attachlist;
	}

	/**
	 * Fetches data from the object part of an activity
	 *
	 * @param array $object
	 *
	 * @return array
	 */
	private static function processObject($object)
	{
		if (!JsonLD::fetchElement($object, '@id')) {
			return false;
		}

		$object_data = [];
		$object_data['object_type'] = JsonLD::fetchElement($object, '@type');
		$object_data['id'] = JsonLD::fetchElement($object, '@id');

		$object_data['reply-to-id'] = JsonLD::fetchElement($object, 'as:inReplyTo');

		if (empty($object_data['reply-to-id'])) {
			$object_data['reply-to-id'] = $object_data['id'];
		}

		$object_data['published'] = JsonLD::fetchElement($object, 'as:published', '@value');
		$object_data['updated'] = JsonLD::fetchElement($object, 'as:updated', '@value');

		if (empty($object_data['updated'])) {
			$object_data['updated'] = $object_data['published'];
		}

		if (empty($object_data['published']) && !empty($object_data['updated'])) {
			$object_data['published'] = $object_data['updated'];
		}

		$actor = JsonLD::fetchElement($object, 'as:attributedTo');
		if (empty($actor)) {
			$actor = JsonLD::fetchElement($object, 'as:actor');
		}

		$object_data['diaspora:guid'] = JsonLD::fetchElement($object, 'diaspora:guid');
		$object_data['diaspora:comment'] = JsonLD::fetchElement($object, 'diaspora:comment');
		$object_data['diaspora:like'] = JsonLD::fetchElement($object, 'diaspora:like');
		$object_data['actor'] = $object_data['author'] = $actor;
		$object_data['context'] = JsonLD::fetchElement($object, 'as:context');
		$object_data['conversation'] = JsonLD::fetchElement($object, 'ostatus:conversation');
		$object_data['sensitive'] = JsonLD::fetchElement($object, 'as:sensitive');
		$object_data['name'] = JsonLD::fetchElement($object, 'as:name');
		$object_data['summary'] = JsonLD::fetchElement($object, 'as:summary');
		$object_data['content'] = JsonLD::fetchElement($object, 'as:content');
		$object_data['source'] = JsonLD::fetchElement($object, 'as:source', 'as:content', 'as:mediaType', 'text/bbcode');
		$object_data['start-time'] = JsonLD::fetchElement($object, 'as:startTime', '@value');
		$object_data['end-time'] = JsonLD::fetchElement($object, 'as:endTime', '@value');
		$object_data['location'] = JsonLD::fetchElement($object, 'as:location', 'as:name', '@type', 'as:Place');
		$object_data['latitude'] = JsonLD::fetchElement($object, 'as:location', 'as:latitude', '@type', 'as:Place');
		$object_data['latitude'] = JsonLD::fetchElement($object_data, 'latitude', '@value');
		$object_data['longitude'] = JsonLD::fetchElement($object, 'as:location', 'as:longitude', '@type', 'as:Place');
		$object_data['longitude'] = JsonLD::fetchElement($object_data, 'longitude', '@value');
		$object_data['attachments'] = self::processAttachments(JsonLD::fetchElementArray($object, 'as:attachment'));
		$object_data['tags'] = self::processTags(JsonLD::fetchElementArray($object, 'as:tag'));
		$object_data['generator'] = JsonLD::fetchElement($object, 'as:generator', 'as:name', '@type', 'as:Application');
		$object_data['alternate-url'] = JsonLD::fetchElement($object, 'as:url');

		// Special treatment for Hubzilla links
		if (is_array($object_data['alternate-url'])) {
			$object_data['alternate-url'] = JsonLD::fetchElement($object_data['alternate-url'], 'as:href');

			if (!is_string($object_data['alternate-url'])) {
				$object_data['alternate-url'] = JsonLD::fetchElement($object['as:url'], 'as:href');
			}
		}

		$object_data['receiver'] = self::getReceivers($object, $object_data['actor']);

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
