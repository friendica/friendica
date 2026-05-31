<?php

namespace Friendica\Test\src\Console;

use Friendica\App\Mode;
use Friendica\Console\AuthEJabberd;
use Friendica\DI;
use Friendica\Network\HTTPClient\Capability\ICanHandleHttpResponses;
use Friendica\Network\HTTPClient\Capability\ICanSendHttpRequests;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;
use Friendica\Network\HTTPClient\Client\HttpClientOptions;
use Friendica\Network\HTTPClient\Client\HttpClientRequest;
use Friendica\Test\ConsoleTestCase;
use Friendica\Test\FixtureTestTrait;
use Mockery;
use Mockery\MockInterface;

class EjabberdAuthTest extends ConsoleTestCase
{
	use FixtureTestTrait;

	/** @var int Default HTTP timeout the daemon applies to outgoing requests */
	private const DEFAULT_HTTP_TIMEOUT = 5;

	private $inputStream;
	private $outputStream;

	/** @var Mode|MockInterface */
	private $mode;
	/** @var ICanSendHttpRequests|MockInterface */
	private $httpClient;

	protected function setUp(): void
	{
		parent::setUp();

		$this->setUpFixtures();
		$this->mode       = \Mockery::mock(Mode::class);
		$this->httpClient = \Mockery::mock(ICanSendHttpRequests::class);

		$this->loadFixture(__DIR__ . '/../../datasets/ejabberd/fixture.php', DI::dba());

		$this->inputStream  = fopen('php://memory', 'r+');
		$this->outputStream = fopen('php://memory', 'w+');
	}

	protected function tearDown(): void
	{
		$this->tearDownFixtures();

		parent::tearDown();
	}

	private function sendInput(string $payload, bool $rewind = true)
	{
		$bin = pack('n', strlen($payload)) . $payload;

		fwrite($this->inputStream, $bin);
	}

	private function assertSuccess(): void
	{
		$this->assertEquals(pack('nn', 2, 1), fread($this->outputStream, 4), 'Expected success'); // 2-byte length + 2-byte response
	}

	private function assertFailed(): void
	{
		$this->assertEquals(pack('nn', 2, 0), fread($this->outputStream, 4), 'Expected success'); // 2-byte length + 2-byte response
	}

	private function newConsole(): AuthEJabberd
	{
		return new AuthEJabberd(
			$this->mode,
			DI::config(),
			DI::pConfig(),
			DI::dba(),
			DI::baseUrl(),
			$this->httpClient,
			$this->consoleArgv,
			$this->inputStream,
			$this->outputStream,
		);
	}

	public function testWrongMode(): void
	{
		$this->mode->shouldReceive('isNormal')->andReturn(false)->once();

		$console = $this->newConsole();

		$txt = $this->dumpExecute($console);
		rewind($this->outputStream);
		$this->assertFailed();
		$this->assertSame(1, $this->consoleExecReturn);
		$this->assertEquals("[Error] The node isn't ready.\n", $txt);
	}

	public static function dataAuth(): array
	{
		return [
			'empty' => [
				'input' => [
					'',
				],
				'assertion' => [
					false,
				],
			],
			'wrongCommand' => [
				'input' => [
					'wrong:command:so',
				],
				'assertion' => [
					false,
				],
			],
			'isuserValid' => [
				'input' => [
					"isuser:admin:friendica.local",
				],
				'assertion' => [
					true,
				],
			],
			'isuserThreeDifferent' => [
				'input' => [
					"isuser:admin:friendica.local",
					"isuser:wrong:friendica.local",
					"isuser:user:friendica.local",
				],
				'assertion' => [
					true,
					false,
					true,
				],
			],
			'isuserTooShort' => [
				'input' => [
					'isuser',
					'isuser:admin',
					'isuser:admin:friendica.local',
				],
				'assertion' => [
					false,
					false,
					true,
				],
			],
			'authValid' => [
				'input' => [
					"auth:admin:friendica.local:admin",
				],
				'assertion' => [
					true,
				],
			],
			'authThreeDifferent' => [
				'input' => [
					"auth:admin:friendica.local:admin",
					"auth:admin:friendica.local:wrong",
					"auth:user:friendica.local:user",
				],
				'assertion' => [
					true,
					false,
					true,
				],
			],
			'authWrongPassword' => [
				'input' => [
					"auth:admin:friendica.local:wrong",
				],
				'assertion' => [
					false,
				],
			],
			'authTooShort' => [
				'input' => [
					'auth',
					'auth:admin',
					'auth:admin:friendica.local',
					'auth:admin:friendica.local:admin',
				],
				'assertion' => [
					false,
					false,
					false,
					true,
				],
			],
			'setpassNotSupported' => [
				'input' => [
					'setpass',
					'setpass:admin',
					'setpass:admin:friendica.local',
					'setpass:admin:friendica.local:admin',
				],
				'assertion' => [
					false,
					false,
					false,
					false,
				],
			],
		];
	}

