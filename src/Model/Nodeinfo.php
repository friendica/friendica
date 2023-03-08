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

namespace Friendica\Model;

use Friendica\Core\Addon;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Item;
use stdClass;

/**
 * Model interaction for the nodeinfo
 */
class Nodeinfo
{
	/**
	 * Updates the info about the current node
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function update()
	{
		$config = DI::config();
		$logger = DI::logger();

		// If the addon 'statistics_json' is enabled then disable it and activate nodeinfo.
		if (Addon::isEnabled('statistics_json')) {
			$config->set('system', 'nodeinfo', true);
			Addon::uninstall('statistics_json');
		}

		if (empty($config->get('system', 'nodeinfo'))) {
			return;
		}

		$userStats = User::getStatistics();

		DI::keyValue()->set('nodeinfo_total_users', $userStats['total_users']);
		DI::keyValue()->set('nodeinfo_active_users_halfyear', $userStats['active_users_halfyear']);
		DI::keyValue()->set('nodeinfo_active_users_monthly', $userStats['active_users_monthly']);
		DI::keyValue()->set('nodeinfo_active_users_weekly', $userStats['active_users_weekly']);

		$logger->info('user statistics', $userStats);

		$posts = DBA::count('post-thread', ["`uri-id` IN (SELECT `uri-id` FROM `post-user` WHERE NOT `deleted` AND `origin`)"]);
		$comments = DBA::count('post', ["NOT `deleted` AND `gravity` = ? AND `uri-id` IN (SELECT `uri-id` FROM `post-user` WHERE `origin`)", Item::GRAVITY_COMMENT]);
		DI::keyValue()->set('nodeinfo_local_posts', $posts);
		DI::keyValue()->set('nodeinfo_local_comments', $comments);

		$logger->info('User actitivy', ['posts' => $posts, 'comments' => $comments]);
	}

	/**
	 * Return the supported services
	 *
	 * @return Object with supported services
	*/
	public static function getUsage(bool $version2 = false)
	{
		$config = DI::config();

		$usage = new stdClass();
		$usage->users = new \stdClass;

		if (!empty($config->get('system', 'nodeinfo'))) {
			$usage->users->total = intval(DI::keyValue()->get('nodeinfo_total_users'));
			$usage->users->activeHalfyear = intval(DI::keyValue()->get('nodeinfo_active_users_halfyear'));
			$usage->users->activeMonth = intval(DI::keyValue()->get('nodeinfo_active_users_monthly'));
			$usage->localPosts = intval(DI::keyValue()->get('nodeinfo_local_posts'));
			$usage->localComments = intval(DI::keyValue()->get('nodeinfo_local_comments'));

			if ($version2) {
				$usage->users->activeWeek = intval(DI::keyValue()->get('nodeinfo_active_users_weekly'));
			}
		}

		return $usage;
	}

	/**
	 * Return the supported services
	 *
	 * @return array with supported services
	*/
	public static function getServices(): array
	{
		$services = [
			'inbound'  => [],
			'outbound' => [],
		];

		if (Addon::isEnabled('blogger')) {
			$services['outbound'][] = 'blogger';
		}
		if (Addon::isEnabled('dwpost')) {
			$services['outbound'][] = 'dreamwidth';
		}
		if (Addon::isEnabled('statusnet')) {
			$services['inbound'][] = 'gnusocial';
			$services['outbound'][] = 'gnusocial';
		}
		if (Addon::isEnabled('ijpost')) {
			$services['outbound'][] = 'insanejournal';
		}
		if (Addon::isEnabled('libertree')) {
			$services['outbound'][] = 'libertree';
		}
		if (Addon::isEnabled('buffer')) {
			$services['outbound'][] = 'linkedin';
		}
		if (Addon::isEnabled('ljpost')) {
			$services['outbound'][] = 'livejournal';
		}
		if (Addon::isEnabled('buffer')) {
			$services['outbound'][] = 'pinterest';
		}
		if (Addon::isEnabled('posterous')) {
			$services['outbound'][] = 'posterous';
		}
		if (Addon::isEnabled('pumpio')) {
			$services['inbound'][] = 'pumpio';
			$services['outbound'][] = 'pumpio';
		}

		$services['outbound'][] = 'smtp';

		if (Addon::isEnabled('tumblr')) {
			$services['outbound'][] = 'tumblr';
		}
		if (Addon::isEnabled('twitter') || Addon::isEnabled('buffer')) {
			$services['outbound'][] = 'twitter';
		}
		if (Addon::isEnabled('wppost')) {
			$services['outbound'][] = 'wordpress';
		}

		return $services;
	}

	/**
	 * Gathers organization information and returns it as an array
	 *
	 * @param IManageConfigValues $config Configuration instance
	 * @return array Organization information
	 * @throws \Exception
	 */
	public static function getOrganization(IManageConfigValues $config): array
	{
		$administrator = User::getFirstAdmin(['username', 'email', 'nickname']);

		return [
			'name'    => $administrator['username'] ?? null,
			'contact' => $administrator['email']    ?? null,
			'account' => $administrator['nickname'] ?? '' ? DI::baseUrl() . '/profile/' . $administrator['nickname'] : null,
		];
	}
}
