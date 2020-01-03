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
	 * @param int   $userContactId Contact row Id with uid != 0
	 * @param array $userContact   Full Contact table record with uid != 0
	 */
	public function __construct(int $userContactId, array $userContact = [])
	{
		$this->id                   = $userContactId;
		$this->following            = in_array($userContact['rel'] ?? 0, [Contact::SHARING, Contact::FRIEND]);
		$this->followed_by          = in_array($userContact['rel'] ?? 0, [Contact::FOLLOWER, Contact::FRIEND]);
		$this->blocking             = (bool)$userContact['blocked'] ?? false;
		$this->muting               = (bool)$userContact['readonly'] ?? false;
		$this->muting_notifications = (bool)$userContact['readonly'] ?? false;
		$this->requested            = (bool)$userContact['pending'] ?? false;
		$this->domain_blocking      = Network::isUrlBlocked($userContact['url'] ?? '');

		return $this;
	}
}
