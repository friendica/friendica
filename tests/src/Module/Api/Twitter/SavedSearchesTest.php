<?php

namespace Friendica\Test\src\Module\Api\Twitter;

use Friendica\Capabilities\ICanCreateResponses;
use Friendica\DI;
use Friendica\Module\Api\Twitter\SavedSearches;
use Friendica\Test\src\Module\Api\ApiTest;

class SavedSearchesTest extends ApiTest
{
	public function test()
	{
		$response = (new SavedSearches(DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], ['extension' => 'json']))
			->run();

		$result = $this->toJson($response);

		self::assertEquals(['Content-type' => ['application/json'], ICanCreateResponses::X_HEADER => ['json']], $response->getHeaders());
		self::assertEquals(1, $result[0]->id);
		self::assertEquals(1, $result[0]->id_str);
		self::assertEquals('Saved search', $result[0]->name);
		self::assertEquals('Saved search', $result[0]->query);
	}
}
