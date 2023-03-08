<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
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

namespace Friendica\Module\OStatus;

use Friendica\App;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Database\Database;
use Friendica\Model\PushSubscriber;
use Friendica\Module\Response;
use Friendica\Network\HTTPClient\Capability\ICanSendHttpRequests;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use Friendica\Util\Strings;
use Psr\Log\LoggerInterface;

/**
 * An open, simple, web-scale and decentralized pubsub protocol.
 *
 * Part of the OStatus stack.
 *
 * See https://pubsubhubbub.github.io/PubSubHubbub/pubsubhubbub-core-0.4.html
 *
 * @version 0.4
 */
class PubSubHubBub extends \Friendica\BaseModule
{
	/** @var IManageConfigValues */
	private $config;
	/** @var Database */
	private $database;
	/** @var ICanSendHttpRequests */
	private $httpClient;
	/** @var App\Request */
	private $request;

	public function __construct(App\Request $request, ICanSendHttpRequests $httpClient, Database $database, IManageConfigValues $config, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->config     = $config;
		$this->database   = $database;
		$this->httpClient = $httpClient;
		$this->request    = $request;
	}

	protected function post(array $request = [])
	{
		// PuSH subscription must be considered "public" so just block it
		// if public access isn't enabled.
		if ($this->config->get('system', 'block_public')) {
			throw new HTTPException\ForbiddenException();
		}

		$this->logger->debug('Got request data.', ['request' => $request]);

		// Subscription request from subscriber
		// https://pubsubhubbub.github.io/PubSubHubbub/pubsubhubbub-core-0.4.html#rfc.section.5.1
		// Example from GNU Social:
		// [hub_mode] => subscribe
		// [hub_callback] => http://status.local/main/push/callback/1
		// [hub_verify] => sync
		// [hub_verify_token] => af11...
		// [hub_secret] => af11...
		// [hub_topic] => http://friendica.local/dfrn_poll/sazius

		$hub_mode         = $request['hub_mode']         ?? '';
		$hub_callback     = $request['hub_callback']     ?? '';
		$hub_verify_token = $request['hub_verify_token'] ?? '';
		$hub_secret       = $request['hub_secret']       ?? '';
		$hub_topic        = $request['hub_topic']        ?? '';

		// check for valid hub_mode
		if ($hub_mode === 'subscribe') {
			$subscribe = 1;
		} elseif ($hub_mode === 'unsubscribe') {
			$subscribe = 0;
		} else {
			$this->logger->notice('Invalid hub_mod - ignored.', ['mode' => $hub_mode]);
			throw new HTTPException\NotFoundException();
		}

		$this->logger->info('hub_mode request details.', ['from' => $this->request->getRemoteAddress(), 'mode' => $hub_mode]);

		$nickname = $this->parameters['nickname'] ?? $hub_topic;

		// Extract nickname and strip any .atom extension
		$nickname = basename($nickname, '.atom');
		if (!$nickname) {
			$this->logger->notice('Empty nick, ignoring.');
			throw new HTTPException\NotFoundException();
		}

		// fetch user from database given the nickname
		$condition = ['nickname' => $nickname, 'account_expired' => false, 'account_removed' => false];
		$owner     = $this->database->selectFirst('user', ['uid', 'nickname'], $condition);
		if (!$owner) {
			$this->logger->notice('Local account not found', ['nickname' => $nickname, 'topic' => $hub_topic, 'callback' => $hub_callback]);
			throw new HTTPException\NotFoundException();
		}

		// get corresponding row from contact table
		$condition = ['uid' => $owner['uid'], 'blocked' => false, 'pending' => false, 'self' => true];
		$contact   = $this->database->selectFirst('contact', ['poll'], $condition);
		if (!$contact) {
			$this->logger->notice('Self contact for user not found.', ['uid' => $owner['uid']]);
			throw new HTTPException\NotFoundException();
		}

		// sanity check that topic URLs are the same
		$hub_topic2 = str_replace('/feed/', '/dfrn_poll/', $hub_topic);
		$self       = $this->baseUrl . '/api/statuses/user_timeline/' . $owner['nickname'] . '.atom';

		if (!Strings::compareLink($hub_topic, $contact['poll']) && !Strings::compareLink($hub_topic2, $contact['poll']) && !Strings::compareLink($hub_topic, $self)) {
			$this->logger->notice('Hub topic invalid', ['hub_topic' => $hub_topic, 'poll' => $contact['poll']]);
			throw new HTTPException\NotFoundException();
		}

		// do subscriber verification according to the PuSH protocol
		$hub_challenge = Strings::getRandomHex(40);

		$params = http_build_query([
			'hub.mode'         => $subscribe == 1 ? 'subscribe' : 'unsubscribe',
			'hub.topic'        => $hub_topic,
			'hub.challenge'    => $hub_challenge,
			'hub.verify_token' => $hub_verify_token,

			// lease time is hard coded to one week (in seconds)
			// we don't actually enforce the lease time because GNU
			// Social/StatusNet doesn't honour it (yet)
			'hub.lease_seconds' => 604800,
		]);

		$hub_callback = rtrim($hub_callback, ' ?&#');
		$separator    = parse_url($hub_callback, PHP_URL_QUERY) === null ? '?' : '&';

		$fetchResult = $this->httpClient->fetchFull($hub_callback . $separator . $params);
		$body        = $fetchResult->getBody();
		$returnCode  = $fetchResult->getReturnCode();

		// give up if the HTTP return code wasn't a success (2xx)
		if ($returnCode < 200 || $returnCode > 299) {
			$this->logger->notice('Subscriber verification ignored', ['hub_topic' => $hub_topic, 'callback' => $hub_callback, 'returnCode' => $returnCode]);
			throw new HTTPException\NotFoundException();
		}

		// check that the correct hub_challenge code was echoed back
		if (trim($body) !== $hub_challenge) {
			$this->logger->notice('Subscriber did not echo back hub.challenge, ignoring.', ['hub_challenge' => $hub_challenge, 'body' => trim($body)]);
			throw new HTTPException\NotFoundException();
		}

		PushSubscriber::renew($owner['uid'], $nickname, $subscribe, $hub_callback, $hub_topic, $hub_secret);

		throw new HTTPException\AcceptedException();
	}
}
