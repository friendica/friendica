<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Update;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\BaseModule;
use Friendica\Content\Conversation\ConversationRenderer;
use Friendica\Core\L10n;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\Profile as ProfileModel;
use Friendica\Model\User;
use Friendica\Module\Response;
use Friendica\Network\HTTPException\ForbiddenException;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Profiler;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

class Profile extends BaseModule
{
	public function __construct(
		private readonly ConversationRenderer $conversationRenderer,
		L10n $l10n,
		BaseURL $baseUrl,
		Arguments $args,
		LoggerInterface $logger,
		Profiler $profiler,
		Response $response,
		EventDispatcherInterface $eventDispatcher,
		array $server,
		array $parameters = [],
	) {
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters, $eventDispatcher);
	}

	protected function rawContent(array $request = [])
	{
		$appHelper = DI::appHelper();

		// Ensure we've got a profile owner if updating.
		$appHelper->setProfileOwner((int) ($request['p'] ?? 0));

		if (DI::config()->get('system', 'block_public') && !DI::userSession()->getLocalUserId() && !DI::userSession()->getRemoteContactID($appHelper->getProfileOwner())) {
			throw new ForbiddenException();
		}

		$remote_contact   = DI::userSession()->getRemoteContactID($appHelper->getProfileOwner());
		$is_owner         = DI::userSession()->getLocalUserId() == $appHelper->getProfileOwner();
		$last_updated_key = "profile:" . $appHelper->getProfileOwner() . ":" . DI::userSession()->getLocalUserId() . ":" . $remote_contact;

		if (!DI::userSession()->isAuthenticated()) {
			$user = User::getById($appHelper->getProfileOwner(), ['hidewall']);
			if ($user['hidewall']) {
				throw new ForbiddenException(DI::l10n()->t('Access to this profile has been restricted.'));
			}
		}

		$o = '';

		if (empty($request['force'])) {
			System::htmlUpdateExit($o);
		}

		// Get permissions SQL - if $remote_contact is true, our remote user has been pre-verified and we already have fetched their circles
		$sql_extra = Item::getPermissionsSQLByUserId($appHelper->getProfileOwner());

		$last_updated_array = DI::session()->get('last_updated', []);

		$last_updated = $last_updated_array[$last_updated_key] ?? 0;

		$condition = ["`uid` = ? AND NOT `contact-blocked` AND NOT `contact-pending`
				AND `visible` AND (NOT `deleted` OR `gravity` = ?)
				AND `wall` " . $sql_extra, $appHelper->getProfileOwner(), Item::GRAVITY_ACTIVITY];

		if (!empty($request['item'])) {
			// When the parent is provided, we only fetch this
			$condition = DBA::mergeConditions($condition, ['parent' => $request['item']]);
		} elseif ($is_owner || !$last_updated) {
			// If the page user is the owner of the page we should query for unseen
			// items. Otherwise use a timestamp of the last succesful update request.
			$condition = DBA::mergeConditions($condition, ['unseen' => true]);
		} else {
			$gmupdate  = gmdate(DateTimeFormat::MYSQL, $last_updated);
			$condition = DBA::mergeConditions($condition, ["`received` > ?", $gmupdate]);
		}

		$items = Post::selectToArray(['parent-uri-id', 'created', 'received'], $condition, ['group_by' => ['parent-uri-id'], 'order' => ['received' => true]]);
		if (!DBA::isResult($items)) {
			return;
		}

		// @todo the DBA functions should handle "SELECT field AS alias" in the future,
		// so that this workaround here could be removed.
		$items = array_map(function ($item) {
			$item['uri-id'] = $item['parent-uri-id'];
			unset($item['parent-uri-id']);
			return $item;
		}, $items);

		// Set a time stamp for this page. We will make use of it when we
		// search for new items (update routine)
		$last_updated_array[$last_updated_key] = time();
		DI::session()->set('last_updated', $last_updated_array);

		if ($is_owner && !$appHelper->getProfileOwner() && ProfileModel::shouldDisplayEventList(DI::userSession()->getLocalUserId(), DI::mode())) {
			$o .= ProfileModel::getBirthdays(DI::userSession()->getLocalUserId());
			$o .= ProfileModel::getEventsReminderHTML(DI::userSession()->getLocalUserId(), DI::userSession()->getPublicContactId());
		}

		if ($is_owner) {
			$unseen = Post::exists(['wall' => true, 'unseen' => true, 'uid' => DI::userSession()->getLocalUserId()]);
			if ($unseen) {
				Item::update(['unseen' => false], ['wall' => true, 'unseen' => true, 'uid' => DI::userSession()->getLocalUserId()]);
			}
		}

		$o .= $this->conversationRenderer->renderThreaded($items, ConversationRenderer::MODE_PROFILE, true, ConversationRenderer::ORDER_RECEIVED, $appHelper->getProfileOwner(), $request);

		System::htmlUpdateExit($o);
	}
}
