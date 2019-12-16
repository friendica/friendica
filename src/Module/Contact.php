<?php

namespace Friendica\Module;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Content\ContactSelector;
use Friendica\Content\Nav;
use Friendica\Content\Pager;
use Friendica\Content\Text\BBCode;
use Friendica\Content\Widget;
use Friendica\Core\ACL;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Proxy as ProxyUtils;
use Friendica\Util\Strings;

/**
 *  Manages and show Contacts and their content
 *
 *  @brief manages contacts
 */
class Contact extends BaseModule
{
	private static function batchActions()
	{
		if (empty($_POST['contact_batch']) || !is_array($_POST['contact_batch'])) {
			return;
		}

		$contacts_id = $_POST['contact_batch'];

		$stmt = DBA::select('contact', ['id', 'archive'], ['id' => $contacts_id, 'uid' => local_user(), 'self' => false, 'deleted' => false]);
		$orig_records = DBA::toArray($stmt);

		$count_actions = 0;
		foreach ($orig_records as $orig_record) {
			$contact_id = $orig_record['id'];
			if (!empty($_POST['contacts_batch_update'])) {
				self::updateContactFromPoll($contact_id);
				$count_actions++;
			}
			if (!empty($_POST['contacts_batch_block'])) {
				self::blockContact($contact_id);
				$count_actions++;
			}
			if (!empty($_POST['contacts_batch_ignore'])) {
				self::ignoreContact($contact_id);
				$count_actions++;
			}
			if (!empty($_POST['contacts_batch_archive'])
				&& self::archiveContact($contact_id, $orig_record)
			) {
				$count_actions++;
			}
			if (!empty($_POST['contacts_batch_drop'])) {
				self::dropContact($orig_record);
				$count_actions++;
			}
		}
		if ($count_actions > 0) {
			info(L10n::tt('%d contact edited.', '%d contacts edited.', $count_actions));
		}

		DI::baseUrl()->redirect('contact');
	}

	public static function post(array $parameters = [])
	{
		$a = DI::app();

		if (!local_user()) {
			return;
		}

		// @TODO: Replace with parameter from router
		if ($a->argv[1] === 'batch') {
			self::batchActions();
			return;
		}

		// @TODO: Replace with parameter from router
		$contact_id = intval($a->argv[1]);
		if (!$contact_id) {
			return;
		}

		if (!DBA::exists('contact', ['id' => $contact_id, 'uid' => local_user(), 'deleted' => false])) {
			notice(L10n::t('Could not access contact record.') . EOL);
			DI::baseUrl()->redirect('contact');
			return; // NOTREACHED
		}

		Hook::callAll('contact_edit_post', $_POST);

		$profile_id = intval($_POST['profile-assign'] ?? 0);
		if ($profile_id) {
			if (!DBA::exists('profile', ['id' => $profile_id, 'uid' => local_user()])) {
				notice(L10n::t('Could not locate selected profile.') . EOL);
				return;
			}
		}

		$hidden = !empty($_POST['hidden']);

		$notify = !empty($_POST['notify']);

		$fetch_further_information = intval($_POST['fetch_further_information'] ?? 0);

		$ffi_keyword_blacklist = Strings::escapeHtml(trim($_POST['ffi_keyword_blacklist'] ?? ''));

		$priority = intval($_POST['poll'] ?? 0);
		if ($priority > 5 || $priority < 0) {
			$priority = 0;
		}

		$info = Strings::escapeHtml(trim($_POST['info'] ?? ''));

		$r = DBA::update('contact', [
			'profile-id' => $profile_id,
			'priority'   => $priority,
			'info'       => $info,
			'hidden'     => $hidden,
			'notify_new_posts' => $notify,
			'fetch_further_information' => $fetch_further_information,
			'ffi_keyword_blacklist'     => $ffi_keyword_blacklist],
			['id' => $contact_id, 'uid' => local_user()]
		);

		if (DBA::isResult($r)) {
			info(L10n::t('Contact updated.') . EOL);
		} else {
			notice(L10n::t('Failed to update contact record.') . EOL);
		}

		$contact = DBA::selectFirst('contact', [], ['id' => $contact_id, 'uid' => local_user(), 'deleted' => false]);
		if (DBA::isResult($contact)) {
			$a->data['contact'] = $contact;
		}

		return;
	}

	/* contact actions */

	private static function updateContactFromPoll($contact_id)
	{
		$contact = DBA::selectFirst('contact', ['uid', 'url', 'network'], ['id' => $contact_id, 'uid' => local_user(), 'deleted' => false]);
		if (!DBA::isResult($contact)) {
			return;
		}

		$uid = $contact['uid'];

		if ($contact['network'] == Protocol::OSTATUS) {
			$result = Model\Contact::createFromProbe($uid, $contact['url'], false, $contact['network']);

			if ($result['success']) {
				DBA::update('contact', ['subhub' => 1], ['id' => $contact_id]);
			}
		} else {
			// pull feed and consume it, which should subscribe to the hub.
			Worker::add(PRIORITY_HIGH, 'OnePoll', $contact_id, 'force');
		}
	}

	private static function updateContactFromProbe($contact_id)
	{
		$contact = DBA::selectFirst('contact', ['url'], ['id' => $contact_id, 'uid' => local_user(), 'deleted' => false]);
		if (!DBA::isResult($contact)) {
			return;
		}

		// Update the entry in the contact table
		Model\Contact::updateFromProbe($contact_id, '', true);

		// Update the entry in the gcontact table
		Model\GContact::updateFromProbe($contact['url']);
	}

