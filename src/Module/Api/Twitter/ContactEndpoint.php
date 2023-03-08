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

namespace Friendica\Module\Api\Twitter;

use Friendica\App;
use Friendica\Core\L10n;
use Friendica\DI;
use Friendica\Model\User;
use Friendica\Module\Api\ApiResponse;
use Friendica\Module\BaseApi;
use Friendica\Model\Contact;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use Friendica\Util\Strings;
use Psr\Log\LoggerInterface;

abstract class ContactEndpoint extends BaseApi
{
	const DEFAULT_COUNT = 20;
	const MAX_COUNT = 200;

	public function __construct(App $app, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, ApiResponse $response, array $server, array $parameters = [])
	{
		parent::__construct($app, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		self::checkAllowedScope(self::SCOPE_READ);
	}

	/**
	 * Computes the uid from the contact_id + screen_name parameters
	 *
	 * @param int|null $contact_id
	 * @param string   $screen_name
	 * @return int
	 * @throws HTTPException\NotFoundException
	 */
	protected static function getUid(int $contact_id = null, string $screen_name = null)
	{
		$uid = self::getCurrentUserID();

		if ($contact_id || $screen_name) {
			// screen_name trumps user_id when both are provided
			if (!$screen_name) {
				$contact = Contact::getById($contact_id, ['nick', 'url']);
				// We don't have the followers of remote accounts so we check for locality
				if (empty($contact) || !Strings::startsWith($contact['url'], DI::baseUrl())) {
					throw new HTTPException\NotFoundException(DI::l10n()->t('Contact not found'));
				}

				$screen_name = $contact['nick'];
			}

			$user = User::getByNickname($screen_name, ['uid']);
			if (empty($user)) {
				throw new HTTPException\NotFoundException(DI::l10n()->t('User not found'));
			}

			$uid = (int)$user['uid'];
		}

		return $uid;
	}

	/**
	 * This methods expands the contact ids into full user objects in an existing result set.
	 *
	 * @param array $ids           List of contact ids
	 * @param int   $total_count   Total list of contacts
	 * @param int   $uid           The local user id we query the contacts from
	 * @param int   $cursor
	 * @param int   $count
	 * @param bool  $skip_status
	 * @param bool  $include_user_entities
	 * @return array
	 * @throws HTTPException\InternalServerErrorException
	 * @throws HTTPException\NotFoundException
	 * @throws \ImagickException
	 */
	protected static function list(array $ids, int $total_count, int $uid, int $cursor = -1, int $count = self::DEFAULT_COUNT, bool $skip_status = false, bool $include_user_entities = true): array
	{
		$return = self::ids($ids, $total_count, $cursor, $count, false);

		$users = [];
		foreach ($return['ids'] as $contactId) {
			$users[] = DI::twitterUser()->createFromContactId($contactId, $uid, $skip_status, $include_user_entities);
		}

		unset($return['ids']);
		$return['users'] = $users;

		$return = [
			'users' => $users,
			'next_cursor' => $return['next_cursor'],
			'next_cursor_str' => $return['next_cursor_str'],
			'previous_cursor' => $return['previous_cursor'],
			'previous_cursor_str' => $return['previous_cursor_str'],
			'total_count' => $return['total_count'],
		];

		return $return;
	}

	/**
	 * @param array $ids           List of contact ids
	 * @param int   $total_count   Total list of contacts
	 * @param int   $cursor
	 * @param int   $count         Number of elements to return
	 * @param bool  $stringify_ids if "true" then the id is converted to a string
	 * @return array
	 * @throws HTTPException\NotFoundException
	 */
	protected static function ids(array $ids, int $total_count, int $cursor = -1, int $count = self::DEFAULT_COUNT, bool $stringify_ids = false): array
	{
		$next_cursor = 0;
		$previous_cursor = 0;

		// Cursor is on the user-specific contact id since it's the sort field
		if (count($ids)) {
			$previous_cursor = -$ids[0];
			$next_cursor = (int)$ids[count($ids) -1];
		}

		// No next page
		if ($total_count <= count($ids) || count($ids) < $count) {
			$next_cursor = 0;
		}
		// End of results
		if ($cursor < 0 && count($ids) === 0) {
			$next_cursor = -1;
		}

		// No previous page
		if ($cursor === -1) {
			$previous_cursor = 0;
		}

		if ($cursor > 0 && count($ids) === 0) {
			$previous_cursor = -$cursor;
		}

		if ($cursor < 0 && count($ids) === 0) {
			$next_cursor = -1;
		}

		if ($stringify_ids) {
			array_walk($ids, function (&$contactId) {
				$contactId = (string)$contactId;
			});
		}

		$return = [
			'ids' => $ids,
			'next_cursor' => $next_cursor,
			'next_cursor_str' => (string)$next_cursor,
			'previous_cursor' => $previous_cursor,
			'previous_cursor_str' => (string)$previous_cursor,
			'total_count' => $total_count,
		];

		return $return;
	}
}
