<?php

/**
 * @file include/features.php * 
 * @brief Features management
 */

/**
 * @brief check if feature is enabled
 * 
 * return boolean
 */
function feature_enabled($uid,$feature) {
	//return true;

	$x = get_pconfig($uid,'feature',$feature);
	if($x === false) {
		$x = get_config('feature',$feature);
		if($x === false)
			$x = get_feature_default($feature);
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
	foreach($f as $cat) {
		foreach($cat as $feat) {
			if(is_array($feat) && $feat[0] === $feature)
				return $feat[3];
		}
	}
	return false;
}

/**
 * @ brief get a list of all available features
 * The array includes the setting group, the setting name,
 * explainations for the setting and if it's enabled or disabled
 * by default
 * 
 * @return array
 */
function get_features() {

	$arr = array(

		// General
		'general' => array(
			t('General Features'),
			//array('expire',         t('Content Expiration'),		t('Remove old posts/comments after a period of time')),
			array('multi_profiles', t('Multiple Profiles'),			t('Ability to create multiple profiles'),false),
			array('photo_location', t('Photo Location'),			t('Photo metadata is normally stripped. This extracts the location (if present) prior to stripping metadata and links it to a map.'),false),
		),

		// Post composition
		'composition' => array(
			t('Post Composition Features'),
			array('richtext',	t('Richtext Editor'),			t('Enable richtext editor'),false),
			array('preview',	t('Post Preview'),			t('Allow previewing posts and comments before publishing them'),false),
			array('aclautomention',	t('Auto-mention Forums'),		t('Add/remove mention when a fourm page is selected/deselected in ACL window.'),false),
		),

		// Network sidebar widgets
		'widgets' => array(
			t('Network Sidebar Widgets'),
			array('archives',	t('Search by Date'),			t('Ability to select posts by date ranges'),false),
			array('forumlist_widget', t('List Forums'),			t('Enable widget to display the forums your are connected with'),true),
			array('groups',		t('Group Filter'),			t('Enable widget to display Network posts only from selected group'),false),
			array('networks',	t('Network Filter'),			t('Enable widget to display Network posts only from selected network'),false),
			array('savedsearch',	t('Saved Searches'),			t('Save search terms for re-use'),false),
		),

		// Network tabs
		'net_tabs' => array(
			t('Network Tabs'),
			array('personal_tab',	t('Network Personal Tab'),		t('Enable tab to display only Network posts that you\'ve interacted on'),false),
			array('new_tab',	t('Network New Tab'),			t('Enable tab to display only new Network posts (from the last 12 hours)'),false),
			array('link_tab',	t('Network Shared Links Tab'),		t('Enable tab to display only Network posts with links in them'),false),
		),

		// Item tools
		'tools' => array(
			t('Post/Comment Tools'),
			array('multi_delete',	t('Multiple Deletion'),			t('Select and delete multiple posts/comments at once'),false),
			array('edit_posts',	t('Edit Sent Posts'),			t('Edit and correct posts and comments after sending'),false),
			array('commtag',	t('Tagging'),				t('Ability to tag existing posts'),false),
			array('categories',	t('Post Categories'),			t('Add categories to your posts'),false),
			array('filing',		t('Saved Folders'),			t('Ability to file posts under folders'),false),
			array('dislike',	t('Dislike Posts'),			t('Ability to dislike posts/comments')),
			array('star_posts',	t('Star Posts'),			t('Ability to mark special posts with a star indicator'),false),
			array('ignore_posts',	t('Mute Post Notifications'),		t('Ability to mute notifications for a thread'),false),
		),

		// Advanced Profile Settings
		'advanced_profile' => array(
			t('Advanced Profile Settings'),
			array('forumlist_profile', t('List Forums'),			t('Show visitors public community forums at the Advanced Profile Page'),false),
		),
	);

	call_hooks('get_features',$arr);
	return $arr;
}
