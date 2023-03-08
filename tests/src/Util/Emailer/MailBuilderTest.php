<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Test\src\Util\Emailer;

use Friendica\App\BaseURL;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Network\HTTPException\UnprocessableEntityException;
use Friendica\Object\EMail\IEmail;
use Friendica\Test\MockedTest;
use Friendica\Test\Util\SampleMailBuilder;
use Friendica\Test\Util\VFSTrait;
use Friendica\Util\EMailer\MailBuilder;
use Mockery\MockInterface;
use Psr\Log\NullLogger;

/**
 * This class tests the "MailBuilder" (@see MailBuilder )
 * Since it's an abstract class and every extended class of it has dependencies, we use a "SampleMailBuilder" (@see SampleMailBuilder ) to make this class work
 */
class MailBuilderTest extends MockedTest
{
	use VFSTrait;

	/** @var IManageConfigValues|MockInterface */
	private $config;
	/** @var L10n|MockInterface */
	private $l10n;
	/** @var BaseURL|MockInterface */
	private $baseUrl;

	/** @var string */
	private $defaultHeaders;

	protected function setUp(): void
	{
		parent::setUp();

		$this->setUpVfsDir();

		$this->config  = \Mockery::mock(IManageConfigValues::class);
		$this->l10n    = \Mockery::mock(L10n::class);
		$this->baseUrl = \Mockery::mock(BaseURL::class);
		$this->baseUrl->shouldReceive('getHost')->andReturn('friendica.local');
		$this->baseUrl->shouldReceive('__toString')->andReturn('http://friendica.local');

		$this->defaultHeaders = [];
	}

	public function assertEmail(IEmail $email, array $asserts)
	{
		self::assertEquals($asserts['subject'] ?? $email->getSubject(), $email->getSubject());
		self::assertEquals($asserts['html'] ?? $email->getMessage(), $email->getMessage());
		self::assertEquals($asserts['text'] ?? $email->getMessage(true), $email->getMessage(true));
		self::assertEquals($asserts['toAddress'] ?? $email->getToAddress(), $email->getToAddress());
		self::assertEquals($asserts['fromAddress'] ?? $email->getFromAddress(), $email->getFromAddress());
		self::assertEquals($asserts['fromName'] ?? $email->getFromName(), $email->getFromName());
		self::assertEquals($asserts['replyTo'] ?? $email->getReplyTo(), $email->getReplyTo());
		self::assertEquals($asserts['uid'] ?? $email->getRecipientUid(), $email->getRecipientUid());
		self::assertEquals($asserts['header'] ?? $email->getAdditionalMailHeader(), $email->getAdditionalMailHeader());
	}

	/**
	 * Test if the builder instance can get created
	 */
	public function testBuilderInstance()
	{
		$builder = new SampleMailBuilder($this->l10n, $this->baseUrl, $this->config, new NullLogger());

		self::assertInstanceOf(MailBuilder::class, $builder);
	}

	/**
	 * Test if the builder can create full rendered emails
	 *
	 * @todo Create test once "Renderer" and "BBCode" are dynamic
	 * @doesNotPerformAssertions
	 */
	public function testBuilderWithNonRawEmail()
	{
		static::markTestIncomplete('Cannot easily mock Renderer and BBCode, so skipping tests wit them');
	}

	/**
	 * Test if the builder can create a "simple" raw mail
	 */
	public function testBuilderWithRawEmail()
	{
		$builder = new SampleMailBuilder($this->l10n, $this->baseUrl, $this->config, new NullLogger());

		$testEmail = $builder
			->withMessage('Subject', 'Html', 'text')
			->withRecipient('recipient@friendica.local')
			->withSender('Sender', 'sender@friendica.local', 'no-reply@friendica.local')
			->forUser(['uid' => 100])
			->build(true);

		self::assertEmail($testEmail, [
			'subject' => 'Subject',
			'html' => 'Html',
			'text' => 'text',
			'toAddress' => 'recipient@friendica.local',
			'fromName' => 'Sender',
			'fromAddress' => 'sender@friendica.local',
			'noReply' => 'no-reply@friendica.local',
			'uid' => 100,
			'headers' => $this->defaultHeaders,
		]);
	}

	/**
	 * Test if the builder throws an exception in case no recipient
	 *
	 */
	public function testBuilderWithEmptyMail()
	{
		$this->expectException(UnprocessableEntityException::class);
		$this->expectExceptionMessage("Recipient address is missing.");

		$builder = new SampleMailBuilder($this->l10n, $this->baseUrl, $this->config, new NullLogger());

		$builder->build(true);
	}

	/**
	 * Test if the builder throws an exception in case no sender
	 */
	public function testBuilderWithEmptySender()
	{
		$this->expectException(UnprocessableEntityException::class);
		$this->expectExceptionMessage("Sender address or name is missing.");

		$builder = new SampleMailBuilder($this->l10n, $this->baseUrl, $this->config, new NullLogger());

		$builder
			->withRecipient('test@friendica.local')
			->build(true);
	}

	/**
	 * Test if the builder is capable of creating "empty" mails if needed (not the decision of the builder if so ..)
	 */
	public function testBuilderWithoutMessage()
	{
		$builder = new SampleMailBuilder($this->l10n, $this->baseUrl, $this->config, new NullLogger());

		$testEmail = $builder
			->withRecipient('recipient@friendica.local')
			->withSender('Sender', 'sender@friendica.local')
			->build(true);

		self::assertEmail($testEmail, [
			'toAddress' => 'recipient@friendica.local',
			'fromName' => 'Sender',
			'fromAddress' => 'sender@friendica.local',
			'noReply' => 'sender@friendica.local', // no-reply is set same as address in case it's not set
			'headers' => $this->defaultHeaders,
		]);
	}

	/**
	 * Test if the builder sets for the text the same as for
	 */
	public function testBuilderWithJustPreamble()
	{
		$builder = new SampleMailBuilder($this->l10n, $this->baseUrl, $this->config, new NullLogger());

		$testEmail = $builder
			->withRecipient('recipient@friendica.local')
			->withSender('Sender', 'sender@friendica.local')
			->build(true);

		self::assertEmail($testEmail, [
			'toAddress' => 'recipient@friendica.local',
			'fromName' => 'Sender',
			'fromAddress' => 'sender@friendica.local',
			'noReply' => 'sender@friendica.local', // no-reply is set same as address in case it's not set,
			'headers' => $this->defaultHeaders,
		]);
	}
}
