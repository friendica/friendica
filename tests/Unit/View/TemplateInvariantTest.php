<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\View;

use Friendica\Test\Util\Html\Document;
use Friendica\Test\Util\Html\Invariants;
use Friendica\Test\Util\Html\Violation;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Checks the markup rules of {@see Invariants} against every template in view/.
 *
 * Friendica carries a backlog of existing violations, so the rules cannot simply be
 * switched on. The baseline file records how many findings each file is currently allowed
 * to have; anything on top of that fails. This is the same ratchet idea as
 * eslint-suppressions.json for JavaScript - counted per file *and* rule, so the failure
 * message can point at the file you touched instead of just saying "one more than before".
 *
 * Fixed a finding? Lock it in so it cannot come back:
 *
 *     UPDATE_TEMPLATE_BASELINE=1 composer run test:unit
 */
class TemplateInvariantTest extends TestCase
{
	private const BASELINE = 'template-invariants-baseline.json';

	/** Vendored code, kept in sync with eslint.config.mjs and .stylelintrc.json */
	private const VENDORED = [
		'view/asset/',
		'view/theme/frio/frameworks/',
	];

	private const UPDATE_COMMAND = 'UPDATE_TEMPLATE_BASELINE=1 composer run test:unit';

	public function testTemplatesHoldTheirMarkupInvariants(): void
	{
		$violations = [];
		foreach ($this->templates() as $template) {
			$source = (string) file_get_contents($this->basePath() . '/' . $template);
			$xpath  = Document::fromTemplateSource($source);

			$found = Invariants::check($xpath, Invariants::SOURCE, $template);

			$violations = array_merge($violations, $this->withSourceSnippets($found, $source));
		}

		$current  = $this->countByRuleAndFile($violations);
		$baseline = $this->readBaseline();

		if (getenv('UPDATE_TEMPLATE_BASELINE')) {
			$this->writeBaseline($current);
			self::markTestSkipped(sprintf('Baseline rewritten (%s).', self::BASELINE));
		}

		$added = $this->added($current, $baseline);
		if ($added !== []) {
			self::fail($this->reportNewFindings($added, $violations));
		}

		$fixed = $this->fixed($current, $baseline);
		if ($fixed !== []) {
			self::fail($this->reportFixedFindings($fixed));
		}

		self::assertSame($baseline, $current);
	}

	/**
	 * Replaces the reconstructed tag with the actual line from the .tpl file, so the
	 * failure message shows the Smarty code that is really in the editor rather than the
	 * placeholders this check works on internally.
	 *
	 * @param Violation[] $violations
	 *
	 * @return Violation[]
	 */
	private function withSourceSnippets(array $violations, string $source): array
	{
		$lines = explode("\n", $source);

		return array_map(
			function (Violation $violation) use ($lines): Violation {
				$line = $lines[$violation->line - 1] ?? null;

				return $line === null
					? $violation
					: new Violation($violation->rule, $violation->location, $violation->line, trim($line));
			},
			$violations,
		);
	}

	/**
	 * Only reports what got *worse*, so an unrelated pre-existing finding in the same file
	 * never lands in someone else's failure message.
	 *
	 * @param array<string, array<string, int>> $current
	 * @param array<string, array<string, int>> $baseline
	 *
	 * @return array<string, array<string, array{allowed: int, found: int}>>
	 */
	private function added(array $current, array $baseline): array
	{
		$added = [];

		foreach ($current as $rule => $files) {
			foreach ($files as $file => $found) {
				$allowed = $baseline[$rule][$file] ?? 0;
				if ($found > $allowed) {
					$added[$rule][$file] = ['allowed' => $allowed, 'found' => $found];
				}
			}
		}

		return $added;
	}

