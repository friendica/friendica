<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Filer;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Database\DBA;
use Friendica\Model\Post;
use Friendica\Module\Response;
use Friendica\Navigation\SystemMessages;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Remove a tag from a file
 */
class RemoveTag extends BaseModule
{
	public function __construct(
		private readonly SystemMessages $systemMessages,
		L10n $l10n,
		App\BaseURL $baseUrl,
		App\Arguments $args,
		LoggerInterface $logger,
		Profiler $profiler,
		Response $response,
		private readonly IHandleUserSessions $userSession,
		array $server,
		array $parameters = [],
	) {
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
	}

	protected function post(array $request = [])
	{
		$type = 0;
		$term = '';
		$this->httpError($this->removeTag($request, $type, $term));
	}

	protected function content(array $request = []): string
	{
		if (!$this->userSession->getLocalUserId()) {
			throw new HTTPException\ForbiddenException();
		}

		$type = 0;
		$term = '';
		$this->removeTag($request, $type, $term);

		if ($type == Post\Category::FILE) {
			$this->baseUrl->redirect('filed?file=' . rawurlencode((string) $term));
		}

		return '';
	}

	/**
	 * @param array  $request The $_REQUEST array
	 * @param int    $type    Output parameter with the computed type
	 * @param string $term    Output parameter with the computed term
	 *
	 * @return int The relevant HTTP code
	 *
	 * @throws \Exception
	 */
	private function removeTag(array $request, int &$type, string &$term): int
	{
		$item_id = $this->parameters['id'] ?? 0;

		$term = trim($request['term'] ?? '');
		$cat  = trim($request['cat'] ?? '');

		if (!empty($cat)) {
			$type = Post\Category::CATEGORY;
			$term = $cat;
		} else {
			$type = Post\Category::FILE;
		}

		$this->logger->info('Filer - Remove Tag', [
			'term' => $term,
			'item' => $item_id,
			'type' => $type,
		]);

		if (!$item_id || !strlen($term)) {
			$this->systemMessages->addNotice($this->l10n->t('Item was not deleted'));
			return 401;
		}

		$item = Post::selectFirst(['uri-id'], ['id' => $item_id]);
		if (!DBA::isResult($item)) {
			return 404;
		}

		if (!Post\Category::deleteFileByURIId($item['uri-id'], $this->userSession->getLocalUserId(), $type, $term)) {
			$this->systemMessages->addNotice($this->l10n->t('Item was not removed'));
			return 500;
		}

		return 200;
	}
}
