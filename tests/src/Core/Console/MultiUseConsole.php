<?php

namespace Friendica\Test\src\Core\Console;

use Friendica\Core\Console;

/**
 * Adds two methods to the Friendica\Core\Console so we can reuse it during tests
 *
 * @package Friendica\Test\src\Core\Console
 */
class MultiUseConsole extends Console
{
	public function reset() {
		$this->args = [];
		$this->options = [];
	}

	public function parseTestArgv($argv)
	{
		$this->parseArgv($argv);
	}
}
