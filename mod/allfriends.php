<?php
/**
 * @file mod/allfriends.php
 */
use Friendica\App;
use Friendica\Content\ContactSelector;
use Friendica\Core\System;
use Friendica\Database\DBM;
use Friendica\Model\Contact;
use Friendica\Model\GContact;
use Friendica\Model\Profile;
use dba;

require_once 'include/dba.php';
require_once 'mod/contacts.php';

function allfriends_content(App $a)
{
	$o = '';
	if (!local_user()) {
		notice(t('Permission denied.') . EOL);
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

	$contact = dba::selectFirst('contact', ['name', 'url', 'photo'], ['id' => $cid, 'uid' => local_user()]);

	if (!DBM::isResult($contact)) {
		return;
	}

	$a->page['aside'] = "";
	Profile::load($a, "", 0, Contact::getDetailsByURL($contact["url"]));

	$total = GContact::countAllFriends(local_user(), $cid);

	$a->set_pager_total($total);

	$r = GContact::allFriends(local_user(), $cid, $a->pager['start'], $a->pager['itemspage']);
	if (!DBM::isResult($r)) {
		$o .= t('No friends to display.');
		return $o;
	}

	$id = 0;

	$entries = [];
	foreach ($r as $rr) {
		//get further details of the contact
		$contact_details = Contact::getDetailsByURL($rr['url'], $uid, $rr);

		$photo_menu = '';

		$connlnk = '';
		// $rr[cid] is only available for common contacts. So if the contact is a common one, use contact_photo_menu to generate the photo_menu
		// If the contact is not common to the user, Connect/Follow' will be added to the photo menu
		if ($rr['cid']) {
			$rr['id'] = $rr['cid'];
			$photo_menu = Contact::photoMenu($rr);
		} else {
			$connlnk = System::baseUrl() . '/follow/?url=' . $rr['url'];
			$photo_menu = [
				'profile' => [t("View Profile"), Profile::zrl($rr['url'])],
				'follow' => [t("Connect/Follow"), $connlnk]
			];
		}

		$entry = [
			'url'          => $rr['url'],
			'itemurl'      => defaults($contact_details, 'addr', $rr['url']),
			'name'         => htmlentities($contact_details['name']),
			'thumb'        => proxy_url($contact_details['thumb'], false, PROXY_SIZE_THUMB),
			'img_hover'    => htmlentities($contact_details['name']),
			'details'      => $contact_details['location'],
			'tags'         => $contact_details['keywords'],
			'about'        => $contact_details['about'],
			'account_type' => Contact::getAccountType($contact_details),
			'network'      => ContactSelector::networkToName($contact_details['network'], $contact_details['url']),
			'photo_menu'   => $photo_menu,
			'conntxt'      => t('Connect'),
			'connlnk'      => $connlnk,
			'id'           => ++$id,
		];
		$entries[] = $entry;
	}

	$tab_str = contacts_tab($a, $cid, 3);

	$tpl = get_markup_template('viewcontact_template.tpl');

	$o .= replace_macros($tpl, [
		//'$title' => sprintf( t('Friends of %s'), htmlentities($c[0]['name'])),
		'$tab_str' => $tab_str,
		'$contacts' => $entries,
		'$paginate' => paginate($a),
	]);

	return $o;
}
