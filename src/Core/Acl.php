<?php

/**
 * @file src/Core/Acl.php
 */

namespace Friendica\Core;

use dba;
use Friendica\BaseObject;
use Friendica\Content\Feature;
use Friendica\Database\DBM;
use Friendica\Model\Contact;
use Friendica\Model\GContact;
use Friendica\Util\Network;
use const CONTACT_IS_FRIEND;
use const NETWORK_DFRN;
use const NETWORK_DIASPORA;
use const NETWORK_FACEBOOK;
use const NETWORK_MAIL;
use const NETWORK_OSTATUS;
use const PHP_EOL;
use function dbesc;
use function defaults;
use function get_markup_template;
use function get_server;
use function local_user;
use function remote_user;
use function replace_macros;

/**
 * Handle ACL management and display
 *
 * @author Hypolite Petovan <mrpetovan@gmail.com>
 */
class Acl extends BaseObject
{
	/**
	 * Returns a select input tag with all the contact of the local user
	 *
	 * @param string $selname Name attribute of the select input tag
	 * @param string $selclass Class attribute of the select input tag
	 * @param array $options Available options:
	 * - size: length of the select box
	 * - mutual_friends: Only used for the hook
	 * - single: Only used for the hook
	 * - exclude: Only used for the hook
	 * @param array $preselected Contact ID that should be already selected
	 * @return string
	 */
	public static function getSuggestContactSelectHTML($selname, $selclass, array $options = [], array $preselected = [])
	{
		$a = self::getApp();

		$networks = null;

		$size = defaults($options, 'size', 4);
		$mutual = !empty($options['mutual_friends']);
		$single = !empty($options['single']) && empty($options['multiple']);
		$exclude = defaults($options, 'exclude', false);

		switch (defaults($options, 'networks', Protocol::PHANTOM)) {
			case 'DFRN_ONLY':
				$networks = [NETWORK_DFRN];
				break;
			case 'PRIVATE':
				if (!empty($a->user['prvnets'])) {
					$networks = [NETWORK_DFRN, NETWORK_MAIL, NETWORK_DIASPORA];
				} else {
					$networks = [NETWORK_DFRN, NETWORK_FACEBOOK, NETWORK_MAIL, NETWORK_DIASPORA];
				}
				break;
			case 'TWO_WAY':
				if (!empty($a->user['prvnets'])) {
					$networks = [NETWORK_DFRN, NETWORK_MAIL, NETWORK_DIASPORA];
				} else {
					$networks = [NETWORK_DFRN, NETWORK_FACEBOOK, NETWORK_MAIL, NETWORK_DIASPORA, NETWORK_OSTATUS];
				}
				break;
			default: /// @TODO Maybe log this call?
				break;
		}

		$x = ['options' => $options, 'size' => $size, 'single' => $single, 'mutual' => $mutual, 'exclude' => $exclude, 'networks' => $networks];

		Addon::callHooks('contact_select_options', $x);

		$o = '';

		$sql_extra = '';

		if (!empty($x['mutual'])) {
			$sql_extra .= sprintf(" AND `rel` = %d ", intval(CONTACT_IS_FRIEND));
		}

		if (!empty($x['exclude'])) {
			$sql_extra .= sprintf(" AND `id` != %d ", intval($x['exclude']));
		}

		if (!empty($x['networks'])) {
			/// @TODO rewrite to foreach()
			array_walk($x['networks'], function (&$value) {
				$value = "'" . dbesc($value) . "'";
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

		$stmt = dba::p("SELECT `id`, `name`, `url`, `network` FROM `contact`
			WHERE `uid` = %d AND NOT `self` AND NOT `blocked` AND NOT `pending` AND NOT `archive` AND `notify` != ''
			$sql_extra
			ORDER BY `name` ASC ", intval(local_user())
		);

		$contacts = dba::inArray($stmt);

		$arr = ['contact' => $contacts, 'entry' => $o];

		// e.g. 'network_pre_contact_deny', 'profile_pre_contact_allow'
		Addon::callHooks($a->module . '_pre_' . $selname, $arr);

		if (DBM::is_result($contacts)) {
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

		Addon::callHooks($a->module . '_post_' . $selname, $o);

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
	 */
	public static function getMessageContactSelectHTML($selname, $selclass, array $preselected = [], $size = 4, $tabindex = null)
	{
		$a = self::getApp();

		$o = '';

		// When used for private messages, we limit correspondence to mutual DFRN/Friendica friends and the selector
		// to one recipient. By default our selector allows multiple selects amongst all contacts.
		$sql_extra = sprintf(" AND `rel` = %d ", intval(CONTACT_IS_FRIEND));
		$sql_extra .= sprintf(" AND `network` IN ('%s' , '%s') ", NETWORK_DFRN, NETWORK_DIASPORA);

		$tabindex_attr = !empty($tabindex) ? ' tabindex="' . intval($tabindex) . '"' : '';

		$hidepreselected = '';
		if ($preselected) {
			$sql_extra .= " AND `id` IN (" . implode(",", $preselected) . ")";
			$hidepreselected = ' style="display: none;"';
		}

		$o .= "<select name=\"$selname\" id=\"$selclass\" class=\"$selclass\" size=\"$size\"$tabindex_attr$hidepreselected>\r\n";

		$stmt = dba::p("SELECT `id`, `name`, `url`, `network` FROM `contact`
			WHERE `uid` = %d AND NOT `self` AND NOT `blocked` AND NOT `pending` AND NOT `archive` AND `notify` != ''
			$sql_extra
			ORDER BY `name` ASC ", intval(local_user())
		);

		$contacts = dba::inArray($stmt);

		$arr = ['contact' => $contacts, 'entry' => $o];

		// e.g. 'network_pre_contact_deny', 'profile_pre_contact_allow'
		Addon::callHooks($a->module . '_pre_' . $selname, $arr);

		$receiverlist = [];

		if (DBM::is_result($contacts)) {
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

		Addon::callHooks($a->module . '_post_' . $selname, $o);

		return $o;
	}

	/**
	 * Return the default permission of the provided user array
	 *
	 * @param array $user
	 * @return array Hash of contact id lists
	 */
	public static function getDefaultUserPermissions(array $user = null)
	{
		$matches = [];

		$acl_regex = '/<([0-9]+)>/i';

		preg_match_all($acl_regex, defaults($user, 'allow_cid', ''), $matches);
		$allow_cid = $matches[1];
		preg_match_all($acl_regex, defaults($user, 'allow_gid', ''), $matches);
		$allow_gid = $matches[1];
		preg_match_all($acl_regex, defaults($user, 'deny_cid', ''), $matches);
		$deny_cid = $matches[1];
		preg_match_all($acl_regex, defaults($user, 'deny_gid', ''), $matches);
		$deny_gid = $matches[1];

		Contact::pruneUnavailable($allow_cid);

		return [
			'allow_cid' => $allow_cid,
			'allow_gid' => $allow_gid,
			'deny_cid' => $deny_cid,
			'deny_gid' => $deny_gid,
		];
	}

	/**
	 * Return the full jot ACL selector HTML
	 *
	 * @param array $user
	 * @param bool  $show_jotnets
	 * @return string
	 */
	public static function getFullSelectorHTML(array $user = null, $show_jotnets = false)
	{
		$perms = self::getDefaultUserPermissions($user);

		$jotnets = '';
		if ($show_jotnets) {
			$imap_disabled = !function_exists('imap_open') || Config::get('system', 'imap_disabled');

			$mail_enabled = false;
			$pubmail_enabled = false;

			if (!$imap_disabled) {
				$mailacct = dba::selectFirst('mailacct', ['pubmail'], ['`uid` = ? AND `server` != ""', local_user()]);
				if (DBM::is_result($mailacct)) {
					$mail_enabled = true;
					$pubmail_enabled = !empty($mailacct['pubmail']);
				}
			}

			if (empty($user['hidewall'])) {
				if ($mail_enabled) {
					$selected = $pubmail_enabled ? ' checked="checked"' : '';
					$jotnets .= '<div class="profile-jot-net"><input type="checkbox" name="pubmail_enable"' . $selected . ' value="1" /> ' . L10n::t("Post to Email") . '</div>';
				}

				Addon::callHooks('jot_networks', $jotnets);
			} else {
				$jotnets .= L10n::t('Connectors disabled, since "%s" is enabled.',
						L10n::t('Hide your profile details from unknown viewers?'));
			}
		}

		$tpl = get_markup_template('acl_selector.tpl');
		$o = replace_macros($tpl, [
			'$showall' => L10n::t('Visible to everybody'),
			'$show' => L10n::t('show'),
			'$hide' => L10n::t('don\'t show'),
			'$allowcid' => json_encode($perms['allow_cid']),
			'$allowgid' => json_encode($perms['allow_gid']),
			'$denycid' => json_encode($perms['deny_cid']),
			'$denygid' => json_encode($perms['deny_gid']),
			'$networks' => $show_jotnets,
			'$emailcc' => L10n::t('CC: email addresses'),
			'$emtitle' => L10n::t('Example: bob@example.com, mary@example.com'),
			'$jotnets' => $jotnets,
			'$aclModalTitle' => L10n::t('Permissions'),
			'$aclModalDismiss' => L10n::t('Close'),
			'$features' => [
				'aclautomention' => Feature::isEnabled($user['uid'], 'aclautomention') ? 'true' : 'false'
			],
		]);

		return $o;
	}

	/**
	 * Searching for global contacts for autocompletion
	 *
	 * @brief Searching for global contacts for autocompletion
	 * @param string $search Name or part of a name or nick
	 * @param string $mode   Search mode (e.g. "community")
	 * @return array with the search results
	 */
	public static function contactAutocomplete($search, $mode)
	{
		if ((Config::get('system', 'block_public')) && (!local_user()) && (!remote_user())) {
			return [];
		}

		// don't search if search term has less than 2 characters
		if (!$search || mb_strlen($search) < 2) {
			return [];
		}

		if (substr($search, 0, 1) === '@') {
			$search = substr($search, 1);
		}

		// check if searching in the local global contact table is enabled
		if (Config::get('system', 'poco_local_search')) {
			$return = GContact::searchByName($search, $mode);
		} else {
			$a = self::getApp();
			$p = $a->pager['page'] != 1 ? '&p=' . $a->pager['page'] : '';

			$response = Network::curl(get_server() . '/lsearch?f=' . $p . '&search=' . urlencode($search));
			if ($response['success']) {
				$lsearch = json_decode($response['body'], true);
				if (!empty($lsearch['results'])) {
					$return = $lsearch['results'];
				}
			}
		}

		return defaults($return, []);
	}
}
