<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Console;

class Test extends \Asika\SimpleConsole\Console
{
	protected $helpOptions = ['h', 'help', '?'];

	protected function getHelp()
	{
		$help = <<<HELP
console test - Placeholder console command
Usage
	bin/console test [-h|--help|-?] [-v]

Description
	Placeholder console command for developer tests

Options
    -h|--help|-? Show help information
    -v           Show more debug information.
HELP;
		return $help;
	}

	protected function doExecute(): int
	{
		if ($this->getOption('v')) {
			$this->out('Class: ' . self::class);
			$this->out('Arguments: ' . var_export($this->args, true));
			$this->out('Options: ' . var_export($this->options, true));
		}

		/*
		 * Please use this console command for your CLI tests, do not commit anything inside this method.
		 */

		return 0;
	}
}
