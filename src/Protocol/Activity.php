<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Protocol;

/**
 * Base class for the Activity Verbs
 */
final class Activity
{
	/**
	 * Indicates that the actor marked the object as an item of special interest.
	 *
	 * @see http://activitystrea.ms/head/activity-schema.html#verbs
	 * @var string
	 */
	public const LIKE = ActivityNamespace::ACTIVITY_SCHEMA . 'like';
	/**
	 * Dislike a message ("I don't like the post")
	 *
	 * @see http://purl.org/macgirvin/dfrn/1.0/dislike
	 * @var string
	 */
	public const DISLIKE = ActivityNamespace::DFRN . '/dislike';

	/**
	 * Attend an event
	 *
	 * @see https://github.com/friendica/friendica/wiki/ActivityStreams#activity_attend
	 * @var string
	 */
	public const ATTEND = ActivityNamespace::ZOT . '/activity/attendyes';
	/**
	 * Don't attend an event
	 *
	 * @see https://github.com/friendica/friendica/wiki/ActivityStreams#activity_attendno
	 * @var string
	 */
	public const ATTENDNO = ActivityNamespace::ZOT . '/activity/attendno';
	/**
	 * Attend maybe an event
	 *
	 * @see https://github.com/friendica/friendica/wiki/ActivityStreams#activity_attendmaybe
	 * @var string
	 */
	public const ATTENDMAYBE = ActivityNamespace::ZOT . '/activity/attendmaybe';

	/**
	 * Indicates the creation of a friendship that is reciprocated by the object.
	 *
	 * @see http://activitystrea.ms/head/activity-schema.html#verbs
	 * @var string
	 */
	public const FRIEND = ActivityNamespace::ACTIVITY_SCHEMA . 'make-friend';
	/**
	 * Indicates the creation of a friendship that has not yet been reciprocated by the object.
	 *
	 * @see http://activitystrea.ms/head/activity-schema.html#verbs
	 * @var string
	 */
	public const REQ_FRIEND = ActivityNamespace::ACTIVITY_SCHEMA . 'request-friend';
	/**
	 * Indicates that the actor has removed the object from the collection of friends.
	 *
	 * @see http://activitystrea.ms/head/activity-schema.html#verbs
	 * @var string
	 */
	public const UNFRIEND = ActivityNamespace::ACTIVITY_SCHEMA . 'remove-friend';
	/**
	 * Indicates that the actor began following the activity of the object.
	 *
	 * @see http://activitystrea.ms/head/activity-schema.html#verbs
	 * @var string
	 */
	public const FOLLOW = ActivityNamespace::ACTIVITY_SCHEMA . 'follow';
	/**
	 * Indicates that the actor has stopped following the object.
	 *
	 * @see http://activitystrea.ms/head/activity-schema.html#verbs
	 * @var string
	 */
	public const UNFOLLOW = ActivityNamespace::ACTIVITY_SCHEMA . 'stop-following';
	/**
	 * Indicates that the actor has become a member of the object.
	 *
	 * @see http://activitystrea.ms/head/activity-schema.html#verbs
	 * @var string
	 */
	public const JOIN = ActivityNamespace::ACTIVITY_SCHEMA . 'join';
	/**
	 * Implementors SHOULD use verbs such as post where the actor is adding new items to a collection or similar.
	 *
	 * @see http://activitystrea.ms/head/activity-schema.html#verbs
	 * @var string
	 */
	public const POST = ActivityNamespace::ACTIVITY_SCHEMA . 'post';
	/**
	 * The "update" verb indicates that the actor has modified the object.
	 *
	 * @see http://activitystrea.ms/head/activity-schema.html#verbs
	 * @var string
	 */
	public const UPDATE = ActivityNamespace::ACTIVITY_SCHEMA . 'update';
	/**
	 * Indicates that the actor has identified the presence of a target inside another object.
	 *
	 * @see http://activitystrea.ms/head/activity-schema.html#verbs
	 * @var string
	 */
	public const TAG = ActivityNamespace::ACTIVITY_SCHEMA . 'tag';
	/**
	 * Indicates that the actor marked the object as an item of special interest.
	 *
	 * @see http://activitystrea.ms/head/activity-schema.html#verbs
	 * @var string
	 */
	public const FAVORITE = ActivityNamespace::ACTIVITY_SCHEMA . 'favorite';
	/**
	 * Indicates that the actor has removed the object from the collection of favorited items.
	 *
	 * @see http://activitystrea.ms/head/activity-schema.html#verbs
	 * @var string
	 */
	public const UNFAVORITE = ActivityNamespace::ACTIVITY_SCHEMA . 'unfavorite';
	/**
	 * Indicates that the actor has called out the object to readers.
	 *
	 * @see http://activitystrea.ms/head/activity-schema.html#verbs
	 * @var string
	 */
	public const SHARE = ActivityNamespace::ACTIVITY_SCHEMA . 'share';
	/**
	 * Indicates that the actor has deleted the object.
	 *
	 * @see http://activitystrea.ms/head/activity-schema.html#verbs
	 * @var string
	 */
	public const DELETE = ActivityNamespace::ACTIVITY_SCHEMA . 'delete';
	/**
	 * Indicates that the actor is calling the target's attention the object.
	 *
	 * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-announce
	 * @var string
	 */
	public const ANNOUNCE = ActivityNamespace::ACTIVITY2 . 'Announce';
	/**
	 * Indicates that the actor has read the object.
	 *
	 * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-read
	 * @var string
	 */
	public const READ = ActivityNamespace::ACTIVITY2 . 'Read';
	/**
	 *  Indicates that the actor has listened to the object.
	 *
	 * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-listen
	 * @var string
	 */
	public const LISTEN = ActivityNamespace::ACTIVITY2 . 'Listen';
	/**
	 * Indicates that the actor has viewed the object.
	 *
	 * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-view
	 * @var string
	 */
	public const VIEW = ActivityNamespace::ACTIVITY2 . 'View';

