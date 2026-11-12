<?php

declare(strict_types=1);

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Content\Text;

use Friendica\Content\Text\Repository\RenderedContent as RenderedContentRepository;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Util\DateTimeFormat;

/**
 * Stores the HTML that has been rendered from a text, so that it doesn't have to be rendered again with every request.
 *
 * The stored HTML is only used when the hash of the text and the BBCode version are identical to the stored one.
 */
class RenderedContent
{
	/**
	 * Modes that are cached. Other modes are always rendered directly.
	 */
	private const CACHED_MODES = [BBCode::INTERNAL, BBCode::EXTERNAL, BBCode::MASTODON_API];

	public function __construct(private readonly RenderedContentRepository $repository, private readonly IManageConfigValues $config, private readonly L10n $l10n) {}

	/**
	 * Converts a BBCode message for a given URI-ID to a HTML message and caches the result
	 *
	 * @param int|null    $uriId      URI-ID of the post or the contact the text belongs to
	 * @param string|null $text       BBCode text
	 * @param int         $simpleHtml Simple HTML mode, see BBCode::convertForUriId()
	 * @return string HTML
	 */
	public function convert(?int $uriId, ?string $text, int $simpleHtml): string
	{
		if (!$uriId || ($text ?? '') === '' || !in_array($simpleHtml, self::CACHED_MODES, true) || $this->config->get('system', 'ignore_cache')) {
			return BBCode::convertForUriId($uriId, $text, $simpleHtml);
		}

		$hash  = hash('md5', BBCode::VERSION . '::' . $text);
		$today = DateTimeFormat::utcNow('Y-m-d');
		$lang  = $this->l10n->getCurrentLang();

		$rendered = $this->repository->find($uriId, $simpleHtml, $hash, $lang);
		if ($rendered) {
			// Only one write per day, so that reading doesn't cause a database write every time
			if ($rendered['last-access'] !== $today) {
				$this->repository->touch($uriId, $simpleHtml, $rendered['lang'], $today);
			}
			return $rendered['html'];
		}

		$translations = $this->l10n->getTranslationCount();
		$dateFormats  = $this->l10n->getDateFormatCount();

		$html = BBCode::convertForUriId($uriId, $text, $simpleHtml);

		// Formatted dates depend on the time zone of the viewer, so the result is not stored.
		if ($this->l10n->getDateFormatCount() !== $dateFormats) {
			return $html;
		}

		// There is only one entry per language when the html contains translated texts, otherwise one entry is used for all languages.
		$lang = ($this->l10n->getTranslationCount() === $translations) ? '' : $lang;

		$this->repository->save($uriId, $simpleHtml, $lang, $hash, $html, $today);

		return $html;
	}
}