	#[\PHPUnit\Framework\Attributes\TestDox('Various inputs are routed correctly and never crash the daemon')]
	#[\PHPUnit\Framework\Attributes\DataProvider('dataAuth')]
	public function testData(array $input, array $assertion): void
	{
		$this->mode->shouldReceive('isNormal')->andReturn(true)->once();

		// Any remote lookup defaults to "not found" so the daemon has to rely on the local fixtures.
		// The dedicated remote success/failure paths are covered by testCheckUserExternal()/testCheckCredentialsExternal().
		$notFound = Mockery::mock(ICanHandleHttpResponses::class);
		$notFound->shouldReceive('isSuccess')->andReturn(false);
		$notFound->shouldReceive('getReturnCode')->andReturn(404);
		$notFound->shouldReceive('getBodyString')->andReturn('');
		$this->httpClient->shouldReceive('get')->andReturn($notFound)->byDefault();
		$this->httpClient->shouldReceive('head')->andReturn($notFound)->byDefault();

		DI::config()->set('jabber', 'debug', 1);

		foreach ($input as $payload) {
			$this->sendInput($payload);
		}
		rewind($this->inputStream);

		$console = $this->newConsole();

		$txt = $this->dumpExecute($console);
		$this->assertSame(0, $this->consoleExecReturn);
		$this->assertEmpty($txt);

		rewind($this->outputStream);
		foreach ($assertion as $assertType) {
			if ($assertType) {
				$this->assertSuccess();
			} else {
				$this->assertFailed();

			}
		}
	}

	public static function dataCheckCredentialsExternal(): array
	{
		return [
			'authWrongUser' => [
				'payload' => 'auth:wrong:friendica.local:someelse',
				// Only 200 is valid - not even 202
				'assertUrl'  => 'https://friendica.local/api/account/verify_credentials.json?skip_status=true',
				'isSuccess'  => true,
				'returnCode' => 202,
				'assertion'  => false,
			],
			'authRightUser' => [
				'payload'    => 'auth:wrong:friendica.local:someelse',
				'assertUrl'  => 'https://friendica.local/api/account/verify_credentials.json?skip_status=true',
				'isSuccess'  => true,
				'returnCode' => 200,
				'assertion'  => true,
			],
			'authRightUserOtherDomain' => [
				'payload'    => 'auth:wrong:friendi.ca:someelse',
				'assertUrl'  => 'https://friendi.ca/api/account/verify_credentials.json?skip_status=true',
				'isSuccess'  => true,
				'returnCode' => 200,
				'assertion'  => true,
			],
		];
	}

	#[\PHPUnit\Framework\Attributes\TestDox('The remote credential check endpoint is built correctly')]
	#[\PHPUnit\Framework\Attributes\DataProvider('dataCheckCredentialsExternal')]
	public function testCheckCredentialsExternal(string $payload, string $assertUrl, bool $isSuccess, int $returnCode, bool $assertion): void
	{
		$this->mode->shouldReceive('isNormal')->andReturn(true)->once();
		$response = Mockery::mock(ICanHandleHttpResponses::class);
		$response->shouldReceive('isSuccess')->andReturn($isSuccess);
		$response->shouldReceive('getReturnCode')->andReturn($returnCode);
		$this->httpClient->shouldReceive('head')->with($assertUrl, [
			HttpClientOptions::REQUEST => HttpClientRequest::CONTACTVERIFIER,
			HttpClientOptions::TIMEOUT => self::DEFAULT_HTTP_TIMEOUT,
			HttpClientOptions::AUTH    => [
				'wrong',
				'someelse',
			],
		])->andReturn($response)->once();

		$this->sendInput($payload);
		rewind($this->inputStream);

		$console = $this->newConsole();

		$txt = $this->dumpExecute($console);

		$this->assertSame(0, $this->consoleExecReturn);
		$this->assertEmpty($txt);

		rewind($this->outputStream);
		if ($assertion) {
			$this->assertSuccess();
		} else {
			$this->assertFailed();
		}
	}


