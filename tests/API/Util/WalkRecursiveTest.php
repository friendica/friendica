<?php

namespace Friendica\Test\API;

class WalkRecursiveTest extends ApiTest
{
	/**
	 * Test the api_walk_recursive() function.
	 * @return void
	 */
	public function testApiWalkRecursive()
	{
		$array = ['item1'];
		$this->assertEquals(
			$array,
			api_walk_recursive(
				$array,
				function () {
					// Should we test this with a callback that actually does something?
					return true;
				}
			)
		);
	}

	/**
	 * Test the api_walk_recursive() function with an array.
	 * @return void
	 */
	public function testApiWalkRecursiveWithArray()
	{
		$array = [['item1'], ['item2']];
		$this->assertEquals(
			$array,
			api_walk_recursive(
				$array,
				function () {
					// Should we test this with a callback that actually does something?
					return true;
				}
			)
		);
	}
}
