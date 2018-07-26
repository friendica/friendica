<?php

namespace Friendica\Core\Console;

use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Database\DBA;
use Friendica\Model\User;
use RuntimeException;

/**
 * @brief tool to set a new password for a user
 *
 * With this tool, you can set a new password for a user
 *
 * License: AGPLv3 or later, same as Friendica
 *
 * @author Michael Vogel <heluecht@pirati.ca>
 */
class NewPassword extends \Asika\SimpleConsole\Console
{
	protected $helpOptions = ['h', 'help', '?'];

	protected function getHelp()
	{
		$help = <<<HELP
console newpassword - Creates a new password for a given user
Usage
	bin/console newpassword <nickname> [<password>] [-h|--help|-?] [-v]

Description
	Creates a new password for a user without using the "forgot password" functionality.

Options
    -h|--help|-? Show help information
    -v           Show more debug information.
HELP;
		return $help;
	}

	protected function doExecute()
	{
		$a = get_app();

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

		if ($a->isInstallMode()) {
			throw new RuntimeException('Database isn\'t ready or populated yet');
		}

		$nick = $this->getArgument(0);

		$user = DBA::selectFirst('user', ['uid'], ['nickname' => $nick]);
		if (!DBA::isResult($user)) {
			throw new RuntimeException(L10n::t('User not found'));
		}

		$password = $this->getArgument(1);
		if (is_null($password)) {
			$this->out(L10n::t('Enter new password: '), false);
			$password = \Seld\CliPrompt\CliPrompt::hiddenPrompt(true);
		}

		if (!$password) {
			throw new RuntimeException(L10n::t('Password can\'t be empty'));
		}

		if (!Config::get('system', 'disable_password_exposed', false) && User::isPasswordExposed($password)) {
			throw new RuntimeException(L10n::t('The new password has been exposed in a public data dump, please choose another.'));
		}

		if (!User::updatePassword($user['uid'], $password)) {
			throw new RuntimeException(L10n::t('Password update failed. Please try again.'));
		}

		$this->out(L10n::t('Password changed.'));

		return 0;
	}
}
