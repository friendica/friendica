<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Twitter\Media;

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
		$this->checkAllowedScope(BaseApi::SCOPE_WRITE);
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

		$this->logger->info('Media uploaded', ['return' => $returndata]);

		$this->response->addFormattedContent('media', ['media' => $returndata], $this->parameters['extension'] ?? null);
	}
}
