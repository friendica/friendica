<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica;

use Dice\Dice;
use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\App\Mode;
use Friendica\App\Page;
use Friendica\App\Request;
use Friendica\App\Router;
use Friendica\Capabilities\ICanCreateResponses;
use Friendica\Capabilities\ICanHandleRequests;
use Friendica\Content\Nav;
use Friendica\Core\Addon\Capability\ICanLoadAddons;
use Friendica\Core\Config\Factory\Config;
use Friendica\Core\Container;
use Friendica\Core\Logger\LoggerManager;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\DiceContainer;
use Friendica\Core\L10n;
use Friendica\Core\Logger\Capability\LogChannel;
use Friendica\Core\Logger\Handler\ErrorHandler;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\System;
use Friendica\Core\Update;
use Friendica\Database\Definition\DbaDefinition;
use Friendica\Database\Definition\ViewDefinition;
use Friendica\Event\ConfigLoadedEvent;
use Friendica\Event\Event;
use Friendica\EventSubscriber\HookEventBridge;
use Friendica\Module\Maintenance;
use Friendica\Module\Special\HTTPException as ModuleHTTPException;
use Friendica\Network\HTTPException;
use Friendica\Protocol\ATProtocol\DID;
use Friendica\Security\Authentication;
use Friendica\Security\ExAuth;
use Friendica\Security\OpenWebAuth;
use Friendica\Util\BasePath;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\HTTPInputData;
use Friendica\Util\HTTPSignature;
use Friendica\Util\Profiler;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Our main application structure for the life of this page.
 *
 * Primarily deals with the URL that got us here
 * and tries to make some sense of it, and
 * stores our page contents and config storage
 * and anything else that might need to be passed around
 * before we spit the page out.
 *
 * @final
 */
class App
{
	const PLATFORM = 'Friendica';
	const CODENAME = 'Interrupted Fern';
	const VERSION  = '2025.02-dev';

	/**
	 * @internal
	 */
	public static function fromContainer(Container $container): self
	{
		return new self($container);
	}

	/**
	 * @var Container
	 */
	private $container;

	/**
	 * @var Mode The Mode of the Application
	 */
	private $mode;

	/**
	 * @var BaseURL
	 */
	private $baseURL;

	/** @var string */
	private $requestId;

	/** @var Authentication */
	private $auth;

	/**
	 * @var IManageConfigValues The config
	 */
	private $config;

	/**
	 * @var LoggerInterface The logger
	 */
	private $logger;

	/**
	 * @var Profiler The profiler of this app
	 */
	private $profiler;

	/**
	 * @var L10n The translator
	 */
	private $l10n;

	/**
	 * @var App\Arguments
	 */
	private $args;

	/**
	 * @var IHandleUserSessions
	 */
	private $session;

	/**
	 * @var AppHelper $appHelper
	 */
	private $appHelper;

	private function __construct(Container $container)
	{
		$this->container = $container;
	}

	/**
	 * @internal
	 */
	public function processRequest(ServerRequestInterface $request, float $start_time): void
	{
		$this->container->addRule(Mode::class, [
			'call' => [
				['determineRunMode', [false, $request->getServerParams()], Dice::CHAIN_CALL],
			],
		]);

		$this->setupContainerForAddons();

		$this->setupLogChannel(LogChannel::APP);

		$this->setupLegacyServiceLocator();

		$this->registerErrorHandler();

		$this->registerEventDispatcher();

		$this->requestId = $this->container->create(Request::class)->getRequestId();
		$this->auth      = $this->container->create(Authentication::class);
		$this->config    = $this->container->create(IManageConfigValues::class);
		$this->mode      = $this->container->create(Mode::class);
		$this->baseURL   = $this->container->create(BaseURL::class);
		$this->logger    = $this->container->create(LoggerInterface::class);
		$this->profiler  = $this->container->create(Profiler::class);
		$this->l10n      = $this->container->create(L10n::class);
		$this->args      = $this->container->create(Arguments::class);
		$this->session   = $this->container->create(IHandleUserSessions::class);
		$this->appHelper = $this->container->create(AppHelper::class);

		$this->load(
			$request->getServerParams(),
			$this->container->create(DbaDefinition::class),
			$this->container->create(ViewDefinition::class),
			$this->mode,
			$this->config,
			$this->profiler,
			$this->container->create(EventDispatcherInterface::class),
			$this->appHelper,
		);

		$this->registerTemplateEngine();

		$this->runFrontend(
			$this->container->create(EventDispatcherInterface::class),
			$this->container->create(IManagePersonalConfigValues::class),
			$this->container->create(Page::class),
			$this->container->create(Nav::class),
			$this->container->create(ModuleHTTPException::class),
			$start_time,
			$request
		);
	}

