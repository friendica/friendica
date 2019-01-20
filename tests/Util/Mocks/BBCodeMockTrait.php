<?php

namespace Friendica\Test\Util\Mocks;

use Mockery\MockInterface;

trait BBCodeMockTrait
{
	/**
	 * @var MockInterface The mocking interface of Friendica\Core\Hook
	 */
	private $bbCodeMock;

	public function mockConvert($text, $return, $try_oembed = true, $simple_html = false, $for_plaintext = false, $times = null)
	{
		if (!isset($this->bbCodeMock)) {
			$this->bbCodeMock = \Mockery::mock('alias:Friendica\Content\Text\BBCode');
		}

		$closure = function ($text, $try_oembed = true, $simple_html = false, $for_plaintext = false) {
			return true;
		};

		$this->bbCodeMock
			->shouldReceive('convert')
			->withArgs($closure)
			->times($times)
			->andReturn($return);
	}

	public function mockCleanPictureLinks($text, $return, $times = null)
	{
		if (!isset($this->bbCodeMock)) {
			$this->bbCodeMock = \Mockery::mock('alias:Friendica\Content\Text\BBCode');
		}

		$this->bbCodeMock
			->shouldReceive('cleanPictureLinks')
			->with($text)
			->times($times)
			->andReturn($return);
	}

	public function mockGetAttachmentData($text, $return = [], $times = null)
	{
		if (!isset($this->bbCodeMock)) {
			$this->bbCodeMock = \Mockery::mock('alias:Friendica\Content\Text\BBCode');
		}

		$this->bbCodeMock
			->shouldReceive('getAttachmentData')
			->with($text)
			->times($times)
			->andReturn($return);
	}
}
