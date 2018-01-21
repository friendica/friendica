<?php
/**
 * @file mod/crepair.php
 */
use Friendica\App;
use Friendica\Core\Config;
use Friendica\Database\DBM;
use Friendica\Model\Contact;
use Friendica\Model\Profile;

require_once 'mod/contacts.php';

function crepair_init(App $a)
{
	if (!local_user()) {
		return;
	}

	$contact = null;
	if (($a->argc == 2) && intval($a->argv[1])) {
		$contact = dba::selectFirst('contact', [], ['uid' => local_user(), 'id' => $a->argv[1]]);
	}

	if (!x($a->page, 'aside')) {
		$a->page['aside'] = '';
	}

	if (DBM::isResult($contact)) {
		$a->data['contact'] = $contact;
		Profile::load($a, "", 0, Contact::getDetailsByURL($contact["url"]));
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
		$contact = dba::selectFirst('contact', [], ['id' => $cid, 'uid' => local_user()]);
	}

	if (!DBM::isResult($contact)) {
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
	$nurl        = normalise_link($url);

	$r = q("UPDATE `contact` SET `name` = '%s', `nick` = '%s', `url` = '%s', `nurl` = '%s', `request` = '%s', `confirm` = '%s', `notify` = '%s', `poll` = '%s', `attag` = '%s' , `remote_self` = %d
		WHERE `id` = %d AND `uid` = %d",
		dbesc($name),
		dbesc($nick),
		dbesc($url),
		dbesc($nurl),
		dbesc($request),
		dbesc($confirm),
		dbesc($notify),
		dbesc($poll),
		dbesc($attag),
		intval($remote_self),
		intval($contact['id']),
		local_user()
	);

	if ($photo) {
		logger('mod-crepair: updating photo from ' . $photo);

		Contact::updateAvatar($photo, local_user(), $contact['id']);
	}

	if ($r) {
		info(t('Contact settings applied.') . EOL);
	} else {
		notice(t('Contact update failed.') . EOL);
	}

	return;
}

function crepair_content(App $a)
{
	if (!local_user()) {
		notice(t('Permission denied.') . EOL);
		return;
	}

	$cid = (($a->argc > 1) ? intval($a->argv[1]) : 0);

		$contact = null;
	if ($cid) {
		$contact = dba::selectFirst('contact', [], ['id' => $cid, 'uid' => local_user()]);
	}

	if (!DBM::isResult($contact)) {
		notice(t('Contact not found.') . EOL);
		return;
	}

	$warning = t('<strong>WARNING: This is highly advanced</strong> and if you enter incorrect information your communications with this contact may stop working.');
	$info = t('Please use your browser \'Back\' button <strong>now</strong> if you are uncertain what to do on this page.');

	$returnaddr = "contacts/$cid";

	$allow_remote_self = Config::get('system', 'allow_users_remote_self');

	// Disable remote self for everything except feeds.
	// There is an issue when you repeat an item from maybe twitter and you got comments from friendica and twitter
	// Problem is, you couldn't reply to both networks.
	if (!in_array($contact['network'], [NETWORK_FEED, NETWORK_DFRN, NETWORK_DIASPORA])) {
		$allow_remote_self = false;
	}

	if ($contact['network'] == NETWORK_FEED) {
		$remote_self_options = ['0' => t('No mirroring'), '1' => t('Mirror as forwarded posting'), '2' => t('Mirror as my own posting')];
	} else {
		$remote_self_options = ['0' => t('No mirroring'), '2' => t('Mirror as my own posting')];
	}

	$update_profile = in_array($contact['network'], [NETWORK_DFRN, NETWORK_DIASPORA, NETWORK_OSTATUS]);

	$tab_str = contacts_tab($a, $contact['id'], 5);

	$tpl = get_markup_template('crepair.tpl');
	$o = replace_macros($tpl, [
		'$tab_str'        => $tab_str,
		'$warning'        => $warning,
		'$info'           => $info,
		'$returnaddr'     => $returnaddr,
		'$return'         => t('Return to contact editor'),
		'$update_profile' => $update_profile,
		'$udprofilenow'   => t('Refetch contact data'),
		'$contact_id'     => $contact['id'],
		'$lbl_submit'     => t('Submit'),
		'$label_remote_self' => t('Remote Self'),
		'$allow_remote_self' => $allow_remote_self,
		'$remote_self' => ['remote_self',
			t('Mirror postings from this contact'),
			$contact['remote_self'],
			t('Mark this contact as remote_self, this will cause friendica to repost new entries from this contact.'),
			$remote_self_options
		],

		'$name'		=> ['name', t('Name') , htmlentities($contact['name'])],
		'$nick'		=> ['nick', t('Account Nickname'), htmlentities($contact['nick'])],
		'$attag'	=> ['attag', t('@Tagname - overrides Name/Nickname'), $contact['attag']],
		'$url'		=> ['url', t('Account URL'), $contact['url']],
		'$request'	=> ['request', t('Friend Request URL'), $contact['request']],
		'confirm'	=> ['confirm', t('Friend Confirm URL'), $contact['confirm']],
		'notify'	=> ['notify', t('Notification Endpoint URL'), $contact['notify']],
		'poll'		=> ['poll', t('Poll/Feed URL'), $contact['poll']],
		'photo'		=> ['photo', t('New photo from this URL'), ''],
	]);

	return $o;
}