	/**
	 * @internal
	 */
	public function processConsole(array $serverParams): void
	{
		$argv = $serverParams['argv'] ?? [];

		$this->setupContainerForAddons();

		$this->setupLogChannel($this->determineLogChannel($argv));

		$this->setupLegacyServiceLocator();

		$this->registerErrorHandler();

		$this->registerEventDispatcher();

		$this->load(
			$serverParams,
			$this->container->create(DbaDefinition::class),
			$this->container->create(ViewDefinition::class),
			$this->container->create(Mode::class),
			$this->container->create(IManageConfigValues::class),
			$this->container->create(Profiler::class),
			$this->container->create(EventDispatcherInterface::class),
			$this->container->create(AppHelper::class),
		);

		$this->registerTemplateEngine();

		(\Friendica\Core\Console::create($this->container, $argv))->execute();
	}

	/**
	 * @internal
	 */
	public function processEjabberd(array $serverParams): void
	{
		$this->setupContainerForAddons();

		$this->setupLogChannel(LogChannel::AUTH_JABBERED);

		$this->setupLegacyServiceLocator();

		$this->registerErrorHandler();

		$this->registerEventDispatcher();

		$this->load(
			$serverParams,
			$this->container->create(DbaDefinition::class),
			$this->container->create(ViewDefinition::class),
			$this->container->create(Mode::class),
			$this->container->create(IManageConfigValues::class),
			$this->container->create(Profiler::class),
			$this->container->create(EventDispatcherInterface::class),
			$this->container->create(AppHelper::class),
		);

		/** @var BasePath */
		$basePath = $this->container->create(BasePath::class);

		// Check the database structure and possibly fixes it
		Update::check($basePath->getPath(), true);

		$appMode = $this->container->create(Mode::class);

		if ($appMode->isNormal()) {
			/** @var ExAuth $oAuth */
			$oAuth = $this->container->create(ExAuth::class);
			$oAuth->readStdin();
		}
	}

	private function setupContainerForAddons(): void
	{
		/** @var ICanLoadAddons $addonLoader */
		$addonLoader = $this->container->create(ICanLoadAddons::class);

		foreach ($addonLoader->getActiveAddonConfig('dependencies') as $name => $rule) {
			$this->container->addRule($name, $rule);
		}
	}

	private function determineLogChannel(array $argv): string
	{
		$command = strtolower($argv[1] ?? '');

		if ($command === 'daemon' || $command === 'jetstream') {
			return LogChannel::DAEMON;
		}

		if ($command === 'worker') {
			return LogChannel::WORKER;
		}

		// @TODO Add support for jetstream

		return LogChannel::CONSOLE;
	}

	private function setupLogChannel(string $logChannel): void
	{
		/** @var LoggerManager */
		$loggerManager = $this->container->create(LoggerManager::class);
		$loggerManager->changeLogChannel($logChannel);
	}

	private function setupLegacyServiceLocator(): void
	{
		if ($this->container instanceof DiceContainer) {
			DI::init($this->container->getDice());
		}
	}

	private function registerErrorHandler(): void
	{
		ErrorHandler::register($this->container->create(LoggerInterface::class));
	}

	private function registerEventDispatcher(): void
	{
		/** @var \Friendica\Event\EventDispatcher */
		$eventDispatcher = $this->container->create(EventDispatcherInterface::class);

		foreach (HookEventBridge::getStaticSubscribedEvents() as $eventName => $methodName) {
			$eventDispatcher->addListener($eventName, [HookEventBridge::class, $methodName]);
		}
	}

	private function registerTemplateEngine(): void
	{
		Renderer::registerTemplateEngine('Friendica\Render\FriendicaSmartyEngine');
	}

