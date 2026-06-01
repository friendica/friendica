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
use Friendica\App\Mode;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Security\EJabberdAuth;
use Throwable;

/**
 * Console command that runs the ejabberd external authentication daemon.
 *
 * Protocol (see ejabberd src/extauth.erl, port opened with `[{packet, 2}]`):
 *   - ejabberd sends:  2-byte big-endian length + colon-separated command payload
 *   - the daemon answers: 2-byte length (always 2) + 2-byte result (0x0000 fail / 0x0001 ok)
 *   - commands: "isuser:user:server", "auth:user:server:password", "setpass:..."
 *   - ejabberd keeps a fixed pool of these processes (`extauth_pool_size`) and recycles a
 *     worker by closing its STDIN, upon which the read loop here terminates naturally.
 *   - ejabberd aborts a call after 30s, so the daemon must never block longer than that;
 *     all outgoing HTTP requests in {@see EJabberdAuth} use a much shorter, bounded timeout.
 *
 * This class is intentionally thin: it owns only the binary wire protocol (framing,
 * command dispatch, response encoding) and the daemon lifecycle. All authentication
 * business logic lives in {@see EJabberdAuth}.
 */
final class EJabberdAuthDaemon extends Console
{
	private const COMMAND_AUTH     = 'auth';
	private const COMMAND_IS_USER  = 'isuser';
	private const COMMAND_SET_PASS = 'setpass';

	/** @var int Number of bytes in the big-endian length prefix of each frame */
	private const LENGTH_PREFIX_BYTES = 2;

	private int $debugMode = 0;

	private $input;
	private $output;

	public function __construct(
		private readonly Mode $mode,
		private readonly IManageConfigValues $config,
		private readonly EJabberdAuth $auth,
		?array $argv = null,
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

	protected function doExecute(): int
	{
		$this->debugMode = (int) $this->config->get('jabber', 'debug');

		openlog('auth_ejabberd', LOG_PID, LOG_USER);
		$this->writeLog(LOG_NOTICE, 'start');

		if (!$this->mode->isNormal()) {
			$this->writeLog(LOG_ERR, 'The node isn\'t ready.');
			throw new \RuntimeException('The node isn\'t ready.');
		}

		$this->readStdin();

		return 0;
	}

	/**
	 * Main daemon loop: reads frames from ejabberd until STDIN is closed (worker recycled).
	 */
	private function readStdin(): void
	{
		while (!feof($this->input)) {
			$payload = $this->readFrame();
			if ($payload === null) {
				// STDIN closed / no more data: ejabberd is recycling this worker.
				break;
			}

			// If the database connection went down we cannot authenticate. Answer the pending
			// request immediately (so ejabberd does not wait for its 30s call timeout) and then
			// exit, so the ejabberd pool respawns a fresh worker with a working connection.
			if (!$this->auth->isConnected()) {
				$this->writeLog(LOG_ERR, 'the database connection went down');
				$this->writeResult(false);
				throw new \RuntimeException('the database connection went down');
			}

			$this->writeResult($this->handle($payload));
		}
	}

	/**
	 * Reads a single length-prefixed frame in a binary-safe way.
	 *
	 * @return string|null the payload, or null if the stream ended before a full frame arrived
	 */
	private function readFrame(): ?string
	{
		$header = $this->readBytes(self::LENGTH_PREFIX_BYTES);
		if ($header === null) {
			return null;
		}

		$length = unpack('n', $header)['1'];
		if ($length === 0) {
			return null;
		}

		return $this->readBytes($length);
	}

	/**
	 * Reads exactly $count bytes from the input stream, looping over partial reads.
	 *
	 * Using fread (not fgets) is mandatory: the length prefix and payload are binary and
	 * may legitimately contain newline bytes, which fgets would treat as a delimiter.
	 *
	 * @return string|null the bytes, or null on EOF before $count bytes were available
	 */
	private function readBytes(int $count): ?string
	{
		$buffer = '';
		while (strlen($buffer) < $count) {
			$chunk = fread($this->input, $count - strlen($buffer));
			if ($chunk === false || $chunk === '') {
				return null;
			}
			$buffer .= $chunk;
		}

		return $buffer;
	}

	/**
	 * Dispatches a single command payload and returns whether it succeeded.
	 */
	private function handle(string $payload): bool
	{
		$this->writeLog(LOG_DEBUG, 'received data: ' . $payload);

		// Limit to 4 parts so a ':' inside the password is not silently truncated.
		$command = explode(':', $payload, 4);

		try {
			switch ($command[0]) {
				case self::COMMAND_IS_USER:
					if (count($command) < 3) {
						$this->writeLog(LOG_WARNING, 'invalid isuser command');
						return false;
					}
					[, $username, $server] = $command;
					return $this->auth->isUser($username, $server);

				case self::COMMAND_AUTH:
					if (count($command) < 4) {
						$this->writeLog(LOG_WARNING, 'invalid auth command');
						return false;
					}
					[, $username, $server, $password] = $command;
					return $this->auth->authenticate($username, $server, $password);

				case self::COMMAND_SET_PASS:
					// Setting passwords from XMPP into Friendica is intentionally not supported.
					$this->writeLog(LOG_NOTICE, 'setpass command disabled');
					return false;

				default:
					$this->writeLog(LOG_NOTICE, 'unknown command ' . $command[0]);
					return false;
			}
		} catch (Throwable $exception) {
			// A single failing request must never take the daemon down — answer "no" and
			// keep serving the next request.
			$this->writeLog(LOG_ERR, 'error handling command: ' . $exception->getMessage());
			return false;
		}
	}

	/**
	 * Writes the 4-byte ejabberd response frame (length 2 + boolean result).
	 */
	private function writeResult(bool $success): void
	{
		fwrite($this->output, pack('nn', 2, $success ? 1 : 0));
	}

	private function writeLog(int $loglevel, string $message): void
	{
		if (!$this->debugMode && $loglevel >= LOG_DEBUG) {
			return;
		}
		syslog($loglevel, $message);
	}
}
