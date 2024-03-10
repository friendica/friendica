<?php
/**
 * @copyright Copyright (C) 2010-2024, the Friendica project
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

namespace Friendica\Content;

use Friendica\Core\Hook;
use Friendica\DI;

class Feature
{
	const ACCOUNTS          = 'accounts';
	const ADD_ABSTRACT      = 'add_abstract';
	const ARCHIVE           = 'archive';
	const CATEGORIES        = 'categories';
	const CHANNELS          = 'channels';
	const CIRCLES           = 'circles';
	const COMMUNITY         = 'community';
	const EXPLICIT_MENTIONS = 'explicit_mentions';
	const FOLDERS           = 'folders';
	const GROUPS            = 'forumlist_profile';
	const MEMBER_SINCE      = 'profile_membersince';
	const NETWORKS          = 'networks';
	const NOSHARER          = 'nosharer';
	const PHOTO_LOCATION    = 'photo_location';
	const PUBLIC_CALENDAR   = 'public_calendar';
	const SEARCHES          = 'searches';
	const SUMMARY           = 'summary';
	const TAGCLOUD          = 'tagadelic';
	const TRENDING_TAGS     = 'trending_tags';

	/**
	 * check if feature is enabled
	 *
	 * @param integer $uid     user id
	 * @param string  $feature feature
	 * @return boolean
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function isEnabled(int $uid, $feature): bool
	{
		if (!DI::config()->get('feature_lock', $feature, false)) {
			$enabled = DI::config()->get('feature', $feature) ?? self::getDefault($feature);
			$enabled = DI::pConfig()->get($uid, 'feature', $feature) ?? $enabled;
		} else {
			$enabled = true;
		}

		$arr = ['uid' => $uid, 'feature' => $feature, 'enabled' => $enabled];
		Hook::callAll('isEnabled', $arr);
		return (bool)$arr['enabled'];
	}

	/**
	 * check if feature is enabled or disabled by default
	 *
	 * @param string $feature feature
	 * @return boolean
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function getDefault($feature)
	{
		foreach (self::get() as $cat) {
			foreach ($cat as $feat) {
				if (is_array($feat) && $feat[0] === $feature) {
					return $feat[3];
				}
			}
		}
		return false;
	}

	/**
	 * Get a list of all available features
	 *
	 * The array includes the setting group, the setting name,
	 * explanations for the setting and if it's enabled or disabled
	 * by default
	 *
	 * @param bool $filtered True removes any locked features
	 *
	 * @return array
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function get($filtered = true)
	{
		$arr = [

			// General
			'general' => [
				DI::l10n()->t('General Features'),
				//array('expire',         DI::l10n()->t('Content Expiration'),		DI::l10n()->t('Remove old posts/comments after a period of time')),
				[self::PHOTO_LOCATION, DI::l10n()->t('Photo Location'), DI::l10n()->t("Photo metadata is normally stripped. This extracts the location \x28if present\x29 prior to stripping metadata and links it to a map."), false, DI::config()->get('feature_lock', self::PHOTO_LOCATION, false)],
				[self::COMMUNITY, DI::l10n()->t('Display the community in the navigation'), DI::l10n()->t('If enabled, the community can be accessed via the navigation menu. Independent from this setting, the community timelines can always be accessed via the channels.'), true, DI::config()->get('feature_lock', self::COMMUNITY, false)],
			],

			// Post composition
			'composition' => [
				DI::l10n()->t('Post Composition Features'),
				[self::EXPLICIT_MENTIONS, DI::l10n()->t('Explicit Mentions'), DI::l10n()->t('Add explicit mentions to comment box for manual control over who gets mentioned in replies.'), false, DI::config()->get('feature_lock', Feature::EXPLICIT_MENTIONS, false)],
				[self::ADD_ABSTRACT,      DI::l10n()->t('Add an abstract from ActivityPub content warnings'), DI::l10n()->t('Add an abstract when commenting on ActivityPub posts with a content warning. Abstracts are displayed as content warning on systems like Mastodon or Pleroma.'), false, DI::config()->get('feature_lock', self::ADD_ABSTRACT, false)],
			],

			// Item tools
			'tools' => [
				DI::l10n()->t('Post/Comment Tools'),
				[self::CATEGORIES, DI::l10n()->t('Post Categories'), DI::l10n()->t('Add categories to your posts'), false, DI::config()->get('feature_lock', self::CATEGORIES, false)],
				[self::SUMMARY,    DI::l10n()->t('Summary'),         DI::l10n()->t('Add a summary, abstract or spoiler text to your posts'), false, DI::config()->get('feature_lock', self::SUMMARY, false)],
			],

			// Widget visibility on the network stream
			'network' => [
				DI::l10n()->t('Network Widgets'),
				[self::CIRCLES, DI::l10n()->t('Circles'), DI::l10n()->t('Display posts that have been created by accounts of the selected circle.'), true, DI::config()->get('feature_lock', self::CIRCLES, false)],
				[self::GROUPS, DI::l10n()->t('Groups'), DI::l10n()->t('Display posts that have been distributed by the selected group.'), true, DI::config()->get('feature_lock', self::GROUPS, false)],
				[self::ARCHIVE, DI::l10n()->t('Archives'), DI::l10n()->t('Display an archive where posts can be selected by month and year.'), true, DI::config()->get('feature_lock', self::ARCHIVE, false)],
				[self::NETWORKS, DI::l10n()->t('Protocols'), DI::l10n()->t('Display posts with the selected protocols.'), true, DI::config()->get('feature_lock', self::NETWORKS, false)],
				[self::ACCOUNTS, DI::l10n()->t('Account Types'), DI::l10n()->t('Display posts done by accounts with the selected account type.'), true, DI::config()->get('feature_lock', self::ACCOUNTS, false)],
				[self::CHANNELS, DI::l10n()->t('Channels'), DI::l10n()->t('Display posts in the system channels and user defined channels.'), true, DI::config()->get('feature_lock', self::CHANNELS, false)],
				[self::SEARCHES, DI::l10n()->t('Saved Searches'), DI::l10n()->t('Display posts that contain subscribed hashtags.'), true, DI::config()->get('feature_lock', self::SEARCHES, false)],
				[self::FOLDERS, DI::l10n()->t('Saved Folders'), DI::l10n()->t('Display a list of folders in which posts are stored.'), true, DI::config()->get('feature_lock', self::FOLDERS, false)],
				[self::NOSHARER, DI::l10n()->t('Own Contacts'), DI::l10n()->t('Include or exclude posts from subscribed accounts. This widget is not visible on all channels.'), true, DI::config()->get('feature_lock', self::NOSHARER, false)],
				[self::TRENDING_TAGS,  DI::l10n()->t('Trending Tags'), DI::l10n()->t('Display a list of the most popular tags in recent public posts.'), false, DI::config()->get('feature_lock', self::TRENDING_TAGS, false)],
			],

			// Advanced Profile Settings
			'advanced_profile' => [
				DI::l10n()->t('Advanced Profile Settings'),
				[self::TAGCLOUD,     DI::l10n()->t('Tag Cloud'),               DI::l10n()->t('Provide a personal tag cloud on your profile page'), false, DI::config()->get('feature_lock', self::TAGCLOUD, false)],
				[self::MEMBER_SINCE, DI::l10n()->t('Display Membership Date'), DI::l10n()->t('Display membership date in profile'), false, DI::config()->get('feature_lock', self::MEMBER_SINCE, false)],
			],

			//Advanced Calendar Settings
			'advanced_calendar' => [
				DI::l10n()->t('Advanced Calendar Settings'),
				[self::PUBLIC_CALENDAR, DI::l10n()->t('Allow anonymous access to your calendar'), DI::l10n()->t('Allows anonymous visitors to consult your calendar and your public events. Contact birthday events are private to you.'), false, DI::config()->get('feature_lock', self::PUBLIC_CALENDAR, false)],
			]
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

		Hook::callAll('get', $arr);
		return $arr;
	}
}
