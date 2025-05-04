<?php

/*
 * SPDX-FileCopyrightText: Dalibor Karlovic, The Friendica project
 *
 * SPDX-License-Identifier: GPL-2.0-only
 *
 * ejabberd extauth script for the integration with friendica
 *
 * Originally written for joomla by Dalibor Karlovic <dado@krizevci.info>
 * modified for Friendica by Michael Vogel <icarus@dabo.de>
 * published under GPL
 *
 * Latest version of the original script for joomla is available at:
 * http://87.230.15.86/~dado/ejabberd/joomla-login
 */

declare(strict_types=1);

namespace Friendica\Console;

use Asika\SimpleConsole\Console;
use Exception;
use Friendica\App\BaseURL;
use Friendica\App\Mode;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Lock\Capability\ICanLock;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Database\Database;
use Friendica\Model\User;
use Friendica\Network\HTTPClient\Capability\ICanSendHttpRequests;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;
use Friendica\Network\HTTPClient\Client\HttpClientOptions;
use Friendica\Network\HTTPClient\Client\HttpClientRequest;
use Friendica\Network\HTTPException\ForbiddenException;
use Throwable;

/**
 * ejabberd supports external authentication via a small daemon (script or binary)
 * that communicates with the ejabberd server using STDIN and STDOUT and a binary protocol.
 */
final class AuthEJabberd extends Console
{
	/** @var string Command to authenticate user */
	private const COMMAND_AUTH = 'auth';
	/** @var string Command to check if user exists */
	private const COMMAND_IS_USER = 'isuser';
	/** @var string Command to set user password */
	private const COMMAND_SET_PASS = 'setpass';

	/** @var string The name of the lock for the host lock */
	private const LOCK_EX_AUTH_HOST = 'ex_auth_host';

	/** @var string The prefix for the cache */
	private const CACHE_PREFIX = 'ex_auth_';

	private int $debugMode = 0;

	private $input;
	private $output;

	public function __construct(
		private readonly Mode $mode,
		private readonly IManageConfigValues $config,
		private readonly IManagePersonalConfigValues $pConfig,
		private readonly Database $dba,
		private readonly BaseURL $baseURL,
		private readonly ICanLock $lock,
		private readonly ICanSendHttpRequests $httpClient,
		array $argv = null,
		$input = null,
		$output = null,
	) {
		parent::__construct($argv);

		$this->input  = $input  ?? fopen('php://stdin', 'rb');
		$this->output = $output ?? fopen('php://stdout', 'wb');
	}

	protected function getHelp(): string
	{
		return <<<HELP
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
	}

	/**
	 * @return int
	 * @throws Exception
	 */
	protected function doExecute(): int
	{
		$this->debugMode = (int) $this->config->get('jabber', 'debug');

		openlog('auth_ejabberd', LOG_PID, LOG_USER);

		$this->writeLog(LOG_NOTICE, 'start');

		if (!$this->mode->isNormal()) {
			$this->writeFailed('The node isn\'t ready.');
			throw new \RuntimeException('The node isn\'t ready.');
		}

		$this->readStdin();

		return 0;
	}