	public static function dataCheckUserExternal(): array
	{
		return [
			'isuserWrongUser' => [
				'payload' => 'isuser:wrong:friendica.local',
				// Only 200 is valid - not even 202
				'assertUrl'  => 'https://friendica.local/noscrape/wrong',
				'isSuccess'  => true,
				'returnCode' => 202,
				'bodyString' => '',
				'assertion'  => false,
			],
			'isuserNoSuccess' => [
				'payload' => 'isuser:wrong:friendica.local',
				// Only 200 is valid - not even 202
				'assertUrl'  => 'https://friendica.local/noscrape/wrong',
				'isSuccess'  => false,
				'returnCode' => 202,
				'bodyString' => '',
				'assertion'  => false,
			],
			'isuserRightUser' => [
				'payload'    => 'isuser:wrong:friendica.local',
				'assertUrl'  => 'https://friendica.local/noscrape/wrong',
				'isSuccess'  => true,
				'returnCode' => 200,
				'bodyString' => json_encode(['nick' => 'wrong']),
				'assertion'  => true,
			],
			'isuserRightUserOtherDomain' => [
				'payload'    => 'isuser:wrong:friendi.ca',
				'assertUrl'  => 'https://friendi.ca/noscrape/wrong',
				'isSuccess'  => true,
				'returnCode' => 200,
				'bodyString' => json_encode(['nick' => 'wrong']),
				'assertion'  => true,
			],
			'isuserEmptyBody' => [
				'payload'    => 'isuser:wrong:friendica.local',
				'assertUrl'  => 'https://friendica.local/noscrape/wrong',
				'isSuccess'  => true,
				'returnCode' => 200,
				'bodyString' => json_encode([]),
				'assertion'  => false,
			],
		];
	}

	#[\PHPUnit\Framework\Attributes\TestDox('The remote user existence check endpoint is built correctly')]
	#[\PHPUnit\Framework\Attributes\DataProvider('dataCheckUserExternal')]
	public function testCheckUserExternal(string $payload, string $assertUrl, bool $isSuccess, int $returnCode, string $bodyString, bool $assertion): void
	{
		$this->mode->shouldReceive('isNormal')->andReturn(true)->once();
		$response = Mockery::mock(ICanHandleHttpResponses::class);
		$response->shouldReceive('isSuccess')->andReturn($isSuccess);
		$response->shouldReceive('getReturnCode')->andReturn($returnCode);
		$response->shouldReceive('getBodyString')->andReturn($bodyString);
		$this->httpClient->shouldReceive('get')
						 ->with($assertUrl, HttpClientAccept::JSON, [
						 	HttpClientOptions::REQUEST => HttpClientRequest::CONTACTVERIFIER,
						 	HttpClientOptions::TIMEOUT => self::DEFAULT_HTTP_TIMEOUT,
						 ])
						 ->andReturn($response)->once();

		$this->sendInput($payload);
		rewind($this->inputStream);

		$console = $this->newConsole();

		$txt = $this->dumpExecute($console);

		$this->assertSame(0, $this->consoleExecReturn);
		$this->assertEmpty($txt);

		rewind($this->outputStream);
		if ($assertion) {
			$this->assertSuccess();
		} else {
			$this->assertFailed();
		}
	}

