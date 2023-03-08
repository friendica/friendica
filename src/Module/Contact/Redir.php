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

namespace Friendica\Module\Contact;

use Friendica\Core\L10n;
use Friendica\App;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Database\Database;
use Friendica\Model\Contact;
use Friendica\Module\Response;
use Friendica\Network\HTTPClient\Capability\ICanSendHttpRequests;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;
use Friendica\Network\HTTPClient\Client\HttpClientOptions;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use Friendica\Util\Strings;
use Psr\Log\LoggerInterface;

class Redir extends \Friendica\BaseModule
{
	/** @var IHandleUserSessions */
	private $session;
	/** @var Database */
	private $database;
	/** @var App */
	private $app;
	/** @var ICanSendHttpRequests */
	private $httpClient;

	public function __construct(ICanSendHttpRequests $httpClient, App $app, Database $database, IHandleUserSessions $session, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->session    = $session;
		$this->database   = $database;
		$this->app        = $app;
		$this->httpClient = $httpClient;
	}

	protected function rawContent(array $request = [])
	{
		if (!$this->session->isAuthenticated()) {
			throw new HTTPException\ForbiddenException($this->t('Access denied.'));
		}

		$url = $request['url'] ?? '';

		$cid = $this->parameters['id'] ?? 0;

		// Try magic auth before the legacy stuff
		$this->magic($cid, $url);

		$this->legacy($cid, $url);
	}

	/**
	 * @param int    $cid
	 * @param string $url
	 * @return void
	 * @throws HTTPException\ForbiddenException
	 * @throws HTTPException\InternalServerErrorException
	 * @throws HTTPException\NotFoundException
	 * @throws \ImagickException
	 */
	private function magic(int $cid, string $url)
	{
		$visitor = $this->session->getMyUrl();
		if (!empty($visitor)) {
			$this->logger->info('Got my url', ['visitor' => $visitor]);
		}

		$contact = $this->database->selectFirst('contact', ['url'], ['id' => $cid]);
		if (!$contact) {
			$this->logger->info('Contact not found', ['id' => $cid]);
			throw new HTTPException\NotFoundException($this->t('Contact not found.'));
		} else {
			$contact_url = $contact['url'];
			$this->checkUrl($contact_url, $url);
			$target_url = $url ?: $contact_url;
		}

		$basepath = Contact::getBasepath($contact_url);

		// We don't use magic auth when there is no visitor, we are on the same system, or we visit our own stuff
		if (empty($visitor) || Strings::compareLink($basepath, $this->baseUrl) || Strings::compareLink($contact_url, $visitor)) {
			$this->logger->info('Redirecting without magic', ['target' => $target_url, 'visitor' => $visitor, 'contact' => $contact_url]);
			$this->app->redirect($target_url);
		}

		// Test for magic auth on the target system
		$response = $this->httpClient->head($basepath . '/magic', [HttpClientOptions::ACCEPT_CONTENT => HttpClientAccept::HTML]);
		if ($response->isSuccess()) {
			$separator = strpos($target_url, '?') ? '&' : '?';
			$target_url .= $separator . 'zrl=' . urlencode($visitor) . '&addr=' . urlencode($contact_url);

			$this->logger->info('Redirecting with magic', ['target' => $target_url, 'visitor' => $visitor, 'contact' => $contact_url]);
			$this->app->redirect($target_url);
		} else {
			$this->logger->info('No magic for contact', ['contact' => $contact_url]);
		}
	}

	/**
	 * @param int    $cid
	 * @param string $url
	 * @return void
	 * @throws HTTPException\BadRequestException
	 * @throws HTTPException\ForbiddenException
	 * @throws HTTPException\InternalServerErrorException
	 * @throws HTTPException\NotFoundException
	 */
	private function legacy(int $cid, string $url): void
	{
		if (empty($cid)) {
			throw new HTTPException\BadRequestException($this->t('Bad Request.'));
		}

		$fields  = ['id', 'uid', 'nurl', 'url', 'addr', 'name'];
		$contact = $this->database->selectFirst('contact', $fields, ['id' => $cid, 'uid' => [0, $this->session->getLocalUserId()]]);
		if (!$contact) {
			throw new HTTPException\NotFoundException($this->t('Contact not found.'));
		}

		$contact_url = $contact['url'];

		if (!empty($this->app->getContactId()) && $this->app->getContactId() == $cid) {
			// Local user is already authenticated.
			$this->checkUrl($contact_url, $url);
			$this->app->redirect($url ?: $contact_url);
		}

		if ($contact['uid'] == 0 && $this->session->getLocalUserId()) {
			// Let's have a look if there is an established connection
			// between the public contact we have found and the local user.
			$contact = $this->database->selectFirst('contact', $fields, ['nurl' => $contact['nurl'], 'uid' => $this->session->getLocalUserId()]);
			if ($contact) {
				$cid = $contact['id'];
			}

			if (!empty($this->app->getContactId()) && $this->app->getContactId() == $cid) {
				// Local user is already authenticated.
				$this->checkUrl($contact_url, $url);
				$target_url = $url ?: $contact_url;
				$this->logger->info($contact['name'] . " is already authenticated. Redirecting to " . $target_url);
				$this->app->redirect($target_url);
			}
		}

		if ($this->session->getRemoteUserId()) {
			$host       = substr($this->baseUrl->getPath() . ($this->baseUrl->getPath() ? '/' . $this->baseUrl->getPath() : ''), strpos($this->baseUrl->getPath(), '://') + 3);
			$remotehost = substr($contact['addr'], strpos($contact['addr'], '@') + 1);

			// On a local instance we have to check if the local user has already authenticated
			// with the local contact. Otherwise, the local user would ask the local contact
			// for authentification everytime he/she is visiting a profile page of the local
			// contact.
			if (($host == $remotehost) && ($this->session->getRemoteContactID($this->session->get('visitor_visiting')) == $this->session->get('visitor_id'))) {
				// Remote user is already authenticated.
				$this->checkUrl($contact_url, $url);
				$target_url = $url ?: $contact_url;
				$this->logger->info($contact['name'] . " is already authenticated. Redirecting to " . $target_url);
				$this->app->redirect($target_url);
			}
		}

		if (empty($url)) {
			throw new HTTPException\BadRequestException($this->t('Bad Request.'));
		}

		// If we don't have a connected contact, redirect with
		// the 'zrl' parameter.
		$my_profile = $this->session->getMyUrl();

		if (!empty($my_profile) && !Strings::compareLink($my_profile, $url)) {
			$separator = strpos($url, '?') ? '&' : '?';

			$url .= $separator . 'zrl=' . urlencode($my_profile);
		}

		$this->logger->info('redirecting to ' . $url);
		$this->app->redirect($url);
	}


	private function checkUrl(string $contact_url, string $url)
	{
		if (empty($contact_url) || empty($url)) {
			return;
		}

		$url_host = parse_url($url, PHP_URL_HOST);
		if (empty($url_host)) {
			$url_host = parse_url($this->baseUrl, PHP_URL_HOST);
		}

		$contact_url_host = parse_url($contact_url, PHP_URL_HOST);

		if ($url_host == $contact_url_host) {
			return;
		}

		$this->logger->error('URL check host mismatch', ['contact' => $contact_url, 'url' => $url]);
		throw new HTTPException\ForbiddenException($this->t('Access denied.'));
	}
}
