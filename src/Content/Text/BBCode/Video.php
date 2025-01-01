<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Content\Text\BBCode;

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
				if ((stristr($match[1], 'youtube')) || (stristr($match[1], 'youtu.be'))) {
					$bbCodeString = str_replace($match[0], '[youtube]' . $match[1] . '[/youtube]', $bbCodeString);
				} elseif (stristr($match[1], 'vimeo')) {
					$bbCodeString = str_replace($match[0], '[vimeo]' . $match[1] . '[/vimeo]', $bbCodeString);
				}
			}
		}
		return $bbCodeString;
	}
}
