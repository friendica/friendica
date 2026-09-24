<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Content;

use Friendica\App\BaseURL;
use Friendica\Content\Contact\Repository\ContactByType;
use Friendica\Content\Text\BBCode;
use Friendica\Content\Text\HTML;
use Friendica\Content\Text\Plaintext;
use Friendica\Core\Addon\AddonHelper;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\Worker;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Model\GServer;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Proxy;
use Friendica\Worker\UpdateServerDirectory;

/**
 * This class handles methods related to the group functionality
 */
class GroupManager
{
	private const CONTACT_TYPES = [Contact::TYPE_COMMUNITY];

	// Like on the contact page: public threads come from the global copy, private ones from the copy of the user
	private const THREADS = "FROM `post-thread-user` INNER JOIN `post-user` ON `post-user`.`id` = `post-thread-user`.`post-user-id`
		WHERE (`post-thread-user`.`uid` = 0 OR (`post-thread-user`.`uid` = ? AND NOT `post-user`.`global`))";

	public function __construct(
		private readonly ContactByType $contacts,
		private readonly AddonHelper $addonHelper,
		private readonly BaseURL $baseUrl,
		private readonly L10n $l10n,
		private readonly IHandleUserSessions $session,
		private readonly Database $database,
	) {}

	/**
	 * Function to list all groups a user is connected with
	 *
	 * @param int     $uid         of the profile owner
	 * @param boolean $lastitem    Sort by lastitem
	 * @param boolean $showhidden  Show groups which are not hidden
	 * @param boolean $showprivate Show private groups
	 *
	 * @return array
	 *    'url'    => group url
	 *    'name'    => group name
	 *    'id'    => number of the key from the array
	 *    'micro' => contact photo in format micro
	 *    'thumb' => contact photo in format thumb
	 * @throws \Exception
	 */
	public function getList(int $uid, bool $lastitem, bool $showhidden = true, bool $showprivate = false): array
	{
		return $this->contacts->selectForUser($uid, self::CONTACT_TYPES, $lastitem, $showhidden, $showprivate);
	}


	/**
	 * Group list widget
	 *
	 * Sidebar widget to show subscribed Friendica groups. If activated
	 * in the settings, it appears in the network page sidebar
	 *
	 * @param int $uid The ID of the User
	 * @return string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public function widget(int $uid): string
	{
		//sort by last updated item
		$contacts      = $this->getList($uid, true, true, true);
		$total         = count($contacts);
		$visibleGroups = 10;

		$id = 0;

		$entries = [];

		foreach ($contacts as $contact) {
			$entry = [
				'url'          => 'contact/' . $contact['id'] . '/conversations',
				'external_url' => Contact::magicLinkByContact($contact),
				'name'         => $contact['name'],
				'cid'          => $contact['id'],
				'micro'        => $this->baseUrl->remove(Contact::getMicro($contact)),
				'id'           => ++$id,
			];
			$entries[] = $entry;
		}

		$tpl = Renderer::getMarkupTemplate('widget/group_list.tpl');

		return Renderer::replaceMacros(
			$tpl,
			[
				'$title'                         => $this->l10n->t('Groups'),
				'$groups'                        => $entries,
				'$link_desc'                     => $this->l10n->t('External link to group'),
				'$new_group_page'                => 'register/?type=group',
				'$total'                         => $total,
				'$visible_groups'                => $visibleGroups,
				'$showless'                      => $this->l10n->t('show less'),
				'$showmore'                      => $this->l10n->t('show more'),
				'$create_new_group'              => $this->l10n->t('Create new group'),
				'$addon_group_directory_enabled' => $this->addonHelper->isAddonEnabled('groupdirectory'),
				'$visit_groupdirectory'          => $this->l10n->t('Find groups to join'),
			],
		);
	}

	/**
	 * Format group list as contact block
	 *
	 * This function is used to show the group list in
	 * the advanced profile.
	 *
	 * @param int $uid The ID of the User
	 * @return string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public function profileAdvanced(int $uid): string
	{
		if (!Feature::isEnabled($uid, Feature::GROUPS)) {
			return '';
		}

		$o = '';

		// placeholder in case somebody wants configurability
		$show_total = 9999;

		//don't sort by last updated item
		$lastitem = false;

		$contacts = $this->getList($uid, $lastitem, false, false);

		$total_shown = 0;
		foreach ($contacts as $contact) {
			$o .= HTML::micropro($contact, true, 'grouplist-profile-advanced');
			$total_shown++;
			if ($total_shown == $show_total) {
				break;
			}
		}

		return $o;
	}

	/**
	 * count unread group items
	 *
	 * Count unread items of connected groups and private groups
	 *
	 * @return array
	 *    'id' => contact id
	 *    'name' => contact/group name
	 *    'count' => counted unseen group items
	 * @throws \Exception
	 */
	public function countUnseenItems(): array
	{
		$uid = $this->session->getLocalUserId();
		if (!is_int($uid)) {
			return [];
		}

		return $this->contacts->countUnseenItems($uid, self::CONTACT_TYPES);
	}

