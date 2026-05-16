<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\BaseModule;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\Model\User;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Strings;
use Friendica\Navigation\SystemMessages;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Util\Emailer;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Lost password module
 *
 * This module handles password reset functionality for users who have forgotten their passwords.
 */
final class LostPass extends BaseModule
{
	private SystemMessages $sysMessages;
	private IManageConfigValues $config;
	private Emailer $emailer;

	/**
	 * Initialize the module
	 *
	 * @param L10n $l10n
	 * @param BaseURL $baseUrl
	 * @param Arguments $args
	 * @param LoggerInterface $logger
	 * @param Profiler $profiler
	 * @param Response $response
	 * @param SystemMessages $sysMessages
	 * @param IManageConfigValues $config
	 * @param Emailer $emailer
	 * @param array $server
	 * @param array $parameters
	 */
	public function __construct(L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, SystemMessages $sysMessages, IManageConfigValues $config, Emailer $emailer, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->sysMessages = $sysMessages;
		$this->config      = $config;
		$this->emailer     = $emailer;
	}

	/**
	 * Handle POST requests for password reset form submission
	 *
	 * @param array $request
	 * @return void
	 */
	protected function post(array $request = [])
	{
		$loginame = trim($request['login-name'] ?? '');
		if (!$loginame) {
			$this->baseUrl->redirect();
		}

		$condition = ['(`email` = ? OR `nickname` = ?) AND `verified` AND NOT `blocked` AND NOT `account_removed` AND NOT `account_expired`', $loginame, $loginame];
		$user      = DBA::selectFirst('user', ['uid', 'username', 'nickname', 'email', 'language'], $condition);
		if (!DBA::isResult($user)) {
			$this->sysMessages->addNotice($this->l10n->t('No valid account found.'));
			$this->baseUrl->redirect();
		}

		$pwdreset_token = Strings::getRandomHex(32);

		$fields = [
			'pwdreset'      => hash('sha256', $pwdreset_token),
			'pwdreset_time' => DateTimeFormat::utcNow(),
		];
		$result = DBA::update('user', $fields, ['uid' => $user['uid']]);
		if ($result) {
			$this->sysMessages->addInfo($this->l10n->t('Password reset request issued. Check your email.'));
		}

		$sitename  = $this->config->get('config', 'sitename');
		$resetlink = $this->baseUrl . '/lostpass/' . $pwdreset_token;

		$preamble = Strings::deindent($this->l10n->t('
			Dear %1$s,
				A request was recently received at "%2$s" to reset your account
			password. In order to confirm this request, please select the verification link
			below or paste it into your web browser address bar.

			If you did NOT request this change, please DO NOT follow the link
			provided and ignore and/or delete this email, the request will expire shortly.

			Your password will not be changed unless we can verify that you
			issued this request.', $user['username'], $sitename));
		$body = Strings::deindent($this->l10n->t('
			Follow this link soon to verify your identity:

			%1$s

			You will then receive a follow-up message containing the new password.
			You may change that password from your account settings page after logging in.

			The login details are as follows:

			Site Location:	%2$s
			Login Name:	%3$s', $resetlink, $this->baseUrl, $user['nickname']));

		$email = $this->emailer->newSystemMail()
			->withMessage($this->l10n->t('Password reset requested at %s', $sitename), $preamble, $body)
			->forUser($user)
			->withRecipient($user['email'])
			->build();

		$this->emailer->send($email);
		$this->baseUrl->redirect();
	}

	/**
	 * Render page content
	 *
	 * @param array $request
	 * @return string Rendered page content
	 */
	protected function content(array $request = []): string
	{
		if (isset($this->parameters['token'])) {
			$pwdreset_token = $this->parameters['token'];

			$user = DBA::selectFirst('user', ['uid', 'username', 'nickname', 'email', 'pwdreset_time', 'language'], ['pwdreset' => hash('sha256', $pwdreset_token)]);
			if (!DBA::isResult($user)) {
				$this->sysMessages->addNotice($this->l10n->t("Request could not be verified. \x28You may have previously submitted it.\x29 Password reset failed."));

				return $this->form();
			}

			// Password reset requests expire in 60 minutes
			if ($user['pwdreset_time'] < DateTimeFormat::utc('now - 1 hour')) {
				$fields = [
					'pwdreset'      => null,
					'pwdreset_time' => null,
				];
				DBA::update('user', $fields, ['uid' => $user['uid']]);

				$this->sysMessages->addNotice($this->l10n->t('Request has expired, please make a new one.'));

				return $this->form();
			}

			return $this->generatePassword($user);
		} else {
			return $this->form();
		}
	}

	/**
	 * Render the password reset form
	 *
	 * @return string Rendered form HTML
	 */
	private function form(): string
	{
		$tpl = Renderer::getMarkupTemplate('lostpass.tpl');
		$o   = Renderer::replaceMacros($tpl, [
			'$title'  => $this->l10n->t('Forgot your Password?'),
			'$desc'   => $this->l10n->t('Enter your email address and submit to have your password reset. Then check your email for further instructions.'),
			'$name'   => $this->l10n->t('Nickname or email'),
			'$submit' => $this->l10n->t('Reset my password'),
		]);

		return $o;
	}

	/**
	 * Generate and send a new password to the user
	 *
	 * @param array $user User data array
	 * @return string Rendered success message HTML
	 */
	private function generatePassword(array $user): string
	{
		$o = '';

		$new_password = User::generateNewPassword();
		$result       = User::updatePassword($user['uid'], $new_password);
		if (DBA::isResult($result)) {
			$tpl = Renderer::getMarkupTemplate('pwdreset.tpl');
			$o .= Renderer::replaceMacros($tpl, [
				'$lbl1'    => $this->l10n->t('Password Reset'),
				'$lbl2'    => $this->l10n->t('Your password has been reset as requested.'),
				'$lbl3'    => $this->l10n->t('Your new password is'),
				'$lbl4'    => $this->l10n->t('Save or copy your new password - and then'),
				'$lbl5'    => '<a href="' . $this->baseUrl . '">' . $this->l10n->t('click here to login') . '</a>.',
				'$lbl6'    => $this->l10n->t('Your password may be changed from the <em>Settings</em> page after successful login.'),
				'$newpass' => $new_password,
			]);

			$this->sysMessages->addInfo($this->l10n->t("Your password has been reset."));

			$sitename = $this->config->get('config', 'sitename');
			$preamble = Strings::deindent($this->l10n->t('
				Dear %1$s,
					Your password has been changed as requested. Please retain this
				information for your records ' . "\x28" . 'or change your password immediately to
				something that you will remember' . "\x29" . '.
			', $user['username']));
			$body = Strings::deindent($this->l10n->t('
				Your login details are as follows:

				Site Location:	%1$s
				Login Name:	%2$s
				Password:	%3$s

				You may change that password from your account settings page after logging in.
			', $this->baseUrl, $user['nickname'], $new_password));

			$email = $this->emailer->newSystemMail()
				->withMessage($this->l10n->t('Your password has been changed at %s', $sitename), $preamble, $body)
				->forUser($user)
				->withRecipient($user['email'])
				->build();
			$this->emailer->send($email);
		}

		return $o;
	}
}
