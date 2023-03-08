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

namespace Friendica\Test\src\Network;

use Friendica\Network\Probe;
use Friendica\Test\DiceHttpMockHandlerTrait;
use Friendica\Test\MockedTest;
use GuzzleHttp\Middleware;

class ProbeTest extends MockedTest
{
	use DiceHttpMockHandlerTrait;

	protected function setUp(): void
	{
		parent::setUp();

		$this->setupHttpMockHandler();
	}

	protected function tearDown(): void
	{
		$this->tearDownHandler();

		parent::tearDown();
	}

	const TEMPLATENOBASE = '
<!DOCTYPE html>
<html lang="en-us">
<head>
    <title>Example Blog</title>
    <link href="{{$link}}" rel="alternate" type="application/rss+xml" title="Example Blog" />
	<link href="{{$link}}" rel="feed" type="application/rss+xml" title="Example Blog" />
</head>
<body>
    <p>Hello World!</p>
</body>
</html>';

	const TEMPLATEBASE = '
<!DOCTYPE html>
<html lang="en-us">
<head>
    <title>Example Blog</title>
    <link href="{{$link}}" rel="alternate" type="application/rss+xml" title="Example Blog" />
	<link href="{{$link}}" rel="feed" type="application/rss+xml" title="Example Blog" />
    <base href="{{$url}}">
</head>
<body>
    <p>Hello World!</p>
</body>
</html>';

	const EXPECTED = [
		'https://example.org/path/to/blog/index.php' => [
			'index.xml'               => 'https://example.org/path/to/blog/index.xml',
			'./index.xml'             => 'https://example.org/path/to/blog/index.xml',
			'../index.xml'            => 'https://example.org/path/to/index.xml',
			'/index.xml'              => 'https://example.org/index.xml',
			'//example.com/index.xml' => 'https://example.com/index.xml',
		],
		'https://example.org/path/to/blog/' => [
			'index.xml'               => 'https://example.org/path/to/blog/index.xml',
			'./index.xml'             => 'https://example.org/path/to/blog/index.xml',
			'../index.xml'            => 'https://example.org/path/to/index.xml',
			'/index.xml'              => 'https://example.org/index.xml',
			'//example.com/index.xml' => 'https://example.com/index.xml',
		],
		'https://example.org/blog/' => [
			'index.xml'               => 'https://example.org/blog/index.xml',
			'./index.xml'             => 'https://example.org/blog/index.xml',
			'../index.xml'            => 'https://example.org/index.xml',
			'/index.xml'              => 'https://example.org/index.xml',
			'//example.com/index.xml' => 'https://example.com/index.xml',
		],
		'https://example.org' => [
			'index.xml'               => 'https://example.org/index.xml',
			'./index.xml'             => 'https://example.org/index.xml',
			'../index.xml'            => 'https://example.org/index.xml',
			'/index.xml'              => 'https://example.org/index.xml',
			'//example.com/index.xml' => 'https://example.com/index.xml',
		],
	];

	private function replaceMacros($template, $vars)
	{
		foreach ($vars as $var => $value) {
			$template = str_replace('{{' . $var . '}}', $value, $template);
		}

		return $template;
	}

	/**
	 * @small
	 */
	public function testGetFeedLinkNoBase()
	{
		foreach (self::EXPECTED as $url => $hrefs) {
			foreach ($hrefs as $href => $expected) {
				$body = $this->replaceMacros(self::TEMPLATENOBASE, ['$link' => $href]);

				$feedLink = Probe::getFeedLink($url, $body);

				self::assertEquals($expected, $feedLink, 'base url = ' . $url . ' | href = ' . $href);
			}
		}
	}

	/**
	 * @small
	 */
	public function testGetFeedLinkBase()
	{
		foreach (self::EXPECTED as $url => $hrefs) {
			foreach ($hrefs as $href => $expected) {
				$body = $this->replaceMacros(self::TEMPLATEBASE, ['$url' => $url, '$link' => $href]);

				$feedLink = Probe::getFeedLink('http://example.com', $body);

				self::assertEquals($expected, $feedLink, 'base url = ' . $url . ' | href = ' . $href);
			}
		}
	}

	public function dataCleanUri(): array
	{
		return [
			'@-first' => [
				'expected' => 'Artists4Future_Muenchen@climatejustice.global',
				'uri'      => '@Artists4Future_Muenchen@climatejustice.global',
			],
			'no-scheme-no-fragment' => [
				'expected' => 'example.com/path?arg=value',
				'uri'      => 'example.com/path?arg=value',
			],
			/* This case makes little sense, both in our expectation of receiving it in any context and in the way we
			 * do not change it in Probe::cleanUri, but it doesn't seem to be the source of any terrible security hole.
			 */
			'no-scheme-fragment' => [
				'expected' => 'example.com/path?arg=value#fragment',
				'uri'      => 'example.com/path?arg=value#fragment',
			],
			'scheme-no-fragment' => [
				'expected' => 'https://example.com/path?arg=value',
				'uri'      => 'https://example.com/path?arg=value#fragment',
			],
			'scheme-fragment' => [
				'expected' => 'https://example.com/path?arg=value',
				'uri'      => 'https://example.com/path?arg=value#fragment',
			],
		];
	}

	/**
	 * @dataProvider dataCleanUri
	 */
	public function testCleanUri(string $expected, string $uri)
	{
		self::assertEquals($expected, Probe::cleanURI($uri));
	}

