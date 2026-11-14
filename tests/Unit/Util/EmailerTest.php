<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\Unit\Util;

use Friendica\App\BaseURL;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Event\EventDispatcher;
use Friendica\Event\EmailerSendEvent;
use Friendica\Event\EmailerSendPrepareEvent;
use Friendica\Object\EMail\IEmail;
use Friendica\Test\Util\EmailerSpy;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Emailer has to dispatch EmailerSendPrepareEvent and EmailerSendEvent on every send().
 *
 * SMTP addons like phpmailer hook into those two events through the HookEventBridge.
 * If they aren't dispatched, delivery silently falls back to PHP mail() and nothing
 * gets sent on hosts without a relaying MTA.
 *
 * @see https://github.com/friendica/friendica/issues/15998
 */
class EmailerTest extends TestCase
{
	private EventDispatcher $eventDispatcher;

	protected function setUp(): void
	{
		parent::setUp();

		$this->eventDispatcher = new EventDispatcher();
		EmailerSpy::$MAIL_DATA = [];
	}

	protected function tearDown(): void
	{
		EmailerSpy::$MAIL_DATA = [];

		parent::tearDown();
	}

	public function testSendDispatchesThePrepareEvent(): void
	{
		$dispatched = [];

		$this->eventDispatcher->addListener(
			EmailerSendPrepareEvent::NAME,
			function (EmailerSendPrepareEvent $event) use (&$dispatched): void {
				$dispatched[] = $event->getEmail();
			},
		);

		$this->createEmailer()->send($this->createEmail());

		self::assertCount(1, $dispatched);
		self::assertInstanceOf(IEmail::class, $dispatched[0]);
	}

	public function testSendDispatchesTheSendEvent(): void
	{
		$dispatched = [];

		$this->eventDispatcher->addListener(
			EmailerSendEvent::NAME,
			function (EmailerSendEvent $event) use (&$dispatched): void {
				$dispatched[] = $event;
			},
		);

		$this->createEmailer()->send($this->createEmail());

		self::assertCount(1, $dispatched);
		self::assertSame('recipient@friendica.local', $dispatched[0]->getToAddress());
		self::assertSame('Test Subject', $dispatched[0]->getSubject());
		self::assertFalse($dispatched[0]->isSent());
	}

	/**
	 * This is what an SMTP addon does: it delivers the mail itself and marks it as sent.
	 */
	public function testListenerMarkingTheMailAsSentSkipsTheMailFallback(): void
	{
		$this->eventDispatcher->addListener(
			EmailerSendEvent::NAME,
			function (EmailerSendEvent $event): void {
				$event->setSent(true);
			},
		);

		self::assertTrue($this->createEmailer()->send($this->createEmail()));
		self::assertSame([], EmailerSpy::$MAIL_DATA, 'mail() must not be called once a listener sent the mail');
	}

	/**
	 * A listener may drop the mail entirely by removing it from the event.
	 */
	public function testPrepareListenerCanCancelTheMail(): void
	{
		$this->eventDispatcher->addListener(
			EmailerSendPrepareEvent::NAME,
			function (EmailerSendPrepareEvent $event): void {
				$event->setEmail(null);
			},
		);

		self::assertTrue($this->createEmailer()->send($this->createEmail()));
		self::assertSame([], EmailerSpy::$MAIL_DATA);
	}

	public function testSendFallsBackToMailWithoutListeners(): void
	{
		self::assertTrue($this->createEmailer()->send($this->createEmail()));
		self::assertSame('recipient@friendica.local', EmailerSpy::$MAIL_DATA['to']);
	}

	private function createEmailer(): EmailerSpy
	{
		$config = $this->createStub(IManageConfigValues::class);
		$config->method('get')->willReturnCallback(fn (string $cat, string $key): string|true => match ([$cat, $key]) {
			['config', 'sender_email'] => 'test@friendica.local',
			['config', 'sitename'] => 'Friendica Social Network',
			default => true,
		});

		$baseUrl = $this->createStub(BaseURL::class);
		$baseUrl->method('getHost')->willReturn('friendica.local');

		return new EmailerSpy(
			$config,
			$this->createStub(IManagePersonalConfigValues::class),
			$baseUrl,
			new NullLogger(),
			$this->createStub(L10n::class),
			$this->eventDispatcher,
		);
	}

	private function createEmail(): IEmail
	{
		$email = $this->createStub(IEmail::class);
		$email->method('getFromName')->willReturn('Sender');
		$email->method('getFromAddress')->willReturn('sender@friendica.local');
		$email->method('getReplyTo')->willReturn('sender@friendica.local');
		$email->method('getToAddress')->willReturn('recipient@friendica.local');
		$email->method('getSubject')->willReturn('Test Subject');
		$email->method('getMessage')->willReturn('Test Message');
		$email->method('getAdditionalMailHeader')->willReturn([]);
		$email->method('getAdditionalMailHeaderString')->willReturn('');

		return $email;
	}
}
