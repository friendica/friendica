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

namespace Friendica\Module\Api\Friendica\Profile;

use Friendica\Profile\ProfileField\Collection\ProfileFields;
use Friendica\Content\Text\BBCode;
use Friendica\Core\Search;
use Friendica\DI;
use Friendica\Model\Profile;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException;

/**
 * API endpoint: /api/friendica/profile/show
 */
class Show extends BaseApi
{
	protected function rawContent(array $request = [])
	{
		self::checkAllowedScope(self::SCOPE_READ);
		$uid = self::getCurrentUserID();

		// retrieve general information about profiles for user
		$directory = Search::getGlobalDirectory();

		$profile = Profile::getByUID($uid);

		$profileFields = DI::profileField()->selectPublicFieldsByUserId($uid);

		$profile = self::formatProfile($profile, $profileFields);

		$profiles = [];
		if (($this->parameters['extension'] ?? '') == 'xml') {
			$profiles['0:profile'] = $profile;
		} else {
			$profiles[] = $profile;
		}

		$result = [
			'multi_profiles' => false,
			'global_dir' => $directory,
			'friendica_owner' => DI::twitterUser()->createFromUserId($uid),
			'profiles' => $profiles
		];

		$this->response->exit('friendica_profiles', ['$result' => $result], $this->parameters['extension'] ?? null);
	}

	/**
	 * @param array         $profile_row array containing data from db table 'profile'
	 * @param ProfileFields $profileFields
	 *
	 * @return array
	 * @throws HTTPException\InternalServerErrorException
	 */
	private static function formatProfile($profile_row, ProfileFields $profileFields): array
	{
		$custom_fields = [];
		foreach ($profileFields as $profileField) {
			$custom_fields[] = [
				'label' => $profileField->label,
				'value' => BBCode::convert($profileField->value, false, BBCode::TWITTER_API),
			];
		}

		return [
			'profile_id'       => $profile_row['id'],
			'profile_name'     => null,
			'is_default'       => null,
			'hide_friends'     => $profile_row['hide-friends'] ? true : false,
			'profile_photo'    => $profile_row['photo'],
			'profile_thumb'    => $profile_row['thumb'],
			'publish'          => $profile_row['publish'] ? true : false,
			'net_publish'      => $profile_row['net-publish'] ? true : false,
			'description'      => $profile_row['about'],
			'date_of_birth'    => $profile_row['dob'],
			'address'          => $profile_row['address'],
			'city'             => $profile_row['locality'],
			'region'           => $profile_row['region'],
			'postal_code'      => $profile_row['postal-code'],
			'country'          => $profile_row['country-name'],
			'hometown'         => null,
			'gender'           => null,
			'marital'          => null,
			'marital_with'     => null,
			'marital_since'    => null,
			'sexual'           => null,
			'politic'          => null,
			'religion'         => null,
			'public_keywords'  => $profile_row['pub_keywords'],
			'private_keywords' => $profile_row['prv_keywords'],
			'likes'            => null,
			'dislikes'         => null,
			'about'            => null,
			'music'            => null,
			'book'             => null,
			'tv'               => null,
			'film'             => null,
			'interest'         => null,
			'romance'          => null,
			'work'             => null,
			'education'        => null,
			'social_networks'  => null,
			'homepage'         => $profile_row['homepage'],
			'users'            => [],
			'custom_fields'    => $custom_fields,
		];
	}
}
