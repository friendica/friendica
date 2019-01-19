<?php

namespace Friendica\Test\Util\Mocks;

use Mockery\MockInterface;

trait AuhtenticationMockTrait
{
	/**
	 * @var MockInterface The mocking interface of Friendica\Core\Hook
	 */
	private $authenticationMock;

	public function mockSetAuthenticatedSessionForUser($user_record, $login_initial = false, $interactive = false, $login_refresh = false, $times = null)
	{
		if (!isset($this->authenticationMock)) {
			$this->authenticationMock = \Mockery::mock('alias:Friendica\Core\Authentication');
		}

		$closure = function ($user_record, $login_initial = false, $interactive = false, $login_refresh = false) {
			return true;
		};

		$this->authenticationMock
			->shouldReceive('setAuthenticatedSessionForUser')
			->withArgs($closure)
			->times($times);
	}
}
