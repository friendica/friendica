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

namespace Friendica\Network\HTTPClient\Factory;

use Friendica\App;
use Friendica\BaseFactory;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\System;
use Friendica\Network\HTTPClient\Client;
use Friendica\Network\HTTPClient\Capability\ICanSendHttpRequests;
use Friendica\Util\Profiler;
use Friendica\Util\Strings;
use GuzzleHttp;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\RequestOptions;
use mattwright\URLResolver;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;

require_once __DIR__ . '/../../../../static/dbstructure.config.php';

class HttpClient extends BaseFactory
{
	/** @var IManageConfigValues */
	private $config;
	/** @var Profiler */
	private $profiler;
	/** @var App\BaseURL */
	private $baseUrl;

	public function __construct(LoggerInterface $logger, IManageConfigValues $config, Profiler $profiler, App\BaseURL $baseUrl)
	{
		parent::__construct($logger);
		$this->config   = $config;
		$this->profiler = $profiler;
		$this->baseUrl  = $baseUrl;
	}

	/**
	 * Creates a IHTTPClient for communications with HTTP endpoints
	 *
	 * @param HandlerStack|null $handlerStack (optional) A handler replacement (just usefull at test environments)
	 *
	 * @return ICanSendHttpRequests
	 */
	public function createClient(HandlerStack $handlerStack = null): ICanSendHttpRequests
	{
		$proxy = $this->config->get('system', 'proxy');

		if (!empty($proxy)) {
			$proxyUser = $this->config->get('system', 'proxyuser');

			if (!empty($proxyUser)) {
				$proxy = $proxyUser . '@' . $proxy;
			}
		}

		$logger = $this->logger;

		$onRedirect = function (
			RequestInterface $request,
			ResponseInterface $response,
			UriInterface $uri
		) use ($logger) {
			$logger->info('Curl redirect.', ['url' => $request->getUri(), 'to' => $uri, 'method' => $request->getMethod()]);
		};

		$userAgent = App::PLATFORM . " '" .
					 App::CODENAME . "' " .
					 App::VERSION . '-' .
					 DB_UPDATE_VERSION . '; ' .
					 $this->baseUrl;

		$guzzle = new GuzzleHttp\Client([
			RequestOptions::ALLOW_REDIRECTS => [
				'max'             => 8,
				'on_redirect'     => $onRedirect,
				'track_redirects' => true,
				'strict'          => true,
				'referer'         => true,
			],
			RequestOptions::HTTP_ERRORS => false,
			// Without this setting it seems as if some webservers send compressed content
			// This seems to confuse curl so that it shows this uncompressed.
			/// @todo  We could possibly set this value to "gzip" or something similar
			RequestOptions::DECODE_CONTENT   => '',
			RequestOptions::FORCE_IP_RESOLVE => ($this->config->get('system', 'ipv4_resolve') ? 'v4' : null),
			RequestOptions::CONNECT_TIMEOUT  => 10,
			RequestOptions::TIMEOUT          => $this->config->get('system', 'curl_timeout', 60),
			// by default, we will allow self-signed certs,
			// but it can be overridden
			RequestOptions::VERIFY  => (bool)$this->config->get('system', 'verifyssl'),
			RequestOptions::PROXY   => $proxy,
			RequestOptions::HEADERS => [
				'User-Agent' => $userAgent,
			],
			'handler' => $handlerStack ?? HandlerStack::create(),
		]);

		$resolver = new URLResolver();
		$resolver->setUserAgent($userAgent);
		$resolver->setMaxRedirects(10);
		$resolver->setRequestTimeout(10);
		// if the file is too large then exit
		$resolver->setMaxResponseDataSize(1000000);
		// Designate a temporary file that will store cookies during the session.
		// Some websites test the browser for cookie support, so this enhances results.
		$resolver->setCookieJar(System::getTempPath() .'/resolver-cookie-' . Strings::getRandomName(10));

		return new Client\HttpClient($logger, $this->profiler, $guzzle, $resolver);
	}
}
