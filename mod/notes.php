<?php
/**
 * @file mod/notes.php
 */

use Friendica\App;
use Friendica\Content\Nav;
use Friendica\Content\Pager;
use Friendica\Core\L10n;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\Profile;

function notes_init(App $a)
{
	if (! local_user()) {
		return;
	}

	Nav::setSelected('home');
}


function notes_content(App $a, $update = false)
{
	if (!local_user()) {
		notice(L10n::t('Permission denied.') . EOL);
		return;
	}

	$o = Profile::getTabs($a, 'notes', true);

	if (!$update) {
		$o .= '<h3>' . L10n::t('Personal Notes') . '</h3>';

		$x = [
			'is_owner' => true,
			'allow_location' => (($a->user['allow_location']) ? true : false),
			'default_location' => $a->user['default-location'],
			'nickname' => $a->user['nickname'],
			'lockstate' => 'lock',
			'acl' => '',
			'bang' => '',
			'visitor' => 'block',
			'profile_uid' => local_user(),
			'button' => L10n::t('Save'),
			'acl_data' => '',
		];

		$o .= status_editor($a, $x, $a->contact['id']);
	}

	$condition = ['uid' => local_user(), 'post-type' => Item::PT_PERSONAL_NOTE, 'gravity' => GRAVITY_PARENT,
		'contact-id'=> $a->contact['id']];

	$pager = new Pager(DI::args()->getQueryString(), 40);

	$params = ['order' => ['created' => true],
		'limit' => [$pager->getStart(), $pager->getItemsPerPage()]];
	$r = Item::selectThreadForUser(local_user(), ['uri'], $condition, $params);

	$count = 0;

	if (DBA::isResult($r)) {
		$notes = Item::inArray($r);

		$count = count($notes);

		$o .= conversation($a, $notes, $pager, 'notes', $update);
	}

	$o .= $pager->renderMinimal($count);

	return $o;
}
