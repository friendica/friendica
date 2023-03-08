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

namespace Friendica\Module\Api\Twitter\Media\Metadata;

use Friendica\Core\Logger;
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
		BaseApi::checkAllowedScope(BaseApi::SCOPE_WRITE);
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

		Logger::info('Updating metadata', ['media_id' => $data['media_id']]);

		$condition = ['id' => $data['media_id'], 'uid' => $uid];

		$photo = Photo::selectFirst(['resource-id'], $condition);
		if (empty($photo['resource-id'])) {
			throw new BadRequestException('Metadata not found.');
		}

		Photo::update(['desc' => $data['alt_text']['text']], ['resource-id' => $photo['resource-id']]);
	}
}
