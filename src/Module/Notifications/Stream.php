<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Notifications;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\BaseModule;
use Friendica\Content\BoundariesPager;
use Friendica\Content\Conversation\ConversationRenderer;
use Friendica\Content\Nav;
use Friendica\Content\Text\HTML;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\System;
use Friendica\Factory\Api\Mastodon\Notification as MastodonNotificationFactory;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Module\BaseNotifications;
use Friendica\Module\Response;
use Friendica\Module\Security\Login;
use Friendica\Navigation\Notifications\Entity\Notification as NotificationEntity;
use Friendica\Navigation\Notifications\Repository\Notification as NotificationRepository;
use Friendica\Object\Api\Mastodon\Notification as MastodonNotification;
use Friendica\Protocol\Activity;
use Friendica\Util\Profiler;
use Friendica\Util\Proxy;
use Psr\Log\LoggerInterface;

/**
 * All kinds of notifications as one list, similar to the Mastodon notifications.
 *
 * Mentions and replies are shown as the post itself, all other notifications get a line that
 * tells what happened, followed by the post it is about.
 */
class Stream extends BaseModule
{
	private const ITEMS_PER_PAGE = 20;

	public function __construct(protected readonly NotificationRepository $notification, private readonly ConversationRenderer $conversationRenderer, protected readonly IHandleUserSessions $session, private readonly IManagePersonalConfigValues $pConfig, L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
	}

	/**
	 * The notifications for the infinite scroll, without anything around them.
	 */
	protected function rawContent(array $request = [])
	{
		if (($request['mode'] ?? '') !== 'raw') {
			return;
		}

		$uid = $this->session->getLocalUserId();
		if (!$uid) {
			System::exit();
		}

		$this->earlyHttpExit(trim($this->renderList($uid, null, (int) ($request['max_id'] ?? 0) ?: null)['html']));
	}

	protected function content(array $request = []): string
	{
		$uid = $this->session->getLocalUserId();
		if (!$uid) {
			return Login::form('notifications/stream');
		}

		Nav::setSelected('notifications');

		$request = $this->checkDefaults([
			'min_id' => null,
			'max_id' => null,
			'mode'   => '',
		], $request);

		$list = $this->renderList(
			$uid,
			isset($request['min_id']) ? (int) $request['min_id'] : null,
			isset($request['max_id']) ? (int) $request['max_id'] : null,
		);

		$header = $request['mode'] === 'column' ? '' : $this->getHeaderHtml();

		if (!$list['count']) {
			return $header . '<p id="notification-stream-empty" class="notification-stream-empty">' . $this->t('No notifications.') . '</p>' . $this->getLiveUpdateHtml($uid);
		}

		$output = $header . $this->getLiveUpdateHtml($uid) . $list['html'];

		if ($this->pConfig->get($uid, 'system', 'infinite_scroll', true)) {
			return $output . HTML::scrollLoader($request) . "<div id=\"conversation-end\"></div>\n";
		}

		$pager = new BoundariesPager($this->l10n, $this->args->getQueryString(), (string) $list['first'], (string) $list['last'], self::ITEMS_PER_PAGE);

		return $output . $pager->renderMinimal($list['count']);
	}

	/**
	 * The headline and the tab bar that is shared with the other notification pages.
	 */
	private function getHeaderHtml(): string
	{
		return '<h1>' . $this->t('Notifications') . '</h1>' . Renderer::replaceMacros(Renderer::getMarkupTemplate('common_tabs.tpl'), [
			'$tabs' => BaseNotifications::getTabBar($this->l10n, 'stream'),
		]);
	}

	/**
	 * The marker for the automatic update of main.js, see liveUpdate() and columnUpdate() there.
	 */
	private function getLiveUpdateHtml(int $uid): string
	{
		return Renderer::replaceMacros(Renderer::getMarkupTemplate('live_update.tpl'), [
			'$mode'        => 'notifications',
			'$profile_uid' => $uid,
			'$netargs'     => '?f=',
		]);
	}

	/**
	 * Condition for the notifications that are shown in the stream.
	 *
	 * @param int|null $afterId Only notifications that are newer than this id
	 */
	protected function getCondition(int $uid, ?int $afterId = null): array
	{
		$condition = ["`uid` = ? AND NOT `dismissed` AND (NOT `type` IN (?, ?)) AND NOT EXISTS(SELECT `cid` FROM `user-contact` WHERE `user-contact`.`cid` = `notification`.`actor-id` AND `user-contact`.`uid` = `notification`.`uid` AND (`is-blocked` OR `blocked`))",
			$uid, Post\UserNotification::TYPE_ACTIVITY_PARTICIPATION, Post\UserNotification::TYPE_COMMENT_PARTICIPATION];

		if ($afterId) {
			$condition[0] .= ' AND `id` > ?';
			$condition[] = $afterId;
		}

		return $condition;
	}

