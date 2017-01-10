<?php

require_once('include/Scrape.php');
require_once('include/follow.php');
require_once('include/Contact.php');
require_once('include/contact_selectors.php');

function follow_content(App $a) {

	if (! local_user()) {
		notice( t('Permission denied.') . EOL);
		goaway($_SESSION['return_url']);
		// NOTREACHED
	}

	$uid = local_user();
	$url = notags(trim($_REQUEST['url']));

	$submit = t('Submit Request');

	// There is a current issue. It seems as if you can't start following a Friendica that is following you
	// With Diaspora this works - but Friendica is special, it seems ...
	$r = q("SELECT `url` FROM `contact` WHERE `uid` = %d AND ((`rel` != %d) OR (`network` = '%s')) AND
		(`nurl` = '%s' OR `alias` = '%s' OR `alias` = '%s') AND
		`network` != '%s' LIMIT 1",
		intval(local_user()), dbesc(CONTACT_IS_FOLLOWER), dbesc(NETWORK_DFRN), dbesc(normalise_link($url)),
		dbesc(normalise_link($url)), dbesc($url), dbesc(NETWORK_STATUSNET));

	if ($r) {
		notice(t('You already added this contact.').EOL);
		$submit = "";
		//goaway($_SESSION['return_url']);
		// NOTREACHED
	}

	$ret = probe_url($url);

	if (($ret["network"] == NETWORK_DIASPORA) AND !get_config('system','diaspora_enabled')) {
		notice( t("Diaspora support isn't enabled. Contact can't be added.") . EOL);
		$submit = "";
		//goaway($_SESSION['return_url']);
		// NOTREACHED
	}

	if (($ret["network"] == NETWORK_OSTATUS) AND get_config('system','ostatus_disabled')) {
		notice( t("OStatus support is disabled. Contact can't be added.") . EOL);
		$submit = "";
		//goaway($_SESSION['return_url']);
		// NOTREACHED
	}

	if ($ret["network"] == NETWORK_PHANTOM) {
		notice( t("The network type couldn't be detected. Contact can't be added.") . EOL);
		$submit = "";
		//goaway($_SESSION['return_url']);
		// NOTREACHED
	}

	if ($ret["network"] == NETWORK_MAIL) {
		$ret["url"] = $ret["addr"];
	}

	if ($ret['network'] === NETWORK_DFRN) {
		$request = $ret["request"];
		$tpl = get_markup_template('dfrn_request.tpl');
	} else {
		$request = App::get_baseurl()."/follow";
		$tpl = get_markup_template('auto_request.tpl');
	}

	$r = q("SELECT `url` FROM `contact` WHERE `uid` = %d AND `self` LIMIT 1", intval($uid));

	if (!$r) {
		notice( t('Permission denied.') . EOL);
		goaway($_SESSION['return_url']);
		// NOTREACHED
	}

	$myaddr = $r[0]["url"];
	$gcontact_id = 0;

	// Makes the connection request for friendica contacts easier
	$_SESSION["fastlane"] = $ret["url"];

	$r = q("SELECT `id`, `location`, `about`, `keywords` FROM `gcontact` WHERE `nurl` = '%s'",
		normalise_link($ret["url"]));

	if (!$r) {
		$r = array(array("location" => "", "about" => "", "keywords" => ""));
	} else {
		$gcontact_id = $r[0]["id"];
	}

	if ($ret['network'] === NETWORK_DIASPORA) {
		$r[0]["location"] = "";
		$r[0]["about"] = "";
	}

	$header = $ret["name"];

	if ($ret["addr"] != "") {
		$header .= " <".$ret["addr"].">";
	}

	//$header .= " (".network_to_name($ret['network'], $ret['url']).")";
	$header = t("Connect/Follow");

	$o  = replace_macros($tpl,array(
			'$header' => htmlentities($header),
			//'$photo' => proxy_url($ret["photo"], false, PROXY_SIZE_SMALL),
			'$desc' => "",
			'$pls_answer' => t('Please answer the following:'),
			'$does_know_you' => array('knowyou', sprintf(t('Does %s know you?'),$ret["name"]), false, '', array(t('No'),t('Yes'))),
			'$add_note' => t('Add a personal note:'),
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
			'$name' => $ret["name"],
			'$url' => $ret["url"],
			'$zrl' => zrl($ret["url"]),
			'$url_label' => t("Profile URL"),
			'$myaddr' => $myaddr,
			'$request' => $request,
			/*'$location' => bbcode($r[0]["location"]),
			'$location_label' => t("Location:"),
			'$about' => bbcode($r[0]["about"], false, false),
			'$about_label' => t("About:"), */
			'$keywords' => $r[0]["keywords"],
			'$keywords_label' => t("Tags:")
	));

	$a->page['aside'] = "";
	profile_load($a, "", 0, get_contact_details_by_url($ret["url"]));

	if ($gcontact_id <> 0) {
		$o .= replace_macros(get_markup_template('section_title.tpl'),
						array('$title' => t('Status Messages and Posts')
		));

		// Show last public posts
		$o .= posts_from_contact_url($a, $ret["url"]);
	}

	return $o;
}

function follow_post(App $a) {

	if (! local_user()) {
		notice( t('Permission denied.') . EOL);
		goaway($_SESSION['return_url']);
		// NOTREACHED
	}

	if ($_REQUEST['cancel']) {
		goaway($_SESSION['return_url']);
	}

	$uid = local_user();
	$url = notags(trim($_REQUEST['url']));
	$return_url = $_SESSION['return_url'];

	// Makes the connection request for friendica contacts easier
	// This is just a precaution if maybe this page is called somewhere directly via POST
	$_SESSION["fastlane"] = $url;

	$result = new_contact($uid,$url,true);

	if ($result['success'] == false) {
		if ($result['message']) {
			notice($result['message']);
		}
		goaway($return_url);
	} elseif ($result['cid']) {
		goaway(App::get_baseurl().'/contacts/'.$result['cid']);
	}

	info( t('Contact added').EOL);

	if (strstr($return_url,'contacts')) {
		goaway(App::get_baseurl().'/contacts/'.$contact_id);
	}

	goaway($return_url);
	// NOTREACHED
}
