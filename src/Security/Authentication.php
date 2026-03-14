<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Security;

use Exception;
use Friendica\App\BaseURL;
use Friendica\App\Mode;
use Friendica\App\Request;
use Friendica\AppHelper;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Hook;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\System;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\User;
use Friendica\Network\HTTPException;
use Friendica\Security\TwoFactor\Repository\TrustedBrowser;
use Friendica\Util\Network;
use LightOpenID;
use Friendica\Core\L10n;
use Friendica\Core\Worker;
use Friendica\Model\Contact;
use Friendica\Model\User\Cookie;
use Friendica\Util\Strings;
use Psr\Log\LoggerInterface;

/**
 * Handle Authentication, Session and Cookies
 */
class Authentication
{
	/** @var IManageConfigValues */
	private $config;
	/** @var Mode */
	private $mode;
	/** @var BaseURL */
	private $baseUrl;
	/** @var L10n */
	private $l10n;
	/** @var Database */
	private $dba;
	/** @var LoggerInterface */
	private $logger;
	/** @var Cookie */
	private $cookie;
	/** @var IHandleUserSessions */
	private $session;
	/** @var IManagePersonalConfigValues */
	private $pConfig;
	/** @var AppHelper */
	private $appHelper;
	/** @var string */
	private $remoteAddress;

	/**
	 * Sets the X-Account-Management-Status header
	 *
	 * mainly extracted to make it overridable for tests
	 *
	 * @param array $user_record
	 */
	protected function setXAccMgmtStatusHeader(array $user_record)
	{
		header('X-Account-Management-Status: active; name="' . $user_record['username'] . '"; id="' . $user_record['nickname'] . '"');
	}

	/**
	 * Authentication constructor.
	 *
	 * @param IManageConfigValues         $config
	 * @param Mode                        $mode
	 * @param BaseURL                     $baseUrl
	 * @param L10n                        $l10n
	 * @param Database                    $dba
	 * @param LoggerInterface             $logger
	 * @param Cookie                      $cookie
	 * @param IHandleUserSessions         $session
	 * @param IManagePersonalConfigValues $pConfig
	 * @param Request                     $request
	 */
	public function __construct(
		IManageConfigValues $config,
		Mode $mode,
		BaseURL $baseUrl,
		L10n $l10n,
		Database $dba,
		LoggerInterface $logger,
		Cookie $cookie,
		IHandleUserSessions $session,
		IManagePersonalConfigValues $pConfig,
		AppHelper $appHelper,
		Request $request
	) {
		$this->config        = $config;
		$this->mode          = $mode;
		$this->baseUrl       = $baseUrl;
		$this->l10n          = $l10n;
		$this->dba           = $dba;
		$this->logger        = $logger;
		$this->cookie        = $cookie;
		$this->session       = $session;
		$this->pConfig       = $pConfig;
		$this->appHelper     = $appHelper;
		$this->remoteAddress = $request->getRemoteAddress();
	}

