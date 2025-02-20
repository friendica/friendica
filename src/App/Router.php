<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\App;

use FastRoute\DataGenerator\GroupCountBased;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std;
use Friendica\Core\Addon\AddonHelper;
use Friendica\Core\Cache\Enum\Duration;
use Friendica\Core\Cache\Capability\ICanCache;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\Lock\Capability\ICanLock;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\LegacyModule;
use Friendica\Module\HTTPException\MethodNotAllowed;
use Friendica\Module\HTTPException\PageNotFound;
use Friendica\Module\Special\Options;
use Friendica\Network\HTTPException;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Network\HTTPException\MethodNotAllowedException;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Util\Router\FriendicaGroupCountBased;
use Psr\Log\LoggerInterface;

/**
 * Wrapper for FastRoute\Router
 *
 * This wrapper only makes use of a subset of the router features, mainly parses a route rule to return the relevant
 * module class.
 *
 * Actual routes are defined in App->collectRoutes.
 *
 * @package Friendica\App
 */
class Router
{
	const DELETE  = 'DELETE';
	const GET     = 'GET';
	const PATCH   = 'PATCH';
	const POST    = 'POST';
	const PUT     = 'PUT';
	const OPTIONS = 'OPTIONS';

	const ALLOWED_METHODS = [
		self::DELETE,
		self::GET,
		self::PATCH,
		self::POST,
		self::PUT,
		self::OPTIONS
	];

	/** @var RouteCollector */
	protected $routeCollector;

	/**
	 * @var array Module parameters
	 */
	protected $parameters = [];

	/** @var L10n */
	private $l10n;

	/** @var ICanCache */
	private $cache;

	/** @var ICanLock */
	private $lock;

	/** @var Arguments */
	private $args;

	/** @var IManageConfigValues */
	private $config;

	/** @var LoggerInterface */
	private $logger;

	private AddonHelper $addonHelper;

	/** @var bool */
	private $isLocalUser;

	/** @var string */
	private $baseRoutesFilepath;

	/** @var array */
	private $server;

	/** @var string|null */
	protected $moduleClass = null;

	/**
	 * @param array               $server             The $_SERVER variable
	 * @param string              $baseRoutesFilepath The path to a base routes file to leverage cache, can be empty
	 * @param L10n                $l10n
	 * @param ICanCache           $cache
	 * @param ICanLock            $lock
	 * @param IManageConfigValues $config
	 * @param Arguments           $args
	 * @param LoggerInterface     $logger
	 * @param IHandleUserSessions $userSession
	 * @param RouteCollector|null $routeCollector
	 */
	public function __construct(array $server, string $baseRoutesFilepath, L10n $l10n, ICanCache $cache, ICanLock $lock, IManageConfigValues $config, Arguments $args, LoggerInterface $logger, AddonHelper $addonHelper, IHandleUserSessions $userSession, RouteCollector $routeCollector = null)
	{
		$this->baseRoutesFilepath = $baseRoutesFilepath;
		$this->l10n               = $l10n;
		$this->cache              = $cache;
		$this->lock               = $lock;
		$this->args               = $args;
		$this->config             = $config;
		$this->server             = $server;
		$this->logger             = $logger;
		$this->addonHelper        = $addonHelper;
		$this->isLocalUser        = !empty($userSession->getLocalUserId());

		$this->routeCollector = $routeCollector ?? new RouteCollector(new Std(), new GroupCountBased());

		if ($this->baseRoutesFilepath && !file_exists($this->baseRoutesFilepath)) {
			throw new HTTPException\InternalServerErrorException('Routes file path does\'n exist.');
		}

		$this->parameters = [$this->server];
	}

	/**
	 * This will be called either automatically if a base routes file path was submitted,
	 * or can be called manually with a custom route array.
	 *
	 * @param array $routes The routes to add to the Router
	 *
	 * @return self The router instance with the loaded routes
	 *
	 * @throws HTTPException\InternalServerErrorException In case of invalid configs
	 */
	public function loadRoutes(array $routes): Router
	{
		$routeCollector = ($this->routeCollector ?? new RouteCollector(new Std(), new GroupCountBased()));

		$this->addRoutes($routeCollector, $routes);

		$this->routeCollector = $routeCollector;

		// Add routes from addons
		Hook::callAll('route_collection', $this->routeCollector);

		return $this;
	}