	/**
	 * @return array{html: string, first: ?int, last: ?int, count: int}
	 */
	protected function renderList(int $uid, ?int $minId, ?int $maxId): array
	{
		$notifications = $this->notification->selectByBoundaries(
			$this->getCondition($uid),
			['order' => ['id' => true]],
			$minId,
			$maxId,
			self::ITEMS_PER_PAGE,
		);

		$list = ['html' => '', 'first' => null, 'last' => null, 'count' => 0];

		foreach ($notifications as $notification) {
			$list['first'] ??= $notification->id;
			$list['last'] = $notification->id;
			$list['count']++;

			try {
				$list['html'] .= $this->renderNotification($notification, $uid);
			} catch (\Exception $e) {
				$this->logger->notice('Notification could not be rendered', ['id' => $notification->id, 'code' => $e->getCode(), 'message' => $e->getMessage()]);
			}
		}

		return $list;
	}

	/**
	 * The target post alone, without loading and rendering the thread around it.
	 */
	private function renderPost(int $uriId, int $uid): string
	{
		$fields = array_merge(Item::DISPLAY_FIELDLIST, ['featured']);
		$item   = Post::selectFirstForUser($uid, $fields, ['uri-id' => $uriId]);
		if (!$item) {
			return '';
		}

		return $this->conversationRenderer->renderFlat([$item], ConversationRenderer::MODE_CONTACT_POSTS, false, $uid);
	}

	private function getReactionEmoji(string $verb, string $body): string
	{
		return match ($verb) {
			Activity::DISLIKE    => '👎',
			Activity::EMOJIREACT => $body ?: '👍',
			default              => '👍',
		};
	}

	private function renderNotification(NotificationEntity $notification, int $uid): string
	{
		$type = MastodonNotificationFactory::getType($notification);
		if ($type === '') {
			return '';
		}

		$postUriId = $notification->targetUriId;
		$emoji     = '';
		if ($type === MastodonNotification::TYPE_LIKE) {
			// The target is the post that was reacted to, the reaction itself has to be looked up for the emoji
			$activity = Post::selectFirst(['body'], ['thr-parent-id' => $notification->targetUriId, 'author-id' => $notification->actorId, 'gravity' => Item::GRAVITY_ACTIVITY, 'verb' => $notification->verb, 'uid' => [0, $uid]]);
			$emoji    = $this->getReactionEmoji($notification->verb, $activity['body'] ?? '');
		}

		$post = $postUriId ? $this->renderPost($postUriId, $uid) : '';

		$actor = Contact::getById($notification->actorId, ['name', 'nick']);
		$name  = $actor['name'] ?: ($actor['nick'] ?? '');
		$link  = '<a href="contact/' . $notification->actorId . '">' . htmlspecialchars($name) . '</a>';

		switch ($type) {
			case MastodonNotification::TYPE_FOLLOW:
				$icon = 'ri-user-add-line';
				$text = $this->t('%s started following you', $link);
				break;
			case MastodonNotification::TYPE_INTRODUCTION:
				$icon = 'ri-user-add-line';
				$text = $this->t('%s requested to follow you', '<a href="notifications/intros">' . htmlspecialchars($name) . '</a>');
				break;
			case MastodonNotification::TYPE_RESHARE:
				$icon = 'ri-repeat-line';
				$text = $this->t('%s shared your post', $link);
				break;
			case MastodonNotification::TYPE_LIKE:
				$icon = '';
				$text = $this->t('%s reacted to your post', $link);
				break;
			default:
				// Mentions and replies are shown as the post alone
				if ($post === '') {
					return '';
				}
				return Renderer::replaceMacros(Renderer::getMarkupTemplate('notifications/stream_item.tpl'), [
					'$id'   => $notification->id,
					'$type' => $type,
					'$post' => $post,
				]);
		}

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('notifications/stream_item.tpl'), [
			'$id'     => $notification->id,
			'$type'   => $type,
			'$icon'   => $icon,
			'$emoji'  => $emoji,
			'$avatar' => Contact::getAvatarUrlForId($notification->actorId, Proxy::SIZE_MICRO),
			'$text'   => $text,
			'$post'   => $post,
		]);
	}
}
