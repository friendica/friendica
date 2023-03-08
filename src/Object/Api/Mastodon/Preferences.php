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

namespace Friendica\Object\Api\Mastodon;

use Friendica\App\BaseURL;
use Friendica\BaseDataTransferObject;

/**
 * Class Preferences
 *
 * @see https://docs.joinmastodon.org/entities/preferences/
 */
class Preferences extends BaseDataTransferObject
{
//	/** @var string (Enumerable, oneOf) */
//	protected $posting_default_visibility;
//	/** @var bool */
//	protected $posting_default_sensitive;
//	/** @var string (ISO 639-1 language two-letter code), or null*/
//	protected $posting_default_language;
//	/** @var string (Enumerable, oneOf) */
//	protected $reading_expand_media;
//	/** @var bool */
//	protected $reading_expand_spoilers;

	/**
	 * Creates a preferences record.
	 *
	 * @param BaseURL $baseUrl
	 * @param array   $publicContact Full contact table record with uid = 0
	 * @param array   $apcontact     Optional full apcontact table record
	 * @param array   $userContact   Optional full contact table record with uid != 0
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function __construct(string $visibility, bool $sensitive, string $language, string $media, bool $spoilers)
	{
		$this->{'posting:default:visibility'} = $visibility;
		$this->{'posting:default:sensitive'}  = $sensitive;
		$this->{'posting:default:language'}   = $language;
		$this->{'reading:expand:media'}       = $media;
		$this->{'reading:expand:spoilers'}    = $spoilers;
	}
}
