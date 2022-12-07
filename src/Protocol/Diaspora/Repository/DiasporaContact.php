<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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

namespace Friendica\Protocol\Diaspora\Repository;

use Friendica\BaseRepository;
use Friendica\Core\System;
use Friendica\Database\Database;
use Friendica\Database\Definition\DbaDefinition;
use Friendica\Model\APContact;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\ItemURI;
use Friendica\Network\HTTPException;
use Friendica\Protocol\Diaspora\Entity;
use Friendica\Protocol\Diaspora\Factory;
use Friendica\Protocol\WebFingerUri;
use Friendica\Util\DateTimeFormat;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;

class DiasporaContact extends BaseRepository
{
	const ALWAYS_UPDATE                 = true;
	const NEVER_UPDATE                  = false;
	const UPDATE_IF_MISSING_OR_OUTDATED = null;

	protected static $table_name = 'diaspora-contact-view';

	/** @var Factory\DiasporaContact */
	protected $factory;
	/** @var DbaDefinition */
	private $definition;

	public function __construct(DbaDefinition $definition, Database $database, LoggerInterface $logger, Factory\DiasporaContact $factory)
	{
		parent::__construct($database, $logger, $factory);

		$this->definition = $definition;
	}

	/**
	 * @param array $condition
	 * @param array $params
	 * @return Entity\DiasporaContact
	 * @throws HTTPException\NotFoundException
	 */
	public function selectOne(array $condition, array $params = []): Entity\DiasporaContact
	{
		return parent::_selectOne($condition, $params);
	}

	/**
	 * @param int $uriId
	 * @return Entity\DiasporaContact
	 * @throws HTTPException\NotFoundException
	 */
	public function selectOneByUriId(int $uriId): Entity\DiasporaContact
	{
		return $this->selectOne(['uri-id' => $uriId]);
	}

	/**
	 * @param UriInterface $uri
	 * @return Entity\DiasporaContact
	 * @throws HTTPException\NotFoundException
	 */
	public function selectOneByUri(UriInterface $uri): Entity\DiasporaContact
	{
		try {
			return $this->selectOne(['url' => (string) $uri]);
		} catch (HTTPException\NotFoundException $e) {
		}

		try {
			return $this->selectOne(['addr' => (string) $uri]);
		} catch (HTTPException\NotFoundException $e) {
		}

		return $this->selectOne(['alias' => (string) $uri]);
	}

	/**
	 * @param WebFingerUri $uri
	 * @return Entity\DiasporaContact
	 * @throws HTTPException\NotFoundException
	 */
	public function selectOneByAddr(WebFingerUri $uri): Entity\DiasporaContact
	{
		return $this->selectOne(['addr' => $uri->getAddr()]);
	}

	/**
	 * @param int $uriId
	 * @return bool
	 * @throws \Exception
	 */
	public function existsByUriId(int $uriId): bool
	{
		return $this->db->exists(self::$table_name, ['uri-id' => $uriId]);
	}

	public function save(Entity\DiasporaContact $DiasporaContact): Entity\DiasporaContact
	{
		$uriId = $DiasporaContact->uriId ?? ItemURI::insert(['uri' => $DiasporaContact->url, 'guid' => $DiasporaContact->guid]);

		$fields = [
			'uri-id'            => $uriId,
			'addr'              => $DiasporaContact->addr,
			'alias'             => (string)$DiasporaContact->alias,
			'nick'              => $DiasporaContact->nick,
			'name'              => $DiasporaContact->name,
			'given-name'        => $DiasporaContact->givenName,
			'family-name'       => $DiasporaContact->familyName,
			'photo'             => (string)$DiasporaContact->photo,
			'photo-medium'      => (string)$DiasporaContact->photoMedium,
			'photo-small'       => (string)$DiasporaContact->photoSmall,
			'batch'             => (string)$DiasporaContact->batch,
			'notify'            => (string)$DiasporaContact->notify,
			'poll'              => (string)$DiasporaContact->poll,
			'subscribe'         => (string)$DiasporaContact->subscribe,
			'searchable'        => $DiasporaContact->searchable,
			'pubkey'            => $DiasporaContact->pubKey,
			'gsid'              => $DiasporaContact->gsid,
			'created'           => $DiasporaContact->created->format(DateTimeFormat::MYSQL),
			'updated'           => DateTimeFormat::utcNow(),
			'interacting_count' => $DiasporaContact->interacting_count,
			'interacted_count'  => $DiasporaContact->interacted_count,
			'post_count'        => $DiasporaContact->post_count,
		];

		// Limit the length on incoming fields
		$fields = $this->definition->truncateFieldsForTable('diaspora-contact', $fields);

		$this->db->insert('diaspora-contact', $fields, Database::INSERT_UPDATE);

		return $this->selectOneByUriId($uriId);
	}

