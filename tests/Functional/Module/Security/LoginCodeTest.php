<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Functional\Module\Security;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\App\Request;
use Friendica\Core\Cache\Type\ArrayCache;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Lock\Capability\ICanLock;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Model\UserSession;
use Friendica\Core\Session\Type\ArraySession;
use Friendica\DI;
use Friendica\Module\Response;
use Friendica\Network\HTTPException\FoundException;
use Friendica\Module\Security\Login;
use Friendica\Module\Security\LoginCode;
use Friendica\Render\FriendicaSmartyEngine;
use Friendica\Security\Account\Repository\Account;
use Friendica\Security\Account\ValueObject\Account as AccountObject;
use Friendica\Security\Authentication;
use Friendica\Security\FormSecurityToken;
use Friendica\Security\LoginCode\CodeMail;
use Friendica\Security\LoginCode\EmailCodeLogin;
use Friendica\Security\LoginCode\Factory\PendingCode as PendingCodeFactory;
use Friendica\Security\LoginCode\Repository\PendingCode;
use Friendica\Security\Throttle\AttemptLimiter;
use Friendica\Security\Throttle\Repository\Attempts;
use Friendica\Test\FixtureTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\NullLogger;

final class LoginCodeTest extends FixtureTestCase
{
	private const UID   = 42;
	private const LOGIN = 'alice';
	private const IP    = '192.0.2.1';

	private ArrayCache $cache;
	private EmailCodeLogin $service;
	private Authentication&MockObject $auth;
	private bool $enabled;

	/** @var string[] */
	private array $sentCodes;

	protected function setUp(): void
	{
		parent::setUp();

		Renderer::registerTemplateEngine(FriendicaSmartyEngine::class);

		$this->cache     = new ArrayCache('localhost');
		$this->sentCodes = [];
		$this->enabled   = true;
		$this->auth      = $this->createMock(Authentication::class);

		$accounts = $this->createStub(Account::class);
		$accounts->method('selectByLogin')->willReturnCallback(fn (string $login): ?AccountObject => $login === self::LOGIN
			? new AccountObject(self::UID, 'Alice', self::LOGIN, 'alice@example.com', 'en')
			: null);

		$mail = $this->createStub(CodeMail::class);
		$mail->method('send')->willReturnCallback(function (AccountObject $account, string $code): bool {
			$this->sentCodes[] = $code;
			return true;
		});

		$lock = $this->createStub(ICanLock::class);
		$lock->method('acquire')->willReturn(true);

		$this->service = new EmailCodeLogin(
			$accounts,
			new PendingCode($this->cache, new PendingCodeFactory(new NullLogger())),
			new AttemptLimiter(new Attempts($this->cache), $lock),
			$mail,
			new NullLogger(),
		);
	}

	private function session(): UserSession
	{
		return new UserSession(new ArraySession());
	}

	private function module(UserSession $session, string $method, string $ip = self::IP): LoginCode
	{
		$config = $this->createStub(IManageConfigValues::class);
		$config->method('get')->willReturnCallback(fn (string $cat, string $key): bool => $key === 'enable_login_code' && $this->enabled);

		$request = $this->createStub(Request::class);
		$request->method('getRemoteAddress')->willReturn($ip);

		$baseUrl = $this->createStub(BaseURL::class);
		$baseUrl->method('redirect')->willReturnCallback(function (string $to = ''): void {
			throw new FoundException($to);
		});

		$args = $this->createStub(Arguments::class);
		$args->method('getMethod')->willReturn($method);
		$args->method('getQueryString')->willReturn('login/code');
		$args->method('getModuleName')->willReturn('login');

		$events = $this->createStub(EventDispatcherInterface::class);
		$events->method('dispatch')->willReturnArgument(0);

		return new LoginCode(
			$this->service,
			$this->auth,
			$this->formSecurityToken($session),
			$session,
			$config,
			DI::sysmsg(),
			$request,
			DI::l10n(),
			$baseUrl,
			$args,
			new NullLogger(),
			DI::profiler(),
			new Response(),
			[],
			[],
			$events,
		);
	}

	private function formSecurityToken(UserSession $session): FormSecurityToken
	{
		$clock = $this->createStub(ClockInterface::class);
		$clock->method('now')->willReturn(new \DateTimeImmutable());

		return new FormSecurityToken($session, $clock, DI::l10n());
	}

	/**
	 * @return string The path the module redirected to
	 */
	private function post(UserSession $session, array $request, string $ip = self::IP): string
	{
		$request += ['form_security_token' => $this->formSecurityToken($session)->create('login_code')];

		try {
			$this->module($session, 'POST', $ip)->handleRequest($this->serverRequest($request));
		} catch (FoundException $redirect) {
			return $redirect->getMessage();
		}

		self::fail('The module did not redirect');
	}

	private function page(UserSession $session, array $request = []): string
	{
		return (string) $this->module($session, 'GET')->handleRequest($this->serverRequest($request))->getBody();
	}

	private function serverRequest(array $request): ServerRequestInterface
	{
		$serverRequest = $this->createStub(ServerRequestInterface::class);
		$serverRequest->method('getQueryParams')->willReturn($request);

		return $serverRequest;
	}

