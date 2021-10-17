<?php

namespace Friendica\Worker\Contact;

use Friendica\Core\Protocol;
use Friendica\Core\Worker;
use Friendica\Model\Contact;
use Friendica\Model\User;

class Unfollow
{
	public static function execute(int $uid, int $cid)
	{
		$owner = User::getOwnerDataById($uid, false);
		if (empty($owner)) {
			return;
		}

		$contact = Contact::getById($cid);
		if (empty($contact)) {
			return;
		}

		$result = Protocol::terminateFriendship($owner, $contact);
		if ($result === false) {
			Worker::defer();
		}
	}
}
