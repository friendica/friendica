<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Model;

use Friendica\Core\ACL;
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Protocol\Activity;
use Friendica\Protocol\Delivery;
use Friendica\Util\DateTimeFormat;

/**
 * Class to handle private messages
 */
class Mail
{
	/**
	 * Insert private message
	 *
	 * @param array $msg
	 * @param bool  $notification
	 * @return int|boolean Message ID or false on error
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function insert(array $msg, bool $notification = true)
	{
		if (!isset($msg['reply'])) {
			$msg['reply'] = DBA::exists('mail', ['parent-uri' => $msg['parent-uri']]);
		}

		if (empty($msg['convid'])) {
			$mail = DBA::selectFirst('mail', ['convid'], ["`convid` != 0 AND `parent-uri` = ?", $msg['parent-uri']]);
			if (DBA::isResult($mail)) {
				$msg['convid'] = $mail['convid'];
			}
		}

		if (empty($msg['guid'])) {
			$msg['guid'] = Item::guidFromUri($msg['uri'], parse_url($msg['from-url'], PHP_URL_HOST));
		}

		$msg['created'] = (!empty($msg['created']) ? DateTimeFormat::utc($msg['created']) : DateTimeFormat::utcNow());

		$msg['author-id']     = Contact::getIdForURL($msg['from-url'], 0, false);
		$msg['uri-id']        = ItemURI::insert(['uri' => $msg['uri'], 'guid' => $msg['guid']]);
		$msg['parent-uri-id'] = ItemURI::getIdByURI($msg['parent-uri']);

		DBA::lock('mail');

		if (DBA::exists('mail', ['uri' => $msg['uri'], 'uid' => $msg['uid']])) {
			DBA::unlock();
			DI::logger()->info('duplicate message already delivered.');
			return false;
		}

		if ($msg['reply'] && DBA::isResult($reply = DBA::selectFirst('mail', ['uri', 'uri-id'], ['parent-uri' => $msg['parent-uri'], 'reply' => false]))) {
			$msg['thr-parent']    = $reply['uri'];
			$msg['thr-parent-id'] = $reply['uri-id'];
		} else {
			$msg['thr-parent']    = $msg['uri'];
			$msg['thr-parent-id'] = $msg['uri-id'];
		}

		DBA::insert('mail', $msg);

		$msg['id'] = DBA::lastInsertId();

		DBA::unlock();

		if (!empty($msg['convid'])) {
			DBA::update('conv', ['updated' => DateTimeFormat::utcNow()], ['id' => $msg['convid']]);
		}

		if ($notification) {
			$user = User::getById($msg['uid']);
			// send notifications.
			$notif_params = [
				'type'  => Notification\Type::MAIL,
				'otype' => Notification\ObjectType::MAIL,
				'verb'  => Activity::POST,
				'uid'   => $user['uid'],
				'cid'   => $msg['contact-id'],
				'link'  => DI::baseUrl() . '/message/' . $msg['id'],
			];

			DI::notify()->createFromArray($notif_params);

			DI::logger()->info('Mail is processed, notification was sent.', ['id' => $msg['id'], 'uri' => $msg['uri']]);
		}

		return $msg['id'];
	}

	/**
	 * Send private message
	 *
	 * @param integer $sender_uid the user id of the sender
	 * @param integer $recipient recipient id, default 0
	 * @param string  $body      message body, default empty
	 * @param string  $subject   message subject, default empty
	 * @param string  $replyto   reply to
	 * @return int
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function send(int $sender_uid, int $recipient = 0, string $body = '', string $subject = '', string $replyto = ''): int
	{
		if (!$recipient) {
			return -1;
		}

		if (!strlen($subject)) {
			$subject = DI::l10n()->t('[no subject]');
		}

		$me = DBA::selectFirst('contact', [], ['uid' => $sender_uid, 'self' => true]);
		if (!DBA::isResult($me)) {
			return -2;
		}

		$contacts = ACL::getValidMessageRecipientsForUser($sender_uid);

		$contactIndex = array_search($recipient, array_column($contacts, 'id'));
		if ($contactIndex === false) {
			return -2;
		}

		$contact = $contacts[$contactIndex];

		Photo::setPermissionFromBody($body, $sender_uid, $me['id'], '<' . $contact['id'] . '>', '', '', '');

		$guid = System::createUUID();
		$uri  = Item::newURI($guid);

		$convid = 0;
		$reply  = false;

		// look for any existing conversation structure

		if (strlen($replyto)) {
			$reply     = true;
			$condition = ["`uid` = ? AND (`uri` = ? OR `parent-uri` = ?)",
				$sender_uid, $replyto, $replyto];
			$mail = DBA::selectFirst('mail', ['convid'], $condition);
			if (DBA::isResult($mail)) {
				$convid = $mail['convid'];
			}
		}

		$convuri = '';
		if (!$convid) {
			// create a new conversation
			$conv_guid = System::createUUID();
			$convuri   = $contact['addr'] . ':' . $conv_guid;

			$fields = ['uid' => $sender_uid, 'guid' => $conv_guid, 'creator' => $me['addr'],
				'created'       => DateTimeFormat::utcNow(), 'updated' => DateTimeFormat::utcNow(),
				'subject'       => $subject, 'recips' => $contact['addr'] . ';' . $me['addr']];
			if (DBA::insert('conv', $fields)) {
				$convid = DBA::lastInsertId();
			}
		}

		if (!$convid) {
			DI::logger()->warning('conversation not found.');
			return -4;
		}

		if (!strlen($replyto)) {
			$replyto = $convuri;
		}

		$post_id = self::insert(
			[
				'uid'        => $sender_uid,
				'guid'       => $guid,
				'convid'     => $convid,
				'from-name'  => $me['name'],
				'from-photo' => $me['thumb'],
				'from-url'   => $me['url'],
				'contact-id' => $recipient,
				'title'      => $subject,
				'body'       => $body,
				'seen'       => 1,
				'reply'      => $reply,
				'replied'    => 0,
				'uri'        => $uri,
				'parent-uri' => $replyto,
				'created'    => DateTimeFormat::utcNow()
			],
			false
		);

		/**
		 *
		 * When a photo was uploaded into the message using the (profile wall) ajax
		 * uploader, The permissions are initially set to disallow anybody but the
		 * owner from seeing it. This is because the permissions may not yet have been
		 * set for the post. If it's private, the photo permissions should be set
		 * appropriately. But we didn't know the final permissions on the post until
		 * now. So now we'll look for links of uploaded messages that are in the
		 * post and set them to the same permissions as the post itself.
		 *
		 */
		$match = null;
		if (preg_match_all("/\[img\](.*?)\[\/img\]/", $body, $match)) {
			$images = $match[1];
			if (count($images)) {
				foreach ($images as $image) {
					$image_rid = Photo::ridFromURI($image);
					if (!empty($image_rid)) {
						Photo::update(['allow_cid' => '<' . $recipient . '>'], ['resource-id' => $image_rid, 'album' => 'Wall Photos', 'uid' => $sender_uid]);
					}
				}
			}
		}

		if ($post_id) {
			Worker::add(Worker::PRIORITY_HIGH, "Notifier", Delivery::MAIL, $post_id);
			return intval($post_id);
		} else {
			return -3;
		}
	}
}
