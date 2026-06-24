<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\App;

use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\System;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;

/**
 * Container for the whole request
 *
 * @see https://www.php-fig.org/psr/psr-7/#321-psrhttpmessageserverrequestinterface
 */
class Request implements ServerRequestInterface
{
	/**
	 * A comma separated list of default headers that could contain the client IP in a proxy request
	 *
	 * @var string
	 */
	public const DEFAULT_FORWARD_FOR_HEADER = 'HTTP_X_FORWARDED_FOR';
	/**
	 * The default Request-ID header to retrieve the current transaction ID from the HTTP header (if set)
	 *
	 * @var string
	 */
	public const DEFAULT_REQUEST_ID_HEADER = 'HTTP_X_REQUEST_ID';

	/** @var string The remote IP address of the current request */
	protected $remoteAddress;
	/** @var string The request-id of the current request */
	protected $requestId;
	private array $httpInput = [];
	private array $serverParams;

	public function __construct(private ServerRequestInterface $request, private readonly IManageConfigValues $config)
	{
		$this->serverParams  = $this->request->getServerParams();
		$this->remoteAddress = $this->determineRemoteAddress($this->config, $this->serverParams);
		$this->requestId     = $this->serverParams[static::DEFAULT_REQUEST_ID_HEADER] ?? System::createGUID(8, false);
	}

	/**
	 * @return string The remote IP address of the current request
	 *
	 * Do always use this instead of $_SERVER['REMOTE_ADDR']
	 */
	public function getRemoteAddress(): string
	{
		return $this->remoteAddress;
	}

	/**
	 * @return string The request ID of the current request
	 *
	 * Do always use this instead of $_SERVER['X_REQUEST_ID']
	 */
	public function getRequestId(): string
	{
		return $this->requestId;
	}

	private function withRequest(ServerRequestInterface $request): static
	{
		$clone          = clone $this;
		$clone->request = $request;
		return $clone;
	}

	// ----------------------------------------------
	// ServerRequestInterface
	// ----------------------------------------------

	public function getServerParams(): array
	{
		return $this->serverParams;
	}

	public function getCookieParams(): array
	{
		return $this->request->getCookieParams();
	}

	public function withCookieParams(array $cookies): static
	{
		return $this->withRequest($this->request->withCookieParams($cookies));
	}

	public function getQueryParams(): array
	{
		return $this->request->getQueryParams();
	}

	public function withQueryParams(array $query): static
	{
		return $this->withRequest($this->request->withQueryParams($query));
	}

	public function getUploadedFiles(): array
	{
		return $this->request->getUploadedFiles();
	}

	public function withUploadedFiles(array $uploadedFiles): static
	{
		return $this->withRequest($this->request->withUploadedFiles($uploadedFiles));
	}

	public function getParsedBody(): object|array|null
	{
		return $this->request->getParsedBody();
	}

	public function withParsedBody($data): static
	{
		return $this->withRequest($this->request->withParsedBody($data));
	}

	public function getAttributes(): array
	{
		return $this->request->getAttributes();
	}

	public function getAttribute(string $name, $default = null): mixed
	{
		return $this->request->getAttribute($name, $default);
	}

	public function withAttribute(string $name, $value): static
	{
		return $this->withRequest($this->request->withAttribute($name, $value));
	}

	public function withoutAttribute(string $name): static
	{
		return $this->withRequest($this->request->withoutAttribute($name));
	}

	// ----------------------------------------------
	// RequestInterface
	// ----------------------------------------------

	public function getRequestTarget(): string
	{
		return $this->request->getRequestTarget();
	}

	public function withRequestTarget(string $requestTarget): static
	{
		return $this->withRequest($this->request->withRequestTarget($requestTarget));
	}

	public function getMethod(): string
	{
		return $this->request->getMethod();
	}

	public function withMethod(string $method): static
	{
		return $this->withRequest($this->request->withMethod($method));
	}

	public function getUri(): UriInterface
	{
		return $this->request->getUri();
	}

	public function withUri(UriInterface $uri, bool $preserveHost = false): static
	{
		return $this->withRequest($this->request->withUri($uri, $preserveHost));
	}

	// ----------------------------------------------
	// MessageInterface
	// ----------------------------------------------

	public function getProtocolVersion(): string
	{
		return $this->request->getProtocolVersion();
	}

	public function withProtocolVersion(string $version): static
	{
		return $this->withRequest($this->request->withProtocolVersion($version));
	}

	public function getHeaders(): array
	{
		return $this->request->getHeaders();
	}

	public function hasHeader(string $name): bool
	{
		return $this->request->hasHeader($name);
	}

	public function getHeader(string $name): array
	{
		return $this->request->getHeader($name);
	}

	public function getHeaderLine(string $name): string
	{
		return $this->request->getHeaderLine($name);
	}

	public function withHeader(string $name, $value): static
	{
		return $this->withRequest($this->request->withHeader($name, $value));
	}

