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
use Friendica\Util\DateTimeFormat;

/**
 * Class SavedSearch
 *
 * @see https://developer.twitter.com/en/docs/twitter-api/v1/accounts-and-users/manage-account-settings/api-reference/get-saved_searches-list
 */
class SavedSearch extends BaseDataTransferObject
{
	/** @var string|null (Datetime) */
	protected $created_at;
	/** @var int */
	protected $id;
	/** @var string */
	protected $id_str;
	/** @var string */
	protected $name;
	/** @var string|null */
	protected $position;
	/** @var string */
	protected $query;

	/**
	 * Creates a saved search record from a search record.
	 *
	 * @param BaseURL $baseUrl
	 * @param array   $search Full search table record
	 */
	public function __construct(array $search)
	{
		$this->created_at = DateTimeFormat::utcNow(DateTimeFormat::JSON);
		$this->id         = (int)$search['id'];
		$this->id_str     = (string)$search['id'];
		$this->name       = $search['term'];
		$this->position   = null;
		$this->query      = $search['term'];
	}
}
