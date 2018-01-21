<?php
/**
 * @file mod/network.php
 */
use Friendica\App;
use Friendica\Content\Feature;
use Friendica\Content\ForumManager;
use Friendica\Content\Nav;
use Friendica\Content\Widget;
use Friendica\Core\Addon;
use Friendica\Core\System;
use Friendica\Core\Config;
use Friendica\Core\PConfig;
use Friendica\Database\DBM;
use Friendica\Model\Contact;
use Friendica\Model\Group;
use Friendica\Model\Profile;
use Friendica\Module\Login;

require_once 'include/conversation.php';
require_once 'include/items.php';
require_once 'include/acl_selectors.php';

function network_init(App $a) {
	if (!local_user()) {
		notice(t('Permission denied.') . EOL);
		return;
	}

	$search = (x($_GET, 'search') ? escape_tags($_GET['search']) : '');

	if (($search != '') && !empty($_GET['submit'])) {
		goaway('search?search='.urlencode($search));
	}

	if (x($_GET, 'save')) {
		$exists = dba::exists('search', ['uid' => local_user(), 'term' => $search]);
		if (!$exists) {
			dba::insert('search', ['uid' => local_user(), 'term' => $search]);
		}
	}
	if (x($_GET, 'remove')) {
		dba::delete('search', ['uid' => local_user(), 'term' => $search]);
	}

	$is_a_date_query = false;

	$group_id = (($a->argc > 1 && is_numeric($a->argv[1])) ? intval($a->argv[1]) : 0);

	$cid = 0;
	if (x($_GET, 'cid') && intval($_GET['cid']) != 0) {
		$cid = $_GET['cid'];
		$_GET['nets'] = 'all';
		$group_id = 0;
	}

	PConfig::set(local_user(), 'network.view', 'group.selected', $group_id);

	if ($a->argc > 1) {
		for ($x = 1; $x < $a->argc; $x ++) {
			if (is_a_date_arg($a->argv[$x])) {
				$is_a_date_query = true;
				break;
			}
		}
	}

	// convert query string to array. remove friendica args
	$query_array = [];
	$query_string = str_replace($a->cmd."?", "", $a->query_string);
	parse_str($query_string, $query_array);
	array_shift($query_array);

	// fetch last used network view and redirect if needed
	if (!$is_a_date_query) {
		$sel_tabs = network_query_get_sel_tab($a);
		$sel_nets = network_query_get_sel_net();
		$sel_groups = network_query_get_sel_group($a);
		$last_sel_tabs = PConfig::get(local_user(), 'network.view','tab.selected');
		$last_sel_nets = PConfig::get(local_user(), 'network.view', 'net.selected');
		$last_sel_groups = PConfig::get(local_user(), 'network.view', 'group.selected');

		$remember_tab = ($sel_tabs[0] === 'active' && is_array($last_sel_tabs) && $last_sel_tabs[0] !== 'active');
		$remember_net = ($sel_nets === false && $last_sel_nets && $last_sel_nets !== 'all');
		$remember_group = ($sel_groups === false && $last_sel_groups && $last_sel_groups != 0);

		$net_baseurl = '/network';
		$net_args = [];

		if ($remember_group) {
			$net_baseurl .= '/' . $last_sel_groups; // Note that the group number must come before the "/new" tab selection
		} elseif ($sel_groups !== false) {
			$net_baseurl .= '/' . $sel_groups;
		}

		if ($remember_tab) {
			// redirect if current selected tab is '/network' and
			// last selected tab is _not_ '/network?f=&order=comment'.
			// and this isn't a date query

			$tab_baseurls = [
				'',		//all
				'',		//postord
				'',		//conv
				'/new',		//new
				'',		//starred
				'',		//bookmarked
				'',		//spam
			];
			$tab_args = [
				'f=&order=comment',	//all
				'f=&order=post',	//postord
				'f=&conv=1',		//conv
				'',			//new
				'f=&star=1',		//starred
				'f=&bmark=1',		//bookmarked
				'f=&spam=1',		//spam
			];

			$k = array_search('active', $last_sel_tabs);

			if ($k != 3) {
				$net_baseurl .= $tab_baseurls[$k];

				// parse out tab queries
				$dest_qa = [];
				$dest_qs = $tab_args[$k];
				parse_str($dest_qs, $dest_qa);
				$net_args = array_merge($net_args, $dest_qa);
			} else {
				$remember_tab = false;
			}
		} elseif ($sel_tabs[4] === 'active') {
			// The '/new' tab is selected
			$remember_group = false;
		}

		if ($remember_net) {
			$net_args['nets'] = $last_sel_nets;
		} elseif ($sel_nets!==false) {
			$net_args['nets'] = $sel_nets;
		}

		if ($remember_tab || $remember_net || $remember_group) {
			$net_args = array_merge($query_array, $net_args);
			$net_queries = build_querystring($net_args);

			$redir_url = ($net_queries ? $net_baseurl."?".$net_queries : $net_baseurl);

			goaway(System::baseUrl() . $redir_url);
		}
	}

	// If nets is set to all, unset it
	if (x($_GET, 'nets') && $_GET['nets'] === 'all') {
		unset($_GET['nets']);
	}


	if (!x($a->page, 'aside')) {
		$a->page['aside'] = '';
	}

	$a->page['aside'] .= (Feature::isEnabled(local_user(), 'groups') ? Group::sidebarWidget('network/0', 'network', 'standard', $group_id) : '');
	$a->page['aside'] .= (Feature::isEnabled(local_user(), 'forumlist_widget') ? ForumManager::widget(local_user(), $cid) : '');
	$a->page['aside'] .= posted_date_widget('network', local_user(),false);
	$a->page['aside'] .= Widget::networks('network', (x($_GET, 'nets') ? $_GET['nets'] : ''));
	$a->page['aside'] .= saved_searches($search);
	$a->page['aside'] .= Widget::fileAs('network', (x($_GET, 'file') ? $_GET['file'] : ''));
}