	public function dataUri(): array
	{
		return [
			'Artists4Future_Muenchen@climatejustice.global' => [
				'uri'         => 'Artists4Future_Muenchen@climatejustice.global',
				'assertInfos' => [
					'name'         => 'Artists4Future München',
					'nick'         => 'Artists4Future_Muenchen',
					'url'          => 'https://climatejustice.global/users/Artists4Future_Muenchen',
					'alias'        => 'https://climatejustice.global/@Artists4Future_Muenchen',
					'photo'        => 'https://cdn.masto.host/climatejusticeglobal/accounts/avatars/000/021/220/original/05ee9e827a5b47fc.jpg',
					'header'       => 'https://cdn.masto.host/climatejusticeglobal/accounts/headers/000/021/220/original/9b98b75cf696cd11.jpg',
					'account-type' => 0,
					'about'        => 'Wir sind Künstler oder einfach gerne kreativ tätig und setzen uns unabhängig von politischen Parteien für den Klimaschutz ein. Die Bedingungen zu schaffen, die die [url=https://climatejustice.global/tags/Klimakrise]#Klimakrise[/url] verhindern/eindämmen (gemäß den Forderungen der [url=https://climatejustice.global/tags/Fridays4Future]#Fridays4Future[/url]) ist Aufgabe der Politik, muss aber gesamtgesellschaftlich getragen werden. Mit unseren künstlerischen Aktionen wollen wir einen anderen Zugang anbieten für wissenschaftlich rationale Argumente, speziell zur Erderwärmung und ihre Konsequenzen.',
					'hide'         => 0,
					'batch'        => 'https://climatejustice.global/inbox',
					'notify'       => 'https://climatejustice.global/users/Artists4Future_Muenchen/inbox',
					'poll'         => 'https://climatejustice.global/users/Artists4Future_Muenchen/outbox',
					'subscribe'    => 'https://climatejustice.global/authorize_interaction?uri={uri}',
					'following'    => 'https://climatejustice.global/users/Artists4Future_Muenchen/following',
					'followers'    => 'https://climatejustice.global/users/Artists4Future_Muenchen/followers',
					'inbox'        => 'https://climatejustice.global/users/Artists4Future_Muenchen/inbox',
					'outbox'       => 'https://climatejustice.global/users/Artists4Future_Muenchen/outbox',
					'sharedinbox'  => 'https://climatejustice.global/inbox',
					'priority'     => 0,
					'network'      => 'apub',
					'pubkey'       => '-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA6pYKPuDKb+rmBB869uPV
uLYFPosGxMUfenWqfWmFKzEqJ87rAft0IQDAL6dCoYE55ov/lEDNROhasTZLirZf
M5b7/1JmwMrAfEiaciuYqDWT3/yDpnekOIdzP5iSClg4zt7e6HRFuClqo4+b6hIE
DTMV4ksItvq/92MIu62pZ2SZr5ADPPZ/914lJ86hIH5BanbE8ZFzDS9vJA7V74rt
Vvkr5c/OiUyuODNYApSl87Ez8cuj8Edt89YWkDCajQn3EkmXGeJY/VRjEDfcyk6r
AvdUa0ArjXud3y3NkakVFZ0d7tmB20Vn9s/CfYHU8FXzbI1kFkov2BX899VVP5Ay
xQIDAQAB
-----END PUBLIC KEY-----',
					'manually-approve' => 0,
					'baseurl'          => 'https://climatejustice.global',
				]
			]
		];
	}

	/**
	 * @dataProvider dataUri
	 */
	public function testProbeUri(string $uri, array $assertInfos)
	{
		self::markTestIncomplete('hard work due mocking 19 different http-requests');

		/**
		 * Requests:
		 *
		 * GET : https://climatejustice.global/.well-known/webfinger?resource=acct:Artists4Future_Muenchen%40climatejustice.global
		 * 200
		 * GET : http://localhost/.well-known/nodeinfo
		 * 200
		 * GET : http://localhost/statistics.json
		 * 404
		 * GET : http://localhost
		 * 200
		 * GET : http://localhost/friendica/json
		 * 404
		 * GET : http://localhost/friendika/json
		 * 404
		 * GET : http://localhost/poco
		 * 403
		 * GET : http://localhost/api/v1/directory?limit=1
		 * 200
		 * GET : http://localhost/.well-known/x-social-relay
		 * 200
		 * GET : http://localhost/friendica
		 * 404
		 * GET : https://climatejustice.global/users/Artists4Future_Muenchen
		 * 200
		 * GET : https://climatejustice.global/users/Artists4Future_Muenchen/following
		 * 200
		 * GET : https://climatejustice.global/users/Artists4Future_Muenchen/followers
		 * 200
		 * GET : https://climatejustice.global/users/Artists4Future_Muenchen/outbox
		 * 200
		 * GET : https://climatejustice.global/.well-known/nodeinfo
		 * 200
		 * GET : https://climatejustice.global/nodeinfo/2.0
		 * 200
		 * GET : https://climatejustice.global/poco
		 * 404
		 * GET : https://climatejustice.global/api/v1/directory?limit=1
		 * 200
		 * GET : https://climatejustice.global/.well-known/webfinger?resource=acct%3AArtists4Future_Muenchen%40climatejustice.global
		 * 200
		 *
		 */

		$container = [];
		$history   = Middleware::history($container);

		$this->httpRequestHandler->push($history);

		self::assertArraySubset($assertInfos, Probe::uri($uri, '', 0));

		// Iterate over the requests and responses
		foreach ($container as $transaction) {
			echo $transaction['request']->getMethod() . " : " . $transaction['request']->getUri() . PHP_EOL;
			//> GET, HEAD
			if ($transaction['response']) {
				echo $transaction['response']->getStatusCode() . PHP_EOL;
			//> 200, 200
			} elseif ($transaction['error']) {
				echo $transaction['error'];
				//> exception
			}
		}
	}
}
