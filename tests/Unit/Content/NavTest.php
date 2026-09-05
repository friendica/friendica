<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Content;

use Dice\Dice;
use Friendica\App\BaseURL;
use Friendica\App\Router;
use Friendica\Content\Nav;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Database\Database;
use Friendica\DI;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ReflectionMethod;

/**
 * Which navigation links a visitor gets to see, depending on who they are.
 *
 * Runs without a database: Nav::getInfo() reaches into static helpers (Feature,
 * Register, User, Contact) that resolve their dependencies through the DI container,
 * so the container is filled with mocks - same approach as tests/Unit/Core/SystemTest.php.
 *
 * Regression target: #15928, where the vier navigation lost the profile link.
 */
class NavTest extends TestCase
{
	private const NICK = 'testuser';
	private const UID  = 42;

	/**
	 * @return array Result of the private Nav::getInfo()
	 */
	private function getInfo(
		bool $authenticated = false,
		bool $isSiteAdmin = false,
		bool $isModerator = false,
	): array {
		$session = $this->createMock(IHandleUserSessions::class);
		$session->method('isAuthenticated')->willReturn($authenticated);
		$session->method('getLocalUserId')->willReturn($authenticated ? self::UID : 0);
		$session->method('getLocalUserNickname')->willReturn($authenticated ? self::NICK : null);
		$session->method('isSiteAdmin')->willReturn($isSiteAdmin);
		$session->method('isModerator')->willReturn($isModerator);
		$session->method('getSubManagedUserId')->willReturn(0);
		$session->method('get')->willReturn('');

		$database = $this->createMock(Database::class);
		$database->method('selectFirst')->willReturnCallback(fn (string $table): array => $table === 'contact'
			? [
				'id'      => 1,
				'url'     => 'https://example.com/profile/' . self::NICK,
				'avatar'  => 'https://example.com/avatar.png',
				'micro'   => 'https://example.com/micro.png',
				'name'    => 'Test User',
				'nick'    => self::NICK,
				'baseurl' => 'https://example.com',
				'updated' => '',
			]
			// 'user' table, queried by User::hasIdentities()
			: ['parent-uid' => null]);
		$database->method('isResult')->willReturn(true);
		$database->method('exists')->willReturn(false);
		$database->method('selectToArray')->willReturn([]);

		$config = $this->createMock(IManageConfigValues::class);
		$config->method('get')->willReturn(null);

		$pConfig = $this->createMock(IManagePersonalConfigValues::class);
		$pConfig->method('get')->willReturn(null);

		$l10n = $this->createMock(L10n::class);
		$l10n->method('t')->willReturnArgument(0);

		$baseUrl = $this->createMock(BaseURL::class);
		$baseUrl->method('__toString')->willReturn('https://example.com');

		$router = $this->createStub(Router::class);
		$router->method('getModuleClass')->willReturn('');

		// Returns the event unchanged, so the array passed to ArrayFilterEvent survives
		$eventDispatcher = $this->createMock(EventDispatcherInterface::class);
		$eventDispatcher->method('dispatch')->willReturnArgument(0);

		$dice = $this->createMock(Dice::class);
		$dice->method('create')->willReturnCallback(fn (string $name): object => match ($name) {
			LoggerInterface::class             => new NullLogger(),
			BaseURL::class                     => $baseUrl,
			Database::class                    => $database,
			IManageConfigValues::class         => $config,
			IManagePersonalConfigValues::class => $pConfig,
			EventDispatcherInterface::class    => $eventDispatcher,
			IHandleUserSessions::class         => $session,
			L10n::class                        => $l10n,
			default                            => throw new \LogicException('Unexpected class requested: ' . $name),
		});
		DI::init($dice, true);

		$nav = new Nav($baseUrl, $l10n, $session, $database, $config, $router, $eventDispatcher);

		return (new ReflectionMethod(Nav::class, 'getInfo'))->invoke($nav);
	}

	// --- anonymous visitor ---

	public function testAnonymousHasLoginLink(): void
	{
		$nav = $this->getInfo()['nav'];

		self::assertNotNull($nav['login'], 'Login link must exist for anonymous users');
		self::assertSame('login', $nav['login'][0]);
	}

	public function testAnonymousHasNoLogoutLink(): void
	{
		self::assertNull($this->getInfo()['nav']['logout'], 'Logout link must not exist for anonymous users');
	}

