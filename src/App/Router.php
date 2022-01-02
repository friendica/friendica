<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
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

namespace Friendica\App;

use Dice\Dice;
use FastRoute\DataGenerator\GroupCountBased;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std;
use Friendica\Capabilities\ICanHandleRequests;
use Friendica\Core\Addon;
use Friendica\Core\Cache\Enum\Duration;
use Friendica\Core\Cache\Capability\ICanCache;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\Lock\Capability\ICanLock;
use Friendica\LegacyModule;
use Friendica\Module\HTTPException\MethodNotAllowed;
use Friendica\Module\HTTPException\PageNotFound;
use Friendica\Network\HTTPException;
use Friendica\Network\HTTPException\MethodNotAllowedException;
use Friendica\Network\HTTPException\NoContentException;
use Friendica\Network\HTTPException\NotFoundException;
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
	private $parameters = [];

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

	/** @var float */
	private $dice_profiler_threshold;

	/** @var Dice */
	private $dice;

	/** @var string */
	private $baseRoutesFilepath;

	/** @var array */
	private $server;

	/**
	 * @param array               $server             The $_SERVER variable
	 * @param string              $baseRoutesFilepath The path to a base routes file to leverage cache, can be empty
	 * @param L10n                $l10n
	 * @param ICanCache           $cache
	 * @param ICanLock            $lock
	 * @param IManageConfigValues $config
	 * @param Arguments           $args
	 * @param LoggerInterface     $logger
	 * @param Dice                $dice
	 * @param RouteCollector|null $routeCollector
	 */
	public function __construct(array $server, string $baseRoutesFilepath, L10n $l10n, ICanCache $cache, ICanLock $lock, IManageConfigValues $config, Arguments $args, LoggerInterface $logger, Dice $dice, RouteCollector $routeCollector = null)
	{
		$this->baseRoutesFilepath = $baseRoutesFilepath;
		$this->l10n = $l10n;
		$this->cache = $cache;
		$this->lock = $lock;
		$this->args = $args;
		$this->config = $config;
		$this->dice = $dice;
		$this->server = $server;
		$this->logger = $logger;
		$this->dice_profiler_threshold = $config->get('system', 'dice_profiler_threshold', 0);

		// @see https://github.com/tootsuite/mastodon/blob/c3aef491d66aec743a3a53e934a494f653745b61/config/initializers/cors.rb
		if (substr($this->args->getQueryString(), 0, 12) == '.well-known/') {
			header('Access-Control-Allow-Origin: *');
			header('Access-Control-Allow-Headers: *');
			header('Access-Control-Allow-Methods: ' . self::GET);
			header('Access-Control-Allow-Credentials: false');
		} elseif (substr($this->args->getQueryString(), 0, 8) == 'profile/') {
			header('Access-Control-Allow-Origin: *');
			header('Access-Control-Allow-Headers: *');
			header('Access-Control-Allow-Methods: ' . self::GET);
			header('Access-Control-Allow-Credentials: false');
		} elseif (substr($this->args->getQueryString(), 0, 4) == 'api/') {
			header('Access-Control-Allow-Origin: *');
			header('Access-Control-Allow-Headers: *');
			header('Access-Control-Allow-Methods: ' . implode(',', self::ALLOWED_METHODS));
			header('Access-Control-Allow-Credentials: false');
			header('Access-Control-Expose-Headers: Link');
		} elseif (substr($this->args->getQueryString(), 0, 11) == 'oauth/token') {
			header('Access-Control-Allow-Origin: *');
			header('Access-Control-Allow-Headers: *');
			header('Access-Control-Allow-Methods: ' . self::POST);
			header('Access-Control-Allow-Credentials: false');
		}

		// @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods/OPTIONS
		// @todo Check allowed methods per requested path
		if ($this->args->getMethod() == self::OPTIONS) {
			header('HTTP/1.1 204 No Content');
			header('Allow: ' . implode(',', self::ALLOWED_METHODS));
			exit();
		}

		$this->routeCollector = isset($routeCollector) ?
			$routeCollector :
			new RouteCollector(new Std(), new GroupCountBased());

		if ($this->baseRoutesFilepath && !file_exists($this->baseRoutesFilepath)) {
			throw new HTTPException\InternalServerErrorException('Routes file path does\'n exist.');
		}
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
	public function loadRoutes(array $routes)
	{
		$routeCollector = (isset($this->routeCollector) ?
			$this->routeCollector :
			new RouteCollector(new Std(), new GroupCountBased()));

		$this->addRoutes($routeCollector, $routes);

		$this->routeCollector = $routeCollector;

		// Add routes from addons
		Hook::callAll('route_collection', $this->routeCollector);

		return $this;
	}

	private function addRoutes(RouteCollector $routeCollector, array $routes)
	{
		foreach ($routes as $route => $config) {
			if ($this->isGroup($config)) {
				$this->addGroup($route, $config, $routeCollector);
			} elseif ($this->isRoute($config)) {
				$routeCollector->addRoute($config[1], $route, $config[0]);
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
	private function isGroup(array $config)
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
	private function isRoute(array $config)
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
	public function getRouteCollector()
	{
		return $this->routeCollector;
	}

	/**
	 * Returns the relevant module class name for the given page URI or NULL if no route rule matched.
	 *
	 * @return string A Friendica\BaseModule-extending class name if a route rule matched
	 *
	 * @throws HTTPException\InternalServerErrorException
	 * @throws HTTPException\MethodNotAllowedException    If a rule matched but the method didn't
	 * @throws HTTPException\NotFoundException            If no rule matched
	 */
	private function getModuleClass()
	{
		$cmd = $this->args->getCommand();
		$cmd = '/' . ltrim($cmd, '/');

		$dispatcher = new Dispatcher\GroupCountBased($this->getCachedDispatchData());

		$this->parameters = [];

		$routeInfo  = $dispatcher->dispatch($this->args->getMethod(), $cmd);
		if ($routeInfo[0] === Dispatcher::FOUND) {
			$moduleClass = $routeInfo[1];
			$this->parameters = $routeInfo[2];
		} elseif ($routeInfo[0] === Dispatcher::METHOD_NOT_ALLOWED) {
			throw new HTTPException\MethodNotAllowedException($this->l10n->t('Method not allowed for this module. Allowed method(s): %s', implode(', ', $routeInfo[1])));
		} else {
			throw new HTTPException\NotFoundException($this->l10n->t('Page not found.'));
		}

		return $moduleClass;
	}

	public function getModule(?string $module_class = null): ICanHandleRequests
	{
		$module_parameters = [$this->server];
		/**
		 * ROUTING
		 *
		 * From the request URL, routing consists of obtaining the name of a BaseModule-extending class of which the
		 * post() and/or content() static methods can be respectively called to produce a data change or an output.
		 **/
		try {
			$module_class        = $module_class ?? $this->getModuleClass();
			$module_parameters[] = $this->parameters;
		} catch (MethodNotAllowedException $e) {
			$module_class = MethodNotAllowed::class;
		} catch (NotFoundException $e) {
			$moduleName = $this->args->getModuleName();
			// Then we try addon-provided modules that we wrap in the LegacyModule class
			if (Addon::isEnabled($moduleName) && file_exists("addon/{$moduleName}/{$moduleName}.php")) {
				//Check if module is an app and if public access to apps is allowed or not
				$privateapps = $this->config->get('config', 'private_addons', false);
				if ((!local_user()) && Hook::isAddonApp($moduleName) && $privateapps) {
					throw new MethodNotAllowedException($this->l10n->t("You must be logged in to use addons. "));
				} else {
					include_once "addon/{$moduleName}/{$moduleName}.php";
					if (function_exists($moduleName . '_module')) {
						$module_parameters[] = "addon/{$moduleName}/{$moduleName}.php";
						$module_class        = LegacyModule::class;
					}
				}
			}

			/* Finally, we look for a 'standard' program module in the 'mod' directory
			 * We emulate a Module class through the LegacyModule class
			 */
			if (!$module_class && file_exists("mod/{$moduleName}.php")) {
				$module_parameters[] = "mod/{$moduleName}.php";
				$module_class        = LegacyModule::class;
			}

			$module_class = $module_class ?: PageNotFound::class;
		}

		$stamp = microtime(true);

		try {
			/** @var ICanHandleRequests $module */
			return $this->dice->create($module_class, $module_parameters);
		} finally {
			if ($this->dice_profiler_threshold > 0) {
				$dur = floatval(microtime(true) - $stamp);
				if ($dur >= $this->dice_profiler_threshold) {
					$this->logger->warning('Dice module creation lasts too long.', ['duration' => round($dur, 3), 'module' => $module_class, 'parameters' => $module_parameters]);
				}
			}
		}
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
		$routerDispatchData = $this->cache->get('routerDispatchData');
		$lastRoutesFileModifiedTime = $this->cache->get('lastRoutesFileModifiedTime');
		$forceRecompute = false;

		if ($this->baseRoutesFilepath) {
			$routesFileModifiedTime = filemtime($this->baseRoutesFilepath);
			$forceRecompute = $lastRoutesFileModifiedTime != $routesFileModifiedTime;
		}

		if (!$forceRecompute && $routerDispatchData) {
			return $routerDispatchData;
		}

		if (!$this->lock->acquire('getCachedDispatchData', 0)) {
			// Immediately return uncached data when we can't aquire a lock
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
