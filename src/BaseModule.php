<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica;

use Friendica\App\Router;
use Friendica\Capabilities\ICanHandleRequests;
use Friendica\Capabilities\ICanCreateResponses;
use Friendica\Capabilities\IRequestHandler;
use Friendica\Core\EarlyExitException;
use Friendica\Core\L10n;
use Friendica\Core\System;
use Friendica\Event\LegacyEventDispatcher;
use Friendica\Event\ModuleContentEvent;
use Friendica\Event\ModuleInitEvent;
use Friendica\Event\ModulePostEvent;
use Friendica\Module\Response;
use Friendica\Module\Special\HTTPException as ModuleHTTPException;
use Friendica\Network\HTTPException;
use Friendica\Security\LegacyFormSecurityToken;
use Friendica\Util\HTTPInputData;
use Friendica\Util\Profiler;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * All modules in Friendica should extend BaseModule, although not all modules
 * need to extend all the methods described here
 *
 * The filename of the module in src/Module needs to match the class name
 * exactly to make the module available.
 *
 * @author Hypolite Petovan <hypolite@mrpetovan.com>
 */
abstract class BaseModule implements ICanHandleRequests, IRequestHandler
{
	/** @var array */
	protected $parameters = [];
	/** @var L10n */
	protected $l10n;
	/** @var App\BaseURL */
	protected $baseUrl;
	/** @var App\Arguments */
	protected $args;
	/** @var LoggerInterface */
	protected $logger;
	/** @var Profiler */
	protected $profiler;
	/** @var array */
	protected $server;
	/** @var Response */
	protected $response;

	public function __construct(
		L10n $l10n,
		App\BaseURL $baseUrl,
		App\Arguments $args,
		LoggerInterface $logger,
		Profiler $profiler,
		Response $response,
		array $server,
		array $parameters = [],
		private ?EventDispatcherInterface $eventDispatcher = null,
	) {
		$this->parameters = $parameters;
		$this->l10n       = $l10n;
		$this->baseUrl    = $baseUrl;
		$this->args       = $args;
		$this->logger     = $logger;
		$this->profiler   = $profiler;
		$this->server     = $server;
		$this->response   = $response;
	}

