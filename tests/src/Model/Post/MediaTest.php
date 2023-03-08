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

namespace Friendica\Test\src\Model\Post;

use Friendica\Test\MockedTest;

class MediaTest extends MockedTest
{
	/**
	 * Test the api_get_attachments() function.
	 *
	 * @return void
	 */
	public function testApiGetAttachments()
	{
		self::markTestIncomplete('Needs Model\Post\Media refactoring first.');

		// $body = 'body';
		// self::assertEmpty(api_get_attachments($body, 0));
	}

	/**
	 * Test the api_get_attachments() function with an img tag.
	 *
	 * @return void
	 */
	public function testApiGetAttachmentsWithImage()
	{
		self::markTestIncomplete('Needs Model\Post\Media refactoring first.');

		// $body = '[img]http://via.placeholder.com/1x1.png[/img]';
		// self::assertIsArray(api_get_attachments($body, 0));
	}

	/**
	 * Test the api_get_attachments() function with an img tag and an AndStatus user agent.
	 *
	 * @return void
	 */
	public function testApiGetAttachmentsWithImageAndAndStatus()
	{
		self::markTestIncomplete('Needs Model\Post\Media refactoring first.');

		// $_SERVER['HTTP_USER_AGENT'] = 'AndStatus';
		// $body                       = '[img]http://via.placeholder.com/1x1.png[/img]';
		// self::assertIsArray(api_get_attachments($body, 0));
	}
}
