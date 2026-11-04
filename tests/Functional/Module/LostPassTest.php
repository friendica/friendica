<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Functional\Module;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\App\Request;
use Friendica\BaseModule;
use Friendica\Core\Cache\Type\ArrayCache;
use Friendica\Core\Lock\Type\CacheLock;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Module\LostPass;
use Friendica\Module\Response;
use Friendica\Module\Security\ResetPassword;
use Friendica\Object\EMail\IEmail;
use Friendica\Render\FriendicaSmartyEngine;
use Friendica\Security\PasswordReset\ResetLinkSender;
use Friendica\Security\Throttle\AttemptLimiter;
use Friendica\Security\Throttle\Repository\Attempts;
use Friendica\Test\FixtureTestCase;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Emailer;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\NullLogger;

final class LostPassTest extends FixtureTestCase
{
	private const UID          = 42;
	private const NICKNAME     = 'selfcontact';
	private const EMAIL        = 'selfcontact@example.com';
	private const NEW_PASSWORD = 'Correct-Horse-Battery-77';

	private ArrayCache $cache;
	private Emailer $emailer;

	protected function setUp(): void
	{
		parent::setUp();

		Renderer::registerTemplateEngine(FriendicaSmartyEngine::class);

		DBA::update('user', ['email' => self::EMAIL, 'language' => 'en'], ['uid' => self::UID]);
		DI::config()->set('system', 'disable_password_exposed', true);
		DI::config()->set('system', 'worker_dont_fork', true);

		$this->cache   = new ArrayCache('localhost');
		$this->emailer = new class (DI::config(), $this->createStub(IManagePersonalConfigValues::class), DI::baseUrl(), new NullLogger(), DI::l10n(), $this->events()) extends Emailer {
			/** @var IEmail[] */
			public array $sent = [];

			public function send(IEmail $email): bool
			{
				$this->sent[] = $email;
				return true;
			}
		};
	}

	protected function tearDown(): void
	{
		unset($_REQUEST['form_security_token']);

		parent::tearDown();
	}

	private function events(): EventDispatcherInterface
	{
		$events = $this->createStub(EventDispatcherInterface::class);
		$events->method('dispatch')->willReturnArgument(0);

		return $events;
	}

	private function module(string $method, array $parameters = [], string $ip = '192.0.2.1'): BaseModule
	{
		$baseUrl = $this->createStub(BaseURL::class);
		$baseUrl->method('__toString')->willReturn('https://friendica.local');

		$args = $this->createStub(Arguments::class);
		$args->method('getMethod')->willReturn($method);
		$args->method('getQueryString')->willReturn('lostpass');
		$args->method('getModuleName')->willReturn('lostpass');

		if (isset($parameters['token'])) {
			return new ResetPassword(DI::l10n(), $baseUrl, $args, new NullLogger(), DI::profiler(), new Response(), DI::config(), $this->emailer, [], $parameters);
		}

		$request = $this->createStub(Request::class);
		$request->method('getRemoteAddress')->willReturn($ip);

		return new LostPass(
			DI::l10n(),
			$baseUrl,
			$args,
			new NullLogger(),
			DI::profiler(),
			new Response(),
			$this->limiter(),
			$request,
			[],
			$parameters,
		);
	}

	private function limiter(): AttemptLimiter
	{
		return new AttemptLimiter(new Attempts($this->cache), new CacheLock(new ArrayCache('localhost')));
	}

	/**
	 * Runs the queued jobs like the worker does.
	 *
	 * @return int Number of processed jobs
	 */
	private function runQueuedJobs(): int
	{
		$sender = new ResetLinkSender($this->limiter(), DI::config(), DI::baseUrl(), DI::l10n(), $this->emailer, new NullLogger());
		$jobs   = DBA::selectToArray('workerqueue', ['id', 'parameter'], ['command' => 'SendPasswordResetLink', 'done' => false]);
		foreach ($jobs as $job) {
			$sender->send(json_decode((string) $job['parameter'], true)[0]);
			DBA::delete('workerqueue', ['id' => $job['id']]);
		}

		return count($jobs);
	}

