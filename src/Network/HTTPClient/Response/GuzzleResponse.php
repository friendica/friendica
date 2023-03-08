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

namespace Friendica\Network\HTTPClient\Response;

use Friendica\Core\Logger;
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

	public function __construct(ResponseInterface $response, string $url, $errorNumber = 0, $error = '')
	{
		parent::__construct($response->getStatusCode(), $response->getHeaders(), $response->getBody(), $response->getProtocolVersion(), $response->getReasonPhrase());
		$this->url         = $url;
		$this->error       = $error;
		$this->errorNumber = $errorNumber;

		$this->checkSuccess();
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
			Logger::debug('debug', ['info' => $this->getHeaders()]);
		}

		if (!$this->isSuccess && $this->errorNumber == CURLE_OPERATION_TIMEDOUT) {
			$this->isTimeout = true;
		} else {
			$this->isTimeout = false;
		}
	}

	private function checkRedirect(ResponseInterface $response)
	{
		$headersRedirect = $response->getHeader(RedirectMiddleware::HISTORY_HEADER) ?? [];

		if (count($headersRedirect) > 0) {
			$this->redirectUrl   = $headersRedirect[0];
			$this->isRedirectUrl = true;
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

	/// @todo - fix mismatching use of "getBody()" as string here and parent "getBody()" as streaminterface
	public function getBody(): string
	{
		return (string) parent::getBody();
	}
}
