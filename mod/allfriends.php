<?php
/**
 * @file mod/allfriends.php
 */

use Friendica\App;
use Friendica\Content\ContactSelector;
use Friendica\Content\Pager;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Model;
use Friendica\Module;
use Friendica\Util\Proxy as ProxyUtils;


require_once 'include/dba.php';

function allfriends_content(App $a)
{
	$o = '';
	if (!Session::user()->isLocal()) {
		notice(L10n::t('Permission denied.') . EOL);
		return;
	}

	$cid = 0;
	if ($a->argc > 1) {
		$cid = intval($a->argv[1]);
	}

	if (!$cid) {
		return;
	}

	$uid = $a->user['uid'];

	$contact = DBA::selectFirst('contact', ['name', 'url', 'photo', 'uid', 'id'], ['id' => $cid, 'uid' => Session::user()->getUid()]);

	if (!DBA::isResult($contact)) {
		return;
	}

	$a->page['aside'] = "";
	Model\Profile::load($a, "", 0, Model\Contact::getDetailsByURL($contact["url"]));

	$total = Model\GContact::countAllFriends(Session::user()->getUid(), $cid);

	$pager = new Pager($a->query_string);

	$r = Model\GContact::allFriends(Session::user()->getUid(), $cid, $pager->getStart(), $pager->getItemsPerPage());
	if (!DBA::isResult($r)) {
		$o .= L10n::t('No friends to display.');
		return $o;
	}

	$id = 0;

	$entries = [];
	foreach ($r as $rr) {
		//get further details of the contact
		$contact_details = Model\Contact::getDetailsByURL($rr['url'], $uid, $rr);

		$photo_menu = '';

		$connlnk = '';
		// $rr[cid] is only available for common contacts. So if the contact is a common one, use contact_photo_menu to generate the photo_menu
		// If the contact is not common to the user, Connect/Follow' will be added to the photo menu
		if ($rr['cid']) {
			$rr['id'] = $rr['cid'];
			$photo_menu = Model\Contact::photoMenu($rr);
		} else {
			$connlnk = System::baseUrl() . '/follow/?url=' . $rr['url'];
			$photo_menu = [
				'profile' => [L10n::t("View Profile"), Model\Contact::magicLink($rr['url'])],
				'follow' => [L10n::t("Connect/Follow"), $connlnk]
			];
		}

		$entry = [
			'url'          => $rr['url'],
			'itemurl'      => defaults($contact_details, 'addr', $rr['url']),
			'name'         => htmlentities($contact_details['name']),
			'thumb'        => ProxyUtils::proxifyUrl($contact_details['thumb'], false, ProxyUtils::SIZE_THUMB),
			'img_hover'    => htmlentities($contact_details['name']),
			'details'      => $contact_details['location'],
			'tags'         => $contact_details['keywords'],
			'about'        => $contact_details['about'],
			'account_type' => Model\Contact::getAccountType($contact_details),
			'network'      => ContactSelector::networkToName($contact_details['network'], $contact_details['url']),
			'photo_menu'   => $photo_menu,
			'conntxt'      => L10n::t('Connect'),
			'connlnk'      => $connlnk,
			'id'           => ++$id,
		];
		$entries[] = $entry;
	}

	$tab_str = Module\Contact::getTabsHTML($a, $contact, 4);

	$tpl = Renderer::getMarkupTemplate('viewcontact_template.tpl');

	$o .= Renderer::replaceMacros($tpl, [
		//'$title' => L10n::t('Friends of %s', htmlentities($c[0]['name'])),
		'$tab_str' => $tab_str,
		'$contacts' => $entries,
		'$paginate' => $pager->renderFull($total),
	]);

	return $o;
}
