<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Content\Text\BBCode;

use Friendica\Content\Text\BBCode\Video;
use Friendica\Test\MockedTestCase;

class VideoTest extends MockedTestCase
{
	public function dataVideo()
	{
		return [
			'youtube' => [
				'input'  => '[video]https://youtube.link/4523[/video]',
				'assert' => '[youtube]https://youtube.link/4523[/youtube]',
			],
			'youtu.be' => [
				'input'  => '[video]https://youtu.be.link/4523[/video]',
				'assert' => '[youtube]https://youtu.be.link/4523[/youtube]',
			],
			'vimeo' => [
				'input'  => '[video]https://vimeo.link/2343[/video]',
				'assert' => '[vimeo]https://vimeo.link/2343[/vimeo]',
			],
			'mixed' => [
				'input'  => '[video]https://vimeo.link/2343[/video] With other [b]string[/b] [video]https://youtu.be/blaa[/video]',
				'assert' => '[vimeo]https://vimeo.link/2343[/vimeo] With other [b]string[/b] [youtube]https://youtu.be/blaa[/youtube]',
			]
		];
	}

	/**
	 * Test if the BBCode is successfully transformed for video links
	 *
	 * @dataProvider dataVideo
	 */
	public function testTransform(string $input, string $assert)
	{
		$bbCodeVideo = new Video();

		self::assertEquals($assert, $bbCodeVideo->transform($input));
	}
}
