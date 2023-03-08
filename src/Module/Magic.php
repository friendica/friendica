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

namespace Friendica\Module;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\System;
use Friendica\Database\Database;
use Friendica\Model\Contact;
use Friendica\Model\User;
use Friendica\Network\HTTPClient\Capability\ICanSendHttpRequests;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;
use Friendica\Network\HTTPClient\Client\HttpClientOptions;
use Friendica\Util\HTTPSignature;
use Friendica\Util\Profiler;
use Friendica\Util\Strings;
use Psr\Log\LoggerInterface;

/**
 * Magic Auth (remote authentication) module.
 *
 * Ported from Hubzilla: https://framagit.org/hubzilla/core/blob/master/Zotlabs/Module/Magic.php
 */
class Magic extends BaseModule
{
	/** @var App */
	protected $app;
	/** @var Database */
	protected $dba;
	/** @var ICanSendHttpRequests */
	protected $httpClient;
	/** @var IHandleUserSessions */
	protected $userSession;

	public function __construct(App $app, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, Database $dba, ICanSendHttpRequests $httpClient, IHandleUserSessions $userSession, $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->app         = $app;
		$this->dba         = $dba;
		$this->httpClient  = $httpClient;
		$this->userSession = $userSession;
	}

	protected function rawContent(array $request = [])
	{
		$this->logger->info('magic module: invoked');

		$this->logger->debug('args', ['request' => $_REQUEST]);

		$addr = $_REQUEST['addr'] ?? '';
		$dest = $_REQUEST['dest'] ?? '';
		$owa  = (!empty($_REQUEST['owa'])  ? intval($_REQUEST['owa'])  : 0);
		$cid  = 0;

		if (!empty($addr)) {
			$cid = Contact::getIdForURL($addr);
		} elseif (!empty($dest)) {
			$cid = Contact::getIdForURL($dest);
		}

		if (!$cid) {
			$this->logger->info('No contact record found', $_REQUEST);
			// @TODO Finding a more elegant possibility to redirect to either internal or external URL
			$this->app->redirect($dest);
		}
		$contact = $this->dba->selectFirst('contact', ['id', 'nurl', 'url'], ['id' => $cid]);

		// Redirect if the contact is already authenticated on this site.
		if ($this->app->getContactId() && strpos($contact['nurl'], Strings::normaliseLink($this->baseUrl)) !== false) {
			$this->logger->info('Contact is already authenticated');
			System::externalRedirect($dest);
		}

		// OpenWebAuth
		if ($this->userSession->getLocalUserId() && $owa) {
			$user = User::getById($this->userSession->getLocalUserId());

			// Extract the basepath
			// NOTE: we need another solution because this does only work
			// for friendica contacts :-/ . We should have the basepath
			// of a contact also in the contact table.
			$exp = explode('/profile/', $contact['url']);
			$basepath = $exp[0];

			$header = [
				'Accept'		  => ['application/x-dfrn+json', 'application/x-zot+json'],
				'X-Open-Web-Auth' => [Strings::getRandomHex()],
			];

			// Create a header that is signed with the local users private key.
			$header = HTTPSignature::createSig(
				$header,
				$user['prvkey'],
				'acct:' . $user['nickname'] . '@' . $this->baseUrl->getHost() . ($this->baseUrl->getPath() ? '/' . $this->baseUrl->getPath() : '')
			);

			// Try to get an authentication token from the other instance.
			$curlResult = $this->httpClient->get($basepath . '/owa', HttpClientAccept::DEFAULT, [HttpClientOptions::HEADERS => $header]);

			if ($curlResult->isSuccess()) {
				$j = json_decode($curlResult->getBody(), true);

				if ($j['success']) {
					$token = '';
					if ($j['encrypted_token']) {
						// The token is encrypted. If the local user is really the one the other instance
						// thinks he/she is, the token can be decrypted with the local users public key.
						openssl_private_decrypt(Strings::base64UrlDecode($j['encrypted_token']), $token, $user['prvkey']);
					} else {
						$token = $j['token'];
					}
					$args = (strpbrk($dest, '?&') ? '&' : '?') . 'owt=' . $token;

					$this->logger->info('Redirecting', ['path' => $dest . $args]);
					System::externalRedirect($dest . $args);
				}
			}
			System::externalRedirect($dest);
		}

		// @TODO Finding a more elegant possibility to redirect to either internal or external URL
		$this->app->redirect($dest);
	}
}
