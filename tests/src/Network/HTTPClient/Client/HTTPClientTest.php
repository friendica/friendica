<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Network\HTTPClient\Client;

use Friendica\Core\System;
use Friendica\DI;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;
use Friendica\Network\HTTPClient\Client\HttpClientOptions;
use Friendica\Network\HTTPClient\Factory\HttpClient as HttpClientFactory;
use Friendica\Util\Network;
use Friendica\Test\DiceHttpMockHandlerTrait;
use Friendica\Test\MockedTestCase;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\FnStream;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

class HTTPClientTest extends MockedTestCase
{
	use DiceHttpMockHandlerTrait;

	/** @var resource|null Built-in PHP web server */
	private $server = null;

	protected function setUp(): void
	{
		parent::setUp();

		$this->setupHttpMockHandler();
	}

	protected function tearDown(): void
	{
		if (is_resource($this->server)) {
			proc_terminate($this->server);
			proc_close($this->server);
		}

		$this->tearDownHandler();

		parent::tearDown();
	}

	/**
	 * Test for issue https://github.com/friendica/friendica/issues/10473#issuecomment-907749093
	 */
	public function testInvalidURI(): void
	{
		$this->httpRequestHandler->setHandler(new MockHandler([
			new Response(301, ['Location' => 'https:///']),
		]));

		self::assertFalse(DI::httpClient()->get('https://friendica.local')->isSuccess());
	}

	/**
	 * Test for issue https://github.com/friendica/friendica/issues/11726
	 */
	public function testRedirect(): void
	{
		$this->httpRequestHandler->setHandler(new MockHandler([
			new Response(302, ['Location' => 'https://mastodon.social/about']),
			new Response(200, ['Location' => 'https://mastodon.social']),
		]));

		$result = DI::httpClient()->get('https://mastodon.social');
		self::assertEquals('https://mastodon.social', $result->getUrl());
		self::assertEquals('https://mastodon.social/about', $result->getRedirectUrl());
	}

	private static function cookieResponse(): Response
	{
		return new Response(200, ['Set-Cookie' => 'session=abc; Domain=mastodon.social; Max-Age=3600'], 'hello');
	}

	public function testCookieJarIsSaved(): void
	{
		$cookiejar = tempnam(System::getTempPath(), 'cookiejar-test-');

		try {
			$this->httpRequestHandler->setHandler(new MockHandler([self::cookieResponse()]));

			DI::httpClient()->get('https://mastodon.social', HttpClientAccept::DEFAULT, [HttpClientOptions::COOKIEJAR => $cookiejar]);

			self::assertStringContainsString('"Name":"session"', (string) file_get_contents($cookiejar));
		} finally {
			unlink($cookiejar);
		}
	}

	/**
	 * A cookie jar that can't be saved (e.g. full disk) must not fail the request.
	 */
	public function testCookieJarSaveFailureKeepsResponse(): void
	{
		$cookiejar = System::getTempPath() . '/missing-' . bin2hex(random_bytes(8)) . '/cookiejar';

		$this->httpRequestHandler->setHandler(new MockHandler([self::cookieResponse()]));

		$result = @DI::httpClient()->get('https://mastodon.social', HttpClientAccept::DEFAULT, [HttpClientOptions::COOKIEJAR => $cookiejar]);

		self::assertTrue($result->isSuccess());
		self::assertEquals('hello', $result->getBodyString());
	}

	/**
	 * A remote cookie that can't be encoded must not fail the request.
	 */
	public function testInvalidCookieKeepsResponse(): void
	{
		$cookiejar = tempnam(System::getTempPath(), 'cookiejar-test-');

		try {
			$this->httpRequestHandler->setHandler(new MockHandler([
				new Response(200, ['Set-Cookie' => "session=\xff\xfe; Domain=mastodon.social; Max-Age=3600"], 'hello'),
			]));

			$result = DI::httpClient()->get('https://mastodon.social', HttpClientAccept::DEFAULT, [HttpClientOptions::COOKIEJAR => $cookiejar]);

			self::assertTrue($result->isSuccess());
			self::assertEquals('hello', $result->getBodyString());
		} finally {
			unlink($cookiejar);
		}
	}

	public static function privateTargetProvider(): array
	{
		return [
			'loopback'       => ['http://127.0.0.1:9999/'],
			'loopback v6'    => ['http://[::1]:9999/'],
			'private'        => ['http://10.1.2.3/'],
			'private 192'    => ['http://192.168.178.1/'],
			'cloud metadata' => ['http://169.254.169.254/latest/meta-data/'],
			'v4 mapped'      => ['http://[::ffff:127.0.0.1]/'],
		];
	}

	/** Ensures private targets are not requested. */
	#[DataProvider('privateTargetProvider')]
	public function testPrivateTargetIsNotRequested(string $url): void
	{
		$mock = new MockHandler([new Response(200, [], 'internal secret')]);
		$this->httpRequestHandler->setHandler($mock);

		self::assertFalse(DI::httpClient()->get($url)->isSuccess());
		self::assertCount(1, $mock, 'the request was sent even though the target is private');
	}