	/**
	 * The daemon must never block a worker indefinitely: ejabberd recycles a worker by closing
	 * its STDIN and relies on the program seeing EOF, so a slow remote host may not keep the
	 * worker busy past ejabberd's own call timeout. Every outgoing request therefore carries a
	 * short, configurable timeout (jabber.auth_http_timeout) - this guards against orphaned
	 * workers piling up ("auth flood").
	 */
	#[\PHPUnit\Framework\Attributes\TestDox('Outgoing HTTP requests use the configured bounded timeout')]
	public function testHttpRequestsUseConfiguredTimeout(): void
	{
		$this->mode->shouldReceive('isNormal')->andReturn(true)->once();

		DI::config()->set('jabber', 'auth_http_timeout', 3);

		// isuser -> checkUser() (GET) must carry the configured timeout
		$response = Mockery::mock(ICanHandleHttpResponses::class);
		$response->shouldReceive('isSuccess')->andReturn(false);
		$response->shouldReceive('getReturnCode')->andReturn(500);
		$response->shouldReceive('getBodyString')->andReturn('');
		$this->httpClient->shouldReceive('get')
						 ->with('https://friendi.ca/noscrape/someone', HttpClientAccept::JSON, [
						 	HttpClientOptions::REQUEST => HttpClientRequest::CONTACTVERIFIER,
						 	HttpClientOptions::TIMEOUT => 3,
						 ])
						 ->andReturn($response)->once();

		// auth -> checkCredentials() (HEAD) must carry the same configured timeout
		$this->httpClient->shouldReceive('head')
						 ->with('https://friendi.ca/api/account/verify_credentials.json?skip_status=true', [
						 	HttpClientOptions::REQUEST => HttpClientRequest::CONTACTVERIFIER,
						 	HttpClientOptions::TIMEOUT => 3,
						 	HttpClientOptions::AUTH    => ['someone', 'secret'],
						 ])
						 ->andReturn($response)->once();

		$this->sendInput('isuser:someone:friendi.ca');
		$this->sendInput('auth:someone:friendi.ca:secret');
		rewind($this->inputStream);

		$console = $this->newConsole();

		$txt = $this->dumpExecute($console);
		$this->assertSame(0, $this->consoleExecReturn);
		$this->assertEmpty($txt);

		rewind($this->outputStream);
		$this->assertFailed();
		$this->assertFailed();
	}

	/**
	 * Tests, if the auth per pConfig works
	 *
	 * @return void
	 */
	public function testAuthForbidden(): void
	{
		$this->mode->shouldReceive('isNormal')->andReturn(true)->once();

		DI::pConfig()->set(51, 'xmpp', 'password', 'pConfigPW');

		$this->sendInput('auth:admin:friendica.local:pConfigPW');
		rewind($this->inputStream);

		$console = $this->newConsole();

		$txt = $this->dumpExecute($console);
		$this->assertEmpty($txt);

		rewind($this->outputStream);
		$this->assertSuccess();
	}

	/**
	 * Test if the database is gone
	 *
	 * @return void
	 */
	public function testDbaDisconnected(): void
	{
		$this->mode->shouldReceive('isNormal')->andReturn(true)->once();

		DI::dba()->disconnect();

		$this->sendInput('auth:admin:friendica.local:pConfigPW');
		rewind($this->inputStream);

		$console = $this->newConsole();

		$txt = $this->dumpExecute($console);
		$this->assertSame(1, $this->consoleExecReturn);
		$this->assertEquals("[Error] the database connection went down\n", $txt);

		rewind($this->outputStream);
		$this->assertFailed();
	}

	/**
	 * Just tests the help output
	 *
	 * @return void
	 */
	public function testGetHelp()
	{
		// Usable to purposely fail if new commands are added without taking tests into account
		$theHelp = <<<HELP
auth_ejabberd - Daemon that communicates with the ejabberd server
Synopsis
	bin/console auth_ejabberd [-h|--help|-?] [-v]

Description
    ejabberd supports external authentication via a small daemon (script or binary)
    that communicates with the ejabberd server using STDIN and STDOUT and a binary protocol.

Options
    -h|--help|-?            Show help information
    -v                      Show more debug information.

Examples
	bin/console auth_ejabberd
		Starts the daemon and reads per STDIN

HELP;
		$console = $this->newConsole();
		$console->setOption('help', true);

		$txt = $this->dumpExecute($console);

		self::assertEquals($txt, $theHelp);
	}
}
