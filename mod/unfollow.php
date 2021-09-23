<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

use Friendica\App;
use Friendica\Content\Widget;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\User;
use Friendica\Util\Strings;

function unfollow_post(App $a)
{
	if (!local_user()) {
		notice(DI::l10n()->t('Permission denied.'));
		DI::baseUrl()->redirect('login');
		// NOTREACHED
	}

	$url = Strings::escapeTags(trim($_REQUEST['url'] ?? ''));

	unfollow_process($url);
}

function unfollow_content(App $a)
{
	$base_return_path = 'contact';

	if (!local_user()) {
		notice(DI::l10n()->t('Permission denied.'));
		DI::baseUrl()->redirect('login');
		// NOTREACHED
	}

	$uid = local_user();
	$url = Strings::escapeTags(trim($_REQUEST['url']));

	$condition = ["`uid` = ? AND (`rel` = ? OR `rel` = ?) AND (`nurl` = ? OR `alias` = ? OR `alias` = ?)",
		local_user(), Contact::SHARING, Contact::FRIEND, Strings::normaliseLink($url),
		Strings::normaliseLink($url), $url];

	$contact = DBA::selectFirst('contact', ['url', 'id', 'uid', 'network', 'addr', 'name'], $condition);

	if (!DBA::isResult($contact)) {
		notice(DI::l10n()->t("You aren't following this contact."));
		DI::baseUrl()->redirect($base_return_path);
		// NOTREACHED
	}

	if (!in_array($contact['network'], Protocol::NATIVE_SUPPORT)) {
		notice(DI::l10n()->t('Unfollowing is currently not supported by your network.'));
		DI::baseUrl()->redirect($base_return_path . '/' . $contact['id']);
		// NOTREACHED
	}

	$request = DI::baseUrl() . '/unfollow';
	$tpl = Renderer::getMarkupTemplate('auto_request.tpl');

	$self = DBA::selectFirst('contact', ['url'], ['uid' => $uid, 'self' => true]);

	if (!DBA::isResult($self)) {
		notice(DI::l10n()->t('Permission denied.'));
		DI::baseUrl()->redirect($base_return_path);
		// NOTREACHED
	}

	if (!empty($_REQUEST['auto'])) {
		unfollow_process($contact['url']);
	}

	$o = Renderer::replaceMacros($tpl, [
		'$header'        => DI::l10n()->t('Disconnect/Unfollow'),
		'$page_desc'     => '',
		'$your_address'  => DI::l10n()->t('Your Identity Address:'),
		'$invite_desc'   => '',
		'$submit'        => DI::l10n()->t('Submit Request'),
		'$cancel'        => DI::l10n()->t('Cancel'),
		'$url'           => $contact['url'],
		'$zrl'           => Contact::magicLinkByContact($contact),
		'$url_label'     => DI::l10n()->t('Profile URL'),
		'$myaddr'        => $self['url'],
		'$request'       => $request,
		'$keywords'      => '',
		'$keywords_label'=> ''
	]);

	DI::page()['aside'] = Widget\VCard::getHTML(Contact::getByURL($contact['url'], false));

	$o .= Renderer::replaceMacros(Renderer::getMarkupTemplate('section_title.tpl'), ['$title' => DI::l10n()->t('Status Messages and Posts')]);

	// Show last public posts
	$o .= Contact::getPostsFromUrl($contact['url']);

	return $o;
}

function unfollow_process(string $url)
{
	$base_return_path = 'contact';

	$uid = local_user();

	$condition = ["`uid` = ? AND (`rel` = ? OR `rel` = ?) AND (`nurl` = ? OR `alias` = ? OR `alias` = ?)",
		$uid, Contact::SHARING, Contact::FRIEND, Strings::normaliseLink($url),
		Strings::normaliseLink($url), $url];
	$contact = DBA::selectFirst('contact', [], $condition);

	if (!DBA::isResult($contact)) {
		notice(DI::l10n()->t("You aren't following this contact."));
		DI::baseUrl()->redirect($base_return_path);
		// NOTREACHED
	}

	if (!in_array($contact['network'], Protocol::NATIVE_SUPPORT)) {
		notice(DI::l10n()->t('Unfollowing is currently not supported by your network.'));
		DI::baseUrl()->redirect($base_return_path . '/' . $contact['id']);
		// NOTREACHED
	}

	$dissolve = ($contact['rel'] == Contact::SHARING);

	$owner = User::getOwnerDataById($uid);
	if ($owner) {
		Contact::terminateFriendship($owner, $contact, $dissolve);
	}

	// Sharing-only contacts get deleted as there no relationship anymore
	if ($dissolve) {
		Contact::remove($contact['id']);
		$return_path = $base_return_path;
	} else {
		DBA::update('contact', ['rel' => Contact::FOLLOWER], ['id' => $contact['id']]);
		$return_path = $base_return_path . '/' . $contact['id'];
	}

	DI::baseUrl()->redirect($return_path);
}
