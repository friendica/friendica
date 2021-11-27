<?php

namespace Friendica\Test\Util;

use Friendica\App;

class AppDouble extends App
{
	protected $loggedIn = false;

	public function setIsLoggedIn(bool $loggedIn)
	{
		$this->loggedIn = $loggedIn;
	}

	public function isLoggedIn()
	{
		return $this->loggedIn;
	}
}
