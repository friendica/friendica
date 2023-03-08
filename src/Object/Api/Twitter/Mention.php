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

use Friendica\App\BaseURL;
use Friendica\BaseDataTransferObject;

/**
 * Class Mention
 *
 * @see https://developer.twitter.com/en/docs/twitter-api/v1/data-dictionary/object-model/entities#mentions
 */
class Mention extends BaseDataTransferObject
{
	/** @var int */
	protected $id;
	/** @var string */
	protected $id_str;
	/** @var array */
	protected $indices;
	/** @var string */
	protected $name;
	/** @var string */
	protected $screen_name;

	/**
	 * Creates a mention record from an tag-view record.
	 *
	 * @param BaseURL $baseUrl
	 * @param array   $tag     tag-view record
	 * @param array   $contact contact table record
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function __construct(array $tag, array $contact, array $indices)
	{
		$this->id          = (string)($contact['id'] ?? 0);
		$this->id_str      = (string)($contact['id'] ?? 0);
		$this->indices     = $indices;
		$this->name        = $tag['name'];
		$this->screen_name = $contact['nick'];
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
