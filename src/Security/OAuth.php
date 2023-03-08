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

namespace Friendica\Security;

use Friendica\Core\Logger;
use Friendica\Core\Worker;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Model\User;
use Friendica\Module\BaseApi;
use Friendica\Util\DateTimeFormat;

/**
 * OAuth Server
 */
class OAuth
{
	/**
	 * @var bool|int
	 */
	protected static $current_user_id = 0;
	/**
	 * @var array
	 */
	protected static $current_token = [];

	/**
	 * Get current user id, returns 0 if not logged in
	 *
	 * @return int User ID
	 */
	public static function getCurrentUserID()
	{
		if (empty(self::$current_user_id)) {
			$token = self::getCurrentApplicationToken();
			if (!empty($token['uid'])) {
				self::$current_user_id = $token['uid'];
			} else {
				self::$current_user_id = 0;
			}
		}

		return (int)self::$current_user_id;
	}

	/**
	 * Get current application token
	 *
	 * @return array token
	 */
	public static function getCurrentApplicationToken()
	{
		if (empty(self::$current_token)) {
			self::$current_token = self::getTokenByBearer();
		}

		return self::$current_token;
	}

	/**
	 * Get the user token via the Bearer token
	 *
	 * @return array User Token
	 */
	private static function getTokenByBearer()
	{
		$authorization = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

		if (empty($authorization)) {
			// workaround for HTTP-auth in CGI mode
			$authorization = $_SERVER['REDIRECT_REMOTE_USER'] ?? '';
		}

		if (substr($authorization, 0, 7) != 'Bearer ') {
			return [];
		}

		$condition = ['access_token' => trim(substr($authorization, 7))];

		$token = DBA::selectFirst('application-view', ['uid', 'id', 'name', 'website', 'created_at', 'read', 'write', 'follow', 'push'], $condition);
		if (!DBA::isResult($token)) {
			Logger::notice('Token not found', $condition);
			return [];
		}
		Logger::debug('Token found', $token);

		User::updateLastActivity($token['uid']);

		// Regularly update suggestions
		if (Contact\Relation::areSuggestionsOutdated($token['uid'])) {
			Worker::add(Worker::PRIORITY_MEDIUM, 'UpdateSuggestions', $token['uid']);
		}

		return $token;
	}

	/**
	 * Get the application record via the provided request header fields
	 *
	 * @param string $client_id
	 * @param string $client_secret
	 * @param string $redirect_uri
	 * @return array application record
	 */
	public static function getApplication(string $client_id, string $client_secret, string $redirect_uri)
	{
		$condition = ['client_id' => $client_id];
		if (!empty($client_secret)) {
			$condition['client_secret'] = $client_secret;
		}
		if (!empty($redirect_uri)) {
			$condition['redirect_uri'] = $redirect_uri;
		}

		$application = DBA::selectFirst('application', [], $condition);
		if (!DBA::isResult($application)) {
			Logger::warning('Application not found', $condition);
			return [];
		}
		return $application;
	}

	/**
	 * Check if an token for the application and user exists
	 *
	 * @param array $application
	 * @param integer $uid
	 * @return boolean
	 */
	public static function existsTokenForUser(array $application, int $uid)
	{
		return DBA::exists('application-token', ['application-id' => $application['id'], 'uid' => $uid]);
	}

	/**
	 * Fetch the token for the given application and user
	 *
	 * @param array $application
	 * @param integer $uid
	 * @return array application record
	 */
	public static function getTokenForUser(array $application, int $uid)
	{
		return DBA::selectFirst('application-token', [], ['application-id' => $application['id'], 'uid' => $uid]);
	}

	/**
	 * Create and fetch an token for the application and user
	 *
	 * @param array   $application
	 * @param integer $uid
	 * @param string  $scope
	 * @return array application record
	 */
	public static function createTokenForUser(array $application, int $uid, string $scope)
	{
		$code         = bin2hex(random_bytes(32));
		$access_token = bin2hex(random_bytes(32));

		$fields = [
			'application-id' => $application['id'],
			'uid'            => $uid,
			'code'           => $code,
			'access_token'   => $access_token,
			'scopes'         => $scope,
			'read'           => (stripos($scope, BaseApi::SCOPE_READ) !== false),
			'write'          => (stripos($scope, BaseApi::SCOPE_WRITE) !== false),
			'follow'         => (stripos($scope, BaseApi::SCOPE_FOLLOW) !== false),
			'push'           => (stripos($scope, BaseApi::SCOPE_PUSH) !== false),
			'created_at'     => DateTimeFormat::utcNow()];

		foreach ([BaseApi::SCOPE_READ, BaseApi::SCOPE_WRITE, BaseApi::SCOPE_WRITE, BaseApi::SCOPE_PUSH] as $scope) {
			if ($fields[$scope] && !$application[$scope]) {
				Logger::warning('Requested token scope is not allowed for the application', ['token' => $fields, 'application' => $application]);
			}
		}

		if (!DBA::insert('application-token', $fields, Database::INSERT_UPDATE)) {
			return [];
		}

		return DBA::selectFirst('application-token', [], ['application-id' => $application['id'], 'uid' => $uid]);
	}
}