	/**
	 * Toggles the blocked status of a contact identified by id.
	 *
	 * @param $contact_id
	 * @throws \Exception
	 */
	private static function blockContact($contact_id)
	{
		$blocked = !Model\Contact::isBlockedByUser($contact_id, local_user());
		Model\Contact::setBlockedForUser($contact_id, local_user(), $blocked);
	}

	/**
	 * Toggles the ignored status of a contact identified by id.
	 *
	 * @param $contact_id
	 * @throws \Exception
	 */
	private static function ignoreContact($contact_id)
	{
		$ignored = !Model\Contact::isIgnoredByUser($contact_id, local_user());
		Model\Contact::setIgnoredForUser($contact_id, local_user(), $ignored);
	}

	/**
	 * Toggles the archived status of a contact identified by id.
	 * If the current status isn't provided, this will always archive the contact.
	 *
	 * @param $contact_id
	 * @param $orig_record
	 * @return bool
	 * @throws \Exception
	 */
	private static function archiveContact($contact_id, $orig_record)
	{
		$archived = empty($orig_record['archive']);
		$r = DBA::update('contact', ['archive' => $archived], ['id' => $contact_id, 'uid' => local_user()]);

		return DBA::isResult($r);
	}

	private static function dropContact($orig_record)
	{
		$owner = Model\User::getOwnerDataById(local_user());
		if (!DBA::isResult($owner)) {
			return;
		}

		Model\Contact::terminateFriendship($owner, $orig_record, true);
		Model\Contact::remove($orig_record['id']);
	}

