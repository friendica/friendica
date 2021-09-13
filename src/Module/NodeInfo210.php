<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
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

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\Addon;
use Friendica\Core\System;
use Friendica\DI;
use Friendica\Model\Nodeinfo;

/**
 * Version 1.0 of Nodeinfo 2, a sStandardized way of exposing metadata about a server running one of the distributed social networks.
 * @see https://github.com/jhass/nodeinfo/blob/master/PROTOCOL.md
 */
class NodeInfo210 extends BaseModule
{
	public static function rawContent(array $parameters = [])
	{
		$config = DI::config();

		$nodeinfo = [
			'version'           => '1.0',
			'server'          => [
				'baseUrl'  => DI::baseUrl()->get(),
				'name'     => $config->get('config', 'sitename'),
				'software' => 'friendica',
				'version'  => FRIENDICA_VERSION . '-' . DB_UPDATE_VERSION,
			],
			'organization'      => Nodeinfo::getOrganization($config),
			'protocols'         => ['dfrn', 'activitypub'],
			'services'          => [],
			'openRegistrations' => intval($config->get('config', 'register_policy')) !== Register::CLOSED,
			'usage'             => [],
		];

		if (!empty($config->get('system', 'diaspora_enabled'))) {
			$nodeinfo['protocols'][] = 'diaspora';
		}

		if (empty($config->get('system', 'ostatus_disabled'))) {
			$nodeinfo['protocols'][] = 'ostatus';
		}

		$nodeinfo['usage'] = Nodeinfo::getUsage(true);

		$nodeinfo['services'] = Nodeinfo::getServices();

		if (Addon::isEnabled('twitter')) {
			$nodeinfo['services']['inbound'][] = 'twitter';
		}

		$nodeinfo['services']['inbound'][]  = 'atom1.0';
		$nodeinfo['services']['inbound'][]  = 'rss2.0';
		$nodeinfo['services']['outbound'][] = 'atom1.0';

		if (function_exists('imap_open') && !$config->get('system', 'imap_disabled')) {
			$nodeinfo['services']['inbound'][] = 'imap';
		}

		System::jsonExit($nodeinfo, 'application/json; charset=utf-8', JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
	}
}