	/**
	 * Marks all posts in the threads of a group as seen
	 *
	 * @param int $uid  User id
	 * @param int $pcid Public contact id of the group
	 * @throws \Exception
	 */
	public function markSeen(int $uid, int $pcid): void
	{
		Item::update(['unseen' => false], ["`uid` = ? AND `unseen` AND `parent-uri-id` IN (SELECT `uri-id` FROM `post-thread-user` WHERE `uid` = ? AND `owner-id` = ?)", $uid, $uid, $pcid]);
	}

	/**
	 * Fetches the number of posts, the number of unread posts and the date of the latest post per thread in one go
	 *
	 * @param int   $uid          User id
	 * @param int[] $parentUriIds Uri-ids of the threads
	 * @param int[] $gravity      Gravities of the posts that are counted
	 *
	 * @return array Statistics keyed by the uri-id of the thread
	 * @throws \Exception
	 */
	public function getThreadStats(int $uid, array $parentUriIds, array $gravity): array
	{
		if (empty($parentUriIds)) {
			return [];
		}

		$stats = [];

		$gravityPlaceholders = implode(', ', array_fill(0, count($gravity), '?'));
		$parentPlaceholders  = implode(', ', array_fill(0, count($parentUriIds), '?'));

		$posts = $this->database->p(
			"SELECT `parent-uri-id`, COUNT(*) AS `posts`, MAX(`received`) AS `received` FROM `post-user`
				WHERE (`uid` = 0 OR (`uid` = ? AND NOT `global`)) AND `visible` AND NOT `deleted` AND `gravity` IN (" . $gravityPlaceholders . ")
				AND `parent-uri-id` IN (" . $parentPlaceholders . ")
				GROUP BY `parent-uri-id`",
			$uid,
			...$gravity,
			...$parentUriIds,
		);
		while ($row = $this->database->fetch($posts)) {
			$stats[$row['parent-uri-id']] = [
				'posts'    => (int) $row['posts'],
				'unread'   => 0,
				'received' => $row['received'],
			];
		}
		$this->database->close($posts);

		// The global copy has no seen state, so the unread posts are counted on the copy of the user
		$unread = $this->database->p(
			"SELECT `parent-uri-id`, COUNT(*) AS `unread` FROM `post-user`
				WHERE `uid` = ? AND `unseen` AND `visible` AND NOT `deleted` AND `gravity` IN (" . $gravityPlaceholders . ")
				AND `parent-uri-id` IN (" . $parentPlaceholders . ")
				GROUP BY `parent-uri-id`",
			$uid,
			...$gravity,
			...$parentUriIds,
		);
		while ($row = $this->database->fetch($unread)) {
			if (isset($stats[$row['parent-uri-id']])) {
				$stats[$row['parent-uri-id']]['unread'] = (int) $row['unread'];
			}
		}
		$this->database->close($unread);

		return $stats;
	}

	/**
	 * Fetches the latest post of each thread
	 *
	 * @param int   $uid     User id
	 * @param array $stats   Thread statistics from getThreadStats()
	 * @param int[] $gravity Gravities of the posts
	 * @param array $fields  Fields of the posts
	 *
	 * @return array Posts keyed by the uri-id of the thread
	 * @throws \Exception
	 */
	public function getLatestPosts(int $uid, array $stats, array $gravity, array $fields): array
	{
		if (empty($stats)) {
			return [];
		}

		$latest = [];

		$condition = DBA::mergeConditions(["(`uid` = 0 OR (`uid` = ? AND NOT `global`))", $uid], [
			'parent-uri-id' => array_keys($stats),
			'received'      => array_unique(array_column($stats, 'received')),
			'gravity'       => $gravity,
			'visible'       => true,
			'deleted'       => false,
		]);

		$posts = Post::selectForUser($uid, array_merge($fields, ['uri-id', 'parent-uri-id', 'received']), $condition);
		while ($post = Post::fetch($posts)) {
			// Different threads can share the same date, so we have to check the thread as well
			if (!isset($latest[$post['parent-uri-id']]) && ($post['received'] === $stats[$post['parent-uri-id']]['received'])) {
				$latest[$post['parent-uri-id']] = $post;
			}
		}
		$this->database->close($posts);

		if (in_array('unseen', $fields)) {
			$unseen = $this->getUnseen($uid, array_column($latest, 'uri-id'));
			foreach ($latest as $parentUriId => $post) {
				$latest[$parentUriId]['unseen'] = in_array($post['uri-id'], $unseen);
			}
		}

		return $latest;
	}