	public static function content(array $parameters = [], $update = 0)
	{
		if (!local_user()) {
			return Login::form($_SERVER['REQUEST_URI']);
		}

		$a = DI::app();

		$nets = $_GET['nets'] ?? '';
		$rel  = $_GET['rel']  ?? '';

		if (empty($a->page['aside'])) {
			$a->page['aside'] = '';
		}

		$contact_id = null;
		$contact = null;
		// @TODO: Replace with parameter from router
		if ($a->argc == 2 && intval($a->argv[1])
			|| $a->argc == 3 && intval($a->argv[1]) && in_array($a->argv[2], ['posts', 'conversations'])
		) {
			$contact_id = intval($a->argv[1]);
			$contact = DBA::selectFirst('contact', [], ['id' => $contact_id, 'uid' => local_user(), 'deleted' => false]);

			if (!DBA::isResult($contact)) {
				$contact = DBA::selectFirst('contact', [], ['id' => $contact_id, 'uid' => 0, 'deleted' => false]);
			}

			// Don't display contacts that are about to be deleted
			if ($contact['network'] == Protocol::PHANTOM) {
				$contact = false;
			}
		}

		if (DBA::isResult($contact)) {
			if ($contact['self']) {
				// @TODO: Replace with parameter from router
				if (($a->argc == 3) && intval($a->argv[1]) && in_array($a->argv[2], ['posts', 'conversations'])) {
					DI::baseUrl()->redirect('profile/' . $contact['nick']);
				} else {
					DI::baseUrl()->redirect('profile/' . $contact['nick'] . '?tab=profile');
				}
			}

			$a->data['contact'] = $contact;

			if (($contact['network'] != '') && ($contact['network'] != Protocol::DFRN)) {
				$network_link = Strings::formatNetworkName($contact['network'], $contact['url']);
			} else {
				$network_link = '';
			}

			$follow_link = '';
			$unfollow_link = '';
			if (in_array($contact['network'], Protocol::NATIVE_SUPPORT)) {
				if ($contact['uid'] && in_array($contact['rel'], [Model\Contact::SHARING, Model\Contact::FRIEND])) {
					$unfollow_link = 'unfollow?url=' . urlencode($contact['url']);
				} elseif(!$contact['pending']) {
					$follow_link = 'follow?url=' . urlencode($contact['url']);
				}
			}

			$wallmessage_link = '';
			if ($contact['uid'] && Model\Contact::canReceivePrivateMessages($contact)) {
				$wallmessage_link = 'message/new/' . $contact['id'];
			}

			$vcard_widget = Renderer::replaceMacros(Renderer::getMarkupTemplate('widget/vcard.tpl'), [
				'$name'         => $contact['name'],
				'$photo'        => $contact['photo'],
				'$url'          => Model\Contact::magicLinkByContact($contact, $contact['url']),
				'$addr'         => $contact['addr'] ?? '',
				'$network_link' => $network_link,
				'$network'      => L10n::t('Network:'),
				'$account_type' => Model\Contact::getAccountType($contact),
				'$follow'       => L10n::t('Follow'),
				'$follow_link'   => $follow_link,
				'$unfollow'     => L10n::t('Unfollow'),
				'$unfollow_link' => $unfollow_link,
				'$wallmessage'  => L10n::t('Message'),
				'$wallmessage_link' => $wallmessage_link,
			]);

			$findpeople_widget = '';
			$follow_widget = '';
			$networks_widget = '';
			$rel_widget = '';
		} else {
			$vcard_widget = '';
			$findpeople_widget = Widget::findPeople();
			if (isset($_GET['add'])) {
				$follow_widget = Widget::follow($_GET['add']);
			} else {
				$follow_widget = Widget::follow();
			}

			$networks_widget = Widget::networks($_SERVER['REQUEST_URI'], $nets);
			$rel_widget = Widget::contactRels($_SERVER['REQUEST_URI'], $rel);
		}

		if ($contact['uid'] != 0) {
			$groups_widget = Model\Group::sidebarWidget('contact', 'group', 'full', 'everyone', $contact_id);
		} else {
			$groups_widget = null;
		}

		$a->page['aside'] .= $vcard_widget . $findpeople_widget . $follow_widget . $groups_widget . $networks_widget . $rel_widget;

		$tpl = Renderer::getMarkupTemplate('contacts-head.tpl');
		$a->page['htmlhead'] .= Renderer::replaceMacros($tpl, [
			'$baseurl' => DI::baseUrl()->get(true),
		]);

		$sort_type = 0;
		$o = '';
		Nav::setSelected('contact');

		if (!local_user()) {
			notice(L10n::t('Permission denied.') . EOL);
			return Login::form();
		}

		if ($a->argc == 3) {
			$contact_id = intval($a->argv[1]);
			if (!$contact_id) {
				throw new BadRequestException();
			}

			// @TODO: Replace with parameter from router
			$cmd = $a->argv[2];

			$orig_record = DBA::selectFirst('contact', [], ['id' => $contact_id, 'uid' => [0, local_user()], 'self' => false, 'deleted' => false]);
			if (!DBA::isResult($orig_record)) {
				throw new NotFoundException(L10n::t('Contact not found'));
			}

			if ($cmd === 'update' && ($orig_record['uid'] != 0)) {
				self::updateContactFromPoll($contact_id);
				DI::baseUrl()->redirect('contact/' . $contact_id);
				// NOTREACHED
			}

			if ($cmd === 'updateprofile' && ($orig_record['uid'] != 0)) {
				self::updateContactFromProbe($contact_id);
				DI::baseUrl()->redirect('crepair/' . $contact_id);
				// NOTREACHED
			}

			if ($cmd === 'block') {
				self::blockContact($contact_id);

				$blocked = Model\Contact::isBlockedByUser($contact_id, local_user());
				info(($blocked ? L10n::t('Contact has been blocked') : L10n::t('Contact has been unblocked')) . EOL);

				DI::baseUrl()->redirect('contact/' . $contact_id);
				// NOTREACHED
			}

			if ($cmd === 'ignore') {
				self::ignoreContact($contact_id);

				$ignored = Model\Contact::isIgnoredByUser($contact_id, local_user());
				info(($ignored ? L10n::t('Contact has been ignored') : L10n::t('Contact has been unignored')) . EOL);

				DI::baseUrl()->redirect('contact/' . $contact_id);
				// NOTREACHED
			}

			if ($cmd === 'archive' && ($orig_record['uid'] != 0)) {
				$r = self::archiveContact($contact_id, $orig_record);
				if ($r) {
					$archived = (($orig_record['archive']) ? 0 : 1);
					info((($archived) ? L10n::t('Contact has been archived') : L10n::t('Contact has been unarchived')) . EOL);
				}

				DI::baseUrl()->redirect('contact/' . $contact_id);
				// NOTREACHED
			}

			if ($cmd === 'drop' && ($orig_record['uid'] != 0)) {
				// Check if we should do HTML-based delete confirmation
				if (!empty($_REQUEST['confirm'])) {
					// <form> can't take arguments in its 'action' parameter
					// so add any arguments as hidden inputs
					$query = explode_querystring($a->query_string);
					$inputs = [];
					foreach ($query['args'] as $arg) {
						if (strpos($arg, 'confirm=') === false) {
							$arg_parts = explode('=', $arg);
							$inputs[] = ['name' => $arg_parts[0], 'value' => $arg_parts[1]];
						}
					}

					$a->page['aside'] = '';

					return Renderer::replaceMacros(Renderer::getMarkupTemplate('contact_drop_confirm.tpl'), [
						'$header' => L10n::t('Drop contact'),
						'$contact' => self::getContactTemplateVars($orig_record),
						'$method' => 'get',
						'$message' => L10n::t('Do you really want to delete this contact?'),
						'$extra_inputs' => $inputs,
						'$confirm' => L10n::t('Yes'),
						'$confirm_url' => $query['base'],
						'$confirm_name' => 'confirmed',
						'$cancel' => L10n::t('Cancel'),
					]);
				}
				// Now check how the user responded to the confirmation query
				if (!empty($_REQUEST['canceled'])) {
					DI::baseUrl()->redirect('contact');
				}

				self::dropContact($orig_record);
				info(L10n::t('Contact has been removed.') . EOL);

				DI::baseUrl()->redirect('contact');
				// NOTREACHED
			}
			if ($cmd === 'posts') {
				return self::getPostsHTML($a, $contact_id);
			}
			if ($cmd === 'conversations') {
				return self::getConversationsHMTL($a, $contact_id, $update);
			}
		}

		$_SESSION['return_path'] = $a->query_string;

		if (!empty($a->data['contact']) && is_array($a->data['contact'])) {
			$contact = $a->data['contact'];

			$a->page['htmlhead'] .= Renderer::replaceMacros(Renderer::getMarkupTemplate('contact_head.tpl'), [
				'$baseurl' => DI::baseUrl()->get(true),
			]);

			$contact['blocked']  = Model\Contact::isBlockedByUser($contact['id'], local_user());
			$contact['readonly'] = Model\Contact::isIgnoredByUser($contact['id'], local_user());

			$dir_icon = '';
			$relation_text = '';
			switch ($contact['rel']) {
				case Model\Contact::FRIEND:
					$dir_icon = 'images/lrarrow.gif';
					$relation_text = L10n::t('You are mutual friends with %s');
					break;

				case Model\Contact::FOLLOWER;
					$dir_icon = 'images/larrow.gif';
					$relation_text = L10n::t('You are sharing with %s');
					break;

				case Model\Contact::SHARING;
					$dir_icon = 'images/rarrow.gif';
					$relation_text = L10n::t('%s is sharing with you');
					break;

				default:
					break;
			}

			if ($contact['uid'] == 0) {
				$relation_text = '';
			}

			if (!in_array($contact['network'], Protocol::FEDERATED)) {
				$relation_text = '';
			}

			$relation_text = sprintf($relation_text, $contact['name']);

			$url = Model\Contact::magicLink($contact['url']);
			if (strpos($url, 'redir/') === 0) {
				$sparkle = ' class="sparkle" ';
			} else {
				$sparkle = '';
			}

			$insecure = L10n::t('Private communications are not available for this contact.');

			$last_update = (($contact['last-update'] <= DBA::NULL_DATETIME) ? L10n::t('Never') : DateTimeFormat::local($contact['last-update'], 'D, j M Y, g:i A'));

			if ($contact['last-update'] > DBA::NULL_DATETIME) {
				$last_update .= ' ' . (($contact['last-update'] <= $contact['success_update']) ? L10n::t('(Update was successful)') : L10n::t('(Update was not successful)'));
			}
			$lblsuggest = (($contact['network'] === Protocol::DFRN) ? L10n::t('Suggest friends') : '');

			$poll_enabled = in_array($contact['network'], [Protocol::DFRN, Protocol::OSTATUS, Protocol::FEED, Protocol::MAIL]);

			$nettype = L10n::t('Network type: %s', ContactSelector::networkToName($contact['network'], $contact['url']));

			// tabs
			$tab_str = self::getTabsHTML($a, $contact, 3);

			$lost_contact = (($contact['archive'] && $contact['term-date'] > DBA::NULL_DATETIME && $contact['term-date'] < DateTimeFormat::utcNow()) ? L10n::t('Communications lost with this contact!') : '');

			$fetch_further_information = null;
			if ($contact['network'] == Protocol::FEED) {
				$fetch_further_information = [
					'fetch_further_information',
					L10n::t('Fetch further information for feeds'),
					$contact['fetch_further_information'],
					L10n::t('Fetch information like preview pictures, title and teaser from the feed item. You can activate this if the feed doesn\'t contain much text. Keywords are taken from the meta header in the feed item and are posted as hash tags.'),
					[
						'0' => L10n::t('Disabled'),
						'1' => L10n::t('Fetch information'),
						'3' => L10n::t('Fetch keywords'),
						'2' => L10n::t('Fetch information and keywords')
					]
				];
			}

			$poll_interval = null;
			if (in_array($contact['network'], [Protocol::FEED, Protocol::MAIL])) {
				$poll_interval = ContactSelector::pollInterval($contact['priority'], !$poll_enabled);
			}

			$profile_select = null;
			if ($contact['network'] == Protocol::DFRN) {
				$profile_select = ContactSelector::profileAssign($contact['profile-id'], $contact['network'] !== Protocol::DFRN);
			}

			// Load contactact related actions like hide, suggest, delete and others
			$contact_actions = self::getContactActions($contact);

			if ($contact['uid'] != 0) {
				$lbl_vis1 = L10n::t('Profile Visibility');
				$lbl_info1 = L10n::t('Contact Information / Notes');
				$contact_settings_label = L10n::t('Contact Settings');
			} else {
				$lbl_vis1 = null;
				$lbl_info1 = null;
				$contact_settings_label = null;
			}

			$tpl = Renderer::getMarkupTemplate('contact_edit.tpl');
			$o .= Renderer::replaceMacros($tpl, [
				'$header'         => L10n::t('Contact'),
				'$tab_str'        => $tab_str,
				'$submit'         => L10n::t('Submit'),
				'$lbl_vis1'       => $lbl_vis1,
				'$lbl_vis2'       => L10n::t('Please choose the profile you would like to display to %s when viewing your profile securely.', $contact['name']),
				'$lbl_info1'      => $lbl_info1,
				'$lbl_info2'      => L10n::t('Their personal note'),
				'$reason'         => trim(Strings::escapeTags($contact['reason'])),
				'$infedit'        => L10n::t('Edit contact notes'),
				'$common_link'    => 'common/loc/' . local_user() . '/' . $contact['id'],
				'$relation_text'  => $relation_text,
				'$visit'          => L10n::t('Visit %s\'s profile [%s]', $contact['name'], $contact['url']),
				'$blockunblock'   => L10n::t('Block/Unblock contact'),
				'$ignorecont'     => L10n::t('Ignore contact'),
				'$lblcrepair'     => L10n::t('Repair URL settings'),
				'$lblrecent'      => L10n::t('View conversations'),
				'$lblsuggest'     => $lblsuggest,
				'$nettype'        => $nettype,
				'$poll_interval'  => $poll_interval,
				'$poll_enabled'   => $poll_enabled,
				'$lastupdtext'    => L10n::t('Last update:'),
				'$lost_contact'   => $lost_contact,
				'$updpub'         => L10n::t('Update public posts'),
				'$last_update'    => $last_update,
				'$udnow'          => L10n::t('Update now'),
				'$profile_select' => $profile_select,
				'$contact_id'     => $contact['id'],
				'$block_text'     => ($contact['blocked'] ? L10n::t('Unblock') : L10n::t('Block')),
				'$ignore_text'    => ($contact['readonly'] ? L10n::t('Unignore') : L10n::t('Ignore')),
				'$insecure'       => (in_array($contact['network'], [Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::MAIL, Protocol::DIASPORA]) ? '' : $insecure),
				'$info'           => $contact['info'],
				'$cinfo'          => ['info', '', $contact['info'], ''],
				'$blocked'        => ($contact['blocked'] ? L10n::t('Currently blocked') : ''),
				'$ignored'        => ($contact['readonly'] ? L10n::t('Currently ignored') : ''),
				'$archived'       => ($contact['archive'] ? L10n::t('Currently archived') : ''),
				'$pending'        => ($contact['pending'] ? L10n::t('Awaiting connection acknowledge') : ''),
				'$hidden'         => ['hidden', L10n::t('Hide this contact from others'), ($contact['hidden'] == 1), L10n::t('Replies/likes to your public posts <strong>may</strong> still be visible')],
				'$notify'         => ['notify', L10n::t('Notification for new posts'), ($contact['notify_new_posts'] == 1), L10n::t('Send a notification of every new post of this contact')],
				'$fetch_further_information' => $fetch_further_information,
				'$ffi_keyword_blacklist' => ['ffi_keyword_blacklist', L10n::t('Blacklisted keywords'), $contact['ffi_keyword_blacklist'], L10n::t('Comma separated list of keywords that should not be converted to hashtags, when "Fetch information and keywords" is selected')],
				'$photo'          => $contact['photo'],
				'$name'           => $contact['name'],
				'$dir_icon'       => $dir_icon,
				'$sparkle'        => $sparkle,
				'$url'            => $url,
				'$profileurllabel'=> L10n::t('Profile URL'),
				'$profileurl'     => $contact['url'],
				'$account_type'   => Model\Contact::getAccountType($contact),
				'$location'       => BBCode::convert($contact['location']),
				'$location_label' => L10n::t('Location:'),
				'$xmpp'           => BBCode::convert($contact['xmpp']),
				'$xmpp_label'     => L10n::t('XMPP:'),
				'$about'          => BBCode::convert($contact['about'], false),
				'$about_label'    => L10n::t('About:'),
				'$keywords'       => $contact['keywords'],
				'$keywords_label' => L10n::t('Tags:'),
				'$contact_action_button' => L10n::t('Actions'),
				'$contact_actions'=> $contact_actions,
				'$contact_status' => L10n::t('Status'),
				'$contact_settings_label' => $contact_settings_label,
				'$contact_profile_label' => L10n::t('Profile'),
			]);

			$arr = ['contact' => $contact, 'output' => $o];

			Hook::callAll('contact_edit', $arr);

			return $arr['output'];
		}

		$select_uid = local_user();

		// @TODO: Replace with parameter from router
		$type = $a->argv[1] ?? '';

		switch ($type) {
			case 'blocked':
				$sql_extra = sprintf(" AND EXISTS(SELECT `id` from `user-contact` WHERE `contact`.`id` = `user-contact`.`cid` and `user-contact`.`uid` = %d and `user-contact`.`blocked`)", intval(local_user()));
				$select_uid = 0;
				break;
			case 'hidden':
				$sql_extra = " AND `hidden` AND NOT `blocked` AND NOT `pending`";
				break;
			case 'ignored':
				$sql_extra = sprintf(" AND EXISTS(SELECT `id` from `user-contact` WHERE `contact`.`id` = `user-contact`.`cid` and `user-contact`.`uid` = %d and `user-contact`.`ignored`)", intval(local_user()));
				$select_uid = 0;
				break;
			case 'archived':
				$sql_extra = " AND `archive` AND NOT `blocked` AND NOT `pending`";
				break;
			case 'pending':
				$sql_extra = sprintf(" AND `pending` AND NOT `archive` AND ((`rel` = %d)
					OR EXISTS (SELECT `id` FROM `intro` WHERE `contact-id` = `contact`.`id` AND NOT `ignore`))", Model\Contact::SHARING);
				break;
			default:
				$sql_extra = " AND NOT `archive` AND NOT `blocked` AND NOT `pending`";
		}

		$sql_extra .= sprintf(" AND `network` != '%s' ", Protocol::PHANTOM);

		$search = Strings::escapeTags(trim($_GET['search'] ?? ''));
		$nets   = Strings::escapeTags(trim($_GET['nets']   ?? ''));
		$rel    = Strings::escapeTags(trim($_GET['rel']    ?? ''));

		$tabs = [
			[
				'label' => L10n::t('All Contacts'),
				'url'   => 'contact',
				'sel'   => !$type ? 'active' : '',
				'title' => L10n::t('Show all contacts'),
				'id'    => 'showall-tab',
				'accesskey' => 'l',
			],
			[
				'label' => L10n::t('Pending'),
				'url'   => 'contact/pending',
				'sel'   => $type == 'pending' ? 'active' : '',
				'title' => L10n::t('Only show pending contacts'),
				'id'    => 'showpending-tab',
				'accesskey' => 'p',
			],
			[
				'label' => L10n::t('Blocked'),
				'url'   => 'contact/blocked',
				'sel'   => $type == 'blocked' ? 'active' : '',
				'title' => L10n::t('Only show blocked contacts'),
				'id'    => 'showblocked-tab',
				'accesskey' => 'b',
			],
			[
				'label' => L10n::t('Ignored'),
				'url'   => 'contact/ignored',
				'sel'   => $type == 'ignored' ? 'active' : '',
				'title' => L10n::t('Only show ignored contacts'),
				'id'    => 'showignored-tab',
				'accesskey' => 'i',
			],
			[
				'label' => L10n::t('Archived'),
				'url'   => 'contact/archived',
				'sel'   => $type == 'archived' ? 'active' : '',
				'title' => L10n::t('Only show archived contacts'),
				'id'    => 'showarchived-tab',
				'accesskey' => 'y',
			],
			[
				'label' => L10n::t('Hidden'),
				'url'   => 'contact/hidden',
				'sel'   => $type == 'hidden' ? 'active' : '',
				'title' => L10n::t('Only show hidden contacts'),
				'id'    => 'showhidden-tab',
				'accesskey' => 'h',
			],
			[
				'label' => L10n::t('Groups'),
				'url'   => 'group',
				'sel'   => '',
				'title' => L10n::t('Organize your contact groups'),
				'id'    => 'contactgroups-tab',
				'accesskey' => 'e',
			],
		];

		$tab_tpl = Renderer::getMarkupTemplate('common_tabs.tpl');
		$t = Renderer::replaceMacros($tab_tpl, ['$tabs' => $tabs]);

		$total = 0;
		$searching = false;
		$search_hdr = null;
		if ($search) {
			$searching = true;
			$search_hdr = $search;
			$search_txt = DBA::escape(Strings::protectSprintf(preg_quote($search)));
			$sql_extra .= " AND (name REGEXP '$search_txt' OR url REGEXP '$search_txt'  OR nick REGEXP '$search_txt') ";
		}

		if ($nets) {
			$sql_extra .= sprintf(" AND network = '%s' ", DBA::escape($nets));
		}

		switch ($rel) {
			case 'followers': $sql_extra .= " AND `rel` IN (1, 3)"; break;
			case 'following': $sql_extra .= " AND `rel` IN (2, 3)"; break;
			case 'mutuals': $sql_extra .= " AND `rel` = 3"; break;
		}

		$sql_extra .=  " AND NOT `deleted` ";

		$sql_extra2 = ((($sort_type > 0) && ($sort_type <= Model\Contact::FRIEND)) ? sprintf(" AND `rel` = %d ", intval($sort_type)) : '');

		$sql_extra3 = Widget::unavailableNetworks();

		$r = q("SELECT COUNT(*) AS `total` FROM `contact`
			WHERE `uid` = %d AND `self` = 0 $sql_extra $sql_extra2 $sql_extra3",
			intval($select_uid)
		);
		if (DBA::isResult($r)) {
			$total = $r[0]['total'];
		}
		$pager = new Pager($a->query_string);

		$contacts = [];

		$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND `self` = 0 $sql_extra $sql_extra2 $sql_extra3 ORDER BY `name` ASC LIMIT %d , %d ",
			intval($select_uid),
			$pager->getStart(),
			$pager->getItemsPerPage()
		);
		if (DBA::isResult($r)) {
			foreach ($r as $rr) {
				$rr['blocked'] = Model\Contact::isBlockedByUser($rr['id'], local_user());
				$rr['readonly'] = Model\Contact::isIgnoredByUser($rr['id'], local_user());
				$contacts[] = self::getContactTemplateVars($rr);
			}
		}