	/**
	 * Load the whole app instance
	 */
	private function load(
		array $serverParams,
		DbaDefinition $dbaDefinition,
		ViewDefinition $viewDefinition,
		Mode $mode,
		IManageConfigValues $config,
		Profiler $profiler,
		EventDispatcherInterface $eventDispatcher,
		AppHelper $appHelper
	): void {
		if ($config->get('system', 'ini_max_execution_time') !== false) {
			set_time_limit((int) $config->get('system', 'ini_max_execution_time'));
		}

		if ($config->get('system', 'ini_pcre_backtrack_limit') !== false) {
			ini_set('pcre.backtrack_limit', (int) $config->get('system', 'ini_pcre_backtrack_limit'));
		}

		// Normally this constant is defined - but not if "pcntl" isn't installed
		if (!defined('SIGTERM')) {
			define('SIGTERM', 15);
		}

		// Ensure that all "strtotime" operations do run timezone independent
		date_default_timezone_set('UTC');

		$profiler->reset();

		if ($mode->has(Mode::DBAVAILABLE)) {
			Core\Hook::loadHooks();
			$loader = (new Config())->createConfigFileManager($appHelper->getBasePath(), $serverParams);

			$eventDispatcher->dispatch(new ConfigLoadedEvent(ConfigLoadedEvent::CONFIG_LOADED, $loader));

			// Hooks are now working, reload the whole definitions with hook enabled
			$dbaDefinition->load(true);
			$viewDefinition->load(true);
		}

		$this->loadDefaultTimezone($config, $appHelper);
	}

	/**
	 * Loads the default timezone
	 *
	 * Include support for legacy $default_timezone
	 *
	 * @global string $default_timezone
	 */
	private function loadDefaultTimezone(IManageConfigValues $config, AppHelper $appHelper)
	{
		if ($config->get('system', 'default_timezone')) {
			$timezone = $config->get('system', 'default_timezone', 'UTC');
		} else {
			global $default_timezone;
			$timezone = $default_timezone ?? '' ?: 'UTC';
		}

		$appHelper->setTimeZone($timezone);
	}

