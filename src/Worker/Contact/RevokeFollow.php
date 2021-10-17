<?php

namespace Friendica\Worker\Contact;

use Friendica\Core\Protocol;
use Friendica\Core\Worker;
use Friendica\Model\Contact;

class RevokeFollow
{
	public static function execute(int $cid)
	{
		$contact = Contact::getById($cid);
		if (empty($contact)) {
			return;
		}

		$result = Protocol::revokeFollow($contact);
		if ($result === false) {
			Worker::defer();
		}
	}
}
