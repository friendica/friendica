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

namespace Friendica\Content\Text;

use \HTMLPurifier_URIScheme;

/**
 * Validates content-id ("cid") as used in multi-part MIME messages, as defined by RFC 2392
 */
class HTMLPurifier_URIScheme_cid extends HTMLPurifier_URIScheme
{
	/**
	 * @type bool
	 */
	public $browsable = true;

	/**
	 * @type bool
	 */
	public $may_omit_host = true;

	/**
	 * @param HTMLPurifier_URI $uri
	 * @param HTMLPurifier_Config $config
	 * @param HTMLPurifier_Context $context
	 * @return bool
	 */
	public function doValidate(&$uri, $config, $context)
	{
		$uri->userinfo = null;
		$uri->host = null;
		$uri->port = null;
		$uri->query = null;
		// typecode check needed on path
		return true;
	}
}