	/**
	 * Prepares a post from getLatestPosts() for the display
	 *
	 * @param array $post Post with guid, title, body, author and unseen fields
	 *
	 * @return array
	 */
	public function getPostSummary(array $post): array
	{
		return [
			'link'        => 'display/' . $post['guid'],
			'author'      => $post['author-name'],
			'author_link' => Contact::magicLink($post['author-link']),
			'thumb'       => Contact::getAvatarUrlForId($post['author-id'], Proxy::SIZE_MICRO, $post['author-updated']),
			'received'    => $this->l10n->relativeDateTime($post['received']),
			'excerpt'     => Plaintext::shorten($post['title'] ?: BBCode::toPlaintext($post['body'], false), 100),
			'unseen'      => $post['unseen'],
		];
	}

	/**
	 * Local groups are addressed by their nickname, remote ones by their address
	 *
	 * @param array $contact Contact with id, url and addr fields
	 *
	 * @return string
	 */
	public function getLink(array $contact): string
	{
		$id = (string) $contact['addr'] ?: (string) $contact['id'];
		if (Contact::isLocal($contact['url'])) {
			$id = strstr($id, '@', true) ?: $id;
		}

		return 'group/' . rawurlencode($id);
	}

	/**
	 * Fetches the posts that the user hasn't seen yet
	 *
	 * @param int   $uid    User id
	 * @param int[] $uriIds Uri-ids of the posts
	 *
	 * @return int[] Uri-ids of the unseen posts
	 * @throws \Exception
	 */
	public function getUnseen(int $uid, array $uriIds): array
	{
		if (empty($uriIds) || !$uid) {
			return [];
		}

		return array_column($this->database->selectToArray('post-user', ['uri-id'], ['uid' => $uid, 'uri-id' => $uriIds, 'unseen' => true]), 'uri-id');
	}

	/**
	 * Fetches the threads of a group, ordered by their latest comment
	 *
	 * @param int $uid   User id
	 * @param int $pcid  Public contact id of the group
	 * @param int $start Offset
	 * @param int $limit Number of threads
	 *
	 * @return int[] Uri-ids of the threads
	 * @throws \Exception
	 */
	public function getThreadIds(int $uid, int $pcid, int $start, int $limit): array
	{
		$threads = $this->database->p(
			"SELECT `post-thread-user`.`uri-id` " . self::THREADS . " AND `post-thread-user`.`owner-id` = ?
				ORDER BY `post-thread-user`.`commented` DESC LIMIT ?, ?",
			$uid,
			$pcid,
			$start,
			$limit,
		);

		return array_column($this->database->toArray($threads), 'uri-id');
	}

	/**
	 * Fetches the number of threads, the number of threads with unread posts and the latest activity per group in one go
	 *
	 * @param int   $uid   User id
	 * @param int[] $pcids Public contact ids of the groups
	 *
	 * @return array Statistics keyed by the public contact id
	 * @throws \Exception
	 */
	public function getGroupStats(int $uid, array $pcids): array
	{
		if (empty($pcids)) {
			return [];
		}

		$stats        = [];
		$placeholders = implode(', ', array_fill(0, count($pcids), '?'));

		$threads = $this->database->p(
			"SELECT `post-thread-user`.`owner-id`, COUNT(*) AS `threads`, MAX(`post-thread-user`.`commented`) AS `commented` " . self::THREADS . "
				AND `post-thread-user`.`owner-id` IN (" . $placeholders . ")
				GROUP BY `post-thread-user`.`owner-id`",
			$uid,
			...$pcids,
		);
		while ($row = $this->database->fetch($threads)) {
			$stats[$row['owner-id']] = [
				'threads'   => (int) $row['threads'],
				'unread'    => 0,
				'commented' => $row['commented'],
			];
		}
		$this->database->close($threads);

		$unread = $this->database->p(
			"SELECT `post-thread-user`.`owner-id`, COUNT(DISTINCT `post-user`.`parent-uri-id`) AS `unread` FROM `post-user`
				INNER JOIN `post-thread-user` ON `post-thread-user`.`uri-id` = `post-user`.`parent-uri-id` AND `post-thread-user`.`uid` = `post-user`.`uid`
				WHERE `post-user`.`uid` = ? AND `post-user`.`unseen` AND NOT `post-user`.`hidden` AND NOT `post-user`.`deleted`
				AND `post-user`.`gravity` IN (?, ?) AND `post-thread-user`.`owner-id` IN (" . $placeholders . ")
				GROUP BY `post-thread-user`.`owner-id`",
			$uid,
			Item::GRAVITY_PARENT,
			Item::GRAVITY_COMMENT,
			...$pcids,
		);
		while ($row = $this->database->fetch($unread)) {
			if (isset($stats[$row['owner-id']])) {
				$stats[$row['owner-id']]['unread'] = (int) $row['unread'];
			}
		}
		$this->database->close($unread);

		return $stats;
	}

