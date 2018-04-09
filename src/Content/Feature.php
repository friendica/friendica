<?php
/**
 * @file src/Content/Feature.php
 * @brief Features management
 */
namespace Friendica\Content;

use Friendica\Core\Addon;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\PConfig;

class Feature
{
	/**
	 * @brief check if feature is enabled
	 *
	 * @param integer $uid     user id
	 * @param string  $feature feature
	 * @return boolean
	 */
	public static function isEnabled($uid, $feature)
	{
		$x = Config::get('feature_lock', $feature, false);

		if ($x === false) {
			$x = PConfig::get($uid, 'feature', $feature, false);
		}

		if ($x === false) {
			$x = Config::get('feature', $feature, false);
		}

		if ($x === false) {
			$x = self::getDefault($feature);
		}

		$arr = ['uid' => $uid, 'feature' => $feature, 'enabled' => $x];
		Addon::callHooks('isEnabled', $arr);
		return($arr['enabled']);
	}

	/**
	 * @brief check if feature is enabled or disabled by default
	 *
	 * @param string $feature feature
	 * @return boolean
	 */
	private static function getDefault($feature)
	{
		$f = self::get();
		foreach ($f as $cat) {
			foreach ($cat as $feat) {
				if (is_array($feat) && $feat[0] === $feature) {
					return $feat[3];
				}
			}
		}
		return false;
	}

	/**
	 * @brief Get a list of all available features
	 *
	 * The array includes the setting group, the setting name,
	 * explainations for the setting and if it's enabled or disabled
	 * by default
	 *
	 * @param bool $filtered True removes any locked features
	 *
	 * @return array
	 */
	public static function get($filtered = true)
	{
		$arr = [

			// General
			'general' => [
				L10n::t('General Features'),
				//array('expire',         L10n::t('Content Expiration'),		L10n::t('Remove old posts/comments after a period of time')),
				['multi_profiles',  L10n::t('Multiple Profiles'),      L10n::t('Ability to create multiple profiles'), false, Config::get('feature_lock', 'multi_profiles', false)],
				['photo_location',  L10n::t('Photo Location'),         L10n::t("Photo metadata is normally stripped. This extracts the location \x28if present\x29 prior to stripping metadata and links it to a map."), false, Config::get('feature_lock', 'photo_location', false)],
				['export_calendar', L10n::t('Export Public Calendar'), L10n::t('Ability for visitors to download the public calendar'), false, Config::get('feature_lock', 'export_calendar', false)],
			],

			// Post composition
			'composition' => [
				L10n::t('Post Composition Features'),
				['preview',        L10n::t('Post Preview'),        L10n::t('Allow previewing posts and comments before publishing them'), false, Config::get('feature_lock', 'preview', false)],
				['aclautomention', L10n::t('Auto-mention Forums'), L10n::t('Add/remove mention when a forum page is selected/deselected in ACL window.'), false, Config::get('feature_lock', 'aclautomention', false)],
			],

			// Network sidebar widgets
			'widgets' => [
				L10n::t('Network Sidebar Widgets'),
				['archives',         L10n::t('Search by Date'), L10n::t('Ability to select posts by date ranges'), false, Config::get('feature_lock', 'archives', false)],
				['forumlist_widget', L10n::t('List Forums'),    L10n::t('Enable widget to display the forums your are connected with'), true, Config::get('feature_lock', 'forumlist_widget', false)],
				['groups',           L10n::t('Group Filter'),   L10n::t('Enable widget to display Network posts only from selected group'), false, Config::get('feature_lock', 'groups', false)],
				['networks',         L10n::t('Network Filter'), L10n::t('Enable widget to display Network posts only from selected network'), false, Config::get('feature_lock', 'networks', false)],
				['savedsearch',      L10n::t('Saved Searches'), L10n::t('Save search terms for re-use'), false, Config::get('feature_lock', 'savedsearch', false)],
			],

			// Network tabs
			'net_tabs' => [
				L10n::t('Network Tabs'),
				['personal_tab', L10n::t('Network Personal Tab'),     L10n::t('Enable tab to display only Network posts that you\'ve interacted on'), false, Config::get('feature_lock', 'personal_tab', false)],
				['new_tab',      L10n::t('Network New Tab'),          L10n::t("Enable tab to display only new Network posts \x28from the last 12 hours\x29"), false, Config::get('feature_lock', 'new_tab', false)],
				['link_tab',     L10n::t('Network Shared Links Tab'), L10n::t('Enable tab to display only Network posts with links in them'), false, Config::get('feature_lock', 'link_tab', false)],
			],

			// Item tools
			'tools' => [
				L10n::t('Post/Comment Tools'),
				['multi_delete', L10n::t('Multiple Deletion'),       L10n::t('Select and delete multiple posts/comments at once'), false, Config::get('feature_lock', 'multi_delete', false)],
				['edit_posts',   L10n::t('Edit Sent Posts'),         L10n::t('Edit and correct posts and comments after sending'), false, Config::get('feature_lock', 'edit_posts', false)],
				['commtag',      L10n::t('Tagging'),                 L10n::t('Ability to tag existing posts'), false, Config::get('feature_lock', 'commtag', false)],
				['categories',   L10n::t('Post Categories'),         L10n::t('Add categories to your posts'), false, Config::get('feature_lock', 'categories', false)],
				['filing',       L10n::t('Saved Folders'),           L10n::t('Ability to file posts under folders'), false, Config::get('feature_lock', 'filing', false)],
				['dislike',      L10n::t('Dislike Posts'),           L10n::t('Ability to dislike posts/comments'), false, Config::get('feature_lock', 'dislike', false)],
				['star_posts',   L10n::t('Star Posts'),              L10n::t('Ability to mark special posts with a star indicator'), false, Config::get('feature_lock', 'star_posts', false)],
				['ignore_posts', L10n::t('Mute Post Notifications'), L10n::t('Ability to mute notifications for a thread'), false, Config::get('feature_lock', 'ignore_posts', false)],
			],

			// Advanced Profile Settings
			'advanced_profile' => [
				L10n::t('Advanced Profile Settings'),
				['forumlist_profile',   L10n::t('List Forums'),             L10n::t('Show visitors public community forums at the Advanced Profile Page'), false, Config::get('feature_lock', 'forumlist_profile', false)],
				['tagadelic',           L10n::t('Tag Cloud'),               L10n::t('Provide a personal tag cloud on your profile page'), false, Config::get('feature_lock', 'tagadelic', false)],
				['profile_membersince', L10n::t('Display Membership Date'), L10n::t('Display membership date in profile'), false, Config::get('feature_lock', 'profile_membersince', false)],
			],
		];

		// removed any locked features and remove the entire category if this makes it empty

		if ($filtered) {
			foreach ($arr as $k => $x) {
				$has_items = false;
				$kquantity = count($arr[$k]);
				for ($y = 0; $y < $kquantity; $y ++) {
					if (is_array($arr[$k][$y])) {
						if ($arr[$k][$y][4] === false) {
							$has_items = true;
						} else {
							unset($arr[$k][$y]);
						}
					}
				}
				if (! $has_items) {
					unset($arr[$k]);
				}
			}
		}

		Addon::callHooks('get', $arr);
		return $arr;
	}
}
