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

use Friendica\Core\Storage\Exception\ReferenceStorageException;
use Friendica\Core\Storage\Exception\StorageException;
use Friendica\Core\Storage\Capability\ICanReadFromStorage;

/**
 * System resource storage class
 *
 * This class is used to load system resources, like images.
 * Is not intended to be selectable by admins as default storage class.
 */
class SystemResource implements ICanReadFromStorage
{
	const NAME = 'SystemResource';

	// Valid folders to look for resources
	const VALID_FOLDERS = ["images"];

	/**
	 * @inheritDoc
	 */
	public function get(string $reference): string
	{
		$folder = dirname($reference);
		if (!in_array($folder, self::VALID_FOLDERS)) {
			throw new ReferenceStorageException(sprintf('System Resource is invalid for reference %s, no valid folder found', $reference));
		}
		if (!file_exists($reference)) {
			throw new StorageException(sprintf('System Resource is invalid for reference %s, the file doesn\'t exist', $reference));
		}
		$content = file_get_contents($reference);

		if ($content === false) {
			throw new StorageException(sprintf('Cannot get content for reference %s', $reference));
		}

		return $content;
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
