<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Util;

use Friendica\Network\Entity\MimeType;

/**
 * Resolves the icon and color style used to display non-visual (file) attachments
 */
class AttachType
{
	public const ICON_DISABLED = -1;
	public const ICON_COLOR    = 0;
	public const ICON_BLACK    = 1;
	public const ICON_WHITE    = 2;

	// mimetype type => default icon category, and per-subtype icon categories that are more specific than the default
	private static $types = [
		'audio' => [
			'default' => 'audio',
		],
		'video' => [
			'default' => 'video',
		],
		'image' => [
			'default' => 'image',
		],
		'text' => [
			'default'  => 'text',
			'subtypes' => [
				'html'           => 'link',
				'calendar'       => 'calendar',
				'csv'            => 'excel',
				'xml'            => 'code',
				'x-sh'           => 'code',
				'markdown'       => 'markdown',
				'x-web-markdown' => 'markdown',
			],
		],
		'application' => [
			'default'  => 'generic',
			'subtypes' => [
				'pdf' => 'pdf',

				'msword'                                                      => 'word',
				'vnd.openxmlformats-officedocument.wordprocessingml.document' => 'word',
				'vnd.oasis.opendocument.text'                                 => 'word',
				'rtf'                                                         => 'word',

				'vnd.ms-excel'                                          => 'excel',
				'vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'excel',
				'vnd.oasis.opendocument.spreadsheet'                    => 'excel',
				'csv'                                                   => 'excel',

				'vnd.ms-powerpoint'                                             => 'ppt',
				'vnd.openxmlformats-officedocument.presentationml.presentation' => 'ppt',
				'vnd.oasis.opendocument.presentation'                           => 'ppt',

				'zip'                         => 'zip',
				'x-tar'                       => 'zip',
				'x-7z-compressed'             => 'zip',
				'x-rar-compressed'            => 'zip',
				'gzip'                        => 'zip',
				'x-gzip'                      => 'zip',
				'x-bzip2'                     => 'zip',
				'x-xz'                        => 'zip',
				'epub+zip'                    => 'zip',
				'vnd.android.package-archive' => 'zip',
				'x-zip-compressed'            => 'zip',

				// HLS/DASH streaming manifests are functionally video
				'vnd.apple.mpegurl' => 'video',
				'x-mpegurl'         => 'video',
				'dash+xml'          => 'video',

				'x-bittorrent' => 'torrent',

				'rss+xml'   => 'feed',
				'atom+xml'  => 'feed',
				'x-rss+xml' => 'feed',

				'xhtml+xml' => 'link',

				'json'          => 'code',
				'ld+json'       => 'code',
				'activity+json' => 'code',
				'jrd+json'      => 'code',
				'did+ld+json'   => 'code',
				'rdap+json'     => 'code',
				'xrd+xml'       => 'code',
				'xml'           => 'code',
				'javascript'    => 'code',
				'x-sh'          => 'code',
			],
		],
	];

	/**
	 * Returns the icon category for the given mimetype: the default for its type,
	 * refined by a more specific subtype if one is known.
	 *
	 * @param MimeType $mimetype
	 * @return string
	 */
	private static function toCategory(MimeType $mimetype): string
	{
		$type = self::$types[$mimetype->type] ?? null;
		if (!$type) {
			return 'generic';
		}

		return $type['subtypes'][$mimetype->subtype] ?? $type['default'];
	}

	/**
	 * Returns the CSS classes that select the icon shape and color for the given mimetype and style.
	 * The icon itself is drawn via a CSS mask, see view/theme/frio/css/style.css
	 *
	 * @param MimeType $mimetype
	 * @param int      $style One of the ICON_* constants
	 * @return string
	 */
	public static function toClass(MimeType $mimetype, int $style): string
	{
		if ($style == self::ICON_DISABLED) {
			return '';
		}

		$category = self::toCategory($mimetype);

		if ($style == self::ICON_BLACK) {
			return 'attach-' . $category . ' attach-black';
		}

		if ($style == self::ICON_WHITE) {
			return 'attach-' . $category . ' attach-white';
		}

		return 'attach-' . $category . ' attach-color';
	}
}
