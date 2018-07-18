<?php
/**
 * @file mod/follow.php
 */
use Friendica\App;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\System;
use Friendica\Model\Contact;
use Friendica\Model\Profile;
use Friendica\Network\Probe;
use Friendica\Database\DBM;

function follow_post(App $a)
{
	if (!local_user()) {
		notice(L10n::t('Permission denied.'));
		goaway($_SESSION['return_url']);
		// NOTREACHED
	}

	if (isset($_REQUEST['cancel'])) {
		goaway($_SESSION['return_url']);
	}

	$uid = local_user();
	$url = notags(trim($_REQUEST['url']));
	$return_url = $_SESSION['return_url'];

	// Makes the connection request for friendica contacts easier
	// This is just a precaution if maybe this page is called somewhere directly via POST
	$_SESSION['fastlane'] = $url;

	$result = Contact::createFromProbe($uid, $url, true);

	if ($result['success'] == false) {
		if ($result['message']) {
			notice($result['message']);
		}
		goaway($return_url);
	} elseif ($result['cid']) {
		goaway(System::baseUrl() . '/contacts/' . $result['cid']);
	}

	info(L10n::t('The contact could not be added.'));

	goaway($return_url);
	// NOTREACHED
}

function follow_content(App $a)
{
	if (!local_user()) {
		notice(L10n::t('Permission denied.'));
		goaway($_SESSION['return_url']);
		// NOTREACHED
	}

	$uid = local_user();
	$url = notags(trim($_REQUEST['url']));

	$submit = L10n::t('Submit Request');

	// Don't try to add a pending contact
	$r = q("SELECT `pending` FROM `contact` WHERE `uid` = %d AND ((`rel` != %d) OR (`network` = '%s')) AND
		(`nurl` = '%s' OR `alias` = '%s' OR `alias` = '%s') AND
		`network` != '%s' LIMIT 1",
		intval(local_user()), dbesc(CONTACT_IS_FOLLOWER), dbesc(NETWORK_DFRN), dbesc(normalise_link($url)),
		dbesc(normalise_link($url)), dbesc($url), dbesc(NETWORK_STATUSNET));

	if ($r) {
		if ($r[0]['pending']) {
			notice(L10n::t('You already added this contact.'));
			$submit = '';
			//goaway($_SESSION['return_url']);
			// NOTREACHED
		}
	}

	$ret = Probe::uri($url);

	if (($ret['network'] == NETWORK_DIASPORA) && !Config::get('system', 'diaspora_enabled')) {
		notice(L10n::t("Diaspora support isn't enabled. Contact can't be added."));
		$submit = '';
		//goaway($_SESSION['return_url']);
		// NOTREACHED
	}

	if (($ret['network'] == NETWORK_OSTATUS) && Config::get('system', 'ostatus_disabled')) {
		notice(L10n::t("OStatus support is disabled. Contact can't be added."));
		$submit = '';
		//goaway($_SESSION['return_url']);
		// NOTREACHED
	}

	if ($ret['network'] == NETWORK_PHANTOM) {
		notice(L10n::t("The network type couldn't be detected. Contact can't be added."));
		$submit = '';
		//goaway($_SESSION['return_url']);
		// NOTREACHED
	}

	if ($ret['network'] == NETWORK_MAIL) {
		$ret['url'] = $ret['addr'];
	}

	if (($ret['network'] === NETWORK_DFRN) && !DBM::is_result($r)) {
		$request = $ret['request'];
		$tpl = get_markup_template('dfrn_request.tpl');
	} else {
		$request = System::baseUrl() . '/follow';
		$tpl = get_markup_template('auto_request.tpl');
	}

	$r = q("SELECT `url` FROM `contact` WHERE `uid` = %d AND `self` LIMIT 1", intval($uid));

	if (!$r) {
		notice(L10n::t('Permission denied.'));
		goaway($_SESSION['return_url']);
		// NOTREACHED
	}

	$myaddr = $r[0]['url'];
	$gcontact_id = 0;

	// Makes the connection request for friendica contacts easier
	$_SESSION['fastlane'] = $ret['url'];

	$r = q("SELECT `id`, `location`, `about`, `keywords` FROM `gcontact` WHERE `nurl` = '%s'",
		normalise_link($ret['url']));

	if (!$r) {
		$r = [['location' => '', 'about' => '', 'keywords' => '']];
	} else {
		$gcontact_id = $r[0]['id'];
	}

	if ($ret['network'] === NETWORK_DIASPORA) {
		$r[0]['location'] = '';
		$r[0]['about'] = '';
	}

	$header = L10n::t('Connect/Follow');

	$o = replace_macros($tpl, [
		'$header'        => htmlentities($header),
		//'$photo' => proxy_url($ret['photo'], false, PROXY_SIZE_SMALL),
		'$desc'          => '',
		'$pls_answer'    => L10n::t('Please answer the following:'),
		'$does_know_you' => ['knowyou', L10n::t('Does %s know you?', $ret['name']), false, '', [L10n::t('No'), L10n::t('Yes')]],
		'$add_note'      => L10n::t('Add a personal note:'),
		'$page_desc'     => '',
		'$friendica'     => '',
		'$statusnet'     => '',
		'$diaspora'      => '',
		'$diasnote'      => '',
		'$your_address'  => L10n::t('Your Identity Address:'),
		'$invite_desc'   => '',
		'$emailnet'      => '',
		'$submit'        => $submit,
		'$cancel'        => L10n::t('Cancel'),
		'$nickname'      => '',
		'$name'          => $ret['name'],
		'$url'           => $ret['url'],
		'$zrl'           => Profile::zrl($ret['url']),
		'$url_label'     => L10n::t('Profile URL'),
		'$myaddr'        => $myaddr,
		'$request'       => $request,
		/*
		 * @TODO commented out?
		'$location'      => Friendica\Content\Text\BBCode::::convert($r[0]['location']),
		'$location_label'=> L10n::t('Location:'),
		'$about'         => Friendica\Content\Text\BBCode::::convert($r[0]['about'], false, false),
		'$about_label'   => L10n::t('About:'),
		*/
		'$keywords'      => $r[0]['keywords'],
		'$keywords_label'=> L10n::t('Tags:')
	]);

	$a->page['aside'] = '';

	$profiledata = Contact::getDetailsByURL($ret['url']);
	if ($profiledata) {
		Profile::load($a, '', 0, $profiledata, false);
	}

	if ($gcontact_id <> 0) {
		$o .= replace_macros(get_markup_template('section_title.tpl'),
			['$title' => L10n::t('Status Messages and Posts')]
		);

		// Show last public posts
		$o .= Contact::getPostsFromUrl($ret['url']);
	}

	return $o;
}