	/**
	 * Tries to auth the user from the cookie or session
	 *
	 * @throws HttpException\InternalServerErrorException In case of Friendica internal exceptions
	 * @throws Exception In case of general exceptions (like SQL Grammar)
	 */
	public function withSession()
	{
		// When the "Friendica" cookie is set, take the value to authenticate and renew the cookie.
		if ($this->cookie->get('uid')) {
			$user = $this->dba->selectFirst(
				'user',
				[],
				[
					'uid'             => $this->cookie->get('uid'),
					'blocked'         => false,
					'account_expired' => false,
					'account_removed' => false,
					'verified'        => true,
				],
			);
			if ($this->dba->isResult($user)) {
				if (!$this->cookie->comparePrivateDataHash(
					$this->cookie->get('hash'),
					$user['password'] ?? '',
					$user['prvkey']   ?? '',
				)
				) {
					$this->logger->notice("Hash doesn't fit.", ['user' => $this->cookie->get('uid')]);
					$this->session->clear();
					$this->cookie->clear();
					$this->baseUrl->redirect();
				}

				// Renew the cookie
				$this->cookie->send();

				// Do the authentication if not done by now
				if (!$this->session->isAuthenticated()) {
					$this->setForUser($user);

					if ($this->config->get('system', 'paranoia')) {
						$this->session->set('addr', $this->cookie->get('ip'));
					}
				}
			}
		}

		if ($this->session->isVisitor()) {
			$contact = $this->dba->selectFirst('contact', ['id'], ['id' => $this->session->get('visitor_id')]);
			if ($this->dba->isResult($contact)) {
				$this->appHelper->setContactId($contact['id']);
			}
		}

		if ($this->session->isAuthenticated()) {
			// already logged in user returning
			$check = $this->config->get('system', 'paranoia');
			// extra paranoia - if the IP changed, log them out
			if ($check && ($this->session->get('addr') != $this->remoteAddress)) {
				$this->logger->notice(
					'Session address changed. Paranoid setting in effect, blocking session. ',
					[
						'addr'        => $this->session->get('addr'),
						'remote_addr' => $this->remoteAddress,
					],
				);
				$this->session->clear();
				$this->baseUrl->redirect();
			}

			$user = $this->dba->selectFirst(
				'user',
				[],
				[
					'uid'             => $this->session->get('uid'),
					'blocked'         => false,
					'account_expired' => false,
					'account_removed' => false,
					'verified'        => true,
				],
			);
			if (!$this->dba->isResult($user)) {
				$this->session->clear();
				$this->baseUrl->redirect();
			}

			$this->setForUser($user);
		}
	}

	/**
	 * Attempts to authenticate using OpenId
	 *
	 * @param string $openid_url OpenID URL string
	 * @param bool   $remember   Whether to set the session remember flag
	 *
	 * @throws HttpException\InternalServerErrorException In case of Friendica internal exceptions
	 */
	public function withOpenId(string $openid_url, bool $remember)
	{
		$noid = $this->config->get('system', 'no_openid');

		// if it's an email address or doesn't resolve to a URL, fail.
		if ($noid || strpos($openid_url, '@') || !Network::isUrlValid($openid_url)) {
			DI::sysmsg()->addNotice($this->l10n->t('Login failed.'));
			$this->baseUrl->redirect();
		}

		// Otherwise it's probably an openid.
		try {
			$openid = new LightOpenID($this->baseUrl->getHost());
			/** @phpstan-ignore-next-line $openid->identity is private, but will be set via magic setter */
			$openid->identity = $openid_url;
			$this->session->set('openid', $openid_url);
			$this->session->set('remember', $remember);
			$openid->returnUrl = $this->baseUrl . '/openid';
			$openid->optional  = ['namePerson/friendly', 'contact/email', 'namePerson', 'namePerson/first', 'media/image/aspect11', 'media/image/default'];
			System::externalRedirect($openid->authUrl());
		} catch (Exception $e) {
			DI::sysmsg()->addNotice($this->l10n->t('We encountered a problem while logging in with the OpenID you provided. Please check the correct spelling of the ID.') . '<br /><br >' . $this->l10n->t('The error message was:') . ' ' . $e->getMessage());
		}
	}

