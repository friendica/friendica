<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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

namespace Friendica\Domain\Factory\Api\Mastodon;

use Friendica\App\BaseURL;
use Friendica\Domain\BaseFactory;
use Friendica\DI;
use Friendica\Domain\Entity\Api\Mastodon\Status as MastodonStatusEntity;
use Friendica\Model\Item;
use Friendica\Network\HTTPException;
use Friendica\Domain\Repository\ProfileField;
use Psr\Log\LoggerInterface;

class Status extends BaseFactory
{
	/** @var BaseURL */
	protected $baseUrl;
	/** @var ProfileField */
	protected $profileField;
	/** @var Field */
	protected $mstdnField;

	public function __construct(LoggerInterface $logger, BaseURL $baseURL, ProfileField $profileField, Field $mstdnField)
	{
		parent::__construct($logger);

		$this->baseUrl = $baseURL;
		$this->profileField = $profileField;
		$this->mstdnField = $mstdnField;
	}

	/**
	 * @param int $uriId Uri-ID of the item
	 * @param int $uid   Item user
	 *
	 * @return MastodonStatusEntity
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public function createFromUriId(int $uriId, $uid = 0)
	{
		$item = Item::selectFirst([], ['uri-id' => $uriId, 'uid' => $uid]);
		$account = DI::mstdnAccount()->createFromContactId($item['author-id']);

		return new MastodonStatusEntity($item, $account);
	}
}
