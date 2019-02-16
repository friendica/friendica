<?php

namespace Friendica\Test\Util;

use Mockery\MockInterface;
use Psr\Log\LoggerInterface;

trait UpdateMockTrait
{
	/**
	 * @var MockInterface The mocking interface of Friendica\Util\Network
	 */
	private $updateMock;

	/**
	 * Mocks an Update::run()
	 *
	 * @param $basePath
	 * @param LoggerInterface $logger
	 * @param bool $force
	 * @param bool $verbose
	 * @param bool $sendMail
	 * @param string $return
	 * @param null $times
	 */
	public function mockUpdateRun($expBasePath, LoggerInterface $expLogger, $expForce = false, $expVerbose = false, $expSendMail = false, $return = '', $times = null)
	{
		if (!isset($this->updateMock)) {
			$this->updateMock = \Mockery::mock('alias:Friendica\Core\Update');
		}

		$closure = function($basePath, $logger, $force = false, $verbose = false, $sendMail = false)
			use ($expBasePath, $expLogger, $expForce, $expVerbose, $expSendMail) {
			return $expBasePath === $basePath
				&& $expLogger   === $logger
				&& $expForce    === $force
				&& $expVerbose  === $verbose
				&& $expSendMail === $sendMail;
		};

		$this->updateMock
			->shouldReceive('run')
			->withArgs($closure)
			->andReturn($return)
			->times($times);
	}
}
