<?php
/**
 * @file mod/nogroup.php
 */
use Friendica\App;
use Friendica\Content\ContactSelector;
use Friendica\Core\L10n;
use Friendica\Database\DBM;
use Friendica\Model\Contact;
use Friendica\Model\Group;

function nogroup_init(App $a)
{
	if (! local_user()) {
		return;
	}

	if (! x($a->page, 'aside')) {
		$a->page['aside'] = '';
	}

	$a->page['aside'] .= Group::sidebarWidget('contacts', 'group', 'extended');
}

function nogroup_content(App $a)
{
	if (! local_user()) {
		notice(L10n::t('Permission denied.') . EOL);
		return '';
	}

	$r = Contact::getUngroupedList(local_user());
	if (DBM::is_result($r)) {
		$a->set_pager_total($r[0]['total']);
	}
	$r = Contact::getUngroupedList(local_user(), $a->pager['start'], $a->pager['itemspage']);
	if (DBM::is_result($r)) {
		foreach ($r as $rr) {
			$contact_details = Contact::getDetailsByURL($rr['url'], local_user(), $rr);

			$contacts[] = [
				'img_hover' => L10n::t('Visit %s\'s profile [%s]', $contact_details['name'], $rr['url']),
				'edit_hover' => L10n::t('Edit contact'),
				'photo_menu' => Contact::photoMenu($rr),
				'id' => $rr['id'],
				'thumb' => proxy_url($contact_details['thumb'], false, PROXY_SIZE_THUMB),
				'name' => $contact_details['name'],
				'username' => $contact_details['name'],
				'details'       => $contact_details['location'],
				'tags'          => $contact_details['keywords'],
				'about'         => $contact_details['about'],
				'itemurl' => (($contact_details['addr'] != "") ? $contact_details['addr'] : $rr['url']),
				'url' => $rr['url'],
				'network' => ContactSelector::networkToName($rr['network'], $rr['url']),
			];
		}
	}

	$tpl = get_markup_template("nogroup-template.tpl");
	$o = replace_macros(
		$tpl,
		[
		'$header' => L10n::t('Contacts who are not members of a group'),
		'$contacts' => $contacts,
		'$paginate' => paginate($a)]
	);

	return $o;
}
