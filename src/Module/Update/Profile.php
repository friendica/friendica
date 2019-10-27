<?php

namespace Friendica\Module\Update;

use Friendica\BaseModule;
use Friendica\Content\Pager;
use Friendica\Core\Config;
use Friendica\Core\Session;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\Profile as ProfileModel;
use Friendica\Network\HTTPException\ForbiddenException;
use Friendica\Util\DateTimeFormat;

require_once 'boot.php';

class Profile extends BaseModule
{
	public static function rawContent(array $parameters = [])
	{
		$a = DI::app();

		if (Config::get('system', 'block_public') && !local_user() && !Session::getRemoteContactID($a->profile['profile_uid'])) {
			throw new ForbiddenException();
		}

		$o = '';

		$profile_uid = intval($_GET['p'] ?? 0);

		// Ensure we've got a profile owner if updating.
		$a->profile['profile_uid'] = $profile_uid;

		$remote_contact = Session::getRemoteContactID($a->profile['profile_uid']);
		$is_owner = local_user() == $a->profile['profile_uid'];
		$last_updated_key = "profile:" . $a->profile['profile_uid'] . ":" . local_user() . ":" . $remote_contact;

		if (!empty($a->profile['hidewall']) && !$is_owner && !$remote_contact) {
			throw new ForbiddenException(DI::l10n()->t('Access to this profile has been restricted.'));
		}

		// Get permissions SQL - if $remote_contact is true, our remote user has been pre-verified and we already have fetched his/her groups
		$sql_extra = Item::getPermissionsSQLByUserId($a->profile['profile_uid']);

		$last_updated_array = Session::get('last_updated', []);

		$last_updated = $last_updated_array[$last_updated_key] ?? 0;

		// If the page user is the owner of the page we should query for unseen
		// items. Otherwise use a timestamp of the last succesful update request.
		if ($is_owner || !$last_updated) {
			$sql_extra4 = " AND `item`.`unseen`";
		} else {
			$gmupdate = gmdate(DateTimeFormat::MYSQL, $last_updated);
			$sql_extra4 = " AND `item`.`received` > '" . $gmupdate . "'";
		}

		$items_stmt = DBA::p(
			"SELECT DISTINCT(`parent-uri`) AS `uri`, `item`.`created`
			FROM `item`
			INNER JOIN `contact`
			ON `contact`.`id` = `item`.`contact-id`
				AND NOT `contact`.`blocked`
				AND NOT `contact`.`pending`
			WHERE `item`.`uid` = ?
				AND `item`.`visible`
				AND	(NOT `item`.`deleted` OR `item`.`gravity` = ?)
				AND NOT `item`.`moderated`
				AND `item`.`wall`
				$sql_extra4
				$sql_extra
			ORDER BY `item`.`received` DESC",
			$a->profile['profile_uid'],
			GRAVITY_ACTIVITY
		);

		if (!DBA::isResult($items_stmt)) {
			return '';
		}

		$pager = new Pager(DI::args()->getQueryString());

		// Set a time stamp for this page. We will make use of it when we
		// search for new items (update routine)
		$last_updated_array[$last_updated_key] = time();
		Session::set('last_updated', $last_updated_array);

		if ($is_owner && !$profile_uid && !Config::get('theme', 'hide_eventlist')) {
			$o .= ProfileModel::getBirthdays();
			$o .= ProfileModel::getEventsReminderHTML();
		}

		if ($is_owner) {
			$unseen = Item::exists(['wall' => true, 'unseen' => true, 'uid' => local_user()]);
			if ($unseen) {
				Item::update(['unseen' => false], ['wall' => true, 'unseen' => true, 'uid' => local_user()]);
			}
		}

		$items = DBA::toArray($items_stmt);

		$o .= conversation($a, $items, $pager, 'profile', $profile_uid, false, 'received', $a->profile['profile_uid']);

		header("Content-type: text/html");
		echo "<!DOCTYPE html><html><body>\r\n";
		// We can remove this hack once Internet Explorer recognises HTML5 natively
		echo "<section>";
		echo $o;
		if (DI::pConfig()->get(local_user(), "system", "bandwidth_saver")) {
			$replace = "<br />".DI::l10n()->t("[Embedded content - reload page to view]")."<br />";
			$pattern = "/<\s*audio[^>]*>(.*?)<\s*\/\s*audio>/i";
			$o = preg_replace($pattern, $replace, $o);
			$pattern = "/<\s*video[^>]*>(.*?)<\s*\/\s*video>/i";
			$o = preg_replace($pattern, $replace, $o);
			$pattern = "/<\s*embed[^>]*>(.*?)<\s*\/\s*embed>/i";
			$o = preg_replace($pattern, $replace, $o);
			$pattern = "/<\s*iframe[^>]*>(.*?)<\s*\/\s*iframe>/i";
			$o = preg_replace($pattern, $replace, $o);
		}

		// reportedly some versions of MSIE don't handle tabs in XMLHttpRequest documents very well
		echo str_replace("\t", "       ", $o);
		echo "</section>";
		echo "</body></html>\r\n";
		exit();
	}
}
