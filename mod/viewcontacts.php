<?php
/**
 * @file mod/viewcontacts.php
 */
use Friendica\App;
use Friendica\Content\ContactSelector;
use Friendica\Content\Nav;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Model\Profile;
use Friendica\Util\Proxy as ProxyUtils;
use Friendica\Core\System;

function viewcontacts_init(App $a)
{
	if (Config::get('system', 'block_public') && !local_user() && !remote_user()) {
		System::httpExit(403, ["title" => L10n::t('Access denied.')]);
	}

	if ($a->argc < 2) {
		System::httpExit(403, ["title" => L10n::t('Access denied.')]);
	}

	Nav::setSelected('home');

	$nick = $a->argv[1];
	$r = q("SELECT * FROM `user` WHERE `nickname` = '%s' AND `blocked` = 0 LIMIT 1",
		DBA::escape($nick)
	);

	if (!DBA::isResult($r)) {
		System::httpExit(404, ["title" => L10n::t('Page not found.')]);
	}

	$a->data['user'] = $r[0];
	$a->profile_uid = $r[0]['uid'];
	$is_owner = (local_user() && (local_user() == $a->profile_uid));

	Profile::load($a, $a->argv[1]);
}

function viewcontacts_content(App $a)
{
	if (Config::get('system', 'block_public') && !local_user() && !remote_user()) {
		notice(L10n::t('Public access denied.') . EOL);
		return;
	}

	$is_owner = $a->profile['profile_uid'] == local_user();

	$o = "";

	// tabs
	$o .= Profile::getTabs($a, $is_owner, $a->data['user']['nickname']);

	if (!count($a->profile) || $a->profile['hide-friends']) {
		notice(L10n::t('Permission denied.') . EOL);
		return $o;
	}

	$r = q("SELECT COUNT(*) AS `total` FROM `contact`
		WHERE `uid` = %d AND NOT `blocked` AND NOT `pending`
			AND NOT `hidden` AND NOT `archive`
			AND `network` IN ('%s', '%s', '%s')",
		intval($a->profile['uid']),
		DBA::escape(Protocol::DFRN),
		DBA::escape(Protocol::DIASPORA),
		DBA::escape(Protocol::OSTATUS)
	);
	if (DBA::isResult($r)) {
		$a->set_pager_total($r[0]['total']);
	}

	$r = q("SELECT * FROM `contact`
		WHERE `uid` = %d AND NOT `blocked` AND NOT `pending`
			AND NOT `hidden` AND NOT `archive`
			AND `network` IN ('%s', '%s', '%s')
		ORDER BY `name` ASC LIMIT %d, %d",
		intval($a->profile['uid']),
		DBA::escape(Protocol::DFRN),
		DBA::escape(Protocol::DIASPORA),
		DBA::escape(Protocol::OSTATUS),
		intval($a->pager['start']),
		intval($a->pager['itemspage'])
	);
	if (!DBA::isResult($r)) {
		info(L10n::t('No contacts.').EOL);
		return $o;
	}

	$contacts = [];

	foreach ($r as $rr) {
		/// @TODO This triggers an E_NOTICE if 'self' is not there
		if ($rr['self']) {
			continue;
		}

		$contact_details = Contact::getDetailsByURL($rr['url'], $a->profile['uid'], $rr);

		$contacts[] = [
			'id' => $rr['id'],
			'img_hover' => L10n::t('Visit %s\'s profile [%s]', $contact_details['name'], $rr['url']),
			'photo_menu' => Contact::photoMenu($rr),
			'thumb' => ProxyUtils::proxifyUrl($contact_details['thumb'], false, ProxyUtils::SIZE_THUMB),
			'name' => htmlentities(substr($contact_details['name'], 0, 20)),
			'username' => htmlentities($contact_details['name']),
			'details'       => $contact_details['location'],
			'tags'          => $contact_details['keywords'],
			'about'         => $contact_details['about'],
			'account_type'  => Contact::getAccountType($contact_details),
			'url' => Contact::magicLink($rr['url']),
			'sparkle' => '',
			'itemurl' => (($contact_details['addr'] != "") ? $contact_details['addr'] : $rr['url']),
			'network' => ContactSelector::networkToName($rr['network'], $rr['url']),
		];
	}


	$tpl = get_markup_template("viewcontact_template.tpl");
	$o .= replace_macros($tpl, [
		'$title' => L10n::t('Contacts'),
		'$contacts' => $contacts,
		'$paginate' => paginate($a),
	]);

	return $o;
}
