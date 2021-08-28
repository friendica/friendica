<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
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

namespace Friendica\Worker;

use Friendica\Core\Logger;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\PushSubscriber;
use Friendica\Protocol\OStatus;

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

		$headers = [
			'Content-type' => 'application/atom+xml',
			'Link' => sprintf("<%s>;rel=hub,<%s>;rel=self",
					DI::baseUrl() . '/pubsubhubbub/' . $subscriber['nickname'],
					$subscriber['topic']),
			'X-Hub-Signature' => 'sha1=' . $hmac_sig];

		Logger::log('POST ' . print_r($headers, true) . "\n" . $params, Logger::DATA);

		$postResult = DI::httpClient()->post($subscriber['callback_url'], $params, $headers);
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
