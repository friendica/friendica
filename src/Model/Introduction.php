<?php

namespace Friendica\Model;

use Friendica\Core\Protocol;
use Friendica\Model;
use Friendica\Network\HTTPException;
use Friendica\Protocol\ActivityPub;
use Friendica\Protocol\Diaspora;
use Friendica\Util\DateTimeFormat;

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
 * @property bool   ignored
 */
final class Introduction extends Model
{
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

		$contact = Model\Contact::selectFirst([], ['id' => $this->{'contact-id'}, 'uid' => $this->uid]);

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
				$newRelation = Model\Contact::FRIEND;
			} else {
				$newRelation = Model\Contact::FOLLOWER;
			}

			if ($newRelation != Model\Contact::FOLLOWER) {
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

		if ($newRelation == Model\Contact::FRIEND) {
			if ($protocol == Protocol::DIASPORA) {
				$ret = Diaspora::sendShare(Model\Contact::getById($contact['uid']), $contact);
				$this->logger->info('share returns', ['return' => $ret]);
			} elseif ($protocol == Protocol::ACTIVITYPUB) {
				ActivityPub\Transmitter::sendActivity('Follow', $contact['url'], $contact['uid']);
			}
		}

		return true;
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
		return $this->dba->update('intro', ['ignore' => true], ['id' => $this->id]);
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
				'self' => false, 'pending' => true, 'rel' => [0, Model\Contact::FOLLOWER]];
			if ($this->dba->exists('contact', $condition)) {
				Model\Contact::remove($this->{'contact-id'});
			} else {
				$this->dba->update('contact', ['pending' => false], ['id' => $this->{'contact-id'}]);
			}
		}

		$contact = Model\Contact::selectFirst([], ['id' => $this->{'contact-id'}, 'uid' => $this->uid]);

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

		return true;
	}
}