	public function testAnonymousHasNoNetworkLink(): void
	{
		self::assertNull($this->getInfo()['nav']['network'], 'Network link must not exist for anonymous users');
	}

	public function testAnonymousHasNoMessagesLink(): void
	{
		self::assertNull($this->getInfo()['nav']['messages'], 'Messages link must not exist for anonymous users');
	}

	public function testAnonymousHasNoNotificationsLink(): void
	{
		self::assertNull($this->getInfo()['nav']['notifications'], 'Notifications link must not exist for anonymous users');
	}

	public function testAnonymousHasNoCalendarLink(): void
	{
		self::assertNull($this->getInfo()['nav']['calendar'], 'Calendar link must not exist for anonymous users');
	}

	public function testAnonymousUserinfoIsNull(): void
	{
		self::assertNull($this->getInfo()['userinfo']);
	}

	// --- logged-in user ---

	public function testLoggedInHasLogoutAndNoLogin(): void
	{
		$nav = $this->getInfo(authenticated: true)['nav'];

		self::assertNotNull($nav['logout'], 'Logout link must exist for logged-in users');
		self::assertNull($nav['login'], 'Login link must not exist for logged-in users');
	}

	public function testLoggedInHasNetworkLink(): void
	{
		$nav = $this->getInfo(authenticated: true)['nav'];

		self::assertNotNull($nav['network']);
		self::assertSame('network', $nav['network'][0]);
	}

	public function testLoggedInHasMessagesLink(): void
	{
		$nav = $this->getInfo(authenticated: true)['nav'];

		self::assertNotNull($nav['messages']);
		self::assertSame('message', $nav['messages'][0]);
	}

	public function testLoggedInHasNotificationsLink(): void
	{
		self::assertNotNull($this->getInfo(authenticated: true)['nav']['notifications']);
	}

	public function testLoggedInHasCalendarLink(): void
	{
		$nav = $this->getInfo(authenticated: true)['nav'];

		self::assertNotNull($nav['calendar']);
		self::assertSame('calendar', $nav['calendar'][0]);
	}

	public function testLoggedInUsermenuStartsWithPhotos(): void
	{
		$nav = $this->getInfo(authenticated: true)['nav'];

		self::assertNotEmpty($nav['usermenu'], 'User menu must not be empty for logged-in users');
		self::assertSame('profile/' . self::NICK . '/photos', $nav['usermenu'][0][0]);
	}

	/**
	 * Both themes render their profile link from this entry - the one #15529 removed.
	 */
	public function testLoggedInUserinfoHasNameAndProfileLink(): void
	{
		$userinfo = $this->getInfo(authenticated: true)['userinfo'];

		self::assertNotNull($userinfo);
		self::assertSame('Test User', $userinfo['name']);
		self::assertSame('profile/' . self::NICK . '/profile', $userinfo['link'][0], 'the profile link');
		self::assertNotEmpty($userinfo['link'][1], 'the label shown in the menu');
	}

	// --- admin and moderator ---

	public function testAdminLinkAbsentForRegularUser(): void
	{
		self::assertNull($this->getInfo(authenticated: true)['nav']['admin']);
	}

	public function testAdminLinkPresentForSiteAdmin(): void
	{
		$nav = $this->getInfo(authenticated: true, isSiteAdmin: true)['nav'];

		self::assertNotNull($nav['admin']);
		self::assertSame('admin/', $nav['admin'][0]);
	}

	public function testModerationLinkAbsentForRegularUser(): void
	{
		self::assertNull($this->getInfo(authenticated: true)['nav']['moderation']);
	}

	public function testModerationLinkPresentForModerator(): void
	{
		$nav = $this->getInfo(authenticated: true, isModerator: true)['nav'];

		self::assertNotNull($nav['moderation']);
		self::assertSame('moderation/', $nav['moderation'][0]);
	}

	// --- result structure ---

	public function testSitelocationContainsTheSiteHost(): void
	{
		self::assertStringContainsString('example.com', $this->getInfo()['sitelocation']);
	}

	public function testSitelocationIsPrefixedWithTheWebbieWhenLoggedIn(): void
	{
		self::assertStringStartsWith(self::NICK . '@', $this->getInfo(authenticated: true)['sitelocation']);
	}

	public function testBuildReturnsBannerAndApps(): void
	{
		$info = $this->getInfo();

		self::assertArrayHasKey('banner', $info);
		self::assertArrayHasKey('nav', $info);
		self::assertIsArray($info['nav']);
	}
}
