<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Protocol\ActivityPub;

use Friendica\Content\Text\BBCode;
use Friendica\Database\DBA;
use Friendica\Content\Text\HTML;
use Friendica\Content\Text\Markdown;
use Friendica\Core\Protocol;
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Database\Database;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\APContact;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\User;
use Friendica\Protocol\Activity;
use Friendica\Protocol\ActivityPub;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\HTTPSignature;
use Friendica\Util\JsonLD;
use Friendica\Util\LDSignature;
use Friendica\Util\Network;
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

	const ACCOUNT_TYPES  = ['as:Person', 'as:Organization', 'as:Service', 'as:Group', 'as:Application'];
	const CONTENT_TYPES  = ['as:Note', 'as:Article', 'as:Video', 'as:Image', 'as:Event', 'as:Audio', 'as:Page', 'as:Question'];
	const ACTIVITY_TYPES = ['as:Like', 'as:Dislike', 'as:Accept', 'as:Reject', 'as:TentativeAccept', 'as:View', 'as:Read', 'litepub:EmojiReact'];

	const TARGET_UNKNOWN  = 0;
	const TARGET_TO       = 1;
	const TARGET_CC       = 2;
	const TARGET_BTO      = 3;
	const TARGET_BCC      = 4;
	const TARGET_FOLLOWER = 5;
	const TARGET_ANSWER   = 6;
	const TARGET_GLOBAL   = 7;
	const TARGET_AUDIENCE = 8;

	const COMPLETION_NONE     = 0;
	const COMPLETION_ANNOUNCE = 1;
	const COMPLETION_RELAY    = 2;
	const COMPLETION_MANUAL   = 3;
	const COMPLETION_AUTO     = 4;
	const COMPLETION_ASYNC    = 5;
	const COMPLETION_REPLIES  = 6;

	/**
	 * Checks incoming message from the inbox
	 *
	 * @param string  $body Body string
	 * @param array   $header Header lines
	 * @param integer $uid User ID
	 * @return void
	 * @throws \Exception
	 */
	public static function processInbox(string $body, array $header, int $uid)
	{
		$activity = json_decode($body, true);
		if (empty($activity)) {
			DI::logger()->warning('Invalid body.');
			return;
		}

		$ldactivity = JsonLD::compact($activity);

		$actor = JsonLD::fetchElement($ldactivity, 'as:actor', '@id') ?? '';

		$apcontact = APContact::getByURL($actor);

		if (empty($apcontact)) {
			DI::logger()->notice('Unable to retrieve AP contact for actor - message is discarded', ['actor' => $actor]);
			return;
		} elseif (APContact::isRelay($apcontact) && self::isRelayPost($ldactivity)) {
			self::processRelayPost($ldactivity, $actor);
			return;
		} else {
			APContact::unmarkForArchival($apcontact);
		}

		$sig_contact = HTTPSignature::getKeyIdContact($header);
		if (APContact::isRelay($sig_contact) && self::isRelayPost($ldactivity)) {
			DI::logger()->info('Message from a relay', ['url' => $sig_contact['url']]);
			self::processRelayPost($ldactivity, $sig_contact['url']);
			return;
		}

		$http_signer = HTTPSignature::getSigner($body, $header);
		if ($http_signer === false) {
			DI::logger()->notice('Invalid HTTP signature, message will not be trusted.', ['uid' => $uid, 'actor' => $actor, 'header' => $header, 'body' => $body]);
			$signer = [];
		} elseif (empty($http_signer)) {
			DI::logger()->info('Signer is a tombstone. The message will be discarded, the signer account is deleted.');
			return;
		} else {
			DI::logger()->info('Valid HTTP signature', ['signer' => $http_signer]);
			$signer = [$http_signer];
		}

		DI::logger()->info('Message for user ' . $uid . ' is from actor ' . $actor);

		if ($http_signer === false) {
			$trust_source = false;
		} elseif (LDSignature::isSigned($activity)) {
			$ld_signer = LDSignature::getSigner($activity);
			if (empty($ld_signer)) {
				DI::logger()->info('Invalid JSON-LD signature from ' . $actor);
			} elseif ($ld_signer != $http_signer) {
				$signer[] = $ld_signer;
			}
			if (!empty($ld_signer && ($actor == $http_signer))) {
				DI::logger()->info('The HTTP and the JSON-LD signature belong to ' . $ld_signer);
				$trust_source = true;
			} elseif (!empty($ld_signer)) {
				DI::logger()->info('JSON-LD signature is signed by ' . $ld_signer);
				$trust_source = true;
			} elseif ($actor == $http_signer) {
				DI::logger()->info('Bad JSON-LD signature, but HTTP signer fits the actor.');
				$trust_source = true;
			} else {
				DI::logger()->info('Invalid JSON-LD signature and the HTTP signer is different.');
				$trust_source = false;
			}
		} elseif ($actor == $http_signer) {
			DI::logger()->info('Trusting post without JSON-LD signature, The actor fits the HTTP signer.');
			$trust_source = true;
		} else {
			DI::logger()->info('No JSON-LD signature, different actor.');
			$trust_source = false;
		}

		self::processActivity($ldactivity, $body, $uid, $trust_source, true, $signer, $http_signer);
	}

	/**
	 * Check if the activity is a post rhat can be send via a relay
	 *
	 * @param array $activity
	 * @return boolean
	 */
	private static function isRelayPost(array $activity): bool
	{
		$type = JsonLD::fetchElement($activity, '@type');
		if (!$type) {
			return false;
		}

		$object_type = JsonLD::fetchElement($activity, 'as:object', '@type') ?? '';

		$object_id = JsonLD::fetchElement($activity, 'as:object', '@id');
		if (empty($object_id)) {
			return false;
		}

		$handle = ($type == 'as:Announce');

		if (!$handle && in_array($type, ['as:Create', 'as:Update'])) {
			$handle = in_array($object_type, self::CONTENT_TYPES);
		}
		return $handle;
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
			DI::logger()->notice('Empty type', ['activity' => $activity, 'actor' => $actor]);
			return;
		}

		$object_type = JsonLD::fetchElement($activity, 'as:object', '@type') ?? '';

		$object_id = JsonLD::fetchElement($activity, 'as:object', '@id');
		if (empty($object_id)) {
			DI::logger()->notice('No object id found', ['type' => $type, 'object_type' => $object_type, 'actor' => $actor, 'activity' => $activity]);
			return;
		}

		$contact = Contact::getByURL($actor);
		if (empty($contact)) {
			DI::logger()->info('Relay contact not found', ['actor' => $actor]);
			return;
		}

		if (!in_array($contact['rel'], [Contact::SHARING, Contact::FRIEND])) {
			DI::logger()->notice('Relay is no sharer', ['actor' => $actor]);
			return;
		}

		DI::logger()->debug('Process post from relay server', ['type' => $type, 'object_type' => $object_type, 'object_id' => $object_id, 'actor' => $actor]);

		$item_id = Item::searchByLink($object_id);
		if ($item_id) {
			DI::logger()->info('Relayed message already exists', ['id' => $object_id, 'item' => $item_id, 'actor' => $actor]);
			return;
		}

		if (!DI::config()->get('system', 'decoupled_receiver')) {
			$id = Processor::fetchMissingActivity($object_id, [], $actor, self::COMPLETION_RELAY);
			if (!empty($id)) {
				DI::logger()->notice('Relayed message is fetched', ['result' => $id, 'id' => $object_id, 'actor' => $actor]);
			} else {
				DI::logger()->notice('Relayed message had not been fetched', ['id' => $object_id, 'actor' => $actor, 'activity' => $activity]);
			}
		} elseif (!Fetch::hasWorker($object_id)) {
			DI::logger()->notice('Fetching is done by worker.', ['id' => $object_id]);
			Fetch::add($object_id);
			$wid = Worker::add(Worker::PRIORITY_HIGH, 'FetchMissingActivity', $object_id, [], $actor, self::COMPLETION_RELAY);
			Fetch::setWorkerId($object_id, $wid);
		} else {
			DI::logger()->debug('Activity will already be fetched via a worker.', ['url' => $object_id]);
		}
	}

	/**
	 * Fetches the object type for a given object id
	 *
	 * @param array   $activity
	 * @param string  $object_id Object ID of the provided object
	 * @param integer $uid       User ID
	 *
	 * @return string with object type or NULL
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function fetchObjectType(array $activity, string $object_id, int $uid = 0)
	{
		if (!empty($activity['as:object'])) {
			$object_type = JsonLD::fetchElement($activity['as:object'], '@type');
			if (!empty($object_type)) {
				return $object_type;
			}
		}

		$type = JsonLD::fetchElement($activity, '@type');

		// Several activities are only done on content types, so we can assume "Note" here.
		if (Post::exists(['uri' => $object_id, 'gravity' => [Item::GRAVITY_PARENT, Item::GRAVITY_COMMENT]]) || (in_array($type, ['as:Like', 'as:Dislike', 'litepub:EmojiReact', 'as:Announce', 'as:View']))) {
			// We just assume "note" since it doesn't make a difference for the further processing
			return 'as:Note';
		}

		$profile = APContact::getByURL($object_id, false);
		if (!empty($profile['type'])) {
			APContact::unmarkForArchival($profile);
			return 'as:' . $profile['type'];
		}

		$data = Processor::fetchCachedActivity($object_id, $uid);
		if (!empty($data)) {
			$object = JsonLD::compact($data);
			$type   = JsonLD::fetchElement($object, '@type');
			if (!empty($type)) {
				return $type;
			}
		}

		return null;
	}

	/**
	 * Prepare the object array
	 *
	 * @param array   $activity       Array with activity data
	 * @param integer $uid            User ID
	 * @param boolean $push           Message had been pushed to our system
	 * @param boolean $trust_source   Do we trust the source?
	 * @param string  $original_actor Actor of the original activity. Used for receiver detection. (Optional)
	 *
	 * @return array with object data
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function prepareObjectData(array $activity, int $uid, bool $push, bool &$trust_source, string $original_actor = ''): array
	{
		$id          = JsonLD::fetchElement($activity, '@id');
		$type        = JsonLD::fetchElement($activity, '@type');
		$object_id   = JsonLD::fetchElement($activity, 'as:object', '@id');
		$object_type = '';

		if (!empty($object_id) && in_array($type, ['as:Create', 'as:Update'])) {
			$fetch_id = $object_id;
		} else {
			$fetch_id = $id;
		}

		if (!empty($activity['as:object'])) {
			$object_type = JsonLD::fetchElement($activity['as:object'], '@type');
		}

		$fetched = false;

		if (!empty($id) && !$trust_source) {
			$fetch_uid = $uid ?: self::getBestUserForActivity($activity, $original_actor);

			$fetched_activity = Processor::fetchCachedActivity($fetch_id, $fetch_uid);
			if (!empty($fetched_activity)) {
				$fetched = true;
				$object  = JsonLD::compact($fetched_activity);

				$fetched_id   = JsonLD::fetchElement($object, '@id');
				$fetched_type = JsonLD::fetchElement($object, '@type');

				if (($fetched_id == $id) && !empty($fetched_type) && ($fetched_type == $type)) {
					DI::logger()->info('Activity had been fetched successfully', ['id' => $id]);
					$trust_source = true;
					$activity     = $object;
				} elseif (($fetched_id == $object_id) && !empty($fetched_type) && ($fetched_type == $object_type)) {
					DI::logger()->info('Fetched data is the object instead of the activity', ['id' => $id]);
					$trust_source = true;
					unset($object['@context']);
					$activity['as:object'] = $object;
				} else {
					DI::logger()->info('Activity id is not equal', ['id' => $id, 'fetched' => $fetched_id]);
				}
			} else {
				DI::logger()->info('Activity could not been fetched', ['id' => $id]);
			}
		}

		$actor = JsonLD::fetchElement($activity, 'as:actor', '@id');
		if (empty($actor)) {
			DI::logger()->info('Empty actor', ['activity' => $activity]);
			return [];
		}

		$type = JsonLD::fetchElement($activity, '@type');

		// Fetch all receivers from to, cc, bto and bcc
		$receiverdata = self::getReceivers($activity, $original_actor ?: $actor, [], false, $push || $fetched);
		$receivers    = $reception_types = [];
		foreach ($receiverdata as $key => $data) {
			$receivers[$key]               = $data['uid'];
			$reception_types[$data['uid']] = $data['type'] ?? self::TARGET_UNKNOWN;
		}

		$urls = self::getReceiverURL($activity);

		// When it is a delivery to a personal inbox we add that user to the receivers
		if (!empty($uid)) {
			$additional = [$uid => $uid];
			$receivers  = array_replace($receivers, $additional);
			if (empty($activity['thread-completion']) && (empty($reception_types[$uid]) || in_array($reception_types[$uid], [self::TARGET_UNKNOWN, self::TARGET_FOLLOWER, self::TARGET_ANSWER, self::TARGET_GLOBAL]))) {
				$reception_types[$uid] = self::TARGET_BCC;

				$owner = User::getOwnerDataById($uid);
				if (!empty($owner['url'])) {
					$urls['as:bcc'][] = $owner['url'];
				}
			}
		}

		// We possibly need some user to fetch private content,
		// so we fetch one out of the receivers if no uid is provided.
		$fetch_uid = $uid ?: self::getBestUserForActivity($activity, $original_actor);

		$object_id = JsonLD::fetchElement($activity, 'as:object', '@id');
		if (empty($object_id)) {
			DI::logger()->info('No object found');
			return [];
		}

		if (!is_string($object_id)) {
			DI::logger()->info('Invalid object id', ['object' => $object_id]);
			return [];
		}

		$object_type = self::fetchObjectType($activity, $object_id, $fetch_uid);

		// Any activities on account types must not be altered
		if (in_array($type, ['as:Flag'])) {
			$object_data = [];

			$object_data['id']         = JsonLD::fetchElement($activity, '@id');
			$object_data['object_id']  = JsonLD::fetchElement($activity, 'as:object', '@id');
			$object_data['object_ids'] = JsonLD::fetchElementArray($activity, 'as:object', '@id');
			$object_data['content']    = JsonLD::fetchElement($activity, 'as:content', '@type');
		} elseif (in_array($object_type, self::ACCOUNT_TYPES)) {
			$object_data = [];

			$object_data['id']            = JsonLD::fetchElement($activity, '@id');
			$object_data['object_id']     = JsonLD::fetchElement($activity, 'as:object', '@id');
			$object_data['object_actor']  = JsonLD::fetchElement($activity['as:object'], 'as:actor', '@id');
			$object_data['object_object'] = JsonLD::fetchElement($activity['as:object'], 'as:object');
			$object_data['object_type']   = JsonLD::fetchElement($activity['as:object'], '@type');
			if (!$trust_source && ($type == 'as:Delete')) {
				$apcontact    = APContact::getByURL($object_data['object_id'], true);
				$trust_source = empty($apcontact) || ($apcontact['type'] == 'Tombstone') || $apcontact['suspended'];
			}
		} elseif (in_array($type, ['as:Create', 'as:Update', 'as:Invite']) || strpos($type, '#emojiReaction')) {
			// Fetch the content only on activities where this matters
			// We can receive "#emojiReaction" when fetching content from Hubzilla systems
			$object_data = self::fetchObject($object_id, $activity['as:object'], $trust_source, $fetch_uid);
			if (empty($object_data)) {
				DI::logger()->info("Object data couldn't be processed");
				return [];
			}

			$object_data['object_id'] = $object_id;

			// Test if it is a direct message
			if (self::checkForDirectMessage($object_data, $activity)) {
				$object_data['directmessage'] = true;
			} elseif (!empty(JsonLD::fetchElement($activity['as:object'], 'misskey:_misskey_talk'))) {
				$object_data = self::setChatData($object_data, $receivers);
			}
		} elseif (in_array($type, array_merge(self::ACTIVITY_TYPES, ['as:Announce', 'as:Follow'])) && in_array($object_type, self::CONTENT_TYPES)) {
			// Create a mostly empty array out of the activity data (instead of the object).
			// This way we later don't have to check for the existence of each individual array element.
			$object_data = self::processObject($activity, $original_actor);

			$object_data['name']        = $type;
			$object_data['author']      = JsonLD::fetchElement($activity, 'as:actor', '@id');
			$object_data['object_id']   = $object_id;
			$object_data['object_type'] = ''; // Since we don't fetch the object, we don't know the type
		} elseif (in_array($type, ['as:Add', 'as:Remove', 'as:Move'])) {
			$object_data = [];

			$object_data['id']             = JsonLD::fetchElement($activity, '@id');
			$object_data['target_id']      = JsonLD::fetchElement($activity, 'as:target', '@id');
			$object_data['object_id']      = JsonLD::fetchElement($activity, 'as:object', '@id');
			$object_data['object_type']    = JsonLD::fetchElement($activity['as:object'], '@type');
			$object_data['object_content'] = JsonLD::fetchElement($activity['as:object'], 'as:content', '@type');
		} else {
			$object_data = [];

			$object_data['id']            = JsonLD::fetchElement($activity, '@id');
			$object_data['object_id']     = JsonLD::fetchElement($activity, 'as:object', '@id');
			$object_data['object_actor']  = JsonLD::fetchElement($activity['as:object'], 'as:actor', '@id');
			$object_data['object_object'] = JsonLD::fetchElement($activity['as:object'], 'as:object');
			$object_data['object_type']   = JsonLD::fetchElement($activity['as:object'], '@type');

			// An Undo is done on the object of an object, so we need that type as well
			if (($type == 'as:Undo') && !empty($object_data['object_object'])) {
				$object_data['object_object_type'] = self::fetchObjectType([], $object_data['object_object'], $fetch_uid);
			}

			if (!$trust_source && ($type == 'as:Delete') && in_array($object_data['object_type'], array_merge(['as:Tombstone', ''], self::CONTENT_TYPES))) {
				$trust_source = Processor::isActivityGone($object_data['object_id']);
				if (!$trust_source) {
					$trust_source = !empty(APContact::getByURL($object_data['object_id'], false));
				}
			}
		}

		$object_data['push'] = $push;

		$object_data = self::addActivityFields($object_data, $activity);

		if (empty($object_data['object_type'])) {
			$object_data['object_type'] = $object_type;
		}

		foreach (['as:to', 'as:cc', 'as:bto', 'as:bcc', 'as:audience', 'as:attributedTo'] as $element) {
			if ((empty($object_data['receiver_urls'][$element]) || in_array($element, ['as:bto', 'as:bcc'])) && !empty($urls[$element])) {
				$object_data['receiver_urls'][$element] = array_unique(array_merge($object_data['receiver_urls'][$element] ?? [], $urls[$element]));
			}
		}

		$object_data['type']           = $type;
		$object_data['actor']          = $actor;
		$object_data['item_receiver']  = $receivers;
		$object_data['receiver']       = array_replace($object_data['receiver'] ?? [], $receivers);
		$object_data['reception_type'] = array_replace($object_data['reception_type'] ?? [], $reception_types);

		$account  = Contact::selectFirstAccount(['platform'], ['nurl' => Strings::normaliseLink($actor)]);
		$platform = $account['platform'] ?? '';

		DI::logger()->info('Processing', ['type' => $object_data['type'], 'object_type' => $object_data['object_type'], 'id' => $object_data['id'], 'actor' => $actor, 'platform' => $platform]);

		return $object_data;
	}

	/**
	 * Check if the received message is a direct message
	 *
	 * @param array $object_data
	 * @param array $activity
	 * @return boolean
	 */
	private static function checkForDirectMessage(array $object_data, array $activity): bool
	{
		if (DBA::exists('mail', ['uri' => $object_data['reply-to-id']])) {
			return true;
		}

		if ($object_data['id'] != $object_data['reply-to-id']) {
			return false;
		}

		if (JsonLD::fetchElement($activity, 'litepub:directMessage')) {
			return true;
		}

		if (!empty($object_data['attachments'])) {
			return false;
		}

		if (!empty($object_data['receiver_urls']['as:cc']) || empty($object_data['receiver_urls']['as:to'])) {
			return false;
		}

		if ((count($object_data['receiver_urls']['as:to']) != 1) || !User::getIdForURL($object_data['receiver_urls']['as:to'][0])) {
			return false;
		}

		$mentions = 0;
		foreach ($object_data['tags'] as $mention) {
			if ($mention['type'] != 'Mention') {
				continue;
			}
			if (!User::getIdForURL($mention['href'])) {
				return false;
			}
			++$mentions;
		}

		if ($mentions > 1) {
			return false;
		}

		return true;
	}

	private static function setChatData(array $object_data, array $receivers): array
	{
		if (count($receivers) != 1) {
			return $object_data;
		}

		$user = User::getById(array_key_first($receivers), ['language']);
		$l10n = DI::l10n()->withLang($user['language']);

		$object_data['name'] = $l10n->t('Chat');

		$mail = DBA::selectFirst('mail', ['uri'], ['uid' => array_key_first($receivers), 'title' => $object_data['name']], ['order' => ['id' => true]]);
		if (!empty($mail['uri'])) {
			$object_data['reply-to-id'] = $mail['uri'];
		}

		$object_data['directmessage'] = true;
		DI::logger()->debug('Got Misskey Chat');
		return $object_data;
	}

	/**
	 * Fetches the first user id from the receiver array
	 *
	 * @param array $receivers Array with receivers
	 * @return integer user id;
	 */
	public static function getFirstUserFromReceivers(array $receivers): int
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
	 * @param array      $activity     Array with activity data
	 * @param string     $body         The unprocessed body
	 * @param int|null   $uid          User ID
	 * @param boolean    $trust_source Do we trust the source?
	 * @param boolean    $push         Message had been pushed to our system
	 * @param array      $signer       The signer of the post
	 * @param string     $http_signer
	 * @param int        $completion
	 *
	 * @return bool
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function processActivity(array $activity, string $body = '', int $uid = null, bool $trust_source = false, bool $push = false, array $signer = [], string $http_signer = '', int $completion = Receiver::COMPLETION_AUTO): bool
	{
		$type = JsonLD::fetchElement($activity, '@type');
		if (!$type) {
			DI::logger()->info('Empty type', ['activity' => $activity]);
			return true;
		}

		if (!DI::config()->get('system', 'process_view') && ($type == 'as:View')) {
			DI::logger()->info('View activities are ignored.', ['signer' => $signer, 'http_signer' => $http_signer]);
			return true;
		}

		if (!JsonLD::fetchElement($activity, 'as:object', '@id')) {
			DI::logger()->info('Empty object', ['activity' => $activity]);
			return true;
		}

		$actor = JsonLD::fetchElement($activity, 'as:actor', '@id');
		if ($actor === null || $actor === '') {
			DI::logger()->info('Empty actor', ['activity' => $activity]);
			return true;
		}

		if (is_array($activity['as:object'])) {
			$attributed_to = JsonLD::fetchElement($activity['as:object'], 'as:attributedTo', '@id');
			$published     = JsonLD::fetchElement($activity['as:object'], 'as:published', '@value');
			$object_type   = JsonLD::fetchElement($activity['as:object'], '@type');
			$id            = JsonLD::fetchElement($activity, '@id');
			$object_id     = JsonLD::fetchElement($activity, 'as:object', '@id');

			if (!empty($published) && $object_id !== null && in_array($type, ['as:Create', 'as:Update']) && in_array($object_type, self::CONTENT_TYPES)
				&& ($push || ($completion != self::COMPLETION_MANUAL)) && DI::contentItem()->isTooOld($published) && !Post::exists(['uri' => $object_id])) {
				DI::logger()->debug('Activity is too old. It will not be processed', ['push' => $push, 'completion' => $completion, 'type' => $type,  'object-type' => $object_type, 'published' => $published, 'id' => $id, 'object-id' => $object_id]);
				return true;
			}
		} else {
			$attributed_to = '';
		}

		// Test the provided signatures against the actor and "attributedTo"
		if ($trust_source) {
			if ($attributed_to !== false && $attributed_to !== '') {
				$trust_source = (in_array($actor, $signer) && in_array($attributed_to, $signer));
			} else {
				$trust_source = in_array($actor, $signer);
			}
		}

		// Lemmy announces activities.
		// To simplify the further processing, we modify the received object.
		// For announced "create" activities we remove the middle layer.
		// For the rest (like, dislike, update, ...) we just process the activity directly.
		$original_actor = '';
		if (($type == 'as:Announce') && !empty($object_type) && !in_array($object_type, self::CONTENT_TYPES) && self::isGroup($actor)) {
			$object_object_type = JsonLD::fetchElement($activity['as:object']['as:object'] ?? [], '@type');
			if (in_array($object_type, ['as:Create']) && in_array($object_object_type, self::CONTENT_TYPES)) {
				DI::logger()->debug('Replace "create" activity with inner object', ['type' => $object_type, 'object_type' => $object_object_type]);
				$activity['as:object'] = $activity['as:object']['as:object'];
			} elseif (in_array($object_type, array_merge(self::ACTIVITY_TYPES, ['as:Delete', 'as:Undo', 'as:Update']))) {
				DI::logger()->debug('Change announced activity to activity', ['type' => $object_type]);
				$original_actor = $actor;
				$type           = $object_type;
				$activity       = $activity['as:object'];
			} else {
				DI::logger()->info('Unhandled announced activity', ['type' => $object_type, 'object_type' => $object_object_type]);
			}
		}

		// $trust_source is called by reference and is set to true if the content was retrieved successfully
		$object_data = self::prepareObjectData($activity, $uid, $push, $trust_source, $original_actor);
		if (empty($object_data)) {
			DI::logger()->info('No object data found', ['activity' => $activity]);
			return true;
		}

		if (!empty($body) && empty($object_data['raw'])) {
			$object_data['raw'] = $body;
		}

		// Internal flag for thread completion. See Processor.php
		if (!empty($activity['thread-completion'])) {
			$object_data['thread-completion'] = $activity['thread-completion'];
		}

		if (!empty($activity['completion-mode'])) {
			$object_data['completion-mode'] = $activity['completion-mode'];
		}

		if (!empty($activity['thread-children-type'])) {
			$object_data['thread-children-type'] = $activity['thread-children-type'];
		}

		// Internal flag for posts that arrived via relay
		if (!empty($activity['from-relay'])) {
			$object_data['from-relay'] = $activity['from-relay'];
		}

		if ($type == 'as:Announce') {
			$object_data['object_activity'] = $activity;
		}

		if (($type == 'as:Create') && $trust_source && !in_array($completion, [self::COMPLETION_MANUAL, self::COMPLETION_ANNOUNCE])) {
			if (self::hasArrived($object_data['object_id'])) {
				DI::logger()->info('The activity already arrived.', ['id' => $object_data['object_id']]);
				return true;
			}
			self::addArrivedId($object_data['object_id']);

			if (Queue::exists($object_data['object_id'], $type)) {
				DI::logger()->info('The activity is already added.', ['id' => $object_data['object_id']]);
				return true;
			}
		} elseif (($type == 'as:Create') && $trust_source && !self::hasArrived($object_data['object_id'])) {
			self::addArrivedId($object_data['object_id']);
		}

		$object_data['children']  = $activity['children']  ?? [];
		$object_data['callstack'] = $activity['callstack'] ?? [];

		$decouple = DI::config()->get('system', 'decoupled_receiver') && !in_array($completion, [self::COMPLETION_MANUAL, self::COMPLETION_ANNOUNCE]) && empty($object_data['directmessage']);

		if ($decouple && ($trust_source || DI::config()->get('debug', 'ap_inbox_store_untrusted'))) {
			$object_data = Queue::add($object_data, $type, $uid, $http_signer, $push, $trust_source);
		}

		if (!$trust_source) {
			DI::logger()->info('Activity trust could not be achieved.', ['id' => $object_data['object_id'], 'type' => $type, 'signer' => $signer, 'actor' => $actor, 'attributedTo' => $attributed_to]);
			return true;
		}

		if (!empty($object_data['entry-id']) && $decouple && ($push || in_array($completion, [self::COMPLETION_RELAY, self::COMPLETION_ASYNC]))) {
			if (Queue::isProcessable($object_data['entry-id'])) {
				// We delay by 5 seconds to allow to accumulate all receivers
				$delayed = date(DateTimeFormat::MYSQL, time() + 5);
				DI::logger()->debug('Initiate processing', ['id' => $object_data['entry-id'], 'uri' => $object_data['object_id']]);
				$wid = Worker::add(['priority' => Worker::PRIORITY_HIGH, 'delayed' => $delayed], 'ProcessQueue', $object_data['entry-id']);
				Queue::setWorkerId($object_data['entry-id'], $wid);
			} else {
				DI::logger()->debug('Other queue entries need to be processed first.', ['id' => $object_data['entry-id']]);
			}
			return false;
		}

		if (!empty($activity['recursion-depth'])) {
			$object_data['recursion-depth'] = $activity['recursion-depth'];
		}

		if (!self::routeActivities($object_data, $type, $push, true, $uid)) {
			self::storeUnhandledActivity(true, $type, $object_data, $activity, $body, $uid, $trust_source, $push, $signer);
			Queue::remove($object_data);
		}
		return true;
	}

	/**
	 * Checks if the provided actor is a group account
	 *
	 * @param string $actor
	 * @return boolean
	 */
	private static function isGroup(string $actor): bool
	{
		$profile = APContact::getByURL($actor);
		return ($profile['type'] ?? '') == 'Group';
	}

	/**
	 * Route activities
	 *
	 * @param array  $object_data
	 * @param string $type
	 * @param bool   $push
	 * @param bool   $fetch_parents
	 * @param int    $uid
	 *
	 * @return boolean Could the activity be routed?
	 */
	public static function routeActivities(array $object_data, string $type, bool $push, bool $fetch_parents = true, int $uid = 0): bool
	{
		switch ($type) {
			case 'as:Create':
				if (in_array($object_data['object_type'], self::CONTENT_TYPES)) {
					$item = ActivityPub\Processor::createItem($object_data, $fetch_parents);
					ActivityPub\Processor::postItem($object_data, $item);
				} elseif (in_array($object_data['object_type'], ['pt:CacheFile'])) {
					// Unhandled Peertube activity
					Queue::remove($object_data);
				} elseif (in_array($object_data['object_type'], self::ACCOUNT_TYPES)) {
					ActivityPub\Processor::updatePerson($object_data);
				} else {
					return false;
				}
				break;

			case 'as:Invite':
				if (in_array($object_data['object_type'], ['as:Event'])) {
					$item = ActivityPub\Processor::createItem($object_data, $fetch_parents);
					ActivityPub\Processor::postItem($object_data, $item);
				} else {
					return false;
				}
				break;

			case 'as:Add':
				if ($object_data['object_type'] == 'as:tag') {
					ActivityPub\Processor::addTag($object_data);
				} elseif (in_array($object_data['object_type'], self::CONTENT_TYPES)) {
					ActivityPub\Processor::addToFeaturedCollection($object_data);
				} elseif (in_array($object_data['object_type'], ['as:Tombstone', ''])) {
					// We don't have the object here or it is deleted. We ignore this activity.
					Queue::remove($object_data);
				} else {
					return false;
				}
				break;

			case 'as:Announce':
				if (in_array($object_data['object_type'], self::CONTENT_TYPES)) {
					if (!Processor::alreadyKnown($object_data['object_id'], '')) {
						if (ActivityPub\Processor::fetchMissingActivity($object_data['object_id'], [], $object_data['actor'], self::COMPLETION_ANNOUNCE, $uid)) {
							DI::logger()->debug('Created announced id', ['uid' => $uid, 'id' => $object_data['object_id']]);
							Queue::remove($object_data);
						} else {
							DI::logger()->debug('Announced id was not created', ['uid' => $uid, 'id' => $object_data['object_id']]);
							Queue::remove($object_data);
							return true;
						}
					} else {
						DI::logger()->info('Announced id already exists', ['uid' => $uid, 'id' => $object_data['object_id']]);
						Queue::remove($object_data);
					}

					ActivityPub\Processor::createActivity($object_data, Activity::ANNOUNCE);
				} elseif (in_array($object_data['object_type'], ['as:Tombstone', ''])) {
					// We don't have the object here or it is deleted. We ignore this activity.
					Queue::remove($object_data);
				} else {
					return false;
				}
				break;

			case 'as:Like':
				if (in_array($object_data['object_type'], self::CONTENT_TYPES)) {
					ActivityPub\Processor::createActivity($object_data, Activity::LIKE);
				} elseif (in_array($object_data['object_type'], ['as:Tombstone', ''])) {
					// We don't have the object here or it is deleted. We ignore this activity.
					Queue::remove($object_data);
				} else {
					return false;
				}
				break;

			case 'as:Dislike':
				if (in_array($object_data['object_type'], self::CONTENT_TYPES)) {
					ActivityPub\Processor::createActivity($object_data, Activity::DISLIKE);
				} elseif (in_array($object_data['object_type'], ['as:Tombstone', ''])) {
					// We don't have the object here or it is deleted. We ignore this activity.
					Queue::remove($object_data);
				} else {
					return false;
				}
				break;

			case 'as:TentativeAccept':
				if (in_array($object_data['object_type'], self::CONTENT_TYPES)) {
					ActivityPub\Processor::createActivity($object_data, Activity::ATTENDMAYBE);
				} else {
					return false;
				}
				break;

			case 'as:Update':
				if (in_array($object_data['object_type'], self::CONTENT_TYPES)) {
					ActivityPub\Processor::updateItem($object_data);
				} elseif (in_array($object_data['object_type'], self::ACCOUNT_TYPES)) {
					ActivityPub\Processor::updatePerson($object_data);
				} elseif (in_array($object_data['object_type'], ['pt:CacheFile'])) {
					// Unhandled Peertube activity
					Queue::remove($object_data);
				} else {
					return false;
				}
				break;

			case 'as:Delete':
				if (in_array($object_data['object_type'], array_merge(['as:Tombstone'], self::CONTENT_TYPES))) {
					ActivityPub\Processor::deleteItem($object_data);
				} elseif (in_array($object_data['object_type'], self::ACCOUNT_TYPES)) {
					ActivityPub\Processor::deletePerson($object_data);
				} elseif ($object_data['object_type'] == '') {
					// The object type couldn't be determined. Most likely we don't have it here. We ignore this activity.
					Queue::remove($object_data);
				} else {
					return false;
				}
				break;

			case 'as:Move':
				if (in_array($object_data['object_type'], self::ACCOUNT_TYPES)) {
					ActivityPub\Processor::movePerson($object_data);
				} else {
					return false;
				}
				break;

			case 'as:Block':
				if (in_array($object_data['object_type'], self::ACCOUNT_TYPES)) {
					ActivityPub\Processor::blockAccount($object_data);
				} else {
					return false;
				}
				break;

			case 'as:Flag':
				if (in_array($object_data['object_type'], self::ACCOUNT_TYPES)) {
					ActivityPub\Processor::ReportAccount($object_data);
				} else {
					return false;
				}
				break;

			case 'as:Remove':
				if (in_array($object_data['object_type'], self::CONTENT_TYPES)) {
					ActivityPub\Processor::removeFromFeaturedCollection($object_data);
				} elseif (in_array($object_data['object_type'], ['as:Tombstone', ''])) {
					// We don't have the object here or it is deleted. We ignore this activity.
					Queue::remove($object_data);
				} else {
					return false;
				}
				break;

			case 'as:Follow':
				if (in_array($object_data['object_type'], self::ACCOUNT_TYPES)) {
					ActivityPub\Processor::followUser($object_data);
				} elseif (in_array($object_data['object_type'], self::CONTENT_TYPES)) {
					$object_data['reply-to-id'] = $object_data['object_id'];
					ActivityPub\Processor::createActivity($object_data, Activity::FOLLOW);
				} else {
					return false;
				}
				break;

			case 'as:Accept':
				if ($object_data['object_type'] == 'as:Follow') {
					if (!empty($object_data['object_actor'])) {
						ActivityPub\Processor::acceptFollowUser($object_data);
					} else {
						DI::logger()->notice('Unhandled "accept follow" message.', ['object_data' => $object_data]);
					}
				} elseif (in_array($object_data['object_type'], self::CONTENT_TYPES)) {
					ActivityPub\Processor::createActivity($object_data, Activity::ATTEND);
				} elseif (!empty($object_data['object_id']) && empty($object_data['object_actor']) && empty($object_data['object_type'])) {
					// Follow acceptances from gup.pe only contain the object id
					ActivityPub\Processor::acceptFollowUser($object_data);
				} else {
					return false;
				}
				break;

			case 'as:Reject':
				if ($object_data['object_type'] == 'as:Follow') {
					ActivityPub\Processor::rejectFollowUser($object_data);
				} elseif (in_array($object_data['object_type'], self::CONTENT_TYPES)) {
					ActivityPub\Processor::createActivity($object_data, Activity::ATTENDNO);
				} else {
					return false;
				}
				break;

			case 'as:Undo':
				if (($object_data['object_type'] == 'as:Follow') &&
					in_array($object_data['object_object_type'], self::ACCOUNT_TYPES)) {
					ActivityPub\Processor::undoFollowUser($object_data);
				} elseif (($object_data['object_type'] == 'as:Follow') &&
					in_array($object_data['object_object_type'], self::CONTENT_TYPES)) {
					ActivityPub\Processor::undoActivity($object_data);
				} elseif (($object_data['object_type'] == 'as:Accept') &&
					in_array($object_data['object_object_type'], self::ACCOUNT_TYPES)) {
					ActivityPub\Processor::rejectFollowUser($object_data);
				} elseif (($object_data['object_type'] == 'as:Block') &&
					in_array($object_data['object_object_type'], self::ACCOUNT_TYPES)) {
					ActivityPub\Processor::unblockAccount($object_data);
				} elseif (in_array($object_data['object_type'], array_merge(self::ACTIVITY_TYPES, ['as:Announce', 'as:Create', ''])) &&
					empty($object_data['object_object_type'])) {
					// We cannot detect the target object. So we can ignore it.
					Queue::remove($object_data);
				} elseif (in_array($object_data['object_type'], array_merge(self::ACTIVITY_TYPES, ['as:Announce'])) &&
					in_array($object_data['object_object_type'], array_merge(['as:Tombstone'], self::CONTENT_TYPES))) {
					ActivityPub\Processor::undoActivity($object_data);
				} elseif (in_array($object_data['object_type'], ['as:Create']) &&
					in_array($object_data['object_object_type'], ['pt:CacheFile'])) {
					// Unhandled Peertube activity
					Queue::remove($object_data);
				} elseif (in_array($object_data['object_type'], ['as:Delete'])) {
					// We cannot undo deletions, so we just ignore this
					Queue::remove($object_data);
				} elseif (in_array($object_data['object_object_type'], ['as:Tombstone'])) {
					// The object is a tombstone, we ignore any actions on it.
					Queue::remove($object_data);
				} else {
					return false;
				}
				break;

			case 'as:View':
				if (in_array($object_data['object_type'], self::CONTENT_TYPES)) {
					ActivityPub\Processor::createActivity($object_data, Activity::VIEW);
				} elseif (in_array($object_data['object_type'], ['as:Tombstone', ''])) {
					// We don't have the object here or it is deleted. We ignore this activity.
					Queue::remove($object_data);
				} else {
					return false;
				}
				break;
			case 'as:Read':
				if (in_array($object_data['object_type'], self::CONTENT_TYPES)) {
					ActivityPub\Processor::createActivity($object_data, Activity::READ);
				} elseif (in_array($object_data['object_type'], ['as:Tombstone', ''])) {
					// We don't have the object here or it is deleted. We ignore this activity.
					Queue::remove($object_data);
				} else {
					return false;
				}
				break;

			case 'litepub:EmojiReact':
				if (in_array($object_data['object_type'], self::CONTENT_TYPES)) {
					ActivityPub\Processor::createActivity($object_data, Activity::EMOJIREACT);
				} elseif (in_array($object_data['object_type'], ['as:Tombstone', ''])) {
					// We don't have the object here or it is deleted. We ignore this activity.
					Queue::remove($object_data);
				} else {
					return false;
				}
				break;

			default:
				DI::logger()->info('Unknown activity: ' . $type . ' ' . $object_data['object_type']);
				return false;
		}
		return true;
	}

	/**
	 * Stores unhandled or unknown Activities as a file
	 *
	 * @param boolean $unknown      "true" if the activity is unknown, "false" if it is unhandled
	 * @param string  $type         Activity type
	 * @param array   $object_data  Preprocessed array that is generated out of the received activity
	 * @param array   $activity     Array with activity data
	 * @param string  $body         The unprocessed body
	 * @param integer $uid          User ID
	 * @param boolean $trust_source Do we trust the source?
	 * @param boolean $push         Message had been pushed to our system
	 * @param array   $signer       The signer of the post
	 * @return void
	 */
	private static function storeUnhandledActivity(bool $unknown, string $type, array $object_data, array $activity, string $body = '', int $uid = null, bool $trust_source = false, bool $push = false, array $signer = [])
	{
		if (!DI::config()->get('debug', 'ap_log_unknown')) {
			return;
		}

		$file = ($unknown  ? 'unknown-' : 'unhandled-') . str_replace(':', '-', $type) . '-';

		if (!empty($object_data['object_type'])) {
			$file .= str_replace(':', '-', $object_data['object_type']) . '-';
		}

		if (!empty($object_data['object_object_type'])) {
			$file .= str_replace(':', '-', $object_data['object_object_type']) . '-';
		}

		$tempfile = tempnam(System::getTempPath(), $file);
		file_put_contents($tempfile, json_encode(['activity' => $activity, 'body' => $body, 'uid' => $uid, 'trust_source' => $trust_source, 'push' => $push, 'signer' => $signer, 'object_data' => $object_data], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
		DI::logger()->notice('Unknown activity stored', ['type' => $type, 'object_type' => $object_data['object_type'], 'object_object_type' => $object_data['object_object_type'] ?? '', 'file' => $tempfile]);
	}

	/**
	 * Fetch a user id from an activity array
	 *
	 * @param array  $activity
	 * @param string $actor
	 *
	 * @return int   user id
	 */
	private static function getBestUserForActivity(array $activity, string $actor): int
	{
		$uid   = 0;
		$actor = $actor ?: JsonLD::fetchElement($activity, 'as:actor', '@id') ?? '';

		$receivers = self::getReceivers($activity, $actor, [], false, false);
		foreach ($receivers as $receiver) {
			if ($receiver['type'] == self::TARGET_GLOBAL) {
				return 0;
			}
			if (empty($uid) || ($receiver['type'] == self::TARGET_TO)) {
				$uid = $receiver['uid'];
			}
		}

		// When we haven't found any user yet, we just chose a user who most likely could have access to the content
		if (empty($uid)) {
			$contact = Contact::selectFirst(['uid'], ['nurl' => Strings::normaliseLink($actor), 'rel' => [Contact::SHARING, Contact::FRIEND]]);
			if (!empty($contact['uid'])) {
				$uid = $contact['uid'];
			}
		}

		return $uid;
	}

	// @TODO Missing documentation
	public static function getReceiverURL(array $activity): array
	{
		$urls = [];

		foreach (['as:to', 'as:cc', 'as:bto', 'as:bcc', 'as:audience', 'as:attributedTo'] as $element) {
			$receiver_list = JsonLD::fetchElementArray($activity, $element, '@id');
			if (empty($receiver_list)) {
				continue;
			}

			foreach ($receiver_list as $receiver) {
				if ($receiver == 'Public') {
					DI::logger()->warning('Not compacted public collection found', ['activity' => $activity]);
					$receiver = ActivityPub::PUBLIC_COLLECTION;
				}
				if ($receiver == self::PUBLIC_COLLECTION) {
					$receiver = ActivityPub::PUBLIC_COLLECTION;
				}
				$urls[$element][] = $receiver;
			}
		}

		return $urls;
	}

	/**
	 * Fetch the receiver list from an activity array
	 *
	 * @param array   $activity
	 * @param string $actor
	 * @param array  $tags
	 * @param bool   $fetch_unlisted
	 * @param bool   $push
	 *
	 * @return array with receivers (user id)
	 * @throws \Exception
	 */
	private static function getReceivers(array $activity, string $actor, array $tags, bool $fetch_unlisted, bool $push): array
	{
		$reply = $receivers = $profile = [];

		// When it is an answer, we inherit the receivers from the parent
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

		if (!empty($actor)) {
			$profile   = APContact::getByURL($actor);
			$followers = $profile['followers'] ?? '';
			$isGroup   = ($profile['type'] ?? '') == 'Group';
			DI::logger()->info('Got actor and followers', ['actor' => $actor, 'followers' => $followers]);
		} else {
			DI::logger()->info('Empty actor', ['activity' => $activity]);
			$followers = '';
			$isGroup   = false;
		}

		$parent_followers = '';
		$parent_profile   = [];

		$parent = Post::selectFirstPost(['parent-author-link'], ['uri' => $reply]);
		if (!empty($parent['parent-author-link'])) {
			$parent_profile = APContact::getByURL($parent['parent-author-link']);
			if (!in_array($parent_profile['followers'] ?? '', ['', $followers])) {
				$parent_followers = $parent_profile['followers'];
			}
		}

		// We have to prevent false follower assumptions upon thread completions
		$follower_target = empty($activity['thread-completion']) ? self::TARGET_FOLLOWER : self::TARGET_UNKNOWN;

		foreach (['as:to', 'as:cc', 'as:bto', 'as:bcc', 'as:audience'] as $element) {
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
				if ((($receiver == $followers) || (($receiver == self::PUBLIC_COLLECTION) && !$isGroup) || ($isGroup && ($element == 'as:audience'))) && !empty($profile)) {
					$receivers = self::getReceiverForActor($tags, $receivers, $follower_target, $profile);
					continue;
				}

				if ($receiver == $parent_followers) {
					$receivers = self::getReceiverForActor([], $receivers, $follower_target, $parent_profile);
					continue;
				}

				// Fetching all directly addressed receivers
				$condition = ['self' => true, 'nurl' => Strings::normaliseLink($receiver)];
				$contact   = DBA::selectFirst('contact', ['uid', 'contact-type'], $condition);
				if (!DBA::isResult($contact)) {
					continue;
				}

				// Check if the potential receiver is following the actor
				// Exception: The receiver is targetted via "to" or this is a comment
				if ((($element != 'as:to') && empty($replyto)) || ($contact['contact-type'] == Contact::TYPE_COMMUNITY)) {
					$networks  = Protocol::FEDERATED;
					$condition = [
						'nurl'    => Strings::normaliseLink($actor), 'rel' => [Contact::SHARING, Contact::FRIEND],
						'network' => $networks, 'archive' => false, 'pending' => false, 'uid' => $contact['uid']
					];

					// Group posts are only accepted from group contacts
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
						case 'as:audience':
							$type = self::TARGET_AUDIENCE;
							break;
					}

					$receivers[$contact['uid']] = ['uid' => $contact['uid'], 'type' => $type];
				}
			}
		}

		if (empty($receivers) && !empty($parent['parent-author-link'])) {
			$uid = User::getIdForURL($parent['parent-author-link']);

			$receivers[$uid] = ['uid' => $uid, 'type' => self::TARGET_BTO];
		}

		if (!empty($reply) && (!empty($receivers[0]) || !empty($receivers[-1]))) {
			$parents = Post::select(['uid'], DBA::mergeConditions(['uri' => $reply], ["`uid` != ?", 0]));
			while ($parent = Post::fetch($parents)) {
				$receivers[$parent['uid']] = ['uid' => $parent['uid'], 'type' => self::TARGET_ANSWER];
			}
			DBA::close($parents);
		}

		// "birdsitelive" is a service that mirrors tweets into the fediverse
		// These posts can be fetched without authentication, but are not marked as public
		// We treat them as unlisted posts to be able to handle them.
		// We always process deletion activities.
		$activity_type = JsonLD::fetchElement($activity, '@type');
		if (empty($receivers) && $fetch_unlisted && Contact::isPlatform($actor, 'birdsitelive')) {
			$receivers[0]  = ['uid' => 0, 'type' => self::TARGET_GLOBAL];
			$receivers[-1] = ['uid' => -1, 'type' => self::TARGET_GLOBAL];
			DI::logger()->notice('Post from "birdsitelive" is set to "unlisted"', ['id' => JsonLD::fetchElement($activity, '@id')]);
		} elseif (empty($receivers) && in_array($activity_type, ['as:Delete', 'as:Undo'])) {
			$receivers[0] = ['uid' => 0, 'type' => self::TARGET_GLOBAL];
		} elseif (empty($receivers)) {
			DI::logger()->notice('Post has got no receivers', ['fetch_unlisted' => $fetch_unlisted, 'actor' => $actor, 'id' => JsonLD::fetchElement($activity, '@id'), 'type' => $activity_type]);
		}

		return $receivers;
	}

	/**
	 * Fetch the receiver list of a given actor
	 *
	 * @param array   $tags
	 * @param array   $receivers
	 * @param integer $target_type
	 * @param array   $profile
	 *
	 * @return array with receivers (user id)
	 * @throws \Exception
	 */
	private static function getReceiverForActor(array $tags, array $receivers, int $target_type, array $profile): array
	{
		$basecondition = [
			'rel'     => [Contact::SHARING, Contact::FRIEND, Contact::FOLLOWER],
			'network' => Protocol::FEDERATED, 'archive' => false, 'pending' => false
		];

		$condition = DBA::mergeConditions($basecondition, ["`uri-id` = ? AND `uid` != ?", $profile['uri-id'], 0]);
		$contacts  = DBA::select('contact', ['uid', 'rel'], $condition);
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
	 * @param array  $tags
	 *
	 * @return bool with receivers (user id)
	 * @throws \Exception
	 */
	private static function isValidReceiverForActor(array $contact, array $tags): bool
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
	 * @TODO Fix documentation and type-hints
	 *
	 * @param       $object_data
	 * @param array $activity
	 *
	 * @return mixed
	 */
	private static function addActivityFields($object_data, array $activity)
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
				DI::logger()->notice('Fix wrong object-id', ['received' => $object_data['object_id'], 'correct' => $objectId]);
				$object_data['object_id'] = $objectId;
			}
		}

		return $object_data;
	}

	/**
	 * Fetches the object data from external resources if needed
	 *
	 * @param string  $object_id    Object ID of the provided object
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
			$data = Processor::fetchCachedActivity($object_id, $uid);
			if (!empty($data)) {
				$object = JsonLD::compact($data);
				DI::logger()->info('Fetched content for ' . $object_id);
			} else {
				DI::logger()->info('Empty content for ' . $object_id . ', check if content is available locally.');

				$item = Post::selectFirst(Item::DELIVER_FIELDLIST, ['uri' => $object_id]);
				if (!DBA::isResult($item)) {
					DI::logger()->info('Object with url ' . $object_id . ' was not found locally.');
					return false;
				}
				DI::logger()->info('Using already stored item for url ' . $object_id);
				$data   = ActivityPub\Transmitter::createNote($item);
				$object = JsonLD::compact($data);
			}

			$id = JsonLD::fetchElement($object, '@id');
			if (empty($id)) {
				DI::logger()->info('Empty id');
				return false;
			}

			if ($id != $object_id) {
				DI::logger()->info('Fetched id differs from provided id', ['provided' => $object_id, 'fetched' => $id]);
				return false;
			}
		} else {
			DI::logger()->info('Using original object for url ' . $object_id);
		}

		$type = JsonLD::fetchElement($object, '@type');
		if (empty($type)) {
			DI::logger()->info('Empty type');
			return false;
		}

		// We currently don't handle 'pt:CacheFile', but with this step we avoid logging
		if (in_array($type, self::CONTENT_TYPES) || ($type == 'pt:CacheFile')) {
			$object_data = self::processObject($object, '');

			if (!empty($data)) {
				$object_data['raw-object'] = json_encode($data);
			}
			return $object_data;
		}

		DI::logger()->info('Unhandled object type: ' . $type);
		return false;
	}

	/**
	 * Converts the language element (Used by Peertube)
	 *
	 * @param array $languages
	 * @return array Languages
	 */
	public static function processLanguages(array $languages): array
	{
		if (empty($languages)) {
			return [];
		}

		$language_list = [];

		foreach ($languages as $language) {
			if (!empty($language['_:identifier']) && !empty($language['as:name'])) {
				$language_list[$language['_:identifier']] = $language['as:name'];
			}
		}
		return $language_list;
	}

	/**
	 * Convert tags from JSON-LD format into a simplified format
	 *
	 * @param array $tags Tags in JSON-LD format
	 *
	 * @return array with tags in a simplified format
	 */
	public static function processTags(array $tags): array
	{
		$taglist = [];

		foreach ($tags as $tag) {
			if (empty($tag)) {
				continue;
			}

			$element = [
				'type'      => str_replace('as:', '', JsonLD::fetchElement($tag, '@type') ?? ''),
				'href'      => JsonLD::fetchElement($tag, 'as:href', '@id'),
				'name'      => JsonLD::fetchElement($tag, 'as:name', '@value'),
				'mediaType' => JsonLD::fetchElement($tag, 'as:mediaType', '@value')
			];

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
	private static function processEmojis(array $emojis): array
	{
		$emojilist = [];

		foreach ($emojis as $emoji) {
			if (empty($emoji) || (JsonLD::fetchElement($emoji, '@type') != 'toot:Emoji') || empty($emoji['as:icon'])) {
				continue;
			}

			$element = [
				'name' => JsonLD::fetchElement($emoji, 'as:name', '@value'),
				'href' => JsonLD::fetchElement($emoji['as:icon'], 'as:url', '@id')
			];

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
	private static function processAttachments(array $attachments): array
	{
		$attachlist = [];

		// Removes empty values
		$attachments = array_filter($attachments);

		foreach ($attachments as $attachment) {
			switch (JsonLD::fetchElement($attachment, '@type')) {
				case 'as:Page':
					$pageUrl   = null;
					$pageImage = null;

					$urls = JsonLD::fetchElementArray($attachment, 'as:url');
					foreach ($urls as $url) {
						// Single scalar URL case
						if (is_string($url)) {
							$pageUrl = $url;
							continue;
						}

						$href      = JsonLD::fetchElement($url, 'as:href', '@id');
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
				case 'as:Image':
					$mediaType       = JsonLD::fetchElement($attachment, 'as:mediaType', '@value');
					$imageFullUrl    = JsonLD::fetchElement($attachment, 'as:url', '@id');
					$imagePreviewUrl = null;
					// Multiple URLs?
					if (!$imageFullUrl && ($urls = JsonLD::fetchElementArray($attachment, 'as:url'))) {
						$imageVariants   = [];
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
						'type'      => str_replace('as:', '', JsonLD::fetchElement($attachment, '@type')),
						'mediaType' => $mediaType,
						'name'      => JsonLD::fetchElement($attachment, 'as:name', '@value'),
						'url'       => $imageFullUrl,
						'image'     => $imagePreviewUrl !== $imageFullUrl ? $imagePreviewUrl : null,
					];
					break;
				default:
					$attachlist[] = [
						'type'      => str_replace('as:', '', JsonLD::fetchElement($attachment, '@type')),
						'mediaType' => JsonLD::fetchElement($attachment, 'as:mediaType', '@value'),
						'name'      => JsonLD::fetchElement($attachment, 'as:name', '@value'),
						'url'       => JsonLD::fetchElement($attachment, 'as:url', '@id') ?? JsonLD::fetchElement($attachment, 'as:href', '@id'),
						'height'    => JsonLD::fetchElement($attachment, 'as:height', '@value'),
						'width'     => JsonLD::fetchElement($attachment, 'as:width', '@value'),
						'image'     => JsonLD::fetchElement($attachment, 'as:image', '@id')
					];
			}
		}

		return $attachlist;
	}

	/**
	 * Convert questions from JSON-LD format into a simplified format
	 *
	 * @param array $object
	 *
	 * @return array Questions in a simplified format
	 */
	private static function processQuestion(array $object): array
	{
		$question = [];

		if (!empty($object['as:oneOf'])) {
			$question['multiple'] = false;
			$options              = JsonLD::fetchElementArray($object, 'as:oneOf') ?? [];
		} elseif (!empty($object['as:anyOf'])) {
			$question['multiple'] = true;
			$options              = JsonLD::fetchElementArray($object, 'as:anyOf') ?? [];
		} else {
			return [];
		}

		$closed = JsonLD::fetchElement($object, 'as:closed', '@value');
		if (!empty($closed)) {
			$question['end-time'] = $closed;
		} else {
			$question['end-time'] = JsonLD::fetchElement($object, 'as:endTime', '@value');
		}

		$question['voters']  = (int)JsonLD::fetchElement($object, 'toot:votersCount', '@value');
		$question['options'] = [];

		$voters = 0;

		foreach ($options as $option) {
			if (JsonLD::fetchElement($option, '@type') != 'as:Note') {
				continue;
			}

			$name = JsonLD::fetchElement($option, 'as:name', '@value');

			if (empty($option['as:replies'])) {
				continue;
			}

			$replies = JsonLD::fetchElement($option['as:replies'], 'as:totalItems', '@value');

			$question['options'][] = ['name' => $name, 'replies' => $replies];

			$voters += (int)$replies;
		}

		// For single choice question we can count the number of voters if not provided (like with Misskey)
		if (empty($question['voters']) && !$question['multiple']) {
			$question['voters'] = $voters;
		}

		return $question;
	}

	/**
	 * Fetch the original source or content with the "language" Markdown or HTML
	 *
	 * @param array $object
	 * @param array $object_data
	 *
	 * @return array Object data (?)
	 * @throws \Exception
	 */
	private static function getSource(array $object, array $object_data): array
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
	 * Extracts a potential alternate URL from a list of additional URL elements
	 *
	 * @param array $urls
	 * @return string
	 */
	private static function extractAlternateUrl(array $urls): string
	{
		$alternateUrl = '';
		foreach ($urls as $key => $url) {
			// Not a list but a single URL element
			if (!is_numeric($key)) {
				continue;
			}

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
				$alternateUrl = $href;
			}
		}

		return $alternateUrl;
	}

	/**
	 * Check if the "as:url" element is an array with multiple links
	 * This is the case with audio and video posts.
	 * Then the links are added as attachments
	 *
	 * @param array $urls The object URL list
	 * @return array an array of attachments
	 */
	private static function processAttachmentUrls(array $urls): array
	{
		$attachments = [];
		foreach ($urls as $key => $url) {
			// Not a list but a single URL element
			if (!is_numeric($key)) {
				continue;
			}

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

			$filetype = strtolower(substr($mediatype, 0, strpos($mediatype, '/')));

			if ($filetype == 'audio') {
				$attachments[] = ['type' => $filetype, 'mediaType' => $mediatype, 'url' => $href, 'height' => null, 'size' => null, 'name' => ''];
			} elseif ($filetype == 'video') {
				$height = (int)JsonLD::fetchElement($url, 'as:height', '@value');
				// PeerTube audio-only track
				if ($height === 0) {
					continue;
				}

				$size = (int)JsonLD::fetchElement($url, 'pt:size', '@value');

				$attachments[] = ['type' => $filetype, 'mediaType' => $mediatype, 'url' => $href, 'height' => $height, 'size' => $size, 'name' => ''];
			} elseif (in_array($mediatype, ['application/x-bittorrent', 'application/x-bittorrent;x-scheme-handler/magnet'])) {
				$height = (int)JsonLD::fetchElement($url, 'as:height', '@value');

				// For Torrent links we always store the highest resolution
				if (!empty($attachments[$mediatype]['height']) && ($height < $attachments[$mediatype]['height'])) {
					continue;
				}

				$attachments[$mediatype] = ['type' => $mediatype, 'mediaType' => $mediatype, 'url' => $href, 'height' => $height, 'size' => null, 'name' => ''];
			} elseif ($mediatype == 'application/x-mpegURL') {
				// PeerTube exception, actual video link is in the tags of this URL element
				$attachments = array_merge($attachments, self::processAttachmentUrls($url['as:tag']));
			}
		}

		return array_values($attachments);
	}

	/**
	 * Fetches data from the object part of an activity
	 *
	 * @param array  $object
	 * @param string $actor
	 *
	 * @return array|bool Object data or FALSE if $object does not contain @id element
	 * @throws \Exception
	 */
	private static function processObject(array $object, string $actor)
	{
		if (!JsonLD::fetchElement($object, '@id')) {
			return false;
		}

		$object_data = self::getObjectDataFromActivity($object);

		$object_data['receiver_urls']  = self::getReceiverURL($object);
		$object_data['receiver']       = [];
		$object_data['reception_type'] = [];
		$object_data['unlisted']       = false;

		$receiverdata = self::getReceivers($object, $actor ?: $object_data['actor'] ?? '', $object_data['tags'], true, false);

		foreach ($receiverdata as $key => $data) {
			if ($data['uid'] !== -1) {
				$object_data['reception_type'][$data['uid']] = $data['type'] ?? 0;
			}

			if ($key !== -1) {
				$object_data['receiver'][$key] = $data['uid'];
			} else {
				$object_data['unlisted'] = true;
			}
		}

		if (!empty($object['pixelfed:capabilities'])) {
			$object_data['capabilities'] = self::getCapabilities($object);
		}

		return $object_data;
	}

	private static function getCapabilities($object)
	{
		$capabilities = [];
		foreach (['pixelfed:canAnnounce', 'pixelfed:canLike', 'pixelfed:canReply'] as $element) {
			$capabilities_list = JsonLD::fetchElementArray($object['pixelfed:capabilities'], $element, '@id');
			if (empty($capabilities_list)) {
				continue;
			}
			$capabilities[$element] = $capabilities_list;
		}
		return $capabilities;
	}

	/**
	 * Create an object data array from a given activity
	 *
	 * @param array $object
	 *
	 * @return array Object data
	 */
	public static function getObjectDataFromActivity(array $object): array
	{
		$object_data = [];

		$object_data['object_type'] = JsonLD::fetchElement($object, '@type');
		$object_data['id']          = JsonLD::fetchElement($object, '@id');
		$object_data['reply-to-id'] = JsonLD::fetchElement($object, 'as:inReplyTo', '@id');

		// An empty "id" field is translated to "./" by the compactor, so we have to check for this content
		if (empty($object_data['reply-to-id']) || ($object_data['reply-to-id'] == './')) {
			$object_data['reply-to-id'] = $object_data['id'];

			// On activities the "reply to" is the id of the object it refers to
			if (in_array($object_data['object_type'], array_merge(self::ACTIVITY_TYPES, ['as:Announce']))) {
				$object_id = JsonLD::fetchElement($object, 'as:object', '@id');
				if (!empty($object_id)) {
					$object_data['reply-to-id'] = $object_id;
				}
			}
		} else {
			// Some systems (e.g. GNU Social) don't reply to the "id" field but the "uri" field.
			$replyToId = Item::getURIByLink($object_data['reply-to-id']);
			if (!empty($replyToId) && ($object_data['reply-to-id'] != $replyToId)) {
				DI::logger()->notice('Fix wrong reply-to', ['received' => $object_data['reply-to-id'], 'correct' => $replyToId]);
				$object_data['reply-to-id'] = $replyToId;
			}
		}

		$object_data['published'] = JsonLD::fetchElement($object, 'as:published', '@value');
		$object_data['updated']   = JsonLD::fetchElement($object, 'as:updated', '@value');

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
			$location = BBCode::toPlaintext($location);
		}

		$object_data['sc:identifier']         = JsonLD::fetchElement($object, 'sc:identifier', '@value');
		$object_data['diaspora:guid']         = JsonLD::fetchElement($object, 'diaspora:guid', '@value');
		$object_data['diaspora:comment']      = JsonLD::fetchElement($object, 'diaspora:comment', '@value');
		$object_data['diaspora:like']         = JsonLD::fetchElement($object, 'diaspora:like', '@value');
		$object_data['actor']                 = $object_data['author'] = $actor;
		$element                              = JsonLD::fetchElement($object, 'as:context', '@id');
		$object_data['context']               = $element != './' ? $element : null;
		$element                              = JsonLD::fetchElement($object, 'ostatus:conversation', '@id');
		$object_data['conversation']          = $element != './' ? $element : null;
		$object_data['sensitive']             = JsonLD::fetchElement($object, 'as:sensitive');
		$object_data['name']                  = JsonLD::fetchElement($object, 'as:name', '@value');
		$object_data['summary']               = JsonLD::fetchElement($object, 'as:summary', '@value');
		$object_data['content']               = JsonLD::fetchElement($object, 'as:content', '@value');
		$object_data['mediatype']             = JsonLD::fetchElement($object, 'as:mediaType', '@value');
		$object_data                          = self::getSource($object, $object_data);
		$object_data['start-time']            = JsonLD::fetchElement($object, 'as:startTime', '@value');
		$object_data['end-time']              = JsonLD::fetchElement($object, 'as:endTime', '@value');
		$object_data['location']              = $location;
		$object_data['latitude']              = JsonLD::fetchElement($object, 'as:location', 'as:latitude', '@type', 'as:Place');
		$object_data['latitude']              = JsonLD::fetchElement($object_data, 'latitude', '@value');
		$object_data['longitude']             = JsonLD::fetchElement($object, 'as:location', 'as:longitude', '@type', 'as:Place');
		$object_data['longitude']             = JsonLD::fetchElement($object_data, 'longitude', '@value');
		$object_data['attachments']           = self::processAttachments(JsonLD::fetchElementArray($object, 'as:attachment') ?? []);
		$object_data['tags']                  = self::processTags(JsonLD::fetchElementArray($object, 'as:tag') ?? []);
		$object_data['emojis']                = self::processEmojis(JsonLD::fetchElementArray($object, 'as:tag', null, '@type', 'toot:Emoji') ?? []);
		$object_data['languages']             = self::processLanguages(JsonLD::fetchElementArray($object, 'sc:inLanguage') ?? []);
		$object_data['transmitted-languages'] = Processor::getPostLanguages($object);
		$object_data['generator']             = JsonLD::fetchElement($object, 'as:generator', 'as:name', '@type', 'as:Application');
		$object_data['generator']             = JsonLD::fetchElement($object_data, 'generator', '@value');
		$object_data['alternate-url']         = JsonLD::fetchElement($object, 'as:url', '@id');
		$object_data['replies']               = JsonLD::fetchElement($object, 'as:replies', '@id');

		// Special treatment for Hubzilla links
		if (is_array($object_data['alternate-url'])) {
			$object_data['alternate-url'] = JsonLD::fetchElement($object_data['alternate-url'], 'as:href', '@id');

			if (!is_string($object_data['alternate-url'])) {
				$object_data['alternate-url'] = JsonLD::fetchElement($object['as:url'], 'as:href', '@id');
			}
		}

		if (!empty($object_data['alternate-url']) && !Network::isValidHttpUrl($object_data['alternate-url'])) {
			$object_data['alternate-url'] = null;
		}

		if (in_array($object_data['object_type'], ['as:Audio', 'as:Video'])) {
			$object_data['alternate-url'] = self::extractAlternateUrl($object['as:url'] ?? []) ?: $object_data['alternate-url'];
			$object_data['attachments']   = array_merge($object_data['attachments'], self::processAttachmentUrls($object['as:url'] ?? []));
		}

		$object_data['can-comment'] = JsonLD::fetchElement($object, 'pt:commentsEnabled', '@value');
		if (is_null($object_data['can-comment'])) {
			$object_data['can-comment'] = JsonLD::fetchElement($object, 'pixelfed:commentsEnabled', '@value');
		}

		// Support for quoted posts (Pleroma, Fedibird and Misskey)
		$object_data['quote-url'] = JsonLD::fetchElement($object, 'as:quoteUrl', '@id');
		if (empty($object_data['quote-url'])) {
			$object_data['quote-url'] = JsonLD::fetchElement($object, 'as:quoteUrl', '@value');
		}
		if (empty($object_data['quote-url'])) {
			$object_data['quote-url'] = JsonLD::fetchElement($object, 'fedibird:quoteUri', '@id');
		}
		if (empty($object_data['quote-url'])) {
			$object_data['quote-url'] = JsonLD::fetchElement($object, 'fedibird:quoteUri', '@value');
		}
		if (empty($object_data['quote-url'])) {
			$object_data['quote-url'] = JsonLD::fetchElement($object, 'misskey:_misskey_quote', '@id');
		}

		foreach ($object_data['tags'] as $tag) {
			if (HTTPSignature::isValidContentType($tag['mediaType'] ?? '', $tag['href'])) {
				$object_data['quote-url'] = $tag['href'];
			}
		}

		// Misskey adds some data to the standard "content" value for quoted posts for backwards compatibility.
		// Their own "_misskey_content" value does then contain the content without this extra data.
		if (!empty($object_data['quote-url'])) {
			$misskey_content = JsonLD::fetchElement($object, 'misskey:_misskey_content', '@value');
			if (!empty($misskey_content)) {
				$object_data['content'] = $misskey_content;
			}
		}

		// For page types we expect that the alternate url posts to some page.
		// So we add this to the attachments if it differs from the id.
		// Currently only Lemmy is using the page type.
		if (($object_data['object_type'] == 'as:Page') && !empty($object_data['alternate-url']) && !Strings::compareLink($object_data['alternate-url'], $object_data['id'])) {
			$object_data['attachments'][] = ['url' => $object_data['alternate-url']];
			$object_data['alternate-url'] = null;
		}

		if ($object_data['object_type'] == 'as:Question') {
			$object_data['question'] = self::processQuestion($object);
		}

		return $object_data;
	}

	/**
	 * Add an object id to the list of arrived activities
	 *
	 * @param string $id
	 *
	 * @return void
	 */
	private static function addArrivedId(string $id)
	{
		DBA::delete('arrived-activity', ["`received` < ?", DateTimeFormat::utc('now - 5 minutes')]);
		DBA::insert('arrived-activity', ['object-id' => $id, 'received' => DateTimeFormat::utcNow()], Database::INSERT_IGNORE);
	}

	/**
	 * Checks if the given object already arrived before
	 *
	 * @param string $id
	 *
	 * @return boolean
	 */
	private static function hasArrived(string $id): bool
	{
		return DBA::exists('arrived-activity', ['object-id' => $id]);
	}
}
