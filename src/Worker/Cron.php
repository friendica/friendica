<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\Core\Addon;
use Friendica\Core\Hook;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Tag;
use Friendica\Protocol\Relay;
use Friendica\Util\DateTimeFormat;

class Cron
{
	public static function execute()
	{
		$basepath = DI::appHelper()->getBasePath();

		$last = DI::keyValue()->get('last_cron');

		$poll_interval = intval(DI::config()->get('system', 'cron_interval'));

		if ($last) {
			$next = $last + ($poll_interval * 60);
			if ($next > time()) {
				DI::logger()->notice('cron interval not reached');
				return;
			}
		}

		DI::logger()->notice('start');

		// Ensure to have a .htaccess file.
		// this is a precaution for systems that update automatically
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
		if (DI::config()->get('system', 'poco_discovery')) {
			Worker::add(Worker::PRIORITY_LOW, 'UpdateServerDirectories');
		}

		// Expire and remove user entries
		Worker::add(Worker::PRIORITY_MEDIUM, 'ExpireAndRemoveUsers');

		// Call possible post update functions
		Worker::add(Worker::PRIORITY_LOW, 'PostUpdate');

		// Hourly cron calls
		if ((DI::keyValue()->get('last_cron_hourly') ?? 0) + 3600 < time()) {
			// Update trending tags cache for the community page
			Tag::setLocalTrendingHashtags(24, 20);
			Tag::setGlobalTrendingHashtags(24, 20);

			// Process all unprocessed entries
			Worker::add(Worker::PRIORITY_LOW, 'ProcessUnprocessedEntries');

			// Search for new contacts in the directory
			if (DI::config()->get('system', 'synchronize_directory')) {
				Worker::add(Worker::PRIORITY_LOW, 'PullDirectory');
			}

			// Clear cache entries
			Worker::add(Worker::PRIORITY_LOW, 'ClearCache');

			// Update interaction scores
			Worker::add(Worker::PRIORITY_LOW, 'UpdateScores');

			DI::keyValue()->set('last_cron_hourly', time());
		}

		// Daily maintenance cron calls
		if (Worker::isInMaintenanceWindow(true)) {

			Worker::add(Worker::PRIORITY_LOW, 'UpdateContactBirthdays');

			Worker::add(Worker::PRIORITY_LOW, 'UpdatePhotoAlbums');

			Worker::add(Worker::PRIORITY_LOW, 'ExpirePosts');

			Worker::add(Worker::PRIORITY_LOW, 'ExpireActivities');

			Worker::add(Worker::PRIORITY_LOW, 'ExpireSearchIndex');

			Worker::add(Worker::PRIORITY_LOW, 'Expire');

			Worker::add(Worker::PRIORITY_LOW, 'RemoveUnusedTags');

			Worker::add(Worker::PRIORITY_LOW, 'RemoveUnusedContacts');

			Worker::add(Worker::PRIORITY_LOW, 'RemoveUnusedAvatars');

			Worker::add(Worker::PRIORITY_LOW, 'NodeInfo');

			// check upstream version?
			Worker::add(Worker::PRIORITY_LOW, 'CheckVersion');

			Worker::add(Worker::PRIORITY_LOW, 'CheckDeletedContacts');

			Worker::add(Worker::PRIORITY_LOW, 'UpdateAllSuggestions');

			if (DI::config()->get('system', 'optimize_tables')) {
				Worker::add(Worker::PRIORITY_LOW, 'OptimizeTables');
			}

			$users = DBA::select('owner-view', ['uid'], ["`homepage_verified` OR (`last-activity` > ? AND `homepage` != ?)", DateTimeFormat::utc('now - 7 days', 'Y-m-d'), '']);
			while ($user = DBA::fetch($users)) {
				Worker::add(Worker::PRIORITY_LOW, 'CheckRelMeProfileLink', $user['uid']);
			}
			DBA::close($users);

			// Update contact relations for our users
			$users = DBA::select('user', ['uid'], ["`verified` AND NOT `blocked` AND NOT `account_removed` AND NOT `account_expired` AND `uid` > ?", 0]);
			while ($user = DBA::fetch($users)) {
				Worker::add(Worker::PRIORITY_LOW, 'ContactDiscoveryForUser', $user['uid']);
			}
			DBA::close($users);

			// Resubscribe to relay servers
			Relay::reSubscribe();

			// Update "blocked" status of servers
			Worker::add(Worker::PRIORITY_LOW, 'UpdateBlockedServers');

			Addon::reload();

			DI::keyValue()->set('last_cron_daily', time());
		}

		DI::logger()->notice('end');

		DI::keyValue()->set('last_cron', time());
	}

	/**
	 * Kill sleeping database processes
	 *
	 * @return void
	 */
	private static function deleteSleepingProcesses()
	{
		DI::logger()->info('Looking for sleeping processes');

		DBA::deleteSleepingProcesses();
	}
}
