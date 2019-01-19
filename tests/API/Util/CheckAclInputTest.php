<?php

namespace Friendica\Test\API;

class CheckAclInputTest extends ApiTest
{
	/**
	 * Test the check_acl_input() function.
	 * @return void
	 */
	public function testWithWrongAclString()
	{
		$this->mockApiUser(42);
		$this->mockExists('contact', ['id' => 'aclstring', 'uid' => 42], false, 1);

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
