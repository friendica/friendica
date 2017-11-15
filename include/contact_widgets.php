<?php
/**
 * @file include/contact_widgets.php
 */
use Friendica\App;
use Friendica\Core\System;
use Friendica\Core\Config;
use Friendica\Core\PConfig;
use Friendica\Database\DBM;
use Friendica\Model\GlobalContact;

require_once 'include/contact_selectors.php';

function follow_widget($value = "") {

	return replace_macros(get_markup_template('follow.tpl'), array(
		'$connect' => t('Add New Contact'),
		'$desc' => t('Enter address or web location'),
		'$hint' => t('Example: bob@example.com, http://example.com/barbara'),
		'$value' => $value,
		'$follow' => t('Connect')
	));

}

function findpeople_widget() {
	require_once 'include/Contact.php';

	$a = get_app();
	$global_dir = Config::get('system', 'directory');

	if (Config::get('system', 'invitation_only')) {
		$x = PConfig::get(local_user(), 'system', 'invites_remaining');
		if ($x || is_site_admin()) {
			$a->page['aside'] .= '<div class="side-link" id="side-invite-remain">'
			. sprintf( tt('%d invitation available', '%d invitations available', $x), $x)
			. '</div>' . $inv;
		}
	}

	return replace_macros(get_markup_template('peoplefind.tpl'), array(
		'$findpeople' => t('Find People'),
		'$desc' => t('Enter name or interest'),
		'$label' => t('Connect/Follow'),
		'$hint' => t('Examples: Robert Morgenstein, Fishing'),
		'$findthem' => t('Find'),
		'$suggest' => t('Friend Suggestions'),
		'$similar' => t('Similar Interests'),
		'$random' => t('Random Profile'),
		'$inv' => t('Invite Friends'),
		'$directory' => t('View Global Directory'),
		'$global_dir' => $global_dir
	));

}

function unavailable_networks() {
	$network_filter = "";

	$networks = array();

	if (!plugin_enabled("appnet")) {
		$networks[] = NETWORK_APPNET;
	}

	if (!plugin_enabled("fbpost") && !plugin_enabled("facebook")) {
		$networks[] = NETWORK_FACEBOOK;
	}

	if (!plugin_enabled("statusnet")) {
		$networks[] = NETWORK_STATUSNET;
	}

	if (!plugin_enabled("pumpio")) {
		$networks[] = NETWORK_PUMPIO;
	}

	if (!plugin_enabled("twitter")) {
		$networks[] = NETWORK_TWITTER;
	}

	if (Config::get("system", "ostatus_disabled")) {
		$networks[] = NETWORK_OSTATUS;
	}

	if (!Config::get("system", "diaspora_enabled")) {
		$networks[] = NETWORK_DIASPORA;
	}

	if (!plugin_enabled("pnut")) {
		$networks[] = NETWORK_PNUT;
	}

	if (!sizeof($networks)) {
		return "";
	}

	$network_filter = implode("','", $networks);

	$network_filter = "AND `network` NOT IN ('$network_filter')";

	return $network_filter;
}

function networks_widget($baseurl, $selected = '') {

	$a = get_app();

	if (!local_user()) {
		return '';
	}

	if (!feature_enabled(local_user(), 'networks')) {
		return '';
	}

	$extra_sql = unavailable_networks();

	$r = dba::p("SELECT DISTINCT(`network`) FROM `contact` WHERE `uid` = ? AND `network` != '' $extra_sql ORDER BY `network`",
		local_user()
	);

	$nets = array();
	while ($rr = dba::fetch($r)) {
		/// @TODO If 'network' is not there, this triggers an E_NOTICE
		if ($rr['network']) {
			$nets[] = array('ref' => $rr['network'], 'name' => network_to_name($rr['network']), 'selected' => (($selected == $rr['network']) ? 'selected' : '' ));
		}
	}
	dba::close($r);

	if (count($nets) < 2) {
		return '';
	}

	return replace_macros(get_markup_template('nets.tpl'), array(
		'$title' => t('Networks'),
		'$desc' => '',
		'$sel_all' => (($selected == '') ? 'selected' : ''),
		'$all' => t('All Networks'),
		'$nets' => $nets,
		'$base' => $baseurl,

	));
}

