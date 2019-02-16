<?php

namespace Friendica\Test\Util;

use Mockery\MockInterface;

trait NetworkMockTrait
{
	/**
	 * @var MockInterface The mocking interface of Friendica\Util\Network
	 */
	private $networkMock;

	public function mockNetworkFetchUrl($url, $times = null)
	{
		if (!isset($this->networkMock)) {
			$this->networkMock = \Mockery::mock('alias:Friendica\Util\Network');
		}

		$this->networkMock
			->shouldReceive('fetchUrl')
			->with($url)
			->andReturn($url)
			->times($times);
	}
}
