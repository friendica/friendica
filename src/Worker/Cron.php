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

namespace Friendica\Worker;

use Friendica\Core\Hook;
use Friendica\Core\Logger;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Tag;
use Friendica\Model\User;
use Friendica\Protocol\ActivityPub\Queue;
use Friendica\Protocol\Relay;

class Cron
{
	public static function execute()
	{
		$a = DI::app();

		$last = DI::config()->get('system', 'last_cron');

		$poll_interval = intval(DI::config()->get('system', 'cron_interval'));

		if ($last) {
			$next = $last + ($poll_interval * 60);
			if ($next > time()) {
				Logger::notice('cron intervall not reached');
				return;
			}
		}

		Logger::notice('start');

		// Ensure to have a .htaccess file.
		// this is a precaution for systems that update automatically
		$basepath = $a->getBasePath();
		if (!file_exists($basepath . '/.htaccess') && is_writable($basepath)) {
			copy($basepath . '/.htaccess-dist', $basepath . '/.htaccess');
		}

		if (DI::config()->get('system', 'delete_sleeping_processes')) {
			self::deleteSleepingProcesses();
		}

		// Fork the cron jobs in separate parts to avoid problems when one of them is crashing
		Hook::fork(Worker::PRIORITY_MEDIUM, 'cron');

		// Poll contacts
		Worker::add(Worker::PRIORITY_MEDIUM, 'PollContacts');

		// Update contact information
		Worker::add(Worker::PRIORITY_LOW, 'UpdateContacts');

		// Update server information
		Worker::add(Worker::PRIORITY_LOW, 'UpdateGServers');

		// run the process to update server directories in the background
		Worker::add(Worker::PRIORITY_LOW, 'UpdateServerDirectories');

		// Expire and remove user entries
		Worker::add(Worker::PRIORITY_MEDIUM, 'ExpireAndRemoveUsers');

		// Call possible post update functions
		Worker::add(Worker::PRIORITY_LOW, 'PostUpdate');

		// Hourly cron calls
		if (DI::config()->get('system', 'last_cron_hourly', 0) + 3600 < time()) {


			// Update trending tags cache for the community page
			Tag::setLocalTrendingHashtags(24, 20);
			Tag::setGlobalTrendingHashtags(24, 20);

			// Remove old pending posts from the queue
			Queue::clear();

			// Process all unprocessed entries
			Queue::processAll();

			// Search for new contacts in the directory
			if (DI::config()->get('system', 'synchronize_directory')) {
				Worker::add(Worker::PRIORITY_LOW, 'PullDirectory');
			}

			// Clear cache entries
			Worker::add(Worker::PRIORITY_LOW, 'ClearCache');

			DI::config()->set('system', 'last_cron_hourly', time());
		}

		// Daily maintenance cron calls
		if (Worker::isInMaintenanceWindow(true)) {

			Worker::add(Worker::PRIORITY_LOW, 'UpdateContactBirthdays');

			Worker::add(Worker::PRIORITY_LOW, 'UpdatePhotoAlbums');

			Worker::add(Worker::PRIORITY_LOW, 'ExpirePosts');

			Worker::add(Worker::PRIORITY_LOW, 'ExpireActivities');

			Worker::add(Worker::PRIORITY_LOW, 'RemoveUnusedTags');

			Worker::add(Worker::PRIORITY_LOW, 'RemoveUnusedContacts');

			Worker::add(Worker::PRIORITY_LOW, 'RemoveUnusedAvatars');

			// check upstream version?
			Worker::add(Worker::PRIORITY_LOW, 'CheckVersion');

			Worker::add(Worker::PRIORITY_LOW, 'CheckDeletedContacts');

			Worker::add(Worker::PRIORITY_LOW, 'UpdateAllSuggestions');

			if (DI::config()->get('system', 'optimize_tables')) {
				Worker::add(Worker::PRIORITY_LOW, 'OptimizeTables');
			}

			foreach (User::getList(1, PHP_INT_MAX, 'active') as $user) {
				Worker::add(Worker::PRIORITY_LOW, 'CheckRelMeProfileLink', $user['uid']);
			}

			// Resubscribe to relay servers
			Relay::reSubscribe();

			DI::config()->set('system', 'last_cron_daily', time());
		}

		Logger::notice('end');

		DI::config()->set('system', 'last_cron', time());
	}

	/**
	 * Kill sleeping database processes
	 *
	 * @return void
	 */
	private static function deleteSleepingProcesses()
	{
		Logger::info('Looking for sleeping processes');
		
		$processes = DBA::p("SHOW FULL PROCESSLIST");
		while ($process = DBA::fetch($processes)) {
			if (($process['Command'] != 'Sleep') || ($process['Time'] < 300) || ($process['db'] != DBA::databaseName())) {
				continue;
			}

			DBA::e("KILL ?", $process['Id']);
			Logger::notice('Killed sleeping process', ['id' => $process['Id']]);
		}
		DBA::close($processes);
	}
}
