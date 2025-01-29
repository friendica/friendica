<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Content;

use Friendica\Core\Hook;
use Friendica\DI;
use Friendica\Event\ArrayFilterEvent;

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
		$config          = DI::config();
		$pConfig         = DI::pConfig();
		$eventDispatcher = DI::eventDispatcher();

		if (!$config->get('feature_lock', $feature, false)) {
			$enabled = $config->get('feature', $feature) ?? self::getDefault($feature);
			$enabled = $pConfig->get($uid, 'feature', $feature) ?? $enabled;
		} else {
			$enabled = true;
		}

		$arr = ['uid' => $uid, 'feature' => $feature, 'enabled' => $enabled];

		$arr = $eventDispatcher->dispatch(
			new ArrayFilterEvent(ArrayFilterEvent::FEATURE_ENABLED, $arr)
		)->getArray();

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
		$l10n   = DI::l10n();
		$config = DI::config();

		$arr = [
			// General
			'general' => [
				$l10n->t('General Features'),
				//array('expire', $l10n->t('Content Expiration'), $l10n->t('Remove old posts/comments after a period of time')),
				[self::PHOTO_LOCATION, $l10n->t('Photo Location'), $l10n->t("Photo metadata is normally stripped. This extracts the location \x28if present\x29 prior to stripping metadata and links it to a map."), false, $config->get('feature_lock', self::PHOTO_LOCATION, false)],
				[self::COMMUNITY, $l10n->t('Display the community in the navigation'), $l10n->t('If enabled, the community can be accessed via the navigation menu. Independent from this setting, the community timelines can always be accessed via the channels.'), true, $config->get('feature_lock', self::COMMUNITY, false)],
			],

			// Post composition
			'composition' => [
				$l10n->t('Post Composition Features'),
				[self::EXPLICIT_MENTIONS, $l10n->t('Explicit Mentions'), $l10n->t('Add explicit mentions to comment box for manual control over who gets mentioned in replies.'), false, $config->get('feature_lock', Feature::EXPLICIT_MENTIONS, false)],
				[self::ADD_ABSTRACT,      $l10n->t('Add an abstract from ActivityPub content warnings'), $l10n->t('Add an abstract when commenting on ActivityPub posts with a content warning. Abstracts are displayed as content warning on systems like Mastodon or Pleroma.'), false, $config->get('feature_lock', self::ADD_ABSTRACT, false)],
			],

			// Item tools
			'tools' => [
				$l10n->t('Post/Comment Tools'),
				[self::CATEGORIES, $l10n->t('Post Categories'),         $l10n->t('Add categories to your posts'), false, $config->get('feature_lock', self::CATEGORIES, false)],
			],

			// Widget visibility on the network stream
			'network' => [
				$l10n->t('Network Widgets'),
				[self::CIRCLES, $l10n->t('Circles'), $l10n->t('Display posts that have been created by accounts of the selected circle.'), true, $config->get('feature_lock', self::CIRCLES, false)],
				[self::GROUPS, $l10n->t('Groups'), $l10n->t('Display posts that have been distributed by the selected group.'), true, $config->get('feature_lock', self::GROUPS, false)],
				[self::ARCHIVE, $l10n->t('Archives'), $l10n->t('Display an archive where posts can be selected by month and year.'), true, $config->get('feature_lock', self::ARCHIVE, false)],
				[self::NETWORKS, $l10n->t('Protocols'), $l10n->t('Display posts with the selected protocols.'), true, $config->get('feature_lock', self::NETWORKS, false)],
				[self::ACCOUNTS, $l10n->t('Account Types'), $l10n->t('Display posts done by accounts with the selected account type.'), true, $config->get('feature_lock', self::ACCOUNTS, false)],
				[self::CHANNELS, $l10n->t('Channels'), $l10n->t('Display posts in the system channels and user defined channels.'), true, $config->get('feature_lock', self::CHANNELS, false)],
				[self::SEARCHES, $l10n->t('Saved Searches'), $l10n->t('Display posts that contain subscribed hashtags.'), true, $config->get('feature_lock', self::SEARCHES, false)],
				[self::FOLDERS, $l10n->t('Saved Folders'), $l10n->t('Display a list of folders in which posts are stored.'), true, $config->get('feature_lock', self::FOLDERS, false)],
				[self::NOSHARER, $l10n->t('Own Contacts'), $l10n->t('Include or exclude posts from subscribed accounts. This widget is not visible on all channels.'), true, $config->get('feature_lock', self::NOSHARER, false)],
				[self::TRENDING_TAGS,  $l10n->t('Trending Tags'), $l10n->t('Display a list of the most popular tags in recent public posts.'), false, $config->get('feature_lock', self::TRENDING_TAGS, false)],
			],

			// Advanced Profile Settings
			'advanced_profile' => [
				$l10n->t('Advanced Profile Settings'),
				[self::TAGCLOUD,     $l10n->t('Tag Cloud'),               $l10n->t('Provide a personal tag cloud on your profile page'), false, $config->get('feature_lock', self::TAGCLOUD, false)],
				[self::MEMBER_SINCE, $l10n->t('Display Membership Date'), $l10n->t('Display membership date in profile'), false, $config->get('feature_lock', self::MEMBER_SINCE, false)],
			],

			//Advanced Calendar Settings
			'advanced_calendar' => [
				$l10n->t('Advanced Calendar Settings'),
				[self::PUBLIC_CALENDAR, $l10n->t('Allow anonymous access to your calendar'), $l10n->t('Allows anonymous visitors to consult your calendar and your public events. Contact birthday events are private to you.'), false, $config->get('feature_lock', self::PUBLIC_CALENDAR, false)],
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
