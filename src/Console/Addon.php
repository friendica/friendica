<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
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

namespace Friendica\Console;

use Console_Table;
use Friendica\App;
use Friendica\Content\Pager;
use Friendica\Core\L10n;
use Friendica\Core\Addon as AddonCore;
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
	 * @var App\Mode
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

	public function __construct(App\Mode $appMode, L10n $l10n, Database $dba, array $argv = null)
	{
		parent::__construct($argv);

		$this->appMode     = $appMode;
		$this->l10n        = $l10n;
		$this->dba         = $dba;

		AddonCore::loadAddons();
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
		foreach (AddonCore::getAvailableList() as $addon) {
			$addon_name = $addon[0];
			$enabled = AddonCore::isEnabled($addon_name) ? "enabled" : "disabled";
			switch ($subCmd) {
				case 'all':
					$table->addRow([$addon_name, $enabled]);
					break;
				case 'enabled':
					if (!$enabled) {
						continue 2;
					}
					$table->addRow([$addon_name]);
				case 'disabled':
					if ($enabled) {
						continue 2;
					}
					$table->addRow([$addon_name]);
					break;
			}

		}
		$this->out($table->getTable());
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

		if (AddonCore::isEnabled($addon)) {
			throw new RuntimeException($this->l10n->t('Addon already enabled'));
		}

		AddonCore::install($addon);

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

		if (!AddonCore::isEnabled($addon)) {
			throw new RuntimeException($this->l10n->t('Addon already disabled'));
		}

		AddonCore::uninstall($addon);

		return 0;
	}
}