	/**
	 * Sets the event dispatcher for modules whose constructor doesn't pass it on.
	 *
	 * The DI container calls this for every module (see static/dependencies.config.php), a dispatcher from the constructor is kept.
	 */
	public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): void
	{
		$this->eventDispatcher ??= $eventDispatcher;
	}

	private function getEventDispatcher(): EventDispatcherInterface
	{
		// Modules created with new instead of the DI container, e.g. in tests
		return $this->eventDispatcher ??= LegacyEventDispatcher::get();
	}

	/**
	 * Wraps the L10n::t() function for Modules
	 *
	 * @see L10n::t()
	 */
	protected function t(string $s, ...$args): string
	{
		return $this->l10n->t($s, ...$args);
	}

	/**
	 * Wraps the L10n::tt() function for Modules
	 *
	 * @see L10n::tt()
	 */
	protected function tt(string $singular, string $plural, int $count): string
	{
		return $this->l10n->tt($singular, $plural, $count);
	}

	/**
	 * Module GET method to display raw content from technical endpoints
	 *
	 * Extend this method if the module is supposed to return communication data,
	 * e.g. from protocol implementations.
	 *
	 * @param string[] $request The $_REQUEST content
	 * @return void
	 */
	protected function rawContent(array $request = [])
	{
		// $this->httpExit(...);
	}

	/**
	 * Module GET method to display any content
	 *
	 * Extend this method if the module is supposed to return any display
	 * through a GET request. It can be an HTML page through templating or a
	 * XML feed or a JSON output.
	 *
	 * @param string[] $request The $_REQUEST content
	 * @return string
	 */
	protected function content(array $request = []): string
	{
		return '';
	}

	/**
	 * Module DELETE method to process submitted data
	 *
	 * Extend this method if the module is supposed to process DELETE requests.
	 * Doesn't display any content
	 *
	 * @param string[] $request The $_REQUEST content
	 * @return void
	 */
	protected function delete(array $request = []) {}

	/**
	 * Module PATCH method to process submitted data
	 *
	 * Extend this method if the module is supposed to process PATCH requests.
	 * Doesn't display any content
	 *
	 * @param string[] $request The $_REQUEST content
	 * @return void
	 */
	protected function patch(array $request = []) {}

	/**
	 * Module POST method to process submitted data
	 *
	 * Extend this method if the module is supposed to process POST requests.
	 * Doesn't display any content
	 *
	 * @param string[] $request The $_REQUEST content
	 * @return void
	 */
	protected function post(array $request = [])
	{
		// $this->baseUrl->redirect('module');
	}

	/**
	 * Module PUT method to process submitted data
	 *
	 * Extend this method if the module is supposed to process PUT requests.
	 * Doesn't display any content
	 *
	 * @param string[] $request The $_REQUEST content
	 * @return void
	 */
	protected function put(array $request = []) {}

	/**
	 * Module GET method to process submitted data
	 *
	 * Extend this method if the module is supposed to process GET requests.
	 * Doesn't display any content
	 *
	 * @param string[] $request The $_REQUEST content
	 * @return void
	 */
	protected function get(array $request = []) {}

	/**
	 * Module HEAD method, only called for routes registered with HEAD
	 *
	 * Must lead to the same status and headers as GET. content() is skipped for HEAD,
	 * so status-relevant checks belong here. Routes without HEAD handle HEAD as GET.
	 *
	 * @param string[] $request The $_REQUEST content
	 * @return void
	 */
	protected function head(array $request = []) {}

	/**
	 * @internal Used by App::runFrontend() to set error responses on the module's response instance
	 */
	public function getResponseBuilder(): ICanCreateResponses
	{
		return $this->response;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @deprecated 2026.08 Use {@see IRequestHandler::handleRequest()} instead
	 */
	public function run(ModuleHTTPException $httpException, array $request = []): ResponseInterface
	{
		try {
			$this->dispatch($request, $httpException);
		} catch (EarlyExitException $e) {
			System::echoResponse($e->getResponse());
			System::exit();
		}

		return $this->response->generate();
	}

	/**
	 * Hook for modules to perform scope or permission checks before dispatch
	 *
	 * @internal
	 *
	 * @throws HTTPException
	 */
	protected function checkScope(): void {}

	/**
	 * Dispatches the module CORS headers, events and method handling
	 *
	 * @param array                              $request The request data
	 * @param ModuleHTTPException|null $httpException Optional exception renderer for the content phase
	 * @return void
	 * @throws HTTPException
	 * @throws EarlyExitException
	 */
	final protected function dispatch(array $request, ?ModuleHTTPException $httpException = null): void
	{
		$this->checkScope();
		// @see https://github.com/tootsuite/mastodon/blob/c3aef491d66aec743a3a53e934a494f653745b61/config/initializers/cors.rb
		if (str_starts_with($this->args->getQueryString(), '.well-known/')) {
			$this->response->setHeader('*', 'Access-Control-Allow-Origin');
			$this->response->setHeader('*', 'Access-Control-Allow-Headers');
			$this->response->setHeader(Router::GET, 'Access-Control-Allow-Methods');
			$this->response->setHeader('false', 'Access-Control-Allow-Credentials');
		} elseif (str_starts_with($this->args->getQueryString(), 'nodeinfo/')) {
			$this->response->setHeader('*', 'Access-Control-Allow-Origin');
			$this->response->setHeader('*', 'Access-Control-Allow-Headers');
			$this->response->setHeader(Router::GET, 'Access-Control-Allow-Methods');
			$this->response->setHeader('false', 'Access-Control-Allow-Credentials');
		} elseif (str_starts_with($this->args->getQueryString(), 'profile/')) {
			$this->response->setHeader('*', 'Access-Control-Allow-Origin');
			$this->response->setHeader('*', 'Access-Control-Allow-Headers');
			$this->response->setHeader(Router::GET, 'Access-Control-Allow-Methods');
			$this->response->setHeader('false', 'Access-Control-Allow-Credentials');
		} elseif (str_starts_with($this->args->getQueryString(), 'api/')) {
			$this->response->setHeader('*', 'Access-Control-Allow-Origin');
			$this->response->setHeader('*', 'Access-Control-Allow-Headers');
			$this->response->setHeader(implode(',', Router::ALLOWED_METHODS), 'Access-Control-Allow-Methods');
			$this->response->setHeader('false', 'Access-Control-Allow-Credentials');
			$this->response->setHeader('Link', 'Access-Control-Expose-Headers');
		} elseif (str_starts_with($this->args->getQueryString(), 'oauth/token')) {
			$this->response->setHeader('*', 'Access-Control-Allow-Origin');
			$this->response->setHeader('*', 'Access-Control-Allow-Headers');
			$this->response->setHeader(Router::POST, 'Access-Control-Allow-Methods');
			$this->response->setHeader('false', 'Access-Control-Allow-Credentials');
		}

		$this->profiler->set(microtime(true), 'ready');
		$timestamp = microtime(true);

		$this->getEventDispatcher()->dispatch(
			new ModuleInitEvent(ModuleInitEvent::MODULE_INIT, $this->args->getModuleName(), static::class),
		);

		$this->profiler->set(microtime(true) - $timestamp, 'init');

		switch ($this->args->getMethod()) {
			case Router::DELETE:
				$this->delete($request);
				break;
			case Router::PATCH:
				$this->patch($request);
				break;
			case Router::POST:
				$request = $this->getEventDispatcher()->dispatch(
					new ModulePostEvent(ModulePostEvent::MODULE_POST, $this->args->getModuleName(), static::class, $request),
				)->getPost();

				$this->post($request);
				break;
			case Router::PUT:
				$this->put($request);
				break;
			case Router::GET:
				$this->get($request);
				break;
			case Router::HEAD:
				$this->head($request);
				break;
		}

		$timestamp = microtime(true);
		// "rawContent" is especially meant for technical endpoints.
		// This endpoint doesn't need any theme initialization or
		// templating and is expected to exit on its own if it is set.
		$this->rawContent($request);

		// The body of a HEAD response is discarded.
		if ($this->args->getMethod() === Router::HEAD) {
			$this->profiler->set(microtime(true) - $timestamp, 'content');
			return;
		}

		try {
			$content = $this->getEventDispatcher()->dispatch(
				new ModuleContentEvent(ModuleContentEvent::MODULE_CONTENT, $this->args->getModuleName(), static::class, ''),
			)->getContent();
			$this->response->addContent($content);
			$this->response->addContent($this->content($request));
		} catch (HTTPException $e) {
			if ($e instanceof HTTPException\FoundException
				|| $e instanceof HTTPException\MovedPermanentlyException
				|| $e instanceof HTTPException\TemporaryRedirectException
			) {
				throw $e;
			}

			if ($httpException) {
				$this->response->setStatus($e->getCode(), $e->getMessage());
				$this->response->addContent($httpException->content($e));
			} else {
				throw $e;
			}
		}

		$this->profiler->set(microtime(true) - $timestamp, 'content');
	}

	/**
	 * @throws HTTPException
	 */
	public function handleRequest(ServerRequestInterface $request): ResponseInterface
	{
		$httpInput  = new HTTPInputData($request->getServerParams());
		$httpinput  = $httpInput->process();
		$queryVars  = $request->getQueryParams();
		$parsedBody = $request->getParsedBody();

		$requestArray = array_merge(
			$httpinput['variables'] ?? [],
			$httpinput['files']     ?? [],
			$queryVars,
			is_array($parsedBody) ? $parsedBody : [],
		);

		$this->dispatch($requestArray);
		return $this->response->generate();
	}

	/**
	 * Checks request inputs and sets default parameters
	 *
	 * @param array $defaults Associative array of expected request keys and their default typed value. A null
	 *                        value will remove the request key from the resulting value array.
	 * @param array $input    Custom REQUEST array, superglobal instead
	 *
	 * @return array Request data
	 */
	protected function checkDefaults(array $defaults, array $input): array
	{
		$request = [];

		foreach ($defaults as $parameter => $defaultvalue) {
			$request[$parameter] = $this->getRequestValue($input, $parameter, $defaultvalue);
		}

		foreach ($input as $parameter => $value) {
			if ($parameter == 'pagename') {
				continue;
			}
			if (!in_array($parameter, array_keys($defaults))) {
				$this->logger->notice('Unhandled request field', ['parameter' => $parameter, 'value' => $value, 'command' => $this->args->getCommand()]);
			}
		}

		$this->logger->debug('Got request parameters', ['request' => $request, 'command' => $this->args->getCommand()]);
		return $request;
	}

	/**
	 * Fetch a request value and apply default values and check against minimal and maximal values
	 *
	 * @param array $input Input fields
	 * @param string $parameter Parameter
	 * @param mixed $default Default
	 * @param mixed $minimal_value Minimal value
	 * @param mixed $maximum_value Maximum value
	 * @return mixed null on error anything else on success (?)
	 */
	public function getRequestValue(array $input, string $parameter, $default = null, $minimal_value = null, $maximum_value = null)
	{
		if (is_string($default)) {
			$value = (string) ($input[$parameter] ?? $default);
		} elseif (is_int($default)) {
			$value = filter_var($input[$parameter] ?? $default, FILTER_VALIDATE_INT);
			if (!is_null($minimal_value)) {
				$value = max(filter_var($minimal_value, FILTER_VALIDATE_INT), $value);
			}
			if (!is_null($maximum_value)) {
				$value = min(filter_var($maximum_value, FILTER_VALIDATE_INT), $value);
			}
		} elseif (is_float($default)) {
			$value = filter_var($input[$parameter] ?? $default, FILTER_VALIDATE_FLOAT);
			if (!is_null($minimal_value)) {
				$value = max(filter_var($minimal_value, FILTER_VALIDATE_FLOAT), $value);
			}
			if (!is_null($maximum_value)) {
				$value = min(filter_var($maximum_value, FILTER_VALIDATE_FLOAT), $value);
			}
		} elseif (is_array($default)) {
			$value = filter_var($input[$parameter] ?? $default, FILTER_UNSAFE_RAW, ['flags' => FILTER_FORCE_ARRAY]);
		} elseif (is_bool($default)) {
			$value = filter_var($input[$parameter] ?? $default, FILTER_VALIDATE_BOOLEAN);
		} elseif (is_null($default)) {
			$value = $input[$parameter] ?? null;
		} else {
			$this->logger->notice('Unhandled default value type', ['parameter' => $parameter, 'type' => gettype($default)]);
			$value = null;
		}

		return $value;
	}

	/**
	 * Returns a security token that protects a form against Cross-Site Request Forgery.
	 *
	 * New code injects {@see \Friendica\Security\FormSecurityToken} instead.
	 *
	 * @param string $typename Type name
	 * @return string Security hash with timestamp
	 */
	public static function getFormSecurityToken(string $typename = ''): string
	{
		return LegacyFormSecurityToken::create($typename);
	}

	/**
	 * Checks if form's security (CSRF) token is valid.
	 *
	 * @param string $typename Type name the token was created for
	 * @param string $formname Name of the form field that carries the token
	 * @return bool Whether it is valid
	 */
	public static function checkFormSecurityToken(string $typename = '', string $formname = 'form_security_token'): bool
	{
		return LegacyFormSecurityToken::check($typename, $formname);
	}

	public static function getFormSecurityStandardErrorMessage(): string
	{
		return LegacyFormSecurityToken::getErrorMessage();
	}

	public static function checkFormSecurityTokenRedirectOnError(string $err_redirect, string $typename = '', string $formname = 'form_security_token')
	{
		LegacyFormSecurityToken::checkRedirectOnError($err_redirect, $typename, $formname);
	}

	public static function checkFormSecurityTokenForbiddenOnError(string $typename = '', string $formname = 'form_security_token')
	{
		LegacyFormSecurityToken::checkForbiddenOnError($typename, $formname);
	}

	protected function getContactFilterTabs(string $baseUrl, string $current, bool $displayCommonTab): array
	{
		$tabs = [
			[
				'label' => $this->t('All contacts'),
				'url'   => $baseUrl . '/contacts',
				'sel'   => !$current || $current == 'all' ? 'active' : '',
			],
			[
				'label' => $this->t('Followers'),
				'url'   => $baseUrl . '/contacts/followers',
				'sel'   => $current == 'followers' ? 'active' : '',
			],
			[
				'label' => $this->t('Following'),
				'url'   => $baseUrl . '/contacts/following',
				'sel'   => $current == 'following' ? 'active' : '',
			],
			[
				'label' => $this->t('Friends'),
				'url'   => $baseUrl . '/contacts/mutuals',
				'sel'   => $current == 'mutuals' ? 'active' : '',
			],
		];

		if ($displayCommonTab) {
			$tabs[] = [
				'label' => $this->t('Common'),
				'url'   => $baseUrl . '/contacts/common',
				'sel'   => $current == 'common' ? 'active' : '',
			];
		}

		return $tabs;
	}

	/**
	 * This function adds the content and a content-type HTTP header to the output.
	 * After finishing the process is getting killed.
	 *
	 * @deprecated 2026.08 Use {@see earlyHttpExit()} instead
	 *
	 * @param string      $content
	 * @param string      $type
	 * @param string|null $content_type
	 * @return never
	 * @throws HTTPException\InternalServerErrorException
	 */
	public function httpExit(string $content, string $type = Response::TYPE_HTML, ?string $content_type = null)
	{
		@trigger_error('Method `' . __METHOD__ . '` is deprecated since 2026.08, use `earlyHttpExit()` instead.', E_USER_DEPRECATED);
		$this->response->setType($type, $content_type);
		$this->response->addContent($content);
		System::echoResponse($this->response->generate());

		System::exit();
	}

	/**
	 * Send HTTP status header and exit.
	 *
	 * @deprecated 2026.08 Use {@see earlyHttpError()} instead
	 *
	 * @param integer $httpCode HTTP status result value
	 * @param string  $message  Error message. Optional.
	 * @param mixed  $content   Response body. Optional.
	 * @throws \Exception
	 */
	public function httpError(int $httpCode, string $message = '', $content = '')
	{
		@trigger_error('Method `' . __METHOD__ . '` is deprecated since 2026.08, use `earlyHttpError()` instead.', E_USER_DEPRECATED);
		if ($httpCode >= 400) {
			$this->logger->debug('Exit with error', ['code' => $httpCode, 'message' => $message, 'method' => $this->args->getMethod(), 'agent' => $this->server['HTTP_USER_AGENT'] ?? '']);
		}

		$this->response->setStatus($httpCode, $message);

		$this->httpExit($content);
	}

	/**
	 * Display the response using JSON to encode the content
	 *
	 * @deprecated 2026.08 Use {@see earlyJsonExit()} instead
	 *
	 * @param mixed  $content
	 * @param string $content_type
	 * @param int    $options A combination of json_encode() binary flags
	 * @return never
	 * @throws HTTPException\InternalServerErrorException
	 * @see json_encode()
	 */
	public function jsonExit($content, string $content_type = 'application/json; charset=utf-8', int $options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
	{
		@trigger_error('Method `' . __METHOD__ . '` is deprecated since 2026.08, use `earlyJsonExit()` instead.', E_USER_DEPRECATED);
		$this->httpExit(json_encode($content, $options), ICanCreateResponses::TYPE_JSON, $content_type);
	}

	/**
	 * Display a non-200 HTTP code response using JSON to encode the content and exit
	 *
	 * @deprecated 2026.08 Use {@see earlyJsonError()} instead
	 *
	 * @param int    $httpCode
	 * @param mixed  $content
	 * @param string $content_type
	 * @return never
	 * @throws HTTPException\InternalServerErrorException
	 */
	public function jsonError(int $httpCode, $content, string $content_type = 'application/json')
	{
		@trigger_error('Method `' . __METHOD__ . '` is deprecated since 2026.08, use `earlyJsonError()` instead.', E_USER_DEPRECATED);
		if ($httpCode >= 400) {
			$this->logger->debug('Exit with error', ['code' => $httpCode, 'content_type' => $content_type, 'method' => $this->args->getMethod(), 'agent' => $this->server['HTTP_USER_AGENT'] ?? '']);
		}

		$this->response->setStatus($httpCode);
		$this->jsonExit($content, $content_type);
	}

	/**
	 * Send content and a content-type HTTP header to the output and exit.
	 *
	 * @throws HTTPException\InternalServerErrorException
	 * @throws EarlyExitException
	 */
	protected function earlyHttpExit(string $content, string $type = Response::TYPE_HTML, ?string $contentType = null): never
	{
		$this->response->setType($type, $contentType);
		$this->response->addContent($content);

		throw new EarlyExitException($this->response->generate());
	}

	/**
	 * Send HTTP status header and exit.
	 *
	 * @param integer $httpCode HTTP status result value
	 * @param string  $message  Error message. Optional.
	 * @param mixed   $content  Response body. Optional.
	 * @throws HTTPException\InternalServerErrorException
	 * @throws EarlyExitException
	 */
	protected function earlyHttpError(int $httpCode, string $message = '', mixed $content = ''): never
	{
		if ($httpCode >= 400) {
			$this->logger->debug('Exit with error', ['code' => $httpCode, 'message' => $message, 'method' => $this->args->getMethod(), 'agent' => $this->server['HTTP_USER_AGENT'] ?? '']);
		}

		$this->response->setStatus($httpCode, $message);
		$this->earlyHttpExit($content);
	}

	/**
	 * Display the response using JSON to encode the content.
	 *
	 * @throws HTTPException\InternalServerErrorException
	 * @throws EarlyExitException
	 */
	protected function earlyJsonExit(mixed $content, string $contentType = 'application/json; charset=utf-8', int $options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT): never
	{
		$this->earlyHttpExit(json_encode($content, $options), ICanCreateResponses::TYPE_JSON, $contentType);
	}

	/**
	 * Display a non-200 HTTP code response using JSON to encode the content and exit.
	 *
	 * @throws HTTPException\InternalServerErrorException
	 * @throws EarlyExitException
	 */
	protected function earlyJsonError(int $httpCode, mixed $content, string $contentType = 'application/json'): never
	{
		if ($httpCode >= 400) {
			$this->logger->debug('Exit with error', ['code' => $httpCode, 'content_type' => $contentType, 'method' => $this->args->getMethod(), 'agent' => $this->server['HTTP_USER_AGENT'] ?? '']);
		}

		$this->response->setStatus($httpCode);
		$this->earlyJsonExit($content, $contentType);
	}
}
