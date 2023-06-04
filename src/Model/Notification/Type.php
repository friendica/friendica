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

namespace Friendica\Model\Notification;

/**
 * Enum for different types of the Notify
 */
class Type
{
	/** @var int Notification about a introduction */
	const INTRO = 1;
	/** @var int Notification about a confirmed introduction */
	const CONFIRM = 2;
	/** @var int Notification about a post on your wall */
	const WALL = 4;
	/** @var int Notification about a followup comment */
	const COMMENT = 8;
	/** @var int Notification about a private message */
	const MAIL = 16;
	/** @var int Notification about a friend suggestion */
	const SUGGEST = 32;
	/** @var int Notification about being tagged in a post */
	const TAG_SELF = 128;
	/** @var int Notification about getting poked/prodded/etc. (Obsolete) */
	const POKE = 512;
	/** @var int Notification about either a contact had posted something directly or the contact is a mentioned group */
	const SHARE = 1024;

	/** @var int Global System notifications */
	const SYSTEM = 32768;
}
