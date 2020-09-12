<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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

namespace Friendica\Domain\Model;

use Friendica\Domain\BaseModel;
use Friendica\Core\Protocol;
use Friendica\Database\Database;
use Friendica\Domain\Repository\Introduction as IntroductionRepo;
use Friendica\Model\Contact;
use Friendica\Model\User;
use Friendica\Network\HTTPException;
use Friendica\Protocol\ActivityPub;
use Friendica\Protocol\Diaspora;
use Friendica\Util\DateTimeFormat;
use Psr\Log\LoggerInterface;

/**
 * @property int    uid
 * @property int    fid
 * @property int    contact-id
 * @property bool   knowyou
 * @property bool   duplex
 * @property string note
 * @property string hash
 * @property string datetime
 * @property bool   blocked
 * @property bool   ignore
 */
class Introduction extends BaseModel
{
	/** @var IntroductionRepo */
	protected $intro;

	public function __construct(Database $dba, LoggerInterface $logger, IntroductionRepo $intro, array $data = [])
	{
		parent::__construct($dba, $logger, $data);

		$this->intro = $intro;
	}

	/**
	 * Confirms a follow request and sends a notice to the remote contact.
	 *
	 * @param bool               $duplex       Is it a follow back?
	 * @param bool|null          $hidden       Should this contact be hidden? null = no change
	 * @return bool
	 * @throws HTTPException\InternalServerErrorException
	 * @throws HTTPException\NotFoundException
	 * @throws \ImagickException
	 */
	public function confirm(bool $duplex = false, bool $hidden = null)
	{
		$this->logger->info('Confirming follower', ['cid' => $this->{'contact-id'}]);

		$contact = Contact::selectFirst([], ['id' => $this->{'contact-id'}, 'uid' => $this->uid]);

		if (!$contact) {
			throw new HTTPException\NotFoundException('Contact record not found.');
		}

		$newRelation = $contact['rel'];
		$writable = $contact['writable'];

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
		$this->dba->update('contact', $fields, ['id' => $contact['id']]);

		array_merge($contact, $fields);

		if ($newRelation == Contact::FRIEND) {
			if ($protocol == Protocol::DIASPORA) {
				$ret = Diaspora::sendShare(User::getById($contact['uid']), $contact);
				$this->logger->info('share returns', ['return' => $ret]);
			} elseif ($protocol == Protocol::ACTIVITYPUB) {
				ActivityPub\Transmitter::sendActivity('Follow', $contact['url'], $contact['uid']);
			}
		}

		return $this->intro->delete($this);
	}

	/**
	 * Silently ignores the introduction, hides it from notifications and prevents the remote contact from submitting
	 * additional follow requests.
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function ignore()
	{
		$this->ignore = true;

		return $this->intro->update($this);
	}

	/**
	 * Discards the introduction and sends a rejection message to AP contacts.
	 *
	 * @return bool
	 * @throws HTTPException\InternalServerErrorException
	 * @throws HTTPException\NotFoundException
	 * @throws \ImagickException
	 */
	public function discard()
	{
		// If it is a friend suggestion, the contact is not a new friend but an existing friend
		// that should not be deleted.
		if (!$this->fid) {
			// When the contact entry had been created just for that intro, we want to get rid of it now
			$condition = ['id' => $this->{'contact-id'}, 'uid' => $this->uid,
				'self' => false, 'pending' => true, 'rel' => [0, Contact::FOLLOWER]];
			if ($this->dba->exists('contact', $condition)) {
				Contact::remove($this->{'contact-id'});
			} else {
				$this->dba->update('contact', ['pending' => false], ['id' => $this->{'contact-id'}]);
			}
		}

		$contact = Contact::selectFirst([], ['id' => $this->{'contact-id'}, 'uid' => $this->uid]);

		if (!$contact) {
			throw new HTTPException\NotFoundException('Contact record not found.');
		}

		if (!empty($contact['protocol'])) {
			$protocol = $contact['protocol'];
		} else {
			$protocol = $contact['network'];
		}

		if ($protocol == Protocol::ACTIVITYPUB) {
			ActivityPub\Transmitter::sendContactReject($contact['url'], $contact['hub-verify'], $contact['uid']);
		}

		return $this->intro->delete($this);
	}
}