	/**
	 * Attempts to authenticate using login/password
	 *
	 * @param string $username
	 * @param string $password    Clear password
	 * @param bool   $remember    Whether to set the session remember flag
	 * @param string $return_path The relative path to redirect the user to after authentication
	 *
	 * @throws HTTPException\ForbiddenException
	 * @throws HTTPException\FoundException
	 * @throws HTTPException\InternalServerErrorException In case of Friendica internal exceptions
	 * @throws HTTPException\MovedPermanentlyException
	 * @throws HTTPException\TemporaryRedirectException
	 */
	public function withPassword(string $username, string $password, bool $remember, string $return_path = '')
	{
		$record = null;

		try {
			$record = $this->dba->selectFirst(
				'user',
				[],
				['uid' => User::getIdFromPasswordAuthentication($username, $password, false, true)],
			);
		} catch (Exception $e) {
			$this->logger->warning('authenticate: failed login attempt', ['action' => 'login', 'username' => $username, 'ip' => $this->remoteAddress]);
			DI::sysmsg()->addNotice($this->l10n->t('Login failed. Please check your credentials.'));
			$this->baseUrl->redirect();
		}

		if ($record['blocked']) {
			$this->logger->warning('authenticate: user is blocked', ['action' => 'login', 'username' => $username, 'ip' => $this->remoteAddress]);
			DI::sysmsg()->addNotice($this->l10n->t('Login failed because your account is blocked.'));
			$this->baseUrl->redirect();
		}

		if (!$remember) {
			$trusted = $this->cookie->get('2fa_cookie_hash') ?? null;
			$this->cookie->clear();
			if ($trusted) {
				$this->cookie->set('2fa_cookie_hash', $trusted);
			}
		}

		// if we haven't failed up this point, log them in.
		$this->session->set('remember', $remember);

		$openid_identity = $this->session->get('openid_identity');
		$openid_server   = $this->session->get('openid_server');

		if (!empty($openid_identity) || !empty($openid_server)) {
			$this->dba->update('user', ['openid' => $openid_identity, 'openidserver' => $openid_server], ['uid' => $record['uid']]);
		}

		/**
		 * @see User::getPasswordRegExp()
		 */
		if (PASSWORD_DEFAULT === PASSWORD_BCRYPT && strlen($password) > 72) {
			$return_path = '/security/password_too_long?' . http_build_query(['return_path' => $return_path]);
		}

		$this->setForUser($record, true, true);

		$this->baseUrl->redirect($return_path);
	}

	/**
	 * Sets the provided user's authenticated session
	 *
	 * @param array $user_record The current "user" record
	 * @param bool  $login_initial
	 * @param bool  $interactive
	 * @param bool  $refresh_login
	 *
	 * @throws HTTPException\FoundException
	 * @throws HTTPException\MovedPermanentlyException
	 * @throws HTTPException\TemporaryRedirectException
	 * @throws HTTPException\ForbiddenException
	 *
	 * @throws HTTPException\InternalServerErrorException In case of Friendica specific exceptions
	 *
	 */
	public function setForUser(array $user_record, bool $login_initial = false, bool $interactive = false, bool $refresh_login = true)
	{
		$my_url = $this->baseUrl . '/profile/' . $user_record['nickname'];

		$this->session->setMultiple([
			'uid'           => $user_record['uid'],
			'theme'         => $user_record['theme'],
			'mobile-theme'  => $this->pConfig->get($user_record['uid'], 'system', 'mobile_theme'),
			'authenticated' => 1,
			'page_flags'    => $user_record['page-flags'],
			'my_url'        => $my_url,
			'my_address'    => $user_record['nickname'] . '@' . substr($this->baseUrl, strpos($this->baseUrl, '://') + 3),
			'addr'          => $this->remoteAddress,
			'nickname'      => $user_record['nickname'],
		]);

		$this->session->setVisitorsContacts($my_url);

		$member_since = strtotime($user_record['register_date']);
		$this->session->set('new_member', time() < ($member_since + (60 * 60 * 24 * 14)));

		if (strlen($user_record['timezone'])) {
			$this->appHelper->setTimeZone($user_record['timezone']);
			$this->session->set('timezone', $user_record['timezone']);
		}

		$contact = $this->dba->selectFirst('contact', ['id'], ['uid' => $user_record['uid'], 'self' => true]);
		if ($this->dba->isResult($contact)) {
			$this->appHelper->setContactId($contact['id']);
			$this->session->set('cid', $contact['id']);
		}

		$this->setXAccMgmtStatusHeader($user_record);

		User::updateLastActivity($user_record, $refresh_login);

		if ($login_initial) {
			// Regularly update suggestions
			if (Contact\Relation::areSuggestionsOutdated($user_record['uid'])) {
				Worker::add(Worker::PRIORITY_MEDIUM, 'UpdateSuggestions', $user_record['uid']);
			}
		}

		if ($login_initial) {
			/*
			 * If the user specified to remember the authentication, then set a cookie
			 * that expires after one week (the default is when the browser is closed).
			 * The cookie will be renewed automatically.
			 * The week ensures that sessions will expire after some inactivity.
			 */
			if ($this->session->get('remember')) {
				$this->logger->info('Injecting cookie for remembered user ' . $user_record['nickname']);
				$this->cookie->setMultiple([
					'uid'  => $user_record['uid'],
					'hash' => $this->cookie->hashPrivateData($user_record['password'], $user_record['prvkey']),
				]);
				$this->session->remove('remember');
			}
		}

		$this->redirectForTwoFactorAuthentication($user_record['uid']);

		if ($interactive) {
			if ($user_record['login_date'] <= DBA::NULL_DATETIME) {
				DI::sysmsg()->addInfo($this->l10n->t('Welcome %s', $user_record['username']));
				DI::sysmsg()->addInfo($this->l10n->t('Please upload a profile photo.'));
				$this->baseUrl->redirect('settings/profile/photo/new');
			}
		}

		if ($login_initial) {
			Hook::callAll('logged_in', $user_record);
		}
	}

