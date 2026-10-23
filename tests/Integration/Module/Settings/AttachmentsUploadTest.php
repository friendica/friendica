<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Integration\Module\Settings;

use Friendica\App\Router;
use Friendica\BaseModule;
use Friendica\Core\Storage\Type\Database;
use Friendica\DI;
use Friendica\Model\Attach;
use Friendica\Module\Response;
use Friendica\Module\Settings\Attachments;
use Friendica\Network\HTTPException\FoundException;
use Friendica\Test\FixtureTestCase;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;

class AttachmentsUploadTest extends FixtureTestCase
{
	private const USER_ID  = 42; // Local user from api.fixture.php.
	private const FILENAME = 'upload-limit-regression.txt';

	private array $originalFiles;
	private array $originalRequest;
	private string $uploadPath;

	protected function setUp(): void
	{
		parent::setUp();
		$this->originalFiles   = $_FILES;
		$this->originalRequest = $_REQUEST;
		$this->uploadPath      = $this->root->url() . '/test/' . self::FILENAME;
		$this->useHttpMethod(Router::POST);
		DI::config()->set('storage', 'name', Database::NAME);
		DI::userSession()->set('authenticated', true);
		DI::userSession()->set('uid', self::USER_ID);
	}

	protected function tearDown(): void
	{
		$_FILES   = $this->originalFiles;
		$_REQUEST = $this->originalRequest;
		parent::tearDown();
	}

	/** @return array<string, array{?string, int, bool}> */
	public static function uploadLimits(): array
	{
		return [
			'default limit'       => [null, 16, true],
			'exactly at limit'    => ['1K', 1024, true],
			'one byte over limit' => ['1K', 1025, false],
		];
	}

	#[DataProvider('uploadLimits')]
	public function testUploadRespectsConfiguredLimit(?string $limit, int $size, bool $accepted): void
	{
		if ($limit === null) {
			self::assertSame(0, DI::config()->get('system', 'maxfilesize'));
		} else {
			DI::config()->set('system', 'maxfilesize', $limit);
		}

		$content = str_repeat('x', $size);
		file_put_contents($this->uploadPath, $content);
		$_FILES = ['userfile' => [
			'name'     => self::FILENAME,
			'tmp_name' => $this->uploadPath,
			'size'     => $size,
			'type'     => 'text/plain',
			'error'    => UPLOAD_ERR_OK,
		]];
		$_REQUEST     = ['form_security_token' => BaseModule::getFormSecurityToken('settings_attachments')];
		$storageCount = DI::dba()->count('storage');
		self::assertTrue(BaseModule::checkFormSecurityToken('settings_attachments'));

		// Rendering the settings sidebar is unrelated to upload validation.
		$module = $this->getMockBuilder(Attachments::class)
			->setConstructorArgs([DI::userSession(), DI::page(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), new Response(), []])
			->onlyMethods(['content'])
			->getMock();
		$module->method('content')->willReturn('');

		try {
			$module->handleRequest((new ServerRequest(Router::POST, DI::baseUrl() . '/settings/attachments'))->withParsedBody($_REQUEST));
		} catch (FoundException) {
			// Redirects terminate module dispatch; the persisted state below is the contract.
		}

		$attachment = Attach::selectFirst([], ['uid' => self::USER_ID, 'filename' => self::FILENAME]);
		if ($accepted) {
			self::assertIsArray($attachment, 'A valid upload must persist with the default or an explicit size limit.');
			self::assertSame($content, Attach::getData($attachment));
			self::assertSame($size, (int) $attachment['filesize']);
		} else {
			self::assertFalse($attachment);
			self::assertSame($storageCount, DI::dba()->count('storage'), 'A rejected upload must not leave stored bytes behind.');
		}
		self::assertFileDoesNotExist($this->uploadPath, 'The temporary upload must be removed.');
	}
}
