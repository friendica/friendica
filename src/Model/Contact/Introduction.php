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

namespace Friendica\Model\Contact;

use Friendica\Contact\Introduction\Entity;
use Friendica\Core\Protocol;
use Friendica\DI;
use Friendica\Network\HTTPException;
use Friendica\Model\Contact;
use Friendica\Model\User;
use Friendica\Protocol\ActivityPub;
use Friendica\Protocol\Diaspora;
use Friendica\Util\DateTimeFormat;

class Introduction
{
	/**
	 * Confirms a follow request and sends a notice to the remote contact.
	 *
	 * @param Entity\Introduction $introduction
	 * @param bool                $duplex       Is it a follow back?
	 * @param bool|null           $hidden       Should this contact be hidden? null = no change
	 *
	 * @throws HTTPException\InternalServerErrorException
	 * @throws HTTPException\NotFoundException
	 * @throws \ImagickException
	 */
	public static function confirm(Entity\Introduction $introduction, bool $duplex = false, ?bool $hidden = null): void
	{
		DI::logger()->info('Confirming follower', ['cid' => $introduction->cid]);

		$contact = Contact::selectFirst([], ['id' => $introduction->cid, 'uid' => $introduction->uid]);

		if (!$contact) {
			throw new HTTPException\NotFoundException('Contact record not found.');
		}

		$newRelation = $contact['rel'];
		$writable    = $contact['writable'];

		if (!empty($contact['protocol'])) {
			$protocol = $contact['protocol'];
		} else {
			$protocol = $contact['network'];
		}

		if ($protocol == Protocol::ACTIVITYPUB) {
			ActivityPub\Transmitter::sendContactAccept($contact['url'], $contact['hub-verify'], $contact['uid']);
		}

		if (in_array($protocol, [Protocol::DIASPORA, Protocol::ACTIVITYPUB])) {
			if ($duplex) {
				$newRelation = Contact::FRIEND;
			} else {
				$newRelation = Contact::FOLLOWER;
			}

			if ($newRelation != Contact::FOLLOWER) {
				$writable = 1;
			}
		}

		$fields = [
			'name-date' => DateTimeFormat::utcNow(),
			'uri-date'  => DateTimeFormat::utcNow(),
			'blocked'   => false,
			'pending'   => false,
			'protocol'  => $protocol,
			'writable'  => $writable,
			'hidden'    => $hidden ?? $contact['hidden'],
			'rel'       => $newRelation,
		];
		Contact::update($fields, ['id' => $contact['id']]);

		array_merge($contact, $fields);

		if ($newRelation == Contact::FRIEND) {
			if ($protocol == Protocol::DIASPORA) {
				$ret = Diaspora::sendShare(User::getById($contact['uid']), $contact);
				DI::logger()->info('share returns', ['return' => $ret]);
			} elseif ($protocol == Protocol::ACTIVITYPUB) {
				ActivityPub\Transmitter::sendActivity('Follow', $contact['url'], $contact['uid']);
			}
		}
	}

	/**
	 * Discards the introduction and sends a rejection message to AP contacts.
	 *
	 * @param Entity\Introduction $introduction
	 *
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function discard(Entity\Introduction $introduction): void
	{
		$contact = Contact::selectFirst([], ['id' => $introduction->cid, 'uid' => $introduction->uid]);
		if (!empty($contact)) {
			if (!empty($contact['protocol'])) {
				$protocol = $contact['protocol'];
			} else {
				$protocol = $contact['network'];
			}

			if ($protocol == Protocol::ACTIVITYPUB) {
				$owner = User::getOwnerDataById($contact['uid']);
				if ($owner) {
					ActivityPub\Transmitter::sendContactReject($contact['url'], $contact['hub-verify'], $owner);
				}
			}
		}
	}
}
