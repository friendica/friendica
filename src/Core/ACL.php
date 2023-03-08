<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
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

namespace Friendica\Core;

use Friendica\App\Page;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Group;
use Friendica\Model\User;

/**
 * Handle ACL management and display
 */
class ACL
{
	/**
	 * Returns the default lock state for the given user id
	 * @param int $uid
	 * @return bool "true" if the default settings are non public
	 */
	public static function getLockstateForUserId(int $uid)
	{
		$user = User::getById($uid, ['allow_cid', 'allow_gid', 'deny_cid', 'deny_gid']);
		return !empty($user['allow_cid']) || !empty($user['allow_gid']) || !empty($user['deny_cid']) || !empty($user['deny_gid']);
	}

	/**
	 * Returns a select input tag for private message recipient
	 *
	 * @param int  $selected Existing recipien contact ID
	 * @return string
	 * @throws \Exception
	 */
	public static function getMessageContactSelectHTML(int $selected = null): string
	{
		$o = '';

		$page = DI::page();

		$page->registerFooterScript(Theme::getPathForFile('asset/typeahead.js/dist/typeahead.bundle.js'));
		$page->registerFooterScript(Theme::getPathForFile('js/friendica-tagsinput/friendica-tagsinput.js'));
		$page->registerStylesheet(Theme::getPathForFile('js/friendica-tagsinput/friendica-tagsinput.css'));
		$page->registerStylesheet(Theme::getPathForFile('js/friendica-tagsinput/friendica-tagsinput-typeahead.css'));

		$contacts = self::getValidMessageRecipientsForUser(DI::userSession()->getLocalUserId());

		$tpl = Renderer::getMarkupTemplate('acl/message_recipient.tpl');
		$o = Renderer::replaceMacros($tpl, [
			'$contacts' => $contacts,
			'$selected' => $selected,
		]);

		Hook::callAll(DI::args()->getModuleName() . '_post_recipient', $o);

		return $o;
	}

	public static function getValidMessageRecipientsForUser(int $uid): array
	{
		$condition = [
			'uid'     => $uid,
			'self'    => false,
			'blocked' => false,
			'pending' => false,
			'archive' => false,
			'deleted' => false,
			'rel'     => [Contact::FOLLOWER, Contact::SHARING, Contact::FRIEND],
			'network' => Protocol::SUPPORT_PRIVATE,
		];

		return Contact::selectToArray(
			['id', 'name', 'addr', 'micro', 'url', 'nick'],
			DBA::mergeConditions($condition, ["`notify` != ''"])
		);
	}

	/**
	 * Returns a minimal ACL block for self-only permissions
	 *
	 * @param int    $localUserId
	 * @param string $explanation
	 * @return string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function getSelfOnlyHTML(int $localUserId, string $explanation)
	{
		$selfPublicContactId = Contact::getPublicIdByUserId($localUserId);

		$tpl = Renderer::getMarkupTemplate('acl/self_only.tpl');
		$o = Renderer::replaceMacros($tpl, [
			'$selfPublicContactId' => $selfPublicContactId,
			'$explanation' => $explanation,
		]);

		return $o;
	}

	/**
	 * Return the default permission of the provided user array
	 *
	 * @param array $user
	 * @return array Hash of contact id lists
	 * @throws \Exception
	 */
	public static function getDefaultUserPermissions(array $user = null)
	{
		$aclFormatter = DI::aclFormatter();

		return [
			'allow_cid' => Contact::pruneUnavailable($aclFormatter->expand($user['allow_cid'] ?? '')),
			'allow_gid' => $aclFormatter->expand($user['allow_gid'] ?? ''),
			'deny_cid'  => $aclFormatter->expand($user['deny_cid']  ?? ''),
			'deny_gid'  => $aclFormatter->expand($user['deny_gid']  ?? ''),
		];
	}

