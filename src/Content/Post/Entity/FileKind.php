<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Content\Post\Entity;

use Friendica\Core\L10n;
use Friendica\Network\Entity\MimeType;

/**
 * Kind of file behind a post media attachment, derived from its mimetype.
 */
enum FileKind: string
{
	case Audio    = 'audio';
	case Video    = 'video';
	case Image    = 'image';
	case Text     = 'text';
	case Link     = 'link';
	case Calendar = 'calendar';
	case Excel    = 'excel';
	case Code     = 'code';
	case Markdown = 'markdown';
	case Generic  = 'generic';
	case Pdf      = 'pdf';
	case Word     = 'word';
	case Ppt      = 'ppt';
	case Zip      = 'zip';
	case Torrent  = 'torrent';
	case Feed     = 'feed';

	// mimetype type => default kind, and per-subtype kinds that are more specific than the default
	private const TYPES = [
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

	public static function fromMimeType(MimeType $mimetype): self
	{
		$type = self::TYPES[$mimetype->type] ?? null;
		if (!$type) {
			return self::Generic;
		}

		return self::from($type['subtypes'][$mimetype->subtype] ?? $type['default']);
	}

	/**
	 * Returns a human readable description of the file kind
	 *
	 * @param L10n $l10n
	 * @return string
	 */
	public function label(L10n $l10n): string
	{
		return match ($this) {
			self::Audio    => $l10n->t('Audio'),
			self::Video    => $l10n->t('Video'),
			self::Image    => $l10n->t('Image'),
			self::Text     => $l10n->t('Text'),
			self::Link     => $l10n->t('Web page'),
			self::Calendar => $l10n->t('Calendar'),
			self::Markdown => $l10n->t('Markdown'),
			self::Code     => $l10n->t('Code'),
			self::Pdf      => $l10n->t('PDF document'),
			self::Word     => $l10n->t('Text document'),
			self::Excel    => $l10n->t('Spreadsheet'),
			self::Ppt      => $l10n->t('Presentation'),
			self::Zip      => $l10n->t('Archive'),
			self::Torrent  => $l10n->t('Torrent'),
			self::Feed     => $l10n->t('Feed'),
			self::Generic  => $l10n->t('File'),
		};
	}
}
