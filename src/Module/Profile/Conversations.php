<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Profile;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\App\Mode;
use Friendica\App\Page;
use Friendica\AppHelper;
use Friendica\Content\Conversation\ConversationRenderer;
use Friendica\Content\Conversation\StatusEditor;
use Friendica\Content\Nav;
use Friendica\Content\Pager;
use Friendica\Content\Widget;
use Friendica\Core\ACL;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Protocol;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\Post\Category;
use Friendica\Model\Profile as ProfileModel;
use Friendica\Model\User;
use Friendica\Model\Verb;
use Friendica\Module\BaseProfile;
use Friendica\Module\Response;
use Friendica\Module\Security\Login;
use Friendica\Network\HTTPException;
use Friendica\Protocol\Activity;
use Friendica\Security\Security;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Profiler;
use Friendica\Util\Strings;
use Psr\Log\LoggerInterface;

class Conversations extends BaseProfile
{
	public function __construct(private readonly Mode $mode, private readonly IManagePersonalConfigValues $pConfig, private readonly ConversationRenderer $conversationRenderer, private readonly StatusEditor $statusEditor, private readonly IHandleUserSessions $session, private readonly IManageConfigValues $config, private readonly DateTimeFormat $dateTimeFormat, private Page $page, private readonly AppHelper $appHelper, L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
	}

	protected function content(array $request = []): string
	{
		$profile = ProfileModel::load($this->appHelper, $this->parameters['nickname'] ?? '');
		if (empty($profile)) {
			throw new HTTPException\NotFoundException($this->t('User not found.'));
		}

		if ($this->config->get('system', 'block_public') && !$this->session->isAuthenticated()) {
			return Login::form();
		}

		if (!empty($profile['hidewall']) && !$this->session->isAuthenticated()) {
			$this->baseUrl->redirect('profile/' . $profile['nickname'] . '/restricted');
		}

		if (!$profile['net-publish']) {
			$this->page['htmlhead'] .= '<meta content="noindex, noarchive" name="robots" />' . "\n";
		}

		$this->page['htmlhead'] .= '<link rel="alternate" type="application/atom+xml" href="' . $this->baseUrl . '/feed/' . $this->parameters['nickname'] . '/" title="' . $this->t('%s\'s posts', Strings::escapeHtml($profile['name'])) . '"/>' . "\n";
		$this->page['htmlhead'] .= '<link rel="alternate" type="application/atom+xml" href="' . $this->baseUrl . '/feed/' . $this->parameters['nickname'] . '/comments" title="' . $this->t('%s\'s comments', Strings::escapeHtml($profile['name'])) . '"/>' . "\n";
		$this->page['htmlhead'] .= '<link rel="alternate" type="application/atom+xml" href="' . $this->baseUrl . '/feed/' . $this->parameters['nickname'] . '/activity" title="' . $this->t('%s\'s timeline', Strings::escapeHtml($profile['name'])) . '"/>' . "\n";

		$category = $datequery = $datequery2 = '';

		if ($this->args->getArgc() > 3) {
			for ($x = 3; $x < $this->args->getArgc(); $x++) {
				if ($this->dateTimeFormat->isYearMonthDay($this->args->get($x))) {
					if ($datequery) {
						$datequery2 = $this->args->get($x);
					} else {
						$datequery = $this->args->get($x);
					}
				} else {
					$category = $this->args->get($x);
				}
			}
		}

		if (empty($category)) {
			$category = $request['category'] ?? '';
		}

		$hashtags = $request['tag'] ?? '';

		$o = '';

		if ($profile['uid'] == $this->session->getLocalUserId()) {
			Nav::setSelected('home');
		}

		$remote_contact   = $this->session->getRemoteContactID($profile['uid']);
		$is_owner         = $this->session->getLocalUserId() == $profile['uid'];
		$last_updated_key = "profile:" . $profile['uid'] . ":" . $this->session->getLocalUserId() . ":" . $remote_contact;

		$o .= self::getTabsHTML('status', $is_owner, $profile['nickname'], $profile['hide-friends']);

		$o .= Widget::commonFriendsVisitor($profile['uid'], $profile['nickname']);

		$commpage    = in_array($profile['page-flags'], [User::PAGE_FLAGS_COMMUNITY, User::PAGE_FLAGS_COMM_MAN]);
		$commvisitor = $commpage && $remote_contact;

		$this->page['aside'] .= Widget::postedByYear($this->baseUrl . '/profile/' . $profile['nickname'] . '/conversations', $profile['profile_uid'] ?? 0, true);
		$this->page['aside'] .= Widget::categories($profile['uid'], $this->baseUrl . '/profile/' . $profile['nickname'] . '/conversations', $category);
		$this->page['aside'] .= Widget::tagCloud($profile['uid']);

		if (Security::canWriteToUserWall($profile['uid'])) {
			$x = [
				'is_owner'         => $is_owner,
				'allow_location'   => ($is_owner || $commvisitor) && $profile['allow_location'],
				'default_location' => $is_owner ? $profile['default-location'] : '',
				'nickname'         => $profile['nickname'],
				'acl'              => $is_owner ? ACL::getFullSelectorHTML($this->page, $this->session->getLocalUserId(), true) : '',
				'visitor'          => $is_owner || $commvisitor ? 'block' : 'none',
				'profile_uid'      => $profile['uid'],
			];

			$o .= $this->statusEditor->renderEditor($x);
		}

		// Get permissions SQL - if $remote_contact is true, our remote user has been pre-verified and we already have fetched their circles
		$condition = Item::getPermissionsConditionArrayByUserId($profile['uid']);

		$last_updated_array = $this->session->get('last_updated', []);

		if (!empty($category)) {
			$condition = DBA::mergeConditions($condition, ["`uri-id` IN (SELECT `uri-id` FROM `category-view` WHERE `name` = ? AND `type` = ? AND `uid` = ?)",
				$category, Category::CATEGORY, $profile['uid']]);
		}

		if (!empty($hashtags)) {
			$condition = DBA::mergeConditions($condition, ["`uri-id` IN (SELECT `uri-id` FROM `tag-search-view` WHERE `name` = ? AND `uid` = ?)",
				$hashtags, $profile['uid']]);
		}

		if (!empty($datequery)) {
			$condition = DBA::mergeConditions($condition, ["`received` <= ?", DateTimeFormat::convert($datequery, 'UTC', $this->appHelper->getTimeZone())]);
		}

		if (!empty($datequery2)) {
			$condition = DBA::mergeConditions($condition, ["`received` >= ?", DateTimeFormat::convert($datequery2, 'UTC', $this->appHelper->getTimeZone())]);
		}

		// Does the profile page belong to a group?
		// If not then we can improve the performance with an additional condition
		if ($profile['account-type'] != User::ACCOUNT_TYPE_COMMUNITY) {
			$condition = DBA::mergeConditions($condition, ['contact-id' => $profile['id']]);
		}

		if ($this->mode->isMobile()) {
			$itemspage_network = $this->pConfig->get(
				$this->session->getLocalUserId(),
				'system',
				'itemspage_mobile_network',
				$this->config->get('system', 'itemspage_network_mobile'),
			);
		} else {
			$itemspage_network = $this->pConfig->get(
				$this->session->getLocalUserId(),
				'system',
				'itemspage_network',
				$this->config->get('system', 'itemspage_network'),
			);
		}

		$condition = DBA::mergeConditions($condition, ["((`gravity` = ? AND `wall`) OR
			(`gravity` = ? AND `vid` = ? AND `origin`
			AND EXISTS(SELECT `uri-id` FROM `post` WHERE `uri-id` = `post-origin-view`.`thr-parent-id` AND `gravity` = ? AND `network` IN (?, ?))))",
			Item::GRAVITY_PARENT, Item::GRAVITY_ACTIVITY, Verb::getID(Activity::ANNOUNCE), Item::GRAVITY_PARENT, Protocol::ACTIVITYPUB, Protocol::DFRN]);