	/**
	 * Returns the ACL list of contacts for a given user id
	 *
	 * @param int   $user_id
	 * @param array $condition Additional contact lookup table conditions
	 * @return array
	 * @throws \Exception
	 */
	public static function getContactListByUserId(int $user_id, array $condition = [])
	{
		$fields = ['id', 'name', 'addr', 'micro'];
		$params = ['order' => ['name']];
		$acl_contacts = Contact::selectToArray(
			$fields,
			array_merge([
				'uid' => $user_id,
				'self' => false,
				'blocked' => false,
				'archive' => false,
				'deleted' => false,
				'pending' => false,
				'network' => Protocol::FEDERATED,
				'rel' => [Contact::FOLLOWER, Contact::FRIEND]
			], $condition),
			$params
		);

		$acl_yourself = Contact::selectFirst($fields, ['uid' => $user_id, 'self' => true]);
		$acl_yourself['name'] = DI::l10n()->t('Yourself');

		$acl_contacts[] = $acl_yourself;

		$acl_forums = Contact::selectToArray($fields,
			['uid' => $user_id, 'self' => false, 'blocked' => false, 'archive' => false, 'deleted' => false,
			'network' => Protocol::FEDERATED, 'pending' => false, 'contact-type' => Contact::TYPE_COMMUNITY], $params
		);

		$acl_contacts = array_merge($acl_forums, $acl_contacts);

		array_walk($acl_contacts, function (&$value) {
			$value['type'] = 'contact';
		});

		return $acl_contacts;
	}

	/**
	 * Returns the ACL list of groups (including meta-groups) for a given user id
	 *
	 * @param int $user_id
	 * @return array
	 */
	public static function getGroupListByUserId(int $user_id)
	{
		$acl_groups = [
			[
				'id' => Group::FOLLOWERS,
				'name' => DI::l10n()->t('Followers'),
				'addr' => '',
				'micro' => 'images/twopeople.png',
				'type' => 'group',
			],
			[
				'id' => Group::MUTUALS,
				'name' => DI::l10n()->t('Mutuals'),
				'addr' => '',
				'micro' => 'images/twopeople.png',
				'type' => 'group',
			]
		];
		foreach (Group::getByUserId($user_id) as $group) {
			$acl_groups[] = [
				'id' => $group['id'],
				'name' => $group['name'],
				'addr' => '',
				'micro' => 'images/twopeople.png',
				'type' => 'group',
			];
		}

		return $acl_groups;
	}

