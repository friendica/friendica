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
				'input'  => '[video]https://youtube.com/4523[/video]',
				'assert' => '[embed]https://youtube.com/4523[/embed]',
			],
			'youtu.be' => [
				'input'  => '[video]https://youtu.be/4523[/video]',
				'assert' => '[embed]https://youtu.be/4523[/embed]',
			],
			'vimeo' => [
				'input'  => '[video]https://vimeo.com/2343[/video]',
				'assert' => '[embed]https://vimeo.com/2343[/embed]',
			],
			'dailymotion.com' => [
				'input'  => '[video]https://dailymotion.com/2343[/video]',
				'assert' => '[embed]https://dailymotion.com/2343[/embed]',
			],
			'dai.ly' => [
				'input'  => '[video]https://dai.ly/2343[/video]',
				'assert' => '[embed]https://dai.ly/2343[/embed]',
			],
			'mixed' => [
				'input'  => '[video]https://vimeo.com/2343[/video] With other [b]string[/b] [video]https://youtu.be/blaa[/video]',
				'assert' => '[embed]https://vimeo.com/2343[/embed] With other [b]string[/b] [embed]https://youtu.be/blaa[/embed]',
			],
			'negative' => [
				'input'  => '[video]https://some.video.link/video.mp4[/video]',
				'assert' => '[video]https://some.video.link/video.mp4[/video]',
			],
			'invalid' => [
				'input'  => '[video]invalid.link/video.mp4[/video]',
				'assert' => '[video]invalid.link/video.mp4[/video]',
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
