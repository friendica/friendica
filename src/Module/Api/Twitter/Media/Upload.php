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

namespace Friendica\Module\Api\Twitter\Media;

use Friendica\Core\Logger;
use Friendica\DI;
use Friendica\Model\Photo;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Network\HTTPException\InternalServerErrorException;

/**
 * Uploads an image to Friendica.
 *
 * @see https://developer.twitter.com/en/docs/media/upload-media/api-reference/post-media-upload
 */
class Upload extends BaseApi
{
	protected function post(array $request = [])
	{
		BaseApi::checkAllowedScope(BaseApi::SCOPE_WRITE);
		$uid = BaseApi::getCurrentUserID();

		if (empty($_FILES['media'])) {
			// Output error
			throw new BadRequestException("No media.");
		}

		$media = Photo::upload($uid, $_FILES['media']);
		if (!$media) {
			// Output error
			throw new InternalServerErrorException();
		}

		$returndata = [];

		$returndata["media_id"]        = $media["id"];
		$returndata["media_id_string"] = (string)$media["id"];
		$returndata["size"]            = $media["size"];
		$returndata["image"]           = [
			"w"                     => $media["width"],
			"h"                     => $media["height"],
			"image_type"            => $media["type"],
			"friendica_preview_url" => $media["preview"]
		];

		Logger::info('Media uploaded', ['return' => $returndata]);

		$this->response->exit('media', ['media' => $returndata], $this->parameters['extension'] ?? null);
	}
}
