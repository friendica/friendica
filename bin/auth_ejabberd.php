#!/usr/bin/env php
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
 * ejabberd extauth script for the integration with friendica
 *
 * Originally written for joomla by Dalibor Karlovic <dado@krizevci.info>
 * modified for Friendica by Michael Vogel <icarus@dabo.de>
 * published under GPL
 *
 * Latest version of the original script for joomla is available at:
 * http://87.230.15.86/~dado/ejabberd/joomla-login
 *
 * Installation:
 *
 * 	- Change it's owner to whichever user is running the server, ie. ejabberd
 * 	  $ chown ejabberd:ejabberd /path/to/friendica/bin/auth_ejabberd.php
 *
 * 	- Change the access mode so it is readable only to the user ejabberd and has exec
 * 	  $ chmod 700 /path/to/friendica/bin/auth_ejabberd.php
 *
 * 	- Edit your ejabberd.yml file and add after "shaper:":
 *
 * 	  auth_method: [external]
 * 	  extauth_program: "/path/to/friendica/bin/auth_ejabberd.php"
 *    auth_use_cache: false
 *
 * 	- Restart your ejabberd service, you should be able to login with your friendica auth info
 *
 * Other hints:
 * 	- if your users have a space or a @ in their nickname, they'll run into trouble
 * 	  registering with any client so they should be instructed to replace these chars
 * 	  " " (space) is replaced with "%20"
 * 	  "@" is replaced with "(a)"
 *
 */

if (php_sapi_name() !== 'cli') {
	header($_SERVER["SERVER_PROTOCOL"] . ' 403 Forbidden');
	exit();
}

use Dice\Dice;
use Friendica\App\Mode;
use Friendica\Security\ExAuth;
use Psr\Log\LoggerInterface;

if (sizeof($_SERVER["argv"]) == 0) {
	die();
}

$directory = dirname($_SERVER["argv"][0]);

if (substr($directory, 0, 1) != DIRECTORY_SEPARATOR) {
	$directory = $_SERVER["PWD"] . DIRECTORY_SEPARATOR . $directory;
}

$directory = realpath($directory . DIRECTORY_SEPARATOR . "..");

chdir($directory);

require dirname(__DIR__) . '/vendor/autoload.php';

$dice = (new Dice())->addRules(include __DIR__ . '/../static/dependencies.config.php');
$dice = $dice->addRule(LoggerInterface::class,['constructParams' => ['auth_ejabberd']]);

\Friendica\DI::init($dice);
\Friendica\Core\Logger\Handler\ErrorHandler::register($dice->create(\Psr\Log\LoggerInterface::class));

// Check the database structure and possibly fixes it
\Friendica\Core\Update::check(\Friendica\DI::basePath(), true);

$appMode = $dice->create(Mode::class);

if ($appMode->isNormal()) {
	/** @var ExAuth $oAuth */
	$oAuth = $dice->create(ExAuth::class);
	$oAuth->readStdin();
}
