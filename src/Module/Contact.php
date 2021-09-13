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

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Content\ContactSelector;
use Friendica\Content\Nav;
use Friendica\Content\Pager;
use Friendica\Content\Text\BBCode;
use Friendica\Content\Widget;
use Friendica\Core\Hook;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Core\Theme;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model;
use Friendica\Model\User;
use Friendica\Module\Security\Login;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Strings;

/**
 *  Manages and show Contacts and their content
 */
class Contact extends BaseModule
{
	const TAB_CONVERSATIONS = 1;
	const TAB_POSTS = 2;
	const TAB_PROFILE = 3;
	const TAB_CONTACTS = 4;
	const TAB_ADVANCED = 5;

	private static function batchActions()
	{
		if (empty($_POST['contact_batch']) || !is_array($_POST['contact_batch'])) {
			return;
		}

		$redirectUrl = $_POST['redirect_url'] ?? 'contact';

		self::checkFormSecurityTokenRedirectOnError($redirectUrl, 'contact_batch_actions');

		$orig_records = Model\Contact::selectToArray(['id', 'uid'], ['id' => $_POST['contact_batch'], 'uid' => [0, local_user()], 'self' => false, 'deleted' => false]);

		$count_actions = 0;
		foreach ($orig_records as $orig_record) {
			$cdata = Model\Contact::getPublicAndUserContactID($orig_record['id'], local_user());
			if (empty($cdata)) {
				continue;
			}

			if (!empty($_POST['contacts_batch_update']) && $cdata['user']) {
				self::updateContactFromPoll($cdata['user']);
				$count_actions++;
			}

			if (!empty($_POST['contacts_batch_block'])) {
				self::toggleBlockContact($cdata['public']);
				$count_actions++;
			}

			if (!empty($_POST['contacts_batch_ignore'])) {
				self::toggleIgnoreContact($cdata['public']);
				$count_actions++;
			}

			if (!empty($_POST['contacts_batch_drop']) && $cdata['user']
				&& self::dropContact($cdata['user'], local_user())
			) {
				$count_actions++;
			}
		}
		if ($count_actions > 0) {
			info(DI::l10n()->tt('%d contact edited.', '%d contacts edited.', $count_actions));
		}

		DI::baseUrl()->redirect($redirectUrl);
	}

	public static function post(array $parameters = [])
	{
		if (!local_user()) {
			return;
		}

		// @TODO: Replace with parameter from router
		if (DI::args()->getArgv()[1] === 'batch') {
			self::batchActions();
			return;
		}

		// @TODO: Replace with parameter from router
		$contact_id = intval(DI::args()->getArgv()[1]);
		if (!$contact_id) {
			return;
		}

		if (!DBA::exists('contact', ['id' => $contact_id, 'uid' => local_user(), 'deleted' => false])) {
			notice(DI::l10n()->t('Could not access contact record.'));
			DI::baseUrl()->redirect('contact');
			return; // NOTREACHED
		}

		Hook::callAll('contact_edit_post', $_POST);

		$hidden = !empty($_POST['hidden']);

		$notify = !empty($_POST['notify']);

		$fetch_further_information = intval($_POST['fetch_further_information'] ?? 0);

		$remote_self = $_POST['remote_self'] ?? false;

		$ffi_keyword_denylist = Strings::escapeHtml(trim($_POST['ffi_keyword_denylist'] ?? ''));

		$priority = intval($_POST['poll'] ?? 0);
		if ($priority > 5 || $priority < 0) {
			$priority = 0;
		}

		$info = Strings::escapeHtml(trim($_POST['info'] ?? ''));

		$r = DBA::update('contact', [
			'priority'   => $priority,
			'info'       => $info,
			'hidden'     => $hidden,
			'notify_new_posts' => $notify,
			'fetch_further_information' => $fetch_further_information,
			'remote_self' => $remote_self,
			'ffi_keyword_denylist'     => $ffi_keyword_denylist],
			['id' => $contact_id, 'uid' => local_user()]
		);

		if (!DBA::isResult($r)) {
			notice(DI::l10n()->t('Failed to update contact record.'));
		}
		return;
	}

	/* contact actions */

	/**
	 * @param int $contact_id Id of contact with uid != 0
	 * @throws NotFoundException
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function updateContactFromPoll(int $contact_id)
	{
		$contact = DBA::selectFirst('contact', ['uid', 'url', 'network'], ['id' => $contact_id, 'uid' => local_user(), 'deleted' => false]);
		if (!DBA::isResult($contact)) {
			return;
		}

		if ($contact['network'] == Protocol::OSTATUS) {
			$result = Model\Contact::createFromProbeForUser($contact['uid'], $contact['url'], $contact['network']);

			if ($result['success']) {
				DBA::update('contact', ['subhub' => 1], ['id' => $contact_id]);
			}

			// pull feed and consume it, which should subscribe to the hub.
			Worker::add(PRIORITY_HIGH, 'OnePoll', $contact_id, 'force');
		} else {
			Worker::add(PRIORITY_HIGH, 'UpdateContact', $contact_id);
		}
	}

	/**
	 * @param int $contact_id Id of the contact with uid != 0
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function updateContactFromProbe(int $contact_id)
	{
		$contact = DBA::selectFirst('contact', ['url'], ['id' => $contact_id, 'uid' => local_user(), 'deleted' => false]);
		if (!DBA::isResult($contact)) {
			return;
		}

		// Update the entry in the contact table
		Model\Contact::updateFromProbe($contact_id);
	}

	/**
	 * Toggles the blocked status of a contact identified by id.
	 *
	 * @param int $contact_id Id of the contact with uid = 0
	 * @throws \Exception
	 */
	private static function toggleBlockContact(int $contact_id)
	{
		$blocked = !Model\Contact\User::isBlocked($contact_id, local_user());
		Model\Contact\User::setBlocked($contact_id, local_user(), $blocked);
	}

