<?php

namespace Friendica\Test\Util;

use Friendica\Core\Renderer;
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
	 * @param string   $templateName The name of the template which should get
	 * @param string   $return       the return value of the mock (should be defined to have it later for followUp use)
	 * @param null|int $times        How often the method will get used
	 */
	public function mockGetMarkupTemplate($templateName, $return = '', $times = null)
	{
		if (!isset($this->rendererMock)) {
			$this->rendererMock = \Mockery::mock('alias:' . Renderer::class);
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
	 * @param string              $template     The template to use (normally, it is the mock result of 'mockGetMarkupTemplate()'
	 * @param array|\Closure|null $args         The arguments to pass to the macro
	 * @param string              $return       the return value of the mock
	 * @param null|int            $times        How often the method will get used
	 */
	public function mockReplaceMacros($template, $args = null, $return = '', $times = null)
	{
		if (!isset($this->rendererMock)) {
			$this->rendererMock = \Mockery::mock('alias:' . Renderer::class);
		}

		if (!isset($args)) {
			$args = [];
		}

		$this->rendererMock
			->shouldReceive('replaceMacros')
			->with($template, $args)
			->times($times)
			->andReturn($return);
	}
}
