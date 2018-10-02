<?php

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\System;
use Friendica\Protocol\OStatus;

/**
 * Provides public Atom feeds
 *
 * Currently supported:
 * - /feed/[nickname]/ => posts
 * - /feed/[nickname]/posts => posts
 * - /feed/[nickname]/comments => comments
 * - /feed/[nickname]/replies => comments
 * - /feed/[nickname]/activity => activity
 *
 * The nocache GET parameter is provided mainly for debug purposes, requires auth
 *
 * @brief Provides public Atom feeds
 *
 * @author Hypolite Petovan <hypolite@mrpetovan.com>
 */
class Feed extends BaseModule
{
	public static function content()
	{
		$a = self::getApp();

		$last_update = x($_GET, 'last_update') ? $_GET['last_update'] : '';
		$nocache     = x($_GET, 'nocache') && local_user();

		if ($a->argc < 2) {
			System::httpExit(400);
		}

		$type = null;
		if ($a->argc > 2) {
			$type = $a->argv[2];
		}

		switch ($type) {
			case 'posts':
			case 'comments':
			case 'activity':
				break;
			case 'replies':
				$type = 'comments';
				break;
			default:
				$type = 'posts';
		}

		$nickname = $a->argv[1];
		header("Content-type: application/atom+xml");
		echo OStatus::feed($nickname, $last_update, 10, $type, $nocache);
		killme();
	}
}
