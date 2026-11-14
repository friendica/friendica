<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test;

use DOMElement;
use DOMXPath;
use Friendica\Render\FriendicaSmarty;
use Friendica\Test\Util\Html\Document;
use Friendica\Test\Util\Html\Invariants;
use Friendica\Test\Util\Html\Violation;
use PHPUnit\Framework\TestCase;

/**
 * Base class for tests that render a Smarty template and assert on the resulting HTML.
 *
 * Deliberately free of database and dependency injection: it drives {@see FriendicaSmarty}
 * directly instead of going through Renderer/FriendicaSmartyEngine, which would pull in
 * config, session, profiler and the event dispatcher. That keeps these tests in the `unit`
 * suite, so every contributor can run them without the Docker stack.
 *
 * The price is that the ArrayFilterEvent::TEMPLATE_VARS middleware (addon variable
 * injection) is skipped - which is what a template test wants anyway.
 *
 * Guidelines for writing tests on top of this:
 *
 *   - Assert invariants ("every image has an alt text"), not concrete markup. Element ids
 *     in view/ churn by roughly 9% per year, invariants do not churn at all.
 *   - Query the DOM via {@see xpath()}. String matching on HTML also matches commented-out
 *     markup and breaks on reformatting.
 *   - Prefer building $vars from the production code path over hand-writing them, so a
 *     changed data contract fails the test instead of silently drifting.
 */
abstract class TemplateTestCase extends TestCase
{
	protected const DEFAULT_THEME = 'frio';

	/** @var array<string, FriendicaSmarty> */
	private array $smarty = [];

	/**
	 * Renders a template the same way Friendica does at runtime.
	 *
	 * @param string               $template Template path relative to the templates folder, e.g. 'media/browser.tpl'
	 * @param array<string, mixed> $vars     Template variables, with or without the leading '$'
	 * @param string               $theme    Theme whose template overrides are applied
	 */
	protected function renderTemplate(string $template, array $vars = [], string $theme = self::DEFAULT_THEME): string
	{
		$smarty = $this->smarty[$theme] ??= $this->createSmarty($theme);

		$smarty->clearAllAssign();

		foreach ($vars as $key => $value) {
			$smarty->assign(ltrim((string) $key, '$'), $value);
		}

		return $smarty->fetch('file:' . $this->resolveTemplate($template, $theme));
	}

	/**
	 * Asserts that rendered HTML holds every markup invariant of {@see Invariants}.
	 *
	 * @param string $html    Rendered HTML
	 * @param string $context Shown in the failure message, e.g. the template name
	 */
	protected function assertMarkupInvariants(string $html, string $context = 'rendered output'): void
	{
		$violations = Invariants::check(Document::fromHtml($html), Invariants::RENDERED, $context);

		self::assertSame(
			[],
			array_map(fn (Violation $violation): string => $violation->rule . ' at ' . $violation->where(), $violations),
			Invariants::report($violations),
		);
	}

	/**
	 * Renders a template and asserts all invariants on it in one step.
	 *
	 * @param array<string, mixed> $vars
	 */
	protected function assertTemplateRendersValidMarkup(string $template, array $vars = [], string $theme = self::DEFAULT_THEME): string
	{
		$html = $this->renderTemplate($template, $vars, $theme);

		$this->assertMarkupInvariants($html, sprintf('%s (theme: %s)', $template, $theme));

		return $html;
	}

	protected function xpath(string $html): DOMXPath
	{
		return Document::fromHtml($html);
	}

	/**
	 * All elements of the rendered HTML matching an XPath expression.
	 *
	 * @return DOMElement[]
	 */
	protected function elements(string $html, string $query): array
	{
		return Document::elements(Document::fromHtml($html), $query);
	}

	/**
	 * The n-th element matching an XPath expression; fails the test if it is missing.
	 */
	protected function element(string $html, string $query, int $index = 0): DOMElement
	{
		$elements = $this->elements($html, $query);

		self::assertArrayHasKey($index, $elements, sprintf('No element #%d for "%s" in the rendered output', $index, $query));

		return $elements[$index];
	}

	/**
	 * Every theme Friendica ships. Use as a data provider to catch the "fixed in frio,
	 * broken in vier" class of regression.
	 *
	 * @return array<string, array{string}>
	 */
	public static function themeProvider(): array
	{
		return [
			'frio' => ['frio'],
			'vier' => ['vier'],
		];
	}

	protected function basePath(): string
	{
		return dirname(__DIR__);
	}

	private function createSmarty(string $theme): FriendicaSmarty
	{
		$workDir = sys_get_temp_dir() . '/friendica-template-tests';

		if (!is_dir($workDir . '/compiled')) {
			mkdir($workDir . '/compiled', 0o777, true);
		}

		// Smarty resolves its template dirs relative to the current working directory.
		chdir($this->basePath());

		return new FriendicaSmarty($theme, [], $workDir, false);
	}

	/**
	 * Mirrors FriendicaSmartyEngine::getTemplateFile(): theme template wins over the
	 * shared one in view/templates/.
	 */
	private function resolveTemplate(string $template, string $theme): string
	{
		$filename = FriendicaSmarty::SMARTY3_TEMPLATE_FOLDER . '/' . $template;
		$themed   = sprintf('%s/view/theme/%s/%s', $this->basePath(), $theme, $filename);

		return file_exists($themed) ? $themed : sprintf('%s/view/%s', $this->basePath(), $filename);
	}
}
