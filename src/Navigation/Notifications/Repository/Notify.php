<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Navigation\Notifications\Repository;

use Friendica\App\BaseURL;
use Friendica\BaseRepository;
use Friendica\Content\Text\BBCode;
use Friendica\Content\Text\Plaintext;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Factory\Api\Mastodon\Notification as NotificationFactory;
use Friendica\Model;
use Friendica\Navigation\Notifications\Collection;
use Friendica\Navigation\Notifications\Entity;
use Friendica\Navigation\Notifications\Exception;
use Friendica\Navigation\Notifications\Factory;
use Friendica\Network\HTTPException;
use Friendica\Object\Api\Mastodon\Notification;
use Friendica\Protocol\Activity;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Emailer;
use Psr\Log\LoggerInterface;

/**
 * @deprecated since 2022.05 Use \Friendica\Navigation\Notifications\Repository\Notification instead
 */
class Notify extends BaseRepository
{
	/** @var Factory\Notify  */
	protected $factory;

	/** @var L10n  */
	protected $l10n;

	/** @var BaseURL  */
	protected $baseUrl;

	/** @var IManageConfigValues */
	protected $config;

	/** @var IManagePersonalConfigValues */
	private $pConfig;

	/** @var Emailer */
	protected $emailer;

	/** @var Factory\Notification */
	protected $notification;

	protected static $table_name = 'notify';

	public function __construct(Database $database, LoggerInterface $logger, L10n $l10n, BaseURL $baseUrl, IManageConfigValues $config, IManagePersonalConfigValues $pConfig, Emailer $emailer, Factory\Notification $notification, Factory\Notify $factory = null)
	{
		$this->l10n         = $l10n;
		$this->baseUrl      = $baseUrl;
		$this->config       = $config;
		$this->pConfig      = $pConfig;
		$this->emailer      = $emailer;
		$this->notification = $notification;

		parent::__construct($database, $logger, $factory ?? new Factory\Notify($logger));
	}

	/**
	 * @param array $condition
	 * @param array $params
	 *
	 * @return Entity\Notify
	 * @throws HTTPException\NotFoundException
	 */
	private function selectOne(array $condition, array $params = []): Entity\Notify
	{
		return parent::_selectOne($condition, $params);
	}

	private function select(array $condition, array $params = []): Collection\Notifies
	{
		return new Collection\Notifies(parent::_select($condition, $params)->getArrayCopy());
	}

	public function countForUser($uid, array $condition, array $params = []): int
	{
		$condition = DBA::mergeConditions($condition, ['uid' => $uid]);

		return $this->count($condition, $params);
	}

	public function existsForUser($uid, array $condition): bool
	{
		$condition = DBA::mergeConditions($condition, ['uid' => $uid]);

		return $this->exists($condition);
	}

	/**
	 * @param int $id
	 *
	 * @return Entity\Notify
	 * @throws HTTPException\NotFoundException
	 */
	public function selectOneById(int $id): Entity\Notify
	{
		return $this->selectOne(['id' => $id]);
	}

	public function selectForUser(int $uid, array $condition, array $params): Collection\Notifies
	{
		$condition = DBA::mergeConditions($condition, ['uid' => $uid]);

		return $this->select($condition, $params);
	}

	/**
	 * Returns notifications for the user, unread first, ordered in descending chronological order.
	 *
	 * @param int $uid
	 * @param int $limit
	 * @return Collection\Notifies
	 */
	public function selectAllForUser(int $uid, int $limit): Collection\Notifies
	{
		return $this->selectForUser($uid, [], ['order' => ['seen' => 'ASC', 'date' => 'DESC'], 'limit' => $limit]);
	}

	public function setAllSeenForUser(int $uid, array $condition = []): bool
	{
		$condition = DBA::mergeConditions($condition, ['uid' => $uid]);

		return $this->db->update(self::$table_name, ['seen' => true], $condition);
	}

