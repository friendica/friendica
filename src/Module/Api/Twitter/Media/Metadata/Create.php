<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Twitter\Media\Metadata;

use Friendica\Model\Photo;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Util\Network;

/**
 * Updates media meta data (picture descriptions)
 *
 * @see https://developer.twitter.com/en/docs/twitter-api/v1/media/upload-media/api-reference/post-media-metadata-create
 */
class Create extends BaseApi
{
	protected function post(array $request = [])
	{
		$this->checkAllowedScope(BaseApi::SCOPE_WRITE);
		$uid = BaseApi::getCurrentUserID();

		$postdata = Network::postdata();

		if (empty($postdata)) {
			throw new BadRequestException('No post data');
		}

		$data = json_decode($postdata, true);
		if (empty($data)) {
			throw new BadRequestException('Invalid post data');
		}

		if (empty($data['media_id']) || empty($data['alt_text'])) {
			throw new BadRequestException('Missing post data values');
		}

		if (empty($data['alt_text']['text'])) {
			throw new BadRequestException('No alt text.');
		}

		$this->logger->info('Updating metadata', ['media_id' => $data['media_id']]);

		$condition = ['id' => $data['media_id'], 'uid' => $uid];

		$photo = Photo::selectFirst(['resource-id'], $condition);
		if (empty($photo['resource-id'])) {
			throw new BadRequestException('Metadata not found.');
		}

		Photo::update(['desc' => $data['alt_text']['text']], ['resource-id' => $photo['resource-id']]);
	}
}
