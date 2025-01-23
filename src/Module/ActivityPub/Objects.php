<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\ActivityPub;

use Friendica\BaseModule;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Network\HTTPException;
use Friendica\Protocol\ActivityPub;
use Friendica\Util\HTTPSignature;
use Friendica\Util\Network;
use Friendica\Util\Strings;

/**
 * ActivityPub Objects
 */
class Objects extends BaseModule
{
	protected function rawContent(array $request = [])
	{
		if (empty($this->parameters['guid'])) {
			throw new HTTPException\BadRequestException();
		}

		if (!ActivityPub::isRequest()) {
			DI::baseUrl()->redirect(str_replace('objects/', 'display/', DI::args()->getQueryString()));
		}

		$itemuri = DBA::selectFirst('item-uri', ['id'], ['guid' => $this->parameters['guid']]);

		if (DBA::isResult($itemuri)) {
			$this->logger->info('Provided GUID found.', ['guid' => $this->parameters['guid'], 'uri-id' => $itemuri['id']]);
		} else {
			// The item URI does not always contain the GUID. This means that we have to search the URL instead
			$url     = DI::baseUrl() . '/' . DI::args()->getQueryString();
			$nurl    = Strings::normaliseLink($url);
			$ssl_url = str_replace('http://', 'https://', $nurl);

			$itemuri = DBA::selectFirst('item-uri', ['guid', 'id'], ['uri' => [$url, $nurl, $ssl_url]]);
			if (DBA::isResult($itemuri)) {
				$this->logger->info('URL found.', ['url' => $url, 'guid' => $itemuri['guid'], 'uri-id' => $itemuri['id']]);
			} else {
				$this->logger->info('URL not found.', ['url' => $url]);
				throw new HTTPException\NotFoundException();
			}
		}

		$item = Post::selectFirst([], ['uri-id' => $itemuri['id'], 'origin' => true]);
		if (!DBA::isResult($item)) {
			throw new HTTPException\NotFoundException();
		}

		$validated = in_array($item['private'], [Item::PUBLIC, Item::UNLISTED]);

		if (!$validated) {
			$requester = HTTPSignature::getSigner('', $_SERVER);
			if (!empty($requester)) {
				$receivers   = Item::enumeratePermissions($item, false);
				$receivers[] = $item['contact-id'];

				$validated = in_array(Contact::getIdForURL($requester, $item['uid']), $receivers);
				if (!$validated) {
					$validated = in_array(Contact::getIdForURL($requester), $receivers);
				}
			}
		}

		if (!$validated) {
			throw new HTTPException\NotFoundException();
		}

		$etag          = md5($this->parameters['guid'] . '-' . $item['changed']);
		$last_modified = $item['changed'];
		Network::checkEtagModified($etag, $last_modified);

		if (empty($this->parameters['activity']) && ($item['gravity'] != Item::GRAVITY_ACTIVITY)) {
			$activity = ActivityPub\Transmitter::createCachedActivityFromItem($item['id'], false, true);
			if (empty($activity['type'])) {
				throw new HTTPException\NotFoundException();
			}

			$activity['type'] = $activity['type'] == 'Update' ? 'Create' : $activity['type'];

			// Only display "Create" activity objects here, no reshares or anything else
			if (empty($activity['object']) || ($activity['type'] != 'Create')) {
				throw new HTTPException\NotFoundException();
			}

			$data = ['@context' => ActivityPub::CONTEXT];
			$data = array_merge($data, $activity['object']);
		} elseif (empty($this->parameters['activity']) || in_array(
			$this->parameters['activity'],
			['Create', 'Announce', 'Update', 'Like', 'Dislike', 'Accept', 'Reject',
				'TentativeAccept', 'Follow', 'Add']
		)) {
			$data = ActivityPub\Transmitter::createCachedActivityFromItem($item['id']);
			if (empty($data)) {
				throw new HTTPException\NotFoundException();
			}
			if (!empty($this->parameters['activity']) && ($this->parameters['activity'] != 'Create')) {
				$data['type'] = $this->parameters['activity'];
				$data['id']   = str_replace('/Create', '/' . $this->parameters['activity'], $data['id']);
			}
		} else {
			throw new HTTPException\NotFoundException();
		}

		// Relaxed CORS header for public items
		header('Access-Control-Allow-Origin: *');

		$this->jsonExit($data, 'application/activity+json');
	}
}
