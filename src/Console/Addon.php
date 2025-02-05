<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Console;

use Console_Table;
use Friendica\App\Mode;
use Friendica\Core\L10n;
use Friendica\Core\Addon\AddonHelper;
use Friendica\Database\Database;
use Friendica\Util\Strings;
use RuntimeException;

/**
 * tool to manage addons on the current node
 */
class Addon extends \Asika\SimpleConsole\Console
{
	protected $helpOptions = ['h', 'help', '?'];

	/**
	 * @var Mode
	 */
	private $appMode;
	/**
	 * @var L10n
	 */
	private $l10n;
	/**
	 * @var Database
	 */
	private $dba;
	private AddonHelper $addonHelper;

	protected function getHelp()
	{
		$help = <<<HELP
console user - Modify addon settings per console commands.
Usage
	bin/console addon list all [-h|--help|-?] [-v]
	bin/console addon list enabled [-h|--help|-?] [-v]
	bin/console addon list disabled [-h|--help|-?] [-v]
	bin/console addon enable <addonname> [-h|--help|-?] [-v]
	bin/console addon disable <addonname> [-h|--help|-?] [-v]

Description
	Modify addon settings per console commands.

Options
    -h|--help|-? Show help information
    -v           Show more debug information
HELP;
		return $help;
	}

	public function __construct(Mode $appMode, L10n $l10n, Database $dba, AddonHelper $addonHelper, array $argv = null)
	{
		parent::__construct($argv);

		$this->appMode     = $appMode;
		$this->l10n        = $l10n;
		$this->dba         = $dba;
		$this->addonHelper = $addonHelper;

		$this->addonHelper->loadAddons();
	}

	protected function doExecute(): int
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

		if ($this->appMode->isInstall()) {
			throw new RuntimeException('Database isn\'t ready or populated yet');
		}

		$command = $this->getArgument(0);

		switch ($command) {
			case 'list':
				return $this->list();
			case 'enable':
				return $this->enable();
			case 'disable':
				return $this->disable();
			default:
				throw new \Asika\SimpleConsole\CommandArgsException('Wrong command.');
		}
	}

	/**
	 * Lists plugins
	 *
	 * @return int|bool Return code of this command, false on error (?)
	 * @throws \Exception
	 */
	private function list()
	{
		$subCmd = $this->getArgument(1);

		$table = new Console_Table();
		switch ($subCmd) {
			case 'all':
				$table->setHeaders(['Name', 'Enabled']);
				break;
			case 'enabled':
			case 'disabled':
				$table->setHeaders(['Name']);
				break;
			default:
				$this->out($this->getHelp());
				return false;
		}

		foreach ($this->addonHelper->getAvailableAddons() as $addonId) {
			$enabled = $this->addonHelper->isAddonEnabled($addonId);

			if ($subCmd === 'all') {
				$table->addRow([$addonId, $enabled ? 'enabled' : 'disabled']);

				continue;
			}

			if ($subCmd === 'enabled' && $enabled === true) {
				$table->addRow([$addonId]);
				continue;
			}

			if ($subCmd === 'disabled' && $enabled === false) {
				$table->addRow([$addonId]);
				continue;
			}
		}

		$this->out($table->getTable());

		return 0;
	}

	/**
	 * Enables an addon
	 *
	 * @return int Return code of this command
	 * @throws \Exception
	 */
	private function enable(): int
	{
		$addonname = $this->getArgument(1);

		$addon = Strings::sanitizeFilePathItem($addonname);
		if (!is_file("addon/$addon/$addon.php")) {
			throw new RuntimeException($this->l10n->t('Addon not found'));
		}

		if ($this->addonHelper->isAddonEnabled($addon)) {
			throw new RuntimeException($this->l10n->t('Addon already enabled'));
		}

		$this->addonHelper->installAddon($addon);

		return 0;
	}

	/**
	 * Disables an addon
	 *
	 * @return int Return code of this command
	 * @throws \Exception
	 */
	private function disable(): int
	{
		$addonname = $this->getArgument(1);

		$addon = Strings::sanitizeFilePathItem($addonname);
		if (!is_file("addon/$addon/$addon.php")) {
			throw new RuntimeException($this->l10n->t('Addon not found'));
		}

		if (!$this->addonHelper->isAddonEnabled($addon)) {
			throw new RuntimeException($this->l10n->t('Addon already disabled'));
		}

		$this->addonHelper->uninstallAddon($addon);

		return 0;
	}
}
