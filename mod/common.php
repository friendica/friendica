<?php
/**
 * @file include/common.php
 */
use Friendica\App;
use Friendica\Content\ContactSelector;
use Friendica\Database\DBM;
use Friendica\Model\Contact;
use Friendica\Model\GContact;
use Friendica\Model\Profile;
use dba;

require_once 'include/dba.php';
require_once 'mod/contacts.php';

function common_content(App $a)
{
	$o = '';

	$cmd = $a->argv[1];
	$uid = intval($a->argv[2]);
	$cid = intval($a->argv[3]);
	$zcid = 0;

	if (!local_user()) {
		notice(t('Permission denied.') . EOL);
		return;
	}

	if ($cmd !== 'loc' && $cmd != 'rem') {
		return;
	}

	if (!$uid) {
		return;
	}

	if ($cmd === 'loc' && $cid) {
		$contact = dba::selectFirst('contact', ['name', 'url', 'photo'], ['id' => $cid, 'uid' => $uid]);

		if (DBM::isResult($contact)) {
			$a->page['aside'] = "";
			Profile::load($a, "", 0, Contact::getDetailsByURL($contact["url"]));
		}
	} else {
		$contact = dba::selectFirst('contact', ['name', 'url', 'photo'], ['self' => true, 'uid' => $uid]);

		if (DBM::isResult($contact)) {
			$vcard_widget = replace_macros(get_markup_template("vcard-widget.tpl"), [
				'$name' => htmlentities($contact['name']),
				'$photo' => $contact['photo'],
				'url' => 'contacts/' . $cid
			]);

			if (!x($a->page, 'aside')) {
				$a->page['aside'] = '';
			}
			$a->page['aside'] .= $vcard_widget;
		}
	}

	if (!DBM::isResult($contact)) {
		return;
	}

	if (!$cid && Profile::getMyURL()) {
		$contact = dba::selectFirst('contact', ['id'], ['nurl' => normalise_link(Profile::getMyURL()), 'uid' => $uid]);
		if (DBM::isResult($contact)) {
			$cid = $contact['id'];
		} else {
			$gcontact = dba::selectFirst('gcontact', ['id'], ['nurl' => normalise_link(Profile::getMyURL())]);
			if (DBM::isResult($gcontact)) {
				$zcid = $gcontact['id'];
			}
		}
	}

	if ($cid == 0 && $zcid == 0) {
		return;
	}

	if ($cid) {
		$t = GContact::countCommonFriends($uid, $cid);
	} else {
		$t = GContact::countCommonFriendsZcid($uid, $zcid);
	}

	if ($t > 0) {
		$a->set_pager_total($t);
	} else {
		notice(t('No contacts in common.') . EOL);
		return $o;
	}

	if ($cid) {
		$r = GContact::commonFriends($uid, $cid, $a->pager['start'], $a->pager['itemspage']);
	} else {
		$r = GContact::commonFriendsZcid($uid, $zcid, $a->pager['start'], $a->pager['itemspage']);
	}

	if (!DBM::isResult($r)) {
		return $o;
	}

	$id = 0;

	$entries = [];
	foreach ($r as $rr) {
		//get further details of the contact
		$contact_details = Contact::getDetailsByURL($rr['url'], $uid);

		// $rr['id'] is needed to use contact_photo_menu()
		/// @TODO Adding '/" here avoids E_NOTICE on missing constants
		$rr['id'] = $rr['cid'];

		$photo_menu = Contact::photoMenu($rr);

		$entry = [
			'url'          => $rr['url'],
			'itemurl'      => defaults($contact_details, 'addr', $rr['url']),
			'name'         => $contact_details['name'],
			'thumb'        => proxy_url($contact_details['thumb'], false, PROXY_SIZE_THUMB),
			'img_hover'    => htmlentities($contact_details['name']),
			'details'      => $contact_details['location'],
			'tags'         => $contact_details['keywords'],
			'about'        => $contact_details['about'],
			'account_type' => Contact::getAccountType($contact_details),
			'network'      => ContactSelector::networkToName($contact_details['network'], $contact_details['url']),
			'photo_menu'   => $photo_menu,
			'id'           => ++$id,
		];
		$entries[] = $entry;
	}

	$title = '';
	$tab_str = '';
	if ($cmd === 'loc' && $cid && local_user() == $uid) {
		$tab_str = contacts_tab($a, $cid, 4);
	} else {
		$title = t('Common Friends');
	}

	$tpl = get_markup_template('viewcontact_template.tpl');

	$o .= replace_macros($tpl, [
		'$title'    => $title,
		'$tab_str'  => $tab_str,
		'$contacts' => $entries,
		'$paginate' => paginate($a),
	]);

	return $o;
}
