<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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

use Friendica\App;
use Friendica\App\Router;
use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\Logger;
use Friendica\Core\System;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Post;
use Friendica\Model\User;
use Friendica\Module\Api\ApiResponse;
use Friendica\Network\HTTPException;
use Friendica\Security\BasicAuth;
use Friendica\Security\OAuth;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Profiler;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class BaseApi extends BaseModule
{
	const LOG_PREFIX = 'API {action} - ';

	const SCOPE_READ   = 'read';
	const SCOPE_WRITE  = 'write';
	const SCOPE_FOLLOW = 'follow';
	const SCOPE_PUSH   = 'push';

	/**
	 * @var array
	 */
	protected static $boundaries = [];

	/**
	 * @var array
	 */
	protected static $request = [];

	/** @var App */
	protected $app;

	/** @var ApiResponse */
	protected $response;

	public function __construct(App $app, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, ApiResponse $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->app = $app;
	}

	/**
	 * Additionally checks, if the caller is permitted to do this action
	 *
	 * {@inheritDoc}
	 *
	 * @throws HTTPException\ForbiddenException
	 */
	public function run(array $request = [], bool $scopecheck = true): ResponseInterface
	{
		if ($scopecheck) {
			switch ($this->args->getMethod()) {
				case Router::DELETE:
				case Router::PATCH:
				case Router::POST:
				case Router::PUT:
					self::checkAllowedScope(self::SCOPE_WRITE);
	
					if (!self::getCurrentUserID()) {
						throw new HTTPException\ForbiddenException($this->t('Permission denied.'));
					}
					break;
			}	
		}

		return parent::run($request);
	}

	/**
	 * Processes data from GET requests and sets defaults
	 *
	 * @param array      $defaults Associative array of expected request keys and their default typed value. A null
	 *                             value will remove the request key from the resulting value array.
	 * @param array $request       Custom REQUEST array, superglobal instead
	 * @return array request data
	 * @throws \Exception
	 */
	public function getRequest(array $defaults, array $request): array
	{
		self::$request    = $request;
		self::$boundaries = [];

		unset(self::$request['pagename']);

		return $this->checkDefaults($defaults, $request);
	}

	/**
	 * Set boundaries for the "link" header
	 * @param array $boundaries
	 * @param int $id
	 */
	protected static function setBoundaries(int $id)
	{
		if (!isset(self::$boundaries['min'])) {
			self::$boundaries['min'] = $id;
		}

		if (!isset(self::$boundaries['max'])) {
			self::$boundaries['max'] = $id;
		}

		self::$boundaries['min'] = min(self::$boundaries['min'], $id);
		self::$boundaries['max'] = max(self::$boundaries['max'], $id);
	}

	/**
	 * Set the "link" header with "next" and "prev" links
	 * @return void
	 */
	protected static function setLinkHeader()
	{
		if (empty(self::$boundaries)) {
			return;
		}

		$request = self::$request;

		unset($request['min_id']);
		unset($request['max_id']);
		unset($request['since_id']);

		$prev_request = $next_request = $request;

		$prev_request['min_id'] = self::$boundaries['max'];
		$next_request['max_id'] = self::$boundaries['min'];

		$command = DI::baseUrl() . '/' . DI::args()->getCommand();

		$prev = $command . '?' . http_build_query($prev_request);
		$next = $command . '?' . http_build_query($next_request);

		header('Link: <' . $next . '>; rel="next", <' . $prev . '>; rel="prev"');
	}

	/**
	 * Get current application token
	 *
	 * @return array token
	 */
	public static function getCurrentApplication()
	{
		$token = OAuth::getCurrentApplicationToken();

		if (empty($token)) {
			$token = BasicAuth::getCurrentApplicationToken();
		}

		return $token;
	}

	/**
	 * Get current user id, returns 0 if not logged in
	 *
	 * @return int User ID
	 */
	public static function getCurrentUserID()
	{
		$uid = OAuth::getCurrentUserID();

		if (empty($uid)) {
			$uid = BasicAuth::getCurrentUserID(false);
		}

		return (int)$uid;
	}

	/**
	 * Check if the provided scope does exist.
	 * halts execution on missing scope or when not logged in.
	 *
	 * @param string $scope the requested scope (read, write, follow, push)
	 */
	public static function checkAllowedScope(string $scope)
	{
		$token = self::getCurrentApplication();

		if (empty($token)) {
			Logger::notice('Empty application token');
			DI::mstdnError()->Forbidden();
		}

		if (!isset($token[$scope])) {
			Logger::warning('The requested scope does not exist', ['scope' => $scope, 'application' => $token]);
			DI::mstdnError()->Forbidden();
		}

		if (empty($token[$scope])) {
			Logger::warning('The requested scope is not allowed', ['scope' => $scope, 'application' => $token]);
			DI::mstdnError()->Forbidden();
		}
	}

	public static function checkThrottleLimit()
	{
		$uid = self::getCurrentUserID();

		// Check for throttling (maximum posts per day, week and month)
		$throttle_day = DI::config()->get('system', 'throttle_limit_day');
		if ($throttle_day > 0) {
			$datefrom = date(DateTimeFormat::MYSQL, time() - 24*60*60);

			$condition = ["`gravity` = ? AND `uid` = ? AND `wall` AND `received` > ?", GRAVITY_PARENT, $uid, $datefrom];
			$posts_day = Post::countThread($condition);

			if ($posts_day > $throttle_day) {
				Logger::info('Daily posting limit reached', ['uid' => $uid, 'posts' => $posts_day, 'limit' => $throttle_day]);
				$error = DI::l10n()->t('Too Many Requests');
				$error_description = DI::l10n()->tt("Daily posting limit of %d post reached. The post was rejected.", "Daily posting limit of %d posts reached. The post was rejected.", $throttle_day);
				$errorobj = new \Friendica\Object\Api\Mastodon\Error($error, $error_description);
				System::jsonError(429, $errorobj->toArray());
			}
		}

		$throttle_week = DI::config()->get('system', 'throttle_limit_week');
		if ($throttle_week > 0) {
			$datefrom = date(DateTimeFormat::MYSQL, time() - 24*60*60*7);

			$condition = ["`gravity` = ? AND `uid` = ? AND `wall` AND `received` > ?", GRAVITY_PARENT, $uid, $datefrom];
			$posts_week = Post::countThread($condition);

			if ($posts_week > $throttle_week) {
				Logger::info('Weekly posting limit reached', ['uid' => $uid, 'posts' => $posts_week, 'limit' => $throttle_week]);
				$error = DI::l10n()->t('Too Many Requests');
				$error_description = DI::l10n()->tt("Weekly posting limit of %d post reached. The post was rejected.", "Weekly posting limit of %d posts reached. The post was rejected.", $throttle_week);
				$errorobj = new \Friendica\Object\Api\Mastodon\Error($error, $error_description);
				System::jsonError(429, $errorobj->toArray());
			}
		}

		$throttle_month = DI::config()->get('system', 'throttle_limit_month');
		if ($throttle_month > 0) {
			$datefrom = date(DateTimeFormat::MYSQL, time() - 24*60*60*30);

			$condition = ["`gravity` = ? AND `uid` = ? AND `wall` AND `received` > ?", GRAVITY_PARENT, $uid, $datefrom];
			$posts_month = Post::countThread($condition);

			if ($posts_month > $throttle_month) {
				Logger::info('Monthly posting limit reached', ['uid' => $uid, 'posts' => $posts_month, 'limit' => $throttle_month]);
				$error = DI::l10n()->t('Too Many Requests');
				$error_description = DI::l10n()->t("Monthly posting limit of %d post reached. The post was rejected.", "Monthly posting limit of %d posts reached. The post was rejected.", $throttle_month);
				$errorobj = new \Friendica\Object\Api\Mastodon\Error($error, $error_description);
				System::jsonError(429, $errorobj->toArray());
			}
		}
	}

	public static function getContactIDForSearchterm(string $screen_name = null, string $profileurl = null, int $cid = null, int $uid)
	{
		if (!empty($cid)) {
			return $cid;
		}

		if (!empty($profileurl)) {
			return Contact::getIdForURL($profileurl);
		}

		if (empty($cid) && !empty($screen_name)) {
			if (strpos($screen_name, '@') !== false) {
				return Contact::getIdForURL($screen_name, 0, false);
			}

			$user = User::getByNickname($screen_name, ['uid']);
			if (!empty($user['uid'])) {
				return Contact::getPublicIdByUserId($user['uid']);
			}
		}

		if ($uid != 0) {
			return Contact::getPublicIdByUserId($uid);
		}

		return null;
	}
}
