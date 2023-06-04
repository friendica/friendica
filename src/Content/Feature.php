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

namespace Friendica\Content;

use Friendica\Core\Hook;
use Friendica\DI;

class Feature
{
	/**
	 * check if feature is enabled
	 *
	 * @param integer $uid     user id
	 * @param string  $feature feature
	 * @return boolean
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function isEnabled(int $uid, $feature)
	{
		$x = DI::config()->get('feature_lock', $feature, false);

		if ($x === false) {
			$x = DI::pConfig()->get($uid, 'feature', $feature, false);
		}

		if ($x === false) {
			$x = DI::config()->get('feature', $feature, false);
		}

		if ($x === false) {
			$x = self::getDefault($feature);
		}

		$arr = ['uid' => $uid, 'feature' => $feature, 'enabled' => $x];
		Hook::callAll('isEnabled', $arr);
		return($arr['enabled']);
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
				['photo_location',  DI::l10n()->t('Photo Location'),         DI::l10n()->t("Photo metadata is normally stripped. This extracts the location \x28if present\x29 prior to stripping metadata and links it to a map."), false, DI::config()->get('feature_lock', 'photo_location', false)],
				['trending_tags',   DI::l10n()->t('Trending Tags'),          DI::l10n()->t('Show a community page widget with a list of the most popular tags in recent public posts.'), false, DI::config()->get('feature_lock', 'trending_tags', false)],
			],

			// Post composition
			'composition' => [
				DI::l10n()->t('Post Composition Features'),
				['aclautomention', DI::l10n()->t('Auto-mention Groups'), DI::l10n()->t('Add/remove mention when a group page is selected/deselected in ACL window.'), false, DI::config()->get('feature_lock', 'aclautomention', false)],
				['explicit_mentions', DI::l10n()->t('Explicit Mentions'), DI::l10n()->t('Add explicit mentions to comment box for manual control over who gets mentioned in replies.'), false, DI::config()->get('feature_lock', 'explicit_mentions', false)],
				['add_abstract', DI::l10n()->t('Add an abstract from ActivityPub content warnings'), DI::l10n()->t('Add an abstract when commenting on ActivityPub posts with a content warning. Abstracts are displayed as content warning on systems like Mastodon or Pleroma.'), false, DI::config()->get('feature_lock', 'add_abstract', false)],
			],

			// Item tools
			'tools' => [
				DI::l10n()->t('Post/Comment Tools'),
				['categories',   DI::l10n()->t('Post Categories'),         DI::l10n()->t('Add categories to your posts'), false, DI::config()->get('feature_lock', 'categories', false)],
			],

			// Advanced Profile Settings
			'advanced_profile' => [
				DI::l10n()->t('Advanced Profile Settings'),
				['forumlist_profile',   DI::l10n()->t('List Groups'),             DI::l10n()->t('Show visitors public groups at the Advanced Profile Page'), false, DI::config()->get('feature_lock', 'forumlist_profile', false)],
				['tagadelic',           DI::l10n()->t('Tag Cloud'),               DI::l10n()->t('Provide a personal tag cloud on your profile page'), false, DI::config()->get('feature_lock', 'tagadelic', false)],
				['profile_membersince', DI::l10n()->t('Display Membership Date'), DI::l10n()->t('Display membership date in profile'), false, DI::config()->get('feature_lock', 'profile_membersince', false)],
			],

			//Advanced Calendar Settings
			'advanced_calendar' => [
				DI::l10n()->t('Advanced Calendar Settings'),
				['public_calendar',     DI::l10n()->t('Allow anonymous access to your calendar'), DI::l10n()->t('Allows anonymous visitors to consult your calendar and your public events. Contact birthday events are private to you.'), false, DI::config()->get('feature_lock', 'public_calendar', false)],
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
