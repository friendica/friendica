<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module;

use DateTime;
use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\App\Router;
use Friendica\AppHelper;
use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\User;
use Friendica\Module\Api\ApiResponse;
use Friendica\Module\Special\HTTPException as ModuleHTTPException;
use Friendica\Network\HTTPException;
use Friendica\Object\Api\Mastodon\Error;
use Friendica\Object\Api\Mastodon\Status;
use Friendica\Object\Api\Mastodon\TimelineOrderByTypes;
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

	/** @var AppHelper */
	protected $appHelper;

	/** @var ApiResponse */
	protected $response;

	/** @var \Friendica\Factory\Api\Mastodon\Error */
	protected $errorFactory;

	public function __construct(\Friendica\Factory\Api\Mastodon\Error $errorFactory, AppHelper $appHelper, L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, ApiResponse $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->appHelper    = $appHelper;
		$this->errorFactory = $errorFactory;
	}

	/**
	 * Additionally checks, if the caller is permitted to do this action
	 *
	 * {@inheritDoc}
	 *
	 * @throws HTTPException\ForbiddenException
	 */
	public function run(ModuleHTTPException $httpException, array $request = [], bool $scopecheck = true): ResponseInterface
	{
		if ($scopecheck) {
			switch ($this->args->getMethod()) {
				case Router::DELETE:
				case Router::PATCH:
				case Router::POST:
				case Router::PUT:
					$this->checkAllowedScope(self::SCOPE_WRITE);

					if (!self::getCurrentUserID()) {
						throw new HTTPException\ForbiddenException($this->t('Permission denied.'));
					}
					break;
			}
		}

		return parent::run($httpException, $request);
	}

	/**
	 * Processes data from GET requests and sets paging conditions
	 *
	 * @param array $request       Custom REQUEST array
	 * @param array $condition     Existing conditions to merge
	 * @return array paging data condition parameters data
	 * @throws \Exception
	 */
	protected function addPagingConditions(array $request, array $condition): array
	{
		$requested_order = $request['friendica_order'];
		if ($requested_order == TimelineOrderByTypes::ID) {
			if (!empty($request['max_id'])) {
				$condition = DBA::mergeConditions($condition, ["`uri-id` < ?", intval($request['max_id'])]);
			}

			if (!empty($request['since_id'])) {
				$condition = DBA::mergeConditions($condition, ["`uri-id` > ?", intval($request['since_id'])]);
			}

			if (!empty($request['min_id'])) {
				$condition = DBA::mergeConditions($condition, ["`uri-id` > ?", intval($request['min_id'])]);
			}
		} else {
			switch ($requested_order) {
				case TimelineOrderByTypes::RECEIVED:
				case TimelineOrderByTypes::CHANGED:
				case TimelineOrderByTypes::EDITED:
				case TimelineOrderByTypes::CREATED:
				case TimelineOrderByTypes::COMMENTED:
					$order_field = $requested_order;
					break;
				default:
					throw new \Exception("Unrecognized request order: $requested_order");
			}

			if (!empty($request['max_id'])) {
				$condition = DBA::mergeConditions($condition, ["`$order_field` < ?", DateTimeFormat::convert($request['max_id'], DateTimeFormat::MYSQL)]);
			}

			if (!empty($request['since_id'])) {
				$condition = DBA::mergeConditions($condition, ["`$order_field` > ?", DateTimeFormat::convert($request['since_id'], DateTimeFormat::MYSQL)]);
			}

			if (!empty($request['min_id'])) {
				$condition = DBA::mergeConditions($condition, ["`$order_field` > ?", DateTimeFormat::convert($request['min_id'], DateTimeFormat::MYSQL)]);
			}
		}

		return $condition;
	}

	/**
	 * Processes data from GET requests and sets paging conditions
	 *
	 * @param array $request  Custom REQUEST array
	 * @param array $params   Existing $params element to build on
	 * @return array ordering data added to the params blocks that was passed in
	 * @throws \Exception
	 */
	protected function buildOrderAndLimitParams(array $request, array $params = []): array
	{
		$requested_order = $request['friendica_order'];
		switch ($requested_order) {
			case TimelineOrderByTypes::CHANGED:
			case TimelineOrderByTypes::CREATED:
			case TimelineOrderByTypes::COMMENTED:
			case TimelineOrderByTypes::EDITED:
			case TimelineOrderByTypes::RECEIVED:
				$order_field = $requested_order;
				break;
			case TimelineOrderByTypes::ID:
			default:
				$order_field = 'uri-id';
		}

		if (!empty($request['min_id'])) {
			$params['order'] = [$order_field];
		} else {
			$params['order'] = [$order_field => true];
		}

		$params['limit'] = $request['limit'];

		return $params;
	}

	/**
	 * Update the ID/time boundaries for this result set. Used for building Link Headers
	 *
	 * @param Status $status
	 * @param array $post_item
	 * @param string $order
	 * @return void
	 * @throws \Exception
	 */
	protected function updateBoundaries(Status $status, array $post_item, string $order)
	{
		try {
			switch ($order) {
				case TimelineOrderByTypes::CHANGED:
					if (!empty($status->friendicaExtension()->changedAt())) {
						self::setBoundaries(new DateTime(DateTimeFormat::utc($status->friendicaExtension()->changedAt(), DateTimeFormat::JSON)));
					}
					break;
				case TimelineOrderByTypes::CREATED:
					if (!empty($status->createdAt())) {
						self::setBoundaries(new DateTime(DateTimeFormat::utc($status->createdAt(), DateTimeFormat::JSON)));
					}
					break;
				case TimelineOrderByTypes::COMMENTED:
					if (!empty($status->friendicaExtension()->commentedAt())) {
						self::setBoundaries(new DateTime(DateTimeFormat::utc($status->friendicaExtension()->commentedAt(), DateTimeFormat::JSON)));
					}
					break;
				case TimelineOrderByTypes::EDITED:
					if (!empty($status->editedAt())) {
						self::setBoundaries(new DateTime(DateTimeFormat::utc($status->editedAt(), DateTimeFormat::JSON)));
					}
					break;
				case TimelineOrderByTypes::RECEIVED:
					if (!empty($status->friendicaExtension()->receivedAt())) {
						self::setBoundaries(new DateTime(DateTimeFormat::utc($status->friendicaExtension()->receivedAt(), DateTimeFormat::JSON)));
					}
					break;
				case TimelineOrderByTypes::ID:
				default:
					self::setBoundaries($post_item['uri-id']);
			}
		} catch (\Exception $e) {
			$this->logger->debug('Error processing page boundary calculation, skipping', ['error' => $e]);
		}
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
	 *
	 * @param int|\DateTime $id
	 */
	protected static function setBoundaries($id)
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
	 * Get the "link" header with "next" and "prev" links
	 * @return string
	 */
	protected static function getLinkHeader(bool $asDate = false): string
	{
		if (empty(self::$boundaries)) {
			return '';
		}

		$request = self::$request;

		unset($request['min_id']);
		unset($request['max_id']);
		unset($request['since_id']);

		$prev_request = $next_request = $request;

		if ($asDate) {
			$max_date               = self::$boundaries['max'];
			$min_date               = self::$boundaries['min'];
			$prev_request['min_id'] = $max_date->format(DateTimeFormat::JSON);
			$next_request['max_id'] = $min_date->format(DateTimeFormat::JSON);
		} else {
			$prev_request['min_id'] = self::$boundaries['max'];
			$next_request['max_id'] = self::$boundaries['min'];
		}

		$command = DI::baseUrl() . '/' . DI::args()->getCommand();

		$prev = $command . '?' . http_build_query($prev_request);
		$next = $command . '?' . http_build_query($next_request);

		return 'Link: <' . $next . '>; rel="next", <' . $prev . '>; rel="prev"';
	}

	/**
	 * Get the "link" header with "next" and "prev" links for an offset/limit type call
	 * @return string
	 */
	protected static function getOffsetAndLimitLinkHeader(int $offset, int $limit): string
	{
		$request = self::$request;

		unset($request['offset']);
		$request['limit'] = $limit;

		$prev_request = $next_request = $request;

		$prev_request['offset'] = $offset - $limit;
		$next_request['offset'] = $offset + $limit;

		$command = DI::baseUrl() . '/' . DI::args()->getCommand();

		$prev = $command . '?' . http_build_query($prev_request);
		$next = $command . '?' . http_build_query($next_request);

		if ($prev_request['offset'] >= 0) {
			return 'Link: <' . $next . '>; rel="next", <' . $prev . '>; rel="prev"';
		} else {
			return 'Link: <' . $next . '>; rel="next"';
		}
	}

	/**
	 * Set the "link" header with "next" and "prev" links
	 * @return void
	 */
	protected static function setLinkHeader(bool $asDate = false)
	{
		$header = self::getLinkHeader($asDate);
		if (!empty($header)) {
			header($header);
		}
	}

	/**
	 * Set the "link" header with "next" and "prev" links
	 * @return void
	 */
	protected static function setLinkHeaderByOffsetLimit(int $offset, int $limit)
	{
		$header = self::getOffsetAndLimitLinkHeader($offset, $limit);
		if (!empty($header)) {
			header($header);
		}
	}

	/**
	 * Check if the app is known to support quoted posts
	 *
	 * @return bool
	 */
	public static function appSupportsQuotes(): bool
	{
		$token = OAuth::getCurrentApplicationToken();
		return (!empty($token['name']) && in_array($token['name'], ['Fedilab']));
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
	public function checkAllowedScope(string $scope)
	{
		$token = self::getCurrentApplication();

		if (empty($token)) {
			$this->logger->notice('Empty application token');
			$this->logAndJsonError(403, $this->errorFactory->Forbidden());
		}

		if (!isset($token[$scope])) {
			$this->logger->warning('The requested scope does not exist', ['scope' => $scope, 'application' => $token]);
			$this->logAndJsonError(403, $this->errorFactory->Forbidden());
		}

		if (empty($token[$scope])) {
			$this->logger->warning('The requested scope is not allowed', ['scope' => $scope, 'application' => $token]);
			$this->logAndJsonError(403, $this->errorFactory->Forbidden());
		}
	}

	public function checkThrottleLimit()
	{
		$uid = self::getCurrentUserID();

		// Check for throttling (maximum posts per day, week and month)
		$throttle_day = DI::config()->get('system', 'throttle_limit_day');
		if ($throttle_day > 0) {
			$datefrom = date(DateTimeFormat::MYSQL, time() - 24 * 60 * 60);

			$condition = ["`gravity` = ? AND `uid` = ? AND `wall` AND `received` > ?", Item::GRAVITY_PARENT, $uid, $datefrom];
			$posts_day = Post::countThread($condition);

			if ($posts_day > $throttle_day) {
				$this->logger->notice('Daily posting limit reached', ['uid' => $uid, 'posts' => $posts_day, 'limit' => $throttle_day]);
				$error             = $this->t('Too Many Requests');
				$error_description = $this->tt("Daily posting limit of %d post reached. The post was rejected.", "Daily posting limit of %d posts reached. The post was rejected.", $throttle_day);
				$errorobj          = new \Friendica\Object\Api\Mastodon\Error($error, $error_description);
				$this->jsonError(429, $errorobj->toArray());
			}
		}

		$throttle_week = DI::config()->get('system', 'throttle_limit_week');
		if ($throttle_week > 0) {
			$datefrom = date(DateTimeFormat::MYSQL, time() - 24 * 60 * 60 * 7);

			$condition  = ["`gravity` = ? AND `uid` = ? AND `wall` AND `received` > ?", Item::GRAVITY_PARENT, $uid, $datefrom];
			$posts_week = Post::countThread($condition);

			if ($posts_week > $throttle_week) {
				$this->logger->notice('Weekly posting limit reached', ['uid' => $uid, 'posts' => $posts_week, 'limit' => $throttle_week]);
				$error             = $this->t('Too Many Requests');
				$error_description = $this->tt("Weekly posting limit of %d post reached. The post was rejected.", "Weekly posting limit of %d posts reached. The post was rejected.", $throttle_week);
				$errorobj          = new \Friendica\Object\Api\Mastodon\Error($error, $error_description);
				$this->jsonError(429, $errorobj->toArray());
			}
		}

		$throttle_month = DI::config()->get('system', 'throttle_limit_month');
		if ($throttle_month > 0) {
			$datefrom = date(DateTimeFormat::MYSQL, time() - 24 * 60 * 60 * 30);

			$condition   = ["`gravity` = ? AND `uid` = ? AND `wall` AND `received` > ?", Item::GRAVITY_PARENT, $uid, $datefrom];
			$posts_month = Post::countThread($condition);

			if ($posts_month > $throttle_month) {
				$this->logger->notice('Monthly posting limit reached', ['uid' => $uid, 'posts' => $posts_month, 'limit' => $throttle_month]);
				$error             = $this->t('Too Many Requests');
				$error_description = $this->tt('Monthly posting limit of %d post reached. The post was rejected.', 'Monthly posting limit of %d posts reached. The post was rejected.', $throttle_month);
				$errorobj          = new \Friendica\Object\Api\Mastodon\Error($error, $error_description);
				$this->jsonError(429, $errorobj->toArray());
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

	/**
	 * @param int   $errorno
	 * @param Error $error
	 * @return never
	 * @throws HTTPException\InternalServerErrorException
	 */
	protected function logAndJsonError(int $errorno, Error $error)
	{
		$this->logger->info('API Error', ['no' => $errorno, 'error' => $error->toArray(), 'method' => $this->args->getMethod(), 'command' => $this->args->getQueryString(), 'user-agent' => $this->server['HTTP_USER_AGENT'] ?? '']);
		$this->jsonError(403, $error->toArray());
	}
}
