<?php
/**
 * @copyright Copyright (C) 2021, Friendica
 *
 * @license   GNU AGPL version 3 or any later version
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
namespace Friendica\Module\Api\OAuth\Local;

use Friendica\Core\Session;
use Friendica\Core\Logger;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Module\OAuthApi;
use Friendica\Network\HTTPException;


// 
/**
 * For local oauth, we should still use php session id too, so we need
 * to tie this and the normal login process.
 * This will maximize compatibility as a theme (client) can easily use
 * PASETO tokens while also use the current system
 * 
 * Check: Module/Security/Login
 */

/**
 * @see 
 */
class OAuthLocal extends OAuthApi
{
	/**
	 * @param array $parameters
	 * @throws HTTPException\InternalServerErrorException
	 * @throws 
	 * @see 
	 */
	public static function rawContent(array $parameters = [])
	{

		if(empty($_POST['username']) || empty($_POST['password'])) {
			// Return error
		}

		$username = trim($_POST['username']);
		$password = trim($_POST['password']);
		$remember_me = !empty($_POST['remember'])

		Logger::info('OAuth/Local', ['username' => $username]);

		if (!empty($_POST['auth-params']) && $_POST['auth-params'] === 'login') {
			DI::auth()->withPassword(
				DI::app(),
				trim($_POST['username']),
				trim($_POST['password']),
				!empty($_POST['remember'])
			);
		}

		DBA::close($contacts);

	}
}
