<?php

namespace Friendica\Test\Util\Mocks;


use Mockery\MockInterface;

trait RendererMockTrait
{
	/**
	 * @var MockInterface The Interface for mocking a renderer
	 */
	private $rendererMock;

	/**
	 * Mocking the method 'Renderer::getMarkupTemplate()'
	 *
	 * @param string $templateName The name of the template which should get
	 * @param string $return the return value of the mock (should be defined to have it later for followUp use)
	 * @param null|int $times How often the method will get used
	 */
	public function mockGetMarkupTemplate($templateName, $return = '', $times = null)
	{
		if (!isset($this->rendererMock)) {
			$this->rendererMock = \Mockery::mock('alias:Friendica\Core\Renderer');
		}

		$this->rendererMock
			->shouldReceive('getMarkupTemplate')
			->with($templateName)
			->times($times)
			->andReturn($return);
	}

	/**
	 * Mocking the method 'Renderer::replaceMacros()'
	 *
	 * @param string $template The template to use (normally, it is the mock result of 'mockGetMarkupTemplate()'
	 * @param array $args The arguments to pass to the macro
	 * @param string $return the return value of the mock
	 * @param null|int $times How often the method will get used
	 */
	public function mockReplaceMacros($template, $args = [], $return = '', $times = null)
	{
		if (!isset($this->rendererMock)) {
			$this->rendererMock = \Mockery::mock('alias:Friendica\Core\Renderer');
		}

		$this->rendererMock
			->shouldReceive('replaceMacros')
			->with($template, $args)
			->times($times)
			->andReturn($return);
	}
}
