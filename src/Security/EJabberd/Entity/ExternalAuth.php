<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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
 */

namespace Friendica\Security\EJabberd\Entity;

use Friendica\App;
use Friendica\Core\Cache\Enum\Duration;
use Friendica\Core\Lock\Capability\ICanLock;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\System;
use Friendica\Database\Database;
use Friendica\Model\User;
use Friendica\Network\HTTPClient\Capability\ICanSendHttpRequests;
use Friendica\Network\HTTPException;
use Friendica\Security\EJabberd\Exception\EjabberdAuthenticationException;
use Friendica\Security\EJabberd\Exception\EjabberdInvalidCommandException;
use Friendica\Security\EJabberd\Exception\EJabberdInvalidUserException;
use Psr\Log\LoggerInterface;

/**
 * Authentication class for ejabbered external authentication
 * @see https://docs.ejabberd.im/developer/guide/#external
 */
class ExternalAuth
{
	const LOCK_PREFIX = 'ejabberd:lock:';

	/** @var string */
	private $host;

	/** @var App\Mode */
	private $appMode;
	/** @var IManagePersonalConfigValues */
	private $pConfig;
	/** @var Database */
	private $dba;
	/** @var App\BaseURL */
	private $baseURL;
	/** @var LoggerInterface */
	private $logger;
	/** @var ICanSendHttpRequests */
	private $httpClient;
	/** @var ICanLock */
	private $lock;

	public function __construct(App\Mode $appMode, IManagePersonalConfigValues $pConfig, Database $dba, App\BaseURL $baseURL, LoggerInterface $logger, ICanSendHttpRequests $httpClient, ICanLock $lock)
	{
		$this->appMode    = $appMode;
		$this->pConfig    = $pConfig;
		$this->dba        = $dba;
		$this->baseURL    = $baseURL;
		$this->logger     = $logger;
		$this->httpClient = $httpClient;
		$this->lock       = $lock;
	}

	/**
	 * Standard input reading function, executes the auth with the provided parameters
	 */
	public function execute()
	{
		$this->logger->notice('start');

		if (!$this->appMode->isNormal()) {
			$this->logger->error('The node isn\'t ready.');
			return;
		}

		try {
			while (!feof(STDIN)) {
				// Quit if the database connection went down
				if (!$this->dba->isConnected()) {
					throw new EjabberdAuthenticationException('the database connection went down');
				}

				$iHeader = fgets(STDIN, 3);
				if (empty($iHeader)) {
					throw new EjabberdAuthenticationException('empty stdin');
				}

				$aLength = unpack('n', $iHeader);
				$iLength = $aLength['1'];

				// No data? Then quit
				if ($iLength == 0) {
					throw new EjabberdAuthenticationException('we got no data, quitting');
				}

				// Fetching the data
				$sData = fgets(STDIN, $iLength + 1);
				$this->logger->debug('received data.', ['data' => $sData]);
				$aCommand = explode(':', $sData);
				if (is_array($aCommand)) {
					switch ($aCommand[0]) {
						case 'isuser':
							// Check the existence of a given username
							$this->isUser($aCommand);
							break;
						case 'auth':
							// Check if the given password is correct
							$this->auth($aCommand);
							break;
						case 'setpass':
							// We don't accept the setting of passwords here
							throw new EjabberdInvalidCommandException('setpass command disabled');
						default:
							// We don't know the given command
							throw new EjabberdInvalidCommandException(sprintf('unknown command: %s', $aCommand[0]));
					}
				} else {
					throw new EjabberdInvalidCommandException(sprintf('invalid command string: %s', $sData));
				}
			}
		} catch (EJabberdInvalidUserException $exception) {
			$this->logger->warning('Invalid user.', ['exception' => $exception]);
			$this->loginFailed();
		} catch (EjabberdAuthenticationException $exception) {
			$this->logger->error('Internal error.', ['exception' => $exception]);
			$this->loginFailed();
		} catch (EjabberdInvalidCommandException $exception) {
			$this->logger->notice('Invalid command.', ['exception' => $exception]);
			$this->loginFailed();
		} finally {
			$this->releaseHost();
		}
	}