	/**
	 * Standard input reading function, executes the auth with the provided
	 * parameters
	 *
	 * @throws Exception
	 */
	private function readStdin(): void
	{
		while (!feof($this->input)) {
			$meta = stream_get_meta_data($this->input);
			if ($meta['eof']) {
				$this->writeFailed('we got no data, quitting');
				break;
			}

			// Quit if the database connection went down
			if (!$this->dba->isConnected()) {
				$this->writeFailed('the database connection went down');
				throw new \RuntimeException('the database connection went down');
			}

			$iHeader = fgets($this->input, 3);
			if (empty($iHeader)) {
				$this->writeFailed('empty stdin');
				break;
			}

			$aLength = unpack('n', $iHeader);
			$iLength = $aLength['1'];

			// No data? Then quit
			if ($iLength == 0) {
				$this->writeFailed('we got no data, quitting');
				break;
			}

			// Fetching the data
			$sData = fgets($this->input, $iLength + 1);
			$this->writeLog(LOG_DEBUG, 'received data: ' . $sData);
			$aCommand = explode(':', $sData);

			$cmd = $aCommand[0];
			switch ($cmd) {
				case self::COMMAND_IS_USER:
					// Check the existence of a given username
					if (count($aCommand) < 3) {
						$this->writeFailed('Invalid data.');
						break;
					};
					[$cmd, $username, $server] = $aCommand;
					$this->isUser($username, $server);
					break;
				case self::COMMAND_AUTH:
				case self::COMMAND_SET_PASS:
					if (count($aCommand) < 4) {
						$this->writeFailed('Invalid data.');
						break;
					};
					[$cmd, $username, $server, $password] = $aCommand;
					switch ($cmd) {
						case self::COMMAND_AUTH:
							// Check if the given password is correct
							$this->auth($username, $server, $password);
							break;
						case self::COMMAND_SET_PASS:
							// We don't accept the setting of passwords here
							$this->writeFailed('setpass command disabled');
							break;
					}
					break;
				default:
					// We don't know the given command
					$this->writeFailed('unknown command ' . $cmd);
					break;
			}
		}
	}

	/**
	 * Returns a cache key, based on the credentials
	 * The key is
	 *
	 * @param string $server
	 * @param string $username
	 * @param string $password
	 *
	 * @return string
	 */
	private function getCacheKey(string $server, string $username, string $password): string
	{
		return AuthEJabberd::CACHE_PREFIX . sha1("server=$server&username=$username&password=$password");
	}

	/**
	 * Check if the given username exists
	 *
	 * @throws Exception
	 */
	private function isUser(string $username, string $server): void
	{
		// We only allow one process per hostname. So we set a lock file
		// Problem: We get the firstname after the first auth - not before
		if ($this->lock->acquire(self::LOCK_EX_AUTH_HOST . ':' . $server, 10, 20)) {
			// Now we check if the given user is valid
			$username = str_replace(['%20', '(a)'], [' ', '@'], $username);

			// Does the hostname match? So we try directly
			if ($this->baseURL->getHost() == $server) {
				$this->writeLog(LOG_INFO, 'internal user check for ' . $username . '@' . $server);
				$found = $this->dba->exists('user', ['nickname' => $username]);
			} else {
				$found = false;
			}

			// If the hostnames don't match or there is some failure, we try to check remotely
			if (!$found) {
				$found = $this->checkUser($username, $server);
			}

			if ($found) {
				// The user is okay
				$this->writeSuccess('valid user: ' . $username);
			} else {
				// The user isn't okay
				$this->writeFailed('invalid user: ' . $username);
			}
			$this->lock->release(self::LOCK_EX_AUTH_HOST . ':' . $server);
		}
	}

	/**
	 * Check remote user existence via HTTP(S)
	 *
	 * @param string $user Username
	 * @param string $host The hostname
	 *
	 * @return bool Was the user found?
	 */
	private function checkUser(string $user, string $host): bool
	{
		$this->writeLog(LOG_INFO, 'external user check for ' . $user . '@' . $host);

		$url = 'https://' . $host . '/noscrape/' . $user;

		try {
			$curlResult = $this->httpClient->get($url, HttpClientAccept::JSON, [HttpClientOptions::REQUEST => HttpClientRequest::CONTACTVERIFIER]);
		} catch (Throwable) {
			return false;
		}

		if (!$curlResult->isSuccess()) {
			return false;
		}

		if ($curlResult->getReturnCode() != 200) {
			return false;
		}

		$json = @json_decode($curlResult->getBodyString());
		if (!is_object($json)) {
			return false;
		}

		return $json->nick == $user;
	}

