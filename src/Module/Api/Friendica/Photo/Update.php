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

use Friendica\App;
use Friendica\Core\ACL;
use Friendica\Core\L10n;
use Friendica\Factory\Api\Friendica\Photo as FriendicaPhoto;
use Friendica\Module\BaseApi;
use Friendica\Model\Photo;
use Friendica\Module\Api\ApiResponse;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * API endpoint: /api/friendica/photo/update
 */
class Update extends BaseApi
{
	/** @var FriendicaPhoto */
	private $friendicaPhoto;


	public function __construct(FriendicaPhoto $friendicaPhoto, App $app, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, ApiResponse $response, array $server, array $parameters = [])
	{
		parent::__construct($app, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->friendicaPhoto = $friendicaPhoto;
	}

	protected function post(array $request = [])
	{
		BaseApi::checkAllowedScope(BaseApi::SCOPE_WRITE);
		$uid  = BaseApi::getCurrentUserID();
		$type = $this->getRequestValue($this->parameters, 'extension', 'json');

		// input params
		$photo_id  = $this->getRequestValue($request, 'photo_id');
		$desc      = $this->getRequestValue($request, 'desc');
		$album     = $this->getRequestValue($request, 'album');
		$album_new = $this->getRequestValue($request, 'album_new');
		$allow_cid = $this->getRequestValue($request, 'allow_cid');
		$deny_cid  = $this->getRequestValue($request, 'deny_cid');
		$allow_gid = $this->getRequestValue($request, 'allow_gid');
		$deny_gid  = $this->getRequestValue($request, 'deny_gid');

		// do several checks on input parameters
		// we do not allow calls without album string
		if ($album == null) {
			throw new HTTPException\BadRequestException('no albumname specified');
		}

		// check if photo is existing in databasei
		if (!Photo::exists(['resource-id' => $photo_id, 'uid' => $uid, 'album' => $album])) {
			throw new HTTPException\BadRequestException('photo not available');
		}

		// checks on acl strings provided by clients
		$acl_input_error = false;
		$acl_input_error |= !ACL::isValidContact($allow_cid, $uid);
		$acl_input_error |= !ACL::isValidContact($deny_cid, $uid);
		$acl_input_error |= !ACL::isValidGroup($allow_gid, $uid);
		$acl_input_error |= !ACL::isValidGroup($deny_gid, $uid);
		if ($acl_input_error) {
			throw new HTTPException\BadRequestException('acl data invalid');
		}

		$updated_fields = [];

		if (!is_null($desc)) {
			$updated_fields['desc'] = $desc;
		}

		if (!is_null($album_new)) {
			$updated_fields['album'] = $album_new;
		}

		if (!is_null($allow_cid)) {
			$allow_cid                   = trim($allow_cid);
			$updated_fields['allow_cid'] = $allow_cid;
		}

		if (!is_null($deny_cid)) {
			$deny_cid                   = trim($deny_cid);
			$updated_fields['deny_cid'] = $deny_cid;
		}

		if (!is_null($allow_gid)) {
			$allow_gid                   = trim($allow_gid);
			$updated_fields['allow_gid'] = $allow_gid;
		}

		if (!is_null($deny_gid)) {
			$deny_gid                   = trim($deny_gid);
			$updated_fields['deny_gid'] = $deny_gid;
		}

		$result = false;
		if (count($updated_fields) > 0) {
			$nothingtodo = false;
			$result      = Photo::update($updated_fields, ['uid' => $uid, 'resource-id' => $photo_id, 'album' => $album]);
		} else {
			$nothingtodo = true;
		}

		if (!empty($_FILES['media'])) {
			$nothingtodo = false;
			$photo       = Photo::upload($uid, $_FILES['media'], $album, $allow_cid, $allow_gid, $deny_cid, $deny_gid, $desc, $photo_id);
			if (!empty($photo)) {
				$data = ['photo' => $this->friendicaPhoto->createFromId($photo['resource_id'], null, $uid, $type)];
				$this->response->exit('photo_update', $data, $this->parameters['extension'] ?? null);
				return;
			}
		}

		// return success of updating or error message
		if ($result) {
			Photo::clearAlbumCache($uid);
			$answer = ['result' => 'updated', 'message' => 'Image id `' . $photo_id . '` has been updated.'];
			$this->response->exit('photo_update', ['$result' => $answer], $this->parameters['extension'] ?? null);
			return;
		} else {
			if ($nothingtodo) {
				$answer = ['result' => 'cancelled', 'message' => 'Nothing to update for image id `' . $photo_id . '`.'];
				$this->response->exit('photo_update', ['$result' => $answer], $this->parameters['extension'] ?? null);
				return;
			}
			throw new HTTPException\InternalServerErrorException('unknown error - update photo entry in database failed');
		}

		throw new HTTPException\InternalServerErrorException('unknown error - this error on uploading or updating a photo should never happen');
	}
}
