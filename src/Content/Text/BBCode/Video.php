<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Content\Text\BBCode;

use Friendica\Util\Strings;

/**
 * Video specific BBCode util class
 */
class Video
{
	/**
	 * Transforms video BBCode tagged links to youtube/vimeo tagged links
	 *
	 * @param string $bbCodeString The input BBCode styled string
	 *
	 * @return string The transformed text
	 */
	public function transform(string $bbCodeString)
	{
		$matches = null;
		$found   = preg_match_all("/\[video\](.*?)\[\/video\]/ism", $bbCodeString, $matches, PREG_SET_ORDER);
		if ($found) {
			foreach ($matches as $match) {
				$hostname = parse_url($match[1], PHP_URL_HOST);
				if (!$hostname) {
					continue;
				}
				if (in_array(Strings::normaliseLink($hostname), ['youtube.com', 'youtu.be', 'vimeo.com', 'dailymotion.com', 'dai.ly'])) {
					$bbCodeString = str_replace($match[0], '[embed]' . $match[1] . '[/embed]', $bbCodeString);
				}
			}
		}
		return $bbCodeString;
	}
}
