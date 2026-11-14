<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\GServer;
use Friendica\Util\Network;
use GuzzleHttp\Psr7\Uri;

class UpdateBlockedServers
{
	/**
	 * Updates the server blocked status
	 */
	public static function execute()
	{
		DI::logger()->info('Update blocked servers - start');
		$gservers  = DBA::select('gserver', ['id', 'url', 'blocked']);
		$changed   = 0;
		$unchanged = 0;
		while ($gserver = DBA::fetch($gservers)) {
			try {
				$uri = new Uri($gserver['url']);
			} catch (\Throwable) {
				// A single stored URL that cannot be parsed must not abort the update for all following servers
				DI::logger()->warning('Invalid URL', ['url' => $gserver['url']]);
				continue;
			}

			$blocked = Network::isUriBlocked($uri);
			if (!is_null($gserver['blocked']) && ($blocked == $gserver['blocked'])) {
				$unchanged++;
				continue;
			}

			if ($blocked) {
				GServer::setBlockedById($gserver['id']);
			} else {
				GServer::setUnblockedById($gserver['id']);
			}
			$changed++;
		}
		DBA::close($gservers);
		DI::logger()->info('Update blocked servers - done', ['changed' => $changed, 'unchanged' => $unchanged]);

		if (DI::config()->get('system', 'delete-blocked-servers')) {
			DI::logger()->info('Delete blocked servers - start');
			$ret = DBA::delete('gserver', ["`blocked` AND NOT EXISTS(SELECT `gsid` FROM `inbox-status` WHERE `gsid` = `gserver`.`id`) AND NOT EXISTS(SELECT `gsid` FROM `contact` WHERE gsid= `gserver`.`id`) AND NOT EXISTS(SELECT `gsid` FROM `apcontact` WHERE `gsid` = `gserver`.`id`) AND NOT EXISTS(SELECT `gsid` FROM `delivery-queue` WHERE `gsid` = `gserver`.`id`) AND NOT EXISTS(SELECT `gsid` FROM `diaspora-contact` WHERE `gsid` = `gserver`.`id`) AND NOT EXISTS(SELECT `gserver-id` FROM `gserver-tag` WHERE `gserver-id` = `gserver`.`id`)"]);
			DI::logger()->info('Delete blocked servers - done', ['ret' => $ret, 'rows' => DBA::affectedRows()]);
		}
	}
}
