<?php
/**
 * Created by PhpStorm.
 * User: benlo
 * Date: 25/03/19
 * Time: 21:36
 */

namespace Friendica\Test\src\Content;

use Friendica\Content\Smilies;
use Friendica\DI;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Test\DatabaseTest;

class SmiliesTest extends DatabaseTest
{
	protected function setUp(): void
	{
		parent::setUp();
		DI::config()->set('system', 'no_smilies', false);
		DI::config()->set('system', 'no_smilies', false);
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
	 *
	 * @dataProvider dataLinks
	 *
	 * @param string $text     Test string
	 * @param array  $smilies  List of smilies to replace
	 * @param string $expected Expected result
	 *
	 * @throws InternalServerErrorException
	 */
	public function testReplaceFromArray(string $text, array $smilies, string $expected)
	{
		$output = Smilies::replaceFromArray($text, $smilies);
		self::assertEquals($expected, $output);
	}
}
