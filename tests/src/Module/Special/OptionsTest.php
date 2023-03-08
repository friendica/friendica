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

namespace Friendica\Test\src\Module\Special;

use Friendica\App\Router;
use Friendica\Capabilities\ICanCreateResponses;
use Friendica\DI;
use Friendica\Module\Special\HTTPException;
use Friendica\Module\Special\Options;
use Friendica\Test\FixtureTest;
use Mockery\MockInterface;

class OptionsTest extends FixtureTest
{
	/** @var MockInterface|HTTPException */
	protected $httpExceptionMock;

	protected function setUp(): void
	{
		parent::setUp();

		$this->httpExceptionMock = \Mockery::mock(HTTPException::class);
	}

	public function testOptionsAll()
	{
		$this->useHttpMethod(Router::OPTIONS);

		$response = (new Options(DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))->run($this->httpExceptionMock);

		self::assertEmpty((string)$response->getBody());
		self::assertEquals(204, $response->getStatusCode());
		self::assertEquals('No Content', $response->getReasonPhrase());
		self::assertEquals([
			'Allow'                       => [implode(',', Router::ALLOWED_METHODS)],
			ICanCreateResponses::X_HEADER => ['blank'],
		], $response->getHeaders());
		self::assertEquals(implode(',', Router::ALLOWED_METHODS), $response->getHeaderLine('Allow'));
	}

	public function testOptionsSpecific()
	{
		$this->useHttpMethod(Router::OPTIONS);

		$response = (new Options(DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], [
			'AllowedMethods' => [Router::GET, Router::POST],
		]))->run($this->httpExceptionMock);

		self::assertEmpty((string)$response->getBody());
		self::assertEquals(204, $response->getStatusCode());
		self::assertEquals('No Content', $response->getReasonPhrase());
		self::assertEquals([
			'Allow'                       => [implode(',', [Router::GET, Router::POST])],
			ICanCreateResponses::X_HEADER => ['blank'],
		], $response->getHeaders());
		self::assertEquals(implode(',', [Router::GET, Router::POST]), $response->getHeaderLine('Allow'));
	}
}
