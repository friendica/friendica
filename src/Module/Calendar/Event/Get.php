<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Calendar\Event;

use Friendica\App;
use Friendica\Core\L10n;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Model\Event;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Module\Response;
use Friendica\Network\HTTPException;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Profiler;
use Friendica\Util\Strings;
use Psr\Log\LoggerInterface;

/**
 * GET-Controller for event
 * returns the result as JSON
 */
class Get extends \Friendica\BaseModule
{
	/** @var IHandleUserSessions */
	protected $session;

	public function __construct(L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, IHandleUserSessions $session, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->session = $session;
	}

	protected function rawContent(array $request = [])
	{
		$nickname = $this->parameters['nickname'] ?? $this->session->getLocalUserNickname();
		if (!$nickname) {
			throw new HTTPException\UnauthorizedException();
		}

		$owner = Event::getOwnerForNickname($nickname);

		if (!empty($request['id'])) {
			$events = [Event::getByIdAndUid($owner['uid'], (int) $request['id'])];
		} else {
			$events = Event::getListByDate($owner['uid'], $request['start'] ?? '', $request['end'] ?? '');
		}

		$this->earlyJsonExit($events ? self::map($events) : []);
	}

	private static function map(array $events): array
	{
		return array_map(function ($event) {
			$item = Post::selectFirst(['plink', 'author-name', 'author-avatar', 'author-link', 'private', 'uri-id'], ['id' => $event['itemid']]);
			if (empty($item)) {
				// Using default values when no item had been found
				$item = ['plink' => '', 'author-name' => '', 'author-avatar' => '', 'author-link' => '', 'private' => Item::PUBLIC, 'uri-id' => ($event['uri-id'] ?? 0)];
			}

			return [
				'id'       => $event['id'],
				'title'    => Strings::escapeHtml($event['summary']),
				'start'    => DateTimeFormat::local($event['start']),
				'end'      => DateTimeFormat::local($event['finish']),
				'nofinish' => $event['nofinish'],
				'desc'     => Strings::escapeHtml($event['desc']),
				'location' => Strings::escapeHtml($event['location']),
				'item'     => array_map(Strings::escapeHtml(...), $item),
			];
		}, $events);
	}
}
