<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Contact;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\AppHelper;
use Friendica\Core\L10n;
use Friendica\Core\Protocol;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\Worker;
use Friendica\Database\Database;
use Friendica\Model\Contact;
use Friendica\Module\Response;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use Friendica\Util\Strings;
use Friendica\Worker\UpdateContact;
use Psr\Log\LoggerInterface;

class Redir extends \Friendica\BaseModule
{
	public function __construct(private readonly AppHelper $appHelper, private readonly Database $database, private readonly IHandleUserSessions $session, L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
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

		$contact = Contact::selectFirst(['id', 'url', 'gsid', 'alias', 'network'], ['id' => $cid]);
		if (!$contact) {
			$this->logger->info('Contact not found', ['id' => $cid]);
			throw new HTTPException\NotFoundException($this->t('Contact not found.'));
		} elseif (empty($contact['gsid'])) {
			$this->logger->info('Contact has got no server', ['id' => $cid]);
			return;
		}

		$contact_url = Contact::getProfileLink($contact);
		$this->checkUrl($contact_url, $url);
		$target_url = $url ?: $contact_url;

		$gserver  = $this->database->selectFirst('gserver', ['url', 'network', 'openwebauth'], ['id' => $contact['gsid']]);
		$basepath = $gserver['url'];

		// This part can be removed, when all server entries had been updated. So removing it in 2025 should be safe.
		if (empty($gserver['openwebauth']) && ($gserver['network'] == Protocol::DFRN)) {
			$this->logger->notice('Magic path not provided. Contact update initiated.', ['gsid' => $contact['gsid']]);
			// Update contact to assign the path to the server
			UpdateContact::add(Worker::PRIORITY_MEDIUM, $contact['id']);
		} elseif (empty($gserver['openwebauth'])) {
			$this->logger->debug('Server does not support open web auth.', ['server' => $gserver]);
			return;
		}

		// We don't use magic auth when there is no visitor, we are on the same system, or we visit our own stuff
		if (empty($visitor) || Strings::compareLink($basepath, $this->baseUrl) || Strings::compareLink($contact_url, $visitor)) {
			$this->logger->info('Redirecting without magic', ['target' => $target_url, 'visitor' => $visitor, 'contact' => $contact_url]);
			$this->appHelper->redirect($target_url);
		}

		$separator = strpos($target_url, '?') ? '&' : '?';
		$target_url .= $separator . 'zrl=' . urlencode($visitor) . '&addr=' . urlencode($contact_url);

		$this->logger->info('Redirecting with magic', ['target' => $target_url, 'visitor' => $visitor, 'contact' => $contact_url]);
		$this->appHelper->redirect($target_url);
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

		$fields  = ['id', 'uid', 'nurl', 'url', 'addr', 'name', 'alias', 'network'];
		$contact = Contact::selectFirst($fields, ['id' => $cid, 'uid' => [0, $this->session->getLocalUserId()]]);
		if (!$contact) {
			throw new HTTPException\NotFoundException($this->t('Contact not found.'));
		}

		$contact_url = Contact::getProfileLink($contact);
		$this->checkUrl($contact_url, $url);
		$target_url = $url ?: $contact_url;

		if (!empty($this->appHelper->getContactId()) && $this->appHelper->getContactId() == $cid) {
			// Local user is already authenticated.
			$this->appHelper->redirect($target_url);
		}

		if ($contact['uid'] == 0 && $this->session->getLocalUserId()) {
			// Let's have a look if there is an established connection
			// between the public contact we have found and the local user.
			$contact = Contact::selectFirst($fields, ['nurl' => $contact['nurl'], 'uid' => $this->session->getLocalUserId()]);
			if ($contact) {
				$cid = $contact['id'];
			}

			if (!empty($this->appHelper->getContactId()) && $this->appHelper->getContactId() == $cid) {
				// Local user is already authenticated.
				$this->logger->info('Contact already authenticated. Redirecting to target url', ['url' => $contact['url'], 'name' => $contact['name'], 'target_url' => $target_url]);
				$this->appHelper->redirect($target_url);
			}
		}

		if ($this->session->getRemoteUserId()) {
			$host       = substr($this->baseUrl->getPath() . ($this->baseUrl->getPath() ? '/' . $this->baseUrl->getPath() : ''), strpos($this->baseUrl->getPath(), '://') + 3);
			$remotehost = substr((string) $contact['addr'], strpos((string) $contact['addr'], '@') + 1);

			// On a local instance we have to check if the local user has already authenticated
			// with the local contact. Otherwise, the local user would ask the local contact
			// for authentication everytime he/she is visiting a profile page of the local
			// contact.
			if (($host == $remotehost) && ($this->session->getRemoteContactID($this->session->get('visitor_visiting')) == $this->session->get('visitor_id'))) {
				// Remote user is already authenticated.
				$this->logger->info('Contact already authenticated. Redirecting to target url', ['url' => $contact['url'], 'name' => $contact['name'], 'target_url' => $target_url]);
				$this->appHelper->redirect($target_url);
			}
		}

		// If we don't have a connected contact, redirect with
		// the 'zrl' parameter.
		$my_profile = $this->session->getMyUrl();

		if (!empty($my_profile) && !Strings::compareLink($my_profile, $target_url)) {
			$separator = strpos($target_url, '?') ? '&' : '?';

			$target_url .= $separator . 'zrl=' . urlencode($my_profile);
		}

		$this->logger->info('redirecting to ' . $target_url);
		$this->appHelper->redirect($target_url);
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
