<?php

namespace Friendica\Repository\Storable;

use Friendica\Collection;
use Friendica\Core\Protocol;
use Friendica\Model;
use Friendica\Network\HTTPException;
use Friendica\Protocol\ActivityPub;
use Friendica\Protocol\Diaspora;
use Friendica\Repository\Storable;
use Friendica\Util\DateTimeFormat;

/**
 * @method Model\Introduction       selectFirst(array $condition)
 * @method Collection\Introductions select(array $condition = [], array $params = [])
 * @method Collection\Introductions selectByBoundaries(array $condition = [], array $params = [], int $max_id = null, int $since_id = null, int $limit = self::LIMIT)
 */
class Introduction extends Storable
{
	protected static $table_name = 'intro';

	protected static $model_class = Model\Introduction::class;

	protected static $collection_class = Collection\Introductions::class;

	/**
	 * Confirms a follow request and sends a notic to the remote contact.
	 *
	 * @param Model\Introduction $Introduction
	 * @param bool               $duplex       Is it a follow back?
	 * @param bool|null          $hidden       Should this contact be hidden? null = no change
	 * @return bool
	 * @throws HTTPException\InternalServerErrorException
	 * @throws HTTPException\NotFoundException
	 * @throws \ImagickException
	 */
	public function confirm(Model\Introduction $Introduction, bool $duplex = false, bool $hidden = null)
	{
		$this->logger->info('Confirming follower', ['cid' => $this->{'contact-id'}]);

		$contact = Model\Contact::selectFirst([], ['id' => $Introduction->{'contact-id'}, 'uid' => $Introduction->uid]);

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

		return $this->delete($Introduction);
	}


	/**
	 * Silently ignores the introduction, hides it from notifications and prevents the remote contact from submitting
	 * additional follow requests.
	 *
	 * @param Model\Introduction $Introduction
	 * @return bool
	 * @throws \Exception
	 */
	public function ignore(Model\Introduction $Introduction)
	{
		return $this->dba->update('intro', ['ignore' => true], ['id' => $Introduction->id]);
	}

	/**
	 * Discards the introduction and sends a rejection message to AP contacts.
	 *
	 * @param Model\Introduction $Introduction
	 * @return bool
	 * @throws HTTPException\InternalServerErrorException
	 * @throws HTTPException\NotFoundException
	 * @throws \ImagickException
	 */
	public function discard(Model\Introduction $Introduction)
	{
		// If it is a friend suggestion, the contact is not a new friend but an existing friend
		// that should not be deleted.
		if (!$Introduction->fid) {
			// When the contact entry had been created just for that intro, we want to get rid of it now
			$condition = ['id' => $this->{'contact-id'}, 'uid' => $Introduction->uid,
				'self' => false, 'pending' => true, 'rel' => [0, Model\Contact::FOLLOWER]];
			if ($this->dba->exists('contact', $condition)) {
				Model\Contact::remove($this->{'contact-id'});
			} else {
				$this->dba->update('contact', ['pending' => false], ['id' => $this->{'contact-id'}]);
			}
		}

		$contact = Model\Contact::selectFirst([], ['id' => $this->{'contact-id'}, 'uid' => $Introduction->uid]);

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

		return $this->delete($Introduction);
	}
}