	/** Ensures redirects to private targets are not followed. */
	public function testRedirectToPrivateTargetIsNotFollowed(): void
	{
		$mock = new MockHandler([
			new Response(302, ['Location' => 'http://127.0.0.1:9999/redir-target']),
			new Response(200, [], 'internal secret'),
		]);
		$this->httpRequestHandler->setHandler($mock);

		self::assertFalse(DI::httpClient()->get('https://mastodon.social')->isSuccess());
		self::assertCount(1, $mock, 'the redirect into the private network was followed');
	}

	public static function unsupportedSchemeProvider(): array
	{
		return [
			'file'   => ['file://localhost/etc/passwd'],
			'gopher' => ['gopher://localhost/1'],
			'ftp'    => ['ftp://localhost/file'],
		];
	}

	#[DataProvider('unsupportedSchemeProvider')]
	public function testUnsupportedSchemeIsRejected(string $url): void
	{
		$mock = new MockHandler([new Response(200)]);
		$this->httpRequestHandler->setHandler($mock);

		self::assertFalse(DI::httpClient()->get($url)->isSuccess());
		self::assertCount(1, $mock);
	}

	/** Ensures public targets remain reachable. */
	public function testPublicTargetIsStillRequested(): void
	{
		$this->httpRequestHandler->setHandler(new MockHandler([new Response(200, [], 'hello')]));

		$result = DI::httpClient()->get('https://mastodon.social');
		self::assertTrue($result->isSuccess());
		self::assertEquals('hello', $result->getBodyString());
	}

	/** Ensures the node can request its own base URL. */
	public function testOwnBaseUrlIsNeverPrivate(): void
	{
		self::assertFalse(Network::isPrivateTarget(DI::baseUrl()));
	}

	public function testAllowedInternalHostIsRequested(): void
	{
		DI::config()->set('system', 'allowed_internal_hosts', '127.0.0.1');

		$this->httpRequestHandler->setHandler(new MockHandler([new Response(200, [], 'hello')]));

		self::assertTrue(DI::httpClient()->get('http://127.0.0.1:9999/thumb.jpg')->isSuccess());
	}

	public function testCheckCanBeDisabled(): void
	{
		DI::config()->set('system', 'block_private_addresses', false);

		$this->httpRequestHandler->setHandler(new MockHandler([new Response(200, [], 'internal')]));

		self::assertTrue(DI::httpClient()->get('http://127.0.0.1:9999/')->isSuccess());
	}

	/**
	 * Starts the built-in PHP web server with SinkTestRouter.php and sends requests through curl
	 *
	 * @return string Base URL of the server
	 */
	private function startServer(): string
	{
		$socket = stream_socket_server('tcp://127.0.0.1:0');
		$port   = (int) substr((string) strrchr((string) stream_socket_get_name($socket, false), ':'), 1);
		fclose($socket);

		$this->server = proc_open(
			[PHP_BINARY, '-S', '127.0.0.1:' . $port, __DIR__ . '/SinkTestRouter.php'],
			[['pipe', 'r'], ['file', '/dev/null', 'w'], ['file', '/dev/null', 'w']],
			$pipes,
		);

		for ($i = 0; $i < 50; $i++) {
			$connection = @fsockopen('127.0.0.1', $port);
			if ($connection) {
				fclose($connection);
				break;
			}
			usleep(100000);
		}

		DI::config()->set('system', 'block_private_addresses', false);
		$this->httpRequestHandler->setHandler(new CurlHandler());

		return 'http://127.0.0.1:' . $port;
	}

	/**
	 * Returns the response body temp files in the temp path
	 */
	private static function getSinkFiles(): array
	{
		return glob(System::getTempPath() . '/http-*') ?: [];
	}

	/**
	 * The response body must not be stored in a named file, not even during the transfer.
	 * A process that ends before deleting such a file leaves it behind.
	 */
	public function testResponseBodyIsNotStoredInNamedFile(): void
	{
		$before = self::getSinkFiles();
		$during = null;

		// The mock handler copies the body into the sink during the transfer
		$body = FnStream::decorate(Utils::streamFor('hello'), [
			'__toString' => function () use (&$during): string {
				$during ??= self::getSinkFiles();
				return 'hello';
			},
		]);

		$this->httpRequestHandler->setHandler(new MockHandler([new Response(200, [], $body)]));

		$result = DI::httpClient()->get('https://mastodon.social');

		self::assertSame($before, $during);
		self::assertSame($before, self::getSinkFiles());
		self::assertEquals('hello', $result->getBodyString());
	}

	/**
	 * The body of a redirect response must not end up in the body of the final response.
	 */
	public function testRedirectBodyIsNotPartOfFinalBody(): void
	{
		$url = $this->startServer();

		$result = DI::httpClient()->get($url . '/redirect');

		self::assertEquals($url . '/final', $result->getRedirectUrl());
		self::assertEquals('final', $result->getBodyString());
	}

