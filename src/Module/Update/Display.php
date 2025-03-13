<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Update;

use Friendica\Model\Post;
use Friendica\Module\Item\Display as DisplayModule;
use Friendica\Util\DateTimeFormat;
use Friendica\Network\HTTPException;

/**
 * Asynchronous update class for the display
 */
class Display extends DisplayModule
{
	protected function content(array $request = []): string
	{
		if ($this->config->get('system', 'block_public') && !$this->session->isAuthenticated()) {
			throw new HTTPException\UnauthorizedException($this->t('Access denied.'));
		}

		$profileUid = $request['p']      ?? 0;
		$force      = $request['force']  ?? false;
		$uriId      = $request['uri_id'] ?? 0;

		if (empty($uriId)) {
			throw new HTTPException\BadRequestException($this->t('Parameter uri_id is missing.'));
		}

		$item = Post::selectFirst(
			['uid', 'parent-uri-id', 'uri-id'],
			['uri-id' => $uriId, 'uid' => [0, $profileUid]],
			['order'  => ['uid' => true]]
		);

		if (empty($item)) {
			throw new HTTPException\NotFoundException($this->t('The requested item doesn\'t exist or has been deleted.'));
		}

		$this->appHelper->setProfileOwner($item['uid'] ?: $profileUid);
		$parentUriId = $item['parent-uri-id'];

		if (empty($force)) {
			if ($this->pConfig->get($profileUid, 'system', 'update_content')) {
				$updateDate = date(DateTimeFormat::MYSQL, time() - 120);
				if (!Post::exists([
					"`parent-uri-id` = ? AND `uid` IN (?, ?) AND `received` > ?",
					$parentUriId, 0,
					$profileUid, $updateDate])) {
					$this->logger->debug('No updated content. Ending process', ['uri-id' => $uriId, 'uid' => $profileUid, 'updated' => $updateDate]);
					return '';
				} else {
					$this->logger->debug('Updated content found.', ['uri-id' => $uriId, 'uid' => $profileUid, 'updated' => $updateDate]);
				}
			}
		} else {
			$this->logger->debug('Forced content update.', ['uri-id' => $uriId, 'uid' => $profileUid]);
		}

		if (!$this->pConfig->get($this->session->getLocalUserId(), 'system', 'detailed_notif')) {
			$this->notification->setAllSeenForUser($this->session->getLocalUserId(), ['parent-uri-id' => $item['parent-uri-id']]);
			$this->notify->setAllSeenForUser($this->session->getLocalUserId(), ['parent-uri-id' => $item['parent-uri-id']]);
		}

		return $this->getDisplayData($item, true, $profileUid, $force);
	}
}
