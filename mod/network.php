<?php

/**
 * @file mod/network.php
 */

use Friendica\App;
use Friendica\Content\Feature;
use Friendica\Content\ForumManager;
use Friendica\Content\Nav;
use Friendica\Content\Pager;
use Friendica\Content\Widget;
use Friendica\Content\Text\HTML;
use Friendica\Core\ACL;
use Friendica\Core\Config;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\Logger;
use Friendica\Core\PConfig;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Core\Session;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Group;
use Friendica\Model\Item;
use Friendica\Model\Profile;
use Friendica\Model\Term;
use Friendica\Module\Login;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Proxy as ProxyUtils;
use Friendica\Util\Strings;

function network_init(App $a)
{
	if (!local_user()) {
		notice(L10n::t('Permission denied.') . EOL);
		return;
	}

	Hook::add('head', __FILE__, 'network_infinite_scroll_head');

	$is_a_date_query = false;

	$group_id = (($a->argc > 1 && is_numeric($a->argv[1])) ? intval($a->argv[1]) : 0);

	$cid = 0;
	if (!empty($_GET['cid'])) {
		$cid = $_GET['cid'];
		$_GET['nets'] = '';
		$group_id = 0;
	}

	if ($a->argc > 1) {
		for ($x = 1; $x < $a->argc; $x ++) {
			if (DI::dtFormat()->isYearMonth($a->argv[$x])) {
				$is_a_date_query = true;
				break;
			}
		}
	}

	// convert query string to array. remove friendica args
	$query_array = [];
	parse_str(parse_url($a->query_string, PHP_URL_QUERY), $query_array);

	// fetch last used network view and redirect if needed
	if (!$is_a_date_query) {
		$sel_nets = $_GET['nets'] ?? '';
		$sel_tabs = network_query_get_sel_tab($a);
		$sel_groups = network_query_get_sel_group($a);
		$last_sel_tabs = PConfig::get(local_user(), 'network.view', 'tab.selected');

		$remember_tab = ($sel_tabs[0] === 'active' && is_array($last_sel_tabs) && $last_sel_tabs[0] !== 'active');

		$net_baseurl = '/network';
		$net_args = [];

		if ($sel_groups !== false) {
			$net_baseurl .= '/' . $sel_groups;
		}

		if ($remember_tab) {
			// redirect if current selected tab is '/network' and
			// last selected tab is _not_ '/network?order=activity'.
			// and this isn't a date query

			$tab_baseurls = [
				'',     //all
				'',     //postord
				'',     //conv
				'/new', //new
				'',     //starred
				'',     //bookmarked
			];
			$tab_args = [
				'order=activity', //all
				'order=post',     //postord
				'conv=1',         //conv
				'',               //new
				'star=1',         //starred
				'bmark=1',        //bookmarked
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
		}

		if ($sel_nets) {
			$net_args['nets'] = $sel_nets;
		}

		if ($remember_tab) {
			$net_args = array_merge($query_array, $net_args);
			$net_queries = http_build_query($net_args);

			$redir_url = ($net_queries ? $net_baseurl . '?' . $net_queries : $net_baseurl);

			DI::baseUrl()->redirect($redir_url);
		}
	}

	if (empty($a->page['aside'])) {
		$a->page['aside'] = '';
	}

	$a->page['aside'] .= Group::sidebarWidget('network/0', 'network', 'standard', $group_id);
	$a->page['aside'] .= ForumManager::widget(local_user(), $cid);
	$a->page['aside'] .= Widget::postedByYear('network', local_user(), false);
	$a->page['aside'] .= Widget::networks('network', $_GET['nets'] ?? '');
	$a->page['aside'] .= Widget\SavedSearches::getHTML($a->query_string);
	$a->page['aside'] .= Widget::fileAs('network', $_GET['file'] ?? '');
}

/**
 * Return selected tab from query
 *
 * urls -> returns
 *        '/network'                => $no_active = 'active'
 *        '/network?order=activity' => $activity_active = 'active'
 *        '/network?order=post'     => $postord_active = 'active'
 *        '/network?conv=1',        => $conv_active = 'active'
 *        '/network/new',           => $new_active = 'active'
 *        '/network?star=1',        => $starred_active = 'active'
 *        '/network?bmark=1',       => $bookmarked_active = 'active'
 *
 * @param App $a
 * @return array ($no_active, $activity_active, $postord_active, $conv_active, $new_active, $starred_active, $bookmarked_active);
 */
function network_query_get_sel_tab(App $a)
{
	$no_active = '';
	$starred_active = '';
	$new_active = '';
	$bookmarked_active = '';
	$all_active = '';
	$conv_active = '';
	$postord_active = '';

	if (($a->argc > 1 && $a->argv[1] === 'new') || ($a->argc > 2 && $a->argv[2] === 'new')) {
		$new_active = 'active';
	}

	if (!empty($_GET['star'])) {
		$starred_active = 'active';
	}

	if (!empty($_GET['bmark'])) {
		$bookmarked_active = 'active';
	}

	if (!empty($_GET['conv'])) {
		$conv_active = 'active';
	}

	if (($new_active == '') && ($starred_active == '') && ($bookmarked_active == '') && ($conv_active == '')) {
		$no_active = 'active';
	}

	if ($no_active == 'active' && !empty($_GET['order'])) {
		switch($_GET['order']) {
			case 'post' :     $postord_active = 'active'; $no_active=''; break;
			case 'activity' : $all_active     = 'active'; $no_active=''; break;
		}
	}

	return [$no_active, $all_active, $postord_active, $conv_active, $new_active, $starred_active, $bookmarked_active];
}

function network_query_get_sel_group(App $a)
{
	$group = false;

	if ($a->argc >= 2 && is_numeric($a->argv[1])) {
		$group = $a->argv[1];
	}

	return $group;
}

/**
 * @brief Sets the pager data and returns SQL
 *
 * @param App     $a      The global App
 * @param Pager   $pager
 * @param integer $update Used for the automatic reloading
 * @return string SQL with the appropriate LIMIT clause
 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
 */
function networkPager(App $a, Pager $pager, $update)
{
	if ($update) {
		// only setup pagination on initial page view
		return ' LIMIT 100';
	}

	//  check if we serve a mobile device and get the user settings
	//  accordingly
	if (DI::mode()->isMobile()) {
		$itemspage_network = PConfig::get(local_user(), 'system', 'itemspage_mobile_network');
		$itemspage_network = ((intval($itemspage_network)) ? $itemspage_network : 20);
	} else {
		$itemspage_network = PConfig::get(local_user(), 'system', 'itemspage_network');
		$itemspage_network = ((intval($itemspage_network)) ? $itemspage_network : 40);
	}

	//  now that we have the user settings, see if the theme forces
	//  a maximum item number which is lower then the user choice
	if (($a->force_max_items > 0) && ($a->force_max_items < $itemspage_network)) {
		$itemspage_network = $a->force_max_items;
	}

	$pager->setItemsPerPage($itemspage_network);

	return sprintf(" LIMIT %d, %d ", $pager->getStart(), $pager->getItemsPerPage());
}

/**
 * @brief Sets items as seen
 *
 * @param array $condition The array with the SQL condition
 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
 */
function networkSetSeen($condition)
{
	if (empty($condition)) {
		return;
	}

	$unseen = Item::exists($condition);

	if ($unseen) {
		Item::update(['unseen' => false], $condition);
	}
}

/**
 * @brief Create the conversation HTML
 *
 * @param App     $a      The global App
 * @param array   $items  Items of the conversation
 * @param Pager   $pager
 * @param string  $mode   Display mode for the conversation
 * @param integer $update Used for the automatic reloading
 * @param string  $ordering
 * @return string HTML of the conversation
 * @throws ImagickException
 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
 */
function networkConversation(App $a, $items, Pager $pager, $mode, $update, $ordering = '')
{
	// Set this so that the conversation function can find out contact info for our wall-wall items
	$a->page_contact = $a->contact;

	if (!is_array($items)) {
		Logger::log("Expecting items to be an array. Got " . print_r($items, true));
		$items = [];
	}

	$o = conversation($a, $items, $pager, $mode, $update, false, $ordering, local_user());

	if (!$update) {
		if (PConfig::get(local_user(), 'system', 'infinite_scroll')) {
			$o .= HTML::scrollLoader();
		} else {
			$o .= $pager->renderMinimal(count($items));
		}
	}

	return $o;
}

function network_content(App $a, $update = 0, $parent = 0)
{
	if (!local_user()) {
		return Login::form();
	}

	/// @TODO Is this really necessary? $a is already available to hooks
	$arr = ['query' => $a->query_string];
	Hook::callAll('network_content_init', $arr);

	$flat_mode = false;

	if ($a->argc > 1) {
		for ($x = 1; $x < $a->argc; $x ++) {
			if ($a->argv[$x] === 'new') {
				$flat_mode = true;
			}
		}
	}

	if (!empty($_GET['file'])) {
		$flat_mode = true;
	}

	if ($flat_mode) {
		$o = networkFlatView($a, $update);
	} else {
		$o = networkThreadedView($a, $update, $parent);
	}

	if ($o === '') {
		info("No items found");
	}

	return $o;
}

/**
 * @brief Get the network content in flat view
 *
 * @param App     $a      The global App
 * @param integer $update Used for the automatic reloading
 * @return string HTML of the network content in flat view
 * @throws ImagickException
 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
 * @global Pager  $pager
 */
function networkFlatView(App $a, $update = 0)
{
	global $pager;
	// Rawmode is used for fetching new content at the end of the page
	$rawmode = (isset($_GET['mode']) && ($_GET['mode'] == 'raw'));

	$o = '';

	$file = $_GET['file'] ?? '';

	if (!$update && !$rawmode) {
		$tabs = network_tabs($a);
		$o .= $tabs;

		Nav::setSelected('network');

		$x = [
			'is_owner' => true,
			'allow_location' => $a->user['allow_location'],
			'default_location' => $a->user['default-location'],
			'nickname' => $a->user['nickname'],
			'lockstate' => (is_array($a->user) &&
			(strlen($a->user['allow_cid']) || strlen($a->user['allow_gid']) ||
			strlen($a->user['deny_cid']) || strlen($a->user['deny_gid'])) ? 'lock' : 'unlock'),
			'default_perms' => ACL::getDefaultUserPermissions($a->user),
			'acl' => ACL::getFullSelectorHTML($a->page, $a->user, true),
			'bang' => '',
			'visitor' => 'block',
			'profile_uid' => local_user(),
			'content' => '',
		];

		$o .= status_editor($a, $x);

		if (!Config::get('theme', 'hide_eventlist')) {
			$o .= Profile::getBirthdays();
			$o .= Profile::getEventsReminderHTML();
		}
	}

	$pager = new Pager($a->query_string);

	networkPager($a, $pager, $update);

	$item_params = ['order' => ['id' => true]];

	if (strlen($file)) {
		$term_condition = ["`term` = ? AND `otype` = ? AND `type` = ? AND `uid` = ?",
			$file, Term::OBJECT_TYPE_POST, Term::FILE, local_user()];
		$term_params = ['order' => ['tid' => true], 'limit' => [$pager->getStart(), $pager->getItemsPerPage()]];
		$result = DBA::select('term', ['oid'], $term_condition, $term_params);

		$posts = [];
		while ($term = DBA::fetch($result)) {
			$posts[] = $term['oid'];
		}
		DBA::close($result);

		if (count($posts) == 0) {
			return '';
		}
		$item_condition = ['uid' => local_user(), 'id' => $posts];
	} else {
		$item_condition = ['uid' => local_user()];
		$item_params['limit'] = [$pager->getStart(), $pager->getItemsPerPage()];

		networkSetSeen(['unseen' => true, 'uid' => local_user()]);
	}

	$result = Item::selectForUser(local_user(), [], $item_condition, $item_params);
	$items = Item::inArray($result);
	$o .= networkConversation($a, $items, $pager, 'network-new', $update);

	return $o;
}

/**
 * @brief Get the network content in threaded view
 *
 * @param  App     $a      The global App
 * @param  integer $update Used for the automatic reloading
 * @param  integer $parent
 * @return string HTML of the network content in flat view
 * @throws ImagickException
 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
 * @global Pager   $pager
 */
function networkThreadedView(App $a, $update, $parent)
{
	/// @TODO this will have to be converted to a static property of the converted Module\Network class
	global $pager;

	// Rawmode is used for fetching new content at the end of the page
	$rawmode = (isset($_GET['mode']) AND ( $_GET['mode'] == 'raw'));

	if (isset($_GET['last_received']) && isset($_GET['last_commented']) && isset($_GET['last_created']) && isset($_GET['last_id'])) {
		$last_received = DateTimeFormat::utc($_GET['last_received']);
		$last_commented = DateTimeFormat::utc($_GET['last_commented']);
		$last_created = DateTimeFormat::utc($_GET['last_created']);
		$last_id = intval($_GET['last_id']);
	} else {
		$last_received = '';
		$last_commented = '';
		$last_created = '';
		$last_id = 0;
	}

	$datequery = $datequery2 = '';

	$gid = 0;

	$default_permissions = [];

	if ($a->argc > 1) {
		for ($x = 1; $x < $a->argc; $x ++) {
			if (DI::dtFormat()->isYearMonth($a->argv[$x])) {
				if ($datequery) {
					$datequery2 = Strings::escapeHtml($a->argv[$x]);
				} else {
					$datequery = Strings::escapeHtml($a->argv[$x]);
					$_GET['order'] = 'post';
				}
			} elseif (intval($a->argv[$x])) {
				$gid = intval($a->argv[$x]);
				$default_permissions['allow_gid'] = [$gid];
			}
		}
	}

	$o = '';

	$cid   = intval($_GET['cid']   ?? 0);
	$star  = intval($_GET['star']  ?? 0);
	$bmark = intval($_GET['bmark'] ?? 0);
	$conv  = intval($_GET['conv']  ?? 0);
	$order = Strings::escapeTags(($_GET['order'] ?? '') ?: 'activity');
	$nets  =        $_GET['nets']  ?? '';

	$allowedCids = [];
	if ($cid) {
		$allowedCids[] = (int) $cid;
	} elseif ($nets) {
		$condition = [
			'uid'     => local_user(),
			'network' => $nets,
			'self'    => false,
			'blocked' => false,
			'pending' => false,
			'archive' => false,
			'rel'     => [Contact::SHARING, Contact::FRIEND],
		];
		$contactStmt = DBA::select('contact', ['id'], $condition);
		while ($contact = DBA::fetch($contactStmt)) {
			$allowedCids[] = (int) $contact['id'];
		}
		DBA::close($contactStmt);
	}

	if (count($allowedCids)) {
		$default_permissions['allow_cid'] = $allowedCids;
	}

	if (!$update && !$rawmode) {
		$tabs = network_tabs($a);
		$o .= $tabs;

		if ($gid && ($t = Contact::getOStatusCountByGroupId($gid)) && !PConfig::get(local_user(), 'system', 'nowarn_insecure')) {
			notice(L10n::tt("Warning: This group contains %s member from a network that doesn't allow non public messages.",
				"Warning: This group contains %s members from a network that doesn't allow non public messages.",
				$t) . EOL);
			notice(L10n::t("Messages in this group won't be send to these receivers.").EOL);
		}

		Nav::setSelected('network');

		$content = '';

		if ($cid) {
			// If $cid belongs to a communitity forum or a privat goup,.add a mention to the status editor
			$condition = ["`id` = ? AND (`forum` OR `prv`)", $cid];
			$contact = DBA::selectFirst('contact', ['addr', 'nick'], $condition);
			if (DBA::isResult($contact)) {
				if ($contact['addr'] != '') {
					$content = '!' . $contact['addr'];
				} else {
					$content = '!' . $contact['nick'] . '+' . $cid;
				}
			}
		}

		$x = [
			'is_owner' => true,
			'allow_location' => $a->user['allow_location'],
			'default_location' => $a->user['default-location'],
			'nickname' => $a->user['nickname'],
			'lockstate' => ($gid || $cid || $nets || (is_array($a->user) &&
			(strlen($a->user['allow_cid']) || strlen($a->user['allow_gid']) ||
			strlen($a->user['deny_cid']) || strlen($a->user['deny_gid']))) ? 'lock' : 'unlock'),
			'default_perms' => ACL::getDefaultUserPermissions($a->user),
			'acl' => ACL::getFullSelectorHTML($a->page, $a->user, true, $default_permissions),
			'bang' => (($gid || $cid || $nets) ? '!' : ''),
			'visitor' => 'block',
			'profile_uid' => local_user(),
			'content' => $content,
		];

		$o .= status_editor($a, $x);
	}

	// We don't have to deal with ACLs on this page. You're looking at everything
	// that belongs to you, hence you can see all of it. We will filter by group if
	// desired.

	$sql_post_table = '';
	$sql_options = ($star ? " AND `thread`.`starred` " : '');
	$sql_options .= ($bmark ? sprintf(" AND `thread`.`post-type` = %d ", Item::PT_PAGE) : '');
	$sql_extra = $sql_options;
	$sql_extra2 = '';
	$sql_extra3 = '';
	$sql_table = '`thread`';
	$sql_parent = '`iid`';

	if ($update) {
		$sql_table = '`item`';
		$sql_parent = '`parent`';
		$sql_post_table = " INNER JOIN `thread` ON `thread`.`iid` = `item`.`parent`";
	}

	$sql_nets = (($nets) ? sprintf(" AND $sql_table.`network` = '%s' ", DBA::escape($nets)) : '');
	$sql_tag_nets = (($nets) ? sprintf(" AND `item`.`network` = '%s' ", DBA::escape($nets)) : '');

	if ($gid) {
		$group = DBA::selectFirst('group', ['name'], ['id' => $gid, 'uid' => local_user()]);
		if (!DBA::isResult($group)) {
			if ($update) {
				exit();
			}
			notice(L10n::t('No such group') . EOL);
			DI::baseUrl()->redirect('network/0');
			// NOTREACHED
		}

		$contacts = Group::expand(local_user(), [$gid]);

		if ((is_array($contacts)) && count($contacts)) {
			$contact_str_self = '';

			$contact_str = implode(',', $contacts);
			$self = DBA::selectFirst('contact', ['id'], ['uid' => local_user(), 'self' => true]);
			if (DBA::isResult($self)) {
				$contact_str_self = $self['id'];
			}

			$sql_post_table .= " INNER JOIN `item` AS `temp1` ON `temp1`.`id` = " . $sql_table . "." . $sql_parent;
			$sql_extra3 .= " AND (`thread`.`contact-id` IN ($contact_str) ";
			$sql_extra3 .= " OR (`thread`.`contact-id` = '$contact_str_self' AND `temp1`.`allow_gid` LIKE '" . Strings::protectSprintf('%<' . intval($gid) . '>%') . "' AND `temp1`.`private`))";
		} else {
			$sql_extra3 .= " AND false ";
			info(L10n::t('Group is empty'));
		}

		$o = Renderer::replaceMacros(Renderer::getMarkupTemplate('section_title.tpl'), [
			'$title' => L10n::t('Group: %s', $group['name'])
		]) . $o;
	} elseif ($cid) {
		$fields = ['id', 'name', 'network', 'writable', 'nurl',
			'forum', 'prv', 'contact-type', 'addr', 'thumb', 'location'];
		$condition = ["`id` = ? AND (NOT `blocked` OR `pending`)", $cid];
		$contact = DBA::selectFirst('contact', $fields, $condition);
		if (DBA::isResult($contact)) {
			$sql_extra = " AND " . $sql_table . ".`contact-id` = " . intval($cid);

			$entries[0] = [
				'id' => 'network',
				'name' => $contact['name'],
				'itemurl' => ($contact['addr'] ?? '') ?: $contact['nurl'],
				'thumb' => ProxyUtils::proxifyUrl($contact['thumb'], false, ProxyUtils::SIZE_THUMB),
				'details' => $contact['location'],
			];

			$entries[0]['account_type'] = Contact::getAccountType($contact);

			$o = Renderer::replaceMacros(Renderer::getMarkupTemplate('viewcontact_template.tpl'), [
				'contacts' => $entries,
				'id' => 'network',
			]) . $o;

			if ($contact['network'] === Protocol::OSTATUS && $contact['writable'] && !PConfig::get(local_user(),'system','nowarn_insecure')) {
				notice(L10n::t('Private messages to this person are at risk of public disclosure.') . EOL);
			}
		} else {
			notice(L10n::t('Invalid contact.') . EOL);
			DI::baseUrl()->redirect('network');
			// NOTREACHED
		}
	}

	if (!$gid && !$cid && !$update && !Config::get('theme', 'hide_eventlist')) {
		$o .= Profile::getBirthdays();
		$o .= Profile::getEventsReminderHTML();
	}

	if ($datequery) {
		$sql_extra3 .= Strings::protectSprintf(sprintf(" AND $sql_table.received <= '%s' ",
				DBA::escape(DateTimeFormat::convert($datequery, 'UTC', date_default_timezone_get()))));
	}
	if ($datequery2) {
		$sql_extra3 .= Strings::protectSprintf(sprintf(" AND $sql_table.received >= '%s' ",
				DBA::escape(DateTimeFormat::convert($datequery2, 'UTC', date_default_timezone_get()))));
	}

	if ($conv) {
		$sql_extra3 .= " AND $sql_table.`mention`";
	}

	// Normal conversation view
	if ($order === 'post') {
		$ordering = '`received`';
		$order_mode = 'received';
	} else {
		$ordering = '`commented`';
		$order_mode = 'commented';
	}

	$sql_order = "$sql_table.$ordering";

	if (!empty($_GET['offset'])) {
		$sql_range = sprintf(" AND $sql_order <= '%s'", DBA::escape($_GET['offset']));
	} else {
		$sql_range = '';
	}

	$pager = new Pager($a->query_string);

	$pager_sql = networkPager($a, $pager, $update);

	$last_date = '';

	switch ($order_mode) {
		case 'received':
			if ($last_received != '') {
				$last_date = $last_received;
				$sql_range .= sprintf(" AND $sql_table.`received` < '%s'", DBA::escape($last_received));
				$pager->setPage(1);
				$pager_sql = sprintf(" LIMIT %d, %d ", $pager->getStart(), $pager->getItemsPerPage());
			}
			break;
		case 'commented':
			if ($last_commented != '') {
				$last_date = $last_commented;
				$sql_range .= sprintf(" AND $sql_table.`commented` < '%s'", DBA::escape($last_commented));
				$pager->setPage(1);
				$pager_sql = sprintf(" LIMIT %d, %d ", $pager->getStart(), $pager->getItemsPerPage());
			}
			break;
		case 'created':
			if ($last_created != '') {
				$last_date = $last_created;
				$sql_range .= sprintf(" AND $sql_table.`created` < '%s'", DBA::escape($last_created));
				$pager->setPage(1);
				$pager_sql = sprintf(" LIMIT %d, %d ", $pager->getStart(), $pager->getItemsPerPage());
			}
			break;
		case 'id':
			if (($last_id > 0) && ($sql_table == '`thread`')) {
				$sql_range .= sprintf(" AND $sql_table.`iid` < '%s'", DBA::escape($last_id));
				$pager->setPage(1);
				$pager_sql = sprintf(" LIMIT %d, %d ", $pager->getStart(), $pager->getItemsPerPage());
			}
			break;
	}

	// Fetch a page full of parent items for this page
	if ($update) {
		if (!empty($parent)) {
			// Load only a single thread
			$sql_extra4 = "`item`.`id` = ".intval($parent);
		} else {
			// Load all unseen items
			$sql_extra4 = "`item`.`unseen`";
			if (Config::get("system", "like_no_comment")) {
				$sql_extra4 .= " AND `item`.`gravity` IN (" . GRAVITY_PARENT . "," . GRAVITY_COMMENT . ")";
			}
			if ($order === 'post') {
				// Only show toplevel posts when updating posts in this order mode
				$sql_extra4 .= " AND `item`.`id` = `item`.`parent`";
			}
		}

		$r = q("SELECT `item`.`parent-uri` AS `uri`, `item`.`parent` AS `item_id`, $sql_order AS `order_date`
			FROM `item` $sql_post_table
			STRAIGHT_JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
				AND (NOT `contact`.`blocked` OR `contact`.`pending`)
				AND (`item`.`gravity` != %d
					OR `contact`.`uid` = `item`.`uid` AND `contact`.`self`
					OR `contact`.`rel` IN (%d, %d) AND NOT `contact`.`readonly`)
			LEFT JOIN `user-item` ON `user-item`.`iid` = `item`.`id` AND `user-item`.`uid` = %d
			WHERE `item`.`uid` = %d AND `item`.`visible` AND NOT `item`.`deleted`
			AND (`user-item`.`hidden` IS NULL OR NOT `user-item`.`hidden`)
			AND NOT `item`.`moderated` AND $sql_extra4
			$sql_extra3 $sql_extra $sql_range $sql_nets
			ORDER BY `order_date` DESC LIMIT 100",
			intval(GRAVITY_PARENT),
			intval(Contact::SHARING),
			intval(Contact::FRIEND),
			intval(local_user()),
			intval(local_user())
		);
	} else {
		$r = q("SELECT `item`.`uri`, `thread`.`iid` AS `item_id`, $sql_order AS `order_date`
			FROM `thread` $sql_post_table
			STRAIGHT_JOIN `contact` ON `contact`.`id` = `thread`.`contact-id`
				AND (NOT `contact`.`blocked` OR `contact`.`pending`)
			STRAIGHT_JOIN `item` ON `item`.`id` = `thread`.`iid`
				AND (`item`.`gravity` != %d
					OR `contact`.`uid` = `item`.`uid` AND `contact`.`self`
					OR `contact`.`rel` IN (%d, %d) AND NOT `contact`.`readonly`)
			LEFT JOIN `user-item` ON `user-item`.`iid` = `item`.`id` AND `user-item`.`uid` = %d
			WHERE `thread`.`uid` = %d AND `thread`.`visible` AND NOT `thread`.`deleted`
			AND NOT `thread`.`moderated`
			AND (`user-item`.`hidden` IS NULL OR NOT `user-item`.`hidden`)
			$sql_extra2 $sql_extra3 $sql_range $sql_extra $sql_nets
			ORDER BY `order_date` DESC $pager_sql",
			intval(GRAVITY_PARENT),
			intval(Contact::SHARING),
			intval(Contact::FRIEND),
			intval(local_user()),
			intval(local_user())
		);
	}

	// Only show it when unfiltered (no groups, no networks, ...)
	if (in_array($nets, ['', Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::DIASPORA, Protocol::OSTATUS]) && (strlen($sql_extra . $sql_extra2 . $sql_extra3) == 0)) {
		if (DBA::isResult($r)) {
			$top_limit = current($r)['order_date'];
			$bottom_limit = end($r)['order_date'];
			if (empty($_SESSION['network_last_top_limit']) || ($_SESSION['network_last_top_limit'] < $top_limit)) {
				$_SESSION['network_last_top_limit'] = $top_limit;
			}
		} else {
			$top_limit = $bottom_limit = DateTimeFormat::utcNow();
		}

		// When checking for updates we need to fetch from the newest date to the newest date before
		// Only do this, when the last stored date isn't too long ago (10 times the update interval)
		$browser_update = PConfig::get(local_user(), 'system', 'update_interval', 40000) / 1000;

		if (($browser_update > 0) && $update && !empty($_SESSION['network_last_date']) &&
			(($bottom_limit < $_SESSION['network_last_date']) || ($top_limit == $bottom_limit)) &&
			((time() - $_SESSION['network_last_date_timestamp']) < ($browser_update * 10))) {
			$bottom_limit = $_SESSION['network_last_date'];
		}
		$_SESSION['network_last_date'] = Session::get('network_last_top_limit', $top_limit);
		$_SESSION['network_last_date_timestamp'] = time();

		if ($last_date > $top_limit) {
			$top_limit = $last_date;
		} elseif ($pager->getPage() == 1) {
			// Highest possible top limit when we are on the first page
			$top_limit = DateTimeFormat::utcNow();
		}

		$items = DBA::p("SELECT `item`.`parent-uri` AS `uri`, 0 AS `item_id`, `item`.$ordering AS `order_date`, `author`.`url` AS `author-link` FROM `item`
			STRAIGHT_JOIN (SELECT `oid` FROM `term` WHERE `term` IN
				(SELECT SUBSTR(`term`, 2) FROM `search` WHERE `uid` = ? AND `term` LIKE '#%') AND `otype` = ? AND `type` = ? AND `uid` = 0) AS `term`
			ON `item`.`id` = `term`.`oid`
			STRAIGHT_JOIN `contact` AS `author` ON `author`.`id` = `item`.`author-id`
			WHERE `item`.`uid` = 0 AND `item`.$ordering < ? AND `item`.$ordering > ? AND `item`.`gravity` = ?
				AND NOT `author`.`hidden` AND NOT `author`.`blocked`" . $sql_tag_nets,
			local_user(), TERM_OBJ_POST, TERM_HASHTAG,
			$top_limit, $bottom_limit, GRAVITY_PARENT);

		$data = DBA::toArray($items);

		if (count($data) > 0) {
			$tag_top_limit = current($data)['order_date'];
			if ($_SESSION['network_last_date'] < $tag_top_limit) {
				$_SESSION['network_last_date'] = $tag_top_limit;
			}

			Logger::log('Tagged items: ' . count($data) . ' - ' . $bottom_limit . ' - ' . $top_limit . ' - ' . local_user().' - '.(int)$update);
			$s = [];
			foreach ($r as $item) {
				$s[$item['uri']] = $item;
			}
			foreach ($data as $item) {
				// Don't show hash tag posts from blocked or ignored contacts
				$condition = ["`nurl` = ? AND `uid` = ? AND (`blocked` OR `readonly`)",
					Strings::normaliseLink($item['author-link']), local_user()];
				if (!DBA::exists('contact', $condition)) {
					$s[$item['uri']] = $item;
				}
			}
			$r = $s;
		}
	}

	$parents_str = '';
	$date_offset = '';

	$items = $r;

	if (DBA::isResult($items)) {
		$parents_arr = [];

		foreach ($items as $item) {
			if ($date_offset < $item['order_date']) {
				$date_offset = $item['order_date'];
			}
			if (!in_array($item['item_id'], $parents_arr) && ($item['item_id'] > 0)) {
				$parents_arr[] = $item['item_id'];
			}
		}
		$parents_str = implode(', ', $parents_arr);
	}

	if (!empty($_GET['offset'])) {
		$date_offset = $_GET['offset'];
	}

	$query_string = $a->query_string;
	if ($date_offset && !preg_match('/[?&].offset=/', $query_string)) {
		$query_string .= '&offset=' . urlencode($date_offset);
	}

	$pager->setQueryString($query_string);

	// We aren't going to try and figure out at the item, group, and page
	// level which items you've seen and which you haven't. If you're looking
	// at the top level network page just mark everything seen.

	if (!$gid && !$cid && !$star) {
		$condition = ['unseen' => true, 'uid' => local_user()];
		networkSetSeen($condition);
	} elseif ($parents_str) {
		$condition = ["`uid` = ? AND `unseen` AND `parent` IN (" . DBA::escape($parents_str) . ")", local_user()];
		networkSetSeen($condition);
	}


	$mode = 'network';
	$o .= networkConversation($a, $items, $pager, $mode, $update, $ordering);

	return $o;
}

/**
 * @brief Get the network tabs menu
 *
 * @param App $a The global App
 * @return string Html of the networktab
 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
 */
function network_tabs(App $a)
{
	// item filter tabs
	/// @TODO fix this logic, reduce duplication
	/// $a->page['content'] .= '<div class="tabs-wrapper">';
	list($no_active, $all_active, $post_active, $conv_active, $new_active, $starred_active, $bookmarked_active) = network_query_get_sel_tab($a);

	// if no tabs are selected, defaults to activitys
	if ($no_active == 'active') {
		$all_active = 'active';
	}

	$cmd = $a->cmd;

	// tabs
	$tabs = [
		[
			'label'	=> L10n::t('Latest Activity'),
			'url'	=> str_replace('/new', '', $cmd) . '?order=activity' . (!empty($_GET['cid']) ? '&cid=' . $_GET['cid'] : ''),
			'sel'	=> $all_active,
			'title'	=> L10n::t('Sort by latest activity'),
			'id'	=> 'activity-order-tab',
			'accesskey' => 'e',
		],
		[
			'label'	=> L10n::t('Latest Posts'),
			'url'	=> str_replace('/new', '', $cmd) . '?order=post' . (!empty($_GET['cid']) ? '&cid=' . $_GET['cid'] : ''),
			'sel'	=> $post_active,
			'title'	=> L10n::t('Sort by post received date'),
			'id'	=> 'post-order-tab',
			'accesskey' => 't',
		],
	];

	$tabs[] = [
		'label'	=> L10n::t('Personal'),
		'url'	=> str_replace('/new', '', $cmd) . (!empty($_GET['cid']) ? '/?cid=' . $_GET['cid'] : '/?f=') . '&conv=1',
		'sel'	=> $conv_active,
		'title'	=> L10n::t('Posts that mention or involve you'),
		'id'	=> 'personal-tab',
		'accesskey' => 'r',
	];

	if (Feature::isEnabled(local_user(), 'new_tab')) {
		$tabs[] = [
			'label'	=> L10n::t('New'),
			'url'	=> 'network/new' . (!empty($_GET['cid']) ? '/?cid=' . $_GET['cid'] : ''),
			'sel'	=> $new_active,
			'title'	=> L10n::t('Activity Stream - by date'),
			'id'	=> 'activitiy-by-date-tab',
			'accesskey' => 'w',
		];
	}

	if (Feature::isEnabled(local_user(), 'link_tab')) {
		$tabs[] = [
			'label'	=> L10n::t('Shared Links'),
			'url'	=> str_replace('/new', '', $cmd) . (!empty($_GET['cid']) ? '/?cid=' . $_GET['cid'] : '/?f=') . '&bmark=1',
			'sel'	=> $bookmarked_active,
			'title'	=> L10n::t('Interesting Links'),
			'id'	=> 'shared-links-tab',
			'accesskey' => 'b',
		];
	}

	$tabs[] = [
		'label'	=> L10n::t('Starred'),
		'url'	=> str_replace('/new', '', $cmd) . (!empty($_GET['cid']) ? '/?cid=' . $_GET['cid'] : '/?f=') . '&star=1',
		'sel'	=> $starred_active,
		'title'	=> L10n::t('Favourite Posts'),
		'id'	=> 'starred-posts-tab',
		'accesskey' => 'm',
	];

	// save selected tab, but only if not in file mode
	if (empty($_GET['file'])) {
		PConfig::set(local_user(), 'network.view', 'tab.selected', [
			$all_active, $post_active, $conv_active, $new_active, $starred_active, $bookmarked_active
		]);
	}

	$arr = ['tabs' => $tabs];
	Hook::callAll('network_tabs', $arr);

	$tpl = Renderer::getMarkupTemplate('common_tabs.tpl');

	return Renderer::replaceMacros($tpl, ['$tabs' => $arr['tabs']]);

	// --- end item filter tabs
}

/**
 * Network hook into the HTML head to enable infinite scroll.
 *
 * Since the HTML head is built after the module content has been generated, we need to retrieve the base query string
 * of the page to make the correct asynchronous call. This is obtained through the Pager that was instantiated in
 * networkThreadedView or networkFlatView.
 *
 * @param App     $a
 * @param  string $htmlhead The head tag HTML string
 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
 * @global Pager  $pager
 */
function network_infinite_scroll_head(App $a, &$htmlhead)
{
	/// @TODO this will have to be converted to a static property of the converted Module\Network class
	/**
	 * @var $pager Pager
	 */
	global $pager;

	if (PConfig::get(local_user(), 'system', 'infinite_scroll')
		&& ($_GET['mode'] ?? '') != 'minimal'
	) {
		$tpl = Renderer::getMarkupTemplate('infinite_scroll_head.tpl');
		$htmlhead .= Renderer::replaceMacros($tpl, [
			'$pageno'     => $pager->getPage(),
			'$reload_uri' => $pager->getBaseQueryString()
		]);
	}
}
