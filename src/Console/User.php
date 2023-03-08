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
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Model\Register;
use Friendica\Model\User as UserModel;
use Friendica\Util\Temporal;
use RuntimeException;
use Seld\CliPrompt\CliPrompt;

/**
 * tool to manage users of the current node
 */
class User extends \Asika\SimpleConsole\Console
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
	 * @var IManagePersonalConfigValues
	 */
	private $pConfig;

	protected function getHelp()
	{
		$help = <<<HELP
console user - Modify user settings per console commands.
Usage
	bin/console user password <nickname> [<password>] [-h|--help|-?] [-v]
	bin/console user add [<name> [<nickname> [<email> [<language>]]]] [-h|--help|-?] [-v]
	bin/console user delete [<nickname>] [-y] [-h|--help|-?] [-v]
	bin/console user allow [<nickname>] [-h|--help|-?] [-v]
	bin/console user deny [<nickname>] [-h|--help|-?] [-v]
	bin/console user block [<nickname>] [-h|--help|-?] [-v]
	bin/console user unblock [<nickname>] [-h|--help|-?] [-v]
	bin/console user list pending [-s|--start=0] [-c|--count=50] [-h|--help|-?] [-v]
	bin/console user list removed [-s|--start=0] [-c|--count=50] [-h|--help|-?] [-v]
	bin/console user list active [-s|--start=0] [-c|--count=50] [-h|--help|-?] [-v]
	bin/console user list all [-s|--start=0] [-c|--count=50] [-h|--help|-?] [-v]
	bin/console user search id <UID> [-h|--help|-?] [-v]
	bin/console user search nick <nick> [-h|--help|-?] [-v]
	bin/console user search mail <mail> [-h|--help|-?] [-v]
	bin/console user search guid <GUID> [-h|--help|-?] [-v]
	bin/console user config list [<nickname>] [<category>] [-h|--help|-?] [-v]
	bin/console user config get [<nickname>] [<category>] [<key>] [-h|--help|-?] [-v]
	bin/console user config set [<nickname>] [<category>] [<key>] [<value>] [-h|--help|-?] [-v]
	bin/console user config delete [<nickname>] [<category>] [<key>] [-h|--help|-?] [-v]

Description
	Modify user settings per console commands.

Options
    -h|--help|-? Show help information
    -v           Show more debug information
    -y           Non-interactive mode, assume "yes" as answer to the user deletion prompt
HELP;
		return $help;
	}

	public function __construct(App\Mode $appMode, L10n $l10n, IManagePersonalConfigValues $pConfig, array $argv = null)
	{
		parent::__construct($argv);

		$this->appMode = $appMode;
		$this->l10n    = $l10n;
		$this->pConfig = $pConfig;
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
			case 'password':
				return $this->password();
			case 'add':
				return $this->addUser();
			case 'allow':
				return $this->pendingUser(true);
			case 'deny':
				return $this->pendingUser(false);
			case 'block':
				return $this->blockUser(true);
			case 'unblock':
				return $this->blockUser(false);
			case 'delete':
				return $this->deleteUser();
			case 'list':
				return $this->listUser();
			case 'search':
				return $this->searchUser();
			case 'config':
				return $this->configUser();
			default:
				throw new \Asika\SimpleConsole\CommandArgsException('Wrong command.');
		}
	}

	/**
	 * Retrieves the user nick, either as an argument or from a prompt
	 *
	 * @param int $arg_index Index of the nick argument in the arguments list
	 *
	 * @return string nick of the user
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private function getNick($arg_index)
	{
		$nick = $this->getArgument($arg_index);

		if (!$nick) {
			$this->out($this->l10n->t('Enter user nickname: '));
			$nick = CliPrompt::prompt();
			if (empty($nick)) {
				throw new RuntimeException('A nick name must be set.');
			}
		}

		return $nick;
	}

	/**
	 * Retrieves the user from a nick supplied as an argument or from a prompt
	 *
	 * @param int $arg_index Index of the nick argument in the arguments list
	 *
	 * @return array|boolean User record with uid field, or false if user is not found
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private function getUserByNick($arg_index)
	{
		$nick = $this->getNick($arg_index);

		$user = UserModel::getByNickname($nick, ['uid']);
		if (empty($user)) {
			throw new RuntimeException($this->l10n->t('User not found'));
		}

		return $user;
	}

	/**
	 * Sets a new password
	 *
	 * @return int Return code of this command
	 *
	 * @throws \Exception
	 */
	private function password()
	{
		$user = $this->getUserByNick(1);

		$password = $this->getArgument(2);

		if (is_null($password)) {
			$this->out($this->l10n->t('Enter new password: '), false);
			$password = CliPrompt::hiddenPrompt(true);
		}

		try {
			$result = UserModel::updatePassword($user['uid'], $password);

			if (empty($result)) {
				throw new \Exception($this->l10n->t('Password update failed. Please try again.'));
			}

			$this->out($this->l10n->t('Password changed.'));
		} catch (\Exception $e) {
			throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
		}

		return 0;
	}

	/**
	 * Adds a new user based on given console arguments
	 *
	 * @return bool True, if the command was successful
	 * @throws \ErrorException
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private function addUser()
	{
		$name  = $this->getArgument(1);
		$nick  = $this->getArgument(2);
		$email = $this->getArgument(3);
		$lang  = $this->getArgument(4);

		if (empty($name)) {
			$this->out($this->l10n->t('Enter user name: '));
			$name = CliPrompt::prompt();
			if (empty($name)) {
				throw new RuntimeException('A name must be set.');
			}
		}

		if (empty($nick)) {
			$this->out($this->l10n->t('Enter user nickname: '));
			$nick = CliPrompt::prompt();
			if (empty($nick)) {
				throw new RuntimeException('A nick name must be set.');
			}
		}

		if (empty($email)) {
			$this->out($this->l10n->t('Enter user email address: '));
			$email = CliPrompt::prompt();
			if (empty($email)) {
				throw new RuntimeException('A email address must be set.');
			}
		}

		if (empty($lang)) {
			$this->out($this->l10n->t('Enter a language (optional): '));
			$lang = CliPrompt::prompt();
		}

		if (empty($lang)) {
			return UserModel::createMinimal($name, $email, $nick);
		} else {
			return UserModel::createMinimal($name, $email, $nick, $lang);
		}
	}

	/**
	 * Allows or denys a user based on it's nickname
	 *
	 * @param bool $allow True, if the pending user is allowed, false if denies
	 *
	 * @return bool True, if allow was successful
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private function pendingUser(bool $allow = true)
	{
		$user = $this->getUserByNick(1);

		$pending = Register::getPendingForUser($user['uid'] ?? 0);
		if (empty($pending)) {
			throw new RuntimeException($this->l10n->t('User is not pending.'));
		}

		return ($allow) ? UserModel::allow($pending['hash']) : UserModel::deny($pending['hash']);
	}

	/**
	 * Blocks/unblocks a user
	 *
	 * @param bool $block True, if the given user should get blocked
	 *
	 * @return bool True, if the command was successful
	 * @throws \Exception
	 */
	private function blockUser(bool $block = true)
	{
		$user = $this->getUserByNick(1);

		return $block ? UserModel::block($user['uid'] ?? 0) : UserModel::block($user['uid'] ?? 0, false);
	}

	/**
	 * Deletes a user
	 *
	 * @return bool True, if the delete was successful
	 * @throws \Exception
	 */
	private function deleteUser(): bool
	{
		$user = $this->getUserByNick(1);

		if (!empty($user['account_removed'])) {
			$this->out($this->l10n->t('User has already been marked for deletion.'));
			return true;
		}

		if (!$this->getOption('y')) {
			$this->out($this->l10n->t('Type "yes" to delete %s', $this->getArgument(1)));
			if (CliPrompt::prompt() !== 'yes') {
				throw new RuntimeException($this->l10n->t('Deletion aborted.'));
			}
		}

		return UserModel::remove($user['uid']);
	}

	/**
	 * List users of the current node
	 *
	 * @return bool True, if the command was successful
	 */
	private function listUser()
	{
		$subCmd = $this->getArgument(1);
		$start  = $this->getOption(['s', 'start'], 0);
		$count  = $this->getOption(['c', 'count'], Pager::ITEMS_PER_PAGE);

		$table = new Console_Table();

		switch ($subCmd) {
			case 'pending':
				$table->setHeaders(['Nick', 'Name', 'URL', 'E-Mail', 'Register Date', 'Comment']);
				$pending = Register::getPending($start, $count);
				foreach ($pending as $contact) {
					$table->addRow([
						$contact['nick'],
						$contact['name'],
						$contact['url'],
						$contact['email'],
						Temporal::getRelativeDate($contact['created']),
						$contact['note'],
					]);
				}
				$this->out($table->getTable());
				return true;
			case 'all':
			case 'active':
			case 'removed':
				$table->setHeaders(['Nick', 'Name', 'URL', 'E-Mail', 'Register', 'Login', 'Last Item']);
				$contacts = UserModel::getList($start, $count, $subCmd);
				foreach ($contacts as $contact) {
					$table->addRow([
						$contact['nick'],
						$contact['name'],
						$contact['url'],
						$contact['email'],
						Temporal::getRelativeDate($contact['created']),
						Temporal::getRelativeDate($contact['last-activity']),
						Temporal::getRelativeDate($contact['last-item']),
					]);
				}
				$this->out($table->getTable());
				return true;
			default:
				$this->out($this->getHelp());
				return false;
		}
	}

	/**
	 * Returns a user based on search parameter
	 *
	 * @return bool True, if the command was successful
	 */
	private function searchUser()
	{
		$fields = [
			'uid',
			'guid',
			'username',
			'nickname',
			'email',
			'register_date',
			'last-activity',
			'verified',
			'blocked',
		];

		$subCmd = $this->getArgument(1);
		$param  = $this->getArgument(2);

		$table = new Console_Table();
		$table->setHeaders(['UID', 'GUID', 'Name', 'Nick', 'E-Mail', 'Register', 'Login', 'Verified', 'Blocked']);

		switch ($subCmd) {
			case 'id':
				$user = UserModel::getById($param, $fields);
				break;
			case 'guid':
				$user = UserModel::getByGuid($param, $fields);
				break;
			case 'mail':
				$user = UserModel::getByEmail($param, $fields);
				break;
			case 'nick':
				$user = UserModel::getByNickname($param, $fields);
				break;
			default:
				$this->out($this->getHelp());
				return false;
		}

		if (!empty($user)) {
			$table->addRow($user);
		}
		$this->out($table->getTable());

		return true;
	}

	/**
	 * Queries and modifies user-specific configuration
	 *
	 * @return bool True, if the command was successful
	 */
	private function configUser()
	{
		$subCmd = $this->getArgument(1);

		$user = $this->getUserByNick(2);

		$category = $this->getArgument(3);

		if (is_null($category)) {
			$this->out($this->l10n->t('Enter category: '), false);
			$category = CliPrompt::prompt();
			if (empty($category)) {
				throw new RuntimeException('A category must be selected.');
			}
		}

		$key = $this->getArgument(4);

		if ($subCmd != 'list' and is_null($key)) {
			$this->out($this->l10n->t('Enter key: '), false);
			$key = CliPrompt::prompt();
			if (empty($key)) {
				throw new RuntimeException('A key must be selected.');
			}
		}

		$values = $this->pConfig->load($user['uid'], $category);

		switch ($subCmd) {
			case 'list':
				$table = new Console_Table();
				$table->setHeaders(['Key', 'Value']);
				if (array_key_exists($category, $values)) {
					foreach (array_keys($values[$category]) as $key) {
						$table->addRow([$key, $values[$category][$key]]);
					}
				}
				$this->out($table->getTable());
				break;
			case 'get':
				if (!array_key_exists($category, $values)) {
					throw new RuntimeException('Category does not exist');
				}
				if (!array_key_exists($key, $values[$category])) {
					throw new RuntimeException('Key does not exist');
				}

				$this->out($this->pConfig->get($user['uid'], $category, $key));
				break;
			case 'set':
				$value = $this->getArgument(5);

				if (is_null($value)) {
					$this->out($this->l10n->t('Enter value: '), false);
					$value = CliPrompt::prompt();
					if (empty($value)) {
						throw new RuntimeException('A value must be specified.');
					}
				}

				if (array_key_exists($category, $values) and
					array_key_exists($key, $values[$category]) and
					$values[$category][$key] == $value) {
					throw new RuntimeException('Value not changed');
				}

				$this->pConfig->set($user['uid'], $category, $key, $value);
				break;
			case 'delete':
				if (!array_key_exists($category, $values)) {
					throw new RuntimeException('Category does not exist');
				}
				if (!array_key_exists($key, $values[$category])) {
					throw new RuntimeException('Key does not exist');
				}

				$this->pConfig->delete($user['uid'], $category, $key);
				break;
			default:
				$this->out($this->getHelp());
				return false;
		}
	}
}
