<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\BaseModule;
use Friendica\Content\Text\BBCode;
use Friendica\Core\L10n;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Event\ArrayFilterEvent;
use Friendica\Model\Post;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Util;
use Friendica\Util\Profiler;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

class ParseUrl extends BaseModule
{
	/** @var IHandleUserSessions */
	protected $userSession;

	private EventDispatcherInterface $eventDispatcher;

	public function __construct(L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, IHandleUserSessions $userSession, EventDispatcherInterface $eventDispatcher, $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->userSession     = $userSession;
		$this->eventDispatcher = $eventDispatcher;
	}

	protected function rawContent(array $request = [])
	{
		if (!$this->userSession->isAuthenticated()) {
			throw new \Friendica\Network\HTTPException\ForbiddenException();
		}

		$format      = $request['format'] ?? '';
		$title       = '';
		$description = '';
		$ret         = ['success' => false, 'contentType' => ''];

		if (!empty($request['binurl']) && Util\Strings::isHex($request['binurl'])) {
			$url = trim(hex2bin($request['binurl']));
		} elseif (!empty($request['url'])) {
			$url = trim($request['url']);
			// fallback in case no url is valid
		} else {
			throw new BadRequestException('No url given');
		}

		if (!empty($request['title'])) {
			$title = strip_tags(trim($request['title']));
		}

		if (!empty($request['description'])) {
			$description = strip_tags(trim($request['description']));
		}

		// Add url scheme if it is missing
		$arrurl = parse_url($url);
		if (empty($arrurl['scheme'])) {
			if (!empty($arrurl['host'])) {
				$url = 'http:' . $url;
			} else {
				$url = 'http://' . $url;
			}
		}

		$hook_data = [
			'url'    => $url,
			'format' => $format,
			'text'   => null,
		];

		$hook_data = $this->eventDispatcher->dispatch(
			new ArrayFilterEvent(ArrayFilterEvent::PARSE_LINK, $hook_data),
		)->getArray();

		if ($hook_data['text']) {
			if ($format == 'json') {
				$this->jsonExit($hook_data['text']);
			} else {
				$this->httpExit($hook_data['text']);
			}
		}

		if ($format == 'json') {
			$siteinfo = ['url' => $url];
			$embed    = false;

			$contentType = Util\ParseUrl::getContentType($url, HttpClientAccept::DEFAULT, 5);
			$mediatype   = Post\Media::getType(implode('/', $contentType));

			if ($mediatype === Post\Media::HTML) {
				$siteinfo = Util\ParseUrl::getSiteinfoCached($url, implode('/', $contentType));
				$title    = $siteinfo['title'] ?? $title;
				$embed    = isset($siteinfo['embed']);
				unset($siteinfo['keywords']);
			}

			$ret['data']    = $siteinfo;
			$ret['success'] = true;

			if ($mediatype === Post\Media::AUDIO) {
				$ret['contentType'] = 'audio';
			} elseif (in_array($mediatype, [Post\Media::VIDEO, Post\Media::HLS])) {
				$ret['contentType'] = 'video';
			} elseif ($mediatype === Post\Media::IMAGE) {
				$ret['contentType'] = 'image';
			} elseif ($mediatype === Post\Media::HTML && $embed) {
				$ret['contentType'] = 'embed';
			} elseif ($mediatype === Post\Media::HTML) {
				$ret['contentType'] = 'attachment';
			} else {
				$ret['contentType'] = 'url';
			}

			$this->jsonExit($ret);
		} else {
			$this->httpExit(BBCode::embedURL($url, empty($request['noAttachment']), $title, $description, $request['tags'] ?? ''));
		}
	}
}
