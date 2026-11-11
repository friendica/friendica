<?php

declare(strict_types=1);

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Content\Text\Repository;

use Friendica\Database\Database;

/**
 * Repository for the "rendered-content" table, that contains the HTML that has been rendered from a text.
 */
final readonly class RenderedContent
{
	public function __construct(private Database $database) {}

	/**
	 * Fetch the stored HTML
	 *
	 * The entry without a language is valid for every language, an entry with a language only for this language.
	 *
	 * @param int    $uriId URI-ID of the post or the contact the text belongs to
	 * @param int    $mode  Simple HTML mode
	 * @param string $hash  Hash of the text and the BBCode version
	 * @param string $lang  Language of the viewer
	 * @return array{lang: string, html: string, last-access: string}|null
	 */
	public function find(int $uriId, int $mode, string $hash, string $lang): ?array
	{
		$rendered = $this->database->selectFirst('rendered-content', ['lang', 'html', 'last-access'], ['uri-id' => $uriId, 'mode' => $mode, 'lang' => ['', $lang], 'hash' => $hash], ['order' => ['lang']]);
		if (empty($rendered) || ($rendered['html'] === null)) {
			return null;
		}
		return $rendered;
	}

	/**
	 * Mark an entry as accessed on the given day
	 */
	public function touch(int $uriId, int $mode, string $lang, string $day): void
	{
		$this->database->update('rendered-content', ['last-access' => $day], ['uri-id' => $uriId, 'mode' => $mode, 'lang' => $lang]);
	}

	/**
	 * Store the HTML and remove the entries for previous versions of the text
	 */
	public function save(int $uriId, int $mode, string $lang, string $hash, string $html, string $day): void
	{
		$this->database->insert('rendered-content', ['uri-id' => $uriId, 'mode' => $mode, 'lang' => $lang, 'hash' => $hash, 'html' => $html, 'last-access' => $day], Database::INSERT_UPDATE);

		$this->database->delete('rendered-content', ["`uri-id` = ? AND `mode` = ? AND `hash` != ?", $uriId, $mode, $hash]);
	}

	/**
	 * Delete the entries that haven't been accessed since before the given day
	 */
	public function deleteNotAccessedSince(string $day): void
	{
		$this->database->delete('rendered-content', ["`last-access` < ?", $day]);
	}
}
