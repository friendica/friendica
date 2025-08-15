<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Content\Text;

use Exception;
use Friendica\Content\Text\Markdown;
use Friendica\Test\FixtureTestCase;

class MarkdownTest extends FixtureTestCase
{
	public function dataMarkdown()
	{
		$inputFiles = glob(__DIR__ . '/../../../datasets/content/text/markdown/*.md');

		$data = [];

		foreach ($inputFiles as $file) {
			$data[str_replace('.md', '', $file)] = [
				'input'    => file_get_contents($file),
				'expected' => file_get_contents(str_replace('.md', '.html', $file))
			];
		}

		return $data;
	}

	/**
	 * Test convert different input Markdown text into HTML
	 *
	 * @dataProvider dataMarkdown
	 *
	 * @param string $input    The Markdown text to test
	 * @param string $expected The expected HTML output
	 *
	 * @throws Exception
	 */
	public function testConvert(string $input, string $expected)
	{
		$output = Markdown::convert($input);

		self::assertEquals($expected, $output);
	}

	public function dataMarkdownText()
	{
		return [
			'bug-8358-double-decode' => [
				'expectedBBCode' => 'with the <sup> and </sup> tag',
				'markdown'       => 'with the &lt;sup&gt; and &lt;/sup&gt; tag',
			],
			/** @see https://github.com/friendica/friendica/pull/14940 */
			'task-14940-youtube-watch-with-www' => [
				'expectedBBCode' => '[youtube]hfwbmTzBFT0[/youtube]',
				'markdown'       => '[url=https://www.youtube.com/watch?v=hfwbmTzBFT0]https://www.youtube.com/watch?v=hfwbmTzBFT0[/url]',
			],
			'task-14940-youtube-watch-without-www' => [
				'expectedBBCode' => '[youtube]hfwbmTzBFT0[/youtube]',
				'markdown'       => '[url=https://youtube.com/watch?v=hfwbmTzBFT0]https://youtube.com/watch?v=hfwbmTzBFT0[/url]',
			],
			'task-14940-youtube-shorts-with-www' => [
				'expectedBBCode' => '[youtube]hfwbmTzBFT0[/youtube]',
				'markdown'       => '[url=https://www.youtube.com/shorts/hfwbmTzBFT0]https://www.youtube.com/shorts/hfwbmTzBFT0[/url]',
			],
			'task-14940-youtube-shorts-without-www' => [
				'expectedBBCode' => '[youtube]hfwbmTzBFT0[/youtube]',
				'markdown'       => '[url=https://youtube.com/shorts/hfwbmTzBFT0]https://youtube.com/shorts/hfwbmTzBFT0[/url]',
			],
			'task-14940-youtube-embed-with-www' => [
				'expectedBBCode' => '[youtube]hfwbmTzBFT0[/youtube]',
				'markdown'       => '[url=https://www.youtube.com/embed/hfwbmTzBFT0]https://www.youtube.com/embed/hfwbmTzBFT0[/url]',
			],
			'task-14940-youtube-embed-without-www' => [
				'expectedBBCode' => '[youtube]hfwbmTzBFT0[/youtube]',
				'markdown'       => '[url=https://youtube.com/embed/hfwbmTzBFT0]https://youtube.com/embed/hfwbmTzBFT0[/url]',
			],
			'task-14940-youtube-mobile' => [
				'expectedBBCode' => '[youtube]hfwbmTzBFT0[/youtube]',
				'markdown'       => '[url=https://m.youtube.com/watch?v=hfwbmTzBFT0]https://m.youtube.com/watch?v=hfwbmTzBFT0[/url]',
			],
			// @todo - should we really ignore the URL content in favor of parsing the link of the body?
			'task-14940-vimeo-custom-url' => [
				'expectedBBCode' => '[vimeo]2345345[/vimeo]',
				'markdown'       => '[url=https://no.thing]https://vimeo.com/2345345[/url]',
			],
			'task-14940-vimeo-custom-text' => [
				'expectedBBCode' => '[vimeo]2345345[/vimeo]',
				'markdown'       => '[url=https://vimeo.com/2345345]CustomText[/url]',
			],
			'task-14940-player-vimeo' => [
				'expectedBBCode' => '[vimeo]2345345[/vimeo]',
				'markdown'       => '[url=https://player.vimeo.com/video/2345345]https://player.vimeo.com/video/2345345[/url]',
			],
		];
	}

	/**
	 * Test convert Markdown to BBCode
	 *
	 * @dataProvider dataMarkdownText
	 *
	 * @param string $expectedBBCode Expected BBCode output
	 * @param string $html           Markdown text
	 */
	public function testToBBCode(string $expectedBBCode, string $html)
	{
		$actual = Markdown::toBBCode($html);

		self::assertEquals($expectedBBCode, $actual);
	}
}
