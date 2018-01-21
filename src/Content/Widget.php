<?php
/**
 * @file src/Content/Widget.php
 */
namespace Friendica\Content;

use Friendica\Content\ContactSelector;
use Friendica\Content\Feature;
use Friendica\Core\Addon;
use Friendica\Core\Config;
use Friendica\Core\PConfig;
use Friendica\Core\System;
use Friendica\Database\DBM;
use Friendica\Model\GContact;
use Friendica\Model\Profile;

use dba;

require_once 'boot.php';

class Widget
{
	/**
	 * Return the follow widget
	 *
	 * @param string $value optional, default empty
	 */
	public static function follow($value = "")
	{
		return replace_macros(get_markup_template('follow.tpl'), array(
			'$connect' => t('Add New Contact'),
			'$desc' => t('Enter address or web location'),
			'$hint' => t('Example: bob@example.com, http://example.com/barbara'),
			'$value' => $value,
			'$follow' => t('Connect')
		));
	}

	/**
	 * Return Find People widget
	 */
	public static function findPeople()
	{
		$a = get_app();
		$global_dir = Config::get('system', 'directory');

		if (Config::get('system', 'invitation_only')) {
			$x = PConfig::get(local_user(), 'system', 'invites_remaining');
			if ($x || is_site_admin()) {
				$a->page['aside'] .= '<div class="side-link" id="side-invite-remain">'
					. tt('%d invitation available', '%d invitations available', $x)
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

	/**
	 * Return unavailable networks
	 */
	public static function unavailableNetworks()
	{
		$networks = array();

		if (!Addon::isEnabled("appnet")) {
			$networks[] = NETWORK_APPNET;
		}

		if (!Addon::isEnabled("fbpost") && !Addon::isEnabled("facebook")) {
			$networks[] = NETWORK_FACEBOOK;
		}

		if (!Addon::isEnabled("statusnet")) {
			$networks[] = NETWORK_STATUSNET;
		}

		if (!Addon::isEnabled("pumpio")) {
			$networks[] = NETWORK_PUMPIO;
		}

		if (!Addon::isEnabled("twitter")) {
			$networks[] = NETWORK_TWITTER;
		}

		if (Config::get("system", "ostatus_disabled")) {
			$networks[] = NETWORK_OSTATUS;
		}

		if (!Config::get("system", "diaspora_enabled")) {
			$networks[] = NETWORK_DIASPORA;
		}

		if (!Addon::isEnabled("pnut")) {
			$networks[] = NETWORK_PNUT;
		}

		if (!sizeof($networks)) {
			return "";
		}

		$network_filter = implode("','", $networks);

		$network_filter = "AND `network` NOT IN ('$network_filter')";

		return $network_filter;
	}

	/**
	 * Return networks widget
	 *
	 * @param string $baseurl  baseurl
	 * @param string $selected optional, default empty
	 */
	public static function networks($baseurl, $selected = '')
	{
		if (!local_user()) {
			return '';
		}

		if (!Feature::isEnabled(local_user(), 'networks')) {
			return '';
		}

		$extra_sql = self::unavailableNetworks();

		$r = dba::p("SELECT DISTINCT(`network`) FROM `contact` WHERE `uid` = ? AND `network` != '' $extra_sql ORDER BY `network`",
			local_user()
		);

		$nets = array();
		while ($rr = dba::fetch($r)) {
			/// @TODO If 'network' is not there, this triggers an E_NOTICE
			if ($rr['network']) {
				$nets[] = array('ref' => $rr['network'], 'name' => ContactSelector::networkToName($rr['network']), 'selected' => (($selected == $rr['network']) ? 'selected' : '' ));
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

	/**
	 * Return file as widget
	 *
	 * @param string $baseurl  baseurl
	 * @param string $selected optional, default empty
	 */
	public static function fileAs($baseurl, $selected = '')
	{
		if (!local_user()) {
			return '';
		}

		if (!Feature::isEnabled(local_user(), 'filing')) {
			return '';
		}

		$saved = PConfig::get(local_user(), 'system', 'filetags');
		if (!strlen($saved)) {
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

	/**
	 * Return categories widget
	 *
	 * @param string $baseurl  baseurl
	 * @param string $selected optional, default empty
	 */
	public static function categories($baseurl, $selected = '')
	{
		$a = get_app();

		if (!Feature::isEnabled($a->profile['profile_uid'], 'categories')) {
			return '';
		}

		$saved = PConfig::get($a->profile['profile_uid'], 'system', 'filetags');
		if (!strlen($saved)) {
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

	/**
	 * Return common friends visitor widget
	 *
	 * @param string $profile_uid uid
	 */
	public static function commonFriendsVisitor($profile_uid)
	{
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

		if (!$cid) {
			if (Profile::getMyURL()) {
				$contact = dba::selectFirst('contact', ['id'],
						['nurl' => normalise_link(Profile::getMyURL()), 'uid' => $profile_uid]);
				if (DBM::isResult($contact)) {
					$cid = $contact['id'];
				} else {
					$gcontact = dba::selectFirst('gcontact', ['id'], ['nurl' => normalise_link(Profile::getMyURL())]);
					if (DBM::isResult($gcontact)) {
						$zcid = $gcontact['id'];
					}
				}
			}
		}

		if ($cid == 0 && $zcid == 0) {
			return;
		}

		if ($cid) {
			$t = GContact::countCommonFriends($profile_uid, $cid);
		} else {
			$t = GContact::countCommonFriendsZcid($profile_uid, $zcid);
		}

		if (!$t) {
			return;
		}

		if ($cid) {
			$r = GContact::commonFriends($profile_uid, $cid, 0, 5, true);
		} else {
			$r = GContact::commonFriendsZcid($profile_uid, $zcid, 0, 5, true);
		}

		return replace_macros(get_markup_template('remote_friends_common.tpl'), array(
			'$desc' => tt("%d contact in common", "%d contacts in common", $t),
			'$base' => System::baseUrl(),
			'$uid' => $profile_uid,
			'$cid' => (($cid) ? $cid : '0'),
			'$linkmore' => (($t > 5) ? 'true' : ''),
			'$more' => t('show more'),
			'$items' => $r)
		);
	}
}
