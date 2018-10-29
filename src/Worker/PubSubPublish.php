<?php
/**
 * @file src/Worker/PubSubPublish.php
 */

namespace Friendica\Worker;

use Friendica\App;
use Friendica\BaseObject;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Model\PushSubscriber;
use Friendica\Protocol\OStatus;
use Friendica\Util\Network;

require_once 'include/items.php';

class PubSubPublish
{
	public static function execute($pubsubpublish_id = 0)
	{
		if ($pubsubpublish_id == 0) {
			return;
		}

		self::publish($pubsubpublish_id);
	}

	private static function publish($id)
	{
		$a = BaseObject::getApp();

		$subscriber = DBA::selectFirst('push_subscriber', [], ['id' => $id]);
		if (!DBA::isResult($subscriber)) {
			return;
		}

		/// @todo Check server status with PortableContact::checkServer()
		// Before this can be done we need a way to safely detect the server url.

		App::logger("Generate feed of user " . $subscriber['nickname']. " to " . $subscriber['callback_url']. " - last updated " . $subscriber['last_update'], LOGGER_DEBUG);

		$last_update = $subscriber['last_update'];
		$params = OStatus::feed($subscriber['nickname'], $last_update);

		if (!$params) {
			return;
		}

		$hmac_sig = hash_hmac("sha1", $params, $subscriber['secret']);

		$headers = ["Content-type: application/atom+xml",
				sprintf("Link: <%s>;rel=hub,<%s>;rel=self",
					System::baseUrl() . '/pubsubhubbub/' . $subscriber['nickname'],
					$subscriber['topic']),
				"X-Hub-Signature: sha1=" . $hmac_sig];

		App::logger('POST ' . print_r($headers, true) . "\n" . $params, LOGGER_DATA);

		$postResult = Network::post($subscriber['callback_url'], $params, $headers);
		$ret = $postResult->getReturnCode();

		$condition = ['id' => $subscriber['id']];

		if ($ret >= 200 && $ret <= 299) {
			App::logger('Successfully pushed to ' . $subscriber['callback_url']);

			PushSubscriber::reset($subscriber['id'], $last_update);
		} else {
			App::logger('Delivery error when pushing to ' . $subscriber['callback_url'] . ' HTTP: ' . $ret);

			PushSubscriber::delay($subscriber['id']);
		}
	}
}
