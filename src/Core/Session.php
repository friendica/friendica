<?php

/**
 * @file src/Core/Session.php
 */
namespace Friendica\Core;

use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Registry\Core;
use Friendica\Util\Strings;

/**
 * High-level Session service class
 *
 * @author Hypolite Petovan <hypolite@mrpetovan.com>
 */
class Session
{
	public static $exists = false;
	public static $expire = 180000;

	public static function exists($name)
	{
		return Core::session()->exists($name);
	}

	public static function get($name, $defaults = null)
	{
		return Core::session()->get($name, $defaults);
	}

	public static function set($name, $value)
	{
		Core::session()->set($name, $value);
	}

	public static function setMultiple(array $values)
	{
		Core::session()->setMultiple($values);
	}

	public static function remove($name)
	{
		Core::session()->remove($name);
	}

	public static function clear()
	{
		Core::session()->clear();
	}

	/**
	 * Returns contact ID for given user ID
	 *
	 * @param integer $uid User ID
	 * @return integer Contact ID of visitor for given user ID
	 */
	public static function getRemoteContactID($uid)
	{
		$session = Core::session();

		if (empty($session->get('remote')[$uid])) {
			return false;
		}

		return $session->get('remote')[$uid];
	}

	/**
	 * Returns User ID for given contact ID of the visitor
	 *
	 * @param integer $cid Contact ID
	 * @return integer User ID for given contact ID of the visitor
	 */
	public static function getUserIDForVisitorContactID($cid)
	{
		$session = Core::session();

		if (empty($session->get('remote'))) {
			return false;
		}

		return array_search($cid, $session->get('remote'));
	}

	/**
	 * Set the session variable that contains the contact IDs for the visitor's contact URL
	 *
	 * @param string $url Contact URL
	 */
	public static function setVisitorsContacts()
	{
		$session = Core::session();

		$session->set('remote', []);

		$remote_contacts = DBA::select('contact', ['id', 'uid'], ['nurl' => Strings::normaliseLink($session->get('my_url')), 'rel' => [Contact::FOLLOWER, Contact::FRIEND], 'self' => false]);
		while ($contact = DBA::fetch($remote_contacts)) {
			if (($contact['uid'] == 0) || Contact::isBlockedByUser($contact['id'], $contact['uid'])) {
				continue;
			}

			$session->set('remote', [$contact['uid'] => $contact['id']]);
		}
		DBA::close($remote_contacts);
	}

	/**
	 * Returns if the current visitor is authenticated
	 *
	 * @return boolean "true" when visitor is either a local or remote user
	 */
	public static function isAuthenticated()
	{
		$session = Core::session();

		return $session->get('authenticated', false);
	}
}
