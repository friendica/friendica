<?php
/**
 * @file src/Worker/PubSubPublish.php
 */

namespace Friendica\Worker;

use Friendica\App;
use Friendica\Core\System;
use Friendica\Core\Config;
use Friendica\Core\Worker;
use Friendica\Database\DBM;
use Friendica\Protocol\OStatus;

require_once 'include/items.php';

class PubSubPublish {
	public static function execute($pubsubpublish_id = 0)
	{
		global $a;

		if ($pubsubpublish_id == 0) {
			// We'll push to each subscriber that has push > 0,
			// i.e. there has been an update (set in notifier.php).
			$r = q("SELECT `id`, `callback_url` FROM `push_subscriber` WHERE `push` > 0 ORDER BY `last_update` DESC");

			foreach ($r as $rr) {
				logger("Publish feed to ".$rr["callback_url"], LOGGER_DEBUG);
				Worker::add(['priority' => PRIORITY_HIGH, 'created' => $a->queue['created'], 'dont_fork' => true],
						'PubSubPublish', (int)$rr["id"]);
			}
		}

		self::publish($pubsubpublish_id);

		return;
	}

	private static function publish($id) {
		global $a;

		$r = q("SELECT * FROM `push_subscriber` WHERE `id` = %d", intval($id));
		if (!DBM::isResult($r)) {
			return;
		}

		$rr = $r[0];

		/// @todo Check server status with PortableContact::checkServer()
		// Before this can be done we need a way to safely detect the server url.

		logger("Generate feed of user ".$rr['nickname']." to ".$rr['callback_url']." - last updated ".$rr['last_update'], LOGGER_DEBUG);

		$last_update = $rr['last_update'];
		$params = OStatus::feed($rr['nickname'], $last_update);

		if (!$params) {
			return;
		}

		$hmac_sig = hash_hmac("sha1", $params, $rr['secret']);

		$headers = ["Content-type: application/atom+xml",
				sprintf("Link: <%s>;rel=hub,<%s>;rel=self",
					System::baseUrl().'/pubsubhubbub/'.$rr['nickname'],
					$rr['topic']),
				"X-Hub-Signature: sha1=".$hmac_sig];

		logger('POST '.print_r($headers, true)."\n".$params, LOGGER_DEBUG);

		post_url($rr['callback_url'], $params, $headers);
		$ret = $a->get_curl_code();

		if ($ret >= 200 && $ret <= 299) {
			logger('successfully pushed to '.$rr['callback_url']);

			// set last_update to the "created" date of the last item, and reset push=0
			q("UPDATE `push_subscriber` SET `push` = 0, last_update = '%s' WHERE id = %d",
				dbesc($last_update),
				intval($rr['id']));

		} else {
			logger('error when pushing to '.$rr['callback_url'].' HTTP: '.$ret);

			// we use the push variable also as a counter, if we failed we
			// increment this until some upper limit where we give up
			$new_push = intval($rr['push']) + 1;

			if ($new_push > 30) // OK, let's give up
				$new_push = 0;

			q("UPDATE `push_subscriber` SET `push` = %d WHERE id = %d",
				$new_push,
				intval($rr['id']));
		}
	}
}
