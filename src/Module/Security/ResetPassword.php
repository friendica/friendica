<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Module\Security;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\BaseModule;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Model\User;
use Friendica\Module\Response;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Emailer;
use Friendica\Util\Profiler;
use Friendica\Util\Strings;
use Psr\Log\LoggerInterface;

/**
 * Sets a new password with the link from a password reset mail.
 *
 * Opening the link only shows a form, so mail scanners that follow it don't change anything.
 */
final class ResetPassword extends BaseModule
{
	/** Validity of a reset token, as understood by DateTimeFormat::utc() */
	private const TOKEN_VALIDITY = 'now - 1 hour';

	/** @var string[] Shown above the form, as anonymous visitors of some themes don't get system messages */
	private array $errors = [];
	/** Replaces the form with this message, when the link doesn't work */
	private string $linkError = '';
	/** Replaces the form with this message, when the password has been changed */
	private string $result = '';

	public function __construct(L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, private readonly IManageConfigValues $config, private readonly Emailer $emailer, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		// The token is part of the URL and must not leak to other sites
		$this->response->setHeader('no-referrer', 'Referrer-Policy');
	}

	protected function post(array $request = []): void
	{
		$token = $this->parameters['token'];

		self::checkFormSecurityTokenRedirectOnError('lostpass/' . $token, 'lostpass_password');

		$user = $this->selectUserByToken($token);
		if ($user === null) {
			return;
		}

		$password = $request['password'] ?? '';
		$confirm  = $request['confirm']  ?? '';

		try {
			if (!is_string($password) || $password !== $confirm) {
				throw new \Exception($this->l10n->t('Passwords do not match.'));
			}

			if (!User::updatePassword((int) $user['uid'], $password)) {
				throw new \Exception($this->l10n->t('Password update failed. Please try again.'));
			}
		} catch (\Exception $e) {
			$this->errors[] = $e->getMessage();
			$this->errors[] = $this->l10n->t('Password unchanged.');
			return;
		}

		$this->sendChangeNotification($user);
		$this->result = $this->l10n->t('Your password has been reset.');
	}

	protected function content(array $request = []): string
	{
		if ($this->result !== '') {
			return $this->resultPage($this->result, 'login', $this->l10n->t('Sign in'));
		}

		$token = $this->parameters['token'];
		if ($this->linkError === '') {
			$this->selectUserByToken($token);
		}

		if ($this->linkError !== '') {
			return $this->resultPage($this->linkError, 'lostpass', $this->l10n->t('Reset my password'));
		}

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('lostpass_password.tpl'), [
			'$title'    => $this->l10n->t('Password Reset'),
			'$desc'     => $this->l10n->t('Please choose a new password.'),
			'$action'   => 'lostpass/' . $token,
			'$password' => ['password', $this->l10n->t('New Password:'), '', $this->l10n->t('Allowed characters are a-z, A-Z, 0-9 and special characters except white spaces and accentuated letters.') . ' ' . $this->l10n->t('Password length is limited to 72 characters.'), $this->l10n->t('Required'), 'autocomplete="new-password" autofocus'],
			'$confirm'  => ['confirm', $this->l10n->t('Confirm:'), '', '', $this->l10n->t('Required'), 'autocomplete="new-password"'],
			'$submit'   => $this->l10n->t('Reset my password'),
			'$errors'   => $this->errors,

			'$form_security_token' => self::getFormSecurityToken('lostpass_password'),
		]);
	}

	private function resultPage(string $message, string $link, string $linkLabel): string
	{
		return Renderer::replaceMacros(Renderer::getMarkupTemplate('lostpass_result.tpl'), [
			'$title'      => $this->l10n->t('Password Reset'),
			'$message'    => $message,
			'$link'       => $link,
			'$link_label' => $linkLabel,
		]);
	}

	/**
	 * Returns the user a valid reset token belongs to, an expired token is removed.
	 *
	 * @return array{uid: int, username: string, nickname: string, email: string, pwdreset_time: string, language: string}|null
	 */
	private function selectUserByToken(string $token): ?array
	{
		$user = User::getByPasswordResetToken(hash('sha256', $token));
		if ($user === null) {
			$this->linkError = $this->l10n->t('Request could not be verified. (You may have previously submitted it.) Password reset failed.');

			return null;
		}

		if ($user['pwdreset_time'] < DateTimeFormat::utc(self::TOKEN_VALIDITY)) {
			User::clearPasswordResetToken((int) $user['uid']);

			$this->linkError = $this->l10n->t('Request has expired, please make a new one.');

			return null;
		}

		return $user;
	}

	/**
	 * Informs the user about the changed password.
	 *
	 * @param array{uid: int, username: string, nickname: string, email: string, language: string} $user
	 */
	private function sendChangeNotification(array $user): void
	{
		$sitename = $this->config->get('config', 'sitename');
		$preamble = Strings::deindent($this->l10n->t('
			Dear %1$s,
				Your password has been changed as requested.
			If you did not request this change, please contact the site administrator.', $user['username']));
		$body = Strings::deindent($this->l10n->t('
			The login details are as follows:

			URL: %1$s
			Username: %2$s', (string) $this->baseUrl, $user['nickname']));

		$email = $this->emailer->newSystemMail()
			->withMessage($this->l10n->t('Your password has been changed at %s', $sitename), $preamble, $body)
			->forUser($user)
			->withRecipient($user['email'])
			->build();
		$this->emailer->send($email);
	}
}
