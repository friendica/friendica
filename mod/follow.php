<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Profile;
use Friendica\Model\Item;
use Friendica\Network\Probe;
use Friendica\Database\DBA;
use Friendica\Model\Post;
use Friendica\Model\User;
use Friendica\Util\Strings;

function follow_post(App $a)
{
	if (!local_user()) {
		throw new \Friendica\Network\HTTPException\ForbiddenException(DI::l10n()->t('Access denied.'));
	}

	if (isset($_REQUEST['cancel'])) {
		DI::baseUrl()->redirect('contact');
	}

	$url = Probe::cleanURI($_REQUEST['url']);

	follow_process($a, $url);
}

function follow_content(App $a)
{
	$return_path = 'contact';

	if (!local_user()) {
		notice(DI::l10n()->t('Permission denied.'));
		DI::baseUrl()->redirect($return_path);
		// NOTREACHED
	}

	$uid = local_user();

	$url = Probe::cleanURI(trim($_REQUEST['url'] ?? ''));

	// Issue 6874: Allow remote following from Peertube
	if (strpos($url, 'acct:') === 0) {
		$url = str_replace('acct:', '', $url);
	}

	if (!$url) {
		DI::baseUrl()->redirect($return_path);
	}

	$submit = DI::l10n()->t('Submit Request');

	// Don't try to add a pending contact
	$user_contact = DBA::selectFirst('contact', ['pending'], ["`uid` = ? AND ((`rel` != ?) OR (`network` = ?)) AND
		(`nurl` = ? OR `alias` = ? OR `alias` = ?) AND `network` != ?",
		$uid, Contact::FOLLOWER, Protocol::DFRN, Strings::normaliseLink($url),
		Strings::normaliseLink($url), $url, Protocol::STATUSNET]);

	if (DBA::isResult($user_contact)) {
		if ($user_contact['pending']) {
			notice(DI::l10n()->t('You already added this contact.'));
			$submit = '';
		}
	}

	$contact = Contact::getByURL($url, true);

	// Possibly it is a mail contact
	if (empty($contact)) {
		$contact = Probe::uri($url, Protocol::MAIL, $uid);
	}

	if (empty($contact) || ($contact['network'] == Protocol::PHANTOM)) {
		// Possibly it is a remote item and not an account
		follow_remote_item($url);

		notice(DI::l10n()->t("The network type couldn't be detected. Contact can't be added."));
		$submit = '';
		$contact = ['url' => $url, 'network' => Protocol::PHANTOM, 'name' => $url, 'keywords' => ''];
	}

	$protocol = Contact::getProtocol($contact['url'], $contact['network']);

	if (($protocol == Protocol::DIASPORA) && !DI::config()->get('system', 'diaspora_enabled')) {
		notice(DI::l10n()->t("Diaspora support isn't enabled. Contact can't be added."));
		$submit = '';
	}

	if (($protocol == Protocol::OSTATUS) && DI::config()->get('system', 'ostatus_disabled')) {
		notice(DI::l10n()->t("OStatus support is disabled. Contact can't be added."));
		$submit = '';
	}

	if ($protocol == Protocol::MAIL) {
		$contact['url'] = $contact['addr'];
	}

	if (!empty($_REQUEST['auto'])) {
		follow_process($a, $contact['url']);
	}

	$request = DI::baseUrl() . '/follow';
	$tpl = Renderer::getMarkupTemplate('auto_request.tpl');

	$owner = User::getOwnerDataById($uid);
	if (empty($owner)) {
		notice(DI::l10n()->t('Permission denied.'));
		DI::baseUrl()->redirect($return_path);
		// NOTREACHED
	}

	$myaddr = $owner['url'];

	$o = Renderer::replaceMacros($tpl, [
		'$header'        => DI::l10n()->t('Connect/Follow'),
		'$pls_answer'    => DI::l10n()->t('Please answer the following:'),
		'$your_address'  => DI::l10n()->t('Your Identity Address:'),
		'$url_label'     => DI::l10n()->t('Profile URL'),
		'$keywords_label'=> DI::l10n()->t('Tags:'),
		'$submit'        => $submit,
		'$cancel'        => DI::l10n()->t('Cancel'),

		'$request'       => $request,
		'$name'          => $contact['name'],
		'$url'           => $contact['url'],
		'$zrl'           => Profile::zrl($contact['url']),
		'$myaddr'        => $myaddr,
		'$keywords'      => $contact['keywords'],

		'$does_know_you' => ['knowyou', DI::l10n()->t('%s knows you', $contact['name'])],
		'$addnote_field' => ['dfrn-request-message', DI::l10n()->t('Add a personal note:')],
	]);

	DI::page()['aside'] = '';

	if (!in_array($protocol, [Protocol::PHANTOM, Protocol::MAIL])) {
		DI::page()['aside'] = Widget\VCard::getHTML($contact);

		$o .= Renderer::replaceMacros(Renderer::getMarkupTemplate('section_title.tpl'),
			['$title' => DI::l10n()->t('Status Messages and Posts')]
		);

		// Show last public posts
		$o .= Contact::getPostsFromUrl($contact['url']);
	}

	return $o;
}

function follow_process(App $a, string $url)
{
	$return_path = 'follow?url=' . urlencode($url);

	$result = Contact::createFromProbeForUser($a->getLoggedInUserId(), $url);

	if ($result['success'] == false) {
		// Possibly it is a remote item and not an account
		follow_remote_item($url);

		if ($result['message']) {
			notice($result['message']);
		}
		DI::baseUrl()->redirect($return_path);
	} elseif ($result['cid']) {
		DI::baseUrl()->redirect('contact/' . $result['cid']);
	}

	notice(DI::l10n()->t('The contact could not be added.'));

	DI::baseUrl()->redirect($return_path);
}

function follow_remote_item($url)
{
	$item_id = Item::fetchByLink($url, local_user());
	if (!$item_id) {
		// If the user-specific search failed, we search and probe a public post
		$item_id = Item::fetchByLink($url);
	}

	if (!empty($item_id)) {
		$item = Post::selectFirst(['guid'], ['id' => $item_id]);
		if (DBA::isResult($item)) {
			DI::baseUrl()->redirect('display/' . $item['guid']);
		}
	}
}
