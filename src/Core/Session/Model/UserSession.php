<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Session\Model;

use Friendica\Core\Session\Capability\IHandleSessions;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Model\Contact;
use Friendica\Model\User;

/**
 * This class handles user sessions, which is directly extended from regular session
 */
class UserSession implements IHandleUserSessions
{
	/** @var IHandleSessions */
	private $session;
	/** @var int|bool saves the public Contact ID for later usage */
	protected $publicContactId = false;

	public function __construct(IHandleSessions $session)
	{
		$this->session = $session;
	}

	/** {@inheritDoc} */
	public function getLocalUserId()
	{
		if (!empty($this->session->get('authenticated')) && !empty($this->session->get('uid'))) {
			return intval($this->session->get('uid'));
		}

		return false;
	}

	/** {@inheritDoc} */
	public function getLocalUserNickname()
	{
		if ($this->isAuthenticated()) {
			return $this->session->get('nickname');
		}

		return false;
	}

	/** {@inheritDoc} */
	public function getPublicContactId()
	{
		if (empty($this->publicContactId) && !empty($this->session->get('authenticated'))) {
			if (!empty($this->session->get('my_address'))) {
				// Local user
				$this->publicContactId = Contact::getIdForURL($this->session->get('my_address'), 0, false);
			} elseif (!empty($this->session->get('visitor_home'))) {
				// Remote user
				$this->publicContactId = Contact::getIdForURL($this->session->get('visitor_home'), 0, false);
			}
		} elseif (empty($this->session->get('authenticated'))) {
			$this->publicContactId = false;
		}

		return $this->publicContactId;
	}

	/** {@inheritDoc} */
	public function getRemoteUserId()
	{
		if (empty($this->session->get('authenticated'))) {
			return false;
		}

		if (!empty($this->session->get('visitor_id'))) {
			return (int)$this->session->get('visitor_id');
		}

		return false;
	}

	/** {@inheritDoc} */
	public function getRemoteContactID(int $uid): int
	{
		if (!empty($this->session->get('remote')[$uid])) {
			$remote = $this->session->get('remote')[$uid];
		} else {
			$remote = 0;
		}

		$local_user = !empty($this->session->get('authenticated')) ? $this->session->get('uid') : 0;

		if (empty($remote) && ($local_user != $uid) && !empty($my_address = $this->session->get('my_address'))) {
			$remote = Contact::getIdForURL($my_address, $uid, false);
		}

		return $remote;
	}

	/** {@inheritDoc} */
	public function getUserIDForVisitorContactID(int $cid): int
	{
		if (empty($this->session->get('remote'))) {
			return 0;
		}

		return array_search($cid, $this->session->get('remote'));
	}

	/** {@inheritDoc} */
	public function getMyUrl(): string
	{
		return $this->session->get('my_url', '');
	}

	/** {@inheritDoc} */
	public function isAuthenticated(): bool
	{
		return $this->session->get('authenticated', false) && $this->getLocalUserId();
	}

	/** {@inheritDoc} */
	public function isSiteAdmin(): bool
	{
		return User::isSiteAdmin($this->getLocalUserId());
	}

	/** {@inheritDoc} */
	public function isModerator(): bool
	{
		return User::isModerator($this->getLocalUserId());
	}

	/** {@inheritDoc} */
	public function isVisitor(): bool
	{
		return $this->session->get('authenticated', false) && $this->session->get('visitor_id') && !$this->session->get('uid');
	}

	/** {@inheritDoc} */
	public function isUnauthenticated(): bool
	{
		return !$this->session->get('authenticated', false);
	}

	/** {@inheritDoc} */
	public function setVisitorsContacts(string $my_url)
	{
		$this->session->set('remote', Contact::getVisitorByUrl($my_url));
	}

	/** {@inheritDoc} */
	public function getSubManagedUserId()
	{
		return $this->session->get('submanage') ?? false;
	}

	/** {@inheritDoc} */
	public function setSubManagedUserId(int $managed_uid): void
	{
		$this->session->set('submanage', $managed_uid);
	}

	/** {@inheritDoc} */
	public function start(): IHandleSessions
	{
		return $this;
	}

	/** {@inheritDoc} */
	public function exists(string $name): bool
	{
		return $this->session->exists($name);
	}

	/** {@inheritDoc} */
	public function get(string $name, $defaults = null)
	{
		return $this->session->get($name, $defaults);
	}

	/** {@inheritDoc} */
	public function pop(string $name, $defaults = null)
	{
		return $this->session->pop($name, $defaults);
	}

	/** {@inheritDoc} */
	public function set(string $name, $value)
	{
		$this->session->set($name, $value);
	}

	/** {@inheritDoc} */
	public function setMultiple(array $values)
	{
		$this->session->setMultiple($values);
	}

	/** {@inheritDoc} */
	public function remove(string $name)
	{
		$this->session->remove($name);
	}

	/** {@inheritDoc} */
	public function clear()
	{
		$this->session->clear();
	}
}
