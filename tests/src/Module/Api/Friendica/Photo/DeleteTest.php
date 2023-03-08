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

namespace Friendica\Test\src\Module\Api\Friendica\Photo;

use Friendica\App\Router;
use Friendica\DI;
use Friendica\Module\Api\Friendica\Photo\Delete;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Test\src\Module\Api\ApiTest;

class DeleteTest extends ApiTest
{
	protected function setUp(): void
	{
		parent::setUp();

		$this->useHttpMethod(Router::POST);
	}

	public function testEmpty()
	{
		$this->expectException(BadRequestException::class);
		(new Delete(DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))->run($this->httpExceptionMock);
	}

	public function testWithoutAuthenticatedUser()
	{
		self::markTestIncomplete('Needs BasicAuth as dynamic method for overriding first');
	}

	public function testWrong()
	{
		$this->expectException(BadRequestException::class);
		(new Delete(DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))->run($this->httpExceptionMock, ['photo_id' => 1]);
	}

	public function testValidWithPost()
	{
		$this->loadFixture(__DIR__ . '/../../../../../datasets/photo/photo.fixture.php', DI::dba());

		$response = (new Delete(DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))
			->run($this->httpExceptionMock, [
				'photo_id' => '709057080661a283a6aa598501504178'
			]);

		$json = $this->toJson($response);

		self::assertEquals('deleted', $json->result);
		self::assertEquals('photo with id `709057080661a283a6aa598501504178` has been deleted from server.', $json->message);
	}

	public function testValidWithDelete()
	{
		$this->loadFixture(__DIR__ . '/../../../../../datasets/photo/photo.fixture.php', DI::dba());

		$response = (new Delete(DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))
			->run($this->httpExceptionMock, [
				'photo_id' => '709057080661a283a6aa598501504178'
			]);

		$responseText = (string)$response->getBody();

		self::assertJson($responseText);

		$json = json_decode($responseText);

		self::assertEquals('deleted', $json->result);
		self::assertEquals('photo with id `709057080661a283a6aa598501504178` has been deleted from server.', $json->message);
	}
}
