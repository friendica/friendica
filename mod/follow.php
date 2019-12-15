<?php
/**
 * @file mod/follow.php
 */
use Friendica\App;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Core\System;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Profile;
use Friendica\Network\Probe;
use Friendica\Database\DBA;
use Friendica\Util\Strings;

function follow_post(App $a)
{
	if (!local_user()) {
		throw new \Friendica\Network\HTTPException\ForbiddenException(L10n::t('Access denied.'));
	}

	if (isset($_REQUEST['cancel'])) {
		DI::baseUrl()->redirect('contact');
	}

	$uid = local_user();
	$url = Strings::escapeTags(trim($_REQUEST['url']));
	$return_path = 'follow?url=' . urlencode($url);

	// Makes the connection request for friendica contacts easier
	// This is just a precaution if maybe this page is called somewhere directly via POST
	$_SESSION['fastlane'] = $url;

	$result = Contact::createFromProbe($uid, $url, true);

	if ($result['success'] == false) {
		if ($result['message']) {
			notice($result['message']);
		}
		DI::baseUrl()->redirect($return_path);
	} elseif ($result['cid']) {
		DI::baseUrl()->redirect('contact/' . $result['cid']);
	}

	info(L10n::t('The contact could not be added.'));

	DI::baseUrl()->redirect($return_path);
	// NOTREACHED
}

function follow_content(App $a)
{
	$return_path = 'contact';

	if (!local_user()) {
		notice(L10n::t('Permission denied.'));
		DI::baseUrl()->redirect($return_path);
		// NOTREACHED
	}

	$uid = local_user();

	// Issue 4815: Silently removing a prefixing @
	$url = ltrim(Strings::escapeTags(trim($_REQUEST['url'] ?? '')), '@!');

	// Issue 6874: Allow remote following from Peertube
	if (strpos($url, 'acct:') === 0) {
		$url = str_replace('acct:', '', $url);
	}

	if (!$url) {
		DI::baseUrl()->redirect($return_path);
	}

	$submit = L10n::t('Submit Request');

	// Don't try to add a pending contact
	$r = q("SELECT `pending` FROM `contact` WHERE `uid` = %d AND ((`rel` != %d) OR (`network` = '%s')) AND
		(`nurl` = '%s' OR `alias` = '%s' OR `alias` = '%s') AND
		`network` != '%s' LIMIT 1",
		intval(local_user()), DBA::escape(Contact::FOLLOWER), DBA::escape(Protocol::DFRN), DBA::escape(Strings::normaliseLink($url)),
		DBA::escape(Strings::normaliseLink($url)), DBA::escape($url), DBA::escape(Protocol::STATUSNET));

	if ($r) {
		if ($r[0]['pending']) {
			notice(L10n::t('You already added this contact.'));
			$submit = '';
			//$a->internalRedirect($_SESSION['return_path']);
			// NOTREACHED
		}
	}

	$ret = Probe::uri($url);

	$protocol = Contact::getProtocol($ret['url'], $ret['network']);

	if (($protocol == Protocol::DIASPORA) && !Config::get('system', 'diaspora_enabled')) {
		notice(L10n::t("Diaspora support isn't enabled. Contact can't be added."));
		$submit = '';
		//$a->internalRedirect($_SESSION['return_path']);
		// NOTREACHED
	}

	if (($protocol == Protocol::OSTATUS) && Config::get('system', 'ostatus_disabled')) {
		notice(L10n::t("OStatus support is disabled. Contact can't be added."));
		$submit = '';
		//$a->internalRedirect($_SESSION['return_path']);
		// NOTREACHED
	}

	if ($protocol == Protocol::PHANTOM) {
		notice(L10n::t("The network type couldn't be detected. Contact can't be added."));
		$submit = '';
		//$a->internalRedirect($_SESSION['return_path']);
		// NOTREACHED
	}

	if ($protocol == Protocol::MAIL) {
		$ret['url'] = $ret['addr'];
	}

	if (($protocol === Protocol::DFRN) && !DBA::isResult($r)) {
		$request = $ret['request'];
		$tpl = Renderer::getMarkupTemplate('dfrn_request.tpl');
	} else {
		$request = System::baseUrl() . '/follow';
		$tpl = Renderer::getMarkupTemplate('auto_request.tpl');
	}

	$r = q("SELECT `url` FROM `contact` WHERE `uid` = %d AND `self` LIMIT 1", intval($uid));

	if (!$r) {
		notice(L10n::t('Permission denied.'));
		DI::baseUrl()->redirect($return_path);
		// NOTREACHED
	}

	$myaddr = $r[0]['url'];
	$gcontact_id = 0;

	// Makes the connection request for friendica contacts easier
	$_SESSION['fastlane'] = $ret['url'];

	$r = q("SELECT `id`, `location`, `about`, `keywords` FROM `gcontact` WHERE `nurl` = '%s'",
		Strings::normaliseLink($ret['url']));

	if (!$r) {
		$r = [['location' => '', 'about' => '', 'keywords' => '']];
	} else {
		$gcontact_id = $r[0]['id'];
	}

	if ($protocol === Protocol::DIASPORA) {
		$r[0]['location'] = '';
		$r[0]['about'] = '';
	}

	$o = Renderer::replaceMacros($tpl, [
		'$header'        => L10n::t('Connect/Follow'),
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
		'$keywords'      => $r[0]['keywords'],
		'$keywords_label'=> L10n::t('Tags:')
	]);

	$a->page['aside'] = '';

	$profiledata = Contact::getDetailsByURL($ret['url']);
	if ($profiledata) {
		Profile::load($a, '', 0, $profiledata, false);
	}

	if ($gcontact_id <> 0) {
		$o .= Renderer::replaceMacros(Renderer::getMarkupTemplate('section_title.tpl'),
			['$title' => L10n::t('Status Messages and Posts')]
		);

		// Show last public posts
		$o .= Contact::getPostsFromUrl($ret['url']);
	}

	return $o;
}
