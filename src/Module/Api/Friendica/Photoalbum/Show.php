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

namespace Friendica\Module\Api\Friendica\Photoalbum;

use Friendica\App;
use Friendica\Core\L10n;
use Friendica\Factory\Api\Friendica\Photo as FriendicaPhoto;
use Friendica\Model\Contact;
use Friendica\Model\Photo;
use Friendica\Module\Api\ApiResponse;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * api/friendica/photoalbum/:name
 *
 * @package  Friendica\Module\Api\Friendica\Photoalbum
 */
class Show extends BaseApi
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
		$uid     = BaseApi::getCurrentUserID();
		$type    = $this->getRequestValue($this->parameters, 'extension', 'json');
		$request = $this->getRequest([
			'album'        => '',    // Get pictures in this album
			'offset'       => 0,     // Return results offset by this value
			'limit'        => 50,    // Maximum number of results to return. Defaults to 50. Max 500
			'latest_first' => false, // Whether to reverse the order so newest are first
		], $request);

		if (empty($request['album'])) {
			throw new HTTPException\BadRequestException('No album name specified.');
		}

		$orderDescending = $request['latest_first'];
		$album           = $request['album'];
		$condition       = ["`uid` = ? AND `album` = ?", $uid, $album];
		$params          = ['order' => ['id' => $orderDescending], 'group_by' => ['resource-id']];

		$limit = $request['limit'];
		if ($limit > 500) {
			$limit = 500;
		}

		if ($limit <= 0) {
			$limit = 1;
		}

		if (!empty($request['offset'])) {
			$params['limit'] = [$request['offset'], $limit];
		} else {
			$params['limit'] = $limit;
		}

		$photos = Photo::selectToArray(['resource-id'], $condition, $params);

		$data = ['photo' => []];
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

		$this->response->exit('statuses', $data, $this->parameters['extension'] ?? null, Contact::getPublicIdByUserId($uid));
	}
}
