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

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\System;
use Friendica\DI;
use Friendica\Model\Photo;
use Friendica\Model\User;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Protocol\ActivityNamespace;
use Friendica\Protocol\Salmon;
use Friendica\Util\XML;

/**
 * Prints responses to /.well-known/webfinger  or /xrd requests
 */
class Xrd extends BaseModule
{
	protected function rawContent(array $request = [])
	{
		// @TODO: Replace with parameter from router
		if (DI::args()->getArgv()[0] == 'xrd') {
			if (empty($_GET['uri'])) {
				return;
			}

			$uri = urldecode(trim($_GET['uri']));
			if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/jrd+json') !== false)  {
				$mode = Response::TYPE_JSON;
			} else {
				$mode = Response::TYPE_XML;
			}
		} else {
			if (empty($_GET['resource'])) {
				return;
			}

			$uri = urldecode(trim($_GET['resource']));
			if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/xrd+xml') !== false)  {
				$mode = Response::TYPE_XML;
			} else {
				$mode = Response::TYPE_JSON;
			}
		}

		if (substr($uri, 0, 4) === 'http') {
			$name = ltrim(basename($uri), '~');
		} else {
			$local = str_replace('acct:', '', $uri);
			if (substr($local, 0, 2) == '//') {
				$local = substr($local, 2);
			}

			$name = substr($local, 0, strpos($local, '@'));
		}

		if ($name == User::getActorName()) {
			$owner = User::getSystemAccount();
			if (empty($owner)) {
				throw new NotFoundException('System account was not found. Please setup your Friendica installation properly.');
			}
			$this->printSystemJSON($owner);
		} else {
			$owner = User::getOwnerDataByNick($name);
			if (empty($owner)) {
				DI::logger()->notice('No owner data for user id', ['uri' => $uri, 'name' => $name]);
				throw new NotFoundException('Owner was not found for user->uid=' . $name);
			}

			$alias = str_replace('/profile/', '/~', $owner['url']);

			$avatar = Photo::selectFirst(['type'], ['uid' => $owner['uid'], 'profile' => true]);
		}

		if (empty($avatar)) {
			$avatar = ['type' => 'image/jpeg'];
		}

		if ($mode == Response::TYPE_XML) {
			$this->printXML($alias, $owner, $avatar);
		} else {
			$this->printJSON($alias, $owner, $avatar);
		}
	}

	private function printSystemJSON(array $owner)
	{
		$baseURL = $this->baseUrl;
		$json = [
			'subject' => 'acct:' . $owner['addr'],
			'aliases' => [$owner['url']],
			'links'   => [
				[
					'rel'  => 'http://webfinger.net/rel/profile-page',
					'type' => 'text/html',
					'href' => $owner['url'],
				],
				[
					'rel'  => 'self',
					'type' => 'application/activity+json',
					'href' => $owner['url'],
				],
				[
					'rel'      => 'http://ostatus.org/schema/1.0/subscribe',
					'template' => $baseURL . '/contact/follow?url={uri}',
				],
				[
					'rel'  => ActivityNamespace::FEED,
					'type' => 'application/atom+xml',
					'href' => $owner['poll'] ?? $baseURL,
				],
				[
					'rel'  => 'salmon',
					'href' => $baseURL . '/salmon/' . $owner['nickname'],
				],
				[
					'rel'  => 'http://microformats.org/profile/hcard',
					'type' => 'text/html',
					'href' => $baseURL . '/hcard/' . $owner['nickname'],
				],
				[
					'rel'  => 'http://joindiaspora.com/seed_location',
					'type' => 'text/html',
					'href' => $baseURL,
				],
			]
		];
		header('Access-Control-Allow-Origin: *');
		System::jsonExit($json, 'application/jrd+json; charset=utf-8');
	}

	private function printJSON(string $alias, array $owner, array $avatar)
	{
		$baseURL = $this->baseUrl;

		$json = [
			'subject' => 'acct:' . $owner['addr'],
			'aliases' => [
				$alias,
				$owner['url'],
			],
			'links'   => [
				[
					'rel'  => ActivityNamespace::DFRN ,
					'href' => $owner['url'],
				],
				[
					'rel'  => ActivityNamespace::FEED,
					'type' => 'application/atom+xml',
					'href' => $owner['poll'],
				],
				[
					'rel'  => 'http://webfinger.net/rel/profile-page',
					'type' => 'text/html',
					'href' => $owner['url'],
				],
				[
					'rel'  => 'self',
					'type' => 'application/activity+json',
					'href' => $owner['url'],
				],
				[
					'rel'  => 'http://microformats.org/profile/hcard',
					'type' => 'text/html',
					'href' => $baseURL . '/hcard/' . $owner['nickname'],
				],
				[
					'rel'  => 'http://webfinger.net/rel/avatar',
					'type' => $avatar['type'],
					'href' => User::getAvatarUrl($owner),
				],
				[
					'rel'  => 'http://joindiaspora.com/seed_location',
					'type' => 'text/html',
					'href' => $baseURL,
				],
				[
					'rel'  => 'salmon',
					'href' => $baseURL . '/salmon/' . $owner['nickname'],
				],
				[
					'rel'  => 'http://salmon-protocol.org/ns/salmon-replies',
					'href' => $baseURL . '/salmon/' . $owner['nickname'],
				],
				[
					'rel'  => 'http://salmon-protocol.org/ns/salmon-mention',
					'href' => $baseURL . '/salmon/' . $owner['nickname'] . '/mention',
				],
				[
					'rel'      => 'http://ostatus.org/schema/1.0/subscribe',
					'template' => $baseURL . '/contact/follow?url={uri}',
				],
				[
					'rel'  => 'magic-public-key',
					'href' => 'data:application/magic-public-key,' . Salmon::salmonKey($owner['spubkey']),
				],
				[
					'rel'  => 'http://purl.org/openwebauth/v1',
					'type' => 'application/x-zot+json',
					'href' => $baseURL . '/owa',
				],
			],
		];

		header('Access-Control-Allow-Origin: *');
		System::jsonExit($json, 'application/jrd+json; charset=utf-8');
	}

	private function printXML(string $alias, array $owner, array $avatar)
	{
		$baseURL = $this->baseUrl;

		$xmlString = XML::fromArray([
			'XRD' => [
				'@attributes' => [
					'xmlns'    => 'http://docs.oasis-open.org/ns/xri/xrd-1.0',
				],
				'Subject' => 'acct:' . $owner['addr'],
				'1:Alias' => $owner['url'],
				'2:Alias' => $alias,
				'1:link' => [
					'@attributes' => [
						'rel'  => 'http://purl.org/macgirvin/dfrn/1.0',
						'href' => $owner['url']
					]
				],
				'2:link' => [
					'@attributes' => [
						'rel'  => 'http://schemas.google.com/g/2010#updates-from',
						'type' => 'application/atom+xml',
						'href' => $owner['poll']
					]
				],
				'3:link' => [
					'@attributes' => [
						'rel'  => 'http://webfinger.net/rel/profile-page',
						'type' => 'text/html',
						'href' => $owner['url']
					]
				],
				'4:link' => [
					'@attributes' => [
						'rel'  => 'http://microformats.org/profile/hcard',
						'type' => 'text/html',
						'href' => $baseURL . '/hcard/' . $owner['nickname']
					]
				],
				'5:link' => [
					'@attributes' => [
						'rel'  => 'http://webfinger.net/rel/avatar',
						'type' => $avatar['type'],
						'href' => User::getAvatarUrl($owner)
					]
				],
				'6:link' => [
					'@attributes' => [
						'rel'  => 'http://joindiaspora.com/seed_location',
						'type' => 'text/html',
						'href' => $baseURL
					]
				],
				'7:link' => [
					'@attributes' => [
						'rel'  => 'salmon',
						'href' => $baseURL . '/salmon/' . $owner['nickname']
					]
				],
				'8:link' => [
					'@attributes' => [
						'rel'  => 'http://salmon-protocol.org/ns/salmon-replies',
						'href' => $baseURL . '/salmon/' . $owner['nickname']
					]
				],
				'9:link' => [
					'@attributes' => [
						'rel'  => 'http://salmon-protocol.org/ns/salmon-mention',
						'href' => $baseURL . '/salmon/' . $owner['nickname'] . '/mention'
					]
				],
				'10:link' => [
					'@attributes' => [
						'rel'  => 'http://ostatus.org/schema/1.0/subscribe',
						'template' => $baseURL . '/contact/follow?url={uri}'
					]
				],
				'11:link' => [
					'@attributes' => [
						'rel'  => 'magic-public-key',
						'href' => 'data:application/magic-public-key,' . Salmon::salmonKey($owner['spubkey'])
					]
				],
				'12:link' => [
					'@attributes' => [
						'rel'  => 'http://purl.org/openwebauth/v1',
						'type' => 'application/x-zot+json',
						'href' => $baseURL . '/owa'
					]
				],
			],
		]);

		header('Access-Control-Allow-Origin: *');

		System::httpExit($xmlString, Response::TYPE_XML, 'application/xrd+xml');
	}
}
