<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Util\Html;

use DOMElement;
use DOMXPath;

/**
 * Markup rules that must hold for every Friendica template.
 *
 * The rules are defined once here and applied in two places:
 *
 *   - {@see \Friendica\Test\Unit\View\TemplateInvariantTest} scans the source of
 *     every .tpl file in view/ (scope SOURCE and BOTH).
 *   - {@see \Friendica\Test\TemplateTestCase::assertMarkupInvariants()} checks the
 *     rendered HTML of a template (scope RENDERED and BOTH).
 *
 * Rules deliberately assert *invariants*, never concrete markup. They therefore do
 * not need maintenance when templates are restructured - only when a template
 * actually breaks one of them.
 */
final class Invariants
{
	/** Checkable on the raw .tpl source (Smarty tags stripped). */
	public const SOURCE = 'source';
	/** Checkable only on rendered HTML, because Smarty branches would give false alarms. */
	public const RENDERED = 'rendered';
	/** Checkable on both. */
	public const BOTH = 'both';

	/**
	 * @var array<string, array{scope: string, xpath: string, problem: string, fix: string, reference?: string}>
	 */
	public const RULES = [
		'img-requires-alt' => [
			'scope'   => self::BOTH,
			'xpath'   => '//img[not(@alt)]',
			'problem' => "This <img> has no alt attribute.\n"
				. "Screen readers then read out the image URL instead of its meaning, and\n"
				. 'the image carries no information when it fails to load.',
			'fix' => "Add an alt attribute to the <img> tag:\n\n"
				. "    alt=\"{{\$item.description}}\"   when the template has a description variable\n"
				. "    alt=\"{{\$title}}\"             when a title or name describes the picture\n"
				. "    alt=\"\"                       when the image is purely decorative\n"
				. '                                 (icons, spacers, spinners, background graphics)',
			'reference' => 'https://developer.mozilla.org/docs/Web/HTML/Element/img#alt',
		],
		'no-nested-interactive' => [
			'scope' => self::BOTH,
			'xpath' => '//button//a | //button//button | //button//input | //button//select'
				. ' | //a//a | //a//button | //a//input | //a//select',
			'problem' => "A clickable element sits inside another clickable element.\n"
				. "Keyboard users cannot reach the inner one, and a mouse click triggers\n"
				. 'both - which browsers resolve differently.',
			'fix' => "Put the two elements next to each other instead of inside each other:\n\n"
				. "    <button>…</button>\n"
				. "    <a href=\"…\">…</a>\n\n"
				. 'If the outer element only provides layout, make it a <div> or <span>.',
			'reference' => 'https://developer.mozilla.org/docs/Web/HTML/Element/button#technical_summary',
		],
		'no-flow-content-in-button' => [
			'scope' => self::BOTH,
			'xpath' => '//button//div | //button//p | //button//ul | //button//ol'
				. ' | //button//section | //button//table | //button//h1 | //button//h2 | //button//h3',
			'problem' => "A block element (<div>, <p>, <ul>, …) sits inside a <button>.\n"
				. "A button may only contain text and inline elements (<span>, <i>, <img>).\n"
				. 'Assistive technology may skip or mis-announce the button label.',
			'fix' => "Replace the block element inside the <button> with an inline one:\n\n"
				. "    <button><div class=\"x\">…</div></button>     ✗\n"
				. "    <button><span class=\"x\">…</span></button>   ✓\n\n"
				. 'The CSS usually keeps working - add `display: block` to the class if needed.',
			'reference' => 'https://developer.mozilla.org/docs/Web/HTML/Element/button',
		],
		'no-empty-attribute' => [
			// SOURCE would flag every `title="{{$var}}"`, so this only makes sense once rendered.
			'scope'   => self::RENDERED,
			'xpath'   => '//*[@href=""] | //*[@src=""] | //*[@title=""] | //*[@id=""]',
			'problem' => "An attribute rendered as an empty string.\n"
				. 'That is almost always a misspelled template variable, e.g. {{$f1}} instead of {{$f.1}}.',
			'fix' => "Check the variable name in the template against the array the PHP side passes in.\n"
				. 'Smarty silently renders unknown variables as an empty string - it does not warn.',
		],
		'no-duplicate-id' => [
			// In the source, both branches of an {{if}}…{{else}} legitimately carry the same
			// id, and ids built from variables ({{$id}}) are not comparable at all.
			'scope'   => self::RENDERED,
			'xpath'   => '',
			'problem' => "The same id is used more than once in the output.\n"
				. "document.getElementById() and CSS #selectors only ever find the first one,\n"
				. 'so JavaScript silently acts on the wrong element.',
			'fix' => "Give the second element its own id, or - if it repeats per item -\n"
				. "append the item id the template already has:\n\n"
				. '    id="comment-edit-wrapper-{{$item.id}}"',
			'reference' => 'https://developer.mozilla.org/docs/Web/HTML/Global_attributes/id',
		],
	];

