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

namespace Friendica\Test;

use Dice\Dice;
use Friendica\DI;
use Friendica\Network\HTTPClient\Factory\HttpClient;
use Friendica\Network\HTTPClient\Capability\ICanSendHttpRequests;
use GuzzleHttp\HandlerStack;

/**
 * This class injects a mockable handler into the IHTTPClient dependency per Dice
 */
trait DiceHttpMockHandlerTrait
{
	use FixtureTestTrait;

	/**
	 * Handler for mocking requests anywhere for testing purpose
	 *
	 * @var HandlerStack
	 */
	protected $httpRequestHandler;

	protected function setupHttpMockHandler(): void
	{
		$this->setUpFixtures();

		$this->httpRequestHandler = HandlerStack::create();

		$dice = DI::getDice();
		// addRule() clones the current instance and returns a new one, so no concurrency problems :-)
		$newDice = $dice->addRule(ICanSendHttpRequests::class, [
			'instanceOf' => HttpClient::class,
			'call'       => [
				['createClient', [$this->httpRequestHandler], Dice::CHAIN_CALL],
			],
		]);

		DI::init($newDice);
	}

	protected function tearDownHandler(): void
	{
		$this->tearDownFixtures();
	}
}
