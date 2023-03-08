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

namespace Friendica\Object\Api\Twitter;

use Friendica\BaseDataTransferObject;
use Friendica\Model\Post;

/**
 * Class Media
 *
 * @see https://developer.twitter.com/en/docs/twitter-api/v1/data-dictionary/object-model/entities#media
 */
class Media extends BaseDataTransferObject
{
	/** @var string */
	protected $display_url;
	/** @var string */
	protected $expanded_url;
	/** @var int */
	protected $id;
	/** @var string */
	protected $id_str;
	/** @var array */
	protected $indices;
	/** @var string */
	protected $media_url;
	/** @var string */
	protected $media_url_https;
	/** @var string */
	protected $sizes;
	/** @var string */
	protected $type;
	/** @var string */
	protected $url;

	/**
	 * Creates a media entity array
	 *
	 * @param array $attachment
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function __construct(array $media, string $url, array $indices)
	{
		$this->display_url     = $media['url'];
		$this->expanded_url    = $media['url'];
		$this->id              = $media['id'];
		$this->id_str          = (string)$media['id'];
		$this->indices         = $indices;
		$this->media_url       = $media['url'];
		$this->media_url_https = $media['url'];
		$this->type            = $media['type'] == Post\Media::IMAGE ? 'photo' : 'video';
		$this->url             = $url;

		if (!empty($media['height']) && !empty($media['width'])) {
			if (($media['height'] <= 680) && ($media['width'] <= 680)) {
				$size = 'small';
			} elseif (($media['height'] <= 1200) && ($media['width'] <= 1200)) {
				$size = 'medium';
			} else {
				$size = 'large';
			}

			$this->sizes = [
				$size => [
					'h'      => $media['height'],
					'resize' => 'fit',
					'w'      => $media['width'],
				]
			];
		}
	}

	/**
	 * Returns the current entity as an array
	 *
	 * @return array
	 */
	public function toArray(): array
	{
		$status = parent::toArray();

		if (empty($status['indices'])) {
			unset($status['indices']);
		}

		return $status;
	}
}