	/**
	 * Fetches the most recently commented thread of each group
	 *
	 * @param int   $uid   User id
	 * @param array $stats Group statistics from getGroupStats()
	 *
	 * @return array Uri-id of the thread keyed by the public contact id
	 * @throws \Exception
	 */
	public function getLatestThreads(int $uid, array $stats): array
	{
		if (empty($stats)) {
			return [];
		}

		$latest    = [];
		$pcids     = array_keys($stats);
		$commented = array_values(array_unique(array_column($stats, 'commented')));

		$threads = $this->database->p(
			"SELECT `post-thread-user`.`owner-id`, `post-thread-user`.`uri-id`, `post-thread-user`.`commented` " . self::THREADS . "
				AND `post-thread-user`.`owner-id` IN (" . implode(', ', array_fill(0, count($pcids), '?')) . ")
				AND `post-thread-user`.`commented` IN (" . implode(', ', array_fill(0, count($commented), '?')) . ")",
			$uid,
			...$pcids,
			...$commented,
		);
		while ($thread = $this->database->fetch($threads)) {
			if (!isset($latest[$thread['owner-id']]) && ($thread['commented'] === $stats[$thread['owner-id']]['commented'])) {
				$latest[$thread['owner-id']] = $thread['uri-id'];
			}
		}
		$this->database->close($threads);

		return $latest;
	}

	/**
	 * Fetches address, name and description of the given servers
	 *
	 * @param array $gsids Server ids
	 *
	 * @return array Servers keyed by their id
	 * @throws \Exception
	 */
	public function getServers(array $gsids): array
	{
		$gsids = array_filter(array_unique($gsids));
		if (empty($gsids)) {
			return [];
		}

		return array_column($this->database->selectToArray('gserver', ['id', 'url', 'site_name', 'info', 'platform', 'last_poco_query'], ['id' => array_values($gsids)]), null, 'id');
	}

	/**
	 * Queues the discovery of the groups on a server, at most once a day
	 *
	 * @param array $server Server from getServers()
	 *
	 * @return void
	 */
	public function discoverServerGroups(array $server): void
	{
		if (!in_array($server['platform'], UpdateServerDirectory::GROUP_PLATFORMS) || ($server['last_poco_query'] > DateTimeFormat::utc('now - 1 day'))) {
			return;
		}

		GServer::update(['last_poco_query' => DateTimeFormat::utcNow()], ['id' => $server['id']]);
		Worker::add(Worker::PRIORITY_LOW, 'UpdateServerDirectory', ['id' => $server['id'], 'url' => $server['url'], 'platform' => $server['platform']]);
	}

	/**
	 * Prepares name and description of a server for the display
	 *
	 * @param array  $server Server from getServers()
	 * @param string $host   Host name of the server
	 *
	 * @return array
	 */
	public function getServerDescription(array $server, string $host): array
	{
		$info = trim(html_entity_decode(strip_tags($server['info'] ?? '')));

		return [
			'name' => $this->getSiteName($server['site_name'] ?? '', $info) ?: $host,
			'host' => $host,
			'info' => Plaintext::shorten($info, 200),
		];
	}

	/**
	 * Removes the server description from the site name, since some systems (like Lemmy) append it
	 *
	 * @param string $name Site name
	 * @param string $info Server description
	 *
	 * @return string Site name
	 */
	private function getSiteName(string $name, string $info): string
	{
		if ($info === '') {
			return $name;
		}

		$offset = 0;
		while (($pos = strpos($name, ' - ', $offset)) !== false) {
			similar_text(trim(substr($name, $pos + 3)), $info, $percent);
			if ($percent >= 80) {
				return trim(substr($name, 0, $pos));
			}
			$offset = $pos + 1;
		}

		return $name;
	}
}