function fileas_widget($baseurl, $selected = '') {
	if (! local_user()) {
		return '';
	}

	if (! feature_enabled(local_user(), 'filing')) {
		return '';
	}

	$saved = PConfig::get(local_user(), 'system', 'filetags');
	if (! strlen($saved)) {
		return;
	}

	$matches = false;
	$terms = array();
	$cnt = preg_match_all('/\[(.*?)\]/', $saved, $matches, PREG_SET_ORDER);
	if ($cnt) {
		foreach ($matches as $mtch) {
			$unescaped = xmlify(file_tag_decode($mtch[1]));
			$terms[] = array('name' => $unescaped, 'selected' => (($selected == $unescaped) ? 'selected' : ''));
		}
	}

	return replace_macros(get_markup_template('fileas_widget.tpl'), array(
		'$title' => t('Saved Folders'),
		'$desc' => '',
		'$sel_all' => (($selected == '') ? 'selected' : ''),
		'$all' => t('Everything'),
		'$terms' => $terms,
		'$base' => $baseurl,

	));
}

function categories_widget($baseurl, $selected = '') {

	$a = get_app();

	if (! feature_enabled($a->profile['profile_uid'], 'categories')) {
		return '';
	}

	$saved = PConfig::get($a->profile['profile_uid'], 'system', 'filetags');
	if (! strlen($saved)) {
		return;
	}

	$matches = false;
	$terms = array();
	$cnt = preg_match_all('/<(.*?)>/', $saved, $matches, PREG_SET_ORDER);

	if ($cnt) {
		foreach ($matches as $mtch) {
			$unescaped = xmlify(file_tag_decode($mtch[1]));
			$terms[] = array('name' => $unescaped, 'selected' => (($selected == $unescaped) ? 'selected' : ''));
		}
	}

	return replace_macros(get_markup_template('categories_widget.tpl'), array(
		'$title' => t('Categories'),
		'$desc' => '',
		'$sel_all' => (($selected == '') ? 'selected' : ''),
		'$all' => t('Everything'),
		'$terms' => $terms,
		'$base' => $baseurl,

	));
}

function common_friends_visitor_widget($profile_uid) {

	$a = get_app();

	if (local_user() == $profile_uid) {
		return;
	}

	$cid = $zcid = 0;

	if (is_array($_SESSION['remote'])) {
		foreach ($_SESSION['remote'] as $visitor) {
			if ($visitor['uid'] == $profile_uid) {
				$cid = $visitor['cid'];
				break;
			}
		}
	}

	if (! $cid) {
		if (get_my_url()) {
			$r = dba::select('contact', array('id'),
					array('nurl' => normalise_link(get_my_url()), 'uid' => $profile_uid), array('limit' => 1));
			if (DBM::is_result($r)) {
				$cid = $r['id'];
			} else {
				$r = dba::select('gcontact', array('id'), array('nurl' => normalise_link(get_my_url())), array('limit' => 1));
				if (DBM::is_result($r)) {
					$zcid = $r['id'];
				}
			}
		}
	}

	if ($cid == 0 && $zcid == 0) {
		return;
	}

	if ($cid) {
		$t = GlobalContact::countCommonFriends($profile_uid, $cid);
	} else {
		$t = GlobalContact::countCommonFriendsZcid($profile_uid, $zcid);
	}
	if (! $t) {
		return;
	}

	if ($cid) {
		$r = GlobalContact::commonFriends($profile_uid, $cid, 0, 5, true);
	} else {
		$r = GlobalContact::commonFriendsZcid($profile_uid, $zcid, 0, 5, true);
	}

	return replace_macros(get_markup_template('remote_friends_common.tpl'), array(
		'$desc' =>  sprintf( tt("%d contact in common", "%d contacts in common", $t), $t),
		'$base' => System::baseUrl(),
		'$uid' => $profile_uid,
		'$cid' => (($cid) ? $cid : '0'),
		'$linkmore' => (($t > 5) ? 'true' : ''),
		'$more' => t('show more'),
		'$items' => $r
	));

};
