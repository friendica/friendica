<?php

use Friendica\App;

require_once('include/dfrn.php');

function display_init(App $a) {

	if ((get_config('system','block_public')) && (! local_user()) && (! remote_user())) {
		return;
	}

	$nick = (($a->argc > 1) ? $a->argv[1] : '');
	$profiledata = array();

	if ($a->argc == 3) {
		if (substr($a->argv[2], -5) == '.atom') {
			$item_id = substr($a->argv[2], 0, -5);
			$xml = dfrn::itemFeed($item_id);
			if ($xml == '') {
				http_status_exit(500);
			}
			header("Content-type: application/atom+xml");
			echo $xml;
			killme();
		}
	}

	// If there is only one parameter, then check if this parameter could be a guid
	if ($a->argc == 2) {
		$nick = "";
		$itemuid = 0;

		// Does the local user have this item?
		if (local_user()) {
			$r = dba::fetch_first("SELECT `id`, `parent`, `author-name`, `author-link`, `author-avatar`, `network`, `body`, `uid`, `owner-link` FROM `item`
				WHERE `item`.`visible` AND NOT `item`.`deleted` AND NOT `item`.`moderated`
					AND `guid` = ? AND `uid` = ? LIMIT 1", $a->argv[1], local_user());
			if (dbm::is_result($r)) {
				$nick = $a->user["nickname"];
				$itemuid = local_user();
			}
		}

		// Or is it anywhere on the server?
		if ($nick == "") {
			$r = dba::fetch_first("SELECT `user`.`nickname`, `item`.`id`, `item`.`parent`, `item`.`author-name`,
				`item`.`author-link`, `item`.`author-avatar`, `item`.`network`, `item`.`uid`, `item`.`owner-link`, `item`.`body`
				FROM `item` STRAIGHT_JOIN `user` ON `user`.`uid` = `item`.`uid`
				WHERE `item`.`visible` AND NOT `item`.`deleted` AND NOT `item`.`moderated`
					AND `item`.`allow_cid` = ''  AND `item`.`allow_gid` = ''
					AND `item`.`deny_cid`  = '' AND `item`.`deny_gid`  = ''
					AND NOT `item`.`private` AND NOT `user`.`hidewall`
					AND `item`.`guid` = ? LIMIT 1", $a->argv[1]);
			if (dbm::is_result($r)) {
				$nick = $r["nickname"];
				$itemuid = $r["uid"];
			}
		}

		// Is it an item with uid=0?
		if ($nick == "") {
			$r = dba::fetch_first("SELECT `item`.`id`, `item`.`parent`, `item`.`author-name`, `item`.`author-link`,
				`item`.`author-avatar`, `item`.`network`, `item`.`uid`, `item`.`owner-link`, `item`.`body`
				FROM `item` WHERE `item`.`visible` AND NOT `item`.`deleted` AND NOT `item`.`moderated`
					AND `item`.`allow_cid` = ''  AND `item`.`allow_gid` = ''
					AND `item`.`deny_cid`  = '' AND `item`.`deny_gid`  = ''
					AND NOT `item`.`private` AND `item`.`uid` = 0
					AND `item`.`guid` = ? LIMIT 1", $a->argv[1]);
		}
		if (dbm::is_result($r)) {
			if ($r["id"] != $r["parent"]) {
				$r = dba::fetch_first("SELECT `id`, `author-name`, `author-link`, `author-avatar`, `network`, `body`, `uid`, `owner-link` FROM `item`
					WHERE `item`.`visible` AND NOT `item`.`deleted` AND NOT `item`.`moderated`
						AND `id` = ?", $r["parent"]);
			}
			if (($itemuid != local_user()) && local_user()) {
				// Do we know this contact but we haven't got this item?
				// Copy the wohle thread to our local storage so that we can interact.
				// We really should change this need for the future since it scales very bad.
				$contactid = get_contact($r['owner-link'], local_user());
				if ($contactid) {
					$items = dba::select('item', array(), array('parent' => $r["id"]), array('order' => array('id')));
					while ($item = dba::fetch($items)) {
						$itemcontactid = get_contact($item['owner-link'], local_user());
						if (!$itemcontactid) {
							$itemcontactid = $contactid;
						}
						unset($item['id']);
						$item['uid'] = local_user();
						$item['origin'] = 0;
						$item['contact-id'] = $itemcontactid;
						$local_copy = item_store($item, false, false, true);
						logger("Stored local copy for post ".$item['guid']." under id ".$local_copy, LOGGER_DEBUG);
					}
					dba::close($items);
				}
			}

			$profiledata = display_fetchauthor($a, $r);

			if (strstr(normalise_link($profiledata["url"]), normalise_link(App::get_baseurl()))) {
				$nickname = str_replace(normalise_link(App::get_baseurl())."/profile/", "", normalise_link($profiledata["url"]));

				if (($nickname != $a->user["nickname"])) {
					$r = dba::fetch_first("SELECT `profile`.`uid` AS `profile_uid`, `profile`.* , `contact`.`avatar-date` AS picdate, `user`.* FROM `profile`
						INNER JOIN `contact` on `contact`.`uid` = `profile`.`uid` INNER JOIN `user` ON `profile`.`uid` = `user`.`uid`
						WHERE `user`.`nickname` = ? AND `profile`.`is-default` AND `contact`.`self` LIMIT 1",
						$nickname
					);
					if (dbm::is_result($r)) {
						$profiledata = $r;
					}
					$profiledata["network"] = NETWORK_DFRN;
				} else {
					$profiledata = array();
				}
			}
		} else {
			$a->error = 404;
			notice(t('Item not found.') . EOL);
			return;
		}
	}

	profile_load($a, $nick, 0, $profiledata);
}

function display_fetchauthor($a, $item) {

	require_once("include/Contact.php");

	$profiledata = array();
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
		$profiledata["network"] = GetProfileUsername($profiledata["url"], "", false, true);

		$profiledata["address"] = "";
		$profiledata["about"] = "";
	}

	$profiledata = get_contact_details_by_url($profiledata["url"], local_user(), $profiledata);

	$profiledata["photo"] = App::remove_baseurl($profiledata["photo"]);

	if (local_user()) {
		if (in_array($profiledata["network"], array(NETWORK_DFRN, NETWORK_DIASPORA, NETWORK_OSTATUS))) {
			$profiledata["remoteconnect"] = App::get_baseurl()."/follow?url=".urlencode($profiledata["url"]);
		}
	} elseif ($profiledata["network"] == NETWORK_DFRN) {
		$connect = str_replace("/profile/", "/dfrn_request/", $profiledata["url"]);
		$profiledata["remoteconnect"] = $connect;
	}

	return($profiledata);
}

function display_content(App $a, $update = 0) {

	if ((get_config('system','block_public')) && (! local_user()) && (! remote_user())) {
		notice(t('Public access denied.') . EOL);
		return;
	}

	require_once('include/security.php');
	require_once('include/conversation.php');
	require_once('include/acl_selectors.php');


	$o = '';

	if ($update) {
		$nick = $_REQUEST['nick'];
	} else {
		$nick = (($a->argc > 1) ? $a->argv[1] : '');
	}

	if ($update) {
		$item_id = $_REQUEST['item_id'];
		$a->profile = array('uid' => intval($update), 'profile_uid' => intval($update));
	} else {
		$item_id = (($a->argc > 2) ? $a->argv[2] : 0);

		if ($a->argc == 2) {
			$nick = "";

			if (local_user()) {
				$r = dba::fetch_first("SELECT `id` FROM `item`
					WHERE `item`.`visible` AND NOT `item`.`deleted` AND NOT `item`.`moderated`
						AND `guid` = ? AND `uid` = ?", $a->argv[1], local_user());
				if (dbm::is_result($r)) {
					$item_id = $r["id"];
					$nick = $a->user["nickname"];
				}
			}

			if ($nick == "") {
				$r = dba::fetch_first("SELECT `user`.`nickname`, `item`.`id` FROM `item` STRAIGHT_JOIN `user` ON `user`.`uid` = `item`.`uid`
					WHERE `item`.`visible` AND NOT `item`.`deleted` AND NOT `item`.`moderated`
						AND `item`.`allow_cid` = ''  AND `item`.`allow_gid` = ''
						AND `item`.`deny_cid`  = '' AND `item`.`deny_gid`  = ''
						AND NOT `item`.`private` AND NOT `user`.`hidewall`
						AND `item`.`guid` = ?", $a->argv[1]);
				if (dbm::is_result($r)) {
					$item_id = $r["id"];
					$nick = $r["nickname"];
				}
			}
			if ($nick == "") {
				$r = dba::fetch_first("SELECT `item`.`id` FROM `item`
					WHERE `item`.`visible` AND NOT `item`.`deleted` AND NOT `item`.`moderated`
						AND `item`.`allow_cid` = ''  AND `item`.`allow_gid` = ''
						AND `item`.`deny_cid`  = '' AND `item`.`deny_gid`  = ''
						AND NOT `item`.`private` AND `item`.`uid` = 0
						AND `item`.`guid` = ?", $a->argv[1]);
				if (dbm::is_result($r)) {
					$item_id = $r["id"];
				}
			}
		}
	}

	if ($item_id && !is_numeric($item_id)) {
		$r = dba::select('item', array('id'), array('uri' => $item_id, 'uid' => $a->profile['uid']), array('limit' => 1));
		if (dbm::is_result($r)) {
			$item_id = $r["id"];
		} else {
			$item_id = false;
		}
	}

	if (!$item_id) {
		$a->error = 404;
		notice(t('Item not found.').EOL);
		return;
	}

	// We are displaying an "alternate" link if that post was public. See issue 2864
	$is_public = dba::exists('item', array('id' => $item_id, 'private' => 0, 'wall' => 1));
	if ($is_public) {
		$alternate = App::get_baseurl().'/display/'.$nick.'/'.$item_id.'.atom';
	} else {
		$alternate = '';
	}

	$a->page['htmlhead'] .= replace_macros(get_markup_template('display-head.tpl'),
				array('$alternate' => $alternate));

	$groups = array();

	$contact = null;
	$remote_contact = false;

	$contact_id = 0;

	if (is_array($_SESSION['remote'])) {
		foreach ($_SESSION['remote'] as $v) {
			if ($v['uid'] == $a->profile['uid']) {
				$contact_id = $v['cid'];
				break;
			}
		}
	}

	if ($contact_id) {
		$groups = init_groups_visitor($contact_id);
		$r = dba::fetch_first("SELECT * FROM `contact` WHERE `id` = ? AND `uid` = ? LIMIT 1",
			$contact_id,
			$a->profile['uid']
		);
		if (dbm::is_result($r)) {
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
	if (dbm::is_result($r)) {
		$a->page_contact = $r;
	}
	$is_owner = ((local_user()) && (local_user() == $a->profile['profile_uid']) ? true : false);

	if ($a->profile['hidewall'] && (! $is_owner) && (! $remote_contact)) {
		notice(t('Access to this profile has been restricted.') . EOL);
		return;
	}

	// We need the editor here to be able to reshare an item.

	if ($is_owner) {
		$x = array(
			'is_owner' => true,
			'allow_location' => $a->user['allow_location'],
			'default_location' => $a->user['default-location'],
			'nickname' => $a->user['nickname'],
			'lockstate' => ( (is_array($a->user)) && ((strlen($a->user['allow_cid'])) || (strlen($a->user['allow_gid'])) || (strlen($a->user['deny_cid'])) || (strlen($a->user['deny_gid']))) ? 'lock' : 'unlock'),
			'acl' => populate_acl($a->user, true),
			'bang' => '',
			'visitor' => 'block',
			'profile_uid' => local_user(),
			'acl_data' => construct_acl_data($a, $a->user), // For non-Javascript ACL selector
		);
		$o .= status_editor($a,$x,0,true);
	}

	$sql_extra = item_permissions_sql($a->profile['uid'],$remote_contact,$groups);

	if ($update) {
		$r = dba::p("SELECT `id` FROM `item` WHERE `item`.`uid` = ?
			AND `item`.`parent` = (SELECT `parent` FROM `item` WHERE `id` = ?)
			$sql_extra AND `unseen`",
			$a->profile['uid'],
			$item_id
		);

		if (dba::num_rows($r) == 0) {
			return '';
		}
	}

	$r = dba::p(item_query()." AND `item`.`uid` = ?
		AND `item`.`parent` = (SELECT `parent` FROM `item` WHERE `id` = ?)
		$sql_extra
		ORDER BY `parent` DESC, `gravity` ASC, `id` ASC",
		$a->profile['uid'],
		$item_id
	);

	if (!dbm::is_result($r) && local_user()) {
		// Check if this is another person's link to a post that we have
		$r = dba::fetch_first("SELECT `item`.uri FROM `item`
			WHERE (`item`.`id` = ? OR `item`.`uri` = ?)
			LIMIT 1",
			$item_id,
			$item_id
		);
		if (dbm::is_result($r)) {
			$item_uri = $r['uri'];

			$r = dba::p(item_query()." AND `item`.`uid` = ?
				AND `item`.`parent` = (SELECT `parent` FROM `item` WHERE `uri` = ? AND uid = ?)
				ORDER BY `parent` DESC, `gravity` ASC, `id` ASC",
				local_user(),
				$item_uri,
				local_user()
			);
		}
	}

	if (dbm::is_result($r)) {
		$s = dba::inArray($r);

		if ((local_user()) && (local_user() == $a->profile['uid'])) {
			$unseen = dba::select('item', array('id'), array('parent' => $s[0]['parent'], 'unseen' => 1), array('limit' => 1));
			if (dbm::is_result($unseen)) {
				dba::update('item', array('unseen' => 0), array('parent' => $s[0]['parent'], 'unseen' => 1));
			}
		}

		$items = conv_sort($s, "`commented`");

		if (!$update) {
			$o .= "<script> var netargs = '?f=&nick=" . $nick . "&item_id=" . $item_id . "'; </script>";
		}
		$o .= conversation($a, $items, 'display', $update);

		// Preparing the meta header
		require_once('include/bbcode.php');
		require_once("include/html2plain.php");
		$description = trim(html2plain(bbcode($s[0]["body"], false, false), 0, true));
		$title = trim(html2plain(bbcode($s[0]["title"], false, false), 0, true));
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
		$a->page['htmlhead'] .= '<meta name="twitter:image" content="'.$image.'" />'."\n";
		$a->page['htmlhead'] .= '<meta name="twitter:url" content="'.$s[0]["plink"].'" />'."\n";

		// Dublin Core
		$a->page['htmlhead'] .= '<meta name="DC.title" content="'.$title.'" />'."\n";
		$a->page['htmlhead'] .= '<meta name="DC.description" content="'.$description.'" />'."\n";

		// Open Graph
		$a->page['htmlhead'] .= '<meta property="og:type" content="website" />'."\n";
		$a->page['htmlhead'] .= '<meta property="og:title" content="'.$title.'" />'."\n";
		$a->page['htmlhead'] .= '<meta property="og:image" content="'.$image.'" />'."\n";
		$a->page['htmlhead'] .= '<meta property="og:url" content="'.$s[0]["plink"].'" />'."\n";
		$a->page['htmlhead'] .= '<meta property="og:description" content="'.$description.'" />'."\n";
		$a->page['htmlhead'] .= '<meta name="og:article:author" content="'.$author_name.'" />'."\n";
		// article:tag

		return $o;
	}

	$r = dba::fetch_first("SELECT `id`,`deleted` FROM `item` WHERE `id` = ? OR `uri` = ? LIMIT 1",
		$item_id,
		$item_id
	);
	if ($r) {
		if ($r['deleted']) {
			notice(t('Item has been removed.') . EOL);
		} else {
			notice(t('Permission denied.') . EOL);
		}
	} else {
		notice(t('Item not found.') . EOL);
	}

	return $o;
}

