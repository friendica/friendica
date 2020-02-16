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
 * Entity class for table mailacct
 *
 * Mail account data for fetching mails
 */
class Mailacct extends BaseEntity
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
	private $server = '';

	/**
	 * @var string
	 */
	private $port = '0';

	/**
	 * @var string
	 */
	private $ssltype = '';

	/**
	 * @var string
	 */
	private $mailbox = '';

	/**
	 * @var string
	 */
	private $user = '';

	/**
	 * @var string
	 */
	private $pass;

	/**
	 * @var string
	 */
	private $replyTo = '';

	/**
	 * @var string
	 */
	private $action = '0';

	/**
	 * @var string
	 */
	private $movetofolder = '';

	/**
	 * @var bool
	 */
	private $pubmail = '0';

	/**
	 * @var string
	 */
	private $lastCheck = '0001-01-01 00:00:00';

	/**
	 * {@inheritDoc}
	 */
	public function toArray()
	{
		return [
			'id' => $this->id,
			'uid' => $this->uid,
			'server' => $this->server,
			'port' => $this->port,
			'ssltype' => $this->ssltype,
			'mailbox' => $this->mailbox,
			'user' => $this->user,
			'pass' => $this->pass,
			'reply_to' => $this->replyTo,
			'action' => $this->action,
			'movetofolder' => $this->movetofolder,
			'pubmail' => $this->pubmail,
			'last_check' => $this->lastCheck,
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
	 * @return string
	 * Get
	 */
	public function getUser()
	{
		return $this->user;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getServer()
	{
		return $this->server;
	}

	/**
	 * @param string $server
	 * Set
	 */
	public function setServer(string $server)
	{
		$this->server = $server;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getPort()
	{
		return $this->port;
	}

	/**
	 * @param string $port
	 * Set
	 */
	public function setPort(string $port)
	{
		$this->port = $port;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getSsltype()
	{
		return $this->ssltype;
	}

	/**
	 * @param string $ssltype
	 * Set
	 */
	public function setSsltype(string $ssltype)
	{
		$this->ssltype = $ssltype;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getMailbox()
	{
		return $this->mailbox;
	}

	/**
	 * @param string $mailbox
	 * Set
	 */
	public function setMailbox(string $mailbox)
	{
		$this->mailbox = $mailbox;
	}

	/**
	 * @param string $user
	 * Set
	 */
	public function setUser(string $user)
	{
		$this->user = $user;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getPass()
	{
		return $this->pass;
	}

	/**
	 * @param string $pass
	 * Set
	 */
	public function setPass(string $pass)
	{
		$this->pass = $pass;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getReplyTo()
	{
		return $this->replyTo;
	}

	/**
	 * @param string $replyTo
	 * Set
	 */
	public function setReplyTo(string $replyTo)
	{
		$this->replyTo = $replyTo;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getAction()
	{
		return $this->action;
	}

	/**
	 * @param string $action
	 * Set
	 */
	public function setAction(string $action)
	{
		$this->action = $action;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getMovetofolder()
	{
		return $this->movetofolder;
	}

	/**
	 * @param string $movetofolder
	 * Set
	 */
	public function setMovetofolder(string $movetofolder)
	{
		$this->movetofolder = $movetofolder;
	}

	/**
	 * @return bool
	 * Get
	 */
	public function getPubmail()
	{
		return $this->pubmail;
	}

	/**
	 * @param bool $pubmail
	 * Set
	 */
	public function setPubmail(bool $pubmail)
	{
		$this->pubmail = $pubmail;
	}

	/**
	 * @return string
	 * Get
	 */
	public function getLastCheck()
	{
		return $this->lastCheck;
	}

	/**
	 * @param string $lastCheck
	 * Set
	 */
	public function setLastCheck(string $lastCheck)
	{
		$this->lastCheck = $lastCheck;
	}
}
