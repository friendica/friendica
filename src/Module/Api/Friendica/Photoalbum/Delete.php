<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
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

namespace Friendica\Module\Api\Friendica\Photoalbum;

use Friendica\Database\DBA;
use Friendica\Model\Item;
use Friendica\Model\Photo;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Network\HTTPException\InternalServerErrorException;

/**
 * API endpoint: /api/friendica/photoalbum/delete
 */
class Delete extends BaseApi
{
	/** @var bool Marks, if the deletion was already done */
	protected $deleted = false;

	protected function post(array $request = [], array $post = [])
	{
		$this->deletePhoto($post);
	}

	protected function delete(array $request = [])
	{
		$this->deletePhoto($request);
	}

	private function deletePhoto(array $input = [])
	{
		if ($this->deleted) {
			return;
		}

		$uid = self::getCurrentUserID();

		$input = $this->checkDefaults([
			'album' => '', // Album name
		], $input);

		// we do not allow calls without album string
		if (empty($input['album'])) {
			throw new BadRequestException("no albumname specified");
		}
		// check if album is existing

		$photos = DBA::selectToArray('photo', ['resource-id'], ['uid' => $uid, 'album' => $input['album']], ['group_by' => ['resource-id']]);
		if (!DBA::isResult($photos)) {
			throw new BadRequestException("album not available");
		}

		$resourceIds = array_column($photos, 'resource-id');

		// function for setting the items to "deleted = 1" which ensures that comments, likes etc. are not shown anymore
		// to the user and the contacts of the users (drop_items() performs the federation of the deletion to other networks
		$condition = ['uid' => $uid, 'resource-id' => $resourceIds];
		Item::deleteForUser($condition, $uid);

		// now let's delete all photos from the album
		$result = Photo::delete(['uid' => $uid, 'album' => $input['album']]);

		// return success of deletion or error message
		if ($result) {
			$this->deleted = true;
			$answer = ['result' => 'deleted', 'message' => 'album `' . $input['album'] . '` with all containing photos has been deleted.'];
			$this->response->exit('photoalbum_delete', ['$result' => $answer], $this->parameters['extension'] ?? null);
		} else {
			throw new InternalServerErrorException("unknown error - deleting from database failed");
		}
	}
}
