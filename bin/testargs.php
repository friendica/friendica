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
 * During installation we need to check if register_argc_argv is
 * enabled for the command line PHP processor, because otherwise
 * deliveries will fail. So we will do a shell exec of php and
 * execute this file with a command line argument, and see if it
 * echoes the argument back to us. Otherwise notify the person
 * that their installation doesn't meet the system requirements.
 *
 */

if (php_sapi_name() !== 'cli') {
	header($_SERVER["SERVER_PROTOCOL"] . ' 403 Forbidden');
	exit();
}

if (($_SERVER["argc"] > 1) && isset($_SERVER["argv"][1])) {
	echo $_SERVER["argv"][1];
} else {
	echo '';
}
