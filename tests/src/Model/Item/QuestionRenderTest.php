<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Model\Item;

use Friendica\Core\Renderer;
use Friendica\Render\FriendicaSmartyEngine;
use Friendica\Test\FixtureTestCase;

/**
 * Renders content/question.tpl directly (not through Item::prepareBody(), which
 * needs a full item/author/contact fixture) to catch template syntax errors and
 * confirm the voting form only appears when a vote can actually be cast.
 */
class QuestionRenderTest extends FixtureTestCase
{
	protected function setUp(): void
	{
		parent::setUp();

		// Normally done by App::registerTemplateEngine() during full app bootstrap,
		// which the lightweight fixture DI container doesn't run.
		Renderer::registerTemplateEngine(FriendicaSmartyEngine::class);
	}

	private function renderQuestion(bool $canVote, bool $multiple): string
	{
		return Renderer::replaceMacros(Renderer::getMarkupTemplate('content/question.tpl'), [
			'$item_id'  => 42,
			'$question' => ['id' => 1, 'multiple' => $multiple, 'voters' => 5, 'endtime' => ''],
			'$options'  => [
				['id' => 1, 'name' => 'Tabs', 'replies' => 3, 'vote' => 'Tabs (60%, 3 votes)', 'percent' => 60],
				['id' => 2, 'name' => 'Spaces', 'replies' => 2, 'vote' => 'Spaces (40%, 2 votes)', 'percent' => 40],
			],
			'$summary'       => '5 voters.',
			'$can_vote'      => $canVote,
			'$multiple'      => $multiple,
			'$vote_label'    => 'Vote',
			'$vote_recorded' => 'Your vote has been recorded.',
			'$vote_failed'   => 'Your vote could not be recorded.',
		]);
	}

	public function testVotingFormWhenAllowedToVote(): void
	{
		$html = $this->renderQuestion(true, false);

		self::assertStringContainsString('poll-form-42', $html);
		self::assertStringContainsString('type="radio"', $html);
		self::assertStringNotContainsString('type="checkbox"', $html);
		self::assertStringContainsString('doPollVote(42,', $html);
		self::assertStringContainsString('Tabs (60%, 3 votes)', $html);
	}

	public function testCheckboxesForMultipleChoice(): void
	{
		$html = $this->renderQuestion(true, true);

		self::assertStringContainsString('type="checkbox"', $html);
		self::assertStringNotContainsString('type="radio"', $html);
	}

	public function testReadOnlyWhenNotAllowedToVote(): void
	{
		$html = $this->renderQuestion(false, false);

		self::assertStringNotContainsString('poll-form-42', $html);
		self::assertStringNotContainsString('<form', $html);
		self::assertStringContainsString('Tabs (60%, 3 votes)', $html);
	}
}
