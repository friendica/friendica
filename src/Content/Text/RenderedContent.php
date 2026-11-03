<?php

declare(strict_types=1);

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Content\Text;

use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Database\Database;
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

	public function __construct(private readonly Database $database, private readonly IManageConfigValues $config, private readonly L10n $l10n) {}

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
		if (!$uriId || ($text ?? '') === '' || !in_array($simpleHtml, self::CACHED_MODES, true)) {
			return BBCode::convertForUriId($uriId, $text, $simpleHtml);
		}

		$hash      = hash('md5', BBCode::VERSION . '::' . $text);
		$condition = ['uri-id' => $uriId, 'mode' => $simpleHtml];
		$today     = DateTimeFormat::utcNow('Y-m-d');
		$lang      = $this->l10n->getCurrentLang();

		if (!$this->config->get('system', 'ignore_cache')) {
			// The entry without a language is valid for every language, an entry with a language only for this language
			$rendered = $this->database->selectFirst('rendered-content', ['lang', 'hash', 'html', 'last-access'], $condition + ['lang' => ['', $lang], 'hash' => $hash], ['order' => ['lang']]);
			if (!empty($rendered) && ($rendered['html'] !== null)) {
				// Only one write per day, so that reading doesn't cause a database write every time
				if ($rendered['last-access'] !== $today) {
					$this->database->update('rendered-content', ['last-access' => $today], $condition + ['lang' => $rendered['lang']]);
				}
				return $rendered['html'];
			}
		}

		$translations = $this->l10n->getTranslationCount();

		$html = BBCode::convertForUriId($uriId, $text, $simpleHtml);

		// There is only one entry per language when the html contains translated texts, otherwise one entry is used for all languages.
		$lang = ($this->l10n->getTranslationCount() === $translations) ? '' : $lang;

		$this->database->insert('rendered-content', $condition + ['lang' => $lang, 'hash' => $hash, 'html' => $html, 'last-access' => $today], Database::INSERT_UPDATE);

		// Entries for a previous version of the text are outdated
		$this->database->delete('rendered-content', ["`uri-id` = ? AND `mode` = ? AND `hash` != ?", $uriId, $simpleHtml, $hash]);

		return $html;
	}
}