	/**
	 * @param array<string, array<string, int>> $current
	 * @param array<string, array<string, int>> $baseline
	 *
	 * @return array<string, array<string, array{was: int, now: int}>>
	 */
	private function fixed(array $current, array $baseline): array
	{
		$fixed = [];

		foreach ($baseline as $rule => $files) {
			foreach ($files as $file => $was) {
				$now = $current[$rule][$file] ?? 0;
				if ($now < $was) {
					$fixed[$rule][$file] = ['was' => $was, 'now' => $now];
				}
			}
		}

		return $fixed;
	}

	/**
	 * @param array<string, array<string, array{allowed: int, found: int}>> $added
	 * @param Violation[]                                                  $violations
	 */
	private function reportNewFindings(array $added, array $violations): string
	{
		$relevant = array_filter(
			$violations,
			fn (Violation $violation): bool => isset($added[$violation->rule][$violation->location]),
		);

		$total = 0;
		foreach ($added as $files) {
			foreach ($files as $counts) {
				$total += $counts['found'] - $counts['allowed'];
			}
		}

		return sprintf("\n%d new markup problem%s in the templates:\n", $total, $total === 1 ? '' : 's')
			. Invariants::report(array_values($relevant))
			. "\n" . str_repeat('-', 78) . "\n"
			. "Note: all findings of the affected rule are listed for the file, including any\n"
			. "that were already there. Fix the one on the line you changed.\n\n"
			. "If it genuinely cannot be fixed right now, record it in " . self::BASELINE . ":\n"
			. '    ' . self::UPDATE_COMMAND . "\n";
	}

	/**
	 * @param array<string, array<string, array{was: int, now: int}>> $fixed
	 */
	private function reportFixedFindings(array $fixed): string
	{
		$lines = '';
		foreach ($fixed as $rule => $files) {
			foreach ($files as $file => $counts) {
				$lines .= sprintf("    %s  [%s]  %d -> %d\n", $file, $rule, $counts['was'], $counts['now']);
			}
		}

		return "\nMarkup problems were fixed - nice:\n\n" . $lines
			. "\nPlease lock that in, so they cannot come back unnoticed:\n"
			. '    ' . self::UPDATE_COMMAND . "\n";
	}

	/**
	 * @param Violation[] $violations
	 *
	 * @return array<string, array<string, int>>
	 */
	private function countByRuleAndFile(array $violations): array
	{
		$counts = [];

		foreach ($violations as $violation) {
			$counts[$violation->rule][$violation->location] ??= 0;
			$counts[$violation->rule][$violation->location]++;
		}

		return $this->sorted($counts);
	}

	/**
	 * @return array<string, array<string, int>>
	 */
	private function readBaseline(): array
	{
		$file = $this->basePath() . '/' . self::BASELINE;

		if (!file_exists($file)) {
			return [];
		}

		return $this->sorted((array) json_decode((string) file_get_contents($file), true));
	}

	/**
	 * @param array<string, array<string, int>> $counts
	 */
	private function writeBaseline(array $counts): void
	{
		file_put_contents(
			$this->basePath() . '/' . self::BASELINE,
			json_encode($counts, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
		);
	}

	/**
	 * Stable ordering, so the baseline produces readable diffs.
	 *
	 * @param array<string, array<string, int>> $counts
	 *
	 * @return array<string, array<string, int>>
	 */
	private function sorted(array $counts): array
	{
		ksort($counts);
		foreach ($counts as &$files) {
			ksort($files);
		}

		return $counts;
	}

	/**
	 * @return string[] Paths relative to the repository root
	 */
	private function templates(): array
	{
		$templates = [];

		$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->basePath() . '/view'));
		foreach ($iterator as $file) {
			if (!$file->isFile() || $file->getExtension() !== 'tpl') {
				continue;
			}

			$relative = str_replace($this->basePath() . '/', '', $file->getPathname());

			foreach (self::VENDORED as $vendored) {
				if (str_starts_with($relative, $vendored)) {
					continue 2;
				}
			}

			$templates[] = $relative;
		}

		sort($templates);

		return $templates;
	}

	private function basePath(): string
	{
		return dirname(__DIR__, 3);
	}
}