function saved_searches($search) {

	if (!Feature::isEnabled(local_user(),'savedsearch')) {
		return '';
	}

	$a = get_app();

	$srchurl = '/network?f='
		. ((x($_GET,'cid'))   ? '&cid='   . $_GET['cid']   : '')
		. ((x($_GET,'star'))  ? '&star='  . $_GET['star']  : '')
		. ((x($_GET,'bmark')) ? '&bmark=' . $_GET['bmark'] : '')
		. ((x($_GET,'conv'))  ? '&conv='  . $_GET['conv']  : '')
		. ((x($_GET,'nets'))  ? '&nets='  . $_GET['nets']  : '')
		. ((x($_GET,'cmin'))  ? '&cmin='  . $_GET['cmin']  : '')
		. ((x($_GET,'cmax'))  ? '&cmax='  . $_GET['cmax']  : '')
		. ((x($_GET,'file'))  ? '&file='  . $_GET['file']  : '');
	;

	$o = '';

	$terms = dba::select('search', ['id', 'term'], ['uid' => local_user()]);
	$saved = [];

	while ($rr = dba::fetch($terms)) {
		$saved[] = [
			'id'          => $rr['id'],
			'term'        => $rr['term'],
			'encodedterm' => urlencode($rr['term']),
			'delete'      => t('Remove term'),
			'selected'    => ($search==$rr['term']),
		];
	}

	$tpl = get_markup_template("saved_searches_aside.tpl");
	$o = replace_macros($tpl, [
		'$title'     => t('Saved Searches'),
		'$add'       => t('add'),
		'$searchbox' => search($search,'netsearch-box',$srchurl,true),
		'$saved'     => $saved,
	]);

	return $o;
}

/**
 * Return selected tab from query
 *
 * urls -> returns
 * 		'/network'					=> $no_active = 'active'
 * 		'/network?f=&order=comment'	=> $comment_active = 'active'
 * 		'/network?f=&order=post'	=> $postord_active = 'active'
 * 		'/network?f=&conv=1',		=> $conv_active = 'active'
 * 		'/network/new',				=> $new_active = 'active'
 * 		'/network?f=&star=1',		=> $starred_active = 'active'
 * 		'/network?f=&bmark=1',		=> $bookmarked_active = 'active'
 * 		'/network?f=&spam=1',		=> $spam_active = 'active'
 *
 * @return Array ($no_active, $comment_active, $postord_active, $conv_active, $new_active, $starred_active, $bookmarked_active, $spam_active);
 */
function network_query_get_sel_tab(App $a) {
	$no_active='';
	$starred_active = '';
	$new_active = '';
	$bookmarked_active = '';
	$all_active = '';
	$conv_active = '';
	$spam_active = '';
	$postord_active = '';

	if (($a->argc > 1 && $a->argv[1] === 'new')
		|| ($a->argc > 2 && $a->argv[2] === 'new')) {
			$new_active = 'active';
	}

	if (x($_GET,'star')) {
		$starred_active = 'active';
	}

	if (x($_GET,'bmark')) {
		$bookmarked_active = 'active';
	}

	if (x($_GET,'conv')) {
		$conv_active = 'active';
	}

	if (x($_GET,'spam')) {
		$spam_active = 'active';
	}



	if (($new_active == '')
		&& ($starred_active == '')
		&& ($bookmarked_active == '')
		&& ($conv_active == '')
		&& ($spam_active == '')) {
			$no_active = 'active';
	}

	if ($no_active=='active' && x($_GET,'order')) {
		switch($_GET['order']){
		 case 'post': $postord_active = 'active'; $no_active=''; break;
		 case 'comment' : $all_active = 'active'; $no_active=''; break;
		}
	}

	return [$no_active, $all_active, $postord_active, $conv_active, $new_active, $starred_active, $bookmarked_active, $spam_active];
}

