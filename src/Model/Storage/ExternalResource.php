<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
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

namespace Friendica\Model\Storage;

use Exception;
use Friendica\Util\HTTPSignature;

/**
 * External resource storage class
 *
 * This class is used to load external resources, like images.
 * Is not intended to be selectable by admins as default storage class.
 */
class ExternalResource implements IStorage
{
	const NAME = 'ExternalResource';

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
			$fetchResult = HTTPSignature::fetchRaw($data->url, $data->uid, ['accept_content' => []]);
		} catch (Exception $exception) {
			throw new ReferenceStorageException(sprintf('External resource failed to get %s', $reference), $exception->getCode(), $exception);
		}
		if ($fetchResult->isSuccess()) {
			return $fetchResult->getBody();
		} else {
			throw new ReferenceStorageException(sprintf('External resource failed to get %s', $reference), $fetchResult->getReturnCode(), new Exception($fetchResult->getBody()));
		}
	}

	/**
	 * @inheritDoc
	 */
	public function __toString()
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
