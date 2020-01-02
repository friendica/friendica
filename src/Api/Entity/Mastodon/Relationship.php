<?php

namespace Friendica\Api\Entity\Mastodon;

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
	/**
	 * Unsupported
	 * @var bool
	 */
	protected $showing_reblogs = true;
	/**
	 * Unsupported
	 * @var bool
	 */
	protected $endorsed = false;

	/**
	 * @param int   $contact_id Contact row Id
	 * @param array $contact    Full Contact table record
	 */
	public function __construct(int $contact_id, array $contact = [])
	{
		$this->id                   = $contact_id;
		$this->following            = in_array($contact['rel'] ?? 0, [Contact::SHARING, Contact::FRIEND]);
		$this->followed_by          = in_array($contact['rel'] ?? 0, [Contact::FOLLOWER, Contact::FRIEND]);
		$this->blocking             = (bool)$contact['blocked'] ?? false;
		$this->muting               = (bool)$contact['readonly'] ?? false;
		$this->muting_notifications = (bool)$contact['readonly'] ?? false;
		$this->requested            = (bool)$contact['pending'] ?? false;
		$this->domain_blocking      = Network::isUrlBlocked($contact['url'] ?? '');

		return $this;
	}
}
