<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core;

use Friendica;
use Friendica\App;

/**
 * Description of Console
 */
class Console extends \Asika\SimpleConsole\Console
{
	/** @var string The default executable for a console call */
	private const CONSOLE_EXECUTABLE = 'bin/console.php';

	/**
	 * @return string The default executable for a console call
	 */
	public static function getDefaultExecutable(): string
	{
		return self::CONSOLE_EXECUTABLE;
	}

	// Disables the default help handling
	protected $helpOptions             = [];
	protected array $customHelpOptions = ['h', 'help', '?'];

	/**
	 * @var Container The Container
	 */
	protected Container $container;

	protected function getHelp()
	{
		$help = <<<HELP
Usage: bin/console [--version] [-h|--help|-?] <command> [<args>] [-v]

Commands:
	daemon                 Interact with the Friendica daemon
	help                   Show help about a command, e.g (bin/console help config)
	jetstream              Interact with the Jetstream daemon
	worker                 Start worker process

	node management
		archivecontact         Archive a contact when you know that it isn't existing anymore
		cache                  Manage node cache
		contact                Contact management
		globalcommunityblock   Block remote profile from interacting with this node
		globalcommunitysilence Silence a profile from the global community page
		lock                   Edit site locks
		mergecontacts          Merge duplicated contact entries
		serverblock            Manage blocked servers
		relay                  Manage ActivityPub relay servers
		user                   User management

	node configuration
		addon                  Addon management
		autoinstall            Starts automatic installation of friendica based on values from htconfig.php
		clearavatarcache       Clear the file based avatar cache
		config                 Edit site config
		dbstructure            Do database updates
		maintenance            Set maintenance mode for this node
		movetoavatarcache      Move cached avatars to the file based avatar cache
		postupdate             Execute pending post update scripts (can last days)
		relocate               Update node base URL
		storage                Manage storage backend

	developer
		createdoxygen          Generate Doxygen headers
		docbloxerrorchecker    Check the file tree for DocBlox errors
		extract                Generate translation string file for the Friendica project (deprecated)
		php2po                 Generate a messages.po file from a strings.php file
		po2php                 Generate a strings.php file from a messages.po file
		typo                   Checks for parse errors in Friendica files

Options:
	-h|--help|-? Show help information
	-v           Show more debug information.
HELP;
		return $help;
	}

	protected array $subConsoles = [
		'addon'                             => Friendica\Console\Addon::class,
		'archivecontact'                    => Friendica\Console\ArchiveContact::class,
		'autoinstall'                       => Friendica\Console\AutomaticInstallation::class,
		'cache'                             => Friendica\Console\Cache::class,
		'clearavatarcache'                  => Friendica\Console\ClearAvatarCache::class,
		'config'                            => Friendica\Console\Config::class,
		'contact'                           => Friendica\Console\Contact::class,
		'createdoxygen'                     => Friendica\Console\CreateDoxygen::class,
		'daemon'                            => Friendica\Console\Daemon::class,
		'jetstream'                         => Friendica\Console\JetstreamDaemon::class,
		'worker'                            => Friendica\Console\Worker::class,
		'docbloxerrorchecker'               => Friendica\Console\DocBloxErrorChecker::class,
		'dbstructure'                       => Friendica\Console\DatabaseStructure::class,
		'extract'                           => Friendica\Console\Extract::class,
		'fixapdeliveryworkertaskparameters' => Friendica\Console\FixAPDeliveryWorkerTaskParameters::class,
		'globalcommunityblock'              => Friendica\Console\GlobalCommunityBlock::class,
		'globalcommunitysilence'            => Friendica\Console\GlobalCommunitySilence::class,
		'lock'                              => Friendica\Console\Lock::class,
		'maintenance'                       => Friendica\Console\Maintenance::class,
		'mergecontacts'                     => Friendica\Console\MergeContacts::class,
		'movetoavatarcache'                 => Friendica\Console\MoveToAvatarCache::class,
		'php2po'                            => Friendica\Console\PhpToPo::class,
		'postupdate'                        => Friendica\Console\PostUpdate::class,
		'po2php'                            => Friendica\Console\PoToPhp::class,
		'relay'                             => Friendica\Console\Relay::class,
		'relocate'                          => Friendica\Console\Relocate::class,
		'serverblock'                       => Friendica\Console\ServerBlock::class,
		'storage'                           => Friendica\Console\Storage::class,
		'test'                              => Friendica\Console\Test::class,
		'typo'                              => Friendica\Console\Typo::class,
		'user'                              => Friendica\Console\User::class,
	];

	/**
	 * CliInput Friendica constructor.
	 *
	 * @param Container $container The Friendica container
	 */
	public function __construct(Container $container, array $argv = null)
	{
		parent::__construct($argv);

		$this->container = $container;
	}

	public static function create(Container $container, array $argv = null): Console
	{
		return new self($container, $argv);
	}

	protected function doExecute(): int
	{
		if ($this->getOption('v')) {
			$this->out('Executable: ' . $this->executable);
			$this->out('Arguments: ' . var_export($this->args, true));
			$this->out('Options: ' . var_export($this->options, true));
		}

		$subHelp = false;
		$command = null;

		if ($this->getOption('version')) {
			$this->out('Friendica Console version ' . App::VERSION);

			return 0;
		} elseif ((count($this->options) === 0 || $this->getOption($this->customHelpOptions) === true || $this->getOption($this->customHelpOptions) === 1) && count($this->args) === 0
		) {
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

		/** @var Console $subconsole */
		$subconsole = $this->container->create($className, [$subargs]);

		foreach ($this->options as $name => $value) {
			$subconsole->setOption($name, $value);
		}

		return $subconsole;
	}
}
