<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Content\Widget;

use Friendica\Content\Conversation\Entity\Community;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Model\Tag;

/**
 * Trending tags aside widget for the community pages, handles both local and global scopes
 *
 * @package Friendica\Content\Widget
 */
class TrendingTags
{
	/**
	 * @param string $content 'global' (all posts) or 'local' (this node's posts only)
	 * @param int    $period  Period in hours to consider posts
	 *
	 * @return string Formatted HTML code
	 * @throws \Exception
	 */
	public static function getHTML(string $content = Community::GLOBAL, int $period = 24): string
	{
		if ($content == Community::LOCAL) {
			$tags = Tag::getLocalTrendingHashtags($period, 20);
		} else {
			$tags = Tag::getGlobalTrendingHashtags($period, 20);
		}

		$tpl = Renderer::getMarkupTemplate('widget/trending_tags.tpl');
		$o   = Renderer::replaceMacros($tpl, [
			'$title'    => DI::l10n()->t('Trending Tags'),
			'$subtitle' => DI::l10n()->tt('(%d hour)', '(%d hours)', $period),
			'$more'     => DI::l10n()->t('More Trending Tags'),
			'$showmore' => DI::l10n()->t('Show More'),
			'$showless' => DI::l10n()->t('Show Less'),
			'$tags'     => $tags,
		]);

		return $o;
	}
}
