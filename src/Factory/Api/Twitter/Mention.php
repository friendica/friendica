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

namespace Friendica\Factory\Api\Twitter;

use Friendica\App\BaseURL;
use Friendica\BaseFactory;
use Friendica\Model\Contact;
use Friendica\Model\Tag;
use Friendica\Network\HTTPException;
use Psr\Log\LoggerInterface;

class Mention extends BaseFactory
{
	/** @var BaseURL */
	private $baseUrl;

	public function __construct(LoggerInterface $logger, BaseURL $baseURL)
	{
		parent::__construct($logger);

		$this->baseUrl = $baseURL;
	}

	/**
	 * @param int $uriId Uri-ID of the item
	 * @return Array
	 * @throws HTTPException\InternalServerErrorException
	 */
	public function createFromUriId(int $uriId): array
	{
		$mentions = [];
		$tags     = Tag::getByURIId($uriId, [Tag::MENTION, Tag::EXCLUSIVE_MENTION, Tag::IMPLICIT_MENTION]);
		foreach ($tags as $tag) {
			$indices    = [];
			$contact    = Contact::getByURL($tag['url'], false);
			$object     = new \Friendica\Object\Api\Twitter\Mention($tag, $contact, $indices);
			$mentions[] = $object->toArray();
		}
		return $mentions;
	}
}