	private function requestCode(UserSession $session, string $ip = self::IP): void
	{
		$this->post($session, ['action' => 'request', 'username' => self::LOGIN], $ip);
	}

	private function waitForNextRequest(): void
	{
		foreach ($this->cache->getAllKeys('throttle:login-code:request:') as $key) {
			$this->cache->delete($key);
		}
	}

	public function testRequestWithoutTokenSendsNoCode(): void
	{
		$session = $this->session();

		self::assertSame('login/code', $this->post($session, ['action' => 'request', 'username' => self::LOGIN, 'form_security_token' => 'invalid']));
		self::assertSame([], $this->sentCodes);
		self::assertNull($session->get('login_code_uid'));
	}

	public function testVerifyWithoutTokenDoesNotLogIn(): void
	{
		$session = $this->session();
		$this->requestCode($session);

		$this->auth->expects(self::never())->method('withLoginCode');

		$this->post($session, ['action' => 'verify', 'code' => $this->sentCodes[0], 'form_security_token' => 'invalid']);
	}

	public function testDisabledFeatureSendsNoCode(): void
	{
		$this->enabled = false;

		self::assertSame('login', $this->post($this->session(), ['action' => 'request', 'username' => self::LOGIN]));
		self::assertSame([], $this->sentCodes);
	}

	public function testCorrectCodeLogsIn(): void
	{
		$session = $this->session();
		$this->requestCode($session);

		$this->auth->expects(self::once())->method('withLoginCode')->with(self::UID, true, 'settings')
			->willThrowException(new FoundException('logged in'));

		self::assertSame('logged in', $this->post($session, ['action' => 'verify', 'code' => $this->sentCodes[0], 'remember' => '1', 'return_path' => 'settings']));
	}

	public function testWrongCodeDoesNotLogIn(): void
	{
		$session = $this->session();
		$this->requestCode($session);

		$this->auth->expects(self::never())->method('withLoginCode');

		self::assertSame('login/code', $this->post($session, ['action' => 'verify', 'code' => 'wrong']));
	}

	public function testUnknownAccountLooksLikeAKnownOne(): void
	{
		$session = $this->session();

		$this->post($session, ['action' => 'request', 'username' => 'nobody']);

		self::assertSame([], $this->sentCodes);
		self::assertStringContainsString('name="code"', $this->page($session));
	}

	public function testRepeatedRequestKeepsTheMailedCodeValid(): void
	{
		$session = $this->session();
		$this->requestCode($session);
		$this->requestCode($session);

		self::assertCount(1, $this->sentCodes);

		$this->auth->expects(self::once())->method('withLoginCode');

		$this->post($session, ['action' => 'verify', 'code' => $this->sentCodes[0]]);
	}

	public function testRequestNewCodeLinkKeepsTheMailedCodeValid(): void
	{
		$session = $this->session();
		$this->requestCode($session);

		self::assertStringContainsString('name="username"', $this->page($session, ['new' => '1']));

		$this->requestCode($session);

		self::assertCount(1, $this->sentCodes);

		$this->auth->expects(self::once())->method('withLoginCode');

		$this->post($session, ['action' => 'verify', 'code' => $this->sentCodes[0]]);
	}

	public function testRequestFromAnotherSessionKeepsTheMailedCodeValid(): void
	{
		$victim   = $this->session();
		$attacker = $this->session();

		$this->requestCode($victim);
		$this->waitForNextRequest();
		$this->requestCode($attacker);

		self::assertCount(2, $this->sentCodes);

		$this->auth->expects(self::once())->method('withLoginCode');

		$this->post($victim, ['action' => 'verify', 'code' => $this->sentCodes[0]]);
	}

	public function testCodeDoesNotWorkInAnotherSession(): void
	{
		$victim   = $this->session();
		$attacker = $this->session();

		$this->requestCode($victim);
		$this->waitForNextRequest();
		$this->requestCode($attacker);

		$this->auth->expects(self::never())->method('withLoginCode');

		$this->post($attacker, ['action' => 'verify', 'code' => $this->sentCodes[0]]);
	}

	public function testReturnPathSurvivesTheWholeFlow(): void
	{
		$session = $this->session();

		self::assertSame(
			'login/code?return_path=settings%2Fprofile',
			$this->post($session, ['action' => 'request', 'username' => self::LOGIN, 'return_path' => 'settings/profile']),
		);

		$page = $this->page($session, ['return_path' => 'settings/profile']);

		self::assertStringContainsString('name="return_path" value="settings/profile"', $page);
		self::assertStringContainsString('login/code?new=1&amp;return_path=settings%2Fprofile', $page);
	}

	public function testPasswordFormLinksToTheCodePageWithTheReturnPath(): void
	{
		DI::config()->set('system', 'enable_login_code', true);

		self::assertStringContainsString('href="login/code?return_path=settings"', Login::form('settings'));
	}

	public function testPasswordFormHasNoCodeLinkWhenDisabled(): void
	{
		DI::config()->set('system', 'enable_login_code', false);

		self::assertStringNotContainsString('login/code', Login::form('settings'));
	}
}
