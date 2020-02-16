<?php

/**
 * @copyright Copyright (C) 2020, Friendica
 *
 * @license GNU APGL version 3 or any later version
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
 * Used to check/generate entities for the Friendica codebase
 */

declare(strict_types=1);

namespace Friendica\Domain\Entity;

use Friendica\BaseEntity;
use Friendica\Network\HTTPException\NotImplementedException;

/**
 * Entity class for table push_subscriber
 *
 * Used for OStatus: Contains feed subscribers
 */
class PushSubscriber extends BaseEntity
{
	/**
	 * @var int
	 * sequential ID
	 */
	private $id;

	/**
	 * @var int
	 * User id
	 */
	private $uid = '0';

	/**
	 * @var string
	 */
	private $callbackUrl = '';

	/**
	 * @var string
	 */
	private $topic = '';

	/**
	 * @var string
	 */
	private $nickname = '';

	/**
	 * @var string
	 * Retrial counter
	 */
	private $push = '0';

	/**
	 * @var string
	 * Date of last successful trial
	 */
	private $lastUpdate = '0001-01-01 00:00:00';

	/**
	 * @var string
	 * Next retrial date
	 */
	private $nextTry = '0001-01-01 00:00:00';

	/**
	 * @var string
	 * Date of last subscription renewal
	 */
	private $renewed = '0001-01-01 00:00:00';

	/**
	 * @var string
	 */
	private $secret = '';

	/**
	 * {@inheritDoc}
	 */
	public function toArray()
	{
		return [
			'id' => $this->id,
			'uid' => $this->uid,
			'callback_url' => $this->callbackUrl,
			'topic' => $this->topic,
			'nickname' => $this->nickname,
			'push' => $this->push,
			'last_update' => $this->lastUpdate,
			'next_try' => $this->nextTry,
			'renewed' => $this->renewed,
			'secret' => $this->secret,
		];
	}

	/**
	 * @return int
	 * Get sequential ID
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return int
	 * Get User id
	 */
	public function getUid()
	{
		return $this->uid;
	}

	/**
	 * @param int $uid
	 * Set User id
	 */
	public function setUid(int $uid)
	{
		$this->uid = $uid;
	}

	/**
	 * Get User
	 *
	 * @return User
	 */
	public function getUser()
	{
		//@todo use closure
		throw new NotImplementedException('lazy loading for uid is not implemented yet');
	}

	/**
	 * @return string
	 * Get
	 */
	public function getCallbackUrl()
	{
		return $this->callbackUrl;
	}

	/**
	 * @param string $callbackUrl
	 * Set
	 */
	public function setCallbackUrl(string $callbackUrl)
	{
		$this->callbackUrl = $callbackUrl;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getTopic()
	{
		return $this->topic;
	}

	/**
	 * @param string $topic
	 * Set
	 */
	public function setTopic(string $topic)
	{
		$this->topic = $topic;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getNickname()
	{
		return $this->nickname;
	}

	/**
	 * @param string $nickname
	 * Set
	 */
	public function setNickname(string $nickname)
	{
		$this->nickname = $nickname;
	}

	/**
	 * @return string
	 * Get Retrial counter
	 */
	public function getPush()
	{
		return $this->push;
	}

	/**
	 * @param string $push
	 * Set Retrial counter
	 */
	public function setPush(string $push)
	{
		$this->push = $push;
	}

	/**
	 * @return string
	 * Get Date of last successful trial
	 */
	public function getLastUpdate()
	{
		return $this->lastUpdate;
	}

	/**
	 * @param string $lastUpdate
	 * Set Date of last successful trial
	 */
	public function setLastUpdate(string $lastUpdate)
	{
		$this->lastUpdate = $lastUpdate;
	}

	/**
	 * @return string
	 * Get Next retrial date
	 */
	public function getNextTry()
	{
		return $this->nextTry;
	}

	/**
	 * @param string $nextTry
	 * Set Next retrial date
	 */
	public function setNextTry(string $nextTry)
	{
		$this->nextTry = $nextTry;
	}

	/**
	 * @return string
	 * Get Date of last subscription renewal
	 */
	public function getRenewed()
	{
		return $this->renewed;
	}

	/**
	 * @param string $renewed
	 * Set Date of last subscription renewal
	 */
	public function setRenewed(string $renewed)
	{
		$this->renewed = $renewed;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getSecret()
	{
		return $this->secret;
	}

	/**
	 * @param string $secret
	 * Set
	 */
	public function setSecret(string $secret)
	{
		$this->secret = $secret;
	}
}
