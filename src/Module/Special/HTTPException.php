<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Special;

use Friendica\App\Arguments;
use Friendica\App\Request;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Model\UserSession;
use Friendica\Core\System;
use Friendica\Module\Response;
use Friendica\Network\HTTPException as NetworkHTTPException;
use Psr\Log\LoggerInterface;
use Throwable;

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
	 * @return array ['$title' => ..., '$description' => ...]
	 */
	private function getVars(NetworkHTTPException $e)
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
	 * @throws \Exception
	 */
	public function rawContent(NetworkHTTPException $e)
	{
		$content = '';

		if ($e->getCode() >= 400) {
			$vars = $this->getVars($e);
			try {
				$tpl     = Renderer::getMarkupTemplate('http_status.tpl');
				$content = Renderer::replaceMacros($tpl, $vars);
			} catch (Throwable) {
				$vars    = array_map('htmlentities', $vars);
				$content = "<h1>{$vars['$title']}</h1><p>{$vars['$message']}</p>";
				if ($this->isSiteAdmin) {
					$content .= "<p>{$vars['$thrown']}</p>";
					$content .= "<pre>{$vars['$trace']}</pre>";
				}
			}
		}

		// We can't use a constructor parameter for this response object because we
		// are in an Exception context where we don't want an existing Response.
		$response = new Response();
		$response->setStatus($e->getCode(), $e->getDescription());
		$response->addContent($content);
		System::echoResponse($response->generate());
		System::exit();
	}

	/**
	 * Returns a content string that can be integrated in the current theme.
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function content(NetworkHTTPException $e): string
	{
		if ($e->getCode() >= 400) {
			$this->logger->debug(
				'Exit with error',
				[
					'code'        => $e->getCode(),
					'description' => $e->getDescription(),
					'query'       => $this->args->getQueryString(),
					'method'      => $this->args->getMethod(),
					'agent'       => $this->server['HTTP_USER_AGENT'] ?? '',
				],
			);
		}

		$tpl = Renderer::getMarkupTemplate('exception.tpl');

		return Renderer::replaceMacros($tpl, $this->getVars($e));
	}
}
