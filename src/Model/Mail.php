<?php
/**
 * @file src/Model/Mail.php
 */
namespace Friendica\Model;

use Friendica\App;
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Database\DBM;
use dba;

require_once 'include/dba.php';
require_once 'include/datetime.php';

/**
 * Class to handle private messages
 */
class Mail
{
	/**
	 * Send private message
	 *
	 * @param integer $recipient recipient id, default 0
	 * @param string  $body      message body, default empty
	 * @param string  $subject   message subject, default empty
	 * @param string  $replyto   reply to
	 */
	public static function send($recipient = 0, $body = '', $subject = '', $replyto = '')
	{
		$a = get_app();

		if (!$recipient) {
			return -1;
		}

		if (!strlen($subject)) {
			$subject = t('[no subject]');
		}

		$me = dba::selectFirst('contact', [], ['uid' => local_user(), 'self' => true]);
		$contact = dba::selectFirst('contact', [], ['id' => $recipient, 'uid' => local_user()]);

		if (!(count($me) && (count($contact)))) {
			return -2;
		}

		$guid = get_guid(32);
		$uri = 'urn:X-dfrn:' . System::baseUrl() . ':' . local_user() . ':' . $guid;

		$convid = 0;
		$reply = false;

		// look for any existing conversation structure

		if (strlen($replyto)) {
			$reply = true;
			$r = q("SELECT `convid` FROM `mail` WHERE `uid` = %d AND (`uri` = '%s' OR `parent-uri` = '%s') LIMIT 1",
				intval(local_user()),
				dbesc($replyto),
				dbesc($replyto)
			);
			if (DBM::isResult($r)) {
				$convid = $r[0]['convid'];
			}
		}

		if (!$convid) {
			// create a new conversation
			$recip_host = substr($contact['url'], strpos($contact['url'], '://') + 3);
			$recip_host = substr($recip_host, 0, strpos($recip_host, '/'));

			$recip_handle = (($contact['addr']) ? $contact['addr'] : $contact['nick'] . '@' . $recip_host);
			$sender_handle = $a->user['nickname'] . '@' . substr(System::baseUrl(), strpos(System::baseUrl(), '://') + 3);

			$conv_guid = get_guid(32);
			$convuri = $recip_handle . ':' . $conv_guid;

			$handles = $recip_handle . ';' . $sender_handle;

			$fields = ['uid' => local_user(), 'guid' => $conv_guid, 'creator' => $sender_handle,
				'created' => datetime_convert(), 'updated' => datetime_convert(),
				'subject' => $subject, 'recips' => $handles];
			if (dba::insert('conv', $fields)) {
				$convid = dba::lastInsertId();
			}
		}

		if (!$convid) {
			logger('send message: conversation not found.');
			return -4;
		}

		if (!strlen($replyto)) {
			$replyto = $convuri;
		}

		$post_id = null;
		$success = dba::insert(
			'mail',
			[
				'uid' => local_user(),
				'guid' => $guid,
				'convid' => $convid,
				'from-name' => $me['name'],
				'from-photo' => $me['thumb'],
				'from-url' => $me['url'],
				'contact-id' => $recipient,
				'title' => $subject,
				'body' => $body,
				'seen' => 1,
				'reply' => $reply,
				'replied' => 0,
				'uri' => $uri,
				'parent-uri' => $replyto,
				'created' => datetime_convert()
			]
		);

		if ($success) {
			$post_id = dba::lastInsertId();
		}

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
					if (!stristr($image, System::baseUrl() . '/photo/')) {
						continue;
					}
					$image_uri = substr($image, strrpos($image, '/') + 1);
					$image_uri = substr($image_uri, 0, strpos($image_uri, '-'));
					dba::update('photo', ['allow-cid' => '<' . $recipient . '>'], ['resource-id' => $image_uri, 'album' => 'Wall Photos', 'uid' => local_user()]);
				}
			}
		}

		if ($post_id) {
			Worker::add(PRIORITY_HIGH, "Notifier", "mail", $post_id);
			return intval($post_id);
		} else {
			return -3;
		}
	}

	/**
	 * @param string $recipient recipient, default empty
	 * @param string $body      message body, default empty
	 * @param string $subject   message subject, default empty
	 * @param string $replyto   reply to, default empty
	 */
	public static function sendWall($recipient = '', $body = '', $subject = '', $replyto = '')
	{
		if (!$recipient) {
			return -1;
		}

		if (!strlen($subject)) {
			$subject = t('[no subject]');
		}

		$guid = get_guid(32);
		$uri = 'urn:X-dfrn:' . System::baseUrl() . ':' . local_user() . ':' . $guid;

		$me = Probe::uri($replyto);

		if (!$me['name']) {
			return -2;
		}

		$conv_guid = get_guid(32);

		$recip_handle = $recipient['nickname'] . '@' . substr(System::baseUrl(), strpos(System::baseUrl(), '://') + 3);

		$sender_nick = basename($replyto);
		$sender_host = substr($replyto, strpos($replyto, '://') + 3);
		$sender_host = substr($sender_host, 0, strpos($sender_host, '/'));
		$sender_handle = $sender_nick . '@' . $sender_host;

		$handles = $recip_handle . ';' . $sender_handle;

		$convid = null;
		$fields = ['uid' => $recipient['uid'], 'guid' => $conv_guid, 'creator' => $sender_handle,
			'created' => datetime_convert(), 'updated' => datetime_convert(),
			'subject' => $subject, 'recips' => $handles];
		if (dba::insert('conv', $fields)) {
			$convid = dba::lastInsertId();
		}
		
		if (!$convid) {
			logger('send message: conversation not found.');
			return -4;
		}

		dba::insert(
			'mail',
			[
				'uid' => $recipient['uid'],
				'guid' => $guid,
				'convid' => $convid,
				'from-name' => $me['name'],
				'from-photo' => $me['photo'],
				'from-url' => $me['url'],
				'contact-id' => 0,
				'title' => $subject,
				'body' => $body,
				'seen' => 0,
				'reply' => 0,
				'replied' => 0,
				'uri' => $uri,
				'parent-uri' => $replyto,
				'created' => datetime_convert(),
				'unknown' => 1
			]
		);

		return 0;
	}
}
