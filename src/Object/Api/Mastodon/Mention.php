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
 * Class Mention
 *
 * @see https://docs.joinmastodon.org/entities/mention
 */
class Mention extends BaseDataTransferObject
{
	/** @var string */
	protected $id;
	/** @var string */
	protected $username;
	/** @var string */
	protected $url = null;
	/** @var string */
	protected $acct = null;

	/**
	 * Creates a mention record from an tag-view record.
	 *
	 * @param BaseURL $baseUrl
	 * @param array   $tag     tag-view record
	 * @param array   $contact contact table record
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function __construct(BaseURL $baseUrl, array $tag, array $contact)
	{
		$this->id       = (string)($contact['id'] ?? 0);
		$this->username = $tag['name'];
		$this->url      = $tag['url'];

		if (!empty($contact)) {
			$this->acct =
				strpos($contact['url'], $baseUrl . '/') === 0 ?
					$contact['nick'] :
					$contact['addr'];

			$this->username = $contact['nick'];
		} else {
			$this->acct = '';
		}
	}
}
