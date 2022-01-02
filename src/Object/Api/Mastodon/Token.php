<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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

use Friendica\BaseDataTransferObject;

/**
 * Class Token
 *
 * @see https://docs.joinmastodon.org/entities/token/
 */
class Token extends BaseDataTransferObject
{
	/** @var string */
	protected $access_token;
	/** @var string */
	protected $token_type;
	/** @var string */
	protected $scope;
	/** @var int (timestamp) */
	protected $created_at;

	/**
	 * Creates a token record
	 *
	 * @param string $access_token
	 * @param string $token_type
	 * @param string $scope
	 * @param string $created_at
	 */
	public function __construct(string $access_token, string $token_type, string $scope, string $created_at)
	{
		$this->access_token = $access_token;
		$this->token_type   = $token_type;
		$this->scope        = $scope;
		$this->created_at   = strtotime($created_at);
	}
}