/**
 * @brief Return selected network from query
 * @return string Name of the selected network
 */
function network_query_get_sel_net() {
	$network = false;

	if (x($_GET,'nets')) {
		$network = $_GET['nets'];
	}

	return $network;
}

function network_query_get_sel_group(App $a) {
	$group = false;

	if ($a->argc >= 2 && is_numeric($a->argv[1])) {
		$group = $a->argv[1];
	}

	return $group;
}

/**
 * @brief Sets the pager data and returns SQL
 *
 * @param App $a The global App
 * @param integer $update Used for the automatic reloading
 * @return string SQL with the appropriate LIMIT clause
 */
function networkPager($a, $update) {
	if ($update) {
		// only setup pagination on initial page view
		return ' LIMIT 100';

	}

	//  check if we serve a mobile device and get the user settings
	//  accordingly
	if ($a->is_mobile) {
		$itemspage_network = PConfig::get(local_user(),'system','itemspage_mobile_network');
		$itemspage_network = ((intval($itemspage_network)) ? $itemspage_network : 20);
	} else {
		$itemspage_network = PConfig::get(local_user(),'system','itemspage_network');
		$itemspage_network = ((intval($itemspage_network)) ? $itemspage_network : 40);
	}

	//  now that we have the user settings, see if the theme forces
	//  a maximum item number which is lower then the user choice
	if (($a->force_max_items > 0) && ($a->force_max_items < $itemspage_network)) {
		$itemspage_network = $a->force_max_items;
	}

	$a->set_pager_itemspage($itemspage_network);

	return sprintf(" LIMIT %d, %d ",intval($a->pager['start']), intval($a->pager['itemspage']));
}

/**
 * @brief Sets items as seen
 *
 * @param array $condition The array with the SQL condition
 */
function networkSetSeen($condition) {
	if (empty($condition)) {
		return;
	}

	$unseen = dba::exists('item', $condition);

	if ($unseen) {
		$r = dba::update('item', ['unseen' => false], $condition);
	}
}

/**
 * @brief Create the conversation HTML
 *
 * @param App $a The global App
 * @param array $items Items of the conversation
 * @param string $mode Display mode for the conversation
 * @param integer $update Used for the automatic reloading
 * @return string HTML of the conversation
 */
function networkConversation($a, $items, $mode, $update) {
	// Set this so that the conversation function can find out contact info for our wall-wall items
	$a->page_contact = $a->contact;

	$o = conversation($a, $items, $mode, $update);

	if (!$update) {
		if (PConfig::get(local_user(), 'system', 'infinite_scroll')) {
			$o .= scroll_loader();
		} else {
			$o .= alt_pager($a, count($items));
		}
	}

	return $o;
}

function network_content(App $a, $update = 0) {
	if (!local_user()) {
		return Login::form();
	}

	/// @TODO Is this really necessary? $a is already available to hooks
	$arr = ['query' => $a->query_string];
	Addon::callHooks('network_content_init', $arr);

	$nouveau = false;

	if ($a->argc > 1) {
		for ($x = 1; $x < $a->argc; $x ++) {
			if ($a->argv[$x] === 'new') {
				$nouveau = true;
			}
		}
	}

	if (x($_GET,'file')) {
		$nouveau = true;
	}

	if ($nouveau) {
		$o = networkFlatView($a, $update);
	} else {
		$o = networkThreadedView($a, $update);
	}

	return $o;
}

/**
 * @brief Get the network content in flat view
 *
 * @param App $a The global App
 * @param integer $update Used for the automatic reloading
 * @return string HTML of the network content in flat view
 */