	/**
	 * Return the full jot ACL selector HTML
	 *
	 * @param Page   $page
	 * @param int    $uid                   User ID
	 * @param bool   $for_federation
	 * @param array  $default_permissions   Static defaults permission array:
	 *                                      [
	 *                                      'allow_cid' => [],
	 *                                      'allow_gid' => [],
	 *                                      'deny_cid' => [],
	 *                                      'deny_gid' => []
	 *                                      ]
	 * @param array  $condition
	 * @param string $form_prefix
	 * @return string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function getFullSelectorHTML(
		Page $page,
		int $uid = null,
		bool $for_federation = false,
		array $default_permissions = [],
		array $condition = [],
		$form_prefix = ''
	) {
		if (empty($uid)) {
			return '';
		}

		static $input_group_id = 0;

		$user = User::getById($uid);

		$input_group_id++;

		$page->registerFooterScript(Theme::getPathForFile('asset/typeahead.js/dist/typeahead.bundle.js'));
		$page->registerFooterScript(Theme::getPathForFile('js/friendica-tagsinput/friendica-tagsinput.js'));
		$page->registerStylesheet(Theme::getPathForFile('js/friendica-tagsinput/friendica-tagsinput.css'));
		$page->registerStylesheet(Theme::getPathForFile('js/friendica-tagsinput/friendica-tagsinput-typeahead.css'));

		// Defaults user permissions
		if (empty($default_permissions)) {
			$default_permissions = self::getDefaultUserPermissions($user);
		}

		$default_permissions = [
			'allow_cid' => $default_permissions['allow_cid'] ?? [],
			'allow_gid' => $default_permissions['allow_gid'] ?? [],
			'deny_cid'  => $default_permissions['deny_cid']  ?? [],
			'deny_gid'  => $default_permissions['deny_gid']  ?? [],
		];

		if (count($default_permissions['allow_cid'])
			+ count($default_permissions['allow_gid'])
			+ count($default_permissions['deny_cid'])
			+ count($default_permissions['deny_gid'])) {
			$visibility = 'custom';
		} else {
			$visibility = 'public';
			// Default permission display for custom panel
			$default_permissions['allow_gid'] = [Group::FOLLOWERS];
		}

		$jotnets_fields = [];
		if ($for_federation) {
			if (function_exists('imap_open') && !DI::config()->get('system', 'imap_disabled')) {
				$mailacct = DBA::selectFirst('mailacct', ['pubmail'], ['`uid` = ? AND `server` != ""', $user['uid']]);
				if (DBA::isResult($mailacct)) {
					$jotnets_fields[] = [
						'type' => 'checkbox',
						'field' => [
							'pubmail_enable',
							DI::l10n()->t('Post to Email'),
							!empty($mailacct['pubmail'])
						]
					];

				}
			}
			Hook::callAll('jot_networks', $jotnets_fields);
		}

		$acl_contacts = self::getContactListByUserId($user['uid'], $condition);

		$acl_groups = self::getGroupListByUserId($user['uid']);

		$acl_list = array_merge($acl_groups, $acl_contacts);

		$input_names = [
			'visibility'    => $form_prefix ? $form_prefix . '[visibility]'    : 'visibility',
			'group_allow'   => $form_prefix ? $form_prefix . '[group_allow]'   : 'group_allow',
			'contact_allow' => $form_prefix ? $form_prefix . '[contact_allow]' : 'contact_allow',
			'group_deny'    => $form_prefix ? $form_prefix . '[group_deny]'    : 'group_deny',
			'contact_deny'  => $form_prefix ? $form_prefix . '[contact_deny]'  : 'contact_deny',
			'emailcc'       => $form_prefix ? $form_prefix . '[emailcc]'       : 'emailcc',
		];

		$tpl = Renderer::getMarkupTemplate('acl/full_selector.tpl');
		$o = Renderer::replaceMacros($tpl, [
			'$public_title'   => DI::l10n()->t('Public'),
			'$public_desc'    => DI::l10n()->t('This content will be shown to all your followers and can be seen in the community pages and by anyone with its link.'),
			'$custom_title'   => DI::l10n()->t('Limited/Private'),
			'$custom_desc'    => DI::l10n()->t('This content will be shown only to the people in the first box, to the exception of the people mentioned in the second box. It won\'t appear anywhere public.') . DI::l10n()->t('Start typing the name of a contact or a group to show a filtered list. You can also mention the special groups "Followers" and "Mutuals".'),
			'$allow_label'    => DI::l10n()->t('Show to:'),
			'$deny_label'     => DI::l10n()->t('Except to:'),
			'$emailcc'        => DI::l10n()->t('CC: email addresses'),
			'$emtitle'        => DI::l10n()->t('Example: bob@example.com, mary@example.com'),
			'$jotnets_summary' => DI::l10n()->t('Connectors'),
			'$visibility'     => $visibility,
			'$acl_contacts'   => $acl_contacts,
			'$acl_groups'     => $acl_groups,
			'$acl_list'       => $acl_list,
			'$contact_allow'  => implode(',', $default_permissions['allow_cid']),
			'$group_allow'    => implode(',', $default_permissions['allow_gid']),
			'$contact_deny'   => implode(',', $default_permissions['deny_cid']),
			'$group_deny'     => implode(',', $default_permissions['deny_gid']),
			'$for_federation' => $for_federation,
			'$jotnets_fields' => $jotnets_fields,
			'$input_names'    => $input_names,
			'$input_group_id' => $input_group_id,
		]);

		return $o;
	}

	/**
	 * Checks the validity of the given ACL string
	 *
	 * @param string $acl_string
	 * @param int    $uid
	 * @return bool
	 * @throws Exception
	 */
	public static function isValidContact($acl_string, $uid)
	{
		if (empty($acl_string)) {
			return true;
		}

		// split <x><y><z> into array of cids
		preg_match_all('/<[A-Za-z0-9]+>/', $acl_string, $array);

		// check for each cid if the contact is valid for the given user
		$cid_array = $array[0];
		foreach ($cid_array as $cid) {
			$cid = str_replace(['<', '>'], ['', ''], $cid);
			if (!DBA::exists('contact', ['id' => $cid, 'uid' => $uid])) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Checks the validity of the given ACL string
	 *
	 * @param string $acl_string
	 * @param int    $uid
	 * @return bool
	 * @throws Exception
	 */
	public static function isValidGroup($acl_string, $uid)
	{
		if (empty($acl_string)) {
			return true;
		}

		// split <x><y><z> into array of cids
		preg_match_all('/<[A-Za-z0-9]+>/', $acl_string, $array);

		// check for each cid if the contact is valid for the given user
		$gid_array = $array[0];
		foreach ($gid_array as $gid) {
			$gid = str_replace(['<', '>'], ['', ''], $gid);
			if (!DBA::exists('group', ['id' => $gid, 'uid' => $uid, 'deleted' => false])) {
				return false;
			}
		}

		return true;
	}
}