	/**
	 * Toggles the ignored status of a contact identified by id.
	 *
	 * @param int $contact_id Id of the contact with uid = 0
	 * @throws \Exception
	 */
	private static function toggleIgnoreContact(int $contact_id)
	{
		$ignored = !Model\Contact\User::isIgnored($contact_id, local_user());
		Model\Contact\User::setIgnored($contact_id, local_user(), $ignored);
	}

	/**
	 * @param int $contact_id Id for contact with uid != 0
	 * @param int $uid        Id for user we want to drop the contact for
	 * @return bool
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function dropContact(int $contact_id, int $uid): bool
	{
		$contact = Model\Contact::getContactForUser($contact_id, $uid);
		if (!DBA::isResult($contact)) {
			return false;
		}

		$owner = Model\User::getOwnerDataById($uid);
		if (!DBA::isResult($owner)) {
			return false;
		}

		Model\Contact::terminateFriendship($owner, $contact, true);
		Model\Contact::remove($contact['id']);

		return true;
	}

	public static function content(array $parameters = [], $update = 0)
	{
		if (!local_user()) {
			return Login::form($_SERVER['REQUEST_URI']);
		}

		$a = DI::app();

		$search = Strings::escapeTags(trim($_GET['search'] ?? ''));
		$nets   = Strings::escapeTags(trim($_GET['nets']   ?? ''));
		$rel    = Strings::escapeTags(trim($_GET['rel']    ?? ''));
		$group  = Strings::escapeTags(trim($_GET['group']  ?? ''));

		$accounttype = $_GET['accounttype'] ?? '';
		$accounttypeid = User::getAccountTypeByString($accounttype);

		$page = DI::page();

		$page->registerFooterScript(Theme::getPathForFile('asset/typeahead.js/dist/typeahead.bundle.js'));
		$page->registerFooterScript(Theme::getPathForFile('js/friendica-tagsinput/friendica-tagsinput.js'));
		$page->registerStylesheet(Theme::getPathForFile('js/friendica-tagsinput/friendica-tagsinput.css'));
		$page->registerStylesheet(Theme::getPathForFile('js/friendica-tagsinput/friendica-tagsinput-typeahead.css'));

		$contact = null;
		// @TODO: Replace with parameter from router
		if (DI::args()->getArgc() == 2 && intval(DI::args()->getArgv()[1])
			|| DI::args()->getArgc() == 3 && intval(DI::args()->getArgv()[1]) && in_array(DI::args()->getArgv()[2], ['posts', 'conversations'])
		) {
			$contact_id = intval(DI::args()->getArgv()[1]);

			// Ensure to use the user contact when the public contact was provided
			$data = Model\Contact::getPublicAndUserContactID($contact_id, local_user());
			if (!empty($data['user']) && ($contact_id == $data['public'])) {
				$contact_id = $data['user'];
			}

			$contact = DBA::selectFirst('contact', [], ['id' => $contact_id, 'uid' => [0, local_user()], 'deleted' => false]);

			// Don't display contacts that are about to be deleted
			if ($contact['network'] == Protocol::PHANTOM) {
				$contact = false;
			}
		}

		if (DBA::isResult($contact)) {
			if ($contact['self']) {
				// @TODO: Replace with parameter from router
				if ((DI::args()->getArgc() == 3) && intval(DI::args()->getArgv()[1]) && in_array(DI::args()->getArgv()[2], ['posts', 'conversations'])) {
					DI::baseUrl()->redirect('profile/' . $contact['nick']);
				} else {
					DI::baseUrl()->redirect('profile/' . $contact['nick'] . '/profile');
				}
			}

			$vcard_widget = Widget\VCard::getHTML($contact);

			$findpeople_widget = '';
			$follow_widget = '';
			$account_widget = '';
			$networks_widget = '';
			$rel_widget = '';

			if ($contact['uid'] != 0) {
				$groups_widget = Model\Group::sidebarWidget('contact', 'group', 'full', 'everyone', $contact_id);
			} else {
				$groups_widget = '';
			}
		} else {
			$vcard_widget = '';
			$findpeople_widget = Widget::findPeople();
			if (isset($_GET['add'])) {
				$follow_widget = Widget::follow($_GET['add']);
			} else {
				$follow_widget = Widget::follow();
			}

			$account_widget = Widget::accounttypes($_SERVER['REQUEST_URI'], $accounttype);
			$networks_widget = Widget::networks($_SERVER['REQUEST_URI'], $nets);
			$rel_widget = Widget::contactRels($_SERVER['REQUEST_URI'], $rel);
			$groups_widget = Widget::groups($_SERVER['REQUEST_URI'], $group);
		}

		DI::page()['aside'] .= $vcard_widget . $findpeople_widget . $follow_widget . $account_widget . $groups_widget . $networks_widget . $rel_widget;

		$tpl = Renderer::getMarkupTemplate('contacts-head.tpl');
		DI::page()['htmlhead'] .= Renderer::replaceMacros($tpl, [
			'$baseurl' => DI::baseUrl()->get(true),
		]);

		$o = '';
		Nav::setSelected('contact');

		if (!local_user()) {
			notice(DI::l10n()->t('Permission denied.'));
			return Login::form();
		}

		if (DI::args()->getArgc() == 3) {
			$contact_id = intval(DI::args()->getArgv()[1]);
			if (!$contact_id) {
				throw new BadRequestException();
			}

			// @TODO: Replace with parameter from router
			$cmd = DI::args()->getArgv()[2];

			$orig_record = DBA::selectFirst('contact', [], ['id' => $contact_id, 'uid' => [0, local_user()], 'self' => false, 'deleted' => false]);
			if (!DBA::isResult($orig_record)) {
				throw new NotFoundException(DI::l10n()->t('Contact not found'));
			}

			if ($cmd === 'posts') {
				return self::getPostsHTML($a, $contact_id);
			}

			if ($cmd === 'conversations') {
				return self::getConversationsHMTL($a, $contact_id, $update);
			}

			self::checkFormSecurityTokenRedirectOnError('contact/' . $contact_id, 'contact_action', 't');

			$cdata = Model\Contact::getPublicAndUserContactID($orig_record['id'], local_user());
			if (empty($cdata)) {
				throw new NotFoundException(DI::l10n()->t('Contact not found'));
			}

			if ($cmd === 'update' && $cdata['user']) {
				self::updateContactFromPoll($cdata['user']);
				DI::baseUrl()->redirect('contact/' . $cdata['public']);
				// NOTREACHED
			}

			if ($cmd === 'updateprofile' && $cdata['user']) {
				self::updateContactFromProbe($cdata['user']);
				DI::baseUrl()->redirect('contact/' . $cdata['public']);
				// NOTREACHED
			}

			if ($cmd === 'block') {
				if (public_contact() === $cdata['public']) {
					throw new BadRequestException(DI::l10n()->t('You can\'t block yourself'));
				}

				self::toggleBlockContact($cdata['public']);

				$blocked = Model\Contact\User::isBlocked($contact_id, local_user());
				info(($blocked ? DI::l10n()->t('Contact has been blocked') : DI::l10n()->t('Contact has been unblocked')));

				DI::baseUrl()->redirect('contact/' . $cdata['public']);
				// NOTREACHED
			}

			if ($cmd === 'ignore') {
				if (public_contact() === $cdata['public']) {
					throw new BadRequestException(DI::l10n()->t('You can\'t ignore yourself'));
				}

				self::toggleIgnoreContact($cdata['public']);

				$ignored = Model\Contact\User::isIgnored($cdata['public'], local_user());
				info(($ignored ? DI::l10n()->t('Contact has been ignored') : DI::l10n()->t('Contact has been unignored')));

				DI::baseUrl()->redirect('contact/' . $cdata['public']);
				// NOTREACHED
			}

			if ($cmd === 'drop' && $cdata['user']) {
				// Check if we should do HTML-based delete confirmation
				if (!empty($_REQUEST['confirm'])) {
					DI::page()['aside'] = '';

					return Renderer::replaceMacros(Renderer::getMarkupTemplate('contact_drop_confirm.tpl'), [
						'$header' => DI::l10n()->t('Drop contact'),
						'$contact' => self::getContactTemplateVars($orig_record),
						'$method' => 'get',
						'$message' => DI::l10n()->t('Do you really want to delete this contact?'),
						'$confirm' => DI::l10n()->t('Yes'),
						'$confirm_url' => DI::args()->getCommand(),
						'$confirm_name' => 'confirmed',
						'$cancel' => DI::l10n()->t('Cancel'),
					]);
				}
				// Now check how the user responded to the confirmation query
				if (!empty($_REQUEST['canceled'])) {
					DI::baseUrl()->redirect('contact');
				}

				if (self::dropContact($cdata['user'], local_user())) {
					info(DI::l10n()->t('Contact has been removed.'));
				}

				DI::baseUrl()->redirect('contact');
				// NOTREACHED
			}
		}

		$_SESSION['return_path'] = DI::args()->getQueryString();

		if (!empty($contact)) {
			DI::page()['htmlhead'] .= Renderer::replaceMacros(Renderer::getMarkupTemplate('contact_head.tpl'), [
				'$baseurl' => DI::baseUrl()->get(true),
			]);

			$contact['blocked']  = Model\Contact\User::isBlocked($contact['id'], local_user());
			$contact['readonly'] = Model\Contact\User::isIgnored($contact['id'], local_user());

			$relation_text = '';
			switch ($contact['rel']) {
				case Model\Contact::FRIEND:
					$relation_text = DI::l10n()->t('You are mutual friends with %s');
					break;

				case Model\Contact::FOLLOWER;
					$relation_text = DI::l10n()->t('You are sharing with %s');
					break;

				case Model\Contact::SHARING;
					$relation_text = DI::l10n()->t('%s is sharing with you');
					break;

				default:
					break;
			}

			if ($contact['uid'] == 0) {
				$relation_text = '';
			}

			if (!in_array($contact['network'], array_merge(Protocol::FEDERATED, [Protocol::TWITTER]))) {
				$relation_text = '';
			}

			$relation_text = sprintf($relation_text, $contact['name']);

			$url = Model\Contact::magicLinkByContact($contact);
			if (strpos($url, 'redir/') === 0) {
				$sparkle = ' class="sparkle" ';
			} else {
				$sparkle = '';
			}

			$insecure = DI::l10n()->t('Private communications are not available for this contact.');

			$last_update = (($contact['last-update'] <= DBA::NULL_DATETIME) ? DI::l10n()->t('Never') : DateTimeFormat::local($contact['last-update'], 'D, j M Y, g:i A'));

			if ($contact['last-update'] > DBA::NULL_DATETIME) {
				$last_update .= ' ' . ($contact['failed'] ? DI::l10n()->t('(Update was not successful)') : DI::l10n()->t('(Update was successful)'));
			}
			$lblsuggest = (($contact['network'] === Protocol::DFRN) ? DI::l10n()->t('Suggest friends') : '');

			$poll_enabled = in_array($contact['network'], [Protocol::DFRN, Protocol::OSTATUS, Protocol::FEED, Protocol::MAIL]);

			$nettype = DI::l10n()->t('Network type: %s', ContactSelector::networkToName($contact['network'], $contact['url'], $contact['protocol'], $contact['gsid']));

			// tabs
			$tab_str = self::getTabsHTML($contact, self::TAB_PROFILE);

			$lost_contact = (($contact['archive'] && $contact['term-date'] > DBA::NULL_DATETIME && $contact['term-date'] < DateTimeFormat::utcNow()) ? DI::l10n()->t('Communications lost with this contact!') : '');

			$fetch_further_information = null;
			if ($contact['network'] == Protocol::FEED) {
				$fetch_further_information = [
					'fetch_further_information',
					DI::l10n()->t('Fetch further information for feeds'),
					$contact['fetch_further_information'],
					DI::l10n()->t('Fetch information like preview pictures, title and teaser from the feed item. You can activate this if the feed doesn\'t contain much text. Keywords are taken from the meta header in the feed item and are posted as hash tags.'),
					[
						'0' => DI::l10n()->t('Disabled'),
						'1' => DI::l10n()->t('Fetch information'),
						'3' => DI::l10n()->t('Fetch keywords'),
						'2' => DI::l10n()->t('Fetch information and keywords')
					]
				];
			}

			// Disable remote self for everything except feeds.
			// There is an issue when you repeat an item from maybe twitter and you got comments from friendica and twitter
			// Problem is, you couldn't reply to both networks.
			$allow_remote_self = in_array($contact['network'], [Protocol::ACTIVITYPUB, Protocol::FEED, Protocol::DFRN, Protocol::DIASPORA, Protocol::TWITTER])
				&& DI::config()->get('system', 'allow_users_remote_self');

			if ($contact['network'] == Protocol::FEED) {
				$remote_self_options = [Model\Contact::MIRROR_DEACTIVATED => DI::l10n()->t('No mirroring'),
					Model\Contact::MIRROR_FORWARDED => DI::l10n()->t('Mirror as forwarded posting'),
					Model\Contact::MIRROR_OWN_POST => DI::l10n()->t('Mirror as my own posting')];
			} elseif (in_array($contact['network'], [Protocol::ACTIVITYPUB])) {
				$remote_self_options = [Model\Contact::MIRROR_DEACTIVATED => DI::l10n()->t('No mirroring'),
				Model\Contact::MIRROR_NATIVE_RESHARE => DI::l10n()->t('Native reshare')];
			} elseif (in_array($contact['network'], [Protocol::DFRN])) {
				$remote_self_options = [Model\Contact::MIRROR_DEACTIVATED => DI::l10n()->t('No mirroring'),
				Model\Contact::MIRROR_OWN_POST => DI::l10n()->t('Mirror as my own posting'),
				Model\Contact::MIRROR_NATIVE_RESHARE => DI::l10n()->t('Native reshare')];
			} else {
				$remote_self_options = [Model\Contact::MIRROR_DEACTIVATED => DI::l10n()->t('No mirroring'),
					Model\Contact::MIRROR_OWN_POST => DI::l10n()->t('Mirror as my own posting')];
			}

			$poll_interval = null;
			if ((($contact['network'] == Protocol::FEED) && !DI::config()->get('system', 'adjust_poll_frequency')) || ($contact['network'] == Protocol::MAIL)) {
				$poll_interval = ContactSelector::pollInterval($contact['priority'], !$poll_enabled);
			}

			// Load contactact related actions like hide, suggest, delete and others
			$contact_actions = self::getContactActions($contact);

			if ($contact['uid'] != 0) {
				$lbl_info1 = DI::l10n()->t('Contact Information / Notes');
				$contact_settings_label = DI::l10n()->t('Contact Settings');
			} else {
				$lbl_info1 = null;
				$contact_settings_label = null;
			}

			$tpl = Renderer::getMarkupTemplate('contact_edit.tpl');
			$o .= Renderer::replaceMacros($tpl, [
				'$header'         => DI::l10n()->t('Contact'),
				'$tab_str'        => $tab_str,
				'$submit'         => DI::l10n()->t('Submit'),
				'$lbl_info1'      => $lbl_info1,
				'$lbl_info2'      => DI::l10n()->t('Their personal note'),
				'$reason'         => trim(Strings::escapeTags($contact['reason'])),
				'$infedit'        => DI::l10n()->t('Edit contact notes'),
				'$common_link'    => 'contact/' . $contact['id'] . '/contacts/common',
				'$relation_text'  => $relation_text,
				'$visit'          => DI::l10n()->t('Visit %s\'s profile [%s]', $contact['name'], $contact['url']),
				'$blockunblock'   => DI::l10n()->t('Block/Unblock contact'),
				'$ignorecont'     => DI::l10n()->t('Ignore contact'),
				'$lblrecent'      => DI::l10n()->t('View conversations'),
				'$lblsuggest'     => $lblsuggest,
				'$nettype'        => $nettype,
				'$poll_interval'  => $poll_interval,
				'$poll_enabled'   => $poll_enabled,
				'$lastupdtext'    => DI::l10n()->t('Last update:'),
				'$lost_contact'   => $lost_contact,
				'$updpub'         => DI::l10n()->t('Update public posts'),
				'$last_update'    => $last_update,
				'$udnow'          => DI::l10n()->t('Update now'),
				'$contact_id'     => $contact['id'],
				'$block_text'     => ($contact['blocked'] ? DI::l10n()->t('Unblock') : DI::l10n()->t('Block')),
				'$ignore_text'    => ($contact['readonly'] ? DI::l10n()->t('Unignore') : DI::l10n()->t('Ignore')),
				'$insecure'       => (in_array($contact['network'], [Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::MAIL, Protocol::DIASPORA]) ? '' : $insecure),
				'$info'           => $contact['info'],
				'$cinfo'          => ['info', '', $contact['info'], ''],
				'$blocked'        => ($contact['blocked'] ? DI::l10n()->t('Currently blocked') : ''),
				'$ignored'        => ($contact['readonly'] ? DI::l10n()->t('Currently ignored') : ''),
				'$archived'       => ($contact['archive'] ? DI::l10n()->t('Currently archived') : ''),
				'$pending'        => ($contact['pending'] ? DI::l10n()->t('Awaiting connection acknowledge') : ''),
				'$hidden'         => ['hidden', DI::l10n()->t('Hide this contact from others'), ($contact['hidden'] == 1), DI::l10n()->t('Replies/likes to your public posts <strong>may</strong> still be visible')],
				'$notify'         => ['notify', DI::l10n()->t('Notification for new posts'), ($contact['notify_new_posts'] == 1), DI::l10n()->t('Send a notification of every new post of this contact')],
				'$fetch_further_information' => $fetch_further_information,
				'$ffi_keyword_denylist' => ['ffi_keyword_denylist', DI::l10n()->t('Keyword Deny List'), $contact['ffi_keyword_denylist'], DI::l10n()->t('Comma separated list of keywords that should not be converted to hashtags, when "Fetch information and keywords" is selected')],
				'$photo'          => Model\Contact::getPhoto($contact),
				'$name'           => $contact['name'],
				'$sparkle'        => $sparkle,
				'$url'            => $url,
				'$profileurllabel'=> DI::l10n()->t('Profile URL'),
				'$profileurl'     => $contact['url'],
				'$account_type'   => Model\Contact::getAccountType($contact),
				'$location'       => BBCode::convertForUriId($contact['uri-id'] ?? 0, $contact['location']),
				'$location_label' => DI::l10n()->t('Location:'),
				'$xmpp'           => BBCode::convertForUriId($contact['uri-id'] ?? 0, $contact['xmpp']),
				'$xmpp_label'     => DI::l10n()->t('XMPP:'),
				'$matrix'         => BBCode::convertForUriId($contact['uri-id'] ?? 0, $contact['matrix']),
				'$matrix_label'   => DI::l10n()->t('Matrix:'),
				'$about'          => BBCode::convertForUriId($contact['uri-id'] ?? 0, $contact['about'], BBCode::EXTERNAL),
				'$about_label'    => DI::l10n()->t('About:'),
				'$keywords'       => $contact['keywords'],
				'$keywords_label' => DI::l10n()->t('Tags:'),
				'$contact_action_button' => DI::l10n()->t('Actions'),
				'$contact_actions'=> $contact_actions,
				'$contact_status' => DI::l10n()->t('Status'),
				'$contact_settings_label' => $contact_settings_label,
				'$contact_profile_label' => DI::l10n()->t('Profile'),
				'$allow_remote_self' => $allow_remote_self,
				'$remote_self'       => ['remote_self',
					DI::l10n()->t('Mirror postings from this contact'),
					$contact['remote_self'],
					DI::l10n()->t('Mark this contact as remote_self, this will cause friendica to repost new entries from this contact.'),
					$remote_self_options
				],
			]);

			$arr = ['contact' => $contact, 'output' => $o];

			Hook::callAll('contact_edit', $arr);

			return $arr['output'];
		}

		$sql_values = [local_user()];

		// @TODO: Replace with parameter from router
		$type = DI::args()->getArgv()[1] ?? '';

		switch ($type) {
			case 'blocked':
				$sql_extra = " AND EXISTS(SELECT `id` from `user-contact` WHERE `contact`.`id` = `user-contact`.`cid` and `user-contact`.`uid` = ? and `user-contact`.`blocked`)";
				// This makes the query look for contact.uid = 0
				array_unshift($sql_values, 0);
				break;
			case 'hidden':
				$sql_extra = " AND `hidden` AND NOT `blocked` AND NOT `pending`";
				break;
			case 'ignored':
				$sql_extra = " AND EXISTS(SELECT `id` from `user-contact` WHERE `contact`.`id` = `user-contact`.`cid` and `user-contact`.`uid` = ? and `user-contact`.`ignored`)";
				// This makes the query look for contact.uid = 0
				array_unshift($sql_values, 0);
				break;
			case 'archived':
				$sql_extra = " AND `archive` AND NOT `blocked` AND NOT `pending`";
				break;
			case 'pending':
				$sql_extra = " AND `pending` AND NOT `archive` AND NOT `failed` AND ((`rel` = ?)
					OR EXISTS (SELECT `id` FROM `intro` WHERE `contact-id` = `contact`.`id` AND NOT `ignore`))";
				$sql_values[] = Model\Contact::SHARING;
				break;
			default:
				$sql_extra = " AND NOT `archive` AND NOT `blocked` AND NOT `pending`";
				break;
		}

		if (isset($accounttypeid)) {
			$sql_extra .= " AND `contact-type` = ?";
			$sql_values[] = $accounttypeid;
		}

		$searching = false;
		$search_hdr = null;
		if ($search) {
			$searching = true;
			$search_hdr = $search;
			$search_txt = preg_quote($search);
			$sql_extra .= " AND (name REGEXP ? OR url REGEXP ? OR nick REGEXP ?)";
			$sql_values[] = $search_txt;
			$sql_values[] = $search_txt;
			$sql_values[] = $search_txt;
		}

		if ($nets) {
			$sql_extra .= " AND network = ? ";
			$sql_values[] = $nets;
		}

		switch ($rel) {
			case 'followers':
				$sql_extra .= " AND `rel` IN (?, ?)";
				$sql_values[] = Model\Contact::FOLLOWER;
				$sql_values[] = Model\Contact::FRIEND;
				break;
			case 'following':
				$sql_extra .= " AND `rel` IN (?, ?)";
				$sql_values[] = Model\Contact::SHARING;
				$sql_values[] = Model\Contact::FRIEND;
				break;
			case 'mutuals':
				$sql_extra .= " AND `rel` = ?";
				$sql_values[] = Model\Contact::FRIEND;
				break;
		}

		if ($group) {
			$sql_extra = " AND EXISTS(SELECT `id` FROM `group_member` WHERE `gid` = ? AND `contact`.`id` = `contact-id`)";
			$sql_values[] = $group;
		}

		$total = 0;
		$stmt = DBA::p("SELECT COUNT(*) AS `total`
			FROM `contact`
			WHERE `uid` = ?
			AND `self` = 0
			AND NOT `deleted`
			$sql_extra
			" . Widget::unavailableNetworks(),
			$sql_values
		);
		if (DBA::isResult($stmt)) {
			$total = DBA::fetch($stmt)['total'];
		}
		DBA::close($stmt);

		$pager = new Pager(DI::l10n(), DI::args()->getQueryString());

		$sql_values[] = $pager->getStart();
		$sql_values[] = $pager->getItemsPerPage();

		$contacts = [];

		$stmt = DBA::p("SELECT *
			FROM `contact`
			WHERE `uid` = ?
			AND `self` = 0
			AND NOT `deleted`
			$sql_extra
			ORDER BY `name` ASC
			LIMIT ?, ?",
			$sql_values
		);
		while ($contact = DBA::fetch($stmt)) {
			$contact['blocked'] = Model\Contact\User::isBlocked($contact['id'], local_user());
			$contact['readonly'] = Model\Contact\User::isIgnored($contact['id'], local_user());
			$contacts[] = self::getContactTemplateVars($contact);
		}
		DBA::close($stmt);

		$tabs = [
			[
				'label' => DI::l10n()->t('All Contacts'),
				'url'   => 'contact',
				'sel'   => !$type ? 'active' : '',
				'title' => DI::l10n()->t('Show all contacts'),
				'id'    => 'showall-tab',
				'accesskey' => 'l',
			],
			[
				'label' => DI::l10n()->t('Pending'),
				'url'   => 'contact/pending',
				'sel'   => $type == 'pending' ? 'active' : '',
				'title' => DI::l10n()->t('Only show pending contacts'),
				'id'    => 'showpending-tab',
				'accesskey' => 'p',
			],
			[
				'label' => DI::l10n()->t('Blocked'),
				'url'   => 'contact/blocked',
				'sel'   => $type == 'blocked' ? 'active' : '',
				'title' => DI::l10n()->t('Only show blocked contacts'),
				'id'    => 'showblocked-tab',
				'accesskey' => 'b',
			],
			[
				'label' => DI::l10n()->t('Ignored'),
				'url'   => 'contact/ignored',
				'sel'   => $type == 'ignored' ? 'active' : '',
				'title' => DI::l10n()->t('Only show ignored contacts'),
				'id'    => 'showignored-tab',
				'accesskey' => 'i',
			],
			[
				'label' => DI::l10n()->t('Archived'),
				'url'   => 'contact/archived',
				'sel'   => $type == 'archived' ? 'active' : '',
				'title' => DI::l10n()->t('Only show archived contacts'),
				'id'    => 'showarchived-tab',
				'accesskey' => 'y',
			],
			[
				'label' => DI::l10n()->t('Hidden'),
				'url'   => 'contact/hidden',
				'sel'   => $type == 'hidden' ? 'active' : '',
				'title' => DI::l10n()->t('Only show hidden contacts'),
				'id'    => 'showhidden-tab',
				'accesskey' => 'h',
			],
			[
				'label' => DI::l10n()->t('Groups'),
				'url'   => 'group',
				'sel'   => '',
				'title' => DI::l10n()->t('Organize your contact groups'),
				'id'    => 'contactgroups-tab',
				'accesskey' => 'e',
			],
		];

		$tabs_tpl = Renderer::getMarkupTemplate('common_tabs.tpl');
		$tabs_html = Renderer::replaceMacros($tabs_tpl, ['$tabs' => $tabs]);

		switch ($rel) {
			case 'followers': $header = DI::l10n()->t('Followers'); break;
			case 'following': $header = DI::l10n()->t('Following'); break;
			case 'mutuals':   $header = DI::l10n()->t('Mutual friends'); break;
			default:          $header = DI::l10n()->t('Contacts');
		}

		switch ($type) {
			case 'pending':	 $header .= ' - ' . DI::l10n()->t('Pending'); break;
			case 'blocked':	 $header .= ' - ' . DI::l10n()->t('Blocked'); break;
			case 'hidden':   $header .= ' - ' . DI::l10n()->t('Hidden'); break;
			case 'ignored':  $header .= ' - ' . DI::l10n()->t('Ignored'); break;
			case 'archived': $header .= ' - ' . DI::l10n()->t('Archived'); break;
		}

		$header .= $nets ? ' - ' . ContactSelector::networkToName($nets) : '';

		$tpl = Renderer::getMarkupTemplate('contacts-template.tpl');
		$o .= Renderer::replaceMacros($tpl, [
			'$header'     => $header,
			'$tabs'       => $tabs_html,
			'$total'      => $total,
			'$search'     => $search_hdr,
			'$desc'       => DI::l10n()->t('Search your contacts'),
			'$finding'    => $searching ? DI::l10n()->t('Results for: %s', $search) : '',
			'$submit'     => DI::l10n()->t('Find'),
			'$cmd'        => DI::args()->getCommand(),
			'$contacts'   => $contacts,
			'$form_security_token'  => BaseModule::getFormSecurityToken('contact_batch_actions'),
			'$contact_drop_confirm' => DI::l10n()->t('Do you really want to delete this contact?'),
			'multiselect' => 1,
			'$batch_actions' => [
				'contacts_batch_update'  => DI::l10n()->t('Update'),
				'contacts_batch_block'   => DI::l10n()->t('Block') . '/' . DI::l10n()->t('Unblock'),
				'contacts_batch_ignore'  => DI::l10n()->t('Ignore') . '/' . DI::l10n()->t('Unignore'),
				'contacts_batch_drop'    => DI::l10n()->t('Delete'),
			],
			'$h_batch_actions' => DI::l10n()->t('Batch Actions'),
			'$paginate'   => $pager->renderFull($total),
		]);

		return $o;
	}

	/**
	 * List of pages for the Contact TabBar
	 *
	 * Available Pages are 'Status', 'Profile', 'Contacts' and 'Common Friends'
	 *
	 * @param array $contact    The contact array
	 * @param int   $active_tab 1 if tab should be marked as active
	 *
	 * @return string HTML string of the contact page tabs buttons.
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function getTabsHTML(array $contact, int $active_tab)
	{
		$cid = $pcid = $contact['id'];
		$data = Model\Contact::getPublicAndUserContactID($contact['id'], local_user());
		if (!empty($data['user']) && ($contact['id'] == $data['public'])) {
			$cid = $data['user'];
		} elseif (!empty($data['public'])) {
			$pcid = $data['public'];
		}

		// tabs
		$tabs = [
			[
				'label' => DI::l10n()->t('Status'),
				'url'   => 'contact/' . $pcid . '/conversations',
				'sel'   => (($active_tab == self::TAB_CONVERSATIONS) ? 'active' : ''),
				'title' => DI::l10n()->t('Conversations started by this contact'),
				'id'    => 'status-tab',
				'accesskey' => 'm',
			],
			[
				'label' => DI::l10n()->t('Posts and Comments'),
				'url'   => 'contact/' . $pcid . '/posts',
				'sel'   => (($active_tab == self::TAB_POSTS) ? 'active' : ''),
				'title' => DI::l10n()->t('Status Messages and Posts'),
				'id'    => 'posts-tab',
				'accesskey' => 'p',
			],
			[
				'label' => DI::l10n()->t('Profile'),
				'url'   => 'contact/' . $cid,
				'sel'   => (($active_tab == self::TAB_PROFILE) ? 'active' : ''),
				'title' => DI::l10n()->t('Profile Details'),
				'id'    => 'profile-tab',
				'accesskey' => 'o',
			],
			['label' => DI::l10n()->t('Contacts'),
				'url'   => 'contact/' . $pcid . '/contacts',
				'sel'   => (($active_tab == self::TAB_CONTACTS) ? 'active' : ''),
				'title' => DI::l10n()->t('View all known contacts'),
				'id'    => 'contacts-tab',
				'accesskey' => 't'
			],
		];

		if (!empty($contact['network']) && in_array($contact['network'], [Protocol::FEED, Protocol::MAIL]) && ($cid != $pcid)) {
			$tabs[] = ['label' => DI::l10n()->t('Advanced'),
				'url'   => 'contact/' . $cid . '/advanced/',
				'sel'   => (($active_tab == self::TAB_ADVANCED) ? 'active' : ''),
				'title' => DI::l10n()->t('Advanced Contact Settings'),
				'id'    => 'advanced-tab',
				'accesskey' => 'r'
			];
		}

		$tab_tpl = Renderer::getMarkupTemplate('common_tabs.tpl');
		$tab_str = Renderer::replaceMacros($tab_tpl, ['$tabs' => $tabs]);

		return $tab_str;
	}

	public static function getConversationsHMTL($a, $contact_id, $update, $parent = 0)
	{
		$o = '';

		if (!$update) {
			// We need the editor here to be able to reshare an item.
			if (local_user()) {
				$o = status_editor($a, [], 0, true);
			}
		}

		$contact = DBA::selectFirst('contact', ['uid', 'url', 'id'], ['id' => $contact_id, 'deleted' => false]);

		if (!$update) {
			$o .= self::getTabsHTML($contact, self::TAB_CONVERSATIONS);
		}

		if (DBA::isResult($contact)) {
			if (!$update) {
				$profiledata = Model\Contact::getByURLForUser($contact['url'], local_user());
				DI::page()['aside'] = Widget\VCard::getHTML($profiledata);
			} else {
				DI::page()['aside'] = '';
			}

			if ($contact['uid'] == 0) {
				$o .= Model\Contact::getPostsFromId($contact['id'], true, $update, $parent);
			} else {
				$o .= Model\Contact::getPostsFromUrl($contact['url'], true, $update, $parent);
			}
		}

		return $o;
	}

	private static function getPostsHTML($a, $contact_id)
	{
		$contact = DBA::selectFirst('contact', ['uid', 'url', 'id'], ['id' => $contact_id, 'deleted' => false]);

		$o = self::getTabsHTML($contact, self::TAB_POSTS);

		if (DBA::isResult($contact)) {
			$profiledata = Model\Contact::getByURLForUser($contact['url'], local_user());

			if (local_user() && in_array($profiledata['network'], Protocol::FEDERATED)) {
				$profiledata['remoteconnect'] = DI::baseUrl() . '/follow?url=' . urlencode($profiledata['url']);
			}

			DI::page()['aside'] = Widget\VCard::getHTML($profiledata);

			if ($contact['uid'] == 0) {
				$o .= Model\Contact::getPostsFromId($contact['id']);
			} else {
				$o .= Model\Contact::getPostsFromUrl($contact['url']);
			}
		}

		return $o;
	}

	/**
	 * Return the fields for the contact template
	 *
	 * @param array $contact Contact array
	 * @return array Template fields
	 */
	public static function getContactTemplateVars(array $contact)
	{
		$alt_text = '';

		if (!empty($contact['url']) && isset($contact['uid']) && ($contact['uid'] == 0) && local_user()) {
			$personal = Model\Contact::getByURL($contact['url'], false, ['uid', 'rel', 'self'], local_user());
			if (!empty($personal)) {
				$contact['uid'] = $personal['uid'];
				$contact['rel'] = $personal['rel'];
				$contact['self'] = $personal['self'];
			}
		}

		if (!empty($contact['uid']) && !empty($contact['rel']) && local_user() == $contact['uid']) {
			switch ($contact['rel']) {
				case Model\Contact::FRIEND:
					$alt_text = DI::l10n()->t('Mutual Friendship');
					break;

				case Model\Contact::FOLLOWER;
					$alt_text = DI::l10n()->t('is a fan of yours');
					break;

				case Model\Contact::SHARING;
					$alt_text = DI::l10n()->t('you are a fan of');
					break;

				default:
					break;
			}
		}

		$url = Model\Contact::magicLinkByContact($contact);

		if (strpos($url, 'redir/') === 0) {
			$sparkle = ' class="sparkle" ';
		} else {
			$sparkle = '';
		}

		if ($contact['pending']) {
			if (in_array($contact['rel'], [Model\Contact::FRIEND, Model\Contact::SHARING])) {
				$alt_text = DI::l10n()->t('Pending outgoing contact request');
			} else {
				$alt_text = DI::l10n()->t('Pending incoming contact request');
			}
		}

		if ($contact['self']) {
			$alt_text = DI::l10n()->t('This is you');
			$url = $contact['url'];
			$sparkle = '';
		}

		return [
			'id'           => $contact['id'],
			'url'          => $url,
			'img_hover'    => DI::l10n()->t('Visit %s\'s profile [%s]', $contact['name'], $contact['url']),
			'photo_menu'   => Model\Contact::photoMenu($contact),
			'thumb'        => Model\Contact::getThumb($contact, true),
			'alt_text'     => $alt_text,
			'name'         => $contact['name'],
			'nick'         => $contact['nick'],
			'details'      => $contact['location'],
			'tags'         => $contact['keywords'],
			'about'        => $contact['about'],
			'account_type' => Model\Contact::getAccountType($contact),
			'sparkle'      => $sparkle,
			'itemurl'      => ($contact['addr'] ?? '') ?: $contact['url'],
			'network'      => ContactSelector::networkToName($contact['network'], $contact['url'], $contact['protocol'], $contact['gsid']),
		];
	}

