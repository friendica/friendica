<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

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
	public function mockGetMarkupTemplate(string $templateName, string $return = '', int $times = null)
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
	 * @param string              $template The template to use (normally, it is the mock result of 'mockGetMarkupTemplate()'
	 * @param array|\Closure|null $args     The arguments to pass to the macro
	 * @param string              $return   the return value of the mock
	 * @param null|int            $times    How often the method will get used
	 */
	public function mockReplaceMacros(string $template, $args = null, string $return = '', int $times = null)
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
