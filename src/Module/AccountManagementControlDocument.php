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

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\System;

/**
 * Static definition for the Firefox Account Manager
 *
 * @see https://wiki.mozilla.org/Labs/Weave/Identity/Account_Manager/Spec/3#Contents_of_the_Account_Management_Control_Document
 */
class AccountManagementControlDocument extends BaseModule
{
	protected function rawContent(array $request = [])
	{
		$output = [
			'version' => 1,
			'sessionstatus' => [
				'method' => 'GET',
				'path' => '/session',
			],
			'auth-methods' => [
				'username-password-form' => [
					'connect' => [
						'method' => 'POST',
						'path' => '/login',
						'params' => [
							'username' => 'login-name',
							'password' => 'password',
						],
						'onsuccess' => [
							'action' => 'reload',
						],
					],
					'disconnect' => [
						'method' => 'GET',
						'path' => '/logout',
					],
				],
			],
			'methods' => [
				'username-password-form' => [
					'connect' => [
						'method' => 'POST',
						'path' => '/login',
						'params' => [
							'username' => 'login-name',
							'password' => 'password',
						],
						'onsuccess' => [
							'action' => 'reload',
						],
					],
					'disconnect' => [
						'method' => 'GET',
						'path' => '/logout',
					],
				],
			],
		];

		System::jsonExit($output);
	}
}
