<?php

/**
 * @file src/Core/Acl.php
 */

namespace Friendica\Core;

use Friendica\App\Page;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Group;

/**
 * Handle ACL management and display
 *
 * @author Hypolite Petovan <hypolite@mrpetovan.com>
 */
class ACL
{
	/**
	 * Returns a select input tag with all the contact of the local user
	 *
	 * @param string $selname     Name attribute of the select input tag
	 * @param string $selclass    Class attribute of the select input tag
	 * @param array  $options     Available options:
	 *                            - size: length of the select box
	 *                            - mutual_friends: Only used for the hook
	 *                            - single: Only used for the hook
	 *                            - exclude: Only used for the hook
	 * @param array  $preselected Contact ID that should be already selected
	 * @return string
	 * @throws \Exception
	 */
	public static function getSuggestContactSelectHTML($selname, $selclass, array $options = [], array $preselected = [])
	{
		$a = DI::app();

		$networks = null;

		$size = ($options['size'] ?? 0) ?: 4;
		$mutual = !empty($options['mutual_friends']);
		$single = !empty($options['single']) && empty($options['multiple']);
		$exclude = $options['exclude'] ?? false;

		switch (($options['networks'] ?? '') ?: Protocol::PHANTOM) {
			case 'DFRN_ONLY':
				$networks = [Protocol::DFRN];
				break;

			case 'PRIVATE':
				$networks = [Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::MAIL, Protocol::DIASPORA];
				break;

			case 'TWO_WAY':
				if (!empty($a->user['prvnets'])) {
					$networks = [Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::MAIL, Protocol::DIASPORA];
				} else {
					$networks = [Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::MAIL, Protocol::DIASPORA, Protocol::OSTATUS];
				}
				break;

			default: /// @TODO Maybe log this call?
				break;
		}

		$x = ['options' => $options, 'size' => $size, 'single' => $single, 'mutual' => $mutual, 'exclude' => $exclude, 'networks' => $networks];

		Hook::callAll('contact_select_options', $x);

		$o = '';

		$sql_extra = '';

		if (!empty($x['mutual'])) {
			$sql_extra .= sprintf(" AND `rel` = %d ", intval(Contact::FRIEND));
		}

		if (!empty($x['exclude'])) {
			$sql_extra .= sprintf(" AND `id` != %d ", intval($x['exclude']));
		}

		if (!empty($x['networks'])) {
			/// @TODO rewrite to foreach()
			array_walk($x['networks'], function (&$value) {
				$value = "'" . DBA::escape($value) . "'";
			});
			$str_nets = implode(',', $x['networks']);
			$sql_extra .= " AND `network` IN ( $str_nets ) ";
		}

		$tabindex = (!empty($options['tabindex']) ? 'tabindex="' . $options["tabindex"] . '"' : '');

		if (!empty($x['single'])) {
			$o .= "<select name=\"$selname\" id=\"$selclass\" class=\"$selclass\" size=\"" . $x['size'] . "\" $tabindex >\r\n";
		} else {
			$o .= "<select name=\"{$selname}[]\" id=\"$selclass\" class=\"$selclass\" multiple=\"multiple\" size=\"" . $x['size'] . "$\" $tabindex >\r\n";
		}

		$stmt = DBA::p("SELECT `id`, `name`, `url`, `network` FROM `contact`
			WHERE `uid` = ? AND NOT `self` AND NOT `blocked` AND NOT `pending` AND NOT `archive` AND NOT `deleted` AND `notify` != ''
			$sql_extra
			ORDER BY `name` ASC ", intval(local_user())
		);

		$contacts = DBA::toArray($stmt);

		$arr = ['contact' => $contacts, 'entry' => $o];

		// e.g. 'network_pre_contact_deny', 'profile_pre_contact_allow'
		Hook::callAll($a->module . '_pre_' . $selname, $arr);

		if (DBA::isResult($contacts)) {
			foreach ($contacts as $contact) {
				if (in_array($contact['id'], $preselected)) {
					$selected = ' selected="selected" ';
				} else {
					$selected = '';
				}

				$trimmed = mb_substr($contact['name'], 0, 20);

				$o .= "<option value=\"{$contact['id']}\" $selected title=\"{$contact['name']}|{$contact['url']}\" >$trimmed</option>\r\n";
			}
		}

		$o .= '</select>' . PHP_EOL;

		Hook::callAll($a->module . '_post_' . $selname, $o);

		return $o;
	}

