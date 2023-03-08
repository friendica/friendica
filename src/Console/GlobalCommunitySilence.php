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
use Friendica\Database\Database;
use Friendica\Model\Contact;
use RuntimeException;

/**
 * tool to silence accounts on the global community page
 *
 * With this tool, you can silence an account on the global community page.
 * Postings from silenced accounts will not be displayed on the community
 * page. This silencing does only affect the display on the community page,
 * accounts following the silenced accounts will still get their postings.
 */
class GlobalCommunitySilence extends \Asika\SimpleConsole\Console
{
	protected $helpOptions = ['h', 'help', '?'];

	/**
	 * @var App\Mode
	 */
	private $appMode;
	/**
	 * @var Database
	 */
	private $dba;

	protected function getHelp()
	{
		$help = <<<HELP
console globalcommunitysilence - Silence a profile from the global community page
Usage
	bin/console globalcommunitysilence <profile_url> [-h|--help|-?] [-v]

Description
	With this tool, you can silence an account on the global community page.
	Postings from silenced accounts will not be displayed on the community page.
	This silencing does only affect the display on the community page, accounts
	following the silenced accounts will still get their postings.

Options
    -h|--help|-? Show help information
    -v           Show more debug information.
HELP;
		return $help;
	}

	public function __construct(App\Mode $appMode, Database $dba, array $argv = null)
	{
		parent::__construct($argv);

		$this->appMode = $appMode;
		$this->dba  =$dba;
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

		if (count($this->args) > 1) {
			throw new \Asika\SimpleConsole\CommandArgsException('Too many arguments');
		}

		if ($this->appMode->isInstall()) {
			throw new RuntimeException('Database isn\'t ready or populated yet');
		}

		$contact_id = Contact::getIdForURL($this->getArgument(0));
		if ($contact_id) {
			Contact::update(['hidden' => true], ['id' => $contact_id]);
			$this->out('The account has been successfully silenced from the global community page.');
		} else {
			throw new RuntimeException('Could not find any public contact entry for this URL (' . $this->getArgument(0) . ')');
		}

		return 0;
	}
}
