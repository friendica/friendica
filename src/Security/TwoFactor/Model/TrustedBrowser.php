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

namespace Friendica\Security\TwoFactor\Model;

use Friendica\BaseEntity;
use Friendica\Util\DateTimeFormat;

/**
 * Class TrustedBrowser
 *
 *
 * @property-read $cookie_hash
 * @property-read $uid
 * @property-read $user_agent
 * @property-read $trusted
 * @property-read $created
 * @property-read $last_used
 * @package Friendica\Model\TwoFactor
 */
class TrustedBrowser extends BaseEntity
{
	protected $cookie_hash;
	protected $uid;
	protected $user_agent;
	protected $trusted;
	protected $created;
	protected $last_used;

	/**
	 * Please do not use this constructor directly, instead use one of the method of the TrustedBroser factory.
	 *
	 * @see \Friendica\Security\TwoFactor\Factory\TrustedBrowser
	 *
	 * @param string      $cookie_hash
	 * @param int         $uid
	 * @param string      $user_agent
	 * @param bool        $trusted
	 * @param string      $created
	 * @param string|null $last_used
	 */
	public function __construct(string $cookie_hash, int $uid, string $user_agent, bool $trusted, string $created, string $last_used = null)
	{
		$this->cookie_hash = $cookie_hash;
		$this->uid         = $uid;
		$this->user_agent  = $user_agent;
		$this->trusted     = $trusted;
		$this->created     = $created;
		$this->last_used   = $last_used;
	}

	/**
	 * Records if the trusted browser was used
	 *
	 * @return void
	 * @throws \Exception unexpected DateTime exception happened
	 */
	public function recordUse()
	{
		$this->last_used = DateTimeFormat::utcNow();
	}
}
