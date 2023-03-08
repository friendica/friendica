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

namespace Friendica\Module\Api\Friendica\Photo;

use Friendica\Model\Item;
use Friendica\Model\Photo;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Network\HTTPException\InternalServerErrorException;

/**
 * API endpoint: /api/friendica/photo/delete
 */
class Delete extends BaseApi
{
	protected function post(array $request = [])
	{
		$uid = self::getCurrentUserID();

		$request = $this->getRequest([
			'photo_id' => '', // Photo id
		], $request);

		// do several checks on input parameters
		// we do not allow calls without photo id
		if (empty($request['photo_id'])) {
			throw new BadRequestException("no photo_id specified");
		}

		// check if photo is existing in database
		if (!Photo::exists(['resource-id' => $request['photo_id'], 'uid' => $uid])) {
			throw new BadRequestException("photo not available");
		}

		// now we can perform on the deletion of the photo
		$result = Photo::delete(['uid' => $uid, 'resource-id' => $request['photo_id']]);

		// return success of deletion or error message
		if ($result) {
			// function for setting the items to "deleted = 1" which ensures that comments, likes etc. are not shown anymore
			// to the user and the contacts of the users (drop_items() do all the necessary magic to avoid orphans in database and federate deletion)
			$condition = ['uid' => $uid, 'resource-id' => $request['photo_id'], 'post-type' => Item::PT_IMAGE, 'origin' => true];
			Item::deleteForUser($condition, $uid);
			Photo::clearAlbumCache($uid);
			$result = ['result' => 'deleted', 'message' => 'photo with id `' . $request['photo_id'] . '` has been deleted from server.'];
			$this->response->exit('photo_delete', ['$result' => $result], $this->parameters['extension'] ?? null);
		} else {
			throw new InternalServerErrorException("unknown error on deleting photo from database table");
		}
	}
}