	/**
	 * Returns a select input tag with all the contact of the local user
	 *
	 * @param string $selname     Name attribute of the select input tag
	 * @param string $selclass    Class attribute of the select input tag
	 * @param array  $preselected Contact IDs that should be already selected
	 * @param int    $size        Length of the select box
	 * @param int    $tabindex    Select input tag tabindex attribute
	 * @return string
	 * @throws \Exception
	 */
	public static function getMessageContactSelectHTML($selname, $selclass, array $preselected = [], $size = 4, $tabindex = null)
	{
		$a = DI::app();

		$o = '';

		// When used for private messages, we limit correspondence to mutual DFRN/Friendica friends and the selector
		// to one recipient. By default our selector allows multiple selects amongst all contacts.
		$sql_extra = sprintf(" AND `rel` = %d ", intval(Contact::FRIEND));
		$sql_extra .= sprintf(" AND `network` IN ('%s' , '%s') ", Protocol::DFRN, Protocol::DIASPORA);

		$tabindex_attr = !empty($tabindex) ? ' tabindex="' . intval($tabindex) . '"' : '';

		$hidepreselected = '';
		if ($preselected) {
			$sql_extra .= " AND `id` IN (" . implode(",", $preselected) . ")";
			$hidepreselected = ' style="display: none;"';
		}

		$o .= "<select name=\"$selname\" id=\"$selclass\" class=\"$selclass\" size=\"$size\"$tabindex_attr$hidepreselected>\r\n";

		$stmt = DBA::p("SELECT `id`, `name`, `url`, `network` FROM `contact`
			WHERE `uid` = ? AND NOT `self` AND NOT `blocked` AND NOT `pending` AND NOT `archive` AND NOT `deleted` AND `notify` != ''
			$sql_extra
			ORDER BY `name` ASC ", intval(local_user())
		);

		$contacts = DBA::toArray($stmt);

		$arr = ['contact' => $contacts, 'entry' => $o];

		// e.g. 'network_pre_contact_deny', 'profile_pre_contact_allow'
		Hook::callAll($a->module . '_pre_' . $selname, $arr);

		$receiverlist = [];

		if (DBA::isResult($contacts)) {
			foreach ($contacts as $contact) {
				if (in_array($contact['id'], $preselected)) {
					$selected = ' selected="selected"';
				} else {
					$selected = '';
				}

				$trimmed = Protocol::formatMention($contact['url'], $contact['name']);

				$receiverlist[] = $trimmed;

				$o .= "<option value=\"{$contact['id']}\"$selected title=\"{$contact['name']}|{$contact['url']}\" >$trimmed</option>\r\n";
			}
		}

		$o .= '</select>' . PHP_EOL;

		if ($preselected) {
			$o .= implode(', ', $receiverlist);
		}

		Hook::callAll($a->module . '_post_' . $selname, $o);

		return $o;
	}

	private static function fixACL(&$item)
	{
		$item = intval(str_replace(['<', '>'], ['', ''], $item));
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
		$matches = [];

		$acl_regex = '/<([0-9]+)>/i';

		preg_match_all($acl_regex, $user['allow_cid'] ?? '', $matches);
		$allow_cid = $matches[1];
		preg_match_all($acl_regex, $user['allow_gid'] ?? '', $matches);
		$allow_gid = $matches[1];
		preg_match_all($acl_regex, $user['deny_cid'] ?? '', $matches);
		$deny_cid = $matches[1];
		preg_match_all($acl_regex, $user['deny_gid'] ?? '', $matches);
		$deny_gid = $matches[1];

		// Reformats the ACL data so that it is accepted by the JS frontend
		array_walk($allow_cid, 'self::fixACL');
		array_walk($allow_gid, 'self::fixACL');
		array_walk($deny_cid, 'self::fixACL');
		array_walk($deny_gid, 'self::fixACL');

		Contact::pruneUnavailable($allow_cid);

		return [
			'allow_cid' => $allow_cid,
			'allow_gid' => $allow_gid,
			'deny_cid' => $deny_cid,
			'deny_gid' => $deny_gid,
		];
	}

