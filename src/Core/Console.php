<?php

namespace Friendica\Core;

/**
 * Description of Console
 *
 * @author Hypolite Petovan <mrpetovan@gmail.com>
 */
class Console extends \Asika\SimpleConsole\Console
{
	// Disables the default help handling
	protected $helpOptions = [];
	protected $customHelpOptions = ['h', 'help', '?'];

	protected $subConsoles = [
		'config'                 => __NAMESPACE__ . '\Console\Config',
		'createdoxygen'          => __NAMESPACE__ . '\Console\CreateDoxygen',
		'docbloxerrorchecker'    => __NAMESPACE__ . '\Console\DocBloxErrorChecker',
		'dbstructure'            => __NAMESPACE__ . '\Console\DatabaseStructure',
		'extract'                => __NAMESPACE__ . '\Console\Extract',
		'globalcommunityblock'   => __NAMESPACE__ . '\Console\GlobalCommunityBlock',
		'globalcommunitysilence' => __NAMESPACE__ . '\Console\GlobalCommunitySilence',
		'maintenance'            => __NAMESPACE__ . '\Console\Maintenance',
		'newpassword'            => __NAMESPACE__ . '\Console\NewPassword',
		'php2po'                 => __NAMESPACE__ . '\Console\PhpToPo',
		'po2php'                 => __NAMESPACE__ . '\Console\PoToPhp',
		'typo'                   => __NAMESPACE__ . '\Console\Typo',
	];

	protected function getHelp()
	{
		$help = <<<HELP
Usage: bin/console [--version] [-h|--help|-?] <command> [<args>] [-v]

Commands:
	config                 Edit site config
	createdoxygen          Generate Doxygen headers
	dbstructure            Do database updates
	docbloxerrorchecker    Check the file tree for DocBlox errors
	extract                Generate translation string file for the Friendica project (deprecated)
	globalcommunityblock   Block remote profile from interacting with this node
	globalcommunitysilence Silence remote profile from global community page
	help                   Show help about a command, e.g (bin/console help config)
	maintenance            Set maintenance mode for this node
	newpassword            Set a new password for a given user
	php2po                 Generate a messages.po file from a strings.php file
	po2php                 Generate a strings.php file from a messages.po file
	typo                   Checks for parse errors in Friendica files

Options:
	-h|--help|-? Show help information
	-v           Show more debug information.
HELP;
		return $help;
	}

	protected function doExecute()
	{
		if ($this->getOption('v')) {
			$this->out('Executable: ' . $this->executable);
			$this->out('Arguments: ' . var_export($this->args, true));
			$this->out('Options: ' . var_export($this->options, true));
		}

		$showHelp = false;
		$subHelp = false;
		$command = null;

		if ($this->getOption('version')) {
			$this->out('Friendica Console version ' . FRIENDICA_VERSION);

			return 0;
		} elseif ((count($this->options) === 0 || $this->getOption($this->customHelpOptions) === true || $this->getOption($this->customHelpOptions) === 1) && count($this->args) === 0
		) {
			$showHelp = true;
		} elseif (count($this->args) >= 2 && $this->getArgument(0) == 'help') {
			$command = $this->getArgument(1);
			$subHelp = true;
			array_shift($this->args);
			array_shift($this->args);
		} elseif (count($this->args) >= 1) {
			$command = $this->getArgument(0);
			array_shift($this->args);
		}

		if (is_null($command)) {
			$this->out($this->getHelp());
			return 0;
		}

		$console = $this->getSubConsole($command);

		if ($subHelp) {
			$console->setOption($this->customHelpOptions, true);
		}

		return $console->execute();
	}

	private function getSubConsole($command)
	{
		if ($this->getOption('v')) {
			$this->out('Command: ' . $command);
		}

		if (!isset($this->subConsoles[$command])) {
			throw new \Asika\SimpleConsole\CommandArgsException('Command ' . $command . ' doesn\'t exist');
		}

		$subargs = $this->args;
		array_unshift($subargs, $this->executable);

		$className = $this->subConsoles[$command];

		$subconsole = new $className($subargs);

		foreach ($this->options as $name => $value) {
			$subconsole->setOption($name, $value);
		}

		return $subconsole;
	}

}
