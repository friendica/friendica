<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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

namespace Friendica\Domain\Entity\Api\Mastodon;

use Friendica\Domain\BaseEntity;
use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\DI;

/**
 * Class Stats
 *
 * @see https://docs.joinmastodon.org/api/entities/#stats
 */
class Stats extends BaseEntity
{
	/** @var int */
	protected $user_count = 0;
	/** @var int */
	protected $status_count = 0;
	/** @var int */
	protected $domain_count = 0;

	/**
	 * Creates a stats record
	 *
	 * @return Stats
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function get() {
		$stats = new Stats();
		if (!empty(DI::config()->get('system', 'nodeinfo'))) {
			$stats->user_count = intval(DI::config()->get('nodeinfo', 'total_users'));
			$stats->status_count = DI::config()->get('nodeinfo', 'local_posts') + DI::config()->get('nodeinfo', 'local_comments');
			$stats->domain_count = DBA::count('gserver', ["`network` in (?, ?) AND NOT `failed`", Protocol::DFRN, Protocol::ACTIVITYPUB]);
		}
		return $stats;
	}
}
