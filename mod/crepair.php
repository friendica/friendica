<?php
/**
 * @file mod/crepair.php
 */

use Friendica\App;
use Friendica\Content\Text;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\Model;
use Friendica\Module;

function crepair_init(App $a)
{
	if (!local_user()) {
		return;
	}

	$contact = null;
	if (($a->argc == 2) && intval($a->argv[1])) {
		$contact = DBA::selectFirst('contact', [], ['uid' => local_user(), 'id' => $a->argv[1]]);
	}

	if (!x($a->page, 'aside')) {
		$a->page['aside'] = '';
	}

	if (DBA::isResult($contact)) {
		$a->data['contact'] = $contact;
		Model\Profile::load($a, "", 0, Model\Contact::getDetailsByURL($contact["url"]));
	}
}

function crepair_post(App $a)
{
	if (!local_user()) {
		return;
	}

	$cid = (($a->argc > 1) ? intval($a->argv[1]) : 0);

	$contact = null;
	if ($cid) {
		$contact = DBA::selectFirst('contact', [], ['id' => $cid, 'uid' => local_user()]);
	}

	if (!DBA::isResult($contact)) {
		return;
	}

	$name        = defaults($_POST, 'name'       , $contact['name']);
	$nick        = defaults($_POST, 'nick'       , '');
	$url         = defaults($_POST, 'url'        , '');
	$request     = defaults($_POST, 'request'    , '');
	$confirm     = defaults($_POST, 'confirm'    , '');
	$notify      = defaults($_POST, 'notify'     , '');
	$poll        = defaults($_POST, 'poll'       , '');
	$attag       = defaults($_POST, 'attag'      , '');
	$photo       = defaults($_POST, 'photo'      , '');
	$remote_self = defaults($_POST, 'remote_self', false);
	$nurl        = Text::normaliseLink($url);

	$r = q("UPDATE `contact` SET `name` = '%s', `nick` = '%s', `url` = '%s', `nurl` = '%s', `request` = '%s', `confirm` = '%s', `notify` = '%s', `poll` = '%s', `attag` = '%s' , `remote_self` = %d
		WHERE `id` = %d AND `uid` = %d",
		DBA::escape($name),
		DBA::escape($nick),
		DBA::escape($url),
		DBA::escape($nurl),
		DBA::escape($request),
		DBA::escape($confirm),
		DBA::escape($notify),
		DBA::escape($poll),
		DBA::escape($attag),
		intval($remote_self),
		intval($contact['id']),
		local_user()
	);

	if ($photo) {
		App::logger('mod-crepair: updating photo from ' . $photo);

		Model\Contact::updateAvatar($photo, local_user(), $contact['id']);
	}

	if ($r) {
		info(L10n::t('Contact settings applied.') . EOL);
	} else {
		notice(L10n::t('Contact update failed.') . EOL);
	}

	return;
}

function crepair_content(App $a)
{
	if (!local_user()) {
		notice(L10n::t('Permission denied.') . EOL);
		return;
	}

	$cid = (($a->argc > 1) ? intval($a->argv[1]) : 0);

	$contact = null;
	if ($cid) {
		$contact = DBA::selectFirst('contact', [], ['id' => $cid, 'uid' => local_user()]);
	}

	if (!DBA::isResult($contact)) {
		notice(L10n::t('Contact not found.') . EOL);
		return;
	}

	$warning = L10n::t('<strong>WARNING: This is highly advanced</strong> and if you enter incorrect information your communications with this contact may stop working.');
	$info = L10n::t('Please use your browser \'Back\' button <strong>now</strong> if you are uncertain what to do on this page.');

	$returnaddr = "contact/$cid";

	$allow_remote_self = Config::get('system', 'allow_users_remote_self');

	// Disable remote self for everything except feeds.
	// There is an issue when you repeat an item from maybe twitter and you got comments from friendica and twitter
	// Problem is, you couldn't reply to both networks.
	if (!in_array($contact['network'], [Protocol::FEED, Protocol::DFRN, Protocol::DIASPORA, Protocol::TWITTER])) {
		$allow_remote_self = false;
	}

	if ($contact['network'] == Protocol::FEED) {
		$remote_self_options = ['0' => L10n::t('No mirroring'), '1' => L10n::t('Mirror as forwarded posting'), '2' => L10n::t('Mirror as my own posting')];
	} else {
		$remote_self_options = ['0' => L10n::t('No mirroring'), '2' => L10n::t('Mirror as my own posting')];
	}

	$update_profile = in_array($contact['network'], [Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::DIASPORA, Protocol::OSTATUS]);

	$tab_str = Module\Contact::getTabsHTML($a, $contact, 5);

	$tpl = Text::getMarkupTemplate('crepair.tpl');
	$o = App::replaceMacros($tpl, [
		'$tab_str'        => $tab_str,
		'$warning'        => $warning,
		'$info'           => $info,
		'$returnaddr'     => $returnaddr,
		'$return'         => L10n::t('Return to contact editor'),
		'$update_profile' => $update_profile,
		'$udprofilenow'   => L10n::t('Refetch contact data'),
		'$contact_id'     => $contact['id'],
		'$lbl_submit'     => L10n::t('Submit'),
		'$label_remote_self' => L10n::t('Remote Self'),
		'$allow_remote_self' => $allow_remote_self,
		'$remote_self' => ['remote_self',
			L10n::t('Mirror postings from this contact'),
			$contact['remote_self'],
			L10n::t('Mark this contact as remote_self, this will cause friendica to repost new entries from this contact.'),
			$remote_self_options
		],

		'$name'		=> ['name', L10n::t('Name') , htmlentities($contact['name'])],
		'$nick'		=> ['nick', L10n::t('Account Nickname'), htmlentities($contact['nick'])],
		'$attag'	=> ['attag', L10n::t('@Tagname - overrides Name/Nickname'), $contact['attag']],
		'$url'		=> ['url', L10n::t('Account URL'), $contact['url']],
		'$request'	=> ['request', L10n::t('Friend Request URL'), $contact['request']],
		'confirm'	=> ['confirm', L10n::t('Friend Confirm URL'), $contact['confirm']],
		'notify'	=> ['notify', L10n::t('Notification Endpoint URL'), $contact['notify']],
		'poll'		=> ['poll', L10n::t('Poll/Feed URL'), $contact['poll']],
		'photo'		=> ['photo', L10n::t('New photo from this URL'), ''],
	]);

	return $o;
}
