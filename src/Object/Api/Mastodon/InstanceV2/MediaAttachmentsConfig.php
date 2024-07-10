<?php
/**
 * @copyright Copyright (C) 2010-2024, the Friendica project
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

namespace Friendica\Object\Api\Mastodon\InstanceV2;

use Friendica\BaseDataTransferObject;

/**
 * Class MediaAttachmentsConfig
 *
 * @see https://docs.joinmastodon.org/entities/Instance/
 */
class MediaAttachmentsConfig extends BaseDataTransferObject
{
	/** @var string[] */
	protected $supported_mime_types;
	/** @var int */
	protected $image_size_limit;
	/** @var int */
	protected $image_matrix_limit;
	/** @var int */
	protected $video_size_limit = 0;
	/** @var int */
	protected $video_frame_rate_limit = 60;
	/** @var int */
	protected $video_matrix_limit = 0;

	/**
	 * @param array $supported_mime_types
	 */
	public function __construct(array $supported_mime_types, int $image_size_limit, int $image_matrix_limit)
	{
		$this->supported_mime_types = $supported_mime_types;
		$this->image_size_limit     = $image_size_limit;
		$this->image_matrix_limit   = $image_matrix_limit;
		$this->video_size_limit     = $image_size_limit;
		$this->video_matrix_limit   = $image_matrix_limit;
	}
}
