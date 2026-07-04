<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Navigation\Notifications\Factory;

use Exception;
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
	/** @var string */
	private $nick;

	public function __construct(LoggerInterface $logger, private readonly Database $dba, private readonly BaseURL $baseUrl, private readonly L10n $l10n, private readonly IManagePersonalConfigValues $pConfig, private readonly IHandleUserSessions $session)
	{
		parent::__construct($logger);
		$this->nick = $this->session->getLocalUserNickname() ?? '';
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
				`suggest-contact`.`name` AS `fname`, `suggest-contact`.`url` AS `furl`, `suggest-contact`.`addr` AS `faddr`,
				`suggest-contact`.`photo` AS `fphoto`, `suggest-contact`.`request` AS `frequest`
			FROM `intro`
				LEFT JOIN `contact` ON `contact`.`id` = `intro`.`contact-id`
				LEFT JOIN `contact` AS `suggest-contact` ON `intro`.`suggest-cid` = `suggest-contact`.`id`
			WHERE `intro`.`uid` = ? $sql_extra
			LIMIT ?, ?",
				$this->session->getLocalUserId(),
				$start,
				$limit,
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
					$return_addr = bin2hex($this->nick . '@'
										   . $this->baseUrl->getHost()
										   . (($this->baseUrl->getPath()) ? '/' . $this->baseUrl->getPath() : ''));

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
						'label'          => 'friend_request',
						'str_type'       => $this->l10n->t('Friend/Connect Request'),
						'dfrn_id'        => $intro['issued-id'],
						'uid'            => $this->session->getLocalUserId(),
						'intro_id'       => $intro['intro_id'],
						'contact_id'     => $intro['contact-id'],
						'photo'          => Contact::getPhoto($intro),
						'name'           => $intro['name'],
						'location'       => BBCode::convertForUriId($intro['uri-id'], $intro['location'], BBCode::EXTERNAL),
						'about'          => BBCode::convertForUriId($intro['uri-id'], $intro['about'], BBCode::EXTERNAL),
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
