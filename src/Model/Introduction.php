<?php

namespace Friendica\Model;

use Friendica\BaseModel;
use Friendica\Core\Protocol;
use Friendica\Network\HTTPException;
use Friendica\Protocol\ActivityPub;
use Friendica\Protocol\Diaspora;
use Friendica\Util\DateTimeFormat;

/**
 *
 * @package Friendica\Model
 */
final class Introduction extends BaseModel
{
	static $table_name = 'intro';
	static $entity_class = Entity\Introduction::class;

	/**
	 * Confirms a follow request and sends a notic to the remote contact.
	 *
	 * @param bool      $duplex Is it a follow back?
	 * @param bool|null $hidden Should this contact be hidden? null = no change
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 * @throws HTTPException\NotFoundException
	 */
	public function confirm(Entity\Introduction $intro, bool $duplex = false, bool $hidden = null)
	{
		$this->logger->info('Confirming follower', ['cid' => $intro->{'contact-id'}]);

		$contact = Contact::selectFirst([], ['id' => $this->{'contact-id'}, 'uid' => $intro->uid]);

		if (!$contact) {
			throw new HTTPException\NotFoundException('Contact record not found.');
		}

		$new_relation = $contact['rel'];
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
				$new_relation = Contact::FRIEND;
			} else {
				$new_relation = Contact::FOLLOWER;
			}

			if ($new_relation != Contact::FOLLOWER) {
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
			'rel'       => $new_relation,
		];
		$this->dba->update('contact', $fields, ['id' => $contact['id']]);

		array_merge($contact, $fields);

		if ($new_relation == Contact::FRIEND) {
			if ($protocol == Protocol::DIASPORA) {
				$ret = Diaspora::sendShare(User::getById($contact['uid']), $contact);
				$this->logger->info('share returns', ['return' => $ret]);
			} elseif ($protocol == Protocol::ACTIVITYPUB) {
				ActivityPub\Transmitter::sendActivity('Follow', $contact['url'], $contact['uid']);
			}
		}

		$this->delete($intro);
	}

	/**
	 * Silently ignores the introduction, hides it from notifications and prevents the remote contact from submitting
	 * additional follow requests.
	 *
	 * @param Entity\Introduction $intro
	 *
	 * @throws \Exception
	 */
	public function ignore(Entity\Introduction &$intro)
	{
		if ($this->dba->update('intro', ['ignore' => true], ['id' => $intro->id])) {
			// since we cannot update a current intro entity, we create a new one with the ignore flag and return it
			$intro = Entity\Introduction::fromEntity($intro, ['ignore' => true]);
		}
	}

	/**
	 * Discards the introduction and sends a rejection message to AP contacts.
	 *
	 * @throws HTTPException\InternalServerErrorException
	 * @throws HTTPException\NotFoundException
	 * @throws \ImagickException
	 */
	public function discard(Entity\Introduction &$intro)
	{
		// If it is a friend suggestion, the contact is not a new friend but an existing friend
		// that should not be deleted.
		if (!$intro->fid) {
			// When the contact entry had been created just for that intro, we want to get rid of it now
			$condition = ['id' => $intro->{'contact-id'}, 'uid' => $intro->uid,
				'self' => false, 'pending' => true, 'rel' => [0, Contact::FOLLOWER]];
			if ($this->dba->exists('contact', $condition)) {
				Contact::remove($this->{'contact-id'});
			} else {
				$this->dba->update('contact', ['pending' => false], ['id' => $intro->{'contact-id'}]);
			}
		}

		$contact = Contact::selectFirst([], ['id' => $this->{'contact-id'}, 'uid' => $intro->uid]);

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

		$this->delete($intro);
	}

	/**
	 * @param array $condition
	 * @param array $fields
	 *
	 * @return Entity\Introduction
	 * @throws HTTPException\NotFoundException
	 */
	public function fetch(array $condition, array $fields = [])
	{
		return parent::fetch($condition, $fields);
	}
}
