<?php
/**
 * @file src/Worker/PubSubPublish.php
 */

namespace Friendica\Worker;

use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Model\PushSubscriber;
use Friendica\Protocol\OStatus;
use Friendica\Util\Network;

class PubSubPublish extends AbstractWorker
{
	/**
	 * {@inheritdoc}
	 */
	public function execute(array $parameters = [])
	{
		$pubsubpublish_id = (isset($parameters[0]) && is_int($parameters[0])) ? $parameters[0] : 0;

		if ($pubsubpublish_id == 0) {
			return;
		}

		$this->publish($pubsubpublish_id);
	}

	private function publish($id)
	{
		$subscriber = DBA::selectFirst('push_subscriber', [], ['id' => $id]);
		if (!DBA::isResult($subscriber)) {
			return;
		}

		/// @todo Check server status with PortableContact::checkServer()
		// Before this can be done we need a way to safely detect the server url.

		$this->logger->info("Generate feed of user " . $subscriber['nickname']. " to " . $subscriber['callback_url']. " - last updated " . $subscriber['last_update']);

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

		$this->logger->debug('POST ' . print_r($headers, true) . "\n" . $params);

		$postResult = Network::post($subscriber['callback_url'], $params, $headers);
		$ret = $postResult->getReturnCode();

		if ($ret >= 200 && $ret <= 299) {
			$this->logger->info('Successfully pushed to ' . $subscriber['callback_url']);

			PushSubscriber::reset($subscriber['id'], $last_update);
		} else {
			$this->logger->info('Delivery error when pushing to ' . $subscriber['callback_url'] . ' HTTP: ' . $ret);

			PushSubscriber::delay($subscriber['id']);
		}
	}
}
