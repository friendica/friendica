<?php
/**
 * Created by PhpStorm.
 * User: benlo
 * Date: 25/03/19
 * Time: 21:36
 */

namespace Friendica\Test\src\Content;

use Friendica\Content\Smilies;
use Friendica\Test\MockedTest;
use Friendica\Test\Util\AppMockTrait;
use Friendica\Test\Util\VFSTrait;

class SmiliesTest extends MockedTest
{
	use VFSTrait;
	use AppMockTrait;

	protected function setUp()
	{
		parent::setUp();
		$this->setUpVfsDir();
		$this->mockApp($this->root);
		$this->app->videowidth = 425;
		$this->app->videoheight = 350;
		$this->configMock->shouldReceive('get')
			->with('system', 'no_smilies')
			->andReturn(false);
		$this->configMock->shouldReceive('get')
			->with(false, 'system', 'no_smilies')
			->andReturn(false);
	}

	public function dataLinks()
	{
		return [
			/** @see https://github.com/friendica/friendica/pull/6933 */
			'bug-6933-1' => [
				'data' => '<code>/</code>',
				'smilies' => ['texts' => [], 'icons' => []],
				'expected' => '<code>/</code>',
			],
			'bug-6933-2' => [
				'data' => '<code>code</code>',
				'smilies' => ['texts' => [], 'icons' => []],
				'expected' => '<code>code</code>',
			],
		];
	}

	/**
	 * Test replace smilies in different texts
	 * @dataProvider dataLinks
	 *
	 * @param string $text     Test string
	 * @param array  $smilies  List of smilies to replace
	 * @param string $expected Expected result
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function testReplaceFromArray($text, $smilies, $expected)
	{
		$output = Smilies::replaceFromArray($text, $smilies);
		$this->assertEquals($expected, $output);
	}
}
