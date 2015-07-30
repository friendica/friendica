<?php
require_once('include/diaspora.php');

function contact_profile_assign($current,$foreign_net) {

	$o = '';

	$disabled = (($foreign_net) ? ' disabled="true" ' : '');

	$o .= "<select id=\"contact-profile-selector\" $disabled name=\"profile-assign\" />\r\n";

	$r = q("SELECT `id`, `profile-name` FROM `profile` WHERE `uid` = %d",
                        intval($_SESSION['uid']));

	if(count($r)) {
		foreach($r as $rr) {
			$selected = (($rr['id'] == $current) ? " selected=\"selected\" " : "");
			$o .= "<option value=\"{$rr['id']}\" $selected >{$rr['profile-name']}</option>\r\n";
		}
	}
	$o .= "</select>\r\n";
	return $o;
}


function contact_reputation($current) {

	$o = '';
	$o .= "<select id=\"contact-reputation-selector\" name=\"reputation\" />\r\n";

	$rep = array(
		0 => t('Unknown | Not categorised'),
		1 => t('Block immediately'),
		2 => t('Shady, spammer, self-marketer'),
		3 => t('Known to me, but no opinion'),
		4 => t('OK, probably harmless'),
		5 => t('Reputable, has my trust')
	);

	foreach($rep as $k => $v) {
		$selected = (($k == $current) ? " selected=\"selected\" " : "");
		$o .= "<option value=\"$k\" $selected >$v</option>\r\n";
	}
	$o .= "</select>\r\n";
	return $o;
}


function contact_poll_interval($current, $disabled = false) {

	$dis = (($disabled) ? ' disabled="disabled" ' : '');
	$o = '';
	$o .= "<select id=\"contact-poll-interval\" name=\"poll\" $dis />" . "\r\n";

	$rep = array(
		0 => t('Frequently'),
		1 => t('Hourly'),
		2 => t('Twice daily'),
		3 => t('Daily'),
		4 => t('Weekly'),
		5 => t('Monthly')
	);

	foreach($rep as $k => $v) {
		$selected = (($k == $current) ? " selected=\"selected\" " : "");
		$o .= "<option value=\"$k\" $selected >$v</option>\r\n";
	}
	$o .= "</select>\r\n";
	return $o;
}


function network_to_name($s, $profile = "") {

	$nets = array(
		NETWORK_DFRN     => t('Friendica'),
		NETWORK_OSTATUS  => t('OStatus'),
		NETWORK_FEED     => t('RSS/Atom'),
		NETWORK_MAIL     => t('Email'),
		NETWORK_DIASPORA => t('Diaspora'),
		NETWORK_FACEBOOK => t('Facebook'),
		NETWORK_ZOT      => t('Zot!'),
		NETWORK_LINKEDIN => t('LinkedIn'),
		NETWORK_XMPP     => t('XMPP/IM'),
		NETWORK_MYSPACE  => t('MySpace'),
		NETWORK_MAIL2    => t('Email'),
		NETWORK_GPLUS    => t('Google+'),
		NETWORK_PUMPIO   => t('pump.io'),
		NETWORK_TWITTER  => t('Twitter'),
		NETWORK_DIASPORA2 => t('Diaspora Connector'),
		NETWORK_STATUSNET => t('Statusnet'),
		NETWORK_APPNET => t('App.net')
	);

	call_hooks('network_to_name', $nets);

	$search  = array_keys($nets);
	$replace = array_values($nets);

	$networkname = str_replace($search,$replace,$s);

	if (($s == NETWORK_DIASPORA) AND ($profile != "") AND diaspora_is_redmatrix($profile))
		$networkname = t("Redmatrix");

	return $networkname;
}