	/**
	 * Fetch a Diaspora profile from a given WebFinger address and updates it depending on the mode
	 *
	 * @param WebFingerUri $uri    Profile address
	 * @param boolean      $update true = always update, false = never update, null = update when not found or outdated
	 * @return Entity\DiasporaContact
	 * @throws HTTPException\NotFoundException
	 */
	public function getByAddr(WebFingerUri $uri, ?bool $update = self::UPDATE_IF_MISSING_OR_OUTDATED): Entity\DiasporaContact
	{
		if ($update !== self::ALWAYS_UPDATE) {
			try {
				$dcontact = $this->selectOneByAddr($uri);
				if ($update === self::NEVER_UPDATE) {
					return $dcontact;
				}
			} catch (HTTPException\NotFoundException $e) {
				if ($update === self::NEVER_UPDATE) {
					throw $e;
				}

				// This is necessary for Contact::getByURL in case the base contact record doesn't need probing,
				// but we still need the result of a probe to create the missing diaspora-contact record.
				$update = self::ALWAYS_UPDATE;
			}
		}

		$contact = Contact::getByURL($uri, $update, ['uri-id']);
		if (empty($contact['uri-id'])) {
			throw new HTTPException\NotFoundException('Diaspora profile with URI ' . $uri . ' not found');
		}

		return self::selectOneByUriId($contact['uri-id']);
	}

	/**
	 * Fetch a Diaspora profile from a given profile URL and updates it depending on the mode
	 *
	 * @param UriInterface $uri    Profile URL
	 * @param boolean      $update true = always update, false = never update, null = update when not found or outdated
	 * @return Entity\DiasporaContact
	 * @throws HTTPException\NotFoundException
	 */
	public function getByUrl(UriInterface $uri, ?bool $update = self::UPDATE_IF_MISSING_OR_OUTDATED): Entity\DiasporaContact
	{
		if ($update !== self::ALWAYS_UPDATE) {
			try {
				$dcontact = $this->selectOneByUriId(ItemURI::getIdByURI($uri));
				if ($update === self::NEVER_UPDATE) {
					return $dcontact;
				}
			} catch (HTTPException\NotFoundException $e) {
				if ($update === self::NEVER_UPDATE) {
					throw $e;
				}

				// This is necessary for Contact::getByURL in case the base contact record doesn't need probing,
				// but we still need the result of a probe to create the missing diaspora-contact record.
				$update = self::ALWAYS_UPDATE;
			}
		}

		$contact = Contact::getByURL($uri, $update, ['uri-id']);
		if (empty($contact['uri-id'])) {
			throw new HTTPException\NotFoundException('Diaspora profile with URI ' . $uri . ' not found');
		}

		return self::selectOneByUriId($contact['uri-id']);
	}

	/**
	 * Update or create a diaspora-contact entry via a probe array
	 *
	 * @param array $data Probe array
	 * @return Entity\DiasporaContact
	 * @throws \Exception
	 */
	public function updateFromProbeArray(array $data): Entity\DiasporaContact
	{
		$uriId = ItemURI::insert(['uri' => $data['url'], 'guid' => $data['guid']]);

		$contact   = Contact::getByUriId($uriId, ['id', 'created']);
		$apcontact = APContact::getByURL($data['url'], false);
		if (!empty($apcontact)) {
			$interacting_count = $apcontact['followers_count'];
			$interacted_count  = $apcontact['following_count'];
			$post_count        = $apcontact['statuses_count'];
		} elseif (!empty($contact['id'])) {
			$last_interaction = DateTimeFormat::utc('now - 180 days');

			$interacting_count = $this->db->count('contact-relation', ["`relation-cid` = ? AND NOT `follows` AND `last-interaction` > ?", $contact['id'], $last_interaction]);
			$interacted_count  = $this->db->count('contact-relation', ["`cid` = ? AND NOT `follows` AND `last-interaction` > ?", $contact['id'], $last_interaction]);
			$post_count        = $this->db->count('post', ['author-id' => $contact['id'], 'gravity' => [Item::GRAVITY_PARENT, Item::GRAVITY_COMMENT]]);
		}

		$DiasporaContact = $this->factory->createfromProbeData(
			$data,
			$uriId,
			new \DateTime($contact['created'] ?? 'now', new \DateTimeZone('UTC')),
			$interacting_count ?? 0,
			$interacted_count ?? 0,
			$post_count ?? 0
		);

		$DiasporaContact = $this->save($DiasporaContact);

		$this->logger->info('Updated diaspora-contact', ['url' => (string) $DiasporaContact->url, 'callstack' => System::callstack(20)]);

		return $DiasporaContact;
	}

	/**
	 * get a url (scheme://domain.tld/u/user) from a given contact guid
	 *
	 * @param mixed $guid Hexadecimal string guid
	 *
	 * @return string the contact url or null
	 * @throws \Exception
	 */
	public function getUrlByGuid(string $guid): ?string
	{
		$diasporaContact = $this->db->selectFirst(self::$table_name, ['url'], ['guid' => $guid]);

		return $diasporaContact['url'] ?? null;
	}
}
