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

namespace Friendica\Object\Api\Mastodon\InstanceV2;

use Friendica\BaseDataTransferObject;

/**
 * Class Configuration
 *
 * @see https://docs.joinmastodon.org/entities/Instance/
 */
class Configuration extends BaseDataTransferObject
{
	/** @var StatusesConfig */
	protected $statuses;
	/** @var MediaAttachmentsConfig */
	protected $media_attachments;
	/** @var int */
	protected $image_size_limit;

	/**
	 * @param StatusesConfig $statuses
	 * @param MediaAttachmentsConfig $media_attachments
	 * @param int $image_size_limit
	 */
	public function __construct(
		StatusesConfig $statuses,
		MediaAttachmentsConfig $media_attachments,
		int $image_size_limit
	) {
		$this->statuses          = $statuses;
		$this->media_attachments = $media_attachments;
		$this->image_size_limit  = $image_size_limit;
	}
}
