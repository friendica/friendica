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

namespace Friendica\Core\Storage\Type;

use Exception;
use Friendica\Core\Storage\Exception\ReferenceStorageException;
use Friendica\Core\Storage\Capability\ICanReadFromStorage;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;
use Friendica\Network\HTTPClient\Client\HttpClientOptions;
use Friendica\Util\HTTPSignature;
use Psr\Log\LoggerInterface;

/**
 * External resource storage class
 *
 * This class is used to load external resources, like images.
 * Is not intended to be selectable by admins as default storage class.
 */
class ExternalResource implements ICanReadFromStorage
{
	const NAME = 'ExternalResource';

	/** @var LoggerInterface */
	protected $logger;

	public function __construct(LoggerInterface $logger)
	{
		$this->logger = $logger;
	}

	/**
	 * @inheritDoc
	 */
	public function get(string $reference): string
	{
		$data = json_decode($reference);
		if (empty($data->url)) {
			throw new ReferenceStorageException(sprintf('Invalid reference %s, cannot retrieve URL', $reference));
		}

		$parts = parse_url($data->url);
		if (empty($parts['scheme']) || empty($parts['host'])) {
			throw new ReferenceStorageException(sprintf('Invalid reference %s, cannot extract scheme and host', $reference));
		}

		try {
			$fetchResult = HTTPSignature::fetchRaw($data->url, $data->uid, [HttpClientOptions::ACCEPT_CONTENT => [HttpClientAccept::IMAGE]]);
		} catch (Exception $exception) {
			$this->logger->notice('URL is invalid', ['url' => $data->url, 'error' => $exception]);
			throw new ReferenceStorageException(sprintf('External resource failed to get %s', $reference), $exception->getCode(), $exception);
		}
		if (!empty($fetchResult) && $fetchResult->isSuccess()) {
			$this->logger->debug('Got picture', ['Content-Type' => $fetchResult->getHeader('Content-Type'), 'uid' => $data->uid, 'url' => $data->url]);
			return $fetchResult->getBody();
		} else {
			if (empty($fetchResult)) {
				throw new ReferenceStorageException(sprintf('External resource failed to get %s', $reference));
			} else {
				throw new ReferenceStorageException(sprintf('External resource failed to get %s', $reference), $fetchResult->getReturnCode(), new Exception($fetchResult->getBody()));
			}
		}
	}

	/**
	 * @inheritDoc
	 */
	public function __toString(): string
	{
		return self::NAME;
	}

	/**
	 * @inheritDoc
	 */
	public static function getName(): string
	{
		return self::NAME;
	}
}