	public function withAddedHeader(string $name, $value): static
	{
		return $this->withRequest($this->request->withAddedHeader($name, $value));
	}

	public function withoutHeader(string $name): static
	{
		return $this->withRequest($this->request->withoutHeader($name));
	}

	public function getBody(): StreamInterface
	{
		return $this->request->getBody();
	}

	public function withBody(StreamInterface $body): static
	{
		return $this->withRequest($this->request->withBody($body));
	}

	// ----------------------------------------------
	// withServerParams (not part of PSR-7 interface)
	// ----------------------------------------------

	public function withServerParams(array $serverParams): static
	{
		$clone                = clone $this;
		$clone->serverParams  = $serverParams;
		$clone->remoteAddress = $clone->determineRemoteAddress($clone->config, $serverParams);
		$clone->requestId     = $serverParams[static::DEFAULT_REQUEST_ID_HEADER] ?? System::createGUID(8, false);
		return $clone;
	}

	// ----------------------------------------------
	// httpInput (immutable)
	// ----------------------------------------------

	public function withHttpInput(array $httpInput): static
	{
		$clone            = $this->withRequest($this->request);
		$clone->httpInput = $httpInput;
		return $clone;
	}

	// ----------------------------------------------
	// Typed query parameter getters
	// ----------------------------------------------

	public function getQueryParam(string $key, mixed $default = null): mixed
	{
		return $this->request->getQueryParams()[$key] ?? $default;
	}

	public function getQueryString(string $key, string $default = ''): string
	{
		return $this->getString($this->getQueryParam($key), $default);
	}

	public function getQueryInt(string $key, int $default = 0): int
	{
		return $this->getInt($this->getQueryParam($key), $default);
	}

	public function getQueryFloat(string $key, float $default = 0.0): float
	{
		return $this->getFloat($this->getQueryParam($key), $default);
	}

	public function getQueryBool(string $key, bool $default = false): bool
	{
		return $this->getBool($this->getQueryParam($key), $default);
	}

	public function getQueryArray(string $key, array $default = []): array
	{
		return $this->getArray($this->getQueryParam($key), $default);
	}

	// ----------------------------------------------
	// Typed body parameter getters
	// ----------------------------------------------

	public function getBodyParam(string $key, mixed $default = null): mixed
	{
		$body = $this->request->getParsedBody();
		return is_array($body) ? ($body[$key] ?? $default) : $default;
	}

	public function getBodyString(string $key, string $default = ''): string
	{
		return $this->getString($this->getBodyParam($key), $default);
	}

	public function getBodyInt(string $key, int $default = 0): int
	{
		return $this->getInt($this->getBodyParam($key), $default);
	}

	public function getBodyFloat(string $key, float $default = 0.0): float
	{
		return $this->getFloat($this->getBodyParam($key), $default);
	}

	public function getBodyBool(string $key, bool $default = false): bool
	{
		return $this->getBool($this->getBodyParam($key), $default);
	}

	public function getBodyArray(string $key, array $default = []): array
	{
		return $this->getArray($this->getBodyParam($key), $default);
	}

	// ----------------------------------------------
	// Server / Cookie parameter getters
	// ----------------------------------------------

	public function getServerParam(string $key, mixed $default = null): mixed
	{
		return $this->request->getServerParams()[$key] ?? $default;
	}

	public function getCookieParam(string $key, mixed $default = null): mixed
	{
		return $this->request->getCookieParams()[$key] ?? $default;
	}

	// ----------------------------------------------
	// Uploaded file getters
	// ----------------------------------------------

	public function getUploadedFile(string $key): ?UploadedFileInterface
	{
		$files = $this->request->getUploadedFiles();
		return isset($files[$key]) && $files[$key] instanceof UploadedFileInterface ? $files[$key] : null;
	}

	public function getUploadedFileArray(string $key): array
	{
		$files = $this->request->getUploadedFiles();
		return isset($files[$key]) && is_array($files[$key]) ? $files[$key] : [];
	}

	// ----------------------------------------------
	// Convenience merged input (Query > Body > httpInput)
	// ----------------------------------------------

	public function getAllInput(): array
	{
		return array_merge($this->request->getQueryParams(), (array) $this->request->getParsedBody(), $this->httpInput);
	}

	public function getInput(string $key, mixed $default = null): mixed
	{
		return $this->getAllInput()[$key] ?? $default;
	}

	public function getInputString(string $key, string $default = ''): string
	{
		return $this->getString($this->getInput($key), $default);
	}

	public function getInputInt(string $key, int $default = 0): int
	{
		return $this->getInt($this->getInput($key), $default);
	}

	public function getInputFloat(string $key, float $default = 0.0): float
	{
		return $this->getFloat($this->getInput($key), $default);
	}

	public function getInputBool(string $key, bool $default = false): bool
	{
		return $this->getBool($this->getInput($key), $default);
	}

	public function getInputArray(string $key, array $default = []): array
	{
		return $this->getArray($this->getInput($key), $default);
	}