		switch ($rel) {
			case 'followers': $header = L10n::t('Followers'); break;
			case 'following': $header = L10n::t('Following'); break;
			case 'mutuals':   $header = L10n::t('Mutual friends'); break;
			default:          $header = L10n::t('Contacts');
		}

		switch ($type) {
			case 'pending':	 $header .= ' - ' . L10n::t('Pending'); break;
			case 'blocked':	 $header .= ' - ' . L10n::t('Blocked'); break;
			case 'hidden':   $header .= ' - ' . L10n::t('Hidden'); break;
			case 'ignored':  $header .= ' - ' . L10n::t('Ignored'); break;
			case 'archived': $header .= ' - ' . L10n::t('Archived'); break;
		}

		$header .= $nets ? ' - ' . ContactSelector::networkToName($nets) : '';

		$tpl = Renderer::getMarkupTemplate('contacts-template.tpl');
		$o .= Renderer::replaceMacros($tpl, [
			'$header'     => $header,
			'$tabs'       => $t,
			'$total'      => $total,
			'$search'     => $search_hdr,
			'$desc'       => L10n::t('Search your contacts'),
			'$finding'    => $searching ? L10n::t('Results for: %s', $search) : '',
			'$submit'     => L10n::t('Find'),
			'$cmd'        => $a->cmd,
			'$contacts'   => $contacts,
			'$contact_drop_confirm' => L10n::t('Do you really want to delete this contact?'),
			'multiselect' => 1,
			'$batch_actions' => [
				'contacts_batch_update'  => L10n::t('Update'),
				'contacts_batch_block'   => L10n::t('Block') . '/' . L10n::t('Unblock'),
				'contacts_batch_ignore'  => L10n::t('Ignore') . '/' . L10n::t('Unignore'),
				'contacts_batch_archive' => L10n::t('Archive') . '/' . L10n::t('Unarchive'),
				'contacts_batch_drop'    => L10n::t('Delete'),
			],
			'$h_batch_actions' => L10n::t('Batch Actions'),
			'$paginate'   => $pager->renderFull($total),
		]);

