<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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

namespace Friendica\Protocol;

use Friendica\Protocol\ActivityNamespace;

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
	const LIKE = ActivityNamespace::ACTIVITY_SCHEMA . 'like';
	/**
	 * Dislike a message ("I don't like the post")
	 *
	 * @see http://purl.org/macgirvin/dfrn/1.0/dislike
	 * @var string
	 */
	const DISLIKE = ActivityNamespace::DFRN . '/dislike';

	/**
	 * Attend an event
	 *
	 * @see https://github.com/friendica/friendica/wiki/ActivityStreams#activity_attend
	 * @var string
	 */
	const ATTEND      = ActivityNamespace::ZOT . '/activity/attendyes';
	/**
	 * Don't attend an event
	 *
	 * @see https://github.com/friendica/friendica/wiki/ActivityStreams#activity_attendno
	 * @var string
	 */
	const ATTENDNO    = ActivityNamespace::ZOT . '/activity/attendno';
	/**
	 * Attend maybe an event
	 *
	 * @see https://github.com/friendica/friendica/wiki/ActivityStreams#activity_attendmaybe
	 * @var string
	 */
	const ATTENDMAYBE = ActivityNamespace::ZOT . '/activity/attendmaybe';

	/**
	 * Indicates the creation of a friendship that is reciprocated by the object.
	 *
	 * @see http://activitystrea.ms/head/activity-schema.html#verbs
	 * @var string
	 */
	const FRIEND      = ActivityNamespace::ACTIVITY_SCHEMA . 'make-friend';
	/**
	 * Indicates the creation of a friendship that has not yet been reciprocated by the object.
	 *
	 * @see http://activitystrea.ms/head/activity-schema.html#verbs
	 * @var string
	 */
	const REQ_FRIEND = ActivityNamespace::ACTIVITY_SCHEMA . 'request-friend';
	/**
	 * Indicates that the actor has removed the object from the collection of friends.
	 *
	 * @see http://activitystrea.ms/head/activity-schema.html#verbs
	 * @var string
	 */
	const UNFRIEND   = ActivityNamespace::ACTIVITY_SCHEMA . 'remove-friend';
	/**
	 * Indicates that the actor began following the activity of the object.
	 *
	 * @see http://activitystrea.ms/head/activity-schema.html#verbs
	 * @var string
	 */
	const FOLLOW     = ActivityNamespace::ACTIVITY_SCHEMA . 'follow';
	/**
	 * Indicates that the actor has stopped following the object.
	 *
	 * @see http://activitystrea.ms/head/activity-schema.html#verbs
	 * @var string
	 */
	const UNFOLLOW   = ActivityNamespace::ACTIVITY_SCHEMA . 'stop-following';
	/**
	 * Indicates that the actor has become a member of the object.
	 *
	 * @see http://activitystrea.ms/head/activity-schema.html#verbs
	 * @var string
	 */
	const JOIN       = ActivityNamespace::ACTIVITY_SCHEMA . 'join';
	/**
	 * Implementors SHOULD use verbs such as post where the actor is adding new items to a collection or similar.
	 *
	 * @see http://activitystrea.ms/head/activity-schema.html#verbs
	 * @var string
	 */
	const POST       = ActivityNamespace::ACTIVITY_SCHEMA . 'post';
	/**
	 * The "update" verb indicates that the actor has modified the object.
	 *
	 * @see http://activitystrea.ms/head/activity-schema.html#verbs
	 * @var string
	 */
	const UPDATE     = ActivityNamespace::ACTIVITY_SCHEMA . 'update';
	/**
	 * Indicates that the actor has identified the presence of a target inside another object.
	 *
	 * @see http://activitystrea.ms/head/activity-schema.html#verbs
	 * @var string
	 */
	const TAG        = ActivityNamespace::ACTIVITY_SCHEMA . 'tag';
	/**
	 * Indicates that the actor marked the object as an item of special interest.
	 *
	 * @see http://activitystrea.ms/head/activity-schema.html#verbs
	 * @var string
	 */
	const FAVORITE   = ActivityNamespace::ACTIVITY_SCHEMA . 'favorite';
	/**
	 * Indicates that the actor has removed the object from the collection of favorited items.
	 *
	 * @see http://activitystrea.ms/head/activity-schema.html#verbs
	 * @var string
	 */
	const UNFAVORITE = ActivityNamespace::ACTIVITY_SCHEMA . 'unfavorite';
	/**
	 * Indicates that the actor has called out the object to readers.
	 *
	 * @see http://activitystrea.ms/head/activity-schema.html#verbs
	 * @var string
	 */
	const SHARE      = ActivityNamespace::ACTIVITY_SCHEMA . 'share';
	/**
	 * Indicates that the actor has deleted the object.
	 *
	 * @see http://activitystrea.ms/head/activity-schema.html#verbs
	 * @var string
	 */
	const DELETE     = ActivityNamespace::ACTIVITY_SCHEMA . 'delete';
	/**
	 * Indicates that the actor is calling the target's attention the object.
	 *
	 * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-announce
	 * @var string
	 */
	const ANNOUNCE   = ActivityNamespace::ACTIVITY2 . 'Announce';

	/**
	 * Pokes an user.
	 *
	 * @see https://github.com/friendica/friendica/wiki/ActivityStreams#activity_poke
	 * @var string
	 */
	const POKE       = ActivityNamespace::ZOT . '/activity/poke';


	const O_UNFOLLOW    = ActivityNamespace::OSTATUS . '/unfollow';
	const O_UNFAVOURITE = ActivityNamespace::OSTATUS . '/unfavorite';

	/**
	 * likes (etc.) can apply to other things besides posts. Check if they are post children,
	 * in which case we handle them specially
	 *
	 * Hidden activities, which doesn't need to be shown
	 */
	const HIDDEN_ACTIVITIES = [
		Activity::LIKE, Activity::DISLIKE,
		Activity::ATTEND, Activity::ATTENDNO, Activity::ATTENDMAYBE,
		Activity::FOLLOW,
		Activity::ANNOUNCE,
	];

	/**
	 * Checks if the given activity is a hidden activity
	 *
	 * @param string $activity The current activity
	 *
	 * @return bool True, if the activity is hidden
	 */
	public function isHidden(string $activity)
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
	public function match(string $haystack, string $needle)
	{
		return (($haystack === $needle) ||
		        ((basename($needle) === $haystack) &&
		         strstr($needle, ActivityNamespace::ACTIVITY_SCHEMA)));
	}
}
