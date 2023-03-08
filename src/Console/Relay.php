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

use Asika\SimpleConsole\CommandArgsException;
use Friendica\Model\APContact;
use Friendica\Model\Contact;
use Friendica\Protocol\ActivityPub\Transmitter;

/**
 * tool to control the list of ActivityPub relay servers from the CLI
 *
 * With this script you can access the relay servers of your node from
 * the CLI.
 */
class Relay extends \Asika\SimpleConsole\Console
{
	protected $helpOptions = ['h', 'help', '?'];

	/**
	 * @var $dba Friendica\Database\Database
	 */
	private $dba;


	protected function getHelp()
	{
		$help = <<<HELP
console relay - Manage ActivityPub relay configuration
Synopsis
	bin/console relay list [-h|--help|-?] [-v]
	bin/console relay add <actor> [-h|--help|-?] [-v]
	bin/console relay remove <actor> [-f|--force] [-h|--help|-?] [-v]

Description
	bin/console relay list
		Lists all active relay servers

	bin/console relay add <actor>
		Add a relay actor in the format https://relayserver.tld/actor

	bin/console relay remove <actor>
		Remove a relay actor in the format https://relayserver.tld/actor

Options
    -f|--force   Change the relay status in the system even if the unsubscribe message failed
    -h|--help|-? Show help information
    -v           Show more debug information.
HELP;
		return $help;
	}

	public function __construct(\Friendica\Database\Database $dba, array $argv = null)
	{
		parent::__construct($argv);

		$this->dba = $dba;
	}

	protected function doExecute(): int
	{
		if ($this->getOption('v')) {
			$this->out('Executable: ' . $this->executable);
			$this->out('Class: ' . __CLASS__);
			$this->out('Arguments: ' . var_export($this->args, true));
			$this->out('Options: ' . var_export($this->options, true));
		}

		if (count($this->args) > 2) {
			throw new CommandArgsException('Too many arguments');
		}

		if ((count($this->args) == 1) && ($this->getArgument(0) == 'list')) {
			$contacts = $this->dba->select('apcontact', ['url'],
			["`type` IN (?, ?) AND `url` IN (SELECT `url` FROM `contact` WHERE `uid` = ? AND `rel` = ?)",
				'Application', 'Service', 0, Contact::FRIEND]);
			while ($contact = $this->dba->fetch($contacts)) {
				$this->out($contact['url']);
			}
			$this->dba->close($contacts);
		} elseif (count($this->args) == 0) {
			throw new CommandArgsException('too few arguments');
		} elseif (count($this->args) == 1) {
			throw new CommandArgsException($this->getArgument(0) . ' is no valid command');
		}

		if (count($this->args) == 2) {
			$mode = $this->getArgument(0);
			$actor = $this->getArgument(1);

			$apcontact = APContact::getByURL($actor);
			if (empty($apcontact) || !in_array($apcontact['type'], ['Application', 'Service'])) {
				$this->out($actor . ' is no relay actor');
				return 1;
			}

			if ($mode == 'add') {
				if (Transmitter::sendRelayFollow($actor)) {
					$this->out('Successfully added ' . $actor);
				} else {
					$this->out($actor . " couldn't be added");
				}
			} elseif ($mode == 'remove') {
				$force = $this->getOption(['f', 'force'], false);

				if (Transmitter::sendRelayUndoFollow($actor, $force)) {
					$this->out('Successfully removed ' . $actor);
				} elseif (!$force) {
					$this->out($actor . " couldn't be removed");
				} else {
					$this->out($actor . " is forcefully removed");
				}
			} else {
				throw new CommandArgsException($mode . ' is no valid command');
			}
		}

		return 0;
	}
}
