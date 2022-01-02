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

namespace Friendica\Model;

use Friendica\Core\Addon;
use Friendica\Database\DBA;
use Friendica\DI;
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

		$config->set('nodeinfo', 'total_users', $userStats['total_users']);
		$config->set('nodeinfo', 'active_users_halfyear', $userStats['active_users_halfyear']);
		$config->set('nodeinfo', 'active_users_monthly', $userStats['active_users_monthly']);
		$config->set('nodeinfo', 'active_users_weekly', $userStats['active_users_weekly']);

		$logger->info('user statistics', $userStats);

		$posts = DBA::count('post-thread', ["EXISTS(SELECT `uri-id` FROM `post-user` WHERE NOT `deleted` AND `origin` AND `uri-id` = `post-thread`.`uri-id`)"]);
		$comments = DBA::count('post', ["NOT `deleted` AND `gravity` = ? AND EXISTS(SELECT `uri-id` FROM `post-user` WHERE `origin` AND `uri-id` = `post`.`uri-id`)", GRAVITY_COMMENT]);
		$config->set('nodeinfo', 'local_posts', $posts);
		$config->set('nodeinfo', 'local_comments', $comments);

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

		if (!empty($config->get('system', 'nodeinfo'))) {
			$usage->users = [
				'total'          => intval($config->get('nodeinfo', 'total_users')),
				'activeHalfyear' => intval($config->get('nodeinfo', 'active_users_halfyear')),
				'activeMonth'    => intval($config->get('nodeinfo', 'active_users_monthly'))
			];
			$usage->localPosts = intval($config->get('nodeinfo', 'local_posts'));
			$usage->localComments = intval($config->get('nodeinfo', 'local_comments'));

			if ($version2) {
				$usage->users['activeWeek'] = intval($config->get('nodeinfo', 'active_users_weekly'));
			}
		}

		return $usage;
	}

	/**
	 * Return the supported services
	 *
	 * @return array with supported services
	*/
	public static function getServices()
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

	public static function getOrganization($config)
	{
		$organization = ['name' => null, 'contact' => null, 'account' => null];

		if (!empty($config->get('config', 'admin_email'))) {
			$adminList = explode(',', str_replace(' ', '', $config->get('config', 'admin_email')));
			$organization['contact'] = $adminList[0];
			$administrator = User::getByEmail($adminList[0], ['username', 'nickname']);
			if (!empty($administrator)) {
				$organization['name'] = $administrator['username'];
				$organization['account'] = DI::baseUrl()->get() . '/profile/' . $administrator['nickname'];
			}
		}

		return $organization;
	}
}
