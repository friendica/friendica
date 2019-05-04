<?php

namespace Friendica\Console;

use Asika\SimpleConsole\Console;
use Friendica\App\Mode;
use Friendica\Core\Config\Configuration;

/**
 * @brief Sets maintenance mode for this node
 *
 * @author Hypolite Petovan <hypolite@mrpetovan.com>
 */
class Maintenance extends Console
{
	protected $helpOptions = ['h', 'help', '?'];

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

	/**
	 * @var Configuration
	 */
	private $config;
	/**
	 * @var Mode
	 */
	private $mode;

	public function __construct(Mode $mode, Configuration $config, $argv = null)
	{
		$this->config = $config;
		$this->mode = $mode;

		parent::__construct($argv);
	}

	protected function doExecute()
	{
		if ($this->getOption('v')) {
			$this->out('Class: ' . __CLASS__);
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

		if ($this->mode->isInstall()) {
			throw new \RuntimeException('Database isn\'t ready or populated yet');
		}

		$enabled = intval($this->getArgument(0));

		$this->config->set('system', 'maintenance', $enabled);

		$reason = $this->getArgument(1);

		if ($enabled && $this->getArgument(1)) {
			$this->config->set('system', 'maintenance_reason', $this->getArgument(1));
		} else {
			$this->config->set('system', 'maintenance_reason', '');
		}

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