	public const O_UNFOLLOW    = ActivityNamespace::OSTATUS . '/unfollow';
	public const O_UNFAVOURITE = ActivityNamespace::OSTATUS . '/unfavorite';

	/**
	 * React to a post via an emoji
	 *
	 * @var string
	 */
	public const EMOJIREACT = ActivityNamespace::LITEPUB . '/emojireact';

	/**
	 * likes (etc.) can apply to other things besides posts. Check if they are post children,
	 * in which case we handle them specially
	 *
	 * Hidden activities, which doesn't need to be shown
	 */
	public const HIDDEN_ACTIVITIES = [
		self::LIKE, self::DISLIKE,
		self::ATTEND, self::ATTENDNO, self::ATTENDMAYBE,
		self::FOLLOW,
		self::ANNOUNCE,
		self::EMOJIREACT,
		self::VIEW,
		self::READ,
	];

	/**
	 * Technical activities, which are usually not considered as content interactions
	 */
	public const TECHNICAL_ACTIVITIES = [
		self::FOLLOW,
		self::VIEW,
		self::READ,
	];

	/**
	 * Checks if the given activity is a hidden activity
	 *
	 * @param string $activity The current activity
	 *
	 * @return bool True, if the activity is hidden
	 */
	public function isHidden(string $activity): bool
	{
		foreach (self::HIDDEN_ACTIVITIES as $hiddenActivity) {
			if ($this->match($activity, $hiddenActivity)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Compare activity uri. Knows about activity namespace.
	 *
	 * @param string $haystack
	 * @param string $needle
	 *
	 * @return boolean
	 */
	public function match(string $haystack, string $needle): bool
	{
		return (($haystack === $needle)
				|| ((basename($needle) === $haystack)
				 && strstr($needle, ActivityNamespace::ACTIVITY_SCHEMA)));
	}
}
