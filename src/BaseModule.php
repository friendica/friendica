<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica;

use Friendica\App\Router;
use Friendica\Capabilities\ICanHandleRequests;
use Friendica\Capabilities\ICanCreateResponses;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\System;
use Friendica\Model\User;
use Friendica\Module\Response;
use Friendica\Module\Special\HTTPException as ModuleHTTPException;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use Psr\Http\Message\ResponseInterface;
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
abstract class BaseModule implements ICanHandleRequests
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
	/** @var ICanCreateResponses */
	protected $response;

	public function __construct(L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
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
	protected function delete(array $request = [])
	{
	}

	/**
	 * Module PATCH method to process submitted data
	 *
	 * Extend this method if the module is supposed to process PATCH requests.
	 * Doesn't display any content
	 *
	 * @param string[] $request The $_REQUEST content
	 * @return void
	 */
	protected function patch(array $request = [])
	{
	}

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
	protected function put(array $request = [])
	{
	}

	/**
	 * Module GET method to process submitted data
	 *
	 * Extend this method if the module is supposed to process GET requests.
	 * Doesn't display any content
	 *
	 * @param string[] $request The $_REQUEST content
	 * @return void
	 */
	protected function get(array $request = [])
	{
	}

	/**
	 * {@inheritDoc}
	 */
	public function run(ModuleHTTPException $httpException, array $request = []): ResponseInterface
	{
		// @see https://github.com/tootsuite/mastodon/blob/c3aef491d66aec743a3a53e934a494f653745b61/config/initializers/cors.rb
		if (substr($this->args->getQueryString(), 0, 12) == '.well-known/') {
			$this->response->setHeader('*', 'Access-Control-Allow-Origin');
			$this->response->setHeader('*', 'Access-Control-Allow-Headers');
			$this->response->setHeader(Router::GET, 'Access-Control-Allow-Methods');
			$this->response->setHeader('false', 'Access-Control-Allow-Credentials');
		} elseif (substr($this->args->getQueryString(), 0, 9) == 'nodeinfo/') {
			$this->response->setHeader('*', 'Access-Control-Allow-Origin');
			$this->response->setHeader('*', 'Access-Control-Allow-Headers');
			$this->response->setHeader(Router::GET, 'Access-Control-Allow-Methods');
			$this->response->setHeader('false', 'Access-Control-Allow-Credentials');
		} elseif (substr($this->args->getQueryString(), 0, 8) == 'profile/') {
			$this->response->setHeader('*', 'Access-Control-Allow-Origin');
			$this->response->setHeader('*', 'Access-Control-Allow-Headers');
			$this->response->setHeader(Router::GET, 'Access-Control-Allow-Methods');
			$this->response->setHeader('false', 'Access-Control-Allow-Credentials');
		} elseif (substr($this->args->getQueryString(), 0, 4) == 'api/') {
			$this->response->setHeader('*', 'Access-Control-Allow-Origin');
			$this->response->setHeader('*', 'Access-Control-Allow-Headers');
			$this->response->setHeader(implode(',', Router::ALLOWED_METHODS), 'Access-Control-Allow-Methods');
			$this->response->setHeader('false', 'Access-Control-Allow-Credentials');
			$this->response->setHeader('Link', 'Access-Control-Expose-Headers');
		} elseif (substr($this->args->getQueryString(), 0, 11) == 'oauth/token') {
			$this->response->setHeader('*', 'Access-Control-Allow-Origin');
			$this->response->setHeader('*', 'Access-Control-Allow-Headers');
			$this->response->setHeader(Router::POST, 'Access-Control-Allow-Methods');
			$this->response->setHeader('false', 'Access-Control-Allow-Credentials');
		}

		$placeholder = '';

		$this->profiler->set(microtime(true), 'ready');
		$timestamp = microtime(true);

		Core\Hook::callAll($this->args->getModuleName() . '_mod_init', $placeholder);

		$this->profiler->set(microtime(true) - $timestamp, 'init');

		switch ($this->args->getMethod()) {
			case Router::DELETE:
				$this->delete($request);
				break;
			case Router::PATCH:
				$this->patch($request);
				break;
			case Router::POST:
				Core\Hook::callAll($this->args->getModuleName() . '_mod_post', $request);
				$this->post($request);
				break;
			case Router::PUT:
				$this->put($request);
				break;
			case Router::GET:
				$this->get($request);
				break;
		}

		$timestamp = microtime(true);
		// "rawContent" is especially meant for technical endpoints.
		// This endpoint doesn't need any theme initialization or
		// templating and is expected to exit on its own if it is set.
		$this->rawContent($request);

		try {
			$arr = ['content' => ''];
			Hook::callAll(static::class . '_mod_content', $arr);
			$this->response->addContent($arr['content']);
			$this->response->addContent($this->content($request));
		} catch (HTTPException $e) {
			// In case of System::externalRedirects(), we don't want to prettyprint the exception
			// just redirect to the new location
			if (($e instanceof HTTPException\FoundException) ||
				($e instanceof HTTPException\MovedPermanentlyException) ||
				($e instanceof HTTPException\TemporaryRedirectException)) {
				throw $e;
			}

			$this->response->setStatus($e->getCode(), $e->getMessage());
			$this->response->addContent($httpException->content($e));
		}

		$this->profiler->set(microtime(true) - $timestamp, 'content');

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
			$value = (string)($input[$parameter] ?? $default);
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
			$value = filter_var($input[$parameter] ?? $default, FILTER_DEFAULT, ['flags' => FILTER_FORCE_ARRAY]);
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
	 * Functions used to protect against Cross-Site Request Forgery
	 * The security token has to base on at least one value that an attacker can't know - here it's the session ID and the private key.
	 * In this implementation, a security token is reusable (if the user submits a form, goes back and resubmits the form, maybe with small changes;
	 * or if the security token is used for ajax-calls that happen several times), but only valid for a certain amount of time (3hours).
	 * The "typename" separates the security tokens of different types of forms. This could be relevant in the following case:
	 *    A security token is used to protect a link from CSRF (e.g. the "delete this profile"-link).
	 *    If the new page contains by any chance external elements, then the used security token is exposed by the referrer.
	 *    Actually, important actions should not be triggered by Links / GET-Requests at all, but sometimes they still are,
	 *    so this mechanism brings in some damage control (the attacker would be able to forge a request to a form of this type, but not to forms of other types).
	 *
	 * @param string $typename Type name
	 * @return string Security hash with timestamp
	 */
	public static function getFormSecurityToken(string $typename = ''): string
	{
		$user      = User::getById(DI::userSession()->getLocalUserId(), ['guid', 'prvkey']);
		$timestamp = time();
		$sec_hash  = hash('whirlpool', ($user['guid'] ?? '') . ($user['prvkey'] ?? '') . session_id() . $timestamp . $typename);

		return $timestamp . '.' . $sec_hash;
	}

	/**
	 * Checks if form's security (CSRF) token is valid.
	 *
	 * @param string $typename ???
	 * @param string $formname Name of form/field (???)
	 * @return bool Whether it is valid
	 */
	public static function checkFormSecurityToken(string $typename = '', string $formname = 'form_security_token'): bool
	{
		$hash = null;

		if (!empty($_REQUEST[$formname])) {
			/// @TODO Careful, not secured!
			$hash = $_REQUEST[$formname];
		}

		if (!empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
			/// @TODO Careful, not secured!
			$hash = $_SERVER['HTTP_X_CSRF_TOKEN'];
		}

		if (empty($hash)) {
			return false;
		}

		$max_livetime = 10800; // 3 hours

		$user = User::getById(DI::userSession()->getLocalUserId(), ['guid', 'prvkey']);

		$x = explode('.', $hash);
		if (time() > (intval($x[0]) + $max_livetime)) {
			return false;
		}

		$sec_hash = hash('whirlpool', ($user['guid'] ?? '') . ($user['prvkey'] ?? '') . session_id() . $x[0] . $typename);

		return ($sec_hash == $x[1]);
	}

	public static function getFormSecurityStandardErrorMessage(): string
	{
		return DI::l10n()->t("The form security token was not correct. This probably happened because the form has been opened for too long \x28>3 hours\x29 before submitting it.");
	}

	public static function checkFormSecurityTokenRedirectOnError(string $err_redirect, string $typename = '', string $formname = 'form_security_token')
	{
		if (!self::checkFormSecurityToken($typename, $formname)) {
			DI::logger()->notice('checkFormSecurityToken failed: user ' . DI::userSession()->getLocalUserNickname() . ' - form element ' . $typename);
			DI::logger()->debug('checkFormSecurityToken failed', ['request' => $_REQUEST]);
			DI::sysmsg()->addNotice(self::getFormSecurityStandardErrorMessage());
			DI::baseUrl()->redirect($err_redirect);
		}
	}

	public static function checkFormSecurityTokenForbiddenOnError(string $typename = '', string $formname = 'form_security_token')
	{
		if (!self::checkFormSecurityToken($typename, $formname)) {
			DI::logger()->notice('checkFormSecurityToken failed: user ' . DI::userSession()->getLocalUserNickname() . ' - form element ' . $typename);
			DI::logger()->debug('checkFormSecurityToken failed', ['request' => $_REQUEST]);

			throw new \Friendica\Network\HTTPException\ForbiddenException();
		}
	}

	protected static function getContactFilterTabs(string $baseUrl, string $current, bool $displayCommonTab): array
	{
		$tabs = [
			[
				'label' => DI::l10n()->t('All contacts'),
				'url'   => $baseUrl . '/contacts',
				'sel'   => !$current || $current == 'all' ? 'active' : '',
			],
			[
				'label' => DI::l10n()->t('Followers'),
				'url'   => $baseUrl . '/contacts/followers',
				'sel'   => $current == 'followers' ? 'active' : '',
			],
			[
				'label' => DI::l10n()->t('Following'),
				'url'   => $baseUrl . '/contacts/following',
				'sel'   => $current == 'following' ? 'active' : '',
			],
			[
				'label' => DI::l10n()->t('Mutual friends'),
				'url'   => $baseUrl . '/contacts/mutuals',
				'sel'   => $current == 'mutuals' ? 'active' : '',
			],
		];

		if ($displayCommonTab) {
			$tabs[] = [
				'label' => DI::l10n()->t('Common'),
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
	 * @param string      $content
	 * @param string      $type
	 * @param string|null $content_type
	 * @return never
	 * @throws HTTPException\InternalServerErrorException
	 */
	public function httpExit(string $content, string $type = Response::TYPE_HTML, ?string $content_type = null)
	{
		$this->response->setType($type, $content_type);
		$this->response->addContent($content);
		System::echoResponse($this->response->generate());

		System::exit();
	}

	/**
	 * Send HTTP status header and exit.
	 *
	 * @param integer $httpCode HTTP status result value
	 * @param string  $message  Error message. Optional.
	 * @param mixed  $content   Response body. Optional.
	 * @throws \Exception
	 */
	public function httpError(int $httpCode, string $message = '', $content = '')
	{
		if ($httpCode >= 400) {
			$this->logger->debug('Exit with error', ['code' => $httpCode, 'message' => $message, 'method' => $this->args->getMethod(), 'agent' => $this->server['HTTP_USER_AGENT'] ?? '']);
		}

		$this->response->setStatus($httpCode, $message);

		$this->httpExit($content);
	}

	/**
	 * Display the response using JSON to encode the content
	 *
	 * @param mixed  $content
	 * @param string $content_type
	 * @param int    $options A combination of json_encode() binary flags
	 * @return never
	 * @throws HTTPException\InternalServerErrorException
	 * @see json_encode()
	 */
	public function jsonExit($content, string $content_type = 'application/json', int $options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
	{
		$this->httpExit(json_encode($content, $options), ICanCreateResponses::TYPE_JSON, $content_type);
	}

	/**
	 * Display a non-200 HTTP code response using JSON to encode the content and exit
	 *
	 * @param int    $httpCode
	 * @param mixed  $content
	 * @param string $content_type
	 * @return never
	 * @throws HTTPException\InternalServerErrorException
	 */
	public function jsonError(int $httpCode, $content, string $content_type = 'application/json')
	{
		if ($httpCode >= 400) {
			$this->logger->debug('Exit with error', ['code' => $httpCode, 'content_type' => $content_type, 'method' => $this->args->getMethod(), 'agent' => $this->server['HTTP_USER_AGENT'] ?? '']);
		}

		$this->response->setStatus($httpCode);
		$this->jsonExit($content, $content_type);
	}
}
