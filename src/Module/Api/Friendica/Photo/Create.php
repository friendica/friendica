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
 * API endpoint: /api/friendica/photo/create
 */
class Create extends BaseApi
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
		$desc      = $this->getRequestValue($request, 'desc', '');
		$album     = $this->getRequestValue($request, 'album');
		$allow_cid = $this->getRequestValue($request, 'allow_cid');
		$deny_cid  = $this->getRequestValue($request, 'deny_cid');
		$allow_gid = $this->getRequestValue($request, 'allow_gid');
		$deny_gid  = $this->getRequestValue($request, 'deny_gid');

		// do several checks on input parameters
		// we do not allow calls without album string
		if ($album === null) {
			throw new HTTPException\BadRequestException('no album name specified');
		}

		// error if no media posted in create-mode
		if (empty($_FILES['media'])) {
			// Output error
			throw new HTTPException\BadRequestException('no media data submitted');
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
		// now let's upload the new media in create-mode
		$photo = Photo::upload($uid, $_FILES['media'], $album, trim($allow_cid), trim($allow_gid), trim($deny_cid), trim($deny_gid), $desc);

		// return success of updating or error message
		if (!empty($photo)) {
			Photo::clearAlbumCache($uid);
			$data = ['photo' => $this->friendicaPhoto->createFromId($photo['resource_id'], null, $uid, $type)];
			$this->response->exit('photo_create', $data, $this->parameters['extension'] ?? null);
		} else {
			throw new HTTPException\InternalServerErrorException('unknown error - uploading photo failed, see Friendica log for more information');
		}
	}
}
