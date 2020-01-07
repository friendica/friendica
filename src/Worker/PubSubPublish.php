<?php
/**
 * @file src/Worker/PubSubPublish.php
 */

namespace Friendica\Worker;

use Friendica\Core\Logger;
use Friendica\Database\DBA;
use Friendica\Model\PushSubscriber;
use Friendica\Protocol\OStatus;
use Friendica\Registry\App;
use Friendica\Util\Network;

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
		$subscriber = DBA::selectFirst('push_subscriber', [], ['id' => $id]);
		if (!DBA::isResult($subscriber)) {
			return;
		}

		/// @todo Check server status with GServer::check()
		// Before this can be done we need a way to safely detect the server url.

		Logger::log("Generate feed of user " . $subscriber['nickname']. " to " . $subscriber['callback_url']. " - last updated " . $subscriber['last_update'], Logger::DEBUG);

		$last_update = $subscriber['last_update'];
		$params = OStatus::feed($subscriber['nickname'], $last_update);

		if (!$params) {
			return;
		}

		$hmac_sig = hash_hmac("sha1", $params, $subscriber['secret']);

		$headers = ["Content-type: application/atom+xml",
				sprintf("Link: <%s>;rel=hub,<%s>;rel=self",
					App::baseUrl() . '/pubsubhubbub/' . $subscriber['nickname'],
					$subscriber['topic']),
				"X-Hub-Signature: sha1=" . $hmac_sig];

		Logger::log('POST ' . print_r($headers, true) . "\n" . $params, Logger::DATA);

		$postResult = Network::post($subscriber['callback_url'], $params, $headers);
		$ret = $postResult->getReturnCode();

		if ($ret >= 200 && $ret <= 299) {
			Logger::log('Successfully pushed to ' . $subscriber['callback_url']);

			PushSubscriber::reset($subscriber['id'], $last_update);
		} else {
			Logger::log('Delivery error when pushing to ' . $subscriber['callback_url'] . ' HTTP: ' . $ret);

			PushSubscriber::delay($subscriber['id']);
		}
	}
}
