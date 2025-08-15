<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Post\Tag;

use Friendica\App;
use Friendica\Content\Text\BBCode;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Model\Post;
use Friendica\Model\Tag;
use Friendica\Module\Response;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class Remove extends \Friendica\BaseModule
{
	/** @var IHandleUserSessions */
	private $session;

	public function __construct(IHandleUserSessions $session, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->session = $session;
	}

	protected function post(array $request = [])
	{
		if (!$this->session->getLocalUserId()) {
			$this->baseUrl->redirect($request['return'] ?? '');
		}


		if (isset($request['cancel'])) {
			$this->baseUrl->redirect($request['return'] ?? '');
		}

		$tags = [];
		foreach ($request['tag'] ?? [] as $tag => $checked) {
			if ($checked) {
				$tags[] = hex2bin(trim($tag));
			}
		}

		$this->removeTagsFromItem($this->parameters['item_id'], $tags);
		$this->baseUrl->redirect($request['return'] ?? '');
	}

	protected function content(array $request = []): string
	{
		$returnUrl = $request['return'] ?? '';

		if (!$this->session->getLocalUserId()) {
			$this->baseUrl->redirect($returnUrl);
		}

		if (isset($this->parameters['tag_name'])) {
			$this->removeTagsFromItem($this->parameters['item_id'], [trim(hex2bin($this->parameters['tag_name']))]);
			$this->baseUrl->redirect($returnUrl);
		}

		$item_id = intval($this->parameters['item_id']);
		if (!$item_id) {
			$this->baseUrl->redirect($returnUrl);
		}

		$item = Post::selectFirst(['uri-id'], ['id' => $item_id, 'uid' => $this->session->getLocalUserId()]);
		if (!$item) {
			$this->baseUrl->redirect($returnUrl);
		}

		$tag_text = Tag::getCSVByURIId($item['uri-id']);

		if ($tag_text === '') {
			$this->baseUrl->redirect($returnUrl);
		}

		$tags = explode(',', $tag_text);

		$tag_checkboxes = array_map(function ($tag_text) {
			return ['tag[' . bin2hex($tag_text) . ']', BBCode::toPlaintext($tag_text)];
		}, $tags);

		$tpl = Renderer::getMarkupTemplate('post/tag/remove.tpl');
		return Renderer::replaceMacros($tpl, [
			'$l10n' => [
				'header' => $this->t('Remove Item Tag'),
				'desc'   => $this->t('Select a tag to remove: '),
				'remove' => $this->t('Remove'),
				'cancel' => $this->t('Cancel'),
			],

			'$item_id'        => $item_id,
			'$return'         => urlencode($returnUrl),
			'$tag_checkboxes' => $tag_checkboxes,
		]);
	}

	/**
	 * @param int   $item_id
	 * @param array $tags
	 * @throws \Exception
	 */
	private function removeTagsFromItem(int $item_id, array $tags)
	{
		if (empty($item_id) || empty($tags)) {
			return;
		}

		$item = Post::selectFirst(['uri-id'], ['id' => $item_id, 'uid' => $this->session->getLocalUserId()]);
		if (empty($item)) {
			return;
		}

		foreach ($tags as $tag) {
			if (preg_match('~([#@!])\[url=([^\[\]]*)]([^\[\]]*)\[/url]~im', $tag, $results)) {
				Tag::removeByHash($item['uri-id'], $results[1], $results[3], $results[2]);
			}
		}
	}
}