	/**
	 * Authenticate the given user and password
	 *
	 * @param string $username
	 * @param string $server
	 * @param string $password
	 *
	 * @throws Exception
	 */
	private function auth(string $username, string $server, string $password): void
	{
		// We only allow one process per hostname. So we set a lock file
		// Problem: We get the firstname after the first auth - not before
		if ($this->lock->acquire(self::LOCK_EX_AUTH_HOST . ':' . $server, 10, 20)) {

			// We now check if the password match
			$username = str_replace(['%20', '(a)'], [' ', '@'], $username);

			$Error = false;
			// Does the hostname match? So we try directly
			if ($this->baseURL->getHost() == $server) {
				try {
					$this->writeLog(LOG_INFO, 'internal auth for ' . $username . '@' . $server);
					User::getIdFromPasswordAuthentication($username, $password, true);
				} catch (ForbiddenException) {
					// User exists, authentication failed
					$this->writeLog(LOG_INFO, 'check against alternate password for ' . $username . '@' . $server);
					$aUser = User::getByNickname($username, ['uid']);
					if (!empty($aUser['uid'])) {
						$sPassword = $this->pConfig->get($aUser['uid'], 'xmpp', 'password', null, true);
						$Error     = ($password != $sPassword);
					} else {
						$Error = true;
					}
				} catch (Throwable $ex) {
					// User doesn't exist and any other failure case
					$this->writeLog(LOG_WARNING, $ex->getMessage() . ': ' . $username);
					$Error = true;
				}
			} else {
				$Error = true;
			}

			// If the hostnames don't match or there is some failure, we try to check remotely
			if ($Error && !$this->checkCredentials($server, $username, $password)) {
				$this->writeFailed('authentication failed for user ' . $username . '@' . $server);
			} else {
				$this->writeSuccess('authenticated user ' . $username . '@' . $server);
			}
			$this->lock->release(self::LOCK_EX_AUTH_HOST . ':' . $server);
		}
	}

	/**
	 * Check remote credentials via HTTP(S)
	 *
	 * @param string $host     The hostname
	 * @param string $user     Username
	 * @param string $password Password
	 *
	 * @return bool Are the credentials okay?
	 */
	private function checkCredentials(string $host, string $user, string $password): bool
	{
		$this->writeLog(LOG_INFO, 'external credential check for ' . $user . '@' . $host);

		$url = 'https://' . $host . '/api/account/verify_credentials.json?skip_status=true';

		try {
			$curlResult = $this->httpClient->head($url, [
				HttpClientOptions::REQUEST => HttpClientRequest::CONTACTVERIFIER,
				HttpClientOptions::TIMEOUT => 5,
				HttpClientOptions::AUTH    => [
					$user,
					$password,
				],
			]);
		} catch (Throwable) {
			return false;
		}

		return ($curlResult->isSuccess() && $curlResult->getReturnCode() == 200);
	}

	/**
	 * Returns a binary "success" to the output stream
	 *
	 * @param string $message
	 * @return void
	 */
	private function writeSuccess(string $message = ''): void
	{
		if (!empty($message)) {
			$this->writeLog(LOG_NOTICE, $message);
		}
		fwrite($this->output, pack('nn', 2, 1));
	}

	/**
	 * Returns a binary "failed" to the output stream
	 *
	 * @param string $message
	 * @return void
	 */
	protected function writeFailed(string $message = ''): void
	{
		if (!empty($message)) {
			$this->writeLog(LOG_ERR, $message);
		}
		fwrite($this->output, pack('nn', 2, 0));
	}

	/**
	 * write data to the syslog
	 *
	 * @param int    $loglevel The syslog loglevel
	 * @param string $message  The syslog message
	 */
	private function writeLog(int $loglevel, string $message): void
	{
		if (!$this->debugMode && ($loglevel >= LOG_DEBUG)) {
			return;
		}
		syslog($loglevel, $message);
	}
}
