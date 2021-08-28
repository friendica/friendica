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

use Friendica\App;
use Friendica\Core\Logger;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\PushSubscriber;
use Friendica\Util\Strings;

function post_var($name) {
	return !empty($_POST[$name]) ? Strings::escapeTags(trim($_POST[$name])) : '';
}

function pubsubhubbub_init(App $a) {
	// PuSH subscription must be considered "public" so just block it
	// if public access isn't enabled.
	if (DI::config()->get('system', 'block_public')) {
		throw new \Friendica\Network\HTTPException\ForbiddenException();
	}

	// Subscription request from subscriber
	// https://pubsubhubbub.googlecode.com/git/pubsubhubbub-core-0.4.html#anchor4
	// Example from GNU Social:
	// [hub_mode] => subscribe
	// [hub_callback] => http://status.local/main/push/callback/1
	// [hub_verify] => sync
	// [hub_verify_token] => af11...
	// [hub_secret] => af11...
	// [hub_topic] => http://friendica.local/dfrn_poll/sazius

	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$hub_mode = post_var('hub_mode');
		$hub_callback = post_var('hub_callback');
		$hub_verify_token = post_var('hub_verify_token');
		$hub_secret = post_var('hub_secret');
		$hub_topic = post_var('hub_topic');

		// check for valid hub_mode
		if ($hub_mode === 'subscribe') {
			$subscribe = 1;
		} elseif ($hub_mode === 'unsubscribe') {
			$subscribe = 0;
		} else {
			Logger::log("Invalid hub_mode=$hub_mode, ignoring.");
			throw new \Friendica\Network\HTTPException\NotFoundException();
		}

		Logger::log("$hub_mode request from " . $_SERVER['REMOTE_ADDR']);

		if (DI::args()->getArgc() > 1) {
			// Normally the url should now contain the nick name as last part of the url
			$nick = DI::args()->getArgv()[1];
		} else {
			// Get the nick name from the topic as a fallback
			$nick = $hub_topic;
		}
		// Extract nick name and strip any .atom extension
		$nick = basename($nick, '.atom');

		if (!$nick) {
			Logger::log('Bad hub_topic=$hub_topic, ignoring.');
			throw new \Friendica\Network\HTTPException\NotFoundException();
		}

		// fetch user from database given the nickname
		$condition = ['nickname' => $nick, 'account_expired' => false, 'account_removed' => false];
		$owner = DBA::selectFirst('user', ['uid', 'nickname'], $condition);
		if (!DBA::isResult($owner)) {
			Logger::log('Local account not found: ' . $nick . ' - topic: ' . $hub_topic . ' - callback: ' . $hub_callback);
			throw new \Friendica\Network\HTTPException\NotFoundException();
		}

		// get corresponding row from contact table
		$condition = ['uid' => $owner['uid'], 'blocked' => false,
			'pending' => false, 'self' => true];
		$contact = DBA::selectFirst('contact', ['poll'], $condition);
		if (!DBA::isResult($contact)) {
			Logger::log('Self contact for user ' . $owner['uid'] . ' not found.');
			throw new \Friendica\Network\HTTPException\NotFoundException();
		}

		// sanity check that topic URLs are the same
		$hub_topic2 = str_replace('/feed/', '/dfrn_poll/', $hub_topic);
		$self = DI::baseUrl() . '/api/statuses/user_timeline/' . $owner['nickname'] . '.atom';

		if (!Strings::compareLink($hub_topic, $contact['poll']) && !Strings::compareLink($hub_topic2, $contact['poll']) && !Strings::compareLink($hub_topic, $self)) {
			Logger::log('Hub topic ' . $hub_topic . ' != ' . $contact['poll']);
			throw new \Friendica\Network\HTTPException\NotFoundException();
		}

		// do subscriber verification according to the PuSH protocol
		$hub_challenge = Strings::getRandomHex(40);

		$params = http_build_query([
			'hub.mode' => $subscribe == 1 ? 'subscribe' : 'unsubscribe',
			'hub.topic' => $hub_topic,
			'hub.challenge' => $hub_challenge,
			'hub.verify_token' => $hub_verify_token,

			// lease time is hard coded to one week (in seconds)
			// we don't actually enforce the lease time because GNU
			// Social/StatusNet doesn't honour it (yet)
			'hub.lease_seconds' => 604800,
		]);

		$hub_callback = rtrim($hub_callback, ' ?&#');
		$separator = parse_url($hub_callback, PHP_URL_QUERY) === null ? '?' : '&';

		$fetchResult = DI::httpClient()->fetchFull($hub_callback . $separator . $params);
		$body = $fetchResult->getBody();
		$ret = $fetchResult->getReturnCode();

		// give up if the HTTP return code wasn't a success (2xx)
		if ($ret < 200 || $ret > 299) {
			Logger::log("Subscriber verification for $hub_topic at $hub_callback returned $ret, ignoring.");
			throw new \Friendica\Network\HTTPException\NotFoundException();
		}

		// check that the correct hub_challenge code was echoed back
		if (trim($body) !== $hub_challenge) {
			Logger::log("Subscriber did not echo back hub.challenge, ignoring.");
			Logger::log("\"$hub_challenge\" != \"".trim($body)."\"");
			throw new \Friendica\Network\HTTPException\NotFoundException();
		}

		PushSubscriber::renew($owner['uid'], $nick, $subscribe, $hub_callback, $hub_topic, $hub_secret);

		throw new \Friendica\Network\HTTPException\AcceptedException();
	}
	exit();
}
