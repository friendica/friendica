<?php

require_once('include/Scrape.php');
require_once('include/follow.php');

function follow_content(&$a) {

	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		goaway($_SESSION['return_url']);
		// NOTREACHED
	}

	$uid = local_user();
	$url = notags(trim($_REQUEST['url']));

	$r = q("SELECT `url` FROM `contact` WHERE `uid` = %d AND (`nurl` = '%s' OR `alias` = '%s' OR `alias` = '%s') LIMIT 1",
		intval(local_user()), dbesc(normalise_link($url)), dbesc(normalise_link($url)), dbesc($url));

	if ($r) {
		notice(t('You already added this contact.').EOL);
		goaway($_SESSION['return_url']);
		// NOTREACHED
	}

	$ret = probe_url($url);

	if($ret['network'] === NETWORK_DFRN) {
		$request = $ret["request"];
		$tpl = get_markup_template('dfrn_request.tpl');
	} else {
		$request = $a->get_baseurl()."/follow";
		$tpl = get_markup_template('auto_request.tpl');
	}

	$r = q("SELECT `url` FROM `contact` WHERE `uid` = %d AND `self` LIMIT 1", intval($uid));

	if (!$r) {
		notice( t('Permission denied.') . EOL);
		goaway($_SESSION['return_url']);
		// NOTREACHED
	}

	$myaddr = $r[0]["url"];

	// Makes the connection request for friendica contacts easier
	$_SESSION["fastlane"] = $ret["url"];

	$o  = replace_macros($tpl,array(
			'$header' => $ret["name"]." (".$ret["addr"].")",
			'$photo' => $ret["photo"],
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
                        '$submit' => t('Submit Request'),
                        '$cancel' => t('Cancel'),
                        '$nickname' => "",
                        '$name' => $ret["name"],
                        '$url' => $ret["url"],
                        '$myaddr' => $myaddr,
			'$request' => $request
	));
	return $o;
}

function follow_post(&$a) {

	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		goaway($_SESSION['return_url']);
		// NOTREACHED
	}

	if ($_REQUEST['cancel'])
		goaway($_SESSION['return_url']);

	$uid = local_user();
	$url = notags(trim($_REQUEST['url']));
	$return_url = $_SESSION['return_url'];

	// Makes the connection request for friendica contacts easier
	// This is just a precaution if maybe this page is called somewhere directly via POST
	$_SESSION["fastlane"] = $url;

	$result = new_contact($uid,$url,true);

	if($result['success'] == false) {
		if($result['message'])
			notice($result['message']);
		goaway($return_url);
	} elseif ($result['cid'])
		goaway($a->get_baseurl().'/contacts/'.$result['cid']);

	info( t('Contact added').EOL);

	if(strstr($return_url,'contacts'))
		goaway($a->get_baseurl().'/contacts/'.$contact_id);

	goaway($return_url);
	// NOTREACHED
}