	/**
	 * Set failure/invalid login for jabberd per STDOUT
	 *
	 * write to stdout: AABB
	 * A: the number 2 (coded as a short, which is bytes length of following result)
	 * B: the result code (coded as a short), should be 1 for success/valid, or 0 for failure/invalid
	 */
	protected function loginFailed()
	{
		fwrite(STDOUT, pack('nn', 2, 0));
	}

	/**
	 * Set success/valid login for jabberd per STDOUT
	 *
	 * write to stdout: AABB
	 * A: the number 2 (coded as a short, which is bytes length of following result)
	 * B: the result code (coded as a short), should be 1 for success/valid, or 0 for failure/invalid
	 */
	protected function loginSuccess()
	{
		fwrite(STDOUT, pack('nn', 2, 1));
	}

	/**
	 * Check if the given username exists
	 *
	 * @param array $aCommand The command array
	 *
	 * @throws EJabberdInvalidUserException
	 * @throws EjabberdAuthenticationException
	 * @throws EjabberdInvalidCommandException
	 */
	private function isUser(array $aCommand)
	{
		// Check if there is a username
		if (!isset($aCommand[1])) {
			throw new EjabberdInvalidCommandException('invalid isuser command, no username given');
		}

		try {
			// We only allow one process per hostname. So we set a lock file
			// Problem: We get the firstname after the first auth - not before
			$this->lockHost($aCommand[2]);

			// Now we check if the given user is valid
			$sUser = str_replace(['%20', '(a)'], [' ', '@'], $aCommand[1]);

			// Does the hostname match? So we try directly
			if ($this->baseURL->getHostname() == $aCommand[2]) {
				$this->logger->info('internal user check.', ['user' => $sUser, 'host' => $aCommand[2]]);
				$found = $this->dba->exists('user', ['nickname' => $sUser]);
			} else {
				$found = false;
			}

			// If the hostnames doesn't match or there is some failure, we try to check remotely
			if (!$found) {
				$found = $this->checkUser($aCommand[2], $aCommand[1], true);
			}

			if ($found) {
				// The user is okay
				$this->logger->notice('valid user found.', ['user' => $sUser]);
				$this->loginSuccess();
			} else {
				// The user isn't okay
				throw new EJabberdInvalidUserException(sprintf('invalid user: %s', $sUser));
			}
		} catch (\Exception $e) {
			throw new EjabberdAuthenticationException('Internal exception', $e);
		} finally {
			$this->releaseHost();
		}
	}

	/**
	 * Check remote user existence via HTTP(S)
	 *
	 * @param string  $host The hostname
	 * @param string  $user Username
	 * @param boolean $ssl  Should the check be done via SSL?
	 *
	 * @return boolean Was the user found?
	 */
	private function checkUser(string $host, string $user, bool $ssl): bool
	{
		$this->logger->info('external user check.', ['user' => "$user@$host"]);

		$url = ($ssl ? 'https' : 'http') . '://' . $host . '/noscrape/' . $user;

		$curlResult = $this->httpClient->get($url);

		if (!$curlResult->isSuccess()) {
			return false;
		}

		if ($curlResult->getReturnCode() != 200) {
			return false;
		}

		$json = @json_decode($curlResult->getBody());
		if (!is_object($json)) {
			return false;
		}

		return $json->nick == $user;
	}

