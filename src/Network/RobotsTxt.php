<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Network;

use Friendica\Network\HTTPClient\Capability\ICanSendHttpRequests;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;
use Friendica\Network\HTTPClient\Client\HttpClientOptions;
use Friendica\Network\HTTPClient\Client\HttpClientRequest;

/**
 * This class handles robots.txt parsing and path validation
 */
class RobotsTxt
{
	/**
	 * @var string The base URL of the server
	 */
	private string $url = '';

	/**
	 * @var array Disallow rules for user-agent *
	 */
	private array $disallowRules = [];

	/**
	 * @var array Allow rules for user-agent *
	 */
	private array $allowRules = [];

	/**
	 * @var bool Indicates whether the robots.txt has been loaded
	 */
	private bool $isLoaded = false;

	protected ICanSendHttpRequests $httpClient;

	public function __construct(ICanSendHttpRequests $httpClient)
	{
		$this->httpClient = $httpClient;
	}

	/**
	 * Loads the RobotsTxt parser with a server URL
	 *
	 * @param string $url The base URL of the server
	 * @return bool True if robots.txt was successfully loaded, false otherwise
	 */
	public function load(string $url): bool
	{
		$this->url           = rtrim($url, '/');
		$this->disallowRules = [];
		$this->allowRules    = [];
		$this->isLoaded      = false;

		$robotsUrl = $this->url . '/robots.txt';

		try {
			$curlResult = $this->httpClient->get(
				$robotsUrl,
				HttpClientAccept::TEXT,
				[HttpClientOptions::REQUEST => HttpClientRequest::SERVERINFO]
			);

			if (!$curlResult->isSuccess()) {
				return false;
			}

			if (empty($curlResult->getBodyString())) {
				return false;
			}

			$this->isLoaded = $this->parseRobotsTxt($curlResult->getBodyString());
			return $this->isLoaded;
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Check if the robots.txt has been loaded successfully
	 *
	 * @return bool True if loaded, false otherwise
	 */
	public function isLoaded(): bool
	{
		return $this->isLoaded;
	}

	/**
	 * Parse the robots.txt content and extract rules for user-agent *
	 *
	 * @param string $content The content of robots.txt
	 * @return bool True if parsing was successful
	 */
	private function parseRobotsTxt(string $content): bool
	{
		$lines = explode("\n", $content);

		$isRelevantSection = false;

		foreach ($lines as $line) {
			$line = preg_replace('~\s*#.*$~', '', $line);
			$line = trim($line);

			if (empty($line)) {
				continue;
			}

			if (stripos($line, 'User-Agent:') === 0) {
				$agent = trim(substr($line, strlen('User-Agent:')));

				$isRelevantSection = ($agent === '*');
				continue;
			}

			if (!$isRelevantSection) {
				continue;
			}

			if (stripos($line, 'Disallow:') === 0) {
				$path = trim(substr($line, strlen('Disallow:')));
				if (!empty($path)) {
					$this->disallowRules[] = $path;
				}
				continue;
			}

			if (stripos($line, 'Allow:') === 0) {
				$path = trim(substr($line, strlen('Allow:')));
				if (!empty($path)) {
					$this->allowRules[] = $path;
				}
				continue;
			}

			if (stripos($line, 'User-Agent:') === 0 && !$isRelevantSection) {
				break;
			}
		}

		return sizeof($this->disallowRules) > 0 || sizeof($this->allowRules) > 0;
	}

	/**
	 * Check if a given path is allowed according to robots.txt rules
	 *
	 * @param string $path The path to check (e.g., "/api/v1/accounts")
	 * @return bool True if the path is allowed, false otherwise
	 */
	public function isAllowed(string $path): bool
	{
		$path    = '/' . ltrim(parse_url($path, PHP_URL_PATH), '/');
		$allowed = true;
		$length  = 0;

		foreach ($this->allowRules as $rule) {
			if (strlen($rule) > $length && $this->pathMatches($path, $rule)) {
				$length = strlen($rule);
			}
		}

		foreach ($this->disallowRules as $rule) {
			if (strlen($rule) > $length && $this->pathMatches($path, $rule)) {
				$allowed = false;
			}
		}

		return $allowed;
	}

	/**
	 * Check if a path matches a robots.txt rule
	 * Rules use prefix matching: /admin will block /admin, /admin/, /admin/page, etc.
	 *
	 * @param string $path The path to check
	 * @param string $rule The robots.txt rule
	 * @return bool True if the path matches the rule
	 */
	private function pathMatches(string $path, string $rule): bool
	{
		if (substr_compare($rule, '*', -strlen('*')) === 0) {
			$rule = substr($rule, 0, -1);
			return strncmp($path, $rule, strlen($rule)) === 0;
		}

		return strncmp($path, $rule, strlen($rule)) === 0;
	}
}