	/**
	 * Runs all rules of the given scope against a parsed document.
	 *
	 * @return Violation[]
	 */
	public static function check(DOMXPath $xpath, string $scope, string $location): array
	{
		$violations = [];

		foreach (self::RULES as $rule => $definition) {
			if ($definition['xpath'] === '') {
				continue;
			}

			if ($definition['scope'] !== $scope && $definition['scope'] !== self::BOTH) {
				continue;
			}

			foreach (Document::elements($xpath, $definition['xpath']) as $node) {
				$violations[] = new Violation($rule, $location, $node->getLineNo(), self::snippet($node));
			}
		}

		if ($scope === self::RENDERED) {
			$violations = array_merge($violations, self::checkDuplicateIds($xpath, $location));
		}

		return $violations;
	}

	/**
	 * Duplicate id attributes.
	 *
	 * Only checkable on rendered HTML: in the source, the two branches of an
	 * {{if}}…{{else}}…{{/if}} legitimately carry the same id, and ids built from
	 * variables ({{$id}}) are not comparable at all.
	 *
	 * @return Violation[]
	 */
	private static function checkDuplicateIds(DOMXPath $xpath, string $location): array
	{
		$seen       = [];
		$violations = [];

		foreach (Document::elements($xpath, '//*[@id]') as $node) {
			$id = $node->getAttribute('id');
			if ($id === '') {
				continue;
			}

			if (isset($seen[$id])) {
				$violations[] = new Violation('no-duplicate-id', $location, $node->getLineNo(), self::snippet($node));
			}

			$seen[$id] = true;
		}

		return $violations;
	}

	/**
	 * Human readable report for a set of violations, grouped by rule.
	 *
	 * Written for people who touch templates but do not read PHP every day: it names
	 * the file and line, shows the offending tag, explains the consequence and gives
	 * a copy-pasteable fix.
	 */
	public static function report(array $violations): string
	{
		$byRule = [];
		foreach ($violations as $violation) {
			$byRule[$violation->rule][] = $violation;
		}

		$out = '';
		foreach ($byRule as $rule => $group) {
			$definition = self::RULES[$rule];

			$out .= "\n" . str_repeat('=', 78) . "\n";
			$out .= sprintf("  %s  (%d %s)\n", $rule, count($group), count($group) === 1 ? 'finding' : 'findings');
			$out .= str_repeat('=', 78) . "\n\n";

			foreach ($group as $violation) {
				$out .= '  ' . $violation->where() . "\n";
				$out .= '      ' . $violation->snippet . "\n\n";
			}

			$out .= '  WHAT IS WRONG' . "\n";
			$out .= self::indent($definition['problem']) . "\n\n";
			$out .= '  HOW TO FIX IT' . "\n";
			$out .= self::indent($definition['fix']) . "\n";

			if (isset($definition['reference'])) {
				$out .= "\n  " . $definition['reference'] . "\n";
			}
		}

		return $out;
	}

	private static function indent(string $text): string
	{
		return implode("\n", array_map(
			fn (string $line): string => $line === '' ? '' : '    ' . $line,
			explode("\n", $text),
		));
	}

	/**
	 * Renders the opening tag of the offending element, shortened for readability.
	 */
	private static function snippet(DOMElement $node): string
	{
		$html = (string) $node->ownerDocument->saveHTML($node);
		$html = (string) preg_replace('/\s+/', ' ', $html);
		$open = (string) preg_replace('/^(<[^>]*>).*/s', '$1', $html);

		return mb_strlen($open) > 160 ? mb_substr($open, 0, 157) . '…' : $open;
	}
}
