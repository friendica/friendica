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

namespace Friendica;

use Friendica\App\Router;
use Friendica\Capabilities\ICanHandleRequests;
use Friendica\Capabilities\ICanCreateResponses;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\Logger;
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
	protected function tt(string $singular, string $plurarl, int $count): string
	{
		return $this->l10n->tt($singular, $plurarl, $count);
	}

	/**
	 * Module GET method to display raw content from technical endpoints
	 *
	 * Extend this method if the module is supposed to return communication data,
	 * e.g. from protocol implementations.
	 *
	 * @param string[] $request The $_REQUEST content
	 */
	protected function rawContent(array $request = [])
	{
		// echo '';
		// exit;
	}

	/**
	 * Module GET method to display any content
	 *
	 * Extend this method if the module is supposed to return any display
	 * through a GET request. It can be an HTML page through templating or a
	 * XML feed or a JSON output.
	 *
	 * @param string[] $request The $_REQUEST content
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
	 *
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
	 */
	protected function put(array $request = [])
	{
	}

	/**
	 * {@inheritDoc}
	 */
	public function run(array $request = []): ResponseInterface
	{
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
		}

		$timestamp = microtime(true);
		// "rawContent" is especially meant for technical endpoints.
		// This endpoint doesn't need any theme initialization or other comparable stuff.
		$this->rawContent($request);

		try {
			$arr = ['content' => ''];
			Hook::callAll(static::class . '_mod_content', $arr);
			$this->response->addContent($arr['content']);
			$this->response->addContent($this->content($request));
		} catch (HTTPException $e) {
			$this->response->addContent((new ModuleHTTPException())->content($e));
		} finally {
			$this->profiler->set(microtime(true) - $timestamp, 'content');
		}

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
			if (is_string($defaultvalue)) {
				$request[$parameter] = $input[$parameter] ?? $defaultvalue;
			} elseif (is_int($defaultvalue)) {
				$request[$parameter] = (int)($input[$parameter] ?? $defaultvalue);
			} elseif (is_float($defaultvalue)) {
				$request[$parameter] = (float)($input[$parameter] ?? $defaultvalue);
			} elseif (is_array($defaultvalue)) {
				$request[$parameter] = $input[$parameter] ?? [];
			} elseif (is_bool($defaultvalue)) {
				$request[$parameter] = in_array(strtolower($input[$parameter] ?? ''), ['true', '1']);
			} else {
				$this->logger->notice('Unhandled default value type', ['parameter' => $parameter, 'type' => gettype($defaultvalue)]);
			}
		}

		foreach ($input ?? [] as $parameter => $value) {
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

	/*
	 * Functions used to protect against Cross-Site Request Forgery
	 * The security token has to base on at least one value that an attacker can't know - here it's the session ID and the private key.
	 * In this implementation, a security token is reusable (if the user submits a form, goes back and resubmits the form, maybe with small changes;
	 * or if the security token is used for ajax-calls that happen several times), but only valid for a certain amount of time (3hours).
	 * The "typename" separates the security tokens of different types of forms. This could be relevant in the following case:
	 *    A security token is used to protect a link from CSRF (e.g. the "delete this profile"-link).
	 *    If the new page contains by any chance external elements, then the used security token is exposed by the referrer.
	 *    Actually, important actions should not be triggered by Links / GET-Requests at all, but sometimes they still are,
	 *    so this mechanism brings in some damage control (the attacker would be able to forge a request to a form of this type, but not to forms of other types).
	 */
	public static function getFormSecurityToken($typename = '')
	{
		$user      = User::getById(DI::app()->getLoggedInUserId(), ['guid', 'prvkey']);
		$timestamp = time();
		$sec_hash  = hash('whirlpool', ($user['guid'] ?? '') . ($user['prvkey'] ?? '') . session_id() . $timestamp . $typename);

		return $timestamp . '.' . $sec_hash;
	}

	public static function checkFormSecurityToken($typename = '', $formname = 'form_security_token')
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

		$user = User::getById(DI::app()->getLoggedInUserId(), ['guid', 'prvkey']);

		$x = explode('.', $hash);
		if (time() > (intval($x[0]) + $max_livetime)) {
			return false;
		}

		$sec_hash = hash('whirlpool', ($user['guid'] ?? '') . ($user['prvkey'] ?? '') . session_id() . $x[0] . $typename);

		return ($sec_hash == $x[1]);
	}

	public static function getFormSecurityStandardErrorMessage()
	{
		return DI::l10n()->t("The form security token was not correct. This probably happened because the form has been opened for too long \x28>3 hours\x29 before submitting it.") . EOL;
	}

	public static function checkFormSecurityTokenRedirectOnError($err_redirect, $typename = '', $formname = 'form_security_token')
	{
		if (!self::checkFormSecurityToken($typename, $formname)) {
			Logger::notice('checkFormSecurityToken failed: user ' . DI::app()->getLoggedInUserNickname() . ' - form element ' . $typename);
			Logger::debug('checkFormSecurityToken failed', ['request' => $_REQUEST]);
			notice(self::getFormSecurityStandardErrorMessage());
			DI::baseUrl()->redirect($err_redirect);
		}
	}

	public static function checkFormSecurityTokenForbiddenOnError($typename = '', $formname = 'form_security_token')
	{
		if (!self::checkFormSecurityToken($typename, $formname)) {
			Logger::notice('checkFormSecurityToken failed: user ' . DI::app()->getLoggedInUserNickname() . ' - form element ' . $typename);
			Logger::debug('checkFormSecurityToken failed', ['request' => $_REQUEST]);

			throw new \Friendica\Network\HTTPException\ForbiddenException();
		}
	}

	protected static function getContactFilterTabs(string $baseUrl, string $current, bool $displayCommonTab)
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
}