	/**
	 * Gives a array with actions which can performed to a given contact
	 *
	 * This includes actions like e.g. 'block', 'hide', 'delete' and others
	 *
	 * @param array $contact Data about the Contact
	 * @return array with contact related actions
	 */
	private static function getContactActions($contact)
	{
		$poll_enabled = in_array($contact['network'], [Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::OSTATUS, Protocol::FEED, Protocol::MAIL]);
		$contact_actions = [];

		$formSecurityToken = self::getFormSecurityToken('contact_action');

		// Provide friend suggestion only for Friendica contacts
		if ($contact['network'] === Protocol::DFRN) {
			$contact_actions['suggest'] = [
				'label' => DI::l10n()->t('Suggest friends'),
				'url'   => 'fsuggest/' . $contact['id'],
				'title' => '',
				'sel'   => '',
				'id'    => 'suggest',
			];
		}

		if ($poll_enabled) {
			$contact_actions['update'] = [
				'label' => DI::l10n()->t('Update now'),
				'url'   => 'contact/' . $contact['id'] . '/update?t=' . $formSecurityToken,
				'title' => '',
				'sel'   => '',
				'id'    => 'update',
			];
		}

		if (in_array($contact['network'], Protocol::NATIVE_SUPPORT)) {
			$contact_actions['updateprofile'] = [
				'label' => DI::l10n()->t('Refetch contact data'),
				'url'   => 'contact/' . $contact['id'] . '/updateprofile?t=' . $formSecurityToken,
				'title' => '',
				'sel'   => '',
				'id'    => 'updateprofile',
			];
		}

		$contact_actions['block'] = [
			'label' => (intval($contact['blocked']) ? DI::l10n()->t('Unblock') : DI::l10n()->t('Block')),
			'url'   => 'contact/' . $contact['id'] . '/block?t=' . $formSecurityToken,
			'title' => DI::l10n()->t('Toggle Blocked status'),
			'sel'   => (intval($contact['blocked']) ? 'active' : ''),
			'id'    => 'toggle-block',
		];

		$contact_actions['ignore'] = [
			'label' => (intval($contact['readonly']) ? DI::l10n()->t('Unignore') : DI::l10n()->t('Ignore')),
			'url'   => 'contact/' . $contact['id'] . '/ignore?t=' . $formSecurityToken,
			'title' => DI::l10n()->t('Toggle Ignored status'),
			'sel'   => (intval($contact['readonly']) ? 'active' : ''),
			'id'    => 'toggle-ignore',
		];

		if ($contact['uid'] != 0) {
			$contact_actions['delete'] = [
				'label' => DI::l10n()->t('Delete'),
				'url'   => 'contact/' . $contact['id'] . '/drop?t=' . $formSecurityToken,
				'title' => DI::l10n()->t('Delete contact'),
				'sel'   => '',
				'id'    => 'delete',
			];
		}

		return $contact_actions;
	}
}
