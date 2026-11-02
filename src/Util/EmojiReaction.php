<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Util;

/**
 * Emoji reactions (FEP-c0e0)
 */
class EmojiReaction
{
	/**
	 * Emojis that are unambiguously positive. Reactions with them are sent as a "Like" with content,
	 * so that systems without support for emoji reactions still show them as a like.
	 */
	public const POSITIVE_REACTIONS = [
		'👍', '❤', '😍', '🥰', '😘', '😊', '☺', '🙂', '😀', '😃', '😄', '😁', '👏', '🙌', '🎉', '🥳', '💯',
		'⭐', '🌟', '✨', '💖', '💕', '💗', '💓', '💞', '💝', '💜', '💙', '💚', '💛', '🧡', '🤍', '🩷', '🩵',
		'😻', '🤩', '👌', '🫶', '🙏', '💐', '🌹', '🏆', '🥇',
	];

	/**
	 * Checks if the body is a valid emoji reaction (FEP-c0e0), which is a single Unicode emoji.
	 * Custom emojis aren't accepted, since they aren't accessible.
	 *
	 * @param string $body
	 * @return boolean
	 */
	public static function isReaction(string $body): bool
	{
		if (($body === '') || (mb_strlen($body) > 16)) {
			return false;
		}

		// Flag, keycap or symbol with modifiers, optionally joined via ZWJ.
		// "\X" isn't used, since PCRE treats consecutive emojis like "👍👍" as a single grapheme.
		$emoji = '(?:[\x{1F1E6}-\x{1F1FF}]{2}|[0-9#*]\x{FE0F}?\x{20E3}|\p{So}[\p{M}\x{1F3FB}-\x{1F3FF}\x{E0020}-\x{E007F}]*)';
		return (bool) preg_match('/^' . $emoji . '(?:\x{200D}' . $emoji . ')*$/u', $body);
	}

	/**
	 * Checks if the reaction is in the list of unambiguously positive emojis
	 *
	 * @param string $reaction
	 * @return boolean
	 */
	public static function isPositiveReaction(string $reaction): bool
	{
		// Ignore the emoji presentation selector and skin tones
		$base = preg_replace('/[\x{FE0F}\x{1F3FB}-\x{1F3FF}]/u', '', $reaction);
		return in_array($base, self::POSITIVE_REACTIONS);
	}
}
