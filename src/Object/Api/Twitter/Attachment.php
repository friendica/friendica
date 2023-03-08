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

namespace Friendica\Object\Api\Twitter;

use Friendica\BaseDataTransferObject;

/**
 * Class Attachment
 *
 *
 */
class Attachment extends BaseDataTransferObject
{
	/** @var string */
	protected $url;
	/** @var string */
	protected $mimetype;
	/** @var int */
	protected $size;

	/**
	 * Creates an Attachment entity array
	 *
	 * @param array $attachment
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function __construct(array $media)
	{
		$this->url      = $media['url'];
		$this->mimetype = $media['mimetype'];
		$this->size     = $media['size'];
	}

	/**
	 * Returns the current entity as an array
	 *
	 * @return array
	 */
	public function toArray(): array
	{
		$status = parent::toArray();

		if (empty($status['mimetype'])) {
			unset($status['mimetype']);
		}

		if (empty($status['size'])) {
			unset($status['size']);
		}

		return $status;
	}
}
