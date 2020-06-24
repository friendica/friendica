<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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

/**
 * @deprecated since 2020.06
 * @see \Friendica\Content\PageInfo::getFooterFromData
 */
function add_page_info_data(array $data, $no_photos = false)
{
	return "\n" . \Friendica\Content\PageInfo::getFooterFromData($data, $no_photos);
}

/**
 * @deprecated since 2020.06
 * @see \Friendica\Content\PageInfo::queryUrl
 */
function query_page_info($url, $photo = "", $keywords = false, $keyword_denylist = "")
{
	return \Friendica\Content\PageInfo::queryUrl($url, $photo, $keywords, $keyword_denylist);
}

/**
 * @deprecated since 2020.06
 * @see \Friendica\Content\PageInfo::getTagsFromUrl()
 */
function get_page_keywords($url, $photo = "", $keywords = false, $keyword_denylist = "")
{
	return $keywords ? \Friendica\Content\PageInfo::getTagsFromUrl($url, $photo, $keyword_denylist) : [];
}

/**
 * @deprecated since 2020.06
 * @see \Friendica\Content\PageInfo::getFooterFromUrl
 */
function add_page_info($url, $no_photos = false, $photo = "", $keywords = false, $keyword_denylist = "")
{
	return "\n" . \Friendica\Content\PageInfo::getFooterFromUrl($url, $no_photos, $photo, $keywords, $keyword_denylist);
}

/**
 * @deprecated since 2020.06
 * @see \Friendica\Content\PageInfo::appendToBody
 */
function add_page_info_to_body($body, $texturl = false, $no_photos = false)
{
	return \Friendica\Content\PageInfo::appendToBody($body, $texturl, $no_photos);
}

/**
 * @deprecated since 2020.06
 * @see \Friendica\Protocol\Feed::consume
 */
function consume_feed($xml, array $importer, array $contact, &$hub)
{
	\Friendica\Protocol\Feed::consume($xml, $importer, $contact, $hub);
}
