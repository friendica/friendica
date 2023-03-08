<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Test\src\Content\Text\BBCode;

use Friendica\Content\Text\BBCode\Video;
use Friendica\Test\MockedTest;

class VideoTest extends MockedTest
{
	public function dataVideo()
	{
		return [
			'youtube' => [
				'input' => '[video]https://youtube.link/4523[/video]',
				'assert' => '[youtube]https://youtube.link/4523[/youtube]',
			],
			'youtu.be' => [
				'input' => '[video]https://youtu.be.link/4523[/video]',
				'assert' => '[youtube]https://youtu.be.link/4523[/youtube]',
			],
			'vimeo' => [
				'input' => '[video]https://vimeo.link/2343[/video]',
				'assert' => '[vimeo]https://vimeo.link/2343[/vimeo]',
			],
			'mixed' => [
				'input' => '[video]https://vimeo.link/2343[/video] With other [b]string[/b] [video]https://youtu.be/blaa[/video]',
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