		return $o;
	}

	/**
	 * @brief List of pages for the Contact TabBar
	 *
	 * Available Pages are 'Status', 'Profile', 'Contacts' and 'Common Friends'
	 *
	 * @param App   $a
	 * @param array $contact    The contact array
	 * @param int   $active_tab 1 if tab should be marked as active
	 *
	 * @return string HTML string of the contact page tabs buttons.
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function getTabsHTML($a, $contact, $active_tab)
	{
		// tabs
		$tabs = [
			[
				'label' => L10n::t('Status'),
				'url'   => "contact/" . $contact['id'] . "/conversations",
				'sel'   => (($active_tab == 1) ? 'active' : ''),
				'title' => L10n::t('Conversations started by this contact'),
				'id'    => 'status-tab',
				'accesskey' => 'm',
			],
			[
				'label' => L10n::t('Posts and Comments'),
				'url'   => "contact/" . $contact['id'] . "/posts",
				'sel'   => (($active_tab == 2) ? 'active' : ''),
				'title' => L10n::t('Status Messages and Posts'),
				'id'    => 'posts-tab',
				'accesskey' => 'p',
			],
			[
				'label' => L10n::t('Profile'),
				'url'   => "contact/" . $contact['id'],
				'sel'   => (($active_tab == 3) ? 'active' : ''),
				'title' => L10n::t('Profile Details'),
				'id'    => 'profile-tab',
				'accesskey' => 'o',
			]
		];

		// Show this tab only if there is visible friend list
		$x = Model\GContact::countAllFriends(local_user(), $contact['id']);
		if ($x) {
			$tabs[] = ['label' => L10n::t('Contacts'),
				'url'   => "allfriends/" . $contact['id'],
				'sel'   => (($active_tab == 4) ? 'active' : ''),
				'title' => L10n::t('View all contacts'),
				'id'    => 'allfriends-tab',
				'accesskey' => 't'];
		}

		// Show this tab only if there is visible common friend list
		$common = Model\GContact::countCommonFriends(local_user(), $contact['id']);
		if ($common) {
			$tabs[] = ['label' => L10n::t('Common Friends'),
				'url'   => "common/loc/" . local_user() . "/" . $contact['id'],
				'sel'   => (($active_tab == 5) ? 'active' : ''),
				'title' => L10n::t('View all common friends'),
				'id'    => 'common-loc-tab',
				'accesskey' => 'd'
			];
		}

		if (!empty($contact['uid'])) {
			$tabs[] = ['label' => L10n::t('Advanced'),
				'url'   => 'crepair/' . $contact['id'],
				'sel'   => (($active_tab == 6) ? 'active' : ''),
				'title' => L10n::t('Advanced Contact Settings'),
				'id'    => 'advanced-tab',
				'accesskey' => 'r'
			];
		}

		$tab_tpl = Renderer::getMarkupTemplate('common_tabs.tpl');
		$tab_str = Renderer::replaceMacros($tab_tpl, ['$tabs' => $tabs]);

		return $tab_str;
	}

	private static function getConversationsHMTL($a, $contact_id, $update)
	{
		$o = '';

		if (!$update) {
			// We need the editor here to be able to reshare an item.
			if (local_user()) {
				$x = [
					'is_owner' => true,
					'allow_location' => $a->user['allow_location'],
					'default_location' => $a->user['default-location'],
					'nickname' => $a->user['nickname'],
					'lockstate' => (is_array($a->user) && (strlen($a->user['allow_cid']) || strlen($a->user['allow_gid']) || strlen($a->user['deny_cid']) || strlen($a->user['deny_gid'])) ? 'lock' : 'unlock'),
					'acl' => ACL::getFullSelectorHTML($a->page, $a->user, true),
					'bang' => '',
					'visitor' => 'block',
					'profile_uid' => local_user(),
				];
				$o = status_editor($a, $x, 0, true);
			}
		}

		$contact = DBA::selectFirst('contact', ['uid', 'url', 'id'], ['id' => $contact_id, 'deleted' => false]);

		if (!$update) {
			$o .= self::getTabsHTML($a, $contact, 1);
		}

		if (DBA::isResult($contact)) {
			$a->page['aside'] = '';

			$profiledata = Model\Contact::getDetailsByURL($contact['url']);

			Model\Profile::load($a, '', 0, $profiledata, true);
			$o .= Model\Contact::getPostsFromUrl($contact['url'], true, $update);
		}

		return $o;
	}

	private static function getPostsHTML($a, $contact_id)
	{
		$contact = DBA::selectFirst('contact', ['uid', 'url', 'id'], ['id' => $contact_id, 'deleted' => false]);

		$o = self::getTabsHTML($a, $contact, 2);

		if (DBA::isResult($contact)) {
			$a->page['aside'] = '';

			$profiledata = Model\Contact::getDetailsByURL($contact['url']);

			if (local_user() && in_array($profiledata['network'], Protocol::FEDERATED)) {
				$profiledata['remoteconnect'] = System::baseUrl() . '/follow?url=' . urlencode($profiledata['url']);
			}

			Model\Profile::load($a, '', 0, $profiledata, true);
			$o .= Model\Contact::getPostsFromUrl($contact['url']);
		}

		return $o;
	}

	public static function getContactTemplateVars(array $rr)
	{
		$dir_icon = '';
		$alt_text = '';

		if (!empty($rr['uid']) && !empty($rr['rel'])) {
			switch ($rr['rel']) {
				case Model\Contact::FRIEND:
					$dir_icon = 'images/lrarrow.gif';
					$alt_text = L10n::t('Mutual Friendship');
					break;

				case Model\Contact::FOLLOWER;
					$dir_icon = 'images/larrow.gif';
					$alt_text = L10n::t('is a fan of yours');
					break;

				case Model\Contact::SHARING;
					$dir_icon = 'images/rarrow.gif';
					$alt_text = L10n::t('you are a fan of');
					break;

				default:
					break;
			}
		}

		$url = Model\Contact::magicLink($rr['url']);

		if (strpos($url, 'redir/') === 0) {
			$sparkle = ' class="sparkle" ';
		} else {
			$sparkle = '';
		}

		if ($rr['pending']) {
			if (in_array($rr['rel'], [Model\Contact::FRIEND, Model\Contact::SHARING])) {
				$alt_text = L10n::t('Pending outgoing contact request');
			} else {
				$alt_text = L10n::t('Pending incoming contact request');
			}
		}

		if ($rr['self']) {
			$dir_icon = 'images/larrow.gif';
			$alt_text = L10n::t('This is you');
			$url = $rr['url'];
			$sparkle = '';
		}

		return [
			'img_hover' => L10n::t('Visit %s\'s profile [%s]', $rr['name'], $rr['url']),
			'edit_hover'=> L10n::t('Edit contact'),
			'photo_menu'=> Model\Contact::photoMenu($rr),
			'id'        => $rr['id'],
			'alt_text'  => $alt_text,
			'dir_icon'  => $dir_icon,
			'thumb'     => ProxyUtils::proxifyUrl($rr['thumb'], false, ProxyUtils::SIZE_THUMB),
			'name'      => $rr['name'],
			'username'  => $rr['name'],
			'account_type' => Model\Contact::getAccountType($rr),
			'sparkle'   => $sparkle,
			'itemurl'   => ($rr['addr'] ?? '') ?: $rr['url'],
			'url'       => $url,
			'network'   => ContactSelector::networkToName($rr['network'], $rr['url']),
			'nick'      => $rr['nick'],
		];
	}

	/**
	 * @brief Gives a array with actions which can performed to a given contact
	 *
	 * This includes actions like e.g. 'block', 'hide', 'archive', 'delete' and others
	 *
	 * @param array $contact Data about the Contact
	 * @return array with contact related actions
	 */
	private static function getContactActions($contact)
	{
		$poll_enabled = in_array($contact['network'], [Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::OSTATUS, Protocol::FEED, Protocol::MAIL]);
		$contact_actions = [];

		// Provide friend suggestion only for Friendica contacts
		if ($contact['network'] === Protocol::DFRN) {
			$contact_actions['suggest'] = [
				'label' => L10n::t('Suggest friends'),
				'url'   => 'fsuggest/' . $contact['id'],
				'title' => '',
				'sel'   => '',
				'id'    => 'suggest',
			];
		}

		if ($poll_enabled) {
			$contact_actions['update'] = [
				'label' => L10n::t('Update now'),
				'url'   => 'contact/' . $contact['id'] . '/update',
				'title' => '',
				'sel'   => '',
				'id'    => 'update',
			];
		}

		$contact_actions['block'] = [
			'label' => (intval($contact['blocked']) ? L10n::t('Unblock') : L10n::t('Block')),
			'url'   => 'contact/' . $contact['id'] . '/block',
			'title' => L10n::t('Toggle Blocked status'),
			'sel'   => (intval($contact['blocked']) ? 'active' : ''),
			'id'    => 'toggle-block',
		];

		$contact_actions['ignore'] = [
			'label' => (intval($contact['readonly']) ? L10n::t('Unignore') : L10n::t('Ignore')),
			'url'   => 'contact/' . $contact['id'] . '/ignore',
			'title' => L10n::t('Toggle Ignored status'),
			'sel'   => (intval($contact['readonly']) ? 'active' : ''),
			'id'    => 'toggle-ignore',
		];

		if ($contact['uid'] != 0) {
			$contact_actions['archive'] = [
				'label' => (intval($contact['archive']) ? L10n::t('Unarchive') : L10n::t('Archive')),
				'url'   => 'contact/' . $contact['id'] . '/archive',
				'title' => L10n::t('Toggle Archive status'),
				'sel'   => (intval($contact['archive']) ? 'active' : ''),
				'id'    => 'toggle-archive',
			];

			$contact_actions['delete'] = [
				'label' => L10n::t('Delete'),
				'url'   => 'contact/' . $contact['id'] . '/drop',
				'title' => L10n::t('Delete contact'),
				'sel'   => '',
				'id'    => 'delete',
			];
		}

		return $contact_actions;
	}
}
