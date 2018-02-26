<?php
/**
 * @file mod/display.php
 */

use Friendica\App;
use Friendica\Content\Text\BBCode;
use Friendica\Core\Acl;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\Protocol;
use Friendica\Core\System;
use Friendica\Database\DBM;
use Friendica\Model\Contact;
use Friendica\Model\Group;
use Friendica\Model\Profile;
use Friendica\Protocol\DFRN;

function display_init(App $a)
{
	if (Config::get('system', 'block_public') && !local_user() && !remote_user()) {
		return;
	}

	$nick = (($a->argc > 1) ? $a->argv[1] : '');
	$profiledata = [];

	if ($a->argc == 3) {
		if (substr($a->argv[2], -5) == '.atom') {
			$item_id = substr($a->argv[2], 0, -5);
			displayShowFeed($item_id, false);
		}
	}

	if ($a->argc == 4) {
		if ($a->argv[3] == 'conversation.atom') {
			$item_id = $a->argv[2];
			displayShowFeed($item_id, true);
		}
	}

	$r = false;

	// If there is only one parameter, then check if this parameter could be a guid
	if ($a->argc == 2) {
		$nick = "";
		$r = false;

		// Does the local user have this item?
		if (local_user()) {
			$r = dba::fetch_first("SELECT `id`, `parent`, `author-name`, `author-link`,
						`author-avatar`, `network`, `body`, `uid`, `owner-link`
				FROM `item` WHERE `visible` AND NOT `deleted` AND NOT `moderated`
					AND `guid` = ? AND `uid` = ? LIMIT 1", $a->argv[1], local_user());
			if (DBM::is_result($r)) {
				$nick = $a->user["nickname"];
			}
		}

		// Is it an item with uid=0?
		if (!DBM::is_result($r)) {
			$r = dba::fetch_first("SELECT `id`, `parent`, `author-name`, `author-link`,
						`author-avatar`, `network`, `body`, `uid`, `owner-link`
				FROM `item` WHERE `visible` AND NOT `deleted` AND NOT `moderated`
					AND NOT `private` AND `uid` = 0
					AND `guid` = ? LIMIT 1", $a->argv[1]);
		}

		if (!DBM::is_result($r)) {
			$a->error = 404;
			notice(L10n::t('Item not found.') . EOL);
			return;
		}
	} elseif (($a->argc == 3) && ($nick == 'feed-item')) {
		$r = dba::fetch_first("SELECT `id`, `parent`, `author-name`, `author-link`,
					`author-avatar`, `network`, `body`, `uid`, `owner-link`
			FROM `item` WHERE `visible` AND NOT `deleted` AND NOT `moderated`
				AND NOT `private` AND `uid` = 0
				AND `id` = ? LIMIT 1", $a->argv[2]);
	}

	if (DBM::is_result($r)) {
		if (strstr($_SERVER['HTTP_ACCEPT'], 'application/atom+xml')) {
			logger('Directly serving XML for id '.$r["id"], LOGGER_DEBUG);
			displayShowFeed($r["id"], false);
		}

		if ($r["id"] != $r["parent"]) {
			$r = dba::fetch_first("SELECT `id`, `author-name`, `author-link`, `author-avatar`, `network`, `body`, `uid`, `owner-link` FROM `item`
				WHERE `item`.`visible` AND NOT `item`.`deleted` AND NOT `item`.`moderated`
					AND `id` = ?", $r["parent"]);
		}

		$profiledata = display_fetchauthor($a, $r);

		if (strstr(normalise_link($profiledata["url"]), normalise_link(System::baseUrl()))) {
			$nickname = str_replace(normalise_link(System::baseUrl())."/profile/", "", normalise_link($profiledata["url"]));

			if (($nickname != $a->user["nickname"])) {
				$r = dba::fetch_first("SELECT `profile`.`uid` AS `profile_uid`, `profile`.* , `contact`.`avatar-date` AS picdate, `user`.* FROM `profile`
					INNER JOIN `contact` on `contact`.`uid` = `profile`.`uid` INNER JOIN `user` ON `profile`.`uid` = `user`.`uid`
					WHERE `user`.`nickname` = ? AND `profile`.`is-default` AND `contact`.`self` LIMIT 1",
					$nickname
				);
				if (DBM::is_result($r)) {
					$profiledata = $r;
				}
				$profiledata["network"] = NETWORK_DFRN;
			} else {
				$profiledata = [];
			}
		}
	}

	Profile::load($a, $nick, 0, $profiledata);
}

function display_fetchauthor($a, $item) {
	$profiledata = [];
	$profiledata["uid"] = -1;
	$profiledata["nickname"] = $item["author-name"];
	$profiledata["name"] = $item["author-name"];
	$profiledata["picdate"] = "";
	$profiledata["photo"] = $item["author-avatar"];
	$profiledata["url"] = $item["author-link"];
	$profiledata["network"] = $item["network"];

	// Check for a repeated message
	$skip = false;
	$body = trim($item["body"]);

	// Skip if it isn't a pure repeated messages
	// Does it start with a share?
	if (!$skip && strpos($body, "[share") > 0) {
		$skip = true;
	}
	// Does it end with a share?
	if (!$skip && (strlen($body) > (strrpos($body, "[/share]") + 8))) {
		$skip = true;
	}
	if (!$skip) {
		$attributes = preg_replace("/\[share(.*?)\]\s?(.*?)\s?\[\/share\]\s?/ism","$1",$body);
		// Skip if there is no shared message in there
		if ($body == $attributes) {
			$skip = true;
		}
	}

	if (!$skip) {
		$author = "";
		preg_match("/author='(.*?)'/ism", $attributes, $matches);
		if ($matches[1] != "") {
			$profiledata["name"] = html_entity_decode($matches[1],ENT_QUOTES,'UTF-8');
		}
		preg_match('/author="(.*?)"/ism', $attributes, $matches);
		if ($matches[1] != "") {
			$profiledata["name"] = html_entity_decode($matches[1],ENT_QUOTES,'UTF-8');
		}
		$profile = "";
		preg_match("/profile='(.*?)'/ism", $attributes, $matches);
		if ($matches[1] != "") {
			$profiledata["url"] = $matches[1];
		}
		preg_match('/profile="(.*?)"/ism', $attributes, $matches);
		if ($matches[1] != "") {
			$profiledata["url"] = $matches[1];
		}
		$avatar = "";
		preg_match("/avatar='(.*?)'/ism", $attributes, $matches);
		if ($matches[1] != "") {
			$profiledata["photo"] = $matches[1];
		}
		preg_match('/avatar="(.*?)"/ism', $attributes, $matches);
		if ($matches[1] != "") {
			$profiledata["photo"] = $matches[1];
		}
		$profiledata["nickname"] = $profiledata["name"];
		$profiledata["network"] = Protocol::matchByProfileUrl($profiledata["url"]);

		$profiledata["address"] = "";
		$profiledata["about"] = "";
	}

	$profiledata = Contact::getDetailsByURL($profiledata["url"], local_user(), $profiledata);

	$profiledata["photo"] = System::removedBaseUrl($profiledata["photo"]);

	if (local_user()) {
		if (in_array($profiledata["network"], [NETWORK_DFRN, NETWORK_DIASPORA, NETWORK_OSTATUS])) {
			$profiledata["remoteconnect"] = System::baseUrl()."/follow?url=".urlencode($profiledata["url"]);
		}
	} elseif ($profiledata["network"] == NETWORK_DFRN) {
		$connect = str_replace("/profile/", "/dfrn_request/", $profiledata["url"]);
		$profiledata["remoteconnect"] = $connect;
	}

	return($profiledata);
}

function display_content(App $a, $update = false, $update_uid = 0) {
	if (Config::get('system','block_public') && !local_user() && !remote_user()) {
		notice(L10n::t('Public access denied.') . EOL);
		return;
	}

	require_once 'include/security.php';
	require_once 'include/conversation.php';
	require_once 'include/acl_selectors.php';

	$o = '';

	if ($update) {
		$item_id = $_REQUEST['item_id'];
		$item = dba::selectFirst('item', ['uid', 'parent'], ['id' => $item_id]);
		$a->profile = ['uid' => intval($item['uid']), 'profile_uid' => intval($item['uid'])];
		$item_parent = $item['parent'];
	} else {
		$item_id = (($a->argc > 2) ? $a->argv[2] : 0);

		if ($a->argc == 2) {
			$item_parent = 0;

			if (local_user()) {
				$r = dba::fetch_first("SELECT `id`, `parent` FROM `item`
					WHERE `item`.`visible` AND NOT `item`.`deleted` AND NOT `item`.`moderated`
						AND `guid` = ? AND `uid` = ?", $a->argv[1], local_user());
				if (DBM::is_result($r)) {
					$item_id = $r["id"];
					$item_parent = $r["parent"];
				}
			}

			if ($item_parent == 0) {
				$r = dba::fetch_first("SELECT `item`.`id`, `item`.`parent` FROM `item`
					WHERE `item`.`visible` AND NOT `item`.`deleted` AND NOT `item`.`moderated`
						AND NOT `item`.`private` AND `item`.`uid` = 0
						AND `item`.`guid` = ?", $a->argv[1]);
				if (DBM::is_result($r)) {
					$item_id = $r["id"];
					$item_parent = $r["parent"];
				}
			}
		}
	}

	if (!$item_id) {
		$a->error = 404;
		notice(L10n::t('Item not found.').EOL);
		return;
	}

	// We are displaying an "alternate" link if that post was public. See issue 2864
	$is_public = dba::exists('item', ['id' => $item_id, 'private' => false]);
	if ($is_public) {
		// For the atom feed the nickname doesn't matter at all, we only need the item id.
		$alternate = System::baseUrl().'/display/feed-item/'.$item_id.'.atom';
		$conversation = System::baseUrl().'/display/feed-item/'.$item_parent.'/conversation.atom';
	} else {
		$alternate = '';
		$conversation = '';
	}

	$a->page['htmlhead'] .= replace_macros(get_markup_template('display-head.tpl'),
				['$alternate' => $alternate,
					'$conversation' => $conversation]);

	$groups = [];

	$contact = null;
	$remote_contact = false;

	$contact_id = 0;

	if (x($_SESSION, 'remote') && is_array($_SESSION['remote'])) {
		foreach ($_SESSION['remote'] as $v) {
			if ($v['uid'] == $a->profile['uid']) {
				$contact_id = $v['cid'];
				break;
			}
		}
	}

	if ($contact_id) {
		$groups = Group::getIdsByContactId($contact_id);
		$r = dba::fetch_first("SELECT * FROM `contact` WHERE `id` = ? AND `uid` = ? LIMIT 1",
			$contact_id,
			$a->profile['uid']
		);
		if (DBM::is_result($r)) {
			$contact = $r;
			$remote_contact = true;
		}
	}

	if (!$remote_contact) {
		if (local_user()) {
			$contact_id = $_SESSION['cid'];
			$contact = $a->contact;
		}
	}

	$r = dba::fetch_first("SELECT * FROM `contact` WHERE `uid` = ? AND `self` LIMIT 1", $a->profile['uid']);
	if (DBM::is_result($r)) {
		$a->page_contact = $r;
	}
	$is_owner = (local_user() && (in_array($a->profile['profile_uid'], [local_user(), 0])) ? true : false);

	if (x($a->profile, 'hidewall') && !$is_owner && !$remote_contact) {
		notice(L10n::t('Access to this profile has been restricted.') . EOL);
		return;
	}

	// We need the editor here to be able to reshare an item.
	if ($is_owner) {
		$x = [
			'is_owner' => true,
			'allow_location' => $a->user['allow_location'],
			'default_location' => $a->user['default-location'],
			'nickname' => $a->user['nickname'],
			'lockstate' => (is_array($a->user) && (strlen($a->user['allow_cid']) || strlen($a->user['allow_gid']) || strlen($a->user['deny_cid']) || strlen($a->user['deny_gid'])) ? 'lock' : 'unlock'),
			'acl' => Acl::getFullSelectorHTML($a->user, true),
			'bang' => '',
			'visitor' => 'block',
			'profile_uid' => local_user(),
		];
		$o .= status_editor($a, $x, 0, true);
	}

	$sql_extra = item_permissions_sql($a->profile['uid'], $remote_contact, $groups);

	if ($update) {
		$r = dba::p("SELECT `id` FROM `item` WHERE
			`item`.`parent` = (SELECT `parent` FROM `item` WHERE `id` = ?)
			$sql_extra AND `unseen`",
			$item_id
		);

		if (dba::num_rows($r) == 0) {
			return '';
		}
	}

	$r = dba::p(item_query()."AND `item`.`parent` = (SELECT `parent` FROM `item` WHERE `id` = ?)
		$sql_extra
		ORDER BY `parent` DESC, `gravity` ASC, `id` ASC",
		$item_id
	);

	if (!DBM::is_result($r)) {
		notice(L10n::t('Item not found.') . EOL);
		return $o;
	}

	$s = dba::inArray($r);

	if (local_user() && (local_user() == $a->profile['uid'])) {
		$unseen = dba::selectFirst('item', ['id'], ['parent' => $s[0]['parent'], 'unseen' => true]);
		if (DBM::is_result($unseen)) {
			dba::update('item', ['unseen' => false], ['parent' => $s[0]['parent'], 'unseen' => true]);
		}
	}

	$items = conv_sort($s, "`commented`");

	if (!$update) {
		$o .= "<script> var netargs = '?f=&item_id=" . $item_id . "'; </script>";
	}
	$o .= conversation($a, $items, 'display', $update_uid);

	// Preparing the meta header
	require_once 'include/html2plain.php';

	$description = trim(html2plain(BBCode::convert($s[0]["body"], false), 0, true));
	$title = trim(html2plain(BBCode::convert($s[0]["title"], false), 0, true));
	$author_name = $s[0]["author-name"];

	$image = $a->remove_baseurl($s[0]["author-thumb"]);

	if ($title == "") {
		$title = $author_name;
	}

	// Limit the description to 160 characters
	if (strlen($description) > 160) {
		$description = substr($description, 0, 157) . '...';
	}

	$description = htmlspecialchars($description, ENT_COMPAT, 'UTF-8', true); // allow double encoding here
	$title = htmlspecialchars($title, ENT_COMPAT, 'UTF-8', true); // allow double encoding here
	$author_name = htmlspecialchars($author_name, ENT_COMPAT, 'UTF-8', true); // allow double encoding here

	//<meta name="keywords" content="">
	$a->page['htmlhead'] .= '<meta name="author" content="'.$author_name.'" />'."\n";
	$a->page['htmlhead'] .= '<meta name="title" content="'.$title.'" />'."\n";
	$a->page['htmlhead'] .= '<meta name="fulltitle" content="'.$title.'" />'."\n";
	$a->page['htmlhead'] .= '<meta name="description" content="'.$description.'" />'."\n";

	// Schema.org microdata
	$a->page['htmlhead'] .= '<meta itemprop="name" content="'.$title.'" />'."\n";
	$a->page['htmlhead'] .= '<meta itemprop="description" content="'.$description.'" />'."\n";
	$a->page['htmlhead'] .= '<meta itemprop="image" content="'.$image.'" />'."\n";
	$a->page['htmlhead'] .= '<meta itemprop="author" content="'.$author_name.'" />'."\n";

	// Twitter cards
	$a->page['htmlhead'] .= '<meta name="twitter:card" content="summary" />'."\n";
	$a->page['htmlhead'] .= '<meta name="twitter:title" content="'.$title.'" />'."\n";
	$a->page['htmlhead'] .= '<meta name="twitter:description" content="'.$description.'" />'."\n";
	$a->page['htmlhead'] .= '<meta name="twitter:image" content="'.System::baseUrl().'/'.$image.'" />'."\n";
	$a->page['htmlhead'] .= '<meta name="twitter:url" content="'.$s[0]["plink"].'" />'."\n";

	// Dublin Core
	$a->page['htmlhead'] .= '<meta name="DC.title" content="'.$title.'" />'."\n";
	$a->page['htmlhead'] .= '<meta name="DC.description" content="'.$description.'" />'."\n";

	// Open Graph
	$a->page['htmlhead'] .= '<meta property="og:type" content="website" />'."\n";
	$a->page['htmlhead'] .= '<meta property="og:title" content="'.$title.'" />'."\n";
	$a->page['htmlhead'] .= '<meta property="og:image" content="'.System::baseUrl().'/'.$image.'" />'."\n";
	$a->page['htmlhead'] .= '<meta property="og:url" content="'.$s[0]["plink"].'" />'."\n";
	$a->page['htmlhead'] .= '<meta property="og:description" content="'.$description.'" />'."\n";
	$a->page['htmlhead'] .= '<meta name="og:article:author" content="'.$author_name.'" />'."\n";
	// article:tag

	return $o;
}

function displayShowFeed($item_id, $conversation) {
	$xml = DFRN::itemFeed($item_id, $conversation);
	if ($xml == '') {
		System::httpExit(500);
	}
	header("Content-type: application/atom+xml");
	echo $xml;
	killme();
}