	private function serverRequest(array $request): ServerRequestInterface
	{
		$serverRequest = $this->createStub(ServerRequestInterface::class);
		$serverRequest->method('getQueryParams')->willReturn($request);

		return $serverRequest;
	}

	/**
	 * @return string The returned page
	 */
	private function post(array $request, array $parameters, string $formType, string $ip = '192.0.2.1'): string
	{
		$_REQUEST['form_security_token'] = BaseModule::getFormSecurityToken($formType);

		return (string) $this->module('POST', $parameters, $ip)->handleRequest($this->serverRequest($request))->getBody();
	}

	private function requestReset(string $login = self::NICKNAME, string $ip = '192.0.2.1'): string
	{
		$page = $this->post(['login-name' => $login], [], 'lostpass', $ip);
		$this->runQueuedJobs();

		return $page;
	}

	private function page(array $parameters = []): string
	{
		return (string) $this->module('GET', $parameters)->handleRequest($this->serverRequest([]))->getBody();
	}

	private function mailedToken(): string
	{
		self::assertNotEmpty($this->emailer->sent);
		self::assertMatchesRegularExpression('#/lostpass/([0-9a-f]+)#', $this->emailer->sent[0]->getMessage(true));
		preg_match('#/lostpass/([0-9a-f]+)#', (string) $this->emailer->sent[0]->getMessage(true), $matches);

		return $matches[1];
	}

	private function passwordHash(): string
	{
		return DBA::selectFirst('user', ['password'], ['uid' => self::UID])['password'];
	}

	public function testKnownAccountGetsAResetMail(): void
	{
		$this->requestReset();

		self::assertCount(1, $this->emailer->sent);
		self::assertSame(self::EMAIL, $this->emailer->sent[0]->getToAddress());
	}

	public function testResponseIsTheSameForKnownAndUnknownAccounts(): void
	{
		$known   = $this->requestReset(self::NICKNAME, '192.0.2.1');
		$unknown = $this->requestReset('doesnotexist', '192.0.2.2');

		self::assertStringContainsString('If the account exists', $known);
		self::assertSame($known, $unknown);
		self::assertCount(1, $this->emailer->sent);
	}

	public function testRequestOnlyQueuesAJobForKnownAndUnknownAccounts(): void
	{
		$this->post(['login-name' => self::NICKNAME], [], 'lostpass', '192.0.2.1');
		$this->post(['login-name' => 'doesnotexist'], [], 'lostpass', '192.0.2.2');

		self::assertSame([], $this->emailer->sent);
		self::assertNull(DBA::selectFirst('user', ['pwdreset'], ['uid' => self::UID])['pwdreset']);
		self::assertSame(2, $this->runQueuedJobs());
		self::assertCount(1, $this->emailer->sent);
	}

	public function testRepeatedRequestFromAnotherAddressSendsNoSecondMail(): void
	{
		$this->requestReset(self::NICKNAME, '192.0.2.1');
		$token = $this->mailedToken();

		$this->requestReset(self::NICKNAME, '192.0.2.2');

		self::assertCount(1, $this->emailer->sent);
		self::assertSame(hash('sha256', $token), DBA::selectFirst('user', ['pwdreset'], ['uid' => self::UID])['pwdreset']);
	}

	public function testRepeatedRequestFromTheSameAddressIsThrottled(): void
	{
		$this->requestReset('doesnotexist', '192.0.2.1');
		$this->requestReset(self::NICKNAME, '192.0.2.1');

		self::assertSame([], $this->emailer->sent);
	}

	/** @return array<string, array{0: mixed}> */
	public static function invalidLogins(): array
	{
		return [
			'empty'    => [''],
			'blank'    => ['   '],
			'array'    => [['a']],
			'not utf8' => ["\xC3\x28"],
		];
	}

	#[DataProvider('invalidLogins')]
	public function testInvalidLoginQueuesNothing(mixed $login): void
	{
		$page = $this->post(['login-name' => $login], [], 'lostpass');

		self::assertStringContainsString('name="login-name"', $page);
		self::assertSame(0, $this->runQueuedJobs());
	}

