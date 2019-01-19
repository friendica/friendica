<?php

namespace Friendica\Test\Util\Mocks;

use Mockery\MockInterface;

trait PhotoMockTrait
{
	/**
	 * @var MockInterface The mocking interface of Friendica\Core\Hook
	 */
	private $photoMock;

	private function checkMock()
	{
		if (!isset($this->photoMock)) {
			$this->photoMock = \Mockery::mock('alias:Friendica\Model\Photo');
		}
	}

	public function mockPhotoNewResource($return = '1234', $times = null)
	{
		$this->checkMock();

		$this->photoMock
			->shouldReceive('newResource')
			->times($times)
			->andReturn($return);
	}

	public function mockPhotoStore($return = true, $times = null)
	{
		$this->checkMock();

		$this->photoMock
			->shouldReceive('store')
			->times($times)
			->andReturn($return);
	}
}