	/**
	 * Adds multiple routes to a route collector
	 *
	 * @param RouteCollector $routeCollector Route collector instance
	 * @param array $routes Multiple routes to be added
	 * @throws HTTPException\InternalServerErrorException If route was wrong (somehow)
	 */
	private function addRoutes(RouteCollector $routeCollector, array $routes)
	{
		foreach ($routes as $route => $config) {
			if ($this->isGroup($config)) {
				$this->addGroup($route, $config, $routeCollector);
			} elseif ($this->isRoute($config)) {
				// Always add the OPTIONS endpoint to a route
				$httpMethods   = (array) $config[1];
				$httpMethods[] = Router::OPTIONS;
				$routeCollector->addRoute($httpMethods, $route, $config[0]);
			} else {
				throw new HTTPException\InternalServerErrorException("Wrong route config for route '" . print_r($route, true) . "'");
			}
		}
	}

	/**
	 * Adds a group of routes to a given group
	 *
	 * @param string         $groupRoute     The route of the group
	 * @param array          $routes         The routes of the group
	 * @param RouteCollector $routeCollector The route collector to add this group
	 */
	private function addGroup(string $groupRoute, array $routes, RouteCollector $routeCollector)
	{
		$routeCollector->addGroup($groupRoute, function (RouteCollector $routeCollector) use ($routes) {
			$this->addRoutes($routeCollector, $routes);
		});
	}

	/**
	 * Returns true in case the config is a group config
	 *
	 * @param array $config
	 *
	 * @return bool
	 */
	private function isGroup(array $config): bool
	{
		return
			is_array($config) &&
			is_string(array_keys($config)[0]) &&
			// This entry should NOT be a BaseModule
			(substr(array_keys($config)[0], 0, strlen('Friendica\Module')) !== 'Friendica\Module') &&
			// The second argument is an array (another routes)
			is_array(array_values($config)[0]);
	}

	/**
	 * Returns true in case the config is a route config
	 *
	 * @param array $config
	 *
	 * @return bool
	 */
	private function isRoute(array $config): bool
	{
		return
			// The config array should at least have one entry
			!empty($config[0]) &&
			// This entry should be a BaseModule
			(substr($config[0], 0, strlen('Friendica\Module')) === 'Friendica\Module') &&
			// Either there is no other argument
			(empty($config[1]) ||
			 // Or the second argument is an array (HTTP-Methods)
			 is_array($config[1]));
	}

	/**
	 * The current route collector
	 *
	 * @return RouteCollector|null
	 */
	public function getRouteCollector(): ?RouteCollector
	{
		return $this->routeCollector;
	}

	/**
	 * Returns the Friendica\BaseModule-extending class name if a route rule matched
	 *
	 * @return string
	 *
	 * @throws InternalServerErrorException
	 * @throws MethodNotAllowedException
	 */
	public function getModuleClass(): string
	{
		if (empty($this->moduleClass)) {
			$this->determineModuleClass();
		}

		return $this->moduleClass;
	}

