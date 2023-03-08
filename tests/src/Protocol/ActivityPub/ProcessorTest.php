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

namespace Friendica\Test\src\Protocol\ActivityPub;

use PHPUnit\Framework\TestCase;

class ProcessorTest extends TestCase
{
	public function dataNormalizeMentionLinks(): array
	{
		return [
			'one-link-@' => [
				'expected' => '@[url=https://example.com]Example[/url]',
				'body'     => '[url=https://example.com]@Example[/url]',
			],
			'one-link-#' => [
				'expected' => '#[url=https://example.com]Example[/url]',
				'body'     => '[url=https://example.com]#Example[/url]',
			],
			'one-link-!' => [
				'expected' => '![url=https://example.com]Example[/url]',
				'body'     => '[url=https://example.com]!Example[/url]',
			],
			'wrong-hash-char' => [
				'expected' => '[url=https://example.com]%Example[/url]',
				'body'     => '[url=https://example.com]%Example[/url]',
			],
			'multiple-links' => [
				'expected' => '@[url=https://example.com]Example[/url] #[url=https://example.com]Example[/url] ![url=https://example.com]Example[/url]',
				'body'     => '[url=https://example.com]@Example[/url] [url=https://example.com]#Example[/url] [url=https://example.com]!Example[/url]',
			],
			'already-correct-format' => [
				'expected' => '@[url=https://example.com]Example[/url] #[url=https://example.com]Example[/url] ![url=https://example.com]Example[/url]',
				'body'     => '@[url=https://example.com]Example[/url] #[url=https://example.com]Example[/url] ![url=https://example.com]Example[/url]',
			],
			'mixed-format' => [
				'expected' => '@[url=https://example.com]Example[/url] #[url=https://example.com]Example[/url] ![url=https://example.com]Example[/url] @[url=https://example.com]Example[/url] #[url=https://example.com]Example[/url] ![url=https://example.com]Example[/url]',
				'body'     => '[url=https://example.com]@Example[/url] [url=https://example.com]#Example[/url] [url=https://example.com]!Example[/url] @[url=https://example.com]Example[/url] #[url=https://example.com]Example[/url] ![url=https://example.com]Example[/url]',
			],
		];
	}

	/**
	 * @dataProvider dataNormalizeMentionLinks
	 *
	 * @param string $expected
	 * @param string $body
	 */
	public function testNormalizeMentionLinks(string $expected, string $body)
	{
		$this->assertEquals($expected, ProcessorMock::normalizeMentionLinks($body));
	}

	public function dataAddMentionLinks(): array
	{
		return [
			'issue-10603' => [
				'expected' => '@[url=https://social.wake.st/users/liaizon]liaizon@social.wake.st[/url] @[url=https://friendica.mrpetovan.com/profile/hypolite]hypolite@friendica.mrpetovan.com[/url] yes<br /><br />',
				'body'     => '@liaizon@social.wake.st @hypolite@friendica.mrpetovan.com yes<br /><br />',
				'tags'     => [
					[
						'type' => 'Mention',
						'href' => 'https://social.wake.st/users/liaizon',
						'name' => '@liaizon@social.wake.st'
					],
					[
						'type' => 'Mention',
						'href' => 'https://friendica.mrpetovan.com/profile/hypolite',
						'name' => '@hypolite@friendica.mrpetovan.com'
					]
				],
			],
			'issue-10617' => [
				'expected' => '@[url=https://mastodon.technology/@sergey_m]sergey_m[/url]',
				'body'     => '@[url=https://mastodon.technology/@sergey_m]sergey_m[/url]',
				'tags'     => [
					[
						'type' => 'Mention',
						'href' => 'https://mastodon.technology/@sergey_m',
						'name' => '@sergey_m'
					],
				],
			],
		];
	}

	/**
	 * @dataProvider dataAddMentionLinks
	 *
	 * @param string $expected
	 * @param string $body
	 * @param array $tags
	 */
	public function testAddMentionLinks(string $expected, string $body, array $tags)
	{
		$this->assertEquals($expected, ProcessorMock::addMentionLinks($body, $tags));
	}
}
