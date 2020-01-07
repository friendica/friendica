<?php

namespace Friendica\Model;

use Friendica\Core\Addon;
use Friendica\Database\DBA;
use Friendica\Registry\Core;

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
		$config = Core::config();
		$logger = Core::logger();

		// If the addon 'statistics_json' is enabled then disable it and activate nodeinfo.
		if (Addon::isEnabled('statistics_json')) {
			$config->set('system', 'nodeinfo', true);

			$addon = 'statistics_json';
			$addons = $config->get('system', 'addon');

			if ($addons) {
				$addons_arr = explode(',', str_replace(' ', '', $addons));

				$idx = array_search($addon, $addons_arr);
				if ($idx !== false) {
					unset($addons_arr[$idx]);
					Addon::uninstall($addon);
					$config->set('system', 'addon', implode(', ', $addons_arr));
				}
			}
		}

		if (empty($config->get('system', 'nodeinfo'))) {
			return;
		}

		$userStats = User::getStatistics();

		$config->set('nodeinfo', 'total_users', $userStats['total_users']);
		$config->set('nodeinfo', 'active_users_halfyear', $userStats['active_users_halfyear']);
		$config->set('nodeinfo', 'active_users_monthly', $userStats['active_users_monthly']);

		$logger->debug('user statistics', $userStats);

		$items = DBA::p("SELECT COUNT(*) AS `total`, `gravity` FROM `item` WHERE `origin` AND NOT `deleted` AND `uid` != 0 AND `gravity` IN (?, ?) GROUP BY `gravity`",
			GRAVITY_PARENT, GRAVITY_COMMENT);
		while ($item = DBA::fetch($items)) {
			if ($item['gravity'] == GRAVITY_PARENT) {
				$config->set('nodeinfo', 'local_posts', $item['total']);
			} elseif ($item['gravity'] == GRAVITY_COMMENT) {
				$config->set('nodeinfo', 'local_comments', $item['total']);
			}
		}
		DBA::close($items);
	}
}
