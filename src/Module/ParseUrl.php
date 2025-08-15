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

		$format      = '';
		$title       = '';
		$description = '';
		$ret         = ['success' => false, 'contentType' => ''];

		if (!empty($_GET['binurl']) && Util\Strings::isHex($_GET['binurl'])) {
			$url = trim(hex2bin($_GET['binurl']));
		} elseif (!empty($_GET['url'])) {
			$url = trim($_GET['url']);
			// fallback in case no url is valid
		} else {
			throw new BadRequestException('No url given');
		}

		if (!empty($_GET['title'])) {
			$title = strip_tags(trim($_GET['title']));
		}

		if (!empty($_GET['description'])) {
			$description = strip_tags(trim($_GET['description']));
		}

		if (!empty($_GET['tags'])) {
			$arr_tags = Util\ParseUrl::convertTagsToArray($_GET['tags']);
			if (count($arr_tags)) {
				$str_tags = "\n" . implode(' ', $arr_tags) . "\n";
			}
		}

		if (isset($_GET['format']) && $_GET['format'] == 'json') {
			$format = 'json';
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
			$siteinfo = Util\ParseUrl::getSiteinfoCached($url);

			if (in_array($siteinfo['type'], ['image', 'video', 'audio'])) {
				switch ($siteinfo['type']) {
					case 'video':
						$content_type = 'video';
						break;
					case 'audio':
						$content_type = 'audio';
						break;
					default:
						$content_type = 'image';
						break;
				}

				$ret['contentType'] = $content_type;
				$ret['data']        = ['url' => $url];
				$ret['success']     = true;
			} else {
				unset($siteinfo['keywords']);

				$ret['data']        = $siteinfo;
				$ret['contentType'] = 'attachment';
				$ret['success']     = true;
			}

			$this->jsonExit($ret);
		} else {
			$this->httpExit(BBCode::embedURL($url, empty($_GET['noAttachment']), $title, $description, $_GET['tags'] ?? ''));
		}
	}
}
