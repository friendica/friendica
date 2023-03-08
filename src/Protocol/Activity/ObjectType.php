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

namespace Friendica\Protocol\Activity;

use Friendica\Protocol\ActivityNamespace;

/**
 * This class contains the different object types in activities
 */
final class ObjectType
{
	/**
	 * The "bookmark" object type represents a pointer to some URL -- typically a web page.
	 *
	 * @see http://activitystrea.ms/head/activity-schema.html#bookmark
	 * @var string
	 */
	const BOOKMARK = ActivityNamespace::ACTIVITY_SCHEMA . 'bookmark';
	/**
	 * The "comment" object type represents a textual response to another object.
	 *
	 * @see http://activitystrea.ms/head/activity-schema.html#comment
	 * @var string
	 */
	const COMMENT = ActivityNamespace::ACTIVITY_SCHEMA . 'comment';
	/**
	 * The "comment" object type represents a textual response to another object.
	 * (Default type for items)
	 *
	 * @see http://activitystrea.ms/head/activity-schema.html#note
	 * @var string
	 */
	const NOTE = ActivityNamespace::ACTIVITY_SCHEMA . 'note';
	/**
	 * The "person" object type represents a user account.
	 *
	 * @see http://activitystrea.ms/head/activity-schema.html#person
	 * @var string
	 */
	const PERSON = ActivityNamespace::ACTIVITY_SCHEMA . 'person';
	/**
	 * The "image" object type represents a graphical image.
	 *
	 * @see http://activitystrea.ms/head/activity-schema.html#image
	 * @var string
	 */
	const IMAGE = ActivityNamespace::ACTIVITY_SCHEMA . 'image';
	/**
	 * @var string
	 */
	const PHOTO = ActivityNamespace::ACTIVITY_SCHEMA . 'photo';
	/**
	 * The "video" object type represents video content,
	 * which usually consists of a motion picture track and an audio track.
	 *
	 * @see http://activitystrea.ms/head/activity-schema.html#video
	 * @var string
	 */
	const VIDEO = ActivityNamespace::ACTIVITY_SCHEMA . 'video';
	/**
	 * @var string
	 */
	const PROFILE_PHOTO = ActivityNamespace::ACTIVITY_SCHEMA . 'profile-photo';
	/**
	 * @var string
	 */
	const ALBUM = ActivityNamespace::ACTIVITY_SCHEMA . 'photo-album';
	/**
	 * The "event" object type represents an event that occurs in a certain place during a particular interval of time.
	 *
	 * @see http://activitystrea.ms/head/activity-schema.html#event
	 * @var string
	 */
	const EVENT = ActivityNamespace::ACTIVITY_SCHEMA . 'event';
	/**
	 * The "group" object type represents a grouping of objects in which member objects can join or leave.
	 *
	 * @see http://activitystrea.ms/head/activity-schema.html#group
	 * @var string
	 */
	const GROUP = ActivityNamespace::ACTIVITY_SCHEMA . 'group';


	/**
	 * @var string
	 */
	const HEART = ActivityNamespace::DFRN . '/heart';
	/**
	 * @var string
	 */
	const TAGTERM = ActivityNamespace::DFRN . '/tagterm';
	/**
	 * @var string
	 */
	const PROFILE = ActivityNamespace::DFRN . '/profile';


	/**
	 * The "question" object type represents a question or poll.
	 *
	 * @see http://activitystrea.ms/head/activity-schema.html#question
	 * @var string
	 */
	const QUESTION = 'http://activityschema.org/object/question';
}
