<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\Integration\Module\Api\Mastodon\Polls;

use Friendica\App\Router;
use Friendica\Core\EarlyExitException;
use Friendica\DI;
use Friendica\Model\Post\Question;
use Friendica\Model\Post\QuestionOption;
use Friendica\Module\Api\Mastodon\Polls\Votes;
use Friendica\Test\ApiTestCase;
use Friendica\Util\DateTimeFormat;
use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class VotesTest extends ApiTestCase
{
	private int $questionId;

	protected function setUp(): void
	{
		parent::setUp();

		$this->useHttpMethod(Router::POST);

		// Post 1 of the fixture is visible to user 42
		Question::update(1, ['multiple' => false, 'end-time' => DateTimeFormat::utc('now + 1 day')]);
		QuestionOption::update(1, 0, ['name' => 'Yes', 'replies' => 3]);
		QuestionOption::update(1, 1, ['name' => 'No', 'replies' => 1]);

		$this->questionId = (int) DI::dba()->selectFirst('post-question', ['id'], ['uri-id' => 1])['id'];
	}

	private function vote(array $choices, int $id = 0): ResponseInterface
	{
		$request = (new ServerRequest('POST', 'https://friendica.local/api/v1/polls/' . ($id ?: $this->questionId) . '/votes'))
			->withParsedBody(['choices' => $choices]);

		$module = new Votes(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], ['id' => $id ?: $this->questionId]);

		try {
			return $module->handleRequest($request);
		} catch (EarlyExitException $e) { // @phpstan-ignore catch.neverThrown
			return $e->getResponse();
		}
	}

	public function testVoteReturnsThePollWithTheOwnVote(): void
	{
		$response = $this->vote(['1']);

		self::assertSame(200, $response->getStatusCode());
		$json = $this->toJson($response);
		self::assertSame((string) $this->questionId, $json->id);
		self::assertTrue($json->voted);
		self::assertSame([1], $json->own_votes);
	}

	public function testSecondVoteIsRejected(): void
	{
		$this->vote([0]);

		self::assertSame(422, $this->vote([1])->getStatusCode());
	}

	public function testInvalidChoiceIsRejected(): void
	{
		self::assertSame(422, $this->vote([5])->getStatusCode());
	}

	public function testUnknownPollIsNotFound(): void
	{
		self::assertSame(404, $this->vote([0], 999999)->getStatusCode());
	}
}
