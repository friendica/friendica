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

namespace Friendica\Util;

/**
 * Derived from the work of Reid Johnson <https://codereview.stackexchange.com/users/4020/reid-johnson>
 * @see https://codereview.stackexchange.com/questions/69882/parsing-multipart-form-data-in-php-for-put-requests
 */
class HTTPInputData
{
	/** @var array The $_SERVER variable */
	protected $server;

	public function __construct(array $server)
	{
		$this->server = $server;
	}

	/**
	 * Process the PHP input stream and creates an array with its content
	 *
	 * @return array|array[]
	 */
	public function process(): array
	{
		$content_parts = explode(';', $this->server['CONTENT_TYPE'] ?? 'application/x-www-form-urlencoded');

		$boundary = '';
		$encoding = '';

		$content_type = array_shift($content_parts);

		foreach ($content_parts as $part) {
			if (strpos($part, 'boundary') !== false) {
				$part = explode('=', $part, 2);
				if (!empty($part[1])) {
					$boundary = '--' . $part[1];
				}
			} elseif (strpos($part, 'charset') !== false) {
				$part = explode('=', $part, 2);
				if (!empty($part[1])) {
					$encoding = $part[1];
				}
			}
			if ($boundary !== '' && $encoding !== '') {
				break;
			}
		}

		if ($content_type == 'multipart/form-data') {
			return $this->fetchFromMultipart($boundary);
		}

		// can be handled by built in PHP functionality
		$content = static::getPhpInputContent();

		$variables = json_decode($content, true);

		if (empty($variables)) {
			parse_str($content, $variables);
		}

		return ['variables' => $variables, 'files' => []];
	}

	private function fetchFromMultipart(string $boundary): array
	{
		$result = ['variables' => [], 'files' => []];

		$stream = static::getPhpInputStream();

		$sanity = fgets($stream, strlen($boundary) + 5);

		// malformed file, boundary should be first item
		if (rtrim($sanity) !== $boundary) {
			return $result;
		}

		$raw_headers = '';

		while (($chunk = fgets($stream)) !== false) {
			if ($chunk === $boundary) {
				continue;
			}

			if (!empty(trim($chunk))) {
				$raw_headers .= $chunk;
				continue;
			}

			$result = $this->parseRawHeader($stream, $raw_headers, $boundary, $result);

			$raw_headers = '';
		}

		fclose($stream);

		return $result;
	}

	private function parseRawHeader($stream, string $raw_headers, string $boundary, array $result)
	{
		$variables = $result['variables'];
		$files     = $result['files'];

		$headers = [];

		foreach (explode("\r\n", $raw_headers) as $header) {
			if (strpos($header, ':') === false) {
				continue;
			}
			[$name, $value] = explode(':', $header, 2);

			$headers[strtolower($name)] = ltrim($value, ' ');
		}

		if (!isset($headers['content-disposition'])) {
			return ['variables' => $variables, 'files' => $files];
		}

		if (!preg_match('/^(.+); *name="([^"]+)"(; *filename="([^"]+)")?/', $headers['content-disposition'], $matches)) {
			return ['variables' => $variables, 'files' => $files];
		}

		$name     = $matches[2];
		$filename = $matches[4] ?? '';

		if (!empty($filename)) {
			$files[$name] = static::fetchFileData($stream, $boundary, $headers, $filename);
			return ['variables' => $variables, 'files' => $files];
		} else {
			$variables = $this->fetchVariables($stream, $boundary, $headers, $name, $variables);
		}

		return ['variables' => $variables, 'files' => $files];
	}

	protected function fetchFileData($stream, string $boundary, array $headers, string $filename)
	{
		$error = UPLOAD_ERR_OK;

		if (isset($headers['content-type'])) {
			$tmp = explode(';', $headers['content-type']);

			$contentType = $tmp[0];
		} else {
			$contentType = 'unknown';
		}

		$tmpnam     = tempnam(ini_get('upload_tmp_dir'), 'php');
		$fileHandle = fopen($tmpnam, 'wb');

		if ($fileHandle === false) {
			$error = UPLOAD_ERR_CANT_WRITE;
		} else {
			$lastLine = null;
			while (($chunk = fgets($stream, 8096)) !== false && strpos($chunk, $boundary) !== 0) {
				if ($lastLine !== null) {
					if (!fwrite($fileHandle, $lastLine)) {
						$error = UPLOAD_ERR_CANT_WRITE;
						break;
					}
				}
				$lastLine = $chunk;
			}

			if ($lastLine !== null && $error !== UPLOAD_ERR_CANT_WRITE) {
				if (!fwrite($fileHandle, rtrim($lastLine, "\r\n"))) {
					$error = UPLOAD_ERR_CANT_WRITE;
				}
			}
		}

		return [
			'name'     => $filename,
			'type'     => $contentType,
			'tmp_name' => $tmpnam,
			'error'    => $error,
			'size'     => filesize($tmpnam)
		];
	}

	private function fetchVariables($stream, string $boundary, array $headers, string $name, array $variables)
	{
		$fullValue = '';
		$lastLine  = null;

		while (($chunk = fgets($stream)) !== false && strpos($chunk, $boundary) !== 0) {
			if ($lastLine !== null) {
				$fullValue .= $lastLine;
			}

			$lastLine = $chunk;
		}

		if ($lastLine !== null) {
			$fullValue .= rtrim($lastLine, "\r\n");
		}

		if (isset($headers['content-type'])) {
			$encoding = '';

			foreach (explode(';', $headers['content-type']) as $part) {
				if (strpos($part, 'charset') !== false) {
					$part = explode($part, '=', 2);
					if (isset($part[1])) {
						$encoding = $part[1];
					}
					break;
				}
			}

			if ($encoding !== '' && strtoupper($encoding) !== 'UTF-8' && strtoupper($encoding) !== 'UTF8') {
				$tmp = mb_convert_encoding($fullValue, 'UTF-8', $encoding);
				if ($tmp !== false) {
					$fullValue = $tmp;
				}
			}
		}

		$fullValue = $name . '=' . $fullValue;

		$tmp = [];
		parse_str($fullValue, $tmp);

		return $this->expandVariables(explode('[', $name), $variables, $tmp);
	}

	private function expandVariables(array $names, $variables, array $values)
	{
		if (!is_array($variables)) {
			return $values;
		}

		$name = rtrim(array_shift($names), ']');
		if ($name !== '') {
			$name = $name . '=p';

			$tmp = [];
			parse_str($name, $tmp);

			$tmp  = array_keys($tmp);
			$name = reset($tmp);
		}

		if ($name === '') {
			$variables[] = reset($values);
		} elseif (isset($variables[$name]) && isset($values[$name])) {
			$variables[$name] = $this->expandVariables($names, $variables[$name], $values[$name]);
		} elseif (isset($values[$name])) {
			$variables[$name] = $values[$name];
		}

		return $variables;
	}

	/**
	 * Returns the current PHP input stream
	 * Mainly used for test doubling
	 *
	 * @return false|resource
	 */
	protected function getPhpInputStream()
	{
		return fopen('php://input', 'rb');
	}

	/**
	 * Returns the content of the current PHP input
	 * Mainly used for test doubling
	 *
	 * @return false|string
	 */
	protected function getPhpInputContent()
	{
		return file_get_contents('php://input');
	}
}
