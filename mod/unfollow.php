<?php
/**
 * @file mod/unfollow.php
 */

use Friendica\App;
use Friendica\Core\L10n;
use Friendica\Core\Protocol;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Model\Profile;
use Friendica\Model\User;

function unfollow_post()
{
	$return_url = $_SESSION['return_url'];

	if (!local_user()) {
		notice(L10n::t('Permission denied.'));
		goaway($return_url);
		// NOTREACHED
	}

	if (!empty($_REQUEST['cancel'])) {
		goaway($return_url);
	}

	$uid = local_user();
	$url = notags(trim(defaults($_REQUEST, 'url', '')));

	$condition = ["`uid` = ? AND (`rel` = ? OR `rel` = ?) AND (`nurl` = ? OR `alias` = ? OR `alias` = ?)",
		$uid, Contact::SHARING, Contact::FRIEND, normalise_link($url),
		normalise_link($url), $url];
	$contact = DBA::selectFirst('contact', [], $condition);

	if (!DBA::isResult($contact)) {
		notice(L10n::t("You aren't following this contact."));
		goaway($return_url);
		// NOTREACHED
	}

	if (!in_array($contact['network'], Protocol::NATIVE_SUPPORT)) {
		notice(L10n::t('Unfollowing is currently not supported by your network.'));
		goaway($return_url);
		// NOTREACHED
	}

	$dissolve = ($contact['rel'] == Contact::SHARING);

	$owner = User::getOwnerDataById($uid);
	if ($owner) {
		Contact::terminateFriendship($owner, $contact, $dissolve);
	}

	// Sharing-only contacts get deleted as there no relationship any more
	if ($dissolve) {
		Contact::remove($contact['id']);
		$return_path = 'contacts';
	} else {
		DBA::update('contact', ['rel' => Contact::FOLLOWER], ['id' => $contact['id']]);
		$return_path = 'contacts/' . $contact['id'];
	}

	info(L10n::t('Contact unfollowed'));
	goaway($return_path);
	// NOTREACHED
}

function unfollow_content(App $a)
{
	if (!local_user()) {
		notice(L10n::t('Permission denied.'));
		goaway($_SESSION['return_url']);
		// NOTREACHED
	}

	$uid = local_user();
	$url = notags(trim($_REQUEST['url']));

	$condition = ["`uid` = ? AND (`rel` = ? OR `rel` = ?) AND (`nurl` = ? OR `alias` = ? OR `alias` = ?)",
		local_user(), Contact::SHARING, Contact::FRIEND, normalise_link($url),
		normalise_link($url), $url];

	$contact = DBA::selectFirst('contact', ['url', 'network', 'addr', 'name'], $condition);

	if (!DBA::isResult($contact)) {
		notice(L10n::t("You aren't following this contact."));
		goaway('contacts');
		// NOTREACHED
	}

	if (!in_array($contact['network'], Protocol::NATIVE_SUPPORT)) {
		notice(L10n::t('Unfollowing is currently not supported by your network.'));
		goaway('contacts/' . $contact['id']);
		// NOTREACHED
	}

	$request = System::baseUrl() . '/unfollow';
	$tpl = get_markup_template('auto_request.tpl');

	$self = DBA::selectFirst('contact', ['url'], ['uid' => $uid, 'self' => true]);

	if (!DBA::isResult($self)) {
		notice(L10n::t('Permission denied.'));
		goaway($_SESSION['return_url']);
		// NOTREACHED
	}

	// Makes the connection request for friendica contacts easier
	$_SESSION['fastlane'] = $contact['url'];

	$header = L10n::t('Disconnect/Unfollow');

	$o = replace_macros($tpl, [
		'$header'        => htmlentities($header),
		'$desc'          => '',
		'$pls_answer'    => '',
		'$does_know_you' => '',
		'$add_note'      => '',
		'$page_desc'     => '',
		'$friendica'     => '',
		'$statusnet'     => '',
		'$diaspora'      => '',
		'$diasnote'      => '',
		'$your_address'  => L10n::t('Your Identity Address:'),
		'$invite_desc'   => '',
		'$emailnet'      => '',
		'$submit'        => L10n::t('Submit Request'),
		'$cancel'        => L10n::t('Cancel'),
		'$nickname'      => '',
		'$name'          => $contact['name'],
		'$url'           => $contact['url'],
		'$zrl'           => Contact::magicLink($contact['url']),
		'$url_label'     => L10n::t('Profile URL'),
		'$myaddr'        => $self['url'],
		'$request'       => $request,
		'$keywords'      => '',
		'$keywords_label'=> ''
	]);

	$a->page['aside'] = '';
	Profile::load($a, '', 0, Contact::getDetailsByURL($contact['url']));

	$o .= replace_macros(get_markup_template('section_title.tpl'), ['$title' => L10n::t('Status Messages and Posts')]);

	// Show last public posts
	$o .= Contact::getPostsFromUrl($contact['url']);

	return $o;
}
