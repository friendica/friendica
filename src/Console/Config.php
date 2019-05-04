<?php

namespace Friendica\Console;

use Asika\SimpleConsole\CommandArgsException;
use Asika\SimpleConsole\Console;
use Friendica\App\Mode;
use Friendica\Core\Config\Configuration;
use RuntimeException;

/**
 * @brief tool to access the system config from the CLI
 *
 * With this script you can access the system configuration of your node from
 * the CLI. You can do both, reading current values stored in the database and
 * set new values to config variables.
 *
 * Usage:
 *   If you specify no parameters at the CLI, the script will list all config
 *   variables defined.
 *
 *   If you specify one parameter, the script will list all config variables
 *   defined in this section of the configuration (e.g. "system").
 *
 *   If you specify two parameters, the script will show you the current value
 *   of the named configuration setting. (e.g. "system loglevel")
 *
 *   If you specify three parameters, the named configuration setting will be
 *   set to the value of the last parameter. (e.g. "system loglevel 0" will
 *   disable logging)
 *
 * @author Tobias Diekershoff <tobias.diekershoff@gmx.net>
 * @author Hypolite Petovan <hypolite@mrpetovan.com>
 */
class Config extends Console
{
	protected $helpOptions = ['h', 'help', '?'];

	protected function getHelp()
	{
		$help = <<<HELP
console config - Manage site configuration
Synopsis
	bin/console config [-h|--help|-?] [-v]
	bin/console config <category> [-h|--help|-?] [-v]
	bin/console config <category> <key> [-h|--help|-?] [-v]
	bin/console config <category> <key> <value> [-h|--help|-?] [-v]

Description
	bin/console config
		Lists all config values

	bin/console config <category>
		Lists all config values in the provided category

	bin/console config <category> <key>
		Shows the value of the provided key in the category

	bin/console config <category> <key> <value>
		Sets the value of the provided key in the category

Notes:
	Setting config entries which are manually set in config/local.config.php may result in
	conflict between database settings and the manual startup settings.

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
		$this->mode = $mode;
		$this->config = $config;

		parent::__construct($argv);
	}

	protected function doExecute()
	{
		if ($this->getOption('v')) {
			$this->out('Executable: ' . $this->executable);
			$this->out('Class: ' . __CLASS__);
			$this->out('Arguments: ' . var_export($this->args, true));
			$this->out('Options: ' . var_export($this->options, true));
		}

		if (count($this->args) > 3) {
			throw new CommandArgsException('Too many arguments');
		}

		if (!$this->mode->has(Mode::DBCONFIGAVAILABLE)) {
			$this->out('Database isn\'t ready or populated yet, showing file config only');
		}

		if (count($this->args) == 3) {
			$cat = $this->getArgument(0);
			$key = $this->getArgument(1);
			$value = $this->getArgument(2);

			if (is_array($this->config->get($cat, $key))) {
				throw new RuntimeException("$cat.$key is an array and can't be set using this command.");
			}

			$result = $this->config->set($cat, $key, $value);
			if ($result) {
				$this->out("{$cat}.{$key} <= " .
					$this->config->get($cat, $key));
			} else {
				$this->out("Unable to set {$cat}.{$key}");
			}
		}

		if (count($this->args) == 2) {
			$cat = $this->getArgument(0);
			$key = $this->getArgument(1);
			$value = $this->config->get($this->getArgument(0), $this->getArgument(1));

			if (is_array($value)) {
				foreach ($value as $k => $v) {
					$this->out("{$cat}.{$key}[{$k}] => " . (is_array($v) ? implode(', ', $v) : $v));
				}
			} else {
				$this->out("{$cat}.{$key} => " . $value);
			}
		}

		if (count($this->args) == 1) {
			$cat = $this->getArgument(0);
			$this->config->load($cat);

			if ($this->config->getCache()->get($cat) !== null) {
				$this->out("[{$cat}]");
				$catVal = $this->config->getCache()->get($cat);
				foreach ($catVal as $key => $value) {
					if (is_array($value)) {
						foreach ($value as $k => $v) {
							$this->out("{$key}[{$k}] => " . (is_array($v) ? implode(', ', $v) : $v));
						}
					} else {
						$this->out("{$key} => " . $value);
					}
				}
			} else {
				$this->out('Config section ' . $this->getArgument(0) . ' returned nothing');
			}
		}

		if (count($this->args) == 0) {
			$this->config->load();

			if ($this->config->get('system', 'config_adapter') == 'jit' && $this->mode->has(Mode::DBCONFIGAVAILABLE)) {
				$this->out('Warning: The JIT (Just In Time) Config adapter doesn\'t support loading the entire configuration, showing file config only');
			}

			$config = $this->config->getCache()->getAll();
			foreach ($config as $cat => $section) {
				if (is_array($section)) {
					foreach ($section as $key => $value) {
						if (is_array($value)) {
							foreach ($value as $k => $v) {
								$this->out("{$cat}.{$key}[{$k}] => " . (is_array($v) ? implode(', ', $v) : $v));
							}
						} else {
							$this->out("{$cat}.{$key} => " . $value);
						}
					}
				} else {
					$this->out("config.{$cat} => " . $section);
				}
			}
		}

		return 0;
	}
}
