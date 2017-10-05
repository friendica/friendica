<?php

use Friendica\App;
use Friendica\Core\System;

require_once 'include/probe.php';
require_once 'include/follow.php';
require_once 'include/Contact.php';
require_once 'include/contact_selectors.php';

function unfollow_post(App $a) {

	if (!local_user()) {
		notice(t('Permission denied.') . EOL);
		goaway($_SESSION['return_url']);
		// NOTREACHED
	}

	if ($_REQUEST['cancel']) {
		goaway($_SESSION['return_url']);
	}

	$uid = local_user();
	$url = notags(trim($_REQUEST['url']));
	$return_url = $_SESSION['return_url'];

	$condition = array("`uid` = ? AND `rel` = ? AND (`nurl` = ? OR `alias` = ? OR `alias` = ?) AND `network` != ?",
			$uid, CONTACT_IS_FRIEND, normalise_link($url),
			normalise_link($url), $url, NETWORK_STATUSNET);
	$contact = dba::select('contact', array(), $condition, array('limit' => 1));

	if (!dbm::is_result($contact)) {
		notice(t("Contact wasn't found or can't be unfollowed."));
	} else {
		if (in_array($contact['network'], array(NETWORK_OSTATUS, NETWORK_DIASPORA))) {
			$r = q("SELECT `contact`.*, `user`.* FROM `contact` INNER JOIN `user` ON `contact`.`uid` = `user`.`uid`
				WHERE `user`.`uid` = %d AND `contact`.`self` LIMIT 1",
				intval($uid)
			);
 			if (dbm::is_result($r)) {
				$self = ""; // Unused parameter
				terminate_friendship($r[0], $self, $contact);
			}
		}
		dba::update('contact', array('rel' => CONTACT_IS_FOLLOWER), array('id' => $contact['id']));

		info(t('Contact unfollowed').EOL);
		goaway(System::baseUrl().'/contacts/'.$contact['id']);
	}
	goaway($return_url);
	// NOTREACHED
}

function unfollow_content(App $a) {

	if (! local_user()) {
		notice(t('Permission denied.') . EOL);
		goaway($_SESSION['return_url']);
		// NOTREACHED
	}

	$uid = local_user();
	$url = notags(trim($_REQUEST['url']));

	$submit = t('Submit Request');

	$condition = array("`uid` = ? AND `rel` = ? AND (`nurl` = ? OR `alias` = ? OR `alias` = ?) AND `network` != ?",
			local_user(), CONTACT_IS_FRIEND, normalise_link($url),
			normalise_link($url), $url, NETWORK_STATUSNET);
	$contact = dba::select('contact', array('url', 'network', 'addr', 'name'), $condition, array('limit' => 1));

	if (!dbm::is_result($contact)) {
		notice(t("You aren't a friend of this contact.").EOL);
		$submit = "";
		// NOTREACHED
	}

	if (!in_array($contact['network'], array(NETWORK_DIASPORA, NETWORK_OSTATUS))) {
		notice(t("Unfollowing is currently not supported by your network.").EOL);
		$submit = "";
		// NOTREACHED
	}

	$request = System::baseUrl()."/unfollow";
	$tpl = get_markup_template('auto_request.tpl');

	$r = q("SELECT `url` FROM `contact` WHERE `uid` = %d AND `self` LIMIT 1", intval($uid));

	if (!$r) {
		notice(t('Permission denied.') . EOL);
		goaway($_SESSION['return_url']);
		// NOTREACHED
	}

	$myaddr = $r[0]["url"];

	// Makes the connection request for friendica contacts easier
	$_SESSION["fastlane"] = $contact["url"];

	$header = t("Disconnect/Unfollow");

	$o  = replace_macros($tpl,array(
			'$header' => htmlentities($header),
			'$desc' => "",
			'$pls_answer' => "",
			'$does_know_you' => "",
			'$add_note' => "",
			'$page_desc' => "",
			'$friendica' => "",
			'$statusnet' => "",
			'$diaspora' => "",
			'$diasnote' => "",
			'$your_address' => t('Your Identity Address:'),
			'$invite_desc' => "",
			'$emailnet' => "",
			'$submit' => $submit,
			'$cancel' => t('Cancel'),
			'$nickname' => "",
			'$name' => $contact["name"],
			'$url' => $contact["url"],
			'$zrl' => zrl($contact["url"]),
			'$url_label' => t("Profile URL"),
			'$myaddr' => $myaddr,
			'$request' => $request,
			'$keywords' => "",
			'$keywords_label' => ""
	));

	$a->page['aside'] = "";
	profile_load($a, "", 0, get_contact_details_by_url($contact["url"]));

	$o .= replace_macros(get_markup_template('section_title.tpl'),
					array('$title' => t('Status Messages and Posts')
	));

	// Show last public posts
	$o .= posts_from_contact_url($a, $contact["url"]);

	return $o;
}
