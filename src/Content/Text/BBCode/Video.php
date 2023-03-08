<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

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
		$found = preg_match_all("/\[video\](.*?)\[\/video\]/ism",$bbCodeString,$matches,PREG_SET_ORDER);
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
