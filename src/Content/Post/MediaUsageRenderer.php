<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Content\Post;

use Friendica\Content\Post\Repository\MediaUsage;
use Friendica\Content\Text\BBCode;
use Friendica\Content\Text\Plaintext;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;

/**
 * Renders the list of posts that use a local photo or attachment.
 */
final readonly class MediaUsageRenderer
{
	private const TEXT_LENGTH = 100;

	public function __construct(
		private MediaUsage $mediaUsage,
		private L10n $l10n,
	) {}

	public function renderForPhoto(string $resourceId, int $uid): string
	{
		return $this->render(
			$this->l10n->t('Used in posts'),
			$this->l10n->t('This photo isn\'t used in any of your posts.'),
			$this->mediaUsage->selectPostsByPhoto($resourceId, $uid),
		);
	}

	public function renderForAttachment(int $attachId, string $filename, int $uid): string
	{
		return $this->render(
			$this->l10n->t('Posts using %s', $filename),
			$this->l10n->t('This file isn\'t used in any of your posts.'),
			$this->mediaUsage->selectPostsByAttachment($attachId, $uid),
		);
	}

	/**
	 * @param array{guid: string, title: string, body: string, created: string}[] $posts
	 */
	private function render(string $title, string $noPosts, array $posts): string
	{
		return Renderer::replaceMacros(Renderer::getMarkupTemplate('media_usage.tpl'), [
			'$title'    => $title,
			'$no_posts' => $noPosts,
			'$posts'    => $this->buildEntries($posts),
		]);
	}

	/**
	 * @param array{guid: string, title: string, body: string, created: string}[] $posts
	 *
	 * @return array{url: string, created: string, text: string}[]
	 */
	private function buildEntries(array $posts): array
	{
		return array_map(fn (array $post): array => [
			'url'     => 'display/' . $post['guid'],
			'created' => $this->l10n->fullDateTime($post['created']),
			'text'    => $post['title'] ?: Plaintext::shorten(BBCode::toPlaintext($post['body'], false), self::TEXT_LENGTH),
		], $posts);
	}
}