	/**
	 * @param Entity\Notify $Notify
	 *
	 * @return Entity\Notify
	 * @throws HTTPException\NotFoundException
	 * @throws HTTPException\InternalServerErrorException
	 * @throws Exception\NotificationCreationInterceptedException
	 */
	public function save(Entity\Notify $Notify): Entity\Notify
	{
		$fields = [
			'type'          => $Notify->type,
			'name'          => $Notify->name,
			'url'           => $Notify->url,
			'photo'         => $Notify->photo,
			'msg'           => $Notify->msg,
			'uid'           => $Notify->uid,
			'link'          => $Notify->link,
			'iid'           => $Notify->itemId,
			'parent'        => $Notify->parent,
			'seen'          => $Notify->seen,
			'verb'          => $Notify->verb,
			'otype'         => $Notify->otype,
			'name_cache'    => $Notify->name_cache,
			'msg_cache'     => $Notify->msg_cache,
			'uri-id'        => $Notify->uriId,
			'parent-uri-id' => $Notify->parentUriId,
		];

		if ($Notify->id) {
			$this->db->update(self::$table_name, $fields, ['id' => $Notify->id]);
		} else {
			$fields['date'] = DateTimeFormat::utcNow();
			Hook::callAll('enotify_store', $fields);

			$this->db->insert(self::$table_name, $fields);

			$Notify = $this->selectOneById($this->db->lastInsertId());
		}

		return $Notify;
	}

	public function setAllSeenForRelatedNotify(Entity\Notify $Notify): bool
	{
		$condition = [
			'(`link` = ? OR (`parent` != 0 AND `parent` = ? AND `otype` = ?)) AND `uid` = ?',
			$Notify->link,
			$Notify->parent,
			$Notify->otype,
			$Notify->uid
		];
		return $this->db->update(self::$table_name, ['seen' => true], $condition);
	}

	/**
	 * Creates a notification entry and possibly sends a mail
	 *
	 * @param array $params Array with the elements:
	 *                      type, event, otype, activity, verb, uid, cid, item, link,
	 *                      source_name, source_mail, source_nick, source_link, source_photo,
	 *                      show_in_notification_page
	 *
	 * @return bool
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function createFromArray($params)
	{
		/** @var string the common prefix of a notification subject */
		$subjectPrefix = $this->l10n->t('[Friendica:Notify]');

		// Temporary logging for finding the origin
		if (!isset($params['uid'])) {
			$this->logger->notice('Missing parameters "uid".', ['params' => $params]);
		}

		// Ensure that the important fields are set at any time
		$fields = ['nickname', 'account-type', 'notify-flags', 'language', 'username', 'email', 'account_removed', 'account_expired'];
		$user   = DBA::selectFirst('user', $fields, ['uid' => $params['uid']]);

		if (!DBA::isResult($user)) {
			$this->logger->error('Unknown user', ['uid' => $params['uid']]);
			return false;
		}

		if ($user['account_removed'] || $user['account_expired']) {
			return false;
		}

		// There is no need to create notifications for group accounts
		if ($user['account-type'] == Model\User::ACCOUNT_TYPE_COMMUNITY) {
			return false;
		}

		$params['notify_flags'] = $user['notify-flags'];
		$params['language']     = $user['language'];
		$params['to_name']      = $user['username'];
		$params['to_email']     = $user['email'];

		// from here on everything is in the recipients language
		$l10n = $this->l10n->withLang($params['language']);

		if (!empty($params['cid'])) {
			$contact = Model\Contact::getById($params['cid'], ['url', 'name', 'photo']);
			if (DBA::isResult($contact)) {
				$params['source_link']  = $contact['url'];
				$params['source_name']  = $contact['name'];
				$params['source_photo'] = $contact['photo'];
			}
		}

		$siteurl  = (string)$this->baseUrl;
		$sitename = $this->config->get('config', 'sitename');

		// with $params['show_in_notification_page'] == false, the notification isn't inserted into
		// the database, and an email is sent if applicable.
		// default, if not specified: true
		$show_in_notification_page = isset($params['show_in_notification_page']) ? $params['show_in_notification_page'] : true;

		$title = $params['item']['title'] ?? '';
		$body  = $params['item']['body']  ?? '';

		$parent_id     = $params['item']['parent']        ?? 0;
		$parent_uri_id = $params['item']['parent-uri-id'] ?? 0;

