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

namespace Friendica\Object\Api\Mastodon;

use Friendica\App\BaseURL;
use Friendica\BaseDataTransferObject;

/**
 * Class Tag
 *
 * @see https://docs.joinmastodon.org/entities/tag
 */
class Tag extends BaseDataTransferObject
{
	/** @var string */
	protected $name;
	/** @var string */
	protected $url = null;
	/** @var array */
	protected $history = [];
	/** @var bool */
	protected $following = false;

	/**
	 * Creates a hashtag record from an tag-view record.
	 *
	 * @param BaseURL $baseUrl
	 * @param array   $tag       tag-view record
	 * @param array   $history
	 * @param array   $following "true" if the user is following this tag
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function __construct(BaseURL $baseUrl, array $tag, array $history = [], bool $following = false)
	{
		$this->name      = $tag['name'];
		$this->url       = $baseUrl . '/search?tag=' . urlencode(strtolower($this->name));
		$this->history   = $history;
		$this->following = $following;
	}
}
