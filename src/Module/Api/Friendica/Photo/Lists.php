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

use Friendica\Database\DBA;
use Friendica\App;
use Friendica\Core\L10n;
use Friendica\Factory\Api\Friendica\Photo as FriendicaPhoto;
use Friendica\Module\BaseApi;
use Friendica\Model\Contact;
use Friendica\Model\Photo;
use Friendica\Module\Api\ApiResponse;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Returns all lists the user subscribes to.
 *
 * @see https://developer.twitter.com/en/docs/accounts-and-users/create-manage-lists/api-reference/get-lists-list
 */
class Lists extends BaseApi
{
	/** @var FriendicaPhoto */
	private $friendicaPhoto;


	public function __construct(FriendicaPhoto $friendicaPhoto, App $app, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, ApiResponse $response, array $server, array $parameters = [])
	{
		parent::__construct($app, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->friendicaPhoto = $friendicaPhoto;
	}

	protected function rawContent(array $request = [])
	{
		BaseApi::checkAllowedScope(BaseApi::SCOPE_READ);
		$uid  = BaseApi::getCurrentUserID();
		$type = $this->getRequestValue($this->parameters, 'extension', 'json');

		$photos = Photo::selectToArray(['resource-id'], ["`uid` = ? AND NOT `photo-type` IN (?, ?)", $uid, Photo::CONTACT_AVATAR, Photo::CONTACT_BANNER],
			['order' => ['id'], 'group_by' => ['resource-id']]);

		$data = ['photo' => []];
		if (DBA::isResult($photos)) {
			foreach ($photos as $photo) {
				$element = $this->friendicaPhoto->createFromId($photo['resource-id'], null, $uid, 'json', false);

				$element['thumb'] = end($element['link']);
				unset($element['link']);

				if ($type == 'xml') {
					$thumb = $element['thumb'];
					unset($element['thumb']);
					$data['photo'][] = ['@attributes' => $element, '1' => $thumb];
				} else {
					$data['photo'][] = $element;
				}
			}
		}

		$this->response->exit('statuses', $data, $this->parameters['extension'] ?? null, Contact::getPublicIdByUserId($uid));
	}
}
