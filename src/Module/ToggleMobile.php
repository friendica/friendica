<?php

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\DI;

/**
 * Toggles the mobile view (on/off)
 */
class ToggleMobile extends BaseModule
{
	public static function content(array $parameters = [])
	{
		$a = DI::app();

		if (isset($_GET['off'])) {
			$_SESSION['show-mobile'] = false;
		} else {
			$_SESSION['show-mobile'] = true;
		}

		if (isset($_GET['address'])) {
			$address = $_GET['address'];
		} else {
			$address = '';
		}

		$a->redirect($address);
	}
}
