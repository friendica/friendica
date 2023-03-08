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

use Friendica\BaseDataTransferObject;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Protocol;
use Friendica\Database\Database;
use Friendica\DI;

/**
 * Class Stats
 *
 * @see https://docs.joinmastodon.org/api/entities/#stats
 */
class Stats extends BaseDataTransferObject
{
	/** @var int */
	protected $user_count = 0;
	/** @var int */
	protected $status_count = 0;
	/** @var int */
	protected $domain_count = 0;

	public function __construct(IManageConfigValues $config, Database $database)
	{
		if (!empty($config->get('system', 'nodeinfo'))) {
			$this->user_count   = intval(DI::keyValue()->get('nodeinfo_total_users'));
			$this->status_count = (int)DI::keyValue()->get('nodeinfo_local_posts') + (int)DI::keyValue()->get('nodeinfo_local_comments');
			$this->domain_count = $database->count('gserver', ["`network` in (?, ?) AND NOT `failed` AND NOT `blocked`", Protocol::DFRN, Protocol::ACTIVITYPUB]);
		}
	}
}
