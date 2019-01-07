<?php
/**
 * @file mod/randprof.php
 */
use Friendica\App;
use Friendica\Model\Contact;
use Friendica\Model\GContact;

function randprof_init(App $a)
{
	$x = GContact::getRandomUrl();

	if ($x) {
		$link = Contact::magicLink($x);
		$a->redirect($link);
	}

	$a->internalRedirect('profile');
}
