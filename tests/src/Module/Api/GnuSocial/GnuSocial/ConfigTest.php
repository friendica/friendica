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

namespace Friendica\Test\src\Module\Api\GnuSocial\GnuSocial;

use Friendica\DI;
use Friendica\Module\Api\GNUSocial\GNUSocial\Config;
use Friendica\Test\src\Module\Api\ApiTest;

class ConfigTest extends ApiTest
{
	/**
	 * Test the api_statusnet_config() function.
	 */
	public function testApiStatusnetConfig()
	{
		$response = (new Config(DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))
			->run($this->httpExceptionMock);
		$json = $this->toJson($response);

		self::assertEquals(DI::baseUrl()->getHost(), $json->site->server);
		self::assertEquals(DI::config()->get('system', 'theme'), $json->site->theme);
		self::assertEquals(DI::baseUrl() . '/images/friendica-64.png', $json->site->logo);
		self::assertTrue($json->site->fancy);
		self::assertEquals(DI::config()->get('system', 'language'), $json->site->language);
		self::assertEquals(DI::config()->get('system', 'default_timezone'), $json->site->timezone);
		self::assertEquals(200000, $json->site->textlimit);
		self::assertFalse($json->site->private);
		self::assertEquals('always', $json->site->ssl);
	}
}
