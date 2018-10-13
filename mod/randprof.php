<?php
/**
 * @file mod/randprof.php
 */
use Friendica\App;
use Friendica\Core\System;
use Friendica\Model\Contact;
use Friendica\Model\GContact;
use Friendica\Model\Profile;

function randprof_init(App $a)
{
	$x = GContact::getRandomUrl();

	if ($x) {
		$a->redirect(Contact::magicLink($x));
	}

	$a->redirect('profile');
}
