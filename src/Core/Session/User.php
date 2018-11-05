<?php

namespace Friendica\Core\Session;

use Friendica\Core\Session;

class User
{
	/**
	 * Checks if the current session user is locally logged in
	 *
	 * @param int $uid Optional UID of an user to check if it is the logged in user
	 *
	 * @return bool
	 */
	public function isLocal($uid = null)
	{
		return Session::get('authenticated')
			&& Session::get('uid')
			&& (isset($uid) ? $uid === Session::get('uid') : true);
	}

	/**
	 * Checks if the current session user is an authenticated site visitor
	 *
	 * @param null $uid Optional UID of an user to check if it is the authenticated site visitor
	 * @return bool
	 */
	public function isRemote($uid = null)
	{
		return Session::get('authenticated')
			&& Session::get('visitor_id')
			&& (isset($uid) ? $uid === Session::get('visitor_id') : true);
	}

	/**
	 * Checks if the current session user is logged in
	 *
	 * @return bool
	 */
	public function isLoggedIn()
	{
		return $this->isLocal() || $this->isRemote();
	}

	/**
	 * Returns the public contact id for logged in user
	 *
	 * @return int|null
	 */
	public function getUid()
	{
		if ($this->isLocal()) {
			return intval(Session::get('uid'));
		} else {
			return null;
		}
	}

	/**
	 * Returns the contact id of authenticated site visitor
	 *
	 * @return int|null
	 */
	public function getVisitorId()
	{
		if ($this->isRemote()) {
			return intval(Session::get('visitor_id'));
		} else {
			return null;
		}
	}

	public function __construct()
	{
	}
}