		$epreamble = '';
		$preamble  = '';
		$subject   = '';
		$sitelink  = '';
		$tsitelink = '';
		$hsitelink = '';
		$itemlink  = '';

		switch ($params['type']) {
			case Model\Notification\Type::MAIL:
				$itemlink = $params['link'];

				$subject = $l10n->t('%s New mail received at %s', $subjectPrefix, $sitename);

				$preamble  = $l10n->t('%1$s sent you a new private message at %2$s.', $params['source_name'], $sitename);
				$epreamble = $l10n->t('%1$s sent you %2$s.', '[url='.$params['source_link'].']'.$params['source_name'].'[/url]', '[url=' . $itemlink . ']' . $l10n->t('a private message').'[/url]');

				$sitelink  = $l10n->t('Please visit %s to view and/or reply to your private messages.');
				$tsitelink = sprintf($sitelink, $itemlink);
				$hsitelink = sprintf($sitelink, '<a href="' . $itemlink . '">' . $sitename . '</a>');

				// Mail notifications aren't using the "notify" table entry
				$show_in_notification_page = false;
				break;

			case Model\Notification\Type::COMMENT:
				if (Model\Post\ThreadUser::getIgnored($parent_uri_id, $params['uid'])) {
					$this->logger->info('Thread is ignored', ['parent' => $parent_id, 'parent-uri-id' => $parent_uri_id]);
					return false;
				}

				$item = Model\Post::selectFirstForUser($params['uid'], Model\Item::ITEM_FIELDLIST, ['id' => $parent_id, 'deleted' => false]);
				if (empty($item)) {
					return false;
				}

				$item_post_type = Model\Item::postType($item, $l10n);

				$body  = BBCode::toPlaintext($item['body'], false);
				$title = Plaintext::shorten($body, 70);
				if (!empty($title)) {
					$title = '"' . trim(str_replace("\n", " ", $title)) . '"';
				}

				// First go for the general message

				// "George Bull's post"
				$message  = $l10n->t('%1$s commented on %2$s\'s %3$s %4$s');
				$dest_str = sprintf($message, $params['source_name'], $item['author-name'], $item_post_type, $title);

				// "your post"
				if ($item['wall']) {
					$message  = $l10n->t('%1$s commented on your %2$s %3$s');
					$dest_str = sprintf($message, $params['source_name'], $item_post_type, $title);
					// "their post"
				} elseif ($item['author-link'] == $params['source_link']) {
					$message  = $l10n->t('%1$s commented on their %2$s %3$s');
					$dest_str = sprintf($message, $params['source_name'], $item_post_type, $title);
				}

				$subject = $l10n->t('%1$s Comment to conversation #%2$d by %3$s', $subjectPrefix, $parent_id, $params['source_name']);

				$preamble = $l10n->t('%s commented on an item/conversation you have been following.', $params['source_name']);

				$epreamble = $dest_str;

				$sitelink  = $l10n->t('Please visit %s to view and/or reply to the conversation.');
				$tsitelink = sprintf($sitelink, $siteurl);
				$hsitelink = sprintf($sitelink, '<a href="' . $siteurl . '">' . $sitename . '</a>');
				$itemlink  = $params['link'];
				break;

			case Model\Notification\Type::WALL:
				$subject = $l10n->t('%s %s posted to your profile wall', $subjectPrefix, $params['source_name']);

				$preamble  = $l10n->t('%1$s posted to your profile wall at %2$s', $params['source_name'], $sitename);
				$epreamble = $l10n->t(
					'%1$s posted to [url=%2$s]your wall[/url]',
					'[url='.$params['source_link'].']'.$params['source_name'].'[/url]',
					$params['link']
				);

				$sitelink  = $l10n->t('Please visit %s to view and/or reply to the conversation.');
				$tsitelink = sprintf($sitelink, $siteurl);
				$hsitelink = sprintf($sitelink, '<a href="'.$siteurl.'">'.$sitename.'</a>');
				$itemlink  = $params['link'];
				break;

			case Model\Notification\Type::INTRO:
				$itemlink = $params['link'];
				$subject  = $l10n->t('%s Introduction received', $subjectPrefix);

				$preamble  = $l10n->t('You\'ve received an introduction from \'%1$s\' at %2$s', $params['source_name'], $sitename);
				$epreamble = $l10n->t(
					'You\'ve received [url=%1$s]an introduction[/url] from %2$s.',
					$itemlink,
					'[url='.$params['source_link'].']'.$params['source_name'].'[/url]'
				);

				$body = $l10n->t('You may visit their profile at %s', $params['source_link']);

				$sitelink  = $l10n->t('Please visit %s to approve or reject the introduction.');
				$tsitelink = sprintf($sitelink, $siteurl);
				$hsitelink = sprintf($sitelink, '<a href="'.$siteurl.'">'.$sitename.'</a>');

				switch ($params['verb']) {
					case Activity::FRIEND:
						// someone started to share with user (mostly OStatus)
						$subject = $l10n->t('%s A new person is sharing with you', $subjectPrefix);

						$preamble  = $l10n->t('%1$s is sharing with you at %2$s', $params['source_name'], $sitename);
						$epreamble = $l10n->t(
							'%1$s is sharing with you at %2$s',
							'[url='.$params['source_link'].']'.$params['source_name'].'[/url]',
							$sitename
						);
						break;
					case Activity::FOLLOW:
						// someone started to follow the user (mostly OStatus)
						$subject = $l10n->t('%s You have a new follower', $subjectPrefix);

						$preamble  = $l10n->t('You have a new follower at %2$s : %1$s', $params['source_name'], $sitename);
						$epreamble = $l10n->t(
							'You have a new follower at %2$s : %1$s',
							'[url='.$params['source_link'].']'.$params['source_name'].'[/url]',
							$sitename
						);
						break;
					default:
						// ACTIVITY_REQ_FRIEND is default activity for notifications
						break;
				}
				break;

			case Model\Notification\Type::SUGGEST:
				$itemlink = $params['link'];
				$subject  = $l10n->t('%s Friend suggestion received', $subjectPrefix);

				$preamble  = $l10n->t('You\'ve received a friend suggestion from \'%1$s\' at %2$s', $params['source_name'], $sitename);
				$epreamble = $l10n->t(
					'You\'ve received [url=%1$s]a friend suggestion[/url] for %2$s from %3$s.',
					$itemlink,
					'[url='.$params['item']['url'].']'.$params['item']['name'].'[/url]',
					'[url='.$params['source_link'].']'.$params['source_name'].'[/url]'
				);

				$body = $l10n->t('Name:').' '.$params['item']['name']."\n";
				$body .= $l10n->t('Photo:').' '.$params['item']['photo']."\n";
				$body .= $l10n->t('You may visit their profile at %s', $params['item']['url']);

				$sitelink  = $l10n->t('Please visit %s to approve or reject the suggestion.');
				$tsitelink = sprintf($sitelink, $siteurl);
				$hsitelink = sprintf($sitelink, '<a href="'.$siteurl.'">'.$sitename.'</a>');
				break;

			case Model\Notification\Type::CONFIRM:
				if ($params['verb'] == Activity::FRIEND) { // mutual connection
					$itemlink = $params['link'];
					$subject  = $l10n->t('%s Connection accepted', $subjectPrefix);

					$preamble  = $l10n->t('\'%1$s\' has accepted your connection request at %2$s', $params['source_name'], $sitename);
					$epreamble = $l10n->t(
						'%2$s has accepted your [url=%1$s]connection request[/url].',
						$itemlink,
						'[url='.$params['source_link'].']'.$params['source_name'].'[/url]'
					);

					$body = $l10n->t('You are now mutual friends and may exchange status updates, photos, and email without restriction.');

					$sitelink  = $l10n->t('Please visit %s if you wish to make any changes to this relationship.');
					$tsitelink = sprintf($sitelink, $siteurl);
					$hsitelink = sprintf($sitelink, '<a href="'.$siteurl.'">'.$sitename.'</a>');
				} else { // ACTIVITY_FOLLOW
					$itemlink = $params['link'];
					$subject  = $l10n->t('%s Connection accepted', $subjectPrefix);

					$preamble  = $l10n->t('\'%1$s\' has accepted your connection request at %2$s', $params['source_name'], $sitename);
					$epreamble = $l10n->t(
						'%2$s has accepted your [url=%1$s]connection request[/url].',
						$itemlink,
						'[url='.$params['source_link'].']'.$params['source_name'].'[/url]'
					);

					$body = $l10n->t('\'%1$s\' has chosen to accept you a fan, which restricts some forms of communication - such as private messaging and some profile interactions. If this is a celebrity or community page, these settings were applied automatically.', $params['source_name']);
					$body .= "\n\n";
					$body .= $l10n->t('\'%1$s\' may choose to extend this into a two-way or more permissive relationship in the future.', $params['source_name']);

					$sitelink  = $l10n->t('Please visit %s  if you wish to make any changes to this relationship.');
					$tsitelink = sprintf($sitelink, $siteurl);
					$hsitelink = sprintf($sitelink, '<a href="'.$siteurl.'">'.$sitename.'</a>');
				}
				break;

			case Model\Notification\Type::SYSTEM:
				switch($params['event']) {
					case 'SYSTEM_REGISTER_REQUEST':
						$itemlink = $params['link'];
						$subject  = $l10n->t('[Friendica System Notify]') . ' ' . $l10n->t('registration request');

						$preamble  = $l10n->t('You\'ve received a registration request from \'%1$s\' at %2$s', $params['source_name'], $sitename);
						$epreamble = $l10n->t(
							'You\'ve received a [url=%1$s]registration request[/url] from %2$s.',
							$itemlink,
							'[url='.$params['source_link'].']'.$params['source_name'].'[/url]'
						);

						$body = $l10n->t(
							"Display Name:	%s\nSite Location:	%s\nLogin Name:	%s (%s)",
							$params['source_name'],
							$siteurl,
							$params['source_mail'],
							$params['source_nick']
						);

						$sitelink  = $l10n->t('Please visit %s to approve or reject the request.');
						$tsitelink = sprintf($sitelink, $params['link']);
						$hsitelink = sprintf($sitelink, '<a href="' . $params['link'] . '">' . $sitename . '</a><br><br>');
						break;

					case 'SYSTEM_REGISTER_NEW':
						$itemlink = $params['link'];
						$subject  = $l10n->t('[Friendica System Notify]') . ' ' . $l10n->t('new registration');

						$preamble  = $l10n->t('You\'ve received a new registration from \'%1$s\' at %2$s', $params['source_name'], $sitename);
						$epreamble = $l10n->t(
							'You\'ve received a [url=%1$s]new registration[/url] from %2$s.',
							$itemlink,
							'[url='.$params['source_link'].']'.$params['source_name'].'[/url]'
						);

						$body = $l10n->t(
							"Display Name:	%s\nSite Location:	%s\nLogin Name:	%s (%s)",
							$params['source_name'],
							$siteurl,
							$params['source_mail'],
							$params['source_nick']
						);

						$sitelink  = $l10n->t('Please visit %s to have a look at the new registration.');
						$tsitelink = sprintf($sitelink, $params['link']);
						$hsitelink = sprintf($sitelink, '<a href="' . $params['link'] . '">' . $sitename . '</a><br><br>');
						break;

					case 'SYSTEM_DB_UPDATE_FAIL': // @TODO Unused (only here)
						break;
				}
				break;

			default:
				$this->logger->notice('Unhandled type', ['type' => $params['type']]);
				return false;
		}