	/**
	 * Returns the relevant module class name for the given page URI or NULL if no route rule matched.
	 *
	 * @return void
	 *
	 * @throws HTTPException\InternalServerErrorException Unexpected exceptions
	 * @throws HTTPException\MethodNotAllowedException    If a rule is private only
	 */
	private function determineModuleClass(): void
	{
		$cmd = $this->args->getCommand();
		$cmd = '/' . ltrim($cmd, '/');

		$dispatcher = new FriendicaGroupCountBased($this->getCachedDispatchData());

		try {
			// Check if the HTTP method is OPTIONS and return the special Options Module with the possible HTTP methods
			if ($this->args->getMethod() === static::OPTIONS) {
				$this->moduleClass  = Options::class;
				$this->parameters[] = ['AllowedMethods' => $dispatcher->getOptions($cmd)];
			} else {
				$routeInfo = $dispatcher->dispatch($this->args->getMethod(), $cmd);
				if ($routeInfo[0] === Dispatcher::FOUND) {
					$this->moduleClass  = $routeInfo[1];
					$this->parameters[] = $routeInfo[2];
				} elseif ($routeInfo[0] === Dispatcher::METHOD_NOT_ALLOWED) {
					throw new HTTPException\MethodNotAllowedException($this->l10n->t('Method not allowed for this module. Allowed method(s): %s', implode(', ', $routeInfo[1])));
				} else {
					throw new HTTPException\NotFoundException($this->l10n->t('Page not found.'));
				}
			}
		} catch (MethodNotAllowedException $e) {
			$this->moduleClass = MethodNotAllowed::class;
		} catch (NotFoundException $e) {
			$moduleName = $this->args->getModuleName();
			// Then we try addon-provided modules that we wrap in the LegacyModule class
			if ($this->addonHelper->isAddonEnabled($moduleName) && file_exists("addon/{$moduleName}/{$moduleName}.php")) {
				//Check if module is an app and if public access to apps is allowed or not
				$privateapps = $this->config->get('config', 'private_addons', false);
				if (!$this->isLocalUser && Hook::isAddonApp($moduleName) && $privateapps) {
					throw new MethodNotAllowedException($this->l10n->t("You must be logged in to use addons. "));
				} else {
					include_once "addon/{$moduleName}/{$moduleName}.php";
					if (function_exists($moduleName . '_module')) {
						$this->parameters[] = "addon/{$moduleName}/{$moduleName}.php";
						$this->moduleClass  = LegacyModule::class;
					}
				}
			}

			/* Finally, we look for a 'standard' program module in the 'mod' directory
			 * We emulate a Module class through the LegacyModule class
			 */
			if (!$this->moduleClass && file_exists("mod/{$moduleName}.php")) {
				$this->parameters[] = "mod/{$moduleName}.php";
				$this->moduleClass  = LegacyModule::class;
			}

			$this->moduleClass = $this->moduleClass ?: PageNotFound::class;
		}
	}

	public function getParameters(): array
	{
		return $this->parameters;
	}

	/**
	 * If a base routes file path has been provided, we can load routes from it if the cache misses.
	 *
	 * @return array
	 * @throws HTTPException\InternalServerErrorException
	 */
	private function getDispatchData()
	{
		$dispatchData = [];

		if ($this->baseRoutesFilepath) {
			$dispatchData = require $this->baseRoutesFilepath;
			if (!is_array($dispatchData)) {
				throw new HTTPException\InternalServerErrorException('Invalid base routes file');
			}
		}

		$this->loadRoutes($dispatchData);

		return $this->routeCollector->getData();
	}

	/**
	 * We cache the dispatch data for speed, as computing the current routes (version 2020.09)
	 * takes about 850ms for each requests.
	 *
	 * The cached "routerDispatchData" lasts for a day, and must be cleared manually when there
	 * is any changes in the enabled addons list.
	 *
	 * Additionally, we check for the base routes file last modification time to automatically
	 * trigger re-computing the dispatch data.
	 *
	 * @return array|mixed
	 * @throws HTTPException\InternalServerErrorException
	 */
	private function getCachedDispatchData()
	{
		$routerDispatchData         = $this->cache->get('routerDispatchData');
		$lastRoutesFileModifiedTime = $this->cache->get('lastRoutesFileModifiedTime');
		$forceRecompute             = false;

		if ($this->baseRoutesFilepath) {
			$routesFileModifiedTime = filemtime($this->baseRoutesFilepath);
			$forceRecompute         = $lastRoutesFileModifiedTime != $routesFileModifiedTime;
		}

		if (!$forceRecompute && $routerDispatchData) {
			return $routerDispatchData;
		}

		if (!$this->lock->acquire('getCachedDispatchData', 0)) {
			// Immediately return uncached data when we can't acquire a lock
			return $this->getDispatchData();
		}

		$routerDispatchData = $this->getDispatchData();

		$this->cache->set('routerDispatchData', $routerDispatchData, Duration::DAY);
		if (!empty($routesFileModifiedTime)) {
			$this->cache->set('lastRoutesFileModifiedTime', $routesFileModifiedTime, Duration::MONTH);
		}

		if ($this->lock->isLocked('getCachedDispatchData')) {
			$this->lock->release('getCachedDispatchData');
		}

		return $routerDispatchData;
	}
}
