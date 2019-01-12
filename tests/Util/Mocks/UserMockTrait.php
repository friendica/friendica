<?php

namespace Friendica\Test\Util\Mocks;

use Mockery\MockInterface;

trait UserMockTrait
{
	/**
	 * @var MockInterface The mocking interface of Friendica\Core\Hook
	 */
	private $userMock;

	public function mockAuthenticate($user, $password, $return = true, $times = null)
	{
		if (!isset($this->userMock)) {
			$this->userMock= \Mockery::mock('alias:Friendica\Model\User');
		}

		$this->userMock
			->shouldReceive('authenticate')
			->with($user, $password)
			->times($times)
			->andReturn($return);
	}

	public function mockIdentites($uid, $return = [], $times = null)
	{
		if (!isset($this->userMock)) {
			$this->userMock = \Mockery::mock('alias:Friendica\Model\User');
		}

		$this->userMock
			->shouldReceive('identities')
			->with($uid)
			->times($times)
			->andReturn($return);
	}
}
