<?php

namespace Friendica\Test\API;

class CheckAclInputTest extends ApiTest
{
	/**
	 * Test the check_acl_input() function.
	 * @return void
	 */
	public function testDefault()
	{
		$result = check_acl_input('<aclstring>');
		// Where does this result come from?
		$this->assertEquals(1, $result);
	}

	/**
	 * Test the check_acl_input() function with an empty ACL string.
	 * @return void
	 */
	public function testWithEmptyAclString()
	{
		$result = check_acl_input(' ');
		$this->assertFalse($result);
	}
}