		$condition = DBA::mergeConditions($condition, ['uid' => $profile['uid'], 'network' => Protocol::FEDERATED,
			'visible'                                           => true, 'deleted' => false]);

		$pager  = new Pager($this->l10n, $this->args->getQueryString(), $itemspage_network);
		$params = ['limit' => [$pager->getStart(), $pager->getItemsPerPage()], 'order' => ['received' => true]];

		$items_stmt = Post::selectOrigin(['uri-id', 'thr-parent-id', 'gravity', 'author-id', 'received'], $condition, $params);

		// Set a time stamp for this page. We will make use of it when we
		// search for new items (update routine)
		$last_updated_array[$last_updated_key] = time();
		$this->session->set('last_updated', $last_updated_array);

		if ($is_owner && ProfileModel::shouldDisplayEventList($this->session->getLocalUserId(), $this->mode)) {
			$o .= ProfileModel::getBirthdays($this->session->getLocalUserId());
			$o .= ProfileModel::getEventsReminderHTML($this->session->getLocalUserId(), $this->session->getPublicContactId());
		}

		if ($is_owner) {
			$unseen = Post::exists(['wall' => true, 'unseen' => true, 'uid' => $this->session->getLocalUserId()]);
			if ($unseen) {
				Item::update(['unseen' => false], ['wall' => true, 'unseen' => true, 'uid' => $this->session->getLocalUserId()]);
			}
		}

		$items = Post::toArray($items_stmt);

		if ($pager->getStart() == 0 && !empty($profile['uid'])) {
			$pcid   = Contact::getPublicIdByUserId($profile['uid']);
			$pinned = Post\Collection::selectToArrayForContact($pcid, Post\Collection::FEATURED);
			$items  = array_merge($items, $pinned);
		}

		$o .= $this->conversationRenderer->renderThreaded($items, ConversationRenderer::MODE_PROFILE, false, ConversationRenderer::ORDER_PINNED_RECEIVED, $this->session->getLocalUserId(), $request);

		$o .= $pager->renderMinimal(count($items));

		return $o;
	}
}
