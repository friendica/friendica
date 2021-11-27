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

use Friendica\DI;
use Friendica\Model\Photo;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Network\HTTPException\InternalServerErrorException;

/**
 * API endpoint: /api/friendica/photoalbum/update
 */
class Update extends BaseApi
{
	protected function post(array $request = [], array $post = [])
	{
		self::checkAllowedScope(self::SCOPE_WRITE);
		$uid = self::getCurrentUserID();

		// we do not allow calls without album string
		if (empty($post['album'])) {
			throw new BadRequestException("no albumname specified");
		}
		if (empty($post['album_new'])) {
			throw new BadRequestException("no new albumname specified");
		}
		// check if album is existing
		if (!Photo::exists(['uid' => $uid, 'album' => $post['album']])) {
			throw new BadRequestException("album not available");
		}
		// now let's update all photos to the albumname
		$result = Photo::update(['album' => $post['album_new']], ['uid' => $uid, 'album' => $post['album']]);

		// return success of updating or error message
		if ($result) {
			$answer = ['result' => 'updated', 'message' => 'album `' . $post['album'] . '` with all containing photos has been renamed to `' . $post['album_new'] . '`.'];
			$this->response->exit('photoalbum_update', ['$result' => $answer], $this->parameters['extension'] ?? null);
		} else {
			throw new InternalServerErrorException("unknown error - updating in database failed");
		}
	}
}