function networkFlatView(App $a, $update = 0) {

	// Rawmode is used for fetching new content at the end of the page
	$rawmode = (isset($_GET["mode"]) AND ($_GET["mode"] == "raw"));

	if (isset($_GET["last_id"])) {
		$last_id = intval($_GET["last_id"]);
	} else {
		$last_id = 0;
	}

	$o = '';

	$file = ((x($_GET,'file')) ? $_GET['file'] : '');

	PConfig::set(local_user(), 'network.view', 'net.selected', 'all');

	if (!$update && !$rawmode) {
		$tabs = network_tabs($a);
		$o .= $tabs;

		Nav::setSelected('network');

		$x = [
			'is_owner' => true,
			'allow_location' => $a->user['allow_location'],
			'default_location' => $a->user['default-location'],
			'nickname' => $a->user['nickname'],
			'lockstate'=> (((is_array($a->user) &&
					((strlen($a->user['allow_cid'])) || (strlen($a->user['allow_gid'])) ||
					(strlen($a->user['deny_cid'])) || (strlen($a->user['deny_gid']))))) ? 'lock' : 'unlock'),
			'default_perms'	=> get_acl_permissions($a->user),
			'acl'	=> populate_acl($a->user, true),
			'bang'	=> '',
			'visitor' => 'block',
			'profile_uid' => local_user(),
			'content' => '',
		];

		$o .= status_editor($a, $x);

		if (!Config::get('theme', 'hide_eventlist')) {
			$o .= Profile::getBirthdays();
			$o .= Profile::getEvents();
		}
	}

	if (strlen($file)) {
		$sql_post_table .= sprintf("INNER JOIN (SELECT `oid` FROM `term` WHERE `term` = '%s' AND `otype` = %d AND `type` = %d AND `uid` = %d ORDER BY `tid` DESC) AS `term` ON `item`.`id` = `term`.`oid` ",
				dbesc(protect_sprintf($file)), intval(TERM_OBJ_POST), intval(TERM_FILE), intval(local_user()));
	} else {
		$sql_post_table = " INNER JOIN `thread` ON `thread`.`iid` = `item`.`parent`";
	}

	$pager_sql = networkPager($a, $update);

	// show all items unthreaded in reverse created date order
	$items = q("SELECT %s FROM `item` $sql_post_table %s
		WHERE %s AND `item`.`uid` = %d
		ORDER BY `item`.`id` DESC $pager_sql ",
		item_fieldlists(), item_joins(), item_condition(),
		intval($_SESSION['uid'])
	);

	$condition = ['unseen' => true, 'uid' => local_user()];
	networkSetSeen($condition);

	$mode = 'network-new';
	$o .= networkConversation($a, $items, $mode, $update);

	return $o;
}

/**
 * @brief Get the network content in threaded view
 *
 * @param App $a The global App
 * @param integer $update Used for the automatic reloading
 * @return string HTML of the network content in flat view
 */
