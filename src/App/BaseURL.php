<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\App;

use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\System;
use Friendica\Util\Strings;
use Friendica\Network\HTTPException;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;

/**
 * A class which checks and contains the basic environment for the BaseURL (url)
 */
class BaseURL extends Uri implements UriInterface
{
	public function __construct(IManageConfigValues $config, LoggerInterface $logger, array $server = [])
	{
		$url = $config->get('system', 'url');
		if (empty($url)) {
			$logger->critical('Invalid config - Missing system.url');
			$url = ServerRequest::getUriFromGlobals()
								->withQuery('')
								->withPath($this->determineURLPath($server));
		}

		parent::__construct($url);
	}

	/**
	 * Figure out if we are running at the top of a domain or in a subdirectory
	 */
	private function determineURLPath(array $server): string
	{
		/* Relative script path to the web server root
		 * Not all of those $_SERVER properties can be present, so we do by inverse priority order
		 */
		$relativeScriptPath = ($server['REDIRECT_URL'] ?? '') ?:
				($server['REDIRECT_URI'] ?? '') ?:
					($server['REDIRECT_SCRIPT_URL'] ?? '') ?:
						($server['SCRIPT_URL'] ?? '') ?:
							$server['REQUEST_URI'] ?? '';

		/* $relativeScriptPath gives /relative/path/to/friendica/module/parameter
		 * QUERY_STRING gives pagename=module/parameter
		 *
		 * To get /relative/path/to/friendica we perform dirname() for as many levels as there are slashes in the QUERY_STRING
		 */
		if (!empty($relativeScriptPath)) {
			// Module
			if (!empty($server['QUERY_STRING'])) {
				return trim(dirname($relativeScriptPath, substr_count(trim($server['QUERY_STRING'], '/'), '/') + 1), '/');
			} else {
				// Root page
				$scriptPathParts = explode('?', $relativeScriptPath, 2);
				return trim($scriptPathParts[0], '/');
			}
		}

		return '';
	}

	/**
	 * Removes the base url from an url. This avoids some mixed content problems.
	 *
	 * @param string $origURL
	 *
	 * @return string The cleaned url
	 */
	public function remove(string $origURL): string
	{
		// Remove the hostname from the url if it is an internal link
		$nurl = Strings::normaliseLink($origURL);
		$base = Strings::normaliseLink($this->__toString());
		$url  = str_replace($base . '/', '', $nurl);

		// if it is an external link return the original value
		if ($url === $nurl) {
			return $origURL;
		} else {
			return $url;
		}
	}

	/**
	 * Redirects to another module relative to the current Friendica base URL.
	 * If you want to redirect to a external URL, use System::externalRedirectTo()
	 *
	 * @param string $toUrl The destination URL (Default is empty, which is the default page of the Friendica node)
	 * @param bool   $ssl   if true, base URL will try to get called with https:// (works just for relative paths)
	 *
	 * @throws HTTPException\FoundException
	 * @throws HTTPException\MovedPermanentlyException
	 * @throws HTTPException\TemporaryRedirectException
	 *
	 * @throws HTTPException\InternalServerErrorException In Case the given URL is not relative to the Friendica node
	 *
	 * @return never
	 */
	public function redirect(string $toUrl = '', bool $ssl = false)
	{
		if (!empty(parse_url($toUrl, PHP_URL_SCHEME))) {
			throw new HTTPException\InternalServerErrorException("$toUrl is not a relative path, please use System::externalRedirectTo");
		}

		$redirectTo = $this->__toString() . '/' . ltrim($toUrl, '/');
		System::externalRedirect($redirectTo);
	}

	public function isLocalUrl(string $url): bool
	{
		return strpos(Strings::normaliseLink($url), Strings::normaliseLink((string)$this)) === 0;
	}

	public function isLocalUri(UriInterface $uri): bool
	{
		return $this->isLocalUrl((string)$uri);
	}
}
