<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Module\Media;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Content\Post\Entity\PostMedia;
use Friendica\Content\Post\Repository\PostMedia as PostMediaRepository;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Module\Response;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Renders the player for an audio or video attachment.
 *
 * Only used in Ajax calls
 */
class Player extends BaseModule
{
	public function __construct(private readonly PostMediaRepository $postMediaRepository, private readonly IHandleUserSessions $session, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
	}

	protected function rawContent(array $request = [])
	{
		$uid = $this->session->getLocalUserId();
		if (!$uid) {
			$this->earlyHttpError(403);
		}

		try {
			$media = $this->postMediaRepository->selectById((int) $this->parameters['id']);
		} catch (NotFoundException) {
			$this->earlyHttpError(404);
		}

		$visible = Post::exists(["`uri-id` = ? AND NOT `deleted` AND (`uid` = ? OR (`uid` = ? AND `private` != ?))", $media->uriId, $uid, 0, Item::PRIVATE]);
		if (!$visible || !$media->isPlayable()) {
			$this->earlyHttpError(404);
		}

		$is_audio = $media->type === PostMedia::TYPE_AUDIO || ($media->width === 0 && $media->height === 0);

		$this->earlyHttpExit(Renderer::replaceMacros(Renderer::getMarkupTemplate('content/player.tpl'), [
			'$type'   => $is_audio ? 'audio' : 'video',
			'$ratio'  => $media->width && $media->height ? $media->width . '/' . $media->height : '16/9',
			'$player' => $this->postMediaRepository->getPlayer($media, $uid),
		]));
	}
}