function networkThreadedView(App $a, $update = 0) {

	// Rawmode is used for fetching new content at the end of the page
	$rawmode = (isset($_GET["mode"]) AND ($_GET["mode"] == "raw"));

	if (isset($_GET["last_received"]) && isset($_GET["last_commented"]) && isset($_GET["last_created"]) && isset($_GET["last_id"])) {
		$last_received = DBM::date($_GET["last_received"]);
		$last_commented = DBM::date($_GET["last_commented"]);
		$last_created = DBM::date($_GET["last_created"]);
		$last_id = intval($_GET["last_id"]);
	} else {
		$last_received = '';
		$last_commented = '';
		$last_created = '';
		$last_id = 0;
	}

	$datequery = $datequery2 = '';

	$gid = 0;

	if ($a->argc > 1) {
		for ($x = 1; $x < $a->argc; $x ++) {
			if (is_a_date_arg($a->argv[$x])) {
				if ($datequery) {
					$datequery2 = escape_tags($a->argv[$x]);
				} else {
					$datequery = escape_tags($a->argv[$x]);
					$_GET['order'] = 'post';
				}
			} elseif (intval($a->argv[$x])) {
				$gid = intval($a->argv[$x]);
				$def_acl = ['allow_gid' => '<' . $gid . '>'];
			}
		}
	}

	$o = '';

	$cid = ((x($_GET,'cid')) ? intval($_GET['cid']) : 0);
	$star = ((x($_GET,'star')) ? intval($_GET['star']) : 0);
	$bmark = ((x($_GET,'bmark')) ? intval($_GET['bmark']) : 0);
	$order = ((x($_GET,'order')) ? notags($_GET['order']) : 'comment');
	$conv = ((x($_GET,'conv')) ? intval($_GET['conv']) : 0);
	$nets = ((x($_GET,'nets')) ? $_GET['nets'] : '');

	if ($cid) {
		$def_acl = ['allow_cid' => '<' . intval($cid) . '>'];
	}

	if ($nets) {
		$r = dba::select('contact', ['id'], ['uid' => local_user(), 'network' => $nets], ['self' => false]);

		$str = '';
		while ($rr = dba::fetch($r)) {
			$str .= '<' . $rr['id'] . '>';
		}
		if (strlen($str)) {
			$def_acl = ['allow_cid' => $str];
		}
	}
	PConfig::set(local_user(), 'network.view', 'net.selected', ($nets ? $nets : 'all'));

	if (!$update && !$rawmode) {
		$tabs = network_tabs($a);
		$o .= $tabs;

		if ($gid) {
			if (($t = Contact::getOStatusCountByGroupId($gid)) && !PConfig::get(local_user(), 'system', 'nowarn_insecure')) {
				notice(tt("Warning: This group contains %s member from a network that doesn't allow non public messages.",
						"Warning: This group contains %s members from a network that doesn't allow non public messages.",
						$t) . EOL);
				notice(t("Messages in this group won't be send to these receivers.").EOL);
			}
		}

		Nav::setSelected('network');

		$content = "";

		if ($cid) {
			// If $cid belongs to a communitity forum or a privat goup,.add a mention to the status editor
			$condition = ["`id` = ? AND (`forum` OR `prv`)", $cid];
			$contact = dba::selectFirst('contact', ['addr', 'nick'], $condition);
			if (DBM::isResult($contact)) {
				if ($contact["addr"] != '') {
					$content = "!".$contact["addr"];
				} else {
					$content = "!".$contact["nick"]."+".$cid;
				}
			}
		}

		$x = [
			'is_owner' => true,
			'allow_location' => $a->user['allow_location'],
			'default_location' => $a->user['default-location'],
			'nickname' => $a->user['nickname'],
			'lockstate'=> ((($gid) || ($cid) || ($nets) || (is_array($a->user) &&
					((strlen($a->user['allow_cid'])) || (strlen($a->user['allow_gid'])) ||
					(strlen($a->user['deny_cid'])) || (strlen($a->user['deny_gid']))))) ? 'lock' : 'unlock'),
			'default_perms'	=> get_acl_permissions($a->user),
			'acl'	=> populate_acl((($gid || $cid || $nets) ? $def_acl : $a->user), true),
			'bang'	=> (($gid || $cid || $nets) ? '!' : ''),
			'visitor' => 'block',
			'profile_uid' => local_user(),
			'content' => $content,
		];

		$o .= status_editor($a, $x);
	}

	// We don't have to deal with ACLs on this page. You're looking at everything
	// that belongs to you, hence you can see all of it. We will filter by group if
	// desired.

	$sql_post_table = "";
	$sql_options  = (($star) ? " AND `thread`.`starred` " : '');
	$sql_options .= (($bmark) ? " AND `thread`.`bookmark` " : '');
	$sql_extra = $sql_options;
	$sql_extra2 = "";
	$sql_extra3 = "";
	$sql_table = "`thread`";
	$sql_parent = "`iid`";

	if ($update) {
		$sql_table = "`item`";
		$sql_parent = "`parent`";
		$sql_post_table = " INNER JOIN `thread` ON `thread`.`iid` = `item`.`parent`";
	}

	$sql_nets = (($nets) ? sprintf(" AND $sql_table.`network` = '%s' ", dbesc($nets)) : '');
	$sql_tag_nets = (($nets) ? sprintf(" AND `item`.`network` = '%s' ", dbesc($nets)) : '');

	if ($gid) {
		$group = dba::selectFirst('group', ['name'], ['id' => $gid, 'uid' => $_SESSION['uid']]);
		if (!DBM::isResult($group)) {
			if ($update) {
				killme();
			}
			notice(t('No such group') . EOL);
			goaway('network/0');
			// NOTREACHED
		}

		$contacts = Group::expand([$gid]);

		if ((is_array($contacts)) && count($contacts)) {
			$contact_str_self = "";

			$contact_str = implode(',',$contacts);
			$self = dba::selectFirst('contact', ['id'], ['uid' => $_SESSION['uid'], 'self' => true]);
			if (DBM::isResult($self)) {
				$contact_str_self = $self["id"];
			}

			$sql_post_table .= " INNER JOIN `item` AS `temp1` ON `temp1`.`id` = ".$sql_table.".".$sql_parent;
			$sql_extra3 .= " AND (`thread`.`contact-id` IN ($contact_str) ";
			$sql_extra3 .= " OR (`thread`.`contact-id` = '$contact_str_self' AND `temp1`.`allow_gid` LIKE '".protect_sprintf('%<'.intval($gid).'>%')."' AND `temp1`.`private`))";
		} else {
			$sql_extra3 .= " AND false ";
			info(t('Group is empty'));
		}

		$o = replace_macros(get_markup_template("section_title.tpl"),[
			'$title' => t('Group: %s', $group['name'])
		]) . $o;

	} elseif ($cid) {
		$fields = ['id', 'name', 'network', 'writable', 'nurl',
				'forum', 'prv', 'contact-type', 'addr', 'thumb', 'location'];
		$condition = ["`id` = ? AND (NOT `blocked` OR `pending`)", $cid];
		$contact = dba::selectFirst('contact', $fields, $condition);
		if (DBM::isResult($contact)) {
			$sql_extra = " AND ".$sql_table.".`contact-id` = ".intval($cid);

			$entries[0] = [
				'id' => 'network',
				'name' => htmlentities($contact['name']),
				'itemurl' => defaults($contact, 'addr', $contact['nurl']),
				'thumb' => proxy_url($contact['thumb'], false, PROXY_SIZE_THUMB),
				'details' => $contact['location'],
			];

			$entries[0]["account_type"] = Contact::getAccountType($contact);

			$o = replace_macros(get_markup_template("viewcontact_template.tpl"),[
				'contacts' => $entries,
				'id' => 'network',
			]) . $o;

			if ($contact['network'] === NETWORK_OSTATUS && $contact['writable'] && !PConfig::get(local_user(),'system','nowarn_insecure')) {
				notice(t('Private messages to this person are at risk of public disclosure.') . EOL);
			}

		} else {
			notice(t('Invalid contact.') . EOL);
			goaway('network');
			// NOTREACHED
		}
	}

	if (!$gid && !$cid && !$update && !Config::get('theme', 'hide_eventlist')) {
		$o .= Profile::getBirthdays();
		$o .= Profile::getEvents();
	}

	if ($datequery) {
		$sql_extra3 .= protect_sprintf(sprintf(" AND $sql_table.created <= '%s' ", dbesc(datetime_convert(date_default_timezone_get(),'',$datequery))));
	}
	if ($datequery2) {
		$sql_extra3 .= protect_sprintf(sprintf(" AND $sql_table.created >= '%s' ", dbesc(datetime_convert(date_default_timezone_get(),'',$datequery2))));
	}

	$sql_order = "";
	$order_mode = "received";

	if ($conv) {
		$sql_extra3 .= " AND $sql_table.`mention`";
	}

	// Normal conversation view
	if ($order === 'post') {
		$ordering = "`created`";
		if ($sql_order == "") {
			$order_mode = "created";
		}
	} else {
		$ordering = "`commented`";
		if ($sql_order == "") {
			$order_mode = "commented";
		}
	}

	if ($sql_order == "") {
		$sql_order = "$sql_table.$ordering";
	}

	if (x($_GET, 'offset')) {
		$sql_range = sprintf(" AND $sql_order <= '%s'", dbesc($_GET["offset"]));
	} else {
		$sql_range = '';
	}

	$pager_sql = networkPager($a, $update);

	$last_date = '';

	switch ($order_mode) {
		case 'received':
			if ($last_received != '') {
				$last_date = $last_received;
				$sql_range .= sprintf(" AND $sql_table.`received` < '%s'", dbesc($last_received));
				$a->set_pager_page(1);
				$pager_sql = sprintf(" LIMIT %d, %d ",intval($a->pager['start']), intval($a->pager['itemspage']));
			}
			break;
		case 'commented':
			if ($last_commented != '') {
				$last_date = $last_commented;
				$sql_range .= sprintf(" AND $sql_table.`commented` < '%s'", dbesc($last_commented));
				$a->set_pager_page(1);
				$pager_sql = sprintf(" LIMIT %d, %d ",intval($a->pager['start']), intval($a->pager['itemspage']));
			}
			break;
		case 'created':
			if ($last_created != '') {
				$last_date = $last_created;
				$sql_range .= sprintf(" AND $sql_table.`created` < '%s'", dbesc($last_created));
				$a->set_pager_page(1);
				$pager_sql = sprintf(" LIMIT %d, %d ",intval($a->pager['start']), intval($a->pager['itemspage']));
			}
			break;
		case 'id':
			if (($last_id > 0) && ($sql_table == "`thread`")) {
				$sql_range .= sprintf(" AND $sql_table.`iid` < '%s'", dbesc($last_id));
				$a->set_pager_page(1);
				$pager_sql = sprintf(" LIMIT %d, %d ",intval($a->pager['start']), intval($a->pager['itemspage']));
			}
			break;
	}

	// Fetch a page full of parent items for this page
	if ($update) {
		if (Config::get("system", "like_no_comment")) {
			$sql_extra4 = " AND `item`.`verb` = '".ACTIVITY_POST."'";
		} else {
			$sql_extra4 = "";
		}
		$r = q("SELECT `item`.`parent` AS `item_id`, `item`.`network` AS `item_network`, `contact`.`uid` AS `contact_uid`, $sql_order AS `order_date`
			FROM $sql_table $sql_post_table INNER JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
			AND (NOT `contact`.`blocked` OR `contact`.`pending`)
			WHERE `item`.`uid` = %d AND `item`.`visible` AND NOT `item`.`deleted` $sql_extra4
			AND NOT `item`.`moderated` AND `item`.`unseen`
			$sql_extra3 $sql_extra $sql_range $sql_nets
			ORDER BY `order_date` DESC LIMIT 100",
			intval(local_user())
		);
	} else {
		$r = q("SELECT `thread`.`iid` AS `item_id`, `thread`.`network` AS `item_network`, `contact`.`uid` AS `contact_uid`, $sql_order AS `order_date`
			FROM $sql_table $sql_post_table STRAIGHT_JOIN `contact` ON `contact`.`id` = `thread`.`contact-id`
			AND (NOT `contact`.`blocked` OR `contact`.`pending`)
			WHERE `thread`.`uid` = %d AND `thread`.`visible` AND NOT `thread`.`deleted`
			AND NOT `thread`.`moderated`
			$sql_extra2 $sql_extra3 $sql_range $sql_extra $sql_nets
			ORDER BY $sql_order DESC $pager_sql",
			intval(local_user())
		);
	}

	// Only show it when unfiltered (no groups, no networks, ...)
	if (Config::get('system', 'comment_public') && in_array($nets, ['', NETWORK_DFRN, NETWORK_DIASPORA, NETWORK_OSTATUS])
		&& (strlen($sql_extra . $sql_extra2 . $sql_extra3) == 0)) {

		if (DBM::isResult($r)) {
			$top_limit = current($r)['order_date'];
			$bottom_limit = end($r)['order_date'];
		} else {
			$top_limit = datetime_convert();
			$bottom_limit = datetime_convert();
		}

		// When checking for updates we need to fetch from the newest date to the newest date before
		if ($update && !empty($_SESSION['network_last_date']) && ($bottom_limit > $_SESSION['network_last_date'])) {
			$bottom_limit = $_SESSION['network_last_date'];
		}
		$_SESSION['network_last_date'] = $top_limit;

		if ($last_date > $top_limit) {
			$top_limit = $last_date;
		} elseif ($a->pager['page'] == 1) {
			// Highest possible top limit when we are on the first page
			$top_limit = datetime_convert();
		}

		$items = dba::p("SELECT `item`.`id` AS `item_id`, `item`.`network` AS `item_network`, `contact`.`uid` AS `contact_uid` FROM `item`
			INNER JOIN (SELECT `oid` FROM `term` WHERE `term` IN
				(SELECT SUBSTR(`term`, 2) FROM `search` WHERE `uid` = ? AND `term` LIKE '#%') AND `otype` = ? AND `type` = ? AND `uid` = 0) AS `term`
			ON `item`.`id` = `term`.`oid`
			INNER JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
			WHERE `item`.`uid` = 0 AND `item`.$ordering < ? AND `item`.$ordering > ?".$sql_tag_nets,
			local_user(), TERM_OBJ_POST, TERM_HASHTAG, $top_limit, $bottom_limit);
		$data = dba::inArray($items);

		if (count($data) > 0) {
			logger('Tagged items: '.count($data).' - '.$bottom_limit." - ".$top_limit.' - '.local_user()); //$last_date);
			$r = array_merge($r, $data);
		}
	}

	// Then fetch all the children of the parents that are on this page

	$parents_arr = [];
	$parents_str = '';
	$date_offset = "";

	$items = [];
	if (DBM::isResult($r)) {
		foreach ($r as $rr) {
			if (!in_array($rr['item_id'], $parents_arr)) {
				$parents_arr[] = $rr['item_id'];
			}
		}

		$parents_str = implode(", ", $parents_arr);

		// splitted into separate queries to avoid the problem with very long threads
		// so always the last X comments are loaded
		// This problem can occur expecially with imported facebook posts
		$max_comments = Config::get("system", "max_comments");
		if ($max_comments == 0) {
			$max_comments = 100;
		}

		foreach ($parents_arr AS $parents) {
			$thread_items = dba::p(item_query() . " AND `item`.`parent` = ?
				ORDER BY `item`.`commented` DESC LIMIT " . intval($max_comments + 1),
				$parents
			);

			if (DBM::isResult($thread_items)) {
				$items = array_merge($items, dba::inArray($thread_items));
			}
		}
		$items = conv_sort($items, $ordering);
	}

	if (x($_GET, 'offset')) {
		$date_offset = $_GET["offset"];
	} elseif(count($items)) {
		$date_offset = $items[0][$order_mode];
	} else {
		$date_offset = '';
	}

	$a->page_offset = $date_offset;

	// We aren't going to try and figure out at the item, group, and page
	// level which items you've seen and which you haven't. If you're looking
	// at the top level network page just mark everything seen.

	if (!$gid && !$cid && !$star) {
		$condition = ['unseen' => true, 'uid' => local_user()];
		networkSetSeen($condition);
	} elseif ($parents_str) {
		$condition = ["`uid` = ? AND `unseen` AND `parent` IN (" . dbesc($parents_str) . ")", local_user()];
		networkSetSeen($condition);
	}


	$mode = 'network';
	$o .= networkConversation($a, $items, $mode, $update);

	return $o;
}

