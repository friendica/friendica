<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Network\HTTPClient\Response;

use Friendica\DI;
use Friendica\Network\HTTPClient\Capability\ICanHandleHttpResponses;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RedirectMiddleware;
use Psr\Http\Message\ResponseInterface;

/**
 * A content wrapper class for Guzzle call results
 */
class GuzzleResponse extends Response implements ICanHandleHttpResponses, ResponseInterface
{
	/** @var string The URL */
	private $url;
	/** @var boolean */
	private $isTimeout;
	/** @var boolean */
	private $isSuccess;
	/** @var boolean */
	private $isGone;
	/**
	 * @var int the error number or 0 (zero) if no error
	 */
	private $errorNumber;

	/**
	 * @var string the error message or '' (the empty string) if no
	 */
	private $error;

	/** @var string  */
	private $redirectUrl = '';
	/** @var bool */
	private $isRedirectUrl = false;
	/** @var bool */
	private $redirectIsPermanent = false;

	public function __construct(ResponseInterface $response, string $url, $errorNumber = 0, $error = '')
	{
		parent::__construct($response->getStatusCode(), $response->getHeaders(), $response->getBody(), $response->getProtocolVersion(), $response->getReasonPhrase());
		$this->url         = $url;
		$this->error       = $error;
		$this->errorNumber = $errorNumber;

		$this->checkSuccess();
		$this->checkGone();
		$this->checkRedirect($response);
	}

	private function checkSuccess()
	{
		$this->isSuccess = ($this->getStatusCode() >= 200 && $this->getStatusCode() <= 299) || $this->errorNumber == 0;

		// Everything higher or equal 400 is not a success
		if ($this->getReturnCode() >= 400) {
			$this->isSuccess = false;
		}

		if (!$this->isSuccess) {
			DI::logger()->debug('debug', ['info' => $this->getHeaders()]);
		}

		if (!$this->isSuccess && $this->errorNumber == CURLE_OPERATION_TIMEDOUT) {
			$this->isTimeout = true;
		} else {
			$this->isTimeout = false;
		}
	}

	private function checkGone()
	{
		$this->isGone = $this->getStatusCode() == 410;
	}

	private function checkRedirect(ResponseInterface $response)
	{
		$headersRedirect = $response->getHeader(RedirectMiddleware::HISTORY_HEADER) ?? [];

		if (count($headersRedirect) > 0) {
			$this->redirectUrl   = end($headersRedirect);
			$this->isRedirectUrl = true;

			$this->redirectIsPermanent = true;
			foreach (($response->getHeader(RedirectMiddleware::STATUS_HISTORY_HEADER) ?? []) as $history) {
				if (preg_match('/30(2|3|4|7)/', $history)) {
					$this->redirectIsPermanent = false;
				}
			}
		}
	}

	/** {@inheritDoc} */
	public function getReturnCode(): string
	{
		return $this->getStatusCode();
	}

	/** {@inheritDoc} */
	public function getContentType(): string
	{
		$contentTypes = $this->getHeader('Content-Type') ?? [];

		return array_pop($contentTypes) ?? '';
	}

	/** {@inheritDoc} */
	public function inHeader(string $field): bool
	{
		return $this->hasHeader($field);
	}

	/** {@inheritDoc} */
	public function getHeaderArray(): array
	{
		return $this->getHeaders();
	}

	/** {@inheritDoc} */
	public function isSuccess(): bool
	{
		return $this->isSuccess;
	}

	/** {@inheritDoc} */
	public function isGone(): bool
	{
		return $this->isGone;
	}

	/** {@inheritDoc} */
	public function getUrl(): string
	{
		return $this->url;
	}

	/** {@inheritDoc} */
	public function getRedirectUrl(): string
	{
		return $this->redirectUrl;
	}

	/** {@inheritDoc}
	 */
	public function isRedirectUrl(): bool
	{
		return $this->isRedirectUrl;
	}

	/** {@inheritDoc} */
	public function redirectIsPermanent(): bool
	{
		return $this->redirectIsPermanent;
	}

	/** {@inheritDoc} */
	public function getErrorNumber(): int
	{
		return $this->errorNumber;
	}

	/** {@inheritDoc} */
	public function getError(): string
	{
		return $this->error;
	}

	/** {@inheritDoc} */
	public function isTimeout(): bool
	{
		return $this->isTimeout;
	}

	public function getBodyString(): string
	{
		return (string) parent::getBody();
	}
}