	public static function oversizedResponseProvider(): array
	{
		return [
			'announced, call site limit'   => ['/big', [HttpClientOptions::CONTENT_LENGTH => 1000000], 0],
			'unannounced, call site limit' => ['/big-unannounced', [HttpClientOptions::CONTENT_LENGTH => 1000000], 0],
			'announced, global limit'      => ['/big', [], 1000000],
			'unannounced, global limit'    => ['/big-unannounced', [], 1000000],
		];
	}

	/**
	 * A response body above the limit must not be received, whether the size is announced or not.
	 */
	#[DataProvider('oversizedResponseProvider')]
	public function testOversizedResponseIsAborted(string $path, array $opts, int $globalLimit): void
	{
		DI::config()->set('performance', 'max_download_size', $globalLimit);

		$url    = $this->startServer();
		$result = DI::httpClient()->get($url . $path, HttpClientAccept::DEFAULT, $opts);

		self::assertFalse($result->isSuccess());
		self::assertSame('', $result->getBodyString());
	}

	/**
	 * An aborted oversized response must be visible at the default log level, it names the URL and the limit.
	 */
	#[DataProvider('oversizedResponseProvider')]
	public function testOversizedResponseIsLoggedAsNotice(string $path, array $opts, int $globalLimit): void
	{
		DI::config()->set('performance', 'max_download_size', $globalLimit);

		$url    = $this->startServer();
		$logger = self::createRecordingLogger();
		$client = (new HttpClientFactory($logger, DI::config(), DI::profiler(), DI::baseUrl()))->createClient($this->httpRequestHandler);

		$client->get($url . $path, HttpClientAccept::DEFAULT, $opts);

		$notices = array_values(array_filter($logger->records, fn (array $record): bool => $record['level'] === LogLevel::NOTICE));
		self::assertCount(1, $notices);
		self::assertSame('Response exceeds the size limit.', $notices[0]['message']);
		self::assertSame($url . $path, $notices[0]['context']['url']);
		self::assertSame(1000000, $notices[0]['context']['limit']);
	}

	/**
	 * Other transfer errors keep their log level, the notice only covers the size limit.
	 */
	public function testOtherTransferErrorIsNotLoggedAsOversized(): void
	{
		$url = $this->startServer();
		proc_terminate($this->server);
		proc_close($this->server);
		$this->server = null;

		$logger = self::createRecordingLogger();
		$client = (new HttpClientFactory($logger, DI::config(), DI::profiler(), DI::baseUrl()))->createClient($this->httpRequestHandler);

		self::assertFalse($client->get($url . '/final')->isSuccess());
		self::assertSame([], array_filter($logger->records, fn (array $record): bool => $record['level'] === LogLevel::NOTICE));
	}

	/**
	 * @return AbstractLogger&object{records: list<array{level: mixed, message: string, context: array<mixed>}>}
	 */
	private static function createRecordingLogger(): AbstractLogger
	{
		return new class () extends AbstractLogger {
			/** @var list<array{level: mixed, message: string, context: array<mixed>}> */
			public array $records = [];

			public function log($level, string|\Stringable $message, array $context = []): void
			{
				$this->records[] = ['level' => $level, 'message' => (string) $message, 'context' => $context];
			}
		};
	}

	/**
	 * The limit applies to each response of a redirect chain, not to their sum.
	 */
	public function testRedirectBodiesDoNotAddUp(): void
	{
		DI::config()->set('performance', 'max_download_size', 1000000);

		$url    = $this->startServer();
		$result = DI::httpClient()->get($url . '/redirect-big');

		self::assertTrue($result->isSuccess());
		self::assertSame(str_repeat('f', 600000), $result->getBodyString());
	}

	/**
	 * A disabled global limit keeps large responses working.
	 */
	public function testGlobalLimitCanBeDisabled(): void
	{
		DI::config()->set('performance', 'max_download_size', 0);

		$url    = $this->startServer();
		$result = DI::httpClient()->get($url . '/big-unannounced');

		self::assertTrue($result->isSuccess());
		self::assertSame(3000000, strlen($result->getBodyString()));
	}

	/**
	 * A worker process that is killed during a download must not leave the received data behind.
	 */
	public function testKilledRequestLeavesNoTempFile(): void
	{
		if (!function_exists('pcntl_fork')) {
			self::markTestSkipped('pcntl is required');
		}

		$url    = $this->startServer();
		$before = self::getSinkFiles();

		$pid = pcntl_fork();
		if ($pid === 0) {
			try {
				DI::httpClient()->get($url . '/slow');
			} finally {
				// Never return into the test runner
				posix_kill(posix_getpid(), SIGKILL);
			}
		}

		// The download takes 10 seconds
		sleep(2);
		posix_kill($pid, SIGKILL);
		pcntl_waitpid($pid, $status);

		$leftover = array_values(array_diff(self::getSinkFiles(), $before));
		array_map(unlink(...), $leftover);

		self::assertTrue(pcntl_wifsignaled($status));
		self::assertSame([], $leftover);
	}
}