	/**
	 * Frontend App script
	 *
	 * The App object behaves like a container and a dispatcher at the same time, including a representation of the
	 * request and a representation of the response.
	 *
	 * This probably should change to limit the size of this monster method.
	 *
	 * @param IManagePersonalConfigValues $pconfig
	 * @param Page                        $page       The Friendica page printing container
	 * @param ModuleHTTPException         $httpException The possible HTTP Exception container
	 * @param float                       $start_time The start time of the overall script execution
	 *
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private function runFrontend(
		EventDispatcherInterface $eventDispatcher,
		IManagePersonalConfigValues $pconfig,
		Page $page,
		Nav $nav,
		ModuleHTTPException $httpException,
		float $start_time,
		ServerRequestInterface $request
	) {
		$this->mode->setExecutor(Mode::INDEX);

		$httpInput  = new HTTPInputData($request->getServerParams());
		$serverVars = $request->getServerParams();
		$queryVars  = $request->getQueryParams();

		$requeststring = ($serverVars['REQUEST_METHOD'] ?? '') . ' ' . ($serverVars['REQUEST_URI'] ?? '') . ' ' . ($serverVars['SERVER_PROTOCOL'] ?? '');
		$this->logger->debug('Request received', ['address' => $serverVars['REMOTE_ADDR'] ?? '', 'request' => $requeststring, 'referer' => $serverVars['HTTP_REFERER'] ?? '', 'user-agent' => $serverVars['HTTP_USER_AGENT'] ?? '']);
		$request_start = microtime(true);
		$request       = $_REQUEST;

		$this->profiler->set($start_time, 'start');
		$this->profiler->set(microtime(true), 'classinit');

		$moduleName = $this->args->getModuleName();
		$page->setLogging($this->args->getMethod(), $this->args->getModuleName(), $this->args->getCommand());

		try {
			// Missing DB connection: ERROR
			if ($this->mode->has(Mode::LOCALCONFIGPRESENT) && !$this->mode->has(Mode::DBAVAILABLE)) {
				throw new HTTPException\InternalServerErrorException($this->l10n->t('Apologies but the website is unavailable at the moment.'));
			}

			if (!$this->mode->isInstall()) {
				// Force SSL redirection
				if ($this->config->get('system', 'force_ssl') &&
					(empty($serverVars['HTTPS']) || $serverVars['HTTPS'] === 'off') &&
					(empty($serverVars['HTTP_X_FORWARDED_PROTO']) || $serverVars['HTTP_X_FORWARDED_PROTO'] === 'http') &&
					!empty($serverVars['REQUEST_METHOD']) &&
					$serverVars['REQUEST_METHOD'] === 'GET') {
					System::externalRedirect($this->baseURL . '/' . $this->args->getQueryString());
				}

				$eventDispatcher->dispatch(new Event(Event::INIT));
			}

			DID::routeRequest($this->args->getCommand(), $serverVars);

			if ($this->mode->isNormal() && !$this->mode->isBackend()) {
				$requester = HTTPSignature::getSigner('', $serverVars);
				if (!empty($requester)) {
					OpenWebAuth::addVisitorCookieForHandle($requester);
				}
			}

			// ZRL
			if (!empty($queryVars['zrl']) && $this->mode->isNormal() && !$this->mode->isBackend() && !$this->session->getLocalUserId()) {
				// Only continue when the given profile link seems valid.
				// Valid profile links contain a path with "/profile/" and no query parameters
				if ((parse_url($queryVars['zrl'], PHP_URL_QUERY) == '') &&
					strpos(parse_url($queryVars['zrl'], PHP_URL_PATH) ?? '', '/profile/') !== false) {
					$this->auth->setUnauthenticatedVisitor($queryVars['zrl']);
					OpenWebAuth::zrlInit();
				} else {
					// Someone came with an invalid parameter, maybe as a DDoS attempt
					// We simply stop processing here
					$this->logger->debug('Invalid ZRL parameter.', ['zrl' => $queryVars['zrl']]);
					throw new HTTPException\ForbiddenException();
				}
			}

			if (!empty($queryVars['owt']) && $this->mode->isNormal()) {
				$token = $queryVars['owt'];
				OpenWebAuth::init($token);
			}

			if (!$this->mode->isBackend()) {
				$this->auth->withSession();
			}

			if ($this->session->isUnauthenticated()) {
				header('X-Account-Management-Status: none');
			}

			/*
			 * check_config() is responsible for running update scripts. These automatically
			 * update the DB schema whenever we push a new one out. It also checks to see if
			 * any addons have been added or removed and reacts accordingly.
			 */

			// in install mode, any url loads install module
			// but we need "view" module for stylesheet
			if ($this->mode->isInstall() && $moduleName !== 'install') {
				$this->baseURL->redirect('install');
			} else {
				Core\Update::check($this->appHelper->getBasePath(), false);
				Core\Addon::loadAddons();
				Core\Hook::loadHooks();
			}

			// Compatibility with Hubzilla
			if ($moduleName == 'rpost') {
				$this->baseURL->redirect('compose');
			}

			// Compatibility with the Android Diaspora client
			if ($moduleName == 'stream') {
				$this->baseURL->redirect('network?order=post');
			}

			if ($moduleName == 'conversations') {
				$this->baseURL->redirect('message');
			}

			if ($moduleName == 'commented') {
				$this->baseURL->redirect('network?order=comment');
			}

			if ($moduleName == 'liked') {
				$this->baseURL->redirect('network?order=comment');
			}

			if ($moduleName == 'activity') {
				$this->baseURL->redirect('network?conv=1');
			}

			if (($moduleName == 'status_messages') && ($this->args->getCommand() == 'status_messages/new')) {
				$this->baseURL->redirect('bookmarklet');
			}

			if (($moduleName == 'user') && ($this->args->getCommand() == 'user/edit')) {
				$this->baseURL->redirect('settings');
			}

			if (($moduleName == 'tag_followings') && ($this->args->getCommand() == 'tag_followings/manage')) {
				$this->baseURL->redirect('search');
			}

			// Initialize module that can set the current theme in the init() method, either directly or via App->setProfileOwner
			$page['page_title'] = $moduleName;

