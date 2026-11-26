<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\AbstractEvent;
use Friendica\Event\EmailerSendPrepareEvent;
use Friendica\Object\EMail\IEmail;
use Friendica\Test\Util\SampleMailBuilder;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class EmailerSendPrepareEventTest extends TestCase
{
	use MockeryPHPUnitIntegration;

	private function createEvent(IEmail $email): EmailerSendPrepareEvent
	{
		return new EmailerSendPrepareEvent($email);
	}

	private function createEmail(): IEmail
	{
		$l10n    = \Mockery::mock(\Friendica\Core\L10n::class);
		$baseUrl = \Mockery::mock(\Friendica\App\BaseURL::class);
		$baseUrl->shouldReceive('getHost')->andReturn('friendica.local');
		$baseUrl->shouldReceive('__toString')->andReturn('http://friendica.local');
		$config = \Mockery::mock(\Friendica\Core\Config\Capability\IManageConfigValues::class);
		$config->shouldReceive('get')->withArgs(['config', 'sitename', 'Friendica Social Network'])->andReturn('Friendica Social Network');

		$builder = new SampleMailBuilder($l10n, $baseUrl, $config, new \Psr\Log\NullLogger());

		return $builder
			->withRecipient('recipient@friendica.local')
			->withMessage('Test Subject', 'Test Message', 'Test Text')
			->withSender('Sender', 'sender@friendica.local')
			->forUser(['uid' => 1])
			->build(true);
	}

	public function testImplementationOfAbstractEvent(): void
	{
		$event = $this->createEvent($this->createEmail());

		$this->assertInstanceOf(AbstractEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = $this->createEvent($this->createEmail());

		$this->assertSame(EmailerSendPrepareEvent::NAME, $event->getName());
	}

	public function testGetEmailReturnsValue(): void
	{
		$event = $this->createEvent($this->createEmail());

		$this->assertInstanceOf(IEmail::class, $event->getEmail());
	}

	public function testGetEmailIsNullByDefault(): void
	{
		$event = new EmailerSendPrepareEvent();

		$this->assertNull($event->getEmail());
	}

	public function testSetEmail(): void
	{
		$event     = new EmailerSendPrepareEvent();
		$testEmail = $this->createEmail();

		$event->setEmail($testEmail);

		$this->assertSame($testEmail, $event->getEmail());
	}

	public function testSetEmailToNull(): void
	{
		$event = $this->createEvent($this->createEmail());

		$event->setEmail(null);

		$this->assertNull($event->getEmail());
	}
}