		return $this->storeAndSend($params, $sitelink, $tsitelink, $hsitelink, $title, $subject, $preamble, $epreamble, $body, $itemlink, $show_in_notification_page);
	}

	private function storeAndSend(array $params, string $sitelink, string $tsitelink, string $hsitelink, string $title, string $subject, string $preamble, string $epreamble, string $body, string $itemlink, bool $show_in_notification_page): bool
	{
		$item_id       = $params['item']['id']            ?? 0;
		$uri_id        = $params['item']['uri-id']        ?? null;
		$parent_id     = $params['item']['parent']        ?? 0;
		$parent_uri_id = $params['item']['parent-uri-id'] ?? null;

		// Ensure that the important fields are set at any time
		$fields = ['nickname', 'account_removed', 'account_expired'];
		$user   = Model\User::getById($params['uid'], $fields);
		if ($user['account_removed'] || $user['account_expired']) {
			return false;
		}

		$sitename = $this->config->get('config', 'sitename');

		$nickname = $user['nickname'];

		$hostname = $this->baseUrl->getHost();
		if (strpos($hostname, ':')) {
			$hostname = substr($hostname, 0, strpos($hostname, ':'));
		}

		// Creates a new email builder for the notification email
		$emailBuilder = $this->emailer->newNotifyMail();

		$emailBuilder->setHeader('X-Friendica-Account', '<' . $nickname . '@' . $hostname . '>');

		$subject .= " (".$nickname."@".$hostname.")";

		$h = [
			'params'    => $params,
			'subject'   => $subject,
			'preamble'  => $preamble,
			'epreamble' => $epreamble,
			'body'      => $body,
			'sitelink'  => $sitelink,
			'tsitelink' => $tsitelink,
			'hsitelink' => $hsitelink,
			'itemlink'  => $itemlink
		];

		Hook::callAll('enotify', $h);

		$subject = $h['subject'];

		$preamble  = $h['preamble'];
		$epreamble = $h['epreamble'];

		$body = $h['body'];

		$tsitelink = $h['tsitelink'];
		$hsitelink = $h['hsitelink'];
		$itemlink  = $h['itemlink'];

		$notify_id = 0;

		if ($show_in_notification_page) {
			$Notify = $this->factory->createFromParams($params, $itemlink, $item_id, $uri_id, $parent_id, $parent_uri_id);
			try {
				$Notify = $this->save($Notify);
			} catch (Exception\NotificationCreationInterceptedException $e) {
				// Notification insertion can be intercepted by an addon registering the 'enotify_store' hook
				return false;
			}

			$Notify->updateMsgFromPreamble($epreamble);
			$Notify = $this->save($Notify);

			$itemlink  = $this->baseUrl . '/notify/' . $Notify->id;
			$notify_id = $Notify->id;
		}

		// send email notification if notification preferences permit
		if ((intval($params['notify_flags']) & intval($params['type']))
			|| $params['type'] == Model\Notification\Type::SYSTEM) {

			$this->logger->notice('sending notification email');

			if (isset($params['parent']) && (intval($params['parent']) != 0)) {
				$parent     = Model\Post::selectFirst(['guid'], ['id' => $params['parent']]);
				$message_id = "<" . $parent['guid'] . "@" . gethostname() . ">";

				// Is this the first email notification for this parent item and user?
				if (!DBA::exists('notify-threads', ['master-parent-uri-id' => $parent_uri_id, 'receiver-uid' => $params['uid']])) {
					$this->logger->info("notify_id:" . intval($notify_id) . ", parent: " . intval($params['parent']) . "uid: " . intval($params['uid']));

					$fields = ['notify-id' => $notify_id, 'master-parent-uri-id' => $parent_uri_id,
						'receiver-uid'        => $params['uid'], 'parent-item' => 0];
					DBA::insert('notify-threads', $fields);

					$emailBuilder->setHeader('Message-ID', $message_id);
					$this->logger->info('No previous notification found for this parent', ['parent' => $params['parent'], 'uid' => $params['uid']]);
				} else {
					// If not, just "follow" the thread.
					$emailBuilder->setHeader('References', $message_id);
					$emailBuilder->setHeader('In-Reply-To', $message_id);
					$this->logger->info("There's already a notification for this parent.");
				}
			}

			$datarray = [
				'preamble'     => $preamble,
				'type'         => $params['type'],
				'parent'       => $parent_id,
				'source_name'  => $params['source_name']  ?? null,
				'source_link'  => $params['source_link']  ?? null,
				'source_photo' => $params['source_photo'] ?? null,
				'uid'          => $params['uid'],
				'hsitelink'    => $hsitelink,
				'tsitelink'    => $tsitelink,
				'itemlink'     => $itemlink,
				'title'        => $title,
				'body'         => $body,
				'subject'      => $subject,
				'headers'      => $emailBuilder->getHeaders(),
			];

			Hook::callAll('enotify_mail', $datarray);

			$emailBuilder
				->withHeaders($datarray['headers'])
				->withRecipient($params['to_email'])
				->forUser([
					'uid'      => $datarray['uid'],
					'language' => $params['language'],
				])
				->withNotification($datarray['subject'], $datarray['preamble'], $datarray['title'], $datarray['body'])
				->withSiteLink($datarray['tsitelink'], $datarray['hsitelink'])
				->withItemLink($datarray['itemlink']);

			// If a photo is present, add it to the email
			if (!empty($datarray['source_photo'])) {
				$emailBuilder->withPhoto(
					$datarray['source_photo'],
					$datarray['source_link'] ?? $sitelink,
					$datarray['source_name'] ?? $sitename
				);
			}

			$email = $emailBuilder->build();

			$this->logger->debug('Send mail', $datarray);

			// use the Emailer class to send the message
			return $this->emailer->send($email);
		}

		return false;
	}

	public function shouldShowOnDesktop(Entity\Notification $Notification, string $type = null): bool
	{
		if (is_null($type)) {
			$type = NotificationFactory::getType($Notification);
		}

		if (in_array($Notification->type, [Model\Post\UserNotification::TYPE_FOLLOW, Model\Post\UserNotification::TYPE_SHARED])) {
			return true;
		}

		if ($this->pConfig->get($Notification->uid, 'system', 'notify_like') && ($type == Notification::TYPE_LIKE)) {
			return true;
		}

		if ($this->pConfig->get($Notification->uid, 'system', 'notify_announce') && ($type == Notification::TYPE_RESHARE)) {
			return true;
		}

		$notify_type = $this->pConfig->get($Notification->uid, 'system', 'notify_type');

		// Fallback for the case when the notify type isn't set at all
		if (is_null($notify_type) && !in_array($type, [Notification::TYPE_RESHARE, Notification::TYPE_LIKE])) {
			return true;
		}

		if (!is_null($notify_type) && ($notify_type & $Notification->type)) {
			return true;
		}

		return false;
	}

	public function createFromNotification(Entity\Notification $Notification): bool
	{
		$this->logger->info('Start', ['uid' => $Notification->uid, 'id' => $Notification->id, 'type' => $Notification->type]);

		if ($Notification->type === Model\Post\UserNotification::TYPE_NONE) {
			$this->logger->info('Not an item based notification, quitting', ['uid' => $Notification->uid, 'id' => $Notification->id, 'type' => $Notification->type]);
			return false;
		}

		$params          = [];
		$params['verb']  = $Notification->verb;
		$params['uid']   = $Notification->uid;
		$params['otype'] = Model\Notification\ObjectType::ITEM;

		$user = Model\User::getById($Notification->uid);
		if ($user['account_removed'] || $user['account_expired']) {
			return false;
		}

		$params['notify_flags'] = $user['notify-flags'];
		$params['language']     = $user['language'];
		$params['to_name']      = $user['username'];
		$params['to_email']     = $user['email'];

		// from here on everything is in the recipients language
		$l10n = $this->l10n->withLang($user['language']);

		$contact = Model\Contact::getById($Notification->actorId, ['url', 'name', 'photo']);
		if (DBA::isResult($contact)) {
			$params['source_link']  = $contact['url'];
			$params['source_name']  = $contact['name'];
			$params['source_photo'] = $contact['photo'];
		}

		$item = Model\Post::selectFirstForUser(
			$Notification->uid,
			Model\Item::ITEM_FIELDLIST,
			['uid'   => [0, $Notification->uid], 'uri-id' => $Notification->targetUriId, 'deleted' => false],
			['order' => ['uid' => true]]
		);
		if (empty($item)) {
			$this->logger->info('Item not found', ['uri-id' => $Notification->targetUriId, 'type' => $Notification->type]);
			return false;
		}

		$params['item']   = $item;
		$params['parent'] = $item['parent'];
		$params['link']   = $this->baseUrl . '/display/' . urlencode($item['guid']);

		$subjectPrefix = $l10n->t('[Friendica:Notify]');

		if (Model\Post\ThreadUser::getIgnored($Notification->parentUriId, $Notification->uid)) {
			$this->logger->info('Thread is ignored', ['parent-uri-id' => $Notification->parentUriId, 'type' => $Notification->type]);
			return false;
		}

		// Check to see if there was already a tag notify or comment notify for this post.
		// If so don't create a second notification
		$condition = ['type' => [Model\Notification\Type::TAG_SELF, Model\Notification\Type::COMMENT, Model\Notification\Type::SHARE],
			'link'              => $params['link'], 'verb' => Activity::POST];
		if ($this->existsForUser($Notification->uid, $condition)) {
			$this->logger->info('Duplicate found, quitting', $condition + ['uid' => $Notification->uid]);
			return false;
		}

		$body  = BBCode::toPlaintext($item['body'], false);
		$title = Plaintext::shorten($body, 70);
		if (!empty($title)) {
			$title = '"' . trim(str_replace("\n", " ", $title)) . '"';
		}

		// Some mail software relies on the subject field for threading.
		// So, we cannot have different subjects for notifications of the same thread.
		// Before this we have the name of the replier on the subject rendering
		// different subjects for messages on the same thread.
		if ($Notification->type === Model\Post\UserNotification::TYPE_EXPLICIT_TAGGED) {
			$params['type'] = Model\Notification\Type::TAG_SELF;
			$subject        = $l10n->t('%s %s tagged you', $subjectPrefix, $contact['name']);
		} elseif ($Notification->type === Model\Post\UserNotification::TYPE_SHARED) {
			$params['type'] = Model\Notification\Type::SHARE;
			$subject        = $l10n->t('%s %s shared a new post', $subjectPrefix, $contact['name']);
		} else {
			$params['type'] = Model\Notification\Type::COMMENT;
			$subject        = $l10n->t('%1$s Comment to conversation #%2$d by %3$s', $subjectPrefix, $item['parent'], $contact['name']);

			if ($params['verb'] == Activity::LIKE) {
				switch ($Notification->type) {
					case Model\Post\UserNotification::TYPE_DIRECT_COMMENT:
						$subject = $l10n->t('%1$s %2$s liked your post #%3$d', $subjectPrefix, $contact['name'], $item['parent']);
						break;
					case Model\Post\UserNotification::TYPE_DIRECT_THREAD_COMMENT:
						$subject = $l10n->t('%1$s %2$s liked your comment on #%3$d', $subjectPrefix, $contact['name'], $item['parent']);
						break;
				}
			}
		}

		$msg = $this->notification->getMessageFromNotification($Notification);
		if (empty($msg)) {
			$this->logger->info('No notification message, quitting', ['uid' => $Notification->uid, 'id' => $Notification->id, 'type' => $Notification->type]);
			return false;
		}

		$preamble  = $msg['plain'];
		$epreamble = $msg['rich'];

		$sitename = $this->config->get('config', 'sitename');
		$siteurl  = (string)$this->baseUrl;

		$sitelink  = $l10n->t('Please visit %s to view and/or reply to the conversation.');
		$tsitelink = sprintf($sitelink, $siteurl);
		$hsitelink = sprintf($sitelink, '<a href="' . $siteurl . '">' . $sitename . '</a>');
		$itemlink  = $params['link'];

		$this->logger->info('Perform notification', ['uid' => $Notification->uid, 'id' => $Notification->id, 'type' => $Notification->type]);

		return $this->storeAndSend($params, $sitelink, $tsitelink, $hsitelink, $title, $subject, $preamble, $epreamble, $item['body'], $itemlink, true);
	}

	public function deleteForItem(int $itemUriId): void
	{
		$this->db->delete('notify', ['otype' => 'item', 'uri-id' => $itemUriId]);
		$this->db->delete('notify', ['otype' => 'item', 'parent-uri-id' => $itemUriId]);
	}
}