			// The "view" module is required to show the theme CSS
			if (!$this->mode->isInstall() && !$this->mode->has(Mode::MAINTENANCEDISABLED) && $moduleName !== 'view') {
				$module = $this->createModuleInstance(Maintenance::class);
			} else {
				// determine the module class and save it to the module instance
				// @todo there's an implicit dependency due SESSION::start(), so it has to be called here (yet)
				$module = $this->createModuleInstance(null);
			}

			// Display can change depending on the requested language, so it shouldn't be cached whole
			header('Vary: Accept-Language', false);

			// Processes data from GET requests
			$httpinput = $httpInput->process();
			$input     = array_merge($httpinput['variables'], $httpinput['files'], $request);

			// Let the module run its internal process (init, get, post, ...)
			$timestamp = microtime(true);
			$response  = $module->run($httpException, $input);
			$this->profiler->set(microtime(true) - $timestamp, 'content');

			// Wrapping HTML responses in the theme template
			if ($response->getHeaderLine(ICanCreateResponses::X_HEADER) === ICanCreateResponses::TYPE_HTML) {
				$response = $page->run($this->appHelper, $this->session, $this->baseURL, $this->args, $this->mode, $response, $this->l10n, $this->profiler, $this->config, $pconfig, $nav, $this->session->getLocalUserId());
			}

			$this->logger->debug('Request processed sucessfully', ['response' => $response->getStatusCode(), 'address' => $serverVars['REMOTE_ADDR'] ?? '', 'request' => $requeststring, 'referer' => $serverVars['HTTP_REFERER'] ?? '', 'user-agent' => $serverVars['HTTP_USER_AGENT'] ?? '', 'duration' => number_format(microtime(true) - $request_start, 3)]);
			$this->logSlowCalls(microtime(true) - $request_start, $response->getStatusCode(), $requeststring, $serverVars['HTTP_USER_AGENT'] ?? '');
			System::echoResponse($response);
		} catch (HTTPException $e) {
			$this->logger->debug('Request processed with exception', ['response' => $e->getCode(), 'address' => $serverVars['REMOTE_ADDR'] ?? '', 'request' => $requeststring, 'referer' => $serverVars['HTTP_REFERER'] ?? '', 'user-agent' => $serverVars['HTTP_USER_AGENT'] ?? '', 'duration' => number_format(microtime(true) - $request_start, 3)]);
			$this->logSlowCalls(microtime(true) - $request_start, $e->getCode(), $requeststring, $serverVars['HTTP_USER_AGENT'] ?? '');
			$httpException->rawContent($e);
		}
		$page->logRuntime($this->config, 'runFrontend');
	}

	private function createModuleInstance(?string $moduleClass = null): ICanHandleRequests
	{
		/** @var Router $router */
		$router = $this->container->create(Router::class);

		$moduleClass = $moduleClass ?? $router->getModuleClass();
		$parameters  = $router->getParameters();

		$dice_profiler_threshold = $this->config->get('system', 'dice_profiler_threshold', 0);

		$stamp = microtime(true);

		/** @var ICanHandleRequests $module */
		$module = $this->container->create($moduleClass, $parameters);

		if ($dice_profiler_threshold > 0) {
			$dur = floatval(microtime(true) - $stamp);
			if ($dur >= $dice_profiler_threshold) {
				$this->logger->notice('Dice module creation lasts too long.', ['duration' => round($dur, 3), 'module' => $moduleClass, 'parameters' => $parameters]);
			}
		}

		return $module;
	}

	/**
	 * Log slow page executions
	 *
	 * @param float $duration
	 * @param integer $code
	 * @param string $request
	 * @param string $agent
	 * @return void
	 */
	private function logSlowCalls(float $duration, int $code, string $request, string $agent)
	{
		$logfile  = $this->config->get('system', 'page_execution_logfile');
		$loglimit = $this->config->get('system', 'page_execution_log_limit');
		if (empty($logfile) || empty($loglimit) || ($duration < $loglimit)) {
			return;
		}

		@file_put_contents(
			$logfile,
			DateTimeFormat::utcNow() . "\t" . round($duration, 3) . "\t" .
			$this->requestId . "\t" . $code . "\t" .
			$request . "\t" . $agent . "\n",
			FILE_APPEND
		);
	}
}
