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

namespace Friendica\Module\Api\Twitter\Statuses;

use Friendica\Content\Text\HTML;
use Friendica\Content\Text\Markdown;
use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\Photo;
use Friendica\Model\Post;
use Friendica\Model\User;
use Friendica\Module\BaseApi;
use Friendica\Protocol\Activity;
use Friendica\Util\Images;
use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * Updates the user’s current status.
 *
 * @see https://developer.twitter.com/en/docs/tweets/post-and-engage/api-reference/post-statuses-update
 */
class Update extends BaseApi
{
	public function post(array $request = [])
	{
		self::checkAllowedScope(self::SCOPE_WRITE);
		$uid = self::getCurrentUserID();

		$owner = User::getOwnerDataById($uid);

		$request = self::getRequest([
			'htmlstatus'            => '',
			'status'                => '',
			'title'                 => '',
			'in_reply_to_status_id' => 0,
			'lat'                   => 0,
			'long'                  => 0,
			'media_ids'             => '',
			'source'                => '',
			'include_entities'      => false,
			'contact_allow'         => $owner['allow_cid'],
			'group_allow'           => $owner['allow_gid'],
			'contact_deny'          => $owner['deny_cid'],
			'group_deny'            => $owner['deny_gid'],
		], $request);

		if (!empty($request['htmlstatus'])) {
			$body = HTML::toBBCodeVideo($request['htmlstatus']);

			$config = HTMLPurifier_Config::createDefault();
			$config->set('Cache.DefinitionImpl', null);

			$purifier = new HTMLPurifier($config);
			$body     = $purifier->purify($body);

			$body = HTML::toBBCode($request['htmlstatus']);
		} else {
			// The imput is defined as text. So we can use Markdown for some enhancements
			$body = Markdown::toBBCode($request['status']);
		}

		$item               = [];
		$item['network']    = Protocol::DFRN;
		$item['uid']        = $uid;
		$item['verb']       = Activity::POST;
		$item['contact-id'] = $owner['id'];
		$item['author-id']  = $item['owner-id'] = Contact::getPublicIdByUserId($uid);
		$item['title']      = $request['title'];
		$item['body']       = $body;
		$item['app']        = $request['source'];

		if (empty($item['app']) && !empty(self::getCurrentApplication()['name'])) {
			$item['app'] = self::getCurrentApplication()['name'];
		}

		if (!empty($request['lat']) && !empty($request['long'])) {
			$item['coord'] = sprintf("%s %s", $request['lat'], $request['long']);
		}

		$aclFormatter = DI::aclFormatter();
		$item['allow_cid'] = $aclFormatter->toString($request['contact_allow']);
		$item['allow_gid'] = $aclFormatter->toString($request['group_allow']);
		$item['deny_cid']  = $aclFormatter->toString($request['contact_deny']);
		$item['deny_gid']  = $aclFormatter->toString($request['group_deny']);

		if (!empty($item['allow_cid'] . $item['allow_gid'] . $item['deny_cid'] . $item['deny_gid'])) {
			$item['private'] = Item::PRIVATE;
		} elseif (DI::pConfig()->get($uid, 'system', 'unlisted')) {
			$item['private'] = Item::UNLISTED;
		} else {
			$item['private'] = Item::PUBLIC;
		}

		if ($request['in_reply_to_status_id']) {
			$parent = Post::selectFirst(['uri'], ['uri-id' => $request['in_reply_to_status_id'], 'uid' => [0, $uid]]);

			$item['thr-parent']  = $parent['uri'];
			$item['gravity']     = Item::GRAVITY_COMMENT;
			$item['object-type'] = Activity\ObjectType::COMMENT;
		} else {
			self::checkThrottleLimit();

			$item['gravity']     = Item::GRAVITY_PARENT;
			$item['object-type'] = Activity\ObjectType::NOTE;
		}

		$item = DI::contentItem()->expandTags($item);

		if (!empty($request['media_ids'])) {
			$ids = explode(',', $request['media_ids']);
		} elseif (!empty($_FILES['media'])) {
			// upload the image if we have one
			$picture = Photo::upload($uid, $_FILES['media']);
			if (!empty($picture)) {
				$ids[] = $picture['id'];
			}
		}

		if (!empty($ids)) {
			$item['object-type'] = Activity\ObjectType::IMAGE;
			$item['post-type']   = Item::PT_IMAGE;
			$item['attachments'] = [];

			foreach ($ids as $id) {
				$media = DBA::toArray(DBA::p("SELECT `resource-id`, `scale`, `type`, `desc`, `filename`, `datasize`, `width`, `height` FROM `photo`
						WHERE `resource-id` IN (SELECT `resource-id` FROM `photo` WHERE `id` = ?) AND `photo`.`uid` = ?
						ORDER BY `photo`.`width` DESC LIMIT 2", $id, $uid));

				if (empty($media)) {
					continue;
				}

				Photo::setPermissionForRessource($media[0]['resource-id'], $uid, $item['allow_cid'], $item['allow_gid'], $item['deny_cid'], $item['deny_gid']);

				$ressources[] = $media[0]['resource-id'];
				$phototypes   = Images::supportedTypes();
				$ext          = $phototypes[$media[0]['type']];

				$attachment = [
					'type'        => Post\Media::IMAGE,
					'mimetype'    => $media[0]['type'],
					'url'         => DI::baseUrl() . '/photo/' . $media[0]['resource-id'] . '-' . $media[0]['scale'] . '.' . $ext,
					'size'        => $media[0]['datasize'],
					'name'        => $media[0]['filename'] ?: $media[0]['resource-id'],
					'description' => $media[0]['desc'] ?? '',
					'width'       => $media[0]['width'],
					'height'      => $media[0]['height']
				];

				if (count($media) > 1) {
					$attachment['preview']        = DI::baseUrl() . '/photo/' . $media[1]['resource-id'] . '-' . $media[1]['scale'] . '.' . $ext;
					$attachment['preview-width']  = $media[1]['width'];
					$attachment['preview-height'] = $media[1]['height'];
				}
				$item['attachments'][] = $attachment;
			}
		}

		$id = Item::insert($item, true);
		if (!empty($id)) {
			$item = Post::selectFirst(['uri-id'], ['id' => $id]);
			if (!empty($item['uri-id'])) {
				// output the post that we just posted.
				$status_info = DI::twitterStatus()->createFromUriId($item['uri-id'], $uid, $request['include_entities'])->toArray();
				DI::apiResponse()->exit('status', ['status' => $status_info], $this->parameters['extension'] ?? null, Contact::getPublicIdByUserId($uid));
				return;
			}
		}
		DI::mstdnError()->InternalError();
	}
}
