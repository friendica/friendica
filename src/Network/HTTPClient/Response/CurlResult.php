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
use Friendica\Network\HTTPException\UnprocessableEntityException;
use Friendica\Util\Network;
use Psr\Log\LoggerInterface;

/**
 * A content class for Curl call results
 */
class CurlResult implements ICanHandleHttpResponses
{
	/**
	 * @var int HTTP return code or 0 if timeout or failure
	 */
	private $returnCode;

	/**
	 * @var string the content type of the Curl call
	 */
	private $contentType;

	/**
	 * @var string the HTTP headers of the Curl call
	 */
	private $header;

	/**
	 * @var array the HTTP headers of the Curl call
	 */
	private $header_fields;

	/**
	 * @var boolean true (if HTTP 2xx result) or false
	 */
	private $isSuccess;

	/**
	 * @var string the URL which was called
	 */
	private $url;

	/**
	 * @var string in case of redirect, content was finally retrieved from this URL
	 */
	private $redirectUrl;

	/**
	 * @var string fetched content
	 */
	private $body;

	/**
	 * @var array some informations about the fetched data
	 */
	private $info;

	/**
	 * @var boolean true if the URL has a redirect
	 */
	private $isRedirectUrl;

	/**
	 * @var boolean true if the curl request timed out
	 */
	private $isTimeout;

	/**
	 * @var int the error number or 0 (zero) if no error
	 */
	private $errorNumber;

	/**
	 * @var string the error message or '' (the empty string) if no
	 */
	private $error;

	/**
	 * @var LoggerInterface
	 */
	protected $logger;

	/**
	 * Creates an errored CURL response
	 *
	 * @param string $url optional URL
	 *
	 * @return ICanHandleHttpResponses a CURL with error response
	 * @throws UnprocessableEntityException
	 */
	public static function createErrorCurl(LoggerInterface $logger, string $url = '')
	{
		return new CurlResult($logger, $url, '', ['http_code' => 0]);
	}

	/**
	 * Curl constructor.
	 *
	 * @param string $url         the URL which was called
	 * @param string $result      the result of the curl execution
	 * @param array  $info        an additional info array
	 * @param int    $errorNumber the error number or 0 (zero) if no error
	 * @param string $error       the error message or '' (the empty string) if no
	 *
	 * @throws UnprocessableEntityException when HTTP code of the CURL response is missing
	 */
	public function __construct(LoggerInterface $logger, string $url, string $result, array $info, int $errorNumber = 0, string $error = '')
	{
		$this->logger = $logger;

		if (!array_key_exists('http_code', $info)) {
			throw new UnprocessableEntityException('CURL response doesn\'t contains a response HTTP code');
		}

		$this->returnCode  = $info['http_code'];
		$this->url         = $url;
		$this->info        = $info;
		$this->errorNumber = $errorNumber;
		$this->error       = $error;

		$this->logger->debug('construct', ['url' => $url, 'returncode' => $this->returnCode, 'result' => $result]);

		$this->parseBodyHeader($result);
		$this->checkSuccess();
		$this->checkRedirect();
		$this->checkInfo();
	}

	private function parseBodyHeader($result)
	{
		// Pull out multiple headers, e.g. proxy and continuation headers
		// allow for HTTP/2.x without fixing code

		$header = '';
		$base   = $result;
		while (preg_match('/^HTTP\/.+? \d+/', $base)) {
			$chunk = substr($base, 0, strpos($base, "\r\n\r\n") + 4);
			$header .= $chunk;
			$base = substr($base, strlen($chunk));
		}

		$this->body          = substr($result, strlen($header));
		$this->header        = $header;
		$this->header_fields = []; // Is filled on demand
	}

	private function checkSuccess()
	{
		$this->isSuccess = ($this->returnCode >= 200 && $this->returnCode <= 299) || $this->errorNumber == 0;

		// Everything higher or equal 400 is not a success
		if ($this->returnCode >= 400) {
			$this->isSuccess = false;
		}

		if (empty($this->returnCode) && empty($this->header) && empty($this->body)) {
			$this->isSuccess = false;
		}

		if (!$this->isSuccess) {
			$this->logger->debug('debug', ['info' => $this->info]);
		}

		if (!$this->isSuccess && $this->errorNumber == CURLE_OPERATION_TIMEDOUT) {
			$this->isTimeout = true;
		} else {
			$this->isTimeout = false;
		}
	}

	private function checkRedirect()
	{
		if (!array_key_exists('url', $this->info)) {
			$this->redirectUrl = '';
		} else {
			$this->redirectUrl = $this->info['url'];
		}

		if ($this->returnCode == 301 || $this->returnCode == 302 || $this->returnCode == 303 || $this->returnCode == 307) {
			$redirect_parts = parse_url($this->info['redirect_url'] ?? '');
			if (empty($redirect_parts)) {
				$redirect_parts = [];
			}

			if (preg_match('/(Location:|URI:)(.*?)\n/i', $this->header, $matches)) {
				$redirect_parts2 = parse_url(trim(array_pop($matches)));
				if (!empty($redirect_parts2)) {
					$redirect_parts = array_merge($redirect_parts, $redirect_parts2);
				}
			}

			$parts = parse_url($this->info['url'] ?? '');
			if (empty($parts)) {
				$parts = [];
			}

			/// @todo Checking the corresponding RFC which parts of a redirect can be ommitted.
			$components = ['scheme', 'host', 'path', 'query', 'fragment'];
			foreach ($components as $component) {
				if (empty($redirect_parts[$component]) && !empty($parts[$component])) {
					$redirect_parts[$component] = $parts[$component];
				}
			}

			$this->redirectUrl = Network::unparseURL($redirect_parts);

			$this->isRedirectUrl = true;
		} else {
			$this->isRedirectUrl = false;
		}
	}

	private function checkInfo()
	{
		if (isset($this->info['content_type'])) {
			$this->contentType = $this->info['content_type'];
		} else {
			$this->contentType = '';
		}
	}

	/** {@inheritDoc} */
	public function getReturnCode(): string
	{
		return $this->returnCode;
	}

	/** {@inheritDoc} */
	public function getContentType(): string
	{
		return $this->contentType;
	}

	/** {@inheritDoc} */
	public function getHeader(string $header): array
	{
		if (empty($header)) {
			return [];
		}

		$header = strtolower(trim($header));

		$headers = $this->getHeaderArray();

		if (isset($headers[$header])) {
			return $headers[$header];
		}

		return [];
	}

	/** {@inheritDoc} */
	public function getHeaders(): array
	{
		return $this->getHeaderArray();
	}

	/** {@inheritDoc} */
	public function inHeader(string $field): bool
	{
		$field = strtolower(trim($field));

		$headers = $this->getHeaderArray();

		return array_key_exists($field, $headers);
	}

	/** {@inheritDoc} */
	public function getHeaderArray(): array
	{
		if (!empty($this->header_fields)) {
			return $this->header_fields;
		}

		$this->header_fields = [];

		$lines = explode("\n", trim($this->header));
		foreach ($lines as $line) {
			$parts       = explode(':', $line);
			$headerfield = strtolower(trim(array_shift($parts)));
			$headerdata  = trim(implode(':', $parts));
			if (empty($this->header_fields[$headerfield])) {
				$this->header_fields[$headerfield] = [$headerdata];
			} elseif (!in_array($headerdata, $this->header_fields[$headerfield])) {
				$this->header_fields[$headerfield][] = $headerdata;
			}
		}

		return $this->header_fields;
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

	/** {@inheritDoc} */
	public function getBody(): string
	{
		return $this->body;
	}

	/** {@inheritDoc} */
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
}