	/**
	 * Decides whether to redirect the user to two-factor authentication.
	 * All return calls in this method skip two-factor authentication
	 *
	 * @param int $uid The User Identified
	 *
	 * @throws HTTPException\ForbiddenException In case the two factor authentication is forbidden (e.g. for AJAX calls)
	 * @throws HTTPException\InternalServerErrorException
	 */
	private function redirectForTwoFactorAuthentication(int $uid)
	{
		// Check user setting, if 2FA disabled return
		if (!$this->pConfig->get($uid, '2fa', 'verified')) {
			return;
		}

		// Check current path, if public or 2fa module return
		if (DI::args()->getArgc() > 0 && in_array(DI::args()->getArgv()[0], ['2fa', 'view', 'help', 'api', 'proxy', 'logout'])) {
			return;
		}

		// Case 1a: 2FA session already present: return
		if ($this->session->get('2fa')) {
			return;
		}

		// Case 1b: Check for trusted browser
		if ($this->cookie->get('2fa_cookie_hash')) {
			// Retrieve a trusted_browser model based on cookie hash
			$trustedBrowserRepository = new TrustedBrowser($this->dba, $this->logger);
			try {
				$trustedBrowser = $trustedBrowserRepository->selectOneByHash($this->cookie->get('2fa_cookie_hash'));
				// Verify record ownership
				if ($trustedBrowser->uid === $uid) {
					// Update last_used date
					$trustedBrowser->recordUse();

					// Save it to the database
					$trustedBrowserRepository->save($trustedBrowser);

					// Only use this entry, if its really trusted, otherwise just update the record and proceed
					if ($trustedBrowser->trusted) {
						// Set 2fa session key and return
						$this->session->set('2fa', true);

						return;
					}
				} else {
					// Invalid trusted cookie value, removing it
					$this->cookie->unset('trusted');
				}
			} catch (\Throwable $e) {
				// Local trusted browser record was probably removed by the user, we carry on with 2FA
			}
		}

		// Case 2: No valid 2FA session: redirect to code verification page
		if ($this->mode->isAjax()) {
			throw new HTTPException\ForbiddenException();
		} else {
			$this->baseUrl->redirect('2fa');
		}
	}

	/**
	 * Set the URL of an unauthenticated visitor
	 *
	 * @param string $url
	 * @return void
	 */
	public function setUnauthenticatedVisitor(string $url)
	{
		if (Strings::compareLink($this->session->get('visitor_home') ?: '', $url)) {
			return;
		}

		$this->session->set('my_url', $url);
		$this->session->set('authenticated', 0);

		$remote_contact = Contact::getByURL($url, false, ['subscribe']);
		if (!empty($remote_contact['subscribe'])) {
			$this->session->set('remote_comment', $remote_contact['subscribe']);
		}
	}
}
