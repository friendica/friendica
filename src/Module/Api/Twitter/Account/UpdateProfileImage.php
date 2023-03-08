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

namespace Friendica\Module\Api\Twitter\Account;

use Friendica\Module\BaseApi;
use Friendica\DI;
use Friendica\Model\Photo;
use Friendica\Network\HTTPException;

/**
 * updates the profile image for the user (either a specified profile or the default profile)
 *
 * @see https://developer.twitter.com/en/docs/accounts-and-users/manage-account-settings/api-reference/post-account-update_profile_image
 */
class UpdateProfileImage extends BaseApi
{
	protected function post(array $request = [])
	{
		BaseApi::checkAllowedScope(BaseApi::SCOPE_WRITE);
		$uid = BaseApi::getCurrentUserID();

		// get mediadata from image or media (Twitter call api/account/update_profile_image provides image)
		if (!empty($_FILES['image'])) {
			$media = $_FILES['image'];
		} elseif (!empty($_FILES['media'])) {
			$media = $_FILES['media'];
		}

		// error if image data is missing
		if (empty($media)) {
			throw new HTTPException\BadRequestException('no media data submitted');
		}

		// save new profile image
		$resource_id = Photo::uploadAvatar($uid, $media);
		if (empty($resource_id)) {
			throw new HTTPException\InternalServerErrorException('image upload failed');
		}

		// output for client
		$skip_status = $this->getRequestValue($request, 'skip_status', false);

		$user_info = DI::twitterUser()->createFromUserId($uid, $skip_status)->toArray();

		// "verified" isn't used here in the standard
		unset($user_info['verified']);

		// "uid" is only needed for some internal stuff, so remove it from here
		unset($user_info['uid']);

		$this->response->exit('user', ['user' => $user_info], $this->parameters['extension'] ?? null);
	}
}
