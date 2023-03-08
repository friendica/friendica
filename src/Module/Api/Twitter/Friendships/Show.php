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

namespace Friendica\Module\Api\Twitter\Friendships;

use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Module\Api\Twitter\ContactEndpoint;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException\NotFoundException;

/**
 * @see https://developer.twitter.com/en/docs/twitter-api/v1/accounts-and-users/follow-search-get-users/api-reference/get-friendships-show
 */
class Show extends ContactEndpoint
{
	protected function rawContent(array $request = [])
	{
		self::checkAllowedScope(self::SCOPE_READ);
		$uid = BaseApi::getCurrentUserID();

		$source_cid = BaseApi::getContactIDForSearchterm($this->getRequestValue($request, 'source_screen_name', ''), '', $this->getRequestValue($request, 'source_id', 0), $uid);
		$target_cid = BaseApi::getContactIDForSearchterm($this->getRequestValue($request, 'target_screen_name', ''), '', $this->getRequestValue($request, 'target_id', 0), $uid);

		$source = Contact::getById($source_cid);
		if (empty($source)) {
			throw new NotFoundException('Source not found');
		}

		$target = Contact::getById($target_cid);
		if (empty($source)) {
			throw new NotFoundException('Target not found');
		}

		$follower  = false;
		$following = false;

		if ($source_cid == Contact::getPublicIdByUserId($uid)) {
			$cdata = Contact::getPublicAndUserContactID($target_cid, $uid);
			if (!empty($cdata['user'])) {
				$usercontact = Contact::getById($cdata['user'], ['rel']);
				switch ($usercontact['rel'] ?? Contact::NOTHING) {
					case Contact::FOLLOWER:
						$follower  = true;
						$following = false;
						break;

					case Contact::SHARING:
						$follower  = false;
						$following = true;
						break;

					case Contact::FRIEND:
						$follower  = true;
						$following = true;
						break;
				}
			}
		} else {
			$follower  = DBA::exists('contact-relation', ['cid' => $source_cid, 'relation-cid' => $target_cid, 'follows' => true]);
			$following = DBA::exists('contact-relation', ['relation-cid' => $source_cid, 'cid' => $target_cid, 'follows' => true]);
		}

		$relationship = [
			'relationship' => [
				'source' => [
					'id'                    => $source['id'],
					'id_str'                => (string)$source['id'],
					'screen_name'           => $source['nick'],
					'following'             => $following,
					'followed_by'           => $follower,
					'live_following'        => false,
					'following_received'    => null,
					'following_requested'   => null,
					'notifications_enabled' => null,
					'can_dm'                => $following && $follower,
					'blocking'              => null,
					'blocked_by'            => null,
					'muting'                => null,
					'want_retweets'         => null,
					'all_replies'           => null,
					'marked_spam'           => null
				],
				'target' => [
					'id'                  => $target['id'],
					'id_str'              => (string)$target['id'],
					'screen_name'         => $target['nick'],
					'following'           => $follower,
					'followed_by'         => $following,
					'following_received'  => null,
					'following_requested' => null
				]
			]
		];

		DI::apiResponse()->exit('relationship', ['relationship' => $relationship], $this->parameters['extension'] ?? null);
	}
}
