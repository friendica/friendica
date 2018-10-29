<?php

namespace Friendica\Network;

use Friendica\App;
use Friendica\Network\HTTPException\InternalServerErrorException;

/**
 * A content class for Curl call results
 */
class CurlResult
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
	 * Creates an errored CURL response
	 *
	 * @param string $url optional URL
	 *
	 * @return CurlResult a CURL with error response
	 */
	public static function createErrorCurl($url = '')
	{
		return new CurlResult($url, '', ['http_code' => 0]);
	}

	/**
	 * Curl constructor.
	 * @param string $url the URL which was called
	 * @param string $result the result of the curl execution
	 * @param array $info an additional info array
	 * @param int $errorNumber the error number or 0 (zero) if no error
	 * @param string $error the error message or '' (the empty string) if no
	 *
	 * @throws InternalServerErrorException when HTTP code of the CURL response is missing
	 */
	public function __construct($url, $result, $info, $errorNumber = 0, $error = '')
	{
		if (!array_key_exists('http_code', $info)) {
			throw new InternalServerErrorException('CURL response doesn\'t contains a response HTTP code');
		}

		$this->returnCode = $info['http_code'];
		$this->url = $url;
		$this->info = $info;
		$this->errorNumber = $errorNumber;
		$this->error = $error;

		App::logger($url . ': ' . $this->returnCode . " " . $result, LOGGER_DATA);

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
		$base = $result;
		while (preg_match('/^HTTP\/.+? \d+/', $base)) {
			$chunk = substr($base, 0, strpos($base, "\r\n\r\n") + 4);
			$header .= $chunk;
			$base = substr($base, strlen($chunk));
		}

		$this->body = substr($result, strlen($header));
		$this->header = $header;
	}

	private function checkSuccess()
	{
		$this->isSuccess = ($this->returnCode >= 200 && $this->returnCode <= 299) || $this->errorNumber == 0;

		if (!$this->isSuccess) {
			App::logger('error: ' . $this->url . ': ' . $this->returnCode . ' - ' . $this->error, LOGGER_INFO);
			App::logger('debug: ' . print_r($this->info, true), LOGGER_DATA);
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

		if ($this->returnCode == 301 || $this->returnCode == 302 || $this->returnCode == 303 || $this->returnCode== 307) {
			$new_location_info = (!array_key_exists('redirect_url', $this->info) ? '' : @parse_url($this->info['redirect_url']));
			$old_location_info = (!array_key_exists('url', $this->info) ? '' : @parse_url($this->info['url']));

			$this->redirectUrl = $new_location_info;

			if (empty($new_location_info['path']) && !empty($new_location_info['host'])) {
				$this->redirectUrl = $new_location_info['scheme'] . '://' . $new_location_info['host'] . $old_location_info['path'];
			}

			$matches = [];

			if (preg_match('/(Location:|URI:)(.*?)\n/i', $this->header, $matches)) {
				$this->redirectUrl = trim(array_pop($matches));
			}
			if (strpos($this->redirectUrl, '/') === 0) {
				$this->redirectUrl = $old_location_info["scheme"] . "://" . $old_location_info["host"] . $this->redirectUrl;
			}
			$old_location_query = @parse_url($this->url, PHP_URL_QUERY);

			if ($old_location_query != '') {
				$this->redirectUrl .= '?' . $old_location_query;
			}

			$this->isRedirectUrl = filter_var($this->redirectUrl, FILTER_VALIDATE_URL) !== false;
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

	/**
	 * Gets the Curl Code
	 *
	 * @return string The Curl Code
	 */
	public function getReturnCode()
	{
		return $this->returnCode;
	}

	/**
	 * Returns the Curl Content Type
	 *
	 * @return string the Curl Content Type
	 */
	public function getContentType()
	{
		return $this->contentType;
	}

	/**
	 * Returns the Curl headers
	 *
	 * @return string the Curl headers
	 */
	public function getHeader()
	{
		return $this->header;
	}

	/**
	 * @return bool
	 */
	public function isSuccess()
	{
		return $this->isSuccess;
	}

	/**
	 * @return string
	 */
	public function getUrl()
	{
		return $this->url;
	}

	/**
	 * @return string
	 */
	public function getRedirectUrl()
	{
		return $this->redirectUrl;
	}

	/**
	 * @return string
	 */
	public function getBody()
	{
		return $this->body;
	}

	/**
	 * @return array
	 */
	public function getInfo()
	{
		return $this->info;
	}

	/**
	 * @return bool
	 */
	public function isRedirectUrl()
	{
		return $this->isRedirectUrl;
	}

	/**
	 * @return int
	 */
	public function getErrorNumber()
	{
		return $this->errorNumber;
	}

	/**
	 * @return string
	 */
	public function getError()
	{
		return $this->error;
	}

	/**
	 * @return bool
	 */
	public function isTimeout()
	{
		return $this->isTimeout;
	}
}
