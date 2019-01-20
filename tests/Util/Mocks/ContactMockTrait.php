<?php

namespace Friendica\Test\Util\Mocks;

use Mockery\MockInterface;

class ContactStub
{
	const FOLLOWER = 1;
	const SHARING  = 2;
	const FRIEND   = 3;

	const PAGE_COMMUNITY = 2;
}

trait ContactMockTrait
{
	/**
	 * @var MockInterface The mocking interface of Friendica\Core\Hook
	 */
	private $contactMock;

	public function mockConstants()
	{
		if (!isset($this->contactMock)) {
			$this->contactMock = \Mockery::namedMock('Friendica\Model\Contact', 'Friendica\Test\Util\Mocks\ContactStub');
		}
	}

	public function mockGetIdForURL($url, $uid = 0, $no_update = null, $default = null, $in_loop = null, $return = null, $times = null)
	{
		if (!isset($this->contactMock)) {
			$this->contactMock = \Mockery::namedMock('Friendica\Model\Contact', 'Friendica\Test\Util\Mocks\ContactStub');
		}

		$closure = function ($url, $uid = 0, $no_update = false, $default = [], $in_loop = false) {
			return true;
		};

		$this->contactMock
			->shouldReceive('getIdForURL')
			->withArgs($closure)
			->times($times)
			->andReturn($return);
	}
}
