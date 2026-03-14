<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\DI;
use Friendica\Model\Photo;
use Friendica\Model\User;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Protocol\ActivityNamespace;
use Friendica\Util\Network;
use Friendica\Util\XML;

/**
 * Prints responses to /.well-known/webfinger  or /xrd requests
 */
class Xrd extends BaseModule
{
	protected function rawContent(array $request = [])
	{
		header('Vary: Accept', false);

		// @TODO: Replace with parameter from router
		if (DI::args()->getArgv()[0] == 'xrd') {
			if (empty($_GET['uri'])) {
				throw new BadRequestException();
			}

			$uri  = urldecode(trim($_GET['uri']));
			$mode = self::getAcceptedContentType($_SERVER['HTTP_ACCEPT'] ?? '', Response::TYPE_XML);
		} else {
			if (empty($_GET['resource'])) {
				throw new BadRequestException();
			}

			$uri  = urldecode(trim($_GET['resource']));
			$mode = self::getAcceptedContentType($_SERVER['HTTP_ACCEPT'] ?? '', Response::TYPE_JSON);
		}

		if (Network::isValidHttpUrl($uri)) {
			$name = ltrim(basename($uri), '~');
			$host = parse_url($uri, PHP_URL_HOST);
		} elseif (preg_match('/^[[:alpha:]][[:alnum:]+-.]+:/', $uri)) {
			$local = str_replace('acct:', '', $uri);
			if (substr($local, 0, 2) == '//') {
				$local = substr($local, 2);
			}

			[$name, $host] = explode('@', $local);
		} else {
			throw new BadRequestException();
		}

		if (!empty($host) && $host !== DI::baseUrl()->getHost()) {
			DI::logger()->notice('Invalid host name for xrd query', ['host' => $host, 'uri' => $uri]);
			throw new NotFoundException('Invalid host name for xrd query: ' . $host);
		}

		header('Vary: Accept', false);

		$alias = '';

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

	/**
	 * Detect the accepted content type.
	 * @todo Handle priorities (see "application/xrd+xml,text/xml;q=0.9")
	 *
	 * @param string $accept
	 * @param string $default
	 * @return string
	 */
	private function getAcceptedContentType(string $accept, string $default): string
	{
		$parts = [];
		foreach (explode(',', $accept) as $part) {
			$parts[] = current(explode(';', $part));
		}

		if ($parts === []) {
			return $default;
		} elseif (in_array('application/jrd+json', $parts) && !in_array('application/xrd+xml', $parts)) {
			return Response::TYPE_JSON;
		} elseif (!in_array('application/jrd+json', $parts) && in_array('application/xrd+xml', $parts)) {
			return Response::TYPE_XML;
		} elseif (in_array('application/json', $parts) && !in_array('text/xml', $parts)) {
			return Response::TYPE_JSON;
		} elseif (!in_array('application/json', $parts) && in_array('text/xml', $parts)) {
			return Response::TYPE_XML;
		} else {
			return $default;
		}
	}

	private function printSystemJSON(array $owner)
	{
		$baseURL = (string)$this->baseUrl;
		$json    = [
			'subject' => 'acct:' . $owner['addr'],
			'aliases' => [$owner['url']],
			'links'   => [
				[
					'rel'  => ActivityNamespace::FEED,
					'type' => 'application/atom+xml',
					'href' => $owner['poll'] ?? $baseURL,
				],
				[
					'rel'  => ActivityNamespace::WEBFINGERPROFILE,
					'type' => 'text/html',
					'href' => $owner['url'],
				],
				[
					'rel'  => 'self',
					'type' => 'application/activity+json',
					'href' => $owner['url'],
				],
				[
					'rel'  => ActivityNamespace::HCARD,
					'type' => 'text/html',
					'href' => $baseURL . '/hcard/' . $owner['nickname'],
				],
				[
					'rel'  => ActivityNamespace::DIASPORA_SEED,
					'type' => 'text/html',
					'href' => $baseURL,
				],
				[
					'rel'  => 'salmon',
					'href' => $baseURL . '/receive/users/' . $owner['guid'],
				],
				[
					'rel'      => ActivityNamespace::OSTATUSSUB,
					'template' => $baseURL . '/contact/follow?url={uri}',
				],
			]
		];
		header('Access-Control-Allow-Origin: *');
		$this->jsonExit($json, 'application/jrd+json; charset=utf-8');
	}

	private function printJSON(string $alias, array $owner, array $avatar)
	{
		$baseURL = (string)$this->baseUrl;

		$json = [
			'subject' => 'acct:' . $owner['addr'],
			'aliases' => [
				$alias,
				$owner['url'],
			],
			'links' => [
				[
					'rel'  => ActivityNamespace::DFRN,
					'href' => $owner['url'],
				],
				[
					'rel'  => ActivityNamespace::FEED,
					'type' => 'application/atom+xml',
					'href' => $owner['poll'],
				],
				[
					'rel'  => ActivityNamespace::WEBFINGERPROFILE,
					'type' => 'text/html',
					'href' => $owner['url'],
				],
				[
					'rel'  => 'self',
					'type' => 'application/activity+json',
					'href' => $owner['url'],
				],
				[
					'rel'  => ActivityNamespace::HCARD,
					'type' => 'text/html',
					'href' => $baseURL . '/hcard/' . $owner['nickname'],
				],
				[
					'rel'  => ActivityNamespace::WEBFINGERAVATAR,
					'type' => $avatar['type'],
					'href' => User::getAvatarUrl($owner),
				],
				[
					'rel'  => ActivityNamespace::DIASPORA_SEED,
					'type' => 'text/html',
					'href' => $baseURL,
				],
				[
					'rel'  => 'salmon',
					'href' => $baseURL . '/receive/users/' . $owner['guid'],
				],
				[
					'rel'      => ActivityNamespace::OSTATUSSUB,
					'template' => $baseURL . '/contact/follow?url={uri}',
				],
				[
					'rel'  => ActivityNamespace::OPENWEBAUTH,
					'type' => 'application/x-zot+json',
					'href' => $baseURL . '/owa',
				],
			],
		];

		header('Access-Control-Allow-Origin: *');
		$this->jsonExit($json, 'application/jrd+json; charset=utf-8');
	}

	private function printXML(string $alias, array $owner, array $avatar)
	{
		$baseURL = (string)$this->baseUrl;

		$xmlString = XML::fromArray([
			'XRD' => [
				'@attributes' => [
					'xmlns' => 'http://docs.oasis-open.org/ns/xri/xrd-1.0',
				],
				'Subject' => 'acct:' . $owner['addr'],
				'1:Alias' => $owner['url'],
				'2:Alias' => $alias,
				'1:link'  => [
					'@attributes' => [
						'rel'  => ActivityNamespace::DFRN,
						'href' => $owner['url']
					]
				],
				'2:link' => [
					'@attributes' => [
						'rel'  => ActivityNamespace::FEED,
						'type' => 'application/atom+xml',
						'href' => $owner['poll']
					]
				],
				'3:link' => [
					'@attributes' => [
						'rel'  => ActivityNamespace::WEBFINGERPROFILE,
						'type' => 'text/html',
						'href' => $owner['url']
					]
				],
				'4:link' => [
					'@attributes' => [
						'rel'  => 'self',
						'type' => 'application/activity+json',
						'href' => $owner['url']
					]
				],
				'5:link' => [
					'@attributes' => [
						'rel'  => ActivityNamespace::HCARD,
						'type' => 'text/html',
						'href' => $baseURL . '/hcard/' . $owner['nickname']
					]
				],
				'6:link' => [
					'@attributes' => [
						'rel'  => ActivityNamespace::WEBFINGERAVATAR,
						'type' => $avatar['type'],
						'href' => User::getAvatarUrl($owner)
					]
				],
				'7:link' => [
					'@attributes' => [
						'rel'  => ActivityNamespace::DIASPORA_SEED,
						'type' => 'text/html',
						'href' => $baseURL
					]
				],
				'8:link' => [
					'@attributes' => [
						'rel'  => 'salmon',
						'href' => $baseURL . '/receive/users/' . $owner['guid']
					]
				],
				'9:link' => [
					'@attributes' => [
						'rel'      => ActivityNamespace::OSTATUSSUB,
						'template' => $baseURL . '/contact/follow?url={uri}'
					]
				],
				'10:link' => [
					'@attributes' => [
						'rel'  => ActivityNamespace::OPENWEBAUTH,
						'type' => 'application/x-zot+json',
						'href' => $baseURL . '/owa'
					]
				],
			],
		]);

		header('Access-Control-Allow-Origin: *');
		$this->httpExit($xmlString, Response::TYPE_XML, 'application/xrd+xml');
	}
}