	/**
	 * Authenticate the given user and password
	 *
	 * @param array $aCommand The command array
	 *
	 * @throws EJabberdInvalidUserException
	 * @throws EjabberdInvalidCommandException
	 */
	private function auth(array $aCommand)
	{
		// check user authentication
		if (sizeof($aCommand) != 4) {
			throw new EjabberdInvalidCommandException('invalid auth command, data missing');
		}

		try {
			// We only allow one process per hostname. So we set a lock file
			// Problem: We get the firstname after the first auth - not before
			$this->lockHost($aCommand[2]);

			// We now check if the password match
			$sUser = str_replace(['%20', '(a)'], [' ', '@'], $aCommand[1]);

			$Error = false;
			// Does the hostname match? So we try directly
			if ($this->baseURL->getHostname() == $aCommand[2]) {
				try {
					$this->logger->info('Retrieve internal auth.', ['user' => $sUser, 'host' => $aCommand[2]]);
					User::getIdFromPasswordAuthentication($sUser, $aCommand[3], true);
				} catch (HTTPException\ForbiddenException $ex) {
					// User exists, authentication failed
					$this->logger->info('check against alternate password.', ['user' => $sUser, 'host' => $aCommand[2]]);
					try {
						$aUser     = User::getByNickname($sUser, ['uid']);
						$sPassword = $this->pConfig->get($aUser['uid'], 'xmpp', 'password', null, true);
						$Error     = ($aCommand[3] != $sPassword);
					} catch (\Throwable $ex) {
						// User doesn't exist and any other failure case
						$this->logger->warning('Cannot retrieve user-data', [
							'callstack' => System::callstack(),
							'exception' => $ex
						]);
						$Error = true;
					}
				} catch (\Throwable $ex) {
					// User doesn't exist and any other failure case
					$this->logger->warning('Cannot retrieve user-data', [
						'callstack' => System::callstack(),
						'exception' => $ex
					]);
					$Error = true;
				}
			} else {
				$Error = true;
			}

			// If the hostnames doesn't match or there is some failure, we try to check remotely
			if ($Error && !$this->checkCredentials($aCommand[2], $aCommand[1], $aCommand[3], true)) {
				throw new EJabberdInvalidUserException(sprintf('authentification failed for user: %s@%s', $sUser, $aCommand[2]));
			} else {
				$this->logger->notice('authenificated user.', ['user' => $sUser, 'host' => $aCommand[2]]);
				$this->loginSuccess();
			}
		} finally {
			$this->releaseHost();
		}
	}

	/**
	 * Check remote credentials via HTTP(S)
	 *
	 * @param string  $host     The hostname
	 * @param string  $user     Username
	 * @param string  $password Password
	 * @param boolean $ssl      Should the check be done via SSL?
	 *
	 * @return boolean Are the credentials okay?
	 */
	private function checkCredentials(string $host, string $user, string $password, bool $ssl): bool
	{
		$this->logger->info('external credential check.', ['user' => $user, 'host' => $host]);

		$url = ($ssl ? 'https' : 'http') . '://' . $host . '/api/account/verify_credentials.json?skip_status=true';

		$curlResponse = $this->httpClient->head($url);

		$http_code = $curlResponse->getReturnCode();

		$this->logger->info('External auth response.', ['user' => $user, 'host' => $host, 'http_code' => $http_code]);

		return $http_code == 200;
	}

	/**
	 * We only allow one process per hostname. So we lock the hostname
	 *
	 * @param string $host The hostname
	 */
	private function lockHost(string $host)
	{
		if (!empty($this->host)) {
			return;
		}

		$this->logger->info('Try lock hostname for process.', ['host' => $host]);

		if (!$this->lock->acquire(self::LOCK_PREFIX . $host, Duration::MINUTE)) {
			throw new EjabberdAuthenticationException('Cannot acquire lock');
		}

		$this->host = $host;
	}

	/**
	 * Release a potential lock of the current host
	 */
	private function releaseHost()
	{
		if (!empty($this->host)) {
			$this->logger->info('Release Lock for current process.', ['host' => $this->host]);
			if ($this->lock->release(self::LOCK_PREFIX . $this->host, true)) {
				$this->host = null;
			}
		} else {
			$this->logger->debug('No host to release.');
		}
	}

	/**
	 * destroy the class, close the syslog connection.
	 */
	public function __destruct()
	{
		$this->logger->notice('stop');
	}
}