	/**
	 * Returns the ACL list of contacts for a given user id
	 *
	 * @param int $user_id
	 * @return array
	 * @throws \Exception
	 */
	public static function getContactListByUserId(int $user_id)
	{
		$fields = ['id', 'name', 'addr', 'micro'];
		$params = ['order' => ['name']];
		$acl_contacts = Contact::selectToArray($fields,
			['uid' => $user_id, 'self' => false, 'blocked' => false, 'archive' => false, 'deleted' => false,
			'pending' => false, 'rel' => [Contact::FOLLOWER, Contact::FRIEND]], $params
		);

		$acl_forums = Contact::selectToArray($fields,
			['uid' => $user_id, 'self' => false, 'blocked' => false, 'archive' => false, 'deleted' => false,
			'pending' => false, 'contact-type' => Contact::TYPE_COMMUNITY], $params
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
				'name' => L10n::t('Followers'),
				'addr' => '',
				'micro' => 'images/twopeople.png',
				'type' => 'group',
			],
			[
				'id' => Group::MUTUALS,
				'name' => L10n::t('Mutuals'),
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
	 * @param Page  $page
	 * @param array $user                User array
	 * @param bool  $for_federation
	 * @param array $default_permissions Static defaults permission array:
	 *                                   [
	 *                                      'allow_cid' => [],
	 *                                      'allow_gid' => [],
	 *                                      'deny_cid' => [],
	 *                                      'deny_gid' => [],
	 *                                      'hidewall' => true/false
	 *                                   ]
	 * @return string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function getFullSelectorHTML(Page $page, array $user = null, bool $for_federation = false, array $default_permissions = [])
	{
		if (empty($user['uid'])) {
			return '';
		}

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
			'hidewall'  => $default_permissions['hidewall']  ?? false,
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
			$mail_enabled = false;
			$pubmail_enabled = false;

			if (function_exists('imap_open') && !Config::get('system', 'imap_disabled')) {
				$mailacct = DBA::selectFirst('mailacct', ['pubmail'], ['`uid` = ? AND `server` != ""', $user['uid']]);
				if (DBA::isResult($mailacct)) {
					$mail_enabled = true;
					$pubmail_enabled = !empty($mailacct['pubmail']);
				}
			}

			if (!$default_permissions['hidewall']) {
				if ($mail_enabled) {
					$jotnets_fields[] = [
						'type' => 'checkbox',
						'field' => [
							'pubmail_enable',
							L10n::t('Post to Email'),
							$pubmail_enabled
						]
					];
				}

				Hook::callAll('jot_networks', $jotnets_fields);
			}
		}

		$acl_contacts = self::getContactListByUserId($user['uid']);

		$acl_groups = self::getGroupListByUserId($user['uid']);

		$acl_list = array_merge($acl_groups, $acl_contacts);

		$tpl = Renderer::getMarkupTemplate('acl_selector.tpl');
		$o = Renderer::replaceMacros($tpl, [
			'$public_title'   => L10n::t('Public'),
			'$public_desc'    => L10n::t('This content will be shown to all your followers and can be seen in the community pages and by anyone with its link.'),
			'$custom_title'   => L10n::t('Limited/Private'),
			'$custom_desc'    => L10n::t('This content will be shown only to the people in the first box, to the exception of the people mentioned in the second box. It won\'t appear anywhere public.'),
			'$allow_label'    => L10n::t('Show to:'),
			'$deny_label'     => L10n::t('Except to:'),
			'$emailcc'        => L10n::t('CC: email addresses'),
			'$emtitle'        => L10n::t('Example: bob@example.com, mary@example.com'),
			'$jotnets_summary' => L10n::t('Connectors'),
			'$jotnets_disabled_label' => L10n::t('Connectors disabled, since "%s" is enabled.', L10n::t('Hide your profile details from unknown viewers?')),
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
			'$user_hidewall'  => $default_permissions['hidewall'],
		]);

		return $o;
	}
}
