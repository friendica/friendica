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

namespace Friendica\Module\Special;

use Friendica\App\Arguments;
use Friendica\App\Request;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Model\UserSession;
use Friendica\Core\System;
use Psr\Log\LoggerInterface;

/**
 * This special module displays HTTPException when they are thrown in modules.
 *
 * @package Friendica\Module\Special
 */
class HTTPException
{
	/** @var L10n */
	protected $l10n;
	/** @var LoggerInterface */
	protected $logger;
	/** @var Arguments */
	protected $args;
	/** @var bool */
	protected $isSiteAdmin;
	/** @var array */
	protected $server;
	/** @var string */
	protected $requestId;

	public function __construct(L10n $l10n, LoggerInterface $logger, Arguments $args, UserSession $session, Request $request, array $server = [])
	{
		$this->logger      = $logger;
		$this->l10n        = $l10n;
		$this->args        = $args;
		$this->isSiteAdmin = $session->isSiteAdmin();
		$this->server      = $server;
		$this->requestId   = $request->getRequestId();
	}

	/**
	 * Generates the necessary template variables from the caught HTTPException.
	 *
	 * Fills in the blanks if title or descriptions aren't provided by the exception.
	 *
	 * @param \Friendica\Network\HTTPException $e
	 *
	 * @return array ['$title' => ..., '$description' => ...]
	 */
	private function getVars(\Friendica\Network\HTTPException $e)
	{
		// Explanations are mostly taken from https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
		$vars = [
			'$title'       => $e->getDescription() ?: 'Error ' . $e->getCode(),
			'$message'     => $e->getMessage() ?: $e->getExplanation(),
			'$back'        => $this->l10n->t('Go back'),
			'$stack_trace' => $this->l10n->t('Stack trace:'),
			'$request_id'  => $this->requestId,
		];

		if ($this->isSiteAdmin) {
			$vars['$thrown'] = $this->l10n->t('Exception thrown in %s:%d', $e->getFile(), $e->getLine());
			$vars['$trace']  = $e->getTraceAsString();
		}

		return $vars;
	}

	/**
	 * Displays a bare message page with no theming at all.
	 *
	 * @param \Friendica\Network\HTTPException $e
	 *
	 * @throws \Exception
	 */
	public function rawContent(\Friendica\Network\HTTPException $e)
	{
		$content = '';

		if ($e->getCode() >= 400) {
			$vars = $this->getVars($e);
			try {
				$tpl     = Renderer::getMarkupTemplate('http_status.tpl');
				$content = Renderer::replaceMacros($tpl, $vars);
			} catch (\Exception $e) {
				$vars = array_map('htmlentities', $vars);
				$content = "<h1>{$vars['$title']}</h1><p>{$vars['$message']}</p>";
				if ($this->isSiteAdmin) {
					$content .= "<p>{$vars['$thrown']}</p>";
					$content .= "<pre>{$vars['$trace']}</pre>";
				}
			}
		}

		System::httpError($e->getCode(), $e->getDescription(), $content);
	}

	/**
	 * Returns a content string that can be integrated in the current theme.
	 *
	 * @param \Friendica\Network\HTTPException $e
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function content(\Friendica\Network\HTTPException $e): string
	{
		header($this->server['SERVER_PROTOCOL'] ?? 'HTTP/1.0' . ' ' . $e->getCode() . ' ' . $e->getDescription());

		if ($e->getCode() >= 400) {
			$this->logger->debug('Exit with error',
				[
					'code'        => $e->getCode(),
					'description' => $e->getDescription(),
					'query'       => $this->args->getQueryString(),
					'callstack'   => System::callstack(20),
					'method'      => $this->args->getMethod(),
					'agent'       => $this->server['HTTP_USER_AGENT'] ?? ''
				]);
		}

		$tpl = Renderer::getMarkupTemplate('exception.tpl');

		return Renderer::replaceMacros($tpl, $this->getVars($e));
	}
}
