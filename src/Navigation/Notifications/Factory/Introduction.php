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

namespace Friendica\Navigation\Notifications\Factory;

use Exception;
use Friendica\App;
use Friendica\App\BaseURL;
use Friendica\BaseFactory;
use Friendica\Content\Text\BBCode;
use Friendica\Core\L10n;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Protocol;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Database\Database;
use Friendica\Model\Contact;
use Friendica\Module\BaseNotifications;
use Friendica\Navigation\Notifications\ValueObject;
use Friendica\Util\Proxy;
use Psr\Log\LoggerInterface;

/**
 * Factory for creating notification objects based on introductions
 * Currently, there are two main types of introduction based notifications:
 * - Friend suggestion
 * - Friend/Follower request
 */
class Introduction extends BaseFactory
{
	/** @var Database */
	private $dba;
	/** @var BaseURL */
	private $baseUrl;
	/** @var L10n */
	private $l10n;
	/** @var IManagePersonalConfigValues */
	private $pConfig;
	/** @var IHandleUserSessions */
	private $session;
	/** @var string */
	private $nick;

	public function __construct(LoggerInterface $logger, Database $dba, BaseURL $baseUrl, L10n $l10n, App $app, IManagePersonalConfigValues $pConfig, IHandleUserSessions $session)
	{
		parent::__construct($logger);

		$this->dba     = $dba;
		$this->baseUrl = $baseUrl;
		$this->l10n    = $l10n;
		$this->pConfig = $pConfig;
		$this->session = $session;
		$this->nick    = $app->getLoggedInUserNickname() ?? '';
	}

	/**
	 * Get introductions
	 *
	 * @param bool $all     If false only include introductions into the query
	 *                      which aren't marked as ignored
	 * @param int  $start   Start the query at this point
	 * @param int  $limit   Maximum number of query results
	 * @param int  $id      When set, only the introduction with this id is displayed
	 *
	 * @return ValueObject\Introduction[]
	 */
	public function getList(bool $all = false, int $start = 0, int $limit = BaseNotifications::DEFAULT_PAGE_LIMIT, int $id = 0): array
	{
		$sql_extra = "";

		if (empty($id)) {
			if (!$all) {
				$sql_extra = " AND NOT `ignore` ";
			}

			$sql_extra .= " AND NOT `intro`.`blocked` ";
		} else {
			$sql_extra = sprintf(" AND `intro`.`id` = %d ", $id);
		}

		$formattedIntroductions = [];

		try {
			$stmtNotifications = $this->dba->p(
				"SELECT `intro`.`id` AS `intro_id`, `intro`.*, `contact`.*,
				`sugggest-contact`.`name` AS `fname`, `sugggest-contact`.`url` AS `furl`, `sugggest-contact`.`addr` AS `faddr`,
				`sugggest-contact`.`photo` AS `fphoto`, `sugggest-contact`.`request` AS `frequest`
			FROM `intro`
				LEFT JOIN `contact` ON `contact`.`id` = `intro`.`contact-id`
				LEFT JOIN `contact` AS `sugggest-contact` ON `intro`.`suggest-cid` = `sugggest-contact`.`id`
			WHERE `intro`.`uid` = ? $sql_extra
			LIMIT ?, ?",
				$this->session->getLocalUserId(),
				$start,
				$limit
			);

			while ($intro = $this->dba->fetch($stmtNotifications)) {
				if (empty($intro['url'])) {
					continue;
				}

			// There are two kind of introduction. Contacts suggested by other contacts and normal connection requests.
				// We have to distinguish between these two because they use different data.
				// Contact suggestions
				if ($intro['suggest-cid'] ?? '') {
					if (empty($intro['furl'])) {
						continue;
					}
					$return_addr = bin2hex($this->nick . '@' .
					                       $this->baseUrl->getHost() .
										   (($this->baseUrl->getPath()) ? '/' . $this->baseUrl->getPath() : ''));

					$formattedIntroductions[] = new ValueObject\Introduction([
						'label'          => 'friend_suggestion',
						'str_type'       => $this->l10n->t('Friend Suggestion'),
						'intro_id'       => $intro['intro_id'],
						'madeby'         => $intro['name'],
						'madeby_url'     => $intro['url'],
						'madeby_zrl'     => Contact::magicLink($intro['url']),
						'madeby_addr'    => $intro['addr'],
						'contact_id'     => $intro['contact-id'],
						'photo'          => Contact::getAvatarUrlForUrl($intro['furl'], 0, Proxy::SIZE_SMALL),
						'name'           => $intro['fname'],
						'url'            => $intro['furl'],
						'zrl'            => Contact::magicLink($intro['furl']),
						'hidden'         => $intro['hidden'] == 1,
						'post_newfriend' => (intval($this->pConfig->get($this->session->getLocalUserId(), 'system', 'post_newfriend')) ? '1' : 0),
						'note'           => $intro['note'],
						'request'        => $intro['frequest'] . '?addr=' . $return_addr]);

					// Normal connection requests
				} else {
					// Don't show these data until you are connected. Diaspora is doing the same.
					if ($intro['network'] === Protocol::DIASPORA) {
						$intro['location'] = "";
						$intro['about']    = "";
					}

					$formattedIntroductions[] = new ValueObject\Introduction([
						'label'          => (($intro['network'] !== Protocol::OSTATUS) ? 'friend_request' : 'follower'),
						'str_type'       => (($intro['network'] !== Protocol::OSTATUS) ? $this->l10n->t('Friend/Connect Request') : $this->l10n->t('New Follower')),
						'dfrn_id'        => $intro['issued-id'],
						'uid'            => $this->session->getLocalUserId(),
						'intro_id'       => $intro['intro_id'],
						'contact_id'     => $intro['contact-id'],
						'photo'          => Contact::getPhoto($intro),
						'name'           => $intro['name'],
						'location'       => BBCode::convert($intro['location'], false),
						'about'          => BBCode::convert($intro['about'], false),
						'keywords'       => $intro['keywords'],
						'hidden'         => $intro['hidden'] == 1,
						'post_newfriend' => (intval($this->pConfig->get($this->session->getLocalUserId(), 'system', 'post_newfriend')) ? '1' : 0),
						'url'            => $intro['url'],
						'zrl'            => Contact::magicLink($intro['url']),
						'addr'           => $intro['addr'],
						'network'        => $intro['network'],
						'knowyou'        => $intro['knowyou'],
						'note'           => $intro['note'],
					]);
				}
			}
		} catch (Exception $e) {
			$this->logger->warning('Select failed.', ['uid' => $this->session->getLocalUserId(), 'exception' => $e]);
		}

		return $formattedIntroductions;
	}
}
