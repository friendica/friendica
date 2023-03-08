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

use Friendica\App;
use Friendica\Core\KeyValueStorage\Capabilities\IManageKeyValuePairs;
use Friendica\Core\L10n;
use Friendica\Core\Update;

/**
 * Performs database post updates
 */
class PostUpdate extends \Asika\SimpleConsole\Console
{
	protected $helpOptions = ['h', 'help', '?'];

	/**
	 * @var App\Mode
	 */
	private $appMode;
	/**
	 * @var IManageKeyValuePairs
	 */
	private $keyValue;
	/**
	 * @var L10n
	 */
	private $l10n;

	protected function getHelp()
	{
		$help = <<<HELP
console postupdate - Performs database post updates
Usage
        bin/console postupdate [-h|--help|-?] [--reset <version>]

Options
    -h|--help|-?      Show help information
    --reset <version> Reset the post update version
HELP;
		return $help;
	}

	public function __construct(App\Mode $appMode, IManageKeyValuePairs $keyValue, L10n $l10n, array $argv = null)
	{
		parent::__construct($argv);

		$this->appMode  = $appMode;
		$this->keyValue = $keyValue;
		$this->l10n     = $l10n;
	}

	protected function doExecute(): int
	{
		$a = \Friendica\DI::app();

		if ($this->getOption($this->helpOptions)) {
			$this->out($this->getHelp());
			return 0;
		}

		$reset_version = $this->getOption('reset');
		if (is_bool($reset_version)) {
			$this->out($this->getHelp());
			return 0;
		} elseif ($reset_version) {
			$this->keyValue->set('post_update_version', $reset_version);
			echo $this->l10n->t('Post update version number has been set to %s.', $reset_version) . "\n";
			return 0;
		}

		if ($this->appMode->isInstall()) {
			throw new \RuntimeException('Database isn\'t ready or populated yet');
		}

		echo $this->l10n->t('Check for pending update actions.') . "\n";
		Update::run($a->getBasePath(), true, false, true, false);
		echo $this->l10n->t('Done.') . "\n";

		echo $this->l10n->t('Execute pending post updates.') . "\n";

		while (!\Friendica\Database\PostUpdate::update()) {
			echo '.';
		}

		echo "\n" . $this->l10n->t('All pending post updates are done.') . "\n";

		return 0;
	}
}
