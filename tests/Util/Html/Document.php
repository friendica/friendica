<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Util\Html;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Turns template sources and rendered HTML fragments into something queryable.
 */
final class Document
{
	/**
	 * Parses a raw .tpl source.
	 *
	 * Smarty constructs are replaced by placeholders instead of being deleted, so that
	 * `id="x-{{$id}}"` does not collapse into `id="x-"` for every item, and so that line
	 * numbers still match the file (multi-line constructs keep their line breaks).
	 */
	public static function fromTemplateSource(string $source): DOMXPath
	{
		$counter = 0;

		$html = preg_replace_callback(
			'/\{\{.*?\}\}/s',
			function (array $match) use (&$counter): string {
				return self::PLACEHOLDER . $counter++ . str_repeat("\n", substr_count($match[0], "\n"));
			},
			$source,
		);

		return self::parse((string) $html);
	}

	/**
	 * Parses rendered HTML.
	 */
	public static function fromHtml(string $html): DOMXPath
	{
		return self::parse($html);
	}

	/**
	 * Placeholder standing in for a Smarty construct. Contains no characters that would
	 * terminate a tag or an attribute value, so the surrounding markup stays parseable.
	 */
	public const PLACEHOLDER = 'SMARTYEXPR';

	/**
	 * All elements matching an XPath expression.
	 *
	 * @return DOMElement[]
	 */
	public static function elements(DOMXPath $xpath, string $query): array
	{
		$elements = [];

		foreach ($xpath->query($query) ?: [] as $node) {
			if ($node instanceof DOMElement) {
				$elements[] = $node;
			}
		}

		return $elements;
	}

	private static function parse(string $html): DOMXPath
	{
		$document = new DOMDocument();

		// Templates are fragments, so they are wrapped. No newline before the fragment:
		// that keeps DOMNode::getLineNo() aligned with the original file.
		$previous = libxml_use_internal_errors(true);
		$document->loadHTML(
			'<!DOCTYPE html><html><body>' . $html . '</body></html>',
			LIBXML_NOWARNING | LIBXML_NOERROR,
		);
		libxml_clear_errors();
		libxml_use_internal_errors($previous);

		return new DOMXPath($document);
	}
}
