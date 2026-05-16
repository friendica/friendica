<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Console;

use Friendica\App\Mode;
use Friendica\Core\Config\Capability\IManageConfigValues;

/**
 * Sets maintenance mode for this node
 */
class Maintenance extends \Asika\SimpleConsole\Console
{
	protected $helpOptions = ['h', 'help', '?'];

	/**
	 * @var Mode
	 */
	private $appMode;
	/**
	 * @var IManageConfigValues
	 */
	private $config;

	protected function getHelp()
	{
		$help = <<<HELP
console maintenance - Sets maintenance mode for this node
Usage
	bin/console maintenance <enable> [<reason>] [-h|--help|-?] [-v]

Description
	<enable> cen be either 0 or 1 to disabled or enable the maintenance mode on this node.

	<reason> is a quote-enclosed string with the optional reason for the maintenance mode.

Examples
	bin/console maintenance 1
		Enables the maintenance mode without setting a reason message

	bin/console maintenance 1 "SSL certification update"
		Enables the maintenance mode with setting a reason message

	bin/console maintenance 0
		Disables the maintenance mode

Options
    -h|--help|-? Show help information
    -v           Show more debug information.
HELP;
		return $help;
	}

	public function __construct(Mode $appMode, IManageConfigValues $config, $argv = null)
	{
		parent::__construct($argv);

		$this->appMode = $appMode;
		$this->config  = $config;
	}

	protected function doExecute(): int
	{
		if ($this->getOption('v')) {
			$this->out('Class: ' . self::class);
			$this->out('Arguments: ' . var_export($this->args, true));
			$this->out('Options: ' . var_export($this->options, true));
		}

		if (count($this->args) == 0) {
			$this->out($this->getHelp());
			return 0;
		}

		if (count($this->args) > 2) {
			throw new \Asika\SimpleConsole\CommandArgsException('Too many arguments');
		}

		if ($this->appMode->isInstall()) {
			throw new \RuntimeException('Database isn\'t ready or populated yet');
		}

		$enabled = intval($this->getArgument(0));

		$transactionConfig = $this->config->beginTransaction();

		$transactionConfig->set('system', 'maintenance', $enabled);

		$reason = $this->getArgument(1);

		if ($enabled && $this->getArgument(1)) {
			$transactionConfig->set('system', 'maintenance_reason', $this->getArgument(1));
		} else {
			$transactionConfig->delete('system', 'maintenance_reason');
		}

		$transactionConfig->commit();

		if ($enabled) {
			$mode_str = "maintenance mode";
		} else {
			$mode_str = "normal mode";
		}

		$this->out('System set in ' . $mode_str);

		if ($enabled && $reason != '') {
			$this->out('Maintenance reason: ' . $reason);
		}

		return 0;
	}

}
