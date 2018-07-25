<?php
/**
 * @file mod/unfollow.php
 */

use Friendica\App;
use Friendica\Core\L10n;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Model\Profile;

function unfollow_post(App $a)
{
	if (!local_user()) {
		notice(L10n::t('Permission denied.') . EOL);
		goaway($_SESSION['return_url']);
		// NOTREACHED
	}

	if ($_REQUEST['cancel']) {
		goaway($_SESSION['return_url']);
	}

	$uid = local_user();
	$url = notags(trim($_REQUEST['url']));
	$return_url = $_SESSION['return_url'];

	$condition = ["`uid` = ? AND `rel` = ? AND (`nurl` = ? OR `alias` = ? OR `alias` = ?) AND `network` != ?",
			$uid, Contact::FRIEND, normalise_link($url),
			normalise_link($url), $url, NETWORK_STATUSNET];
	$contact = DBA::selectFirst('contact', [], $condition);

	if (!DBA::isResult($contact)) {
		notice(L10n::t("Contact wasn't found or can't be unfollowed."));
	} else {
		if (in_array($contact['network'], [NETWORK_OSTATUS, NETWORK_DIASPORA, NETWORK_DFRN])) {
			$r = q("SELECT `contact`.*, `user`.* FROM `contact` INNER JOIN `user` ON `contact`.`uid` = `user`.`uid`
				WHERE `user`.`uid` = %d AND `contact`.`self` LIMIT 1",
				intval($uid)
			);
			if (DBA::isResult($r)) {
				Contact::terminateFriendship($r[0], $contact);
			}
		}
		DBA::update('contact', ['rel' => Contact::FOLLOWER], ['id' => $contact['id']]);

		info(L10n::t('Contact unfollowed').EOL);
		goaway(System::baseUrl().'/contacts/'.$contact['id']);
	}
	goaway($return_url);
	// NOTREACHED
}

function unfollow_content(App $a)
{
	if (! local_user()) {
		notice(L10n::t('Permission denied.') . EOL);
		goaway($_SESSION['return_url']);
		// NOTREACHED
	}

	$uid = local_user();
	$url = notags(trim($_REQUEST['url']));

	$submit = L10n::t('Submit Request');

	$condition = ["`uid` = ? AND `rel` = ? AND (`nurl` = ? OR `alias` = ? OR `alias` = ?) AND `network` != ?",
			local_user(), Contact::FRIEND, normalise_link($url),
			normalise_link($url), $url, NETWORK_STATUSNET];
	$contact = DBA::selectFirst('contact', ['url', 'network', 'addr', 'name'], $condition);

	if (!DBA::isResult($contact)) {
		notice(L10n::t("You aren't a friend of this contact.").EOL);
		$submit = "";
		// NOTREACHED
	}

	if (!in_array($contact['network'], [NETWORK_DIASPORA, NETWORK_OSTATUS, NETWORK_DFRN])) {
		notice(L10n::t("Unfollowing is currently not supported by your network.").EOL);
		$submit = "";
		// NOTREACHED
	}

	$request = System::baseUrl()."/unfollow";
	$tpl = get_markup_template('auto_request.tpl');

	$r = q("SELECT `url` FROM `contact` WHERE `uid` = %d AND `self` LIMIT 1", intval($uid));

	if (!$r) {
		notice(L10n::t('Permission denied.') . EOL);
		goaway($_SESSION['return_url']);
		// NOTREACHED
	}

	$myaddr = $r[0]["url"];

	// Makes the connection request for friendica contacts easier
	$_SESSION["fastlane"] = $contact["url"];

	$header = L10n::t("Disconnect/Unfollow");

	$o  = replace_macros($tpl, [
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
			'$your_address' => L10n::t('Your Identity Address:'),
			'$invite_desc' => "",
			'$emailnet' => "",
			'$submit' => $submit,
			'$cancel' => L10n::t('Cancel'),
			'$nickname' => "",
			'$name' => $contact["name"],
			'$url' => $contact["url"],
			'$zrl' => Contact::magicLink($contact["url"]),
			'$url_label' => L10n::t("Profile URL"),
			'$myaddr' => $myaddr,
			'$request' => $request,
			'$keywords' => "",
			'$keywords_label' => ""
	]);

	$a->page['aside'] = "";
	Profile::load($a, "", 0, Contact::getDetailsByURL($contact["url"]));

	$o .= replace_macros(get_markup_template('section_title.tpl'), ['$title' => L10n::t('Status Messages and Posts')]);

	// Show last public posts
	$o .= Contact::getPostsFromUrl($contact["url"]);

	return $o;
}