/**
 * @brief Get the network tabs menu
 *
 * @param App $a The global App
 * @return string Html of the networktab
 */
function network_tabs(App $a)
{
	// item filter tabs
	/// @TODO fix this logic, reduce duplication
	/// $a->page['content'] .= '<div class="tabs-wrapper">';
	list($no_active, $all_active, $postord_active, $conv_active, $new_active, $starred_active, $bookmarked_active, $spam_active) = network_query_get_sel_tab($a);

	// if no tabs are selected, defaults to comments
	if ($no_active == 'active') {
		$all_active = 'active';
	}

	$cmd = $a->cmd;

	// tabs
	$tabs = [
		[
			'label'	=> t('Commented Order'),
			'url'	=> str_replace('/new', '', $cmd) . '?f=&order=comment' . ((x($_GET,'cid')) ? '&cid=' . $_GET['cid'] : ''),
			'sel'	=> $all_active,
			'title'	=> t('Sort by Comment Date'),
			'id'	=> 'commented-order-tab',
			'accesskey' => "e",
		],
		[
			'label'	=> t('Posted Order'),
			'url'	=> str_replace('/new', '', $cmd) . '?f=&order=post' . ((x($_GET,'cid')) ? '&cid=' . $_GET['cid'] : ''),
			'sel'	=> $postord_active,
			'title'	=> t('Sort by Post Date'),
			'id'	=> 'posted-order-tab',
			'accesskey' => "t",
		],
	];

	if (Feature::isEnabled(local_user(),'personal_tab')) {
		$tabs[] = [
			'label'	=> t('Personal'),
			'url'	=> str_replace('/new', '', $cmd) . ((x($_GET,'cid')) ? '/?f=&cid=' . $_GET['cid'] : '/?f=') . '&conv=1',
			'sel'	=> $conv_active,
			'title'	=> t('Posts that mention or involve you'),
			'id'	=> 'personal-tab',
			'accesskey' => "r",
		];
	}

	if (Feature::isEnabled(local_user(),'new_tab')) {
		$tabs[] = [
			'label'	=> t('New'),
			'url'	=> 'network/new' . ((x($_GET,'cid')) ? '/?f=&cid=' . $_GET['cid'] : ''),
			'sel'	=> $new_active,
			'title'	=> t('Activity Stream - by date'),
			'id'	=> 'activitiy-by-date-tab',
			'accesskey' => "w",
		];
	}

	if (Feature::isEnabled(local_user(),'link_tab')) {
		$tabs[] = [
			'label'	=> t('Shared Links'),
			'url'	=> str_replace('/new', '', $cmd) . ((x($_GET,'cid')) ? '/?f=&cid=' . $_GET['cid'] : '/?f=') . '&bmark=1',
			'sel'	=> $bookmarked_active,
			'title'	=> t('Interesting Links'),
			'id'	=> 'shared-links-tab',
			'accesskey' => "b",
		];
	}

	if (Feature::isEnabled(local_user(),'star_posts')) {
		$tabs[] = [
			'label'	=> t('Starred'),
			'url'	=> str_replace('/new', '', $cmd) . ((x($_GET,'cid')) ? '/?f=&cid=' . $_GET['cid'] : '/?f=') . '&star=1',
			'sel'	=> $starred_active,
			'title'	=> t('Favourite Posts'),
			'id'	=> 'starred-posts-tab',
			'accesskey' => "m",
		];
	}

	// save selected tab, but only if not in file mode
	if (!x($_GET,'file')) {
		PConfig::set(local_user(), 'network.view','tab.selected',[$all_active, $postord_active, $conv_active, $new_active, $starred_active, $bookmarked_active, $spam_active]);
	}

	$arr = ['tabs' => $tabs];
	Addon::callHooks('network_tabs', $arr);

	$tpl = get_markup_template('common_tabs.tpl');

	return replace_macros($tpl, ['$tabs' => $arr['tabs']]);

	// --- end item filter tabs
}
