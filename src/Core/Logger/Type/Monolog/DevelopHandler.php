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
 *
 */

namespace Friendica\Core\Logger\Type\Monolog;

use Monolog\Handler;
use Monolog\Logger;

/**
 * Simple handler for Friendica developers to use for deeper logging
 *
 * If you want to debug only interactions from your IP or the IP of a remote server for federation debug,
 * you'll use Logger::develop() for the duration of your work, and you clean it up when you're done before submitting your PR.
 */
class DevelopHandler extends Handler\AbstractHandler
{
	/**
	 * @var string The IP of the developer who wants to debug
	 */
	private $developerIp;

	/**
	 * @param string $developerIp  The IP of the developer who wants to debug
	 * @param int    $level        The minimum logging level at which this handler will be triggered
	 * @param bool   $bubble       Whether the messages that are handled can bubble up the stack or not
	 */
	public function __construct($developerIp, $level = Logger::DEBUG, bool $bubble = true)
	{
		parent::__construct($level, $bubble);

		$this->developerIp = $developerIp;
	}

	/**
	 * {@inheritdoc}
	 */
	public function handle(array $record): bool
	{
		if (!$this->isHandling($record)) {
			return false;
		}

		/// Just in case the remote IP is the same as the developer IP log the output
		if (!is_null($this->developerIp) && $_SERVER['REMOTE_ADDR'] != $this->developerIp) {
			return false;
		}

		return false === $this->bubble;
	}
}
