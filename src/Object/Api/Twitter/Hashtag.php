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
 * Class Hashtag
 *
 * @see https://developer.twitter.com/en/docs/twitter-api/v1/data-dictionary/object-model/entities#hashtags
 */
class Hashtag extends BaseDataTransferObject
{
	/** @var array */
	protected $indices;
	/** @var string */
	protected $text;

	/**
	 * Creates a hashtag
	 *
	 * @param array $attachment
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function __construct(string $name, array $indices)
	{
		$this->indices = $indices;
		$this->text    = $name;
	}

	/**
	 * Returns the current entity as an array
	 *
	 * @return array
	 */
	public function toArray(): array
	{
		$status = parent::toArray();

		if (empty($status['indices'])) {
			unset($status['indices']);
		}

		return $status;
	}
}
