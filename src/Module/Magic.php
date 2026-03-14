<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module;

use Exception;
use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\AppHelper;
use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\Protocol;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Database\Database;
use Friendica\Model\Contact;
use Friendica\Model\GServer;
use Friendica\Model\User;
use Friendica\Network\HTTPClient\Capability\ICanSendHttpRequests;
use Friendica\Network\HTTPClient\Client\HttpClientOptions;
use Friendica\Util\HTTPSignature;
use Friendica\Util\Network;
use Friendica\Util\Profiler;
use Friendica\Util\Strings;
use Friendica\Worker\UpdateContact;
use Psr\Log\LoggerInterface;

/**
 * Magic Auth (remote authentication) module.
 *
 * Ported from Hubzilla: https://framagit.org/hubzilla/core/blob/master/Zotlabs/Module/Magic.php
 */
class Magic extends BaseModule
{
	/** @var AppHelper */
	protected $appHelper;
	/** @var Database */
	protected $dba;
	/** @var ICanSendHttpRequests */
	protected $httpClient;
	/** @var IHandleUserSessions */
	protected $userSession;

	public function __construct(AppHelper $appHelper, L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, Database $dba, ICanSendHttpRequests $httpClient, IHandleUserSessions $userSession, $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->appHelper   = $appHelper;
		$this->dba         = $dba;
		$this->httpClient  = $httpClient;
		$this->userSession = $userSession;
	}

	protected function rawContent(array $request = [])
	{
		if ($_SERVER['REQUEST_METHOD'] == 'HEAD') {
			$this->logger->debug('Got a HEAD request');
			System::exit();
		}

		$this->logger->debug('Invoked', ['request' => $request]);

		$addr  = (string) ($request['addr'] ?? '');
		$bdest = (string) ($request['bdest'] ?? '');
		$dest  = (string) ($request['dest'] ?? '');
		$owa   = intval($request['owa'] ?? 0);

		// bdest is preferred as it is hex-encoded and can survive url rewrite and argument parsing
		if ($bdest !== '') {
			$dest = hex2bin($bdest);
			$this->logger->debug('bdest detected', ['dest' => $dest]);
		}

		$target = $dest ?: $addr;

		$contact = Contact::getByURL($addr ?: $dest);
		if ($contact === [] && $owa === 0) {
			$this->logger->info('No contact record found, no oWA, redirecting to destination.', ['request' => $request, 'server' => $_SERVER, 'dest' => $dest]);
			$this->appHelper->redirect($dest);
		}

		if ($contact !== []) {
			// Redirect if the contact is already authenticated on this site.
			if ($this->appHelper->getContactId() && strpos($contact['nurl'], Strings::normaliseLink($this->baseUrl)) !== false) {
				$this->logger->info('Contact is already authenticated, redirecting to destination.', ['dest' => $dest]);
				System::externalRedirect($dest);
			}

			$this->logger->debug('Contact found', ['url' => $contact['url']]);
		}

		if (!$this->userSession->getLocalUserId() || $owa === 0) {
			$this->logger->notice('Not logged in or not OWA, redirecting to destination.', ['uid' => $this->userSession->getLocalUserId(), 'owa' => $owa, 'dest' => $dest]);
			$this->appHelper->redirect($dest);
		}

		$dest = Network::removeUrlParameter($dest, 'zid');
		$dest = Network::removeUrlParameter($dest, 'f');

		// OpenWebAuth
		$owner = User::getOwnerDataById($this->userSession->getLocalUserId());

		if (!empty($contact['gsid'])) {
			$gsid = $contact['gsid'];
		} elseif (GServer::check($target)) {
			$gsid = GServer::getID($target);
		}

		if (empty($gsid)) {
			$this->logger->notice('The target is not a server path, redirecting to destination.', ['target' => $target]);
			System::externalRedirect($dest);
		}

		$gserver = $this->dba->selectFirst('gserver', ['url', 'network', 'openwebauth'], ['id' => $gsid]);
		if (empty($gserver)) {
			$this->logger->notice('Server not found, redirecting to destination.', ['gsid' => $gsid, 'dest' => $dest]);
			System::externalRedirect($dest);
		}

		$openwebauth = $gserver['openwebauth'];

		// This part can be removed, when all server entries had been updated. So removing it in 2025 should be safe.
		if (empty($openwebauth) && ($gserver['network'] == Protocol::DFRN)) {
			$this->logger->notice('Open Web Auth path not provided. Assume default path', ['gsid' => $gsid, 'dest' => $dest]);
			$openwebauth = $gserver['url'] . '/owa';
			// Update contact to assign the path to the server
			UpdateContact::add(Worker::PRIORITY_MEDIUM, $contact['id']);
		}

		if (empty($openwebauth)) {
			$this->logger->debug('Server does not support open web auth, redirecting to destination.', ['gsid' => $gsid, 'dest' => $dest]);
			System::externalRedirect($dest);
		}

		$header = [
			'Accept'          => 'application/x-zot+json',
			'X-Open-Web-Auth' => Strings::getRandomHex(),
		];

		// Create a header that is signed with the local users private key.
		$header = HTTPSignature::createSig(
			$header,
			$owner['prvkey'],
			'acct:' . $owner['addr']
		);

		$this->logger->info('Fetch from remote system', ['openwebauth' => $openwebauth, 'headers' => $header]);

		// Try to get an authentication token from the other instance.
		try {
			$curlResult = $this->httpClient->request('get', $openwebauth, [HttpClientOptions::HEADERS => $header]);
		} catch (Exception $exception) {
			$this->logger->notice('URL is invalid, redirecting to destination.', ['url' => $openwebauth, 'error' => $exception, 'dest' => $dest]);
			System::externalRedirect($dest);
		}
		if (!$curlResult->isSuccess()) {
			$this->logger->notice('OWA request failed, redirecting to destination.', ['returncode' => $curlResult->getReturnCode(), 'dest' => $dest]);
			System::externalRedirect($dest);
		}

		$j = json_decode($curlResult->getBodyString(), true);
		if (empty($j) || !$j['success']) {
			$this->logger->notice('Invalid JSON, redirecting to destination.', ['json' => $j, 'dest' => $dest]);
			$this->appHelper->redirect($dest);
		}

		if ($j['encrypted_token']) {
			// The token is encrypted. If the local user is really the one the other instance
			// thinks they is, the token can be decrypted with the local users public key.
			$token = '';
			openssl_private_decrypt(Strings::base64UrlDecode($j['encrypted_token']), $token, $owner['prvkey']);
		} else {
			$token = $j['token'];
		}
		$args = (strpbrk($dest, '?&') ? '&' : '?') . 'owt=' . $token;

		$this->logger->debug('Redirecting', ['path' => $dest . $args]);
		System::externalRedirect($dest . $args);
	}
}
