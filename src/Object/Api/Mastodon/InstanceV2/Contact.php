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

namespace Friendica\Object\Api\Mastodon\InstanceV2;

use Friendica\BaseDataTransferObject;
use Friendica\Object\Api\Mastodon\Account;

/**
 * Class Contact
 *
 * @see https://docs.joinmastodon.org/entities/Instance/
 */
class Contact extends BaseDataTransferObject
{
	/** @var string */
	protected $email;
	/** @var Account|null */
	protected $account = null;


	/**
	 * @param string $email
	 * @param Account|null $account
	 */
	public function __construct(string $email, ?Account $account)
	{
		$this->email   = $email;
		$this->account = $account;
	}
}