	public function testOpeningTheLinkChangesNothing(): void
	{
		$this->requestReset();
		$token = $this->mailedToken();
		$hash  = $this->passwordHash();

		$page = $this->page(['token' => $token]);

		self::assertStringContainsString('name="password"', $page);
		self::assertSame($hash, $this->passwordHash());
		self::assertCount(1, $this->emailer->sent);
		self::assertSame(hash('sha256', $token), DBA::selectFirst('user', ['pwdreset'], ['uid' => self::UID])['pwdreset']);
	}

	public function testOpeningAnUnknownLinkShowsNoForm(): void
	{
		$page = $this->page(['token' => str_repeat('0', 64)]);

		self::assertStringContainsString('Request could not be verified.', $page);
		self::assertStringContainsString('href="lostpass"', $page);
		self::assertStringNotContainsString('name="password"', $page);
	}

	public function testOpeningAnExpiredLinkShowsNoFormAndRemovesTheToken(): void
	{
		$this->requestReset();
		$token = $this->mailedToken();
		DBA::update('user', ['pwdreset_time' => DateTimeFormat::utc('now - 2 hours')], ['uid' => self::UID]);

		$page = $this->page(['token' => $token]);

		self::assertStringContainsString('Request has expired', $page);
		self::assertStringNotContainsString('name="password"', $page);
		self::assertNull(DBA::selectFirst('user', ['pwdreset'], ['uid' => self::UID])['pwdreset']);
	}

	public function testChosenPasswordIsSetAndNotMailed(): void
	{
		$this->requestReset();
		$token = $this->mailedToken();

		$page = $this->post(['password' => self::NEW_PASSWORD, 'confirm' => self::NEW_PASSWORD], ['token' => $token], 'lostpass_password');

		self::assertStringContainsString('Your password has been reset.', $page);
		self::assertTrue(password_verify(self::NEW_PASSWORD, $this->passwordHash()));
		self::assertCount(2, $this->emailer->sent);
		foreach ($this->emailer->sent as $mail) {
			self::assertStringNotContainsString(self::NEW_PASSWORD, $mail->getMessage(true));
			self::assertStringNotContainsString(self::NEW_PASSWORD, $mail->getMessage());
		}
	}

	public function testTokenWorksOnlyOnce(): void
	{
		$this->requestReset();
		$token  = $this->mailedToken();
		$params = ['password' => self::NEW_PASSWORD, 'confirm' => self::NEW_PASSWORD];

		$this->post($params, ['token' => $token], 'lostpass_password');
		$hash = $this->passwordHash();

		$page = $this->post(['password' => 'Another-Password-88', 'confirm' => 'Another-Password-88'], ['token' => $token], 'lostpass_password');

		self::assertStringContainsString('Request could not be verified.', $page);
		self::assertStringContainsString('href="lostpass"', $page);
		self::assertStringNotContainsString('name="password"', $page);
		self::assertSame($hash, $this->passwordHash());
	}

	public function testMismatchKeepsPasswordAndToken(): void
	{
		$this->requestReset();
		$token = $this->mailedToken();
		$hash  = $this->passwordHash();

		$page = $this->post(['password' => self::NEW_PASSWORD, 'confirm' => 'different'], ['token' => $token], 'lostpass_password');

		self::assertStringContainsString('Passwords do not match.', $page);
		self::assertStringContainsString('name="password"', $page);
		self::assertSame($hash, $this->passwordHash());
		self::assertSame(hash('sha256', $token), DBA::selectFirst('user', ['pwdreset'], ['uid' => self::UID])['pwdreset']);
	}

	public function testExpiredTokenIsRejectedAndRemoved(): void
	{
		$this->requestReset();
		$token = $this->mailedToken();
		$hash  = $this->passwordHash();
		DBA::update('user', ['pwdreset_time' => DateTimeFormat::utc('now - 2 hours')], ['uid' => self::UID]);

		$page = $this->post(['password' => self::NEW_PASSWORD, 'confirm' => self::NEW_PASSWORD], ['token' => $token], 'lostpass_password');

		self::assertStringContainsString('Request has expired', $page);
		self::assertStringContainsString('href="lostpass"', $page);
		self::assertStringNotContainsString('name="password"', $page);
		self::assertSame($hash, $this->passwordHash());
		self::assertNull(DBA::selectFirst('user', ['pwdreset'], ['uid' => self::UID])['pwdreset']);
	}
}
