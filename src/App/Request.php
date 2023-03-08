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

namespace Friendica\App;

use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\System;

/**
 * Container for the whole request
 *
 * @see https://www.php-fig.org/psr/psr-7/#321-psrhttpmessageserverrequestinterface
 *
 * @todo future container class for whole requests, currently it's not :-)
 */
class Request
{
	/**
	 * A comma separated list of default headers that could contain the client IP in a proxy request
	 *
	 * @var string
	 */
	const DEFAULT_FORWARD_FOR_HEADER = 'HTTP_X_FORWARDED_FOR';
	/**
	 * The default Request-ID header to retrieve the current transaction ID from the HTTP header (if set)
	 *
	 * @var string
	 */
	const DEFAULT_REQUEST_ID_HEADER = 'HTTP_X_REQUEST_ID';

	/** @var string The remote IP address of the current request */
	protected $remoteAddress;
	/** @var string The request-id of the current request */
	protected $requestId;

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

	public function __construct(IManageConfigValues $config, array $server = [])
	{
		$this->remoteAddress = $this->determineRemoteAddress($config, $server);
		$this->requestId = $server[static::DEFAULT_REQUEST_ID_HEADER] ?? System::createGUID(8, false);
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
		$trustedProxies = preg_split('/(\s*,*\s*)*,+(\s*,*\s*)*/', $config->get('proxy', 'trusted_proxies', ''));

		if (\is_array($trustedProxies) && $this->isTrustedProxy($trustedProxies, $remoteAddress)) {
			$forwardedForHeaders = preg_split('/(\s*,*\s*)*,+(\s*,*\s*)*/', $config->get('proxy', 'forwarded_for_headers', static::DEFAULT_FORWARD_FOR_HEADER));

			foreach ($forwardedForHeaders as $header) {
				if (isset($server[$header])) {
					foreach (explode(',', $server[$header]) as $IP) {
						$IP = trim($IP);

						// remove brackets from IPv6 addresses
						if (strpos($IP, '[') === 0 && substr($IP, -1) === ']') {
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
