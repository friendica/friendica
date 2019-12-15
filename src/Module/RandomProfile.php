<?php

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\GContact;

/**
 * Redirects to a random Friendica profile this node knows about
 */
class RandomProfile extends BaseModule
{
	public static function content(array $parameters = [])
	{
		$a = DI::app();

		$contactUrl = GContact::getRandomUrl();

		if ($contactUrl) {
			$link = Contact::magicLink($contactUrl);
			$a->redirect($link);
		}

		$a->internalRedirect('profile');
	}
}
