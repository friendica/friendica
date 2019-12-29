<?php

namespace Friendica\Api\Mastodon;

use Friendica\Api\Entity;
use Friendica\Model\Contact;
use Friendica\Util\Network;

/**
 * Class Relationship
 *
 * @see https://docs.joinmastodon.org/api/entities/#relationship
 */
class Relationship extends Entity
{
	/** @var int */
	protected $id;
	/** @var bool */
	protected $following = false;
	/** @var bool */
	protected $followed_by = false;
	/** @var bool */
	protected $blocking = false;
	/** @var bool */
	protected $muting = false;
	/** @var bool */
	protected $muting_notifications = false;
	/** @var bool */
	protected $requested = false;
	/** @var bool */
	protected $domain_blocking = false;
	/** @var bool */
	protected $showing_reblogs = false;
	/** @var bool */
	protected $endorsed = false;

	/**
	 * Default relationship factory for deleted user-contacts
	 *
	 * @param int $contactId
	 * @return Relationship
	 */
	public static function createDefaultFromContactId(int $contactId)
	{
		$relationship = new self();
		$relationship->id = $contactId;

		return $relationship;
	}

	/**
	 * @param array $contact Full Contact table record
	 * @return Relationship
	 */
	public static function createFromContact(array $contact)
	{
		$relationship = new self();

		$relationship->id                   = $contact['id'];
		$relationship->following            = in_array($contact['rel'], [Contact::SHARING, Contact::FRIEND]);
		$relationship->followed_by          = in_array($contact['rel'], [Contact::FOLLOWER, Contact::FRIEND]);
		$relationship->blocking             = (bool)$contact['blocked'];
		$relationship->muting               = (bool)$contact['readonly'];
		$relationship->muting_notifications = (bool)$contact['readonly'];
		$relationship->requested            = (bool)$contact['pending'];
		$relationship->domain_blocking      = Network::isUrlBlocked($contact['url']);
		// Unsupported
		$relationship->showing_reblogs      = true;
		// Unsupported
		$relationship->endorsed             = false;

		return $relationship;
	}
}