	// ----------------------------------------------
	// HTTP helper methods
	// ----------------------------------------------

	public function isMethod(string $method): bool
	{
		return strtoupper($this->request->getMethod()) === strtoupper($method);
	}

	public function isGet(): bool
	{
		return $this->isMethod('GET');
	}

	public function isPost(): bool
	{
		return $this->isMethod('POST');
	}

	public function isPut(): bool
	{
		return $this->isMethod('PUT');
	}

	public function isPatch(): bool
	{
		return $this->isMethod('PATCH');
	}

	public function isDelete(): bool
	{
		return $this->isMethod('DELETE');
	}

	// ----------------------------------------------
	// Type casting helpers
	// ----------------------------------------------

	private function getString(mixed $value, string $default): string
	{
		return is_string($value) ? $value : $default;
	}

	private function getInt(mixed $value, int $default): int
	{
		return filter_var($value, FILTER_VALIDATE_INT) !== false ? (int) $value : $default;
	}

	private function getFloat(mixed $value, float $default): float
	{
		return filter_var($value, FILTER_VALIDATE_FLOAT) !== false ? (float) $value : $default;
	}

	private function getBool(mixed $value, bool $default): bool
	{
		if ($value === null) {
			return $default;
		}
		return filter_var($value, FILTER_VALIDATE_BOOLEAN, ['flags' => FILTER_NULL_ON_FAILURE]) ?? $default;
	}

	private function getArray(mixed $value, array $default): array
	{
		return is_array($value) ? $value : $default;
	}

	/**
	 * Checks if given $remoteAddress matches given $trustedProxy.
	 * If $trustedProxy is an IPv4 IP range given in CIDR notation, true will be returned if
	 * $remoteAddress is an IPv4 address within that IP range.
	 * Otherwise, $remoteAddress will be compared to $trustedProxy literally and the result
	 * will be returned.
	 *
	 * @param string $trustedProxy  The current, trusted proxy to check
	 * @param string $remoteAddress The current remote IP address
	 *
	 *
	 * @return boolean true if $remoteAddress matches $trustedProxy, false otherwise
	 */
	protected function matchesTrustedProxy(string $trustedProxy, string $remoteAddress): bool
	{
		$cidrre = '/^([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})\/([0-9]{1,2})$/';

		if (preg_match($cidrre, $trustedProxy, $match)) {
			$net       = $match[1];
			$shiftbits = min(32, max(0, 32 - intval($match[2])));
			$netnum    = ip2long($net) >> $shiftbits;
			$ipnum     = ip2long($remoteAddress) >> $shiftbits;

			return $ipnum === $netnum;
		}

		return $trustedProxy === $remoteAddress;
	}

	/**
	 * Checks if given $remoteAddress matches any entry in the given array $trustedProxies.
	 * For details regarding what "match" means, refer to `matchesTrustedProxy`.
	 *
	 * @param string[] $trustedProxies A list of the trusted proxies
	 * @param string   $remoteAddress  The current remote IP address
	 *
	 * @return boolean true if $remoteAddress matches any entry in $trustedProxies, false otherwise
	 */
	protected function isTrustedProxy(array $trustedProxies, string $remoteAddress): bool
	{
		foreach ($trustedProxies as $tp) {
			if ($this->matchesTrustedProxy($tp, $remoteAddress)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Determines the remote address, if the connection came from a trusted proxy
	 * and `forwarded_for_headers` has been configured then the IP address
	 * specified in this header will be returned instead.
	 *
	 * @param IManageConfigValues $config
	 * @param array               $server The $_SERVER array
	 *
	 * @return string
	 */
	protected function determineRemoteAddress(IManageConfigValues $config, array $server): string
	{
		$remoteAddress  = $server['REMOTE_ADDR'] ?? '0.0.0.0';
		$trustedProxies = preg_split('/(\s*,*\s*)*,+(\s*,*\s*)*/', (string) $config->get('proxy', 'trusted_proxies', ''));

		if (\is_array($trustedProxies) && $this->isTrustedProxy($trustedProxies, $remoteAddress)) {
			$forwardedForHeaders = preg_split('/(\s*,*\s*)*,+(\s*,*\s*)*/', (string) $config->get('proxy', 'forwarded_for_headers', static::DEFAULT_FORWARD_FOR_HEADER));

			foreach ($forwardedForHeaders as $header) {
				if (isset($server[$header])) {
					foreach (explode(',', $server[$header]) as $IP) {
						$IP = trim($IP);

						// remove brackets from IPv6 addresses
						if (str_starts_with($IP, '[') && str_ends_with($IP, ']')) {
							$IP = substr($IP, 1, -1);
						}

						// skip trusted proxies in the list itself
						if ($this->isTrustedProxy($trustedProxies, $IP)) {
							continue;
						}

						if (filter_var($IP, FILTER_VALIDATE_IP) !== false) {
							return $IP;
						}
					}
				}
			}
		}

		return $remoteAddress;
	}
}
