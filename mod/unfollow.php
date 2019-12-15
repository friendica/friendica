<?php
/**
 * @file mod/unfollow.php
 */

use Friendica\App;
use Friendica\Core\L10n;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Profile;
use Friendica\Model\User;
use Friendica\Util\Strings;

function unfollow_post(App $a)
{
	$base_return_path = 'contact';

	if (!local_user()) {
		notice(L10n::t('Permission denied.'));
		DI::baseUrl()->redirect('login');
		// NOTREACHED
	}

	$uid = local_user();
	$url = Strings::escapeTags(trim($_REQUEST['url'] ?? ''));

	$condition = ["`uid` = ? AND (`rel` = ? OR `rel` = ?) AND (`nurl` = ? OR `alias` = ? OR `alias` = ?)",
		$uid, Contact::SHARING, Contact::FRIEND, Strings::normaliseLink($url),
		Strings::normaliseLink($url), $url];
	$contact = DBA::selectFirst('contact', [], $condition);

	if (!DBA::isResult($contact)) {
		notice(L10n::t("You aren't following this contact."));
		DI::baseUrl()->redirect($base_return_path);
		// NOTREACHED
	}

	if (!empty($_REQUEST['cancel'])) {
		DI::baseUrl()->redirect($base_return_path . '/' . $contact['id']);
	}

	if (!in_array($contact['network'], Protocol::NATIVE_SUPPORT)) {
		notice(L10n::t('Unfollowing is currently not supported by your network.'));
		DI::baseUrl()->redirect($base_return_path . '/' . $contact['id']);
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
		$return_path = $base_return_path;
	} else {
		DBA::update('contact', ['rel' => Contact::FOLLOWER], ['id' => $contact['id']]);
		$return_path = $base_return_path . '/' . $contact['id'];
	}

	info(L10n::t('Contact unfollowed'));
	DI::baseUrl()->redirect($return_path);
	// NOTREACHED
}

function unfollow_content(App $a)
{
	$base_return_path = 'contact';

	if (!local_user()) {
		notice(L10n::t('Permission denied.'));
		DI::baseUrl()->redirect('login');
		// NOTREACHED
	}

	$uid = local_user();
	$url = Strings::escapeTags(trim($_REQUEST['url']));

	$condition = ["`uid` = ? AND (`rel` = ? OR `rel` = ?) AND (`nurl` = ? OR `alias` = ? OR `alias` = ?)",
		local_user(), Contact::SHARING, Contact::FRIEND, Strings::normaliseLink($url),
		Strings::normaliseLink($url), $url];

	$contact = DBA::selectFirst('contact', ['url', 'network', 'addr', 'name'], $condition);

	if (!DBA::isResult($contact)) {
		notice(L10n::t("You aren't following this contact."));
		DI::baseUrl()->redirect($base_return_path);
		// NOTREACHED
	}

	if (!in_array($contact['network'], Protocol::NATIVE_SUPPORT)) {
		notice(L10n::t('Unfollowing is currently not supported by your network.'));
		DI::baseUrl()->redirect($base_return_path . '/' . $contact['id']);
		// NOTREACHED
	}

	$request = System::baseUrl() . '/unfollow';
	$tpl = Renderer::getMarkupTemplate('auto_request.tpl');

	$self = DBA::selectFirst('contact', ['url'], ['uid' => $uid, 'self' => true]);

	if (!DBA::isResult($self)) {
		notice(L10n::t('Permission denied.'));
		DI::baseUrl()->redirect($base_return_path);
		// NOTREACHED
	}

	// Makes the connection request for friendica contacts easier
	$_SESSION['fastlane'] = $contact['url'];

	$o = Renderer::replaceMacros($tpl, [
		'$header'        => L10n::t('Disconnect/Unfollow'),
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

	$o .= Renderer::replaceMacros(Renderer::getMarkupTemplate('section_title.tpl'), ['$title' => L10n::t('Status Messages and Posts')]);

	// Show last public posts
	$o .= Contact::getPostsFromUrl($contact['url']);

	return $o;
}
