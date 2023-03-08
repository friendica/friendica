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

namespace Friendica\Protocol\Diaspora\Factory;

use Friendica\Capabilities\ICanCreateFromTableRow;
use Friendica\Database\DBA;
use GuzzleHttp\Psr7\Uri;

class DiasporaContact extends \Friendica\BaseFactory implements ICanCreateFromTableRow
{
	public function createFromTableRow(array $row): \Friendica\Protocol\Diaspora\Entity\DiasporaContact
	{
		return new \Friendica\Protocol\Diaspora\Entity\DiasporaContact(
			new Uri($row['url']),
			new \DateTime($row['created'], new \DateTimeZone('UTC')),
			$row['guid'],
			$row['addr'],
			$row['alias'] ? new Uri($row['alias']) : null,
			$row['nick'],
			$row['name'],
			$row['given-name'],
			$row['family-name'],
			$row['photo'] ? new Uri($row['photo']) : null,
			$row['photo-medium'] ? new Uri($row['photo-medium']) : null,
			$row['photo-small'] ? new Uri($row['photo-small']) : null,
			$row['batch'] ? new Uri($row['batch']) : null,
			$row['notify'] ? new Uri($row['notify']) : null,
			$row['poll'] ? new Uri($row['poll']) : null,
			$row['subscribe'],
			$row['searchable'],
			$row['pubkey'],
			$row['baseurl'] ? new Uri($row['baseurl']) : null,
			$row['gsid'],
			$row['updated'] !== DBA::NULL_DATETIME ? new \DateTime($row['updated'], new \DateTimeZone('UTC')) : null,
			$row['interacting_count'],
			$row['interacted_count'],
			$row['post_count'],
			$row['uri-id'],
		);
	}

	/**
	 * @param array     $data              Data returned by \Friendica\Network\Probe::uri()
	 * @param int       $uriId             The URI ID of the Diaspora contact URL + GUID
	 * @param \DateTime $created
	 * @param int       $interacting_count
	 * @param int       $interacted_count
	 * @param int       $post_count
	 * @return \Friendica\Protocol\Diaspora\Entity\DiasporaContact
	 */
	public function createfromProbeData(array $data, int $uriId, \DateTime $created, int $interacting_count = 0, int $interacted_count = 0, int $post_count = 0): \Friendica\Protocol\Diaspora\Entity\DiasporaContact
	{
		$alias = $data['alias'] != $data['url'] ? $data['alias'] : null;

		return new \Friendica\Protocol\Diaspora\Entity\DiasporaContact(
			new Uri($data['url']),
			$created,
			$data['guid'],
			$data['addr'],
			$alias ? new Uri($alias) : null,
			$data['nick'],
			$data['name'],
			$data['given-name'] ?? '',
			$data['family-name'] ?? '',
			$data['photo'] ? new Uri($data['photo']) : null,
			!empty($data['photo_medium']) ? new Uri($data['photo_medium']) : null,
			!empty($data['photo_small']) ? new Uri($data['photo_small']) : null,
			$data['batch'] ? new Uri($data['batch']) : null,
			$data['notify'] ? new Uri($data['notify']) : null,
			$data['poll'] ? new Uri($data['poll']) : null,
			$data['subscribe'],
			!$data['hide'],
			$data['pubkey'],
			$data['baseurl'] ? new Uri($data['baseurl']) : null,
			$data['gsid'],
			null,
			$interacting_count,
			$interacted_count,
			$post_count,
			$uriId,
		);
	}
}
