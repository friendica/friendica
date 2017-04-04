<?php

/**
 * @file include/features.php
 * @brief Features management
 */

/**
 * @brief check if feature is enabled
 *
 * @return boolean
 */
function feature_enabled($uid, $feature) {
	$x = get_config('feature_lock', $feature);

	if ($x === false) {
		$x = get_pconfig($uid, 'feature', $feature);
		if ($x === false) {
			$x = get_config('feature', $feature);
			if ($x === false) {
				$x = get_feature_default($feature);
			}
		}
	}

	$arr = array('uid' => $uid, 'feature' => $feature, 'enabled' => $x);
	call_hooks('feature_enabled',$arr);
	return($arr['enabled']);
}

/**
 * @brief check if feature is enabled or disabled by default
 *
 * @param string $feature
 * @return boolean
 */
function get_feature_default($feature) {
	$f = get_features();
	foreach ($f as $cat) {
		foreach ($cat as $feat) {
			if (is_array($feat) && $feat[0] === $feature)
				return $feat[3];
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
function get_features($filtered = true) {

	$arr = array(

		// General
		'general' => array(
			t('General Features'),
			//array('expire',         t('Content Expiration'),		t('Remove old posts/comments after a period of time')),
			array('multi_profiles', t('Multiple Profiles'),			t('Ability to create multiple profiles'), false, get_config('feature_lock','multi_profiles')),
			array('photo_location', t('Photo Location'),			t('Photo metadata is normally stripped. This extracts the location (if present) prior to stripping metadata and links it to a map.'), false, get_config('feature_lock','photo_location')),
			array('export_calendar', t('Export Public Calendar'),		t('Ability for visitors to download the public calendar'), false, get_config('feature_lock','export_calendar')),
		),

		// Post composition
		'composition' => array(
			t('Post Composition Features'),
			array('preview',	t('Post Preview'),			t('Allow previewing posts and comments before publishing them'), false, get_config('feature_lock','preview')),
			array('aclautomention',	t('Auto-mention Forums'),		t('Add/remove mention when a forum page is selected/deselected in ACL window.'), false, get_config('feature_lock','aclautomention')),
		),

		// Network sidebar widgets
		'widgets' => array(
			t('Network Sidebar Widgets'),
			array('archives',	t('Search by Date'),			t('Ability to select posts by date ranges'), false, get_config('feature_lock','archives')),
			array('forumlist_widget', t('List Forums'),			t('Enable widget to display the forums your are connected with'), true, get_config('feature_lock','forumlist_widget')),
			array('groups',		t('Group Filter'),			t('Enable widget to display Network posts only from selected group'), false, get_config('feature_lock','groups')),
			array('networks',	t('Network Filter'),			t('Enable widget to display Network posts only from selected network'), false, get_config('feature_lock','networks')),
			array('savedsearch',	t('Saved Searches'),			t('Save search terms for re-use'), false, get_config('feature_lock','savedsearch')),
		),

		// Network tabs
		'net_tabs' => array(
			t('Network Tabs'),
			array('personal_tab',	t('Network Personal Tab'),		t('Enable tab to display only Network posts that you\'ve interacted on'), false, get_config('feature_lock','personal_tab')),
			array('new_tab',	t('Network New Tab'),			t('Enable tab to display only new Network posts (from the last 12 hours)'), false, get_config('feature_lock','new_tab')),
			array('link_tab',	t('Network Shared Links Tab'),		t('Enable tab to display only Network posts with links in them'), false, get_config('feature_lock','link_tab')),
		),

		// Item tools
		'tools' => array(
			t('Post/Comment Tools'),
			array('multi_delete',	t('Multiple Deletion'),			t('Select and delete multiple posts/comments at once'), false, get_config('feature_lock','multi_delete')),
			array('edit_posts',	t('Edit Sent Posts'),			t('Edit and correct posts and comments after sending'), false, get_config('feature_lock','edit_posts')),
			array('commtag',	t('Tagging'),				t('Ability to tag existing posts'), false, get_config('feature_lock','commtag')),
			array('categories',	t('Post Categories'),			t('Add categories to your posts'), false, get_config('feature_lock','categories')),
			array('filing',		t('Saved Folders'),			t('Ability to file posts under folders'), false, get_config('feature_lock','filing')),
			array('dislike',	t('Dislike Posts'),			t('Ability to dislike posts/comments'), false, get_config('feature_lock','dislike')),
			array('star_posts',	t('Star Posts'),			t('Ability to mark special posts with a star indicator'), false, get_config('feature_lock','star_posts')),
			array('ignore_posts',	t('Mute Post Notifications'),		t('Ability to mute notifications for a thread'), false, get_config('feature_lock','ignore_posts')),
		),

		// Advanced Profile Settings
		'advanced_profile' => array(
			t('Advanced Profile Settings'),
			array('forumlist_profile', t('List Forums'),			t('Show visitors public community forums at the Advanced Profile Page'), false, get_config('feature_lock','forumlist_profile')),
		),
	);

	// removed any locked features and remove the entire category if this makes it empty

	if ($filtered) {
		foreach ($arr as $k => $x) {
			$has_items = false;
			$kquantity = count($arr[$k]);
			for ($y = 0; $y < $kquantity; $y ++) {
				if (is_array($arr[$k][$y])) {
					if ($arr[$k][$y][4] === false) {
						$has_items = true;
					}
					else {
						unset($arr[$k][$y]);
					}
				}
			}
			if (! $has_items) {
				unset($arr[$k]);
			}
		}
	}

	call_hooks('get_features',$arr);
	return $arr;
}
