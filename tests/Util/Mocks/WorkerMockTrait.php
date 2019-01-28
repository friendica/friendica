<?php

namespace Friendica\Test\Util\Mocks;

use Mockery\MockInterface;

trait WorkerMockTrait
{
	/**
	 * @var MockInterface The mocking interface of Friendica\Core\Hook
	 */
	private $workerMock;

	private function checkMock()
	{
		if (!isset($this->workerMock)) {
			$this->workerMock = \Mockery::mock('alias:Friendica\Core\Worker');
		}
	}

	public function mockWorkerAdd($expPriority, $expTask, $expUser, $return = true, $times = null)
	{
		$this->checkMock();

		$closure = function ($priority = null, $task = null, $user = null) use ($expPriority, $expTask, $expUser) {
			return
				$priority === $expPriority &&
				$task === $expTask &&
				$user === $expUser;
		};


		$this->workerMock
			->shouldReceive('add')
			->withArgs($closure)
			->times($times)
			->andReturn($return);
	}
}
