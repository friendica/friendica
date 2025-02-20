<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Content;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\App\Mode;
use Friendica\App\Page;
use Friendica\AppHelper;
use Friendica\BaseModule;
use Friendica\Core\ACL;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\Theme;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Model\Item as ItemModel;
use Friendica\Model\Post;
use Friendica\Model\Post\Category;
use Friendica\Model\Tag;
use Friendica\Model\User;
use Friendica\Model\Verb;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Object\Post as PostObject;
use Friendica\Object\Thread;
use Friendica\Protocol\Activity;
use Friendica\User\Settings\Entity\UserGServer as UserGServerEntity;
use Friendica\User\Settings\Repository\UserGServer as UserGServerRepository;
use Friendica\Util\Crypto;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Profiler;
use Friendica\Util\Strings;
use Friendica\Util\Temporal;
use ImagickException;
use Psr\Log\LoggerInterface;

class Conversation
{
	const MODE_CHANNEL       = 'channel';
	const MODE_COMMUNITY     = 'community';
	const MODE_CONTACTS      = 'contacts';
	const MODE_CONTACT_POSTS = 'contact-posts';
	const MODE_DISPLAY       = 'display';
	const MODE_FILED         = 'filed';
	const MODE_NETWORK       = 'network';
	const MODE_NOTES         = 'notes';
	const MODE_SEARCH        = 'search';
	const MODE_PROFILE       = 'profile';

	/** @var Activity */
	private $activity;
	/** @var L10n */
	private $l10n;
	/** @var Profiler */
	private $profiler;
	/** @var LoggerInterface */
	private $logger;
	/** @var Item */
	private $item;
	/** @var Arguments */
	private $args;
	/** @var IManagePersonalConfigValues */
	private $pConfig;
	/** @var BaseURL */
	private $baseURL;
	/** @var IManageConfigValues */
	private $config;
	/** @var AppHelper */
	private $appHelper;
	/** @var Page */
	private $page;
	/** @var Mode */
	private $mode;
	/** @var IHandleUserSessions */
	private $session;
	/** @var UserGServerRepository */
	private $userGServer;

	public function __construct(UserGServerRepository $userGServer, LoggerInterface $logger, Profiler $profiler, Activity $activity, L10n $l10n, Item $item, Arguments $args, BaseURL $baseURL, IManageConfigValues $config, IManagePersonalConfigValues $pConfig, Page $page, Mode $mode, AppHelper $appHelper, IHandleUserSessions $session)
	{
		$this->activity    = $activity;
		$this->item        = $item;
		$this->config      = $config;
		$this->mode        = $mode;
		$this->baseURL     = $baseURL;
		$this->profiler    = $profiler;
		$this->logger      = $logger;
		$this->l10n        = $l10n;
		$this->args        = $args;
		$this->pConfig     = $pConfig;
		$this->page        = $page;
		$this->appHelper   = $appHelper;
		$this->session     = $session;
		$this->userGServer = $userGServer;
	}

	/**
	 * Checks item to see if it is one of the builtin activities (like/dislike, event attendance, consensus items, etc.)
	 *
	 * Increments the count of each matching activity and adds a link to the author as needed.
	 *
	 * @param array  $activity
	 * @param array &$conv_responses (already created with builtin activity structure)
	 * @return void
	 * @throws ImagickException
	 * @throws InternalServerErrorException
	 */
	public function builtinActivityPuller(array $activity, array &$conv_responses)
	{
		$thread_parent = $activity['thr-parent-row'] ?? [];

		foreach ($conv_responses as $mode => $v) {
			$sparkle = '';

			switch ($mode) {
				case 'like':
					$verb = Activity::LIKE;
					break;
				case 'dislike':
					$verb = Activity::DISLIKE;
					break;
				case 'attendyes':
					$verb = Activity::ATTEND;
					break;
				case 'attendno':
					$verb = Activity::ATTENDNO;
					break;
				case 'attendmaybe':
					$verb = Activity::ATTENDMAYBE;
					break;
				case 'announce':
					$verb = Activity::ANNOUNCE;
					break;
				default:
					return;
			}

			if (!empty($activity['verb']) && $this->activity->match($activity['verb'], $verb) && ($activity['gravity'] != ItemModel::GRAVITY_PARENT)) {
				$author = [
					'uid'     => 0,
					'id'      => $activity['author-id'],
					'network' => $activity['author-network'],
					'url'     => $activity['author-link'],
					'alias'   => $activity['author-alias'],
				];
				$url = Contact::magicLinkByContact($author);
				if (strpos($url, 'contact/redir/') === 0) {
					$sparkle = ' class="sparkle" ';
				}

				$link = '<a href="' . $url . '"' . $sparkle . '>' . htmlentities($activity['author-name']) . '</a>';

				if (empty($activity['thr-parent-id'])) {
					$activity['thr-parent-id'] = $activity['parent-uri-id'];
				}

				// Skip when the causer of the parent is the same as the author of the announce
				if (($verb == Activity::ANNOUNCE) && !empty($thread_parent['causer-id']) && ($thread_parent['causer-id'] == $activity['author-id'])) {
					continue;
				}

				if (!isset($conv_responses[$mode][$activity['thr-parent-id']])) {
					$conv_responses[$mode][$activity['thr-parent-id']] = [
						'links' => [],
						'self'  => 0,
					];
				} elseif (in_array($link, $conv_responses[$mode][$activity['thr-parent-id']]['links'])) {
					// only list each unique author once
					continue;
				}

				if ($this->session->getPublicContactId() == $activity['author-id']) {
					$conv_responses[$mode][$activity['thr-parent-id']]['self'] = 1;
				}

				$conv_responses[$mode][$activity['thr-parent-id']]['links'][] = $link;

				// there can only be one activity verb per item so if we found anything, we can stop looking
				return;
			}
		}
	}

	/**
	 * Returns the liker phrase based on a list of likers
	 *
	 * @param string $verb   the activity verb
	 * @param array  $likers a list of likers
	 *
	 * @return string the liker phrase
	 *
	 * @throws InternalServerErrorException in case either the verb is invalid or the list of likers is empty
	 */
	private function getLikerPhrase(string $verb, array $likers): string
	{
		$total = count($likers);

		if ($total === 0) {
			throw new InternalServerErrorException(sprintf('There has to be at least one Liker for verb "%s"', $verb));
		} elseif ($total === 1) {
			$likerString = $likers[0];
		} else {
			if ($total < $this->config->get('system', 'max_likers')) {
				$likerString = implode(', ', array_slice($likers, 0, -1));
				$likerString .= ' ' . $this->l10n->t('and') . ' ' . $likers[count($likers) - 1];
			} else {
				$likerString = implode(', ', array_slice($likers, 0, $this->config->get('system', 'max_likers') - 1));
				$likerString .= ' ' . $this->l10n->t('and %d other people', $total - $this->config->get('system', 'max_likers'));
			}
		}

		switch ($verb) {
			case 'like':
				return $this->l10n->tt('%2$s likes this.', '%2$s like this.', $total, $likerString);
			case 'dislike':
				return $this->l10n->tt('%2$s doesn\'t like this.', '%2$s don\'t like this.', $total, $likerString);
			case 'attendyes':
				return $this->l10n->tt('%2$s attends.', '%2$s attend.', $total, $likerString);
			case 'attendno':
				return $this->l10n->tt('%2$s doesn\'t attend.', '%2$s don\'t attend.', $total, $likerString);
			case 'attendmaybe':
				return $this->l10n->tt('%2$s attends maybe.', '%2$s attend maybe.', $total, $likerString);
			case 'announce':
				return $this->l10n->tt('%2$s reshared this.', '%2$s reshared this.', $total, $likerString);
			default:
				throw new InternalServerErrorException(sprintf('Unknown verb "%s"', $verb));
		}
	}

	/**
	 * Format the activity text for an item/photo/video
	 *
	 * @param array  $links    array of pre-linked names of actors
	 * @param string $verb     one of 'like, 'dislike', 'attendyes', 'attendno', 'attendmaybe'
	 * @param int    $id       item id
	 * @param string $activity Activity URI
	 * @param array  $emojis   Array with emoji reactions
	 * @return string formatted text
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function formatActivity(array $links, string $verb, int $id, string $activity, array $emojis): string
	{
		$this->profiler->startRecording('rendering');
		$expanded = '';

		$phrase = $this->getLikerPhrase($verb, $links);
		$total  = max(count($links), $emojis[$activity]['total'] ?? 0);

		if ($total > 1) {
			$spanatts  = "class=\"btn btn-link fakelink\" onclick=\"openClose('{$verb}list-$id');\"";
			$explikers = $phrase;

			switch ($verb) {
				case 'like':
					$phrase = $this->l10n->tt('<button type="button" %2$s>%1$d person</button> likes this', '<button type="button" %2$s>%1$d people</button> like this', $total, $spanatts);
					break;
				case 'dislike':
					$phrase = $this->l10n->tt('<button type="button" %2$s>%1$d person</button> doesn\'t like this', '<button type="button" %2$s>%1$d peiple</button> don\'t like this', $total, $spanatts);
					break;
				case 'attendyes':
					$phrase = $this->l10n->tt('<button type="button" %2$s>%1$d person</button> attends', '<button type="button" %2$s>%1$d people</button> attend', $total, $spanatts);
					break;
				case 'attendno':
					$phrase = $this->l10n->tt('<button type="button" %2$s>%1$d person</button> doesn\'t attend', '<button type="button" %2$s>%1$d people</button> don\'t attend', $total, $spanatts);
					break;
				case 'attendmaybe':
					$phrase = $this->l10n->tt('<button type="button" %2$s>%1$d person</button> attends maybe', '<button type="button" %2$s>%1$d people</button> attend maybe', $total, $spanatts);
					break;
				case 'announce':
					$phrase = $this->l10n->tt('<button type="button" %2$s>%1$d person</button> reshared this', '<button type="button" %2$s>%1$d people</button> reshared this', $total, $spanatts);
					break;
			}

			$expanded .= "\t" . '<p class="wall-item-' . $verb . '-expanded" id="' . $verb . 'list-' . $id . '" style="display: none;" >' . $explikers . '</p>';
		}

		$output = Renderer::replaceMacros(Renderer::getMarkupTemplate('voting_fakelink.tpl'), [
			'$phrase' => $phrase,
			'$type'   => $verb,
			'$id'     => $id
		]);
		$output .= $expanded;

		$this->profiler->stopRecording();
		return $output;
	}

	public function statusEditor(array $x = [], int $notes_cid = 0, bool $popup = false): string
	{
		$user = User::getById($this->session->getLocalUserId(), ['uid', 'nickname', 'allow_location', 'default-location']);
		if (empty($user['uid'])) {
			return '';
		}

		$this->profiler->startRecording('rendering');
		$o = '';

		$x['allow_location']   = $x['allow_location']   ?? $user['allow_location'];
		$x['default_location'] = $x['default_location'] ?? $user['default-location'];
		$x['nickname']         = $x['nickname']         ?? $user['nickname'];
		$x['lockstate']        = $x['lockstate']        ?? ACL::getLockstateForUserId($user['uid']) ? 'lock' : 'unlock';
		$x['acl']              = $x['acl']              ?? ACL::getFullSelectorHTML($this->page, $user['uid'], true);
		$x['bang']             = $x['bang']             ?? '';
		$x['visitor']          = $x['visitor']          ?? 'block';
		$x['is_owner']         = $x['is_owner']         ?? true;
		$x['profile_uid']      = $x['profile_uid']      ?? $this->session->getLocalUserId();


		$geotag = !empty($x['allow_location']) ? Renderer::replaceMacros(Renderer::getMarkupTemplate('jot_geotag.tpl'), []) : '';

		$tpl = Renderer::getMarkupTemplate('jot-header.tpl');
		$this->page['htmlhead'] .= Renderer::replaceMacros($tpl, [
			'$newpost'   => 'true',
			'$geotag'    => $geotag,
			'$nickname'  => $x['nickname'],
			'$ispublic'  => $this->l10n->t('Visible to <strong>everybody</strong>'),
			'$linkurl'   => $this->l10n->t('Please enter a image/video/audio/webpage URL:'),
			'$term'      => $this->l10n->t('Tag term:'),
			'$fileas'    => $this->l10n->t('Save to Folder'),
			'$whereareu' => $this->l10n->t('Where are you right now?'),
			'$delitems'  => $this->l10n->t("Delete item\x28s\x29?"),
			'$is_mobile' => $this->mode->isMobile(),
		]);

		$jotplugins = '';
		Hook::callAll('jot_tool', $jotplugins);

		if ($this->config->get('system', 'set_creation_date')) {
			$created_at = Temporal::getDateTimeField(
				new \DateTime(DBA::NULL_DATETIME),
				new \DateTime('now'),
				null,
				$this->l10n->t('Created at'),
				'created_at'
			);
		} else {
			$created_at = '';
		}

		$tpl = Renderer::getMarkupTemplate('jot.tpl');

		$o .= Renderer::replaceMacros($tpl, [
			'$new_post'            => $this->l10n->t('New Post'),
			'$return_path'         => $this->args->getQueryString(),
			'$action'              => 'item',
			'$share'               => ($x['button'] ?? '') ?: $this->l10n->t('Share'),
			'$loading'             => $this->l10n->t('Loading...'),
			'$upload'              => $this->l10n->t('Upload photo'),
			'$shortupload'         => $this->l10n->t('upload photo'),
			'$attach'              => $this->l10n->t('Attach file'),
			'$shortattach'         => $this->l10n->t('attach file'),
			'$edbold'              => $this->l10n->t('Bold'),
			'$editalic'            => $this->l10n->t('Italic'),
			'$eduline'             => $this->l10n->t('Underline'),
			'$edquote'             => $this->l10n->t('Quote'),
			'$edemojis'            => $this->l10n->t('Add emojis'),
			'$contentwarn'         => $this->l10n->t('Content Warning'),
			'$edcode'              => $this->l10n->t('Code'),
			'$edimg'               => $this->l10n->t('Image'),
			'$edurl'               => $this->l10n->t('Link'),
			'$edattach'            => $this->l10n->t('Link or Media'),
			'$edvideo'             => $this->l10n->t('Video'),
			'$setloc'              => $this->l10n->t('Set your location'),
			'$shortsetloc'         => $this->l10n->t('set location'),
			'$noloc'               => $this->l10n->t('Clear browser location'),
			'$shortnoloc'          => $this->l10n->t('clear location'),
			'$title'               => $x['title'] ?? '',
			'$placeholdertitle'    => $this->l10n->t('Set title'),
			'$category'            => $x['category'] ?? '',
			'$placeholdercategory' => Feature::isEnabled($this->session->getLocalUserId(), Feature::CATEGORIES) ? $this->l10n->t("Categories \x28comma-separated list\x29") : '',
			'$scheduled_at'        => Temporal::getDateTimeField(
				new \DateTime(),
				new \DateTime('now + 6 months'),
				null,
				$this->l10n->t('Scheduled at'),
				'scheduled_at'
			),
			'$created_at'   => $created_at,
			'$wait'         => $this->l10n->t('Please wait'),
			'$permset'      => $this->l10n->t('Permission settings'),
			'$shortpermset' => $this->l10n->t('Permissions'),
			'$wall'         => $notes_cid ? 0 : 1,
			'$posttype'     => $notes_cid ? ItemModel::PT_PERSONAL_NOTE : ItemModel::PT_ARTICLE,
			'$content'      => $x['content'] ?? '',
			'$post_id'      => $x['post_id'] ?? '',
			'$defloc'       => $x['default_location'],
			'$visitor'      => $x['visitor'],
			'$pvisit'       => $notes_cid ? 'none' : $x['visitor'],
			'$public'       => $this->l10n->t('Public post'),
			'$lockstate'    => $x['lockstate'],
			'$bang'         => $x['bang'],
			'$profile_uid'  => $x['profile_uid'],
			'$preview'      => $this->l10n->t('Preview'),
			'$jotplugins'   => $jotplugins,
			'$notes_cid'    => $notes_cid,
			'$cancel'       => $this->l10n->t('Cancel'),
			'$rand_num'     => Crypto::randomDigits(12),

			// ACL permissions box
			'$acl' => $x['acl'],

			//jot nav tab (used in some themes)
			'$message' => $this->l10n->t('Message'),
			'$browser' => $this->l10n->t('Browser'),

			'$compose_link_title'  => $this->l10n->t('Open Compose page'),
			'$always_open_compose' => $this->pConfig->get($this->session->getLocalUserId(), 'frio', 'always_open_compose', false),
		]);


		if ($popup == true) {
			$o = '<div id="jot-popup" style="display: none;">' . $o . '</div>';
		}

		$this->profiler->stopRecording();
		return $o;
	}

	/**
	 * "Render" a conversation or list of items for HTML display.
	 * There are two major forms of display:
	 *      - Sequential or unthreaded ("New Item View" or search results)
	 *      - conversation view
	 * The $mode parameter decides between the various renderings and also
	 * figures out how to determine page owner and other contextual items
	 * that are based on unique features of the calling module.
	 * @param array  $items   An array of Posts
	 * @param string $mode    One of self::MODE_*
	 * @param bool   $update  Asynchronous update rendering
	 * @param bool   $preview Post preview (no actual database record)
	 * @param string $order   Either "received" or "commented"
	 * @param int    $uid
	 * @return string
	 * @throws ImagickException
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function render(array $items, string $mode, bool $update = false, bool $preview = false, string $order = 'commented', int $uid = 0): string
	{
		$this->profiler->startRecording('rendering');

		$this->page->registerFooterScript(Theme::getPathForFile('asset/typeahead.js/dist/typeahead.bundle.js'));
		$this->page->registerFooterScript(Theme::getPathForFile('js/friendica-tagsinput/friendica-tagsinput.js'));
		$this->page->registerStylesheet(Theme::getPathForFile('js/friendica-tagsinput/friendica-tagsinput.css'));
		$this->page->registerStylesheet(Theme::getPathForFile('js/friendica-tagsinput/friendica-tagsinput-typeahead.css'));

		$live_update_div = '';

		$userGservers = $this->userGServer->listIgnoredByUser($this->session->getLocalUserId());

		$ignoredGsids = array_map(function (UserGServerEntity $userGServer) {
			return $userGServer->gsid;
		}, $userGservers->getArrayCopy());

		if ($mode === self::MODE_NETWORK) {
			$items = $this->addChildren($items, false, $order, $uid, $mode, $ignoredGsids);
			if (!$update) {
				/*
				* The special div is needed for liveUpdate to kick in for this page.
				* We only launch liveUpdate if you aren't filtering in some incompatible
				* way and also you aren't writing a comment (discovered in javascript).
				*/
				$live_update_div = '<div id="live-network"></div>' . "\r\n"
					. "<script> var profile_uid = " . $_SESSION['uid']
					. "; var netargs = '" . substr($this->args->getCommand(), 8)
					. '?f='
					. (!empty($_GET['contactid']) ? '&contactid=' . rawurlencode($_GET['contactid']) : '')
					. (!empty($_GET['search'])    ? '&search='    . rawurlencode($_GET['search'])    : '')
					. (!empty($_GET['star'])      ? '&star='      . rawurlencode($_GET['star'])      : '')
					. (!empty($_GET['order'])     ? '&order='     . rawurlencode($_GET['order'])     : '')
					. (!empty($_GET['bmark'])     ? '&bmark='     . rawurlencode($_GET['bmark'])     : '')
					. (!empty($_GET['liked'])     ? '&liked='     . rawurlencode($_GET['liked'])     : '')
					. (!empty($_GET['conv'])      ? '&conv='      . rawurlencode($_GET['conv'])      : '')
					. (!empty($_GET['nets'])      ? '&nets='      . rawurlencode($_GET['nets'])      : '')
					. (!empty($_GET['cmin'])      ? '&cmin='      . rawurlencode($_GET['cmin'])      : '')
					. (!empty($_GET['cmax'])      ? '&cmax='      . rawurlencode($_GET['cmax'])      : '')
					. (!empty($_GET['file'])      ? '&file='      . rawurlencode($_GET['file'])      : '')
					. (!empty($_GET['channel'])   ? '&channel='   . rawurlencode($_GET['channel'])   : '')
					. (!empty($_GET['no_sharer']) ? '&no_sharer=' . rawurlencode($_GET['no_sharer']) : '')
					. (!empty($_GET['accounttype']) ? '&accounttype=' . rawurlencode($_GET['accounttype']) : '')
					. "'; </script>\r\n";
			}
		} elseif ($mode === self::MODE_PROFILE) {
			$items = $this->addChildren($items, false, $order, $uid, $mode, $ignoredGsids);

			if (!$update) {
				$tab = !empty($_GET['tab']) ? trim($_GET['tab']) : 'posts';

				if ($tab === 'posts') {
					/*
					* This is ugly, but we can't pass the profile_uid through the session to the ajax updater,
					* because browser prefetching might change it on us. We have to deliver it with the page.
					*/

					$live_update_div = '<div id="live-profile"></div>' . "\r\n"
						. "<script> var profile_uid = " . $uid
						. "; var netargs = '?f='; </script>\r\n";
				}
			}
		} elseif ($mode === self::MODE_NOTES) {
			$items = $this->addChildren($items, false, $order, $this->session->getLocalUserId(), $mode);

			if (!$update) {
				$live_update_div = '<div id="live-notes"></div>' . "\r\n"
					. "<script> var profile_uid = " . $this->session->getLocalUserId()
					. "; var netargs = '?f='; </script>\r\n";
			}
		} elseif ($mode === self::MODE_DISPLAY) {
			$items = $this->addChildren($items, false, $order, $uid, $mode, $ignoredGsids);

			if (!$update) {
				$live_update_div = '<div id="live-display"></div>' . "\r\n"
					. "<script> var profile_uid = " . ($this->session->getLocalUserId() ?: 0) . ";"
					. "</script>";
			}
		} elseif ($mode === self::MODE_CHANNEL) {
			$items = $this->addChildren($items, true, $order, $uid, $mode, $ignoredGsids);

			if (!$update) {
				$live_update_div = '<div id="live-channel"></div>' . "\r\n"
					. "<script> var profile_uid = -1; var netargs = '" . substr($this->args->getCommand(), 8)
					. '?f='
					. (!empty($_GET['no_sharer']) ? '&no_sharer=' . rawurlencode($_GET['no_sharer']) : '')
					. (!empty($_GET['accounttype']) ? '&accounttype=' . rawurlencode($_GET['accounttype']) : '')
					. "'; </script>\r\n";
			}
		} elseif ($mode === self::MODE_COMMUNITY) {
			$items = $this->addChildren($items, true, $order, $uid, $mode, $ignoredGsids);

			if (!$update) {
				$live_update_div = '<div id="live-community"></div>' . "\r\n"
					. "<script> var profile_uid = -1; var netargs = '" . substr($this->args->getCommand(), 10)
					. '?f='
					. (!empty($_GET['no_sharer']) ? '&no_sharer=' . rawurlencode($_GET['no_sharer']) : '')
					. (!empty($_GET['accounttype']) ? '&accounttype=' . rawurlencode($_GET['accounttype']) : '')
					. "'; </script>\r\n";
			}
		} elseif ($mode === self::MODE_CONTACTS) {
			$items = $this->addChildren($items, false, $order, $uid, $mode, $ignoredGsids);

			if (!$update) {
				$live_update_div = '<div id="live-contact"></div>' . "\r\n"
					. "<script> var profile_uid = -1; var netargs = '" . substr($this->args->getCommand(), 8)
					. "?f='; </script>\r\n";
			}
		} elseif ($mode === self::MODE_SEARCH) {
			$live_update_div = '<div id="live-search"></div>' . "\r\n";
		}

		$page_dropping = $this->session->getLocalUserId() && $this->pConfig->get($this->session->getLocalUserId(), 'system', 'show_page_drop', true) && ($this->session->getLocalUserId() == $uid && $mode != self::MODE_SEARCH);

		if (!$update) {
			$_SESSION['return_path'] = $this->args->getQueryString();
		}

		$cb = ['items' => $items, 'mode' => $mode, 'update' => $update, 'preview' => $preview];
		Hook::callAll('conversation_start', $cb);

		$items = $cb['items'];

		$formSecurityToken = BaseModule::getFormSecurityToken('contact_action');

		$threads = $this->getThreadList($items, $mode, $preview, $page_dropping, $formSecurityToken);

		if (in_array($mode, [self::MODE_FILED, self::MODE_SEARCH, self::MODE_CONTACT_POSTS])) {
			$page_template = Renderer::getMarkupTemplate('conversation.tpl');
		} else {
			$page_template = Renderer::getMarkupTemplate('threaded_conversation.tpl');
		}

		$o = Renderer::replaceMacros($page_template, [
			'$return_path' => $this->args->getQueryString(),
			'$live_update' => $live_update_div,
			'$remove'      => $this->l10n->t('remove'),
			'$mode'        => $mode,
			'$update'      => $update,
			'$threads'     => $threads,
			'$dropping'    => ($page_dropping ? $this->l10n->t('Delete Selected Items') : false),
		]);

		$this->profiler->stopRecording();
		return $o;
	}

	/**
	 * @param array  $items
	 * @param string $mode One of self::MODE_*
	 * @param bool   $preview
	 * @param bool   $pagedrop Whether to enable the user to select the thread for deletion
	 * @param string $formSecurityToken A 'contact_action' form security token
	 * @return array
	 * @throws InternalServerErrorException
	 * @throws ImagickException
	 */
	public function getThreadList(array $items, string $mode, bool $preview, bool $pagedrop, string $formSecurityToken): array
	{
		if (!$items) {
			return [];
		}

		if (in_array($mode, [self::MODE_FILED, self::MODE_SEARCH, self::MODE_CONTACT_POSTS])) {
			$threads = $this->getContextLessThreadList($items, $mode, $preview, $pagedrop, $formSecurityToken);
		} else {
			$conv_responses = [
				'like'        => [],
				'dislike'     => [],
				'attendyes'   => [],
				'attendno'    => [],
				'attendmaybe' => [],
				'announce'    => [],
			];

			if ($this->pConfig->get($this->session->getLocalUserId(), 'system', 'hide_dislike')) {
				unset($conv_responses['dislike']);
			}

			if (in_array($mode, [self::MODE_CHANNEL, self::MODE_COMMUNITY, self::MODE_CONTACTS, self::MODE_PROFILE])) {
				$writable = true;
			} else {
				$writable = $items[0]['writable'] || ($items[0]['uid'] == 0) && in_array($items[0]['network'], Protocol::FEDERATED);
			}

			if (!$this->session->getLocalUserId()) {
				$writable = false;
			}

			// Normal View
			$conv = new Thread($mode, $preview, $writable);

			/*
			* get all the topmost parents
			* this shouldn't be needed, as we should have only them in our array
			* But for now, this array respects the old style, just in case
			*/
			foreach ($items as $item) {
				// Can we put this after the visibility check?
				$this->builtinActivityPuller($item, $conv_responses);

				// Only add what is visible
				if ($item['network'] === Protocol::MAIL && $this->session->getLocalUserId() != $item['uid']) {
					continue;
				}

				if (!$this->item->isVisibleActivity($item)) {
					continue;
				}

				/// @todo Check if this call is needed or not
				$arr = ['item' => $item];
				Hook::callAll('display_item', $arr);

				$item['pagedrop'] = $pagedrop;

				if ($item['gravity'] == ItemModel::GRAVITY_PARENT) {
					$item_object = new PostObject($item);
					$conv->addParent($item_object);
				}
			}

			$threads = $conv->getTemplateData($conv_responses, $formSecurityToken);
			if (!$threads) {
				$this->logger->info('[ERROR] conversation : Failed to get template data.');
				$threads = [];
			}
		}

		return $threads;
	}

	/**
	 * Adds some information (Causer, post reason, direction) to the fetched post row.
	 *
	 * @param array   $row        Post row
	 * @param array   $activity   Contact data of the resharer
	 * @param array   $thr_parent Thread parent row
	 *
	 * @return array items with parents and comments
	 */
	private function addRowInformation(array $row, array $activity, array $thr_parent): array
	{
		$this->profiler->startRecording('rendering');

		if (!$row['writable']) {
			$row['writable'] = in_array($row['network'], Protocol::FEDERATED);
		}

		if (!empty($activity)) {
			if (($row['gravity'] == ItemModel::GRAVITY_PARENT)) {
				$row['post-reason'] = ItemModel::PR_ANNOUNCEMENT;

				$row     = array_merge($row, $activity);
				$contact = Contact::getById($activity['causer-id'], ['url', 'name', 'thumb']);

				$row['causer-link']   = $contact['url'];
				$row['causer-avatar'] = $contact['thumb'];
				$row['causer-name']   = $contact['name'];
			} elseif (($row['gravity'] == ItemModel::GRAVITY_ACTIVITY) && ($row['verb'] == Activity::ANNOUNCE) &&
				($row['author-id'] == $activity['causer-id'])
			) {
				return $row;
			}
		}

		switch ($row['post-reason']) {
			case ItemModel::PR_TO:
				$row['direction'] = ['direction' => 7, 'title' => $this->l10n->t('You had been addressed (%s).', 'to')];
				break;
			case ItemModel::PR_CC:
				$row['direction'] = ['direction' => 7, 'title' => $this->l10n->t('You had been addressed (%s).', 'cc')];
				break;
			case ItemModel::PR_BTO:
				$row['direction'] = ['direction' => 7, 'title' => $this->l10n->t('You had been addressed (%s).', 'bto')];
				break;
			case ItemModel::PR_BCC:
				$row['direction'] = ['direction' => 7, 'title' => $this->l10n->t('You had been addressed (%s).', 'bcc')];
				break;
			case ItemModel::PR_AUDIENCE:
				$row['direction'] = ['direction' => 7, 'title' => $this->l10n->t('You had been addressed (%s).', 'audience')];
				break;
			case ItemModel::PR_FOLLOWER:
				$row['direction'] = ['direction' => 6, 'title' => $this->l10n->t('You are following %s.', $row['causer-name'] ?: $row['author-name'])];
				break;
			case ItemModel::PR_TAG:
				$tags = Category::getArrayByURIId($row['uri-id'], $row['uid'], Category::SUBCRIPTION);
				if (!empty($tags)) {
					$row['direction'] = ['direction' => 4, 'title' => $this->l10n->t('You subscribed to %s.', implode(', ', $tags))];
				} else {
					$row['direction'] = ['direction' => 4, 'title' => $this->l10n->t('You subscribed to one or more tags in this post.')];
				}
				break;
			case ItemModel::PR_ANNOUNCEMENT:
				if (!empty($row['causer-id']) && $this->pConfig->get($this->session->getLocalUserId(), 'system', 'display_resharer')) {
					$row['owner-id']     = $row['causer-id'];
					$row['owner-link']   = $row['causer-link'];
					$row['owner-avatar'] = $row['causer-avatar'];
					$row['owner-name']   = $row['causer-name'];
				}

				if (in_array($row['gravity'], [ItemModel::GRAVITY_PARENT, ItemModel::GRAVITY_COMMENT]) && !empty($row['causer-id'])) {
					$causer = [
						'uid'     => 0,
						'id'      => $row['causer-id'],
						'network' => $row['causer-network'],
						'url'     => $row['causer-link'],
						'alias'   => $row['causer-alias'],
					];

					$row['reshared'] = $this->l10n->t('%s reshared this.', '<a href="' . htmlentities(Contact::magicLinkByContact($causer)) . '">' . htmlentities($row['causer-name']) . '</a>');
				}
				$row['direction'] = ['direction' => 3, 'title' => (empty($row['causer-id']) ? $this->l10n->t('Reshared') : $this->l10n->t('Reshared by %s <%s>', $row['causer-name'], $row['causer-link']))];
				break;
			case ItemModel::PR_COMMENT:
				$row['direction'] = ['direction' => 5, 'title' => $this->l10n->t('%s is participating in this thread.', $row['author-name'])];
				break;
			case ItemModel::PR_STORED:
				$row['direction'] = ['direction' => 8, 'title' => $this->l10n->t('Stored for general reasons')];
				break;
			case ItemModel::PR_GLOBAL:
				$row['direction'] = ['direction' => 9, 'title' => $this->l10n->t('Global post')];
				break;
			case ItemModel::PR_RELAY:
				$row['direction'] = ['direction' => 10, 'title' => (empty($row['causer-id']) ? $this->l10n->t('Sent via an relay server') : $this->l10n->t('Sent via the relay server %s <%s>', $row['causer-name'], $row['causer-link']))];
				break;
			case ItemModel::PR_FETCHED:
				$row['direction'] = ['direction' => 2, 'title' => (empty($row['causer-id']) ? $this->l10n->t('Fetched') : $this->l10n->t('Fetched because of %s <%s>', $row['causer-name'], $row['causer-link']))];
				break;
			case ItemModel::PR_COMPLETION:
				$row['direction'] = ['direction' => 2, 'title' => $this->l10n->t('Stored because of a child post to complete this thread.')];
				break;
			case ItemModel::PR_DIRECT:
				$row['direction'] = ['direction' => 6, 'title' => $this->l10n->t('Local delivery')];
				break;
			case ItemModel::PR_ACTIVITY:
				$row['direction'] = ['direction' => 2, 'title' => $this->l10n->t('Stored because of your activity (like, comment, star, ...)')];
				break;
			case ItemModel::PR_DISTRIBUTE:
				$row['direction'] = ['direction' => 6, 'title' => $this->l10n->t('Distributed')];
				break;
			case ItemModel::PR_PUSHED:
				$row['direction'] = ['direction' => 1, 'title' => $this->l10n->t('Pushed to us')];
				break;
		}

		$row['thr-parent-row'] = $thr_parent;

		$this->profiler->stopRecording();
		return $row;
	}

	/**
	 * Add comments to top level entries that had been fetched before
	 *
	 * The system will fetch the comments for the local user whenever possible.
	 * This behaviour is currently needed to allow commenting on Friendica posts.
	 *
	 * @param array  $parents       Parent items
	 * @param bool   $block_authors
	 * @param string $order         Either "received" or "commented"
	 * @param int    $uid
	 * @param string $mode          One of self::MODE_*
	 * @param array  $ignoredGsids  List of ids of servers ignored by the user
	 * @return array items with parents and comments
	 * @throws InternalServerErrorException
	 */
	private function addChildren(array $parents, bool $block_authors, string $order, int $uid, string $mode, array $ignoredGsids = []): array
	{
		$this->profiler->startRecording('rendering');
		if (count($parents) > 1) {
			$max_comments = $this->config->get('system', 'max_comments', 100);
		} else {
			$max_comments = $this->config->get('system', 'max_display_comments', 1000);
		}

		$activities      = [];
		$uriids          = [];
		$commentcounter  = [];
		$activitycounter = [];

		foreach ($parents as $parent) {
			if (!empty($parent['thr-parent-id']) && !empty($parent['gravity']) && ($parent['gravity'] == ItemModel::GRAVITY_ACTIVITY)) {
				$uriid = $parent['thr-parent-id'];
				if (!empty($parent['author-id'])) {
					$activities[$uriid] = ['causer-id' => $parent['author-id']];
					foreach (['commented', 'received', 'created'] as $orderfields) {
						if (!empty($parent[$orderfields])) {
							$activities[$uriid][$orderfields] = $parent[$orderfields];
						}
					}
				}
			} else {
				$uriid = $parent['uri-id'];
			}
			$uriids[] = $uriid;

			$commentcounter[$uriid]  = 0;
			$activitycounter[$uriid] = 0;
		}

		$condition = ['parent-uri-id' => $uriids];
		if ($block_authors) {
			$condition['author-hidden'] = false;
		}

		$emojis      = $this->getEmojis($uriids);
		$quoteshares = $this->getQuoteShares($uriids);
		$counts      = $this->getCounts($uriids);

		if (!$this->config->get('system', 'legacy_activities')) {
			$condition = DBA::mergeConditions($condition, ["(`gravity` != ? OR `origin`)", ItemModel::GRAVITY_ACTIVITY]);
		}

		$condition = DBA::mergeConditions(
			$condition,
			["`uid` IN (0, ?) AND (NOT `verb` IN (?, ?, ?) OR `verb` IS NULL)", $uid, Activity::FOLLOW, Activity::VIEW, Activity::READ]
		);

		$condition = DBA::mergeConditions($condition, ["(`uid` != ? OR `private` != ?)", 0, ItemModel::PRIVATE]);

		$condition = DBA::mergeConditions(
			$condition,
			[
				"`visible` AND NOT `deleted` AND NOT `author-blocked` AND NOT `owner-blocked`
			AND ((NOT `contact-pending` AND (`contact-rel` IN (?, ?))) OR `self` OR `contact-uid` = ?)",
				Contact::SHARING, Contact::FRIEND, 0
			]
		);

		$thread_parents = Post::select(['uri-id', 'causer-id'], $condition, ['order' => ['uri-id' => false, 'uid']]);

		$thr_parent = [];

		while ($row = Post::fetch($thread_parents)) {
			$thr_parent[$row['uri-id']] = $row;
		}
		DBA::close($thread_parents);

		$params = ['order' => ['uri-id' => true, 'uid' => true]];

		$thread_items = Post::select(array_merge(ItemModel::DISPLAY_FIELDLIST, ['featured', 'contact-uid', 'gravity', 'post-type', 'post-reason']), $condition, $params);

		$items         = [];
		$quote_uri_ids = [];
		$authors       = [];

		while ($row = Post::fetch($thread_items)) {
			if (!empty($items[$row['uri-id']]) && ($row['uid'] == 0)) {
				continue;
			}

			if (
				in_array($row['author-gsid'], $ignoredGsids)
				|| in_array($row['owner-gsid'], $ignoredGsids)
				|| in_array($row['causer-gsid'], $ignoredGsids)
			) {
				continue;
			}

			if (($mode != self::MODE_CONTACTS) && !$row['origin']) {
				$row['featured'] = false;
			}

			if ($max_comments > 0) {
				if (($row['gravity'] == ItemModel::GRAVITY_COMMENT) && (++$commentcounter[$row['parent-uri-id']] > $max_comments)) {
					continue;
				}
				if (($row['gravity'] == ItemModel::GRAVITY_ACTIVITY) && (++$activitycounter[$row['parent-uri-id']] > $max_comments)) {
					continue;
				}
			}

			$authors[] = $row['author-id'];
			$authors[] = $row['owner-id'];

			if (in_array($row['gravity'], [ItemModel::GRAVITY_PARENT, ItemModel::GRAVITY_COMMENT])) {
				$quote_uri_ids[$row['uri-id']] = [
					'uri-id'        => $row['uri-id'],
					'uri'           => $row['uri'],
					'parent-uri-id' => $row['parent-uri-id'],
					'parent-uri'    => $row['parent-uri'],
				];
			}

			$items[$row['uri-id']] = $this->addRowInformation($row, $activities[$row['uri-id']] ?? [], $thr_parent[$row['thr-parent-id']] ?? []);
		}

		DBA::close($thread_items);

		$quotes = Post::select(array_merge(ItemModel::DISPLAY_FIELDLIST, ['featured', 'contact-uid', 'gravity', 'post-type', 'post-reason']), ['quote-uri-id' => array_column($quote_uri_ids, 'uri-id'), 'body' => '', 'uid' => 0]);
		while ($quote = Post::fetch($quotes)) {
			$row = $quote;

			$row['uid']           = $uid;
			$row['verb']          = $row['body'] = $row['raw-body'] = Activity::ANNOUNCE;
			$row['gravity']       = ItemModel::GRAVITY_ACTIVITY;
			$row['object-type']   = Activity\ObjectType::NOTE;
			$row['parent-uri']    = $quote_uri_ids[$quote['quote-uri-id']]['parent-uri'];
			$row['parent-uri-id'] = $quote_uri_ids[$quote['quote-uri-id']]['parent-uri-id'];
			$row['thr-parent']    = $quote_uri_ids[$quote['quote-uri-id']]['uri'];
			$row['thr-parent-id'] = $quote_uri_ids[$quote['quote-uri-id']]['uri-id'];

			$authors[] = $row['author-id'];
			$authors[] = $row['owner-id'];

			$items[$row['uri-id']] = $this->addRowInformation($row, [], []);
		}
		DBA::close($quotes);

		$authors = array_unique($authors);

		$blocks    = [];
		$ignores   = [];
		$collapses = [];
		if (!empty($authors)) {
			$usercontacts = DBA::select('user-contact', ['cid', 'blocked', 'ignored', 'collapsed'], ['uid' => $uid, 'cid' => $authors]);
			while ($usercontact = DBA::fetch($usercontacts)) {
				if ($usercontact['blocked']) {
					$blocks[] = $usercontact['cid'];
				}
				if ($usercontact['ignored']) {
					$ignores[] = $usercontact['cid'];
				}
				if ($usercontact['collapsed']) {
					$collapses[] = $usercontact['cid'];
				}
			}
			DBA::close($usercontacts);
		}

		foreach ($items as $key => $row) {
			$items[$key]['emojis']      = $emojis[$key]      ?? [];
			$items[$key]['counts']      = $counts[$key]      ?? 0;
			$items[$key]['quoteshares'] = $quoteshares[$key] ?? [];

			$always_display = in_array($mode, [self::MODE_CONTACTS, self::MODE_CONTACT_POSTS]);

			$items[$key]['user-blocked-author']   = !$always_display && in_array($row['author-id'], $blocks);
			$items[$key]['user-ignored-author']   = !$always_display && in_array($row['author-id'], $ignores);
			$items[$key]['user-blocked-owner']    = !$always_display && in_array($row['owner-id'], $blocks);
			$items[$key]['user-ignored-owner']    = !$always_display && in_array($row['owner-id'], $ignores);
			$items[$key]['user-collapsed-author'] = !$always_display && in_array($row['author-id'], $collapses);
			$items[$key]['user-collapsed-owner']  = !$always_display && in_array($row['owner-id'], $collapses);

			if (
				in_array($mode, [self::MODE_CHANNEL, self::MODE_COMMUNITY, self::MODE_NETWORK]) &&
				(in_array($row['author-id'], $blocks) || in_array($row['owner-id'], $blocks) || in_array($row['author-id'], $ignores) || in_array($row['owner-id'], $ignores))
			) {
				unset($items[$key]);
			}
		}

		$items = $this->convSort($items, $order);

		$this->profiler->stopRecording();
		return $items;
	}

	/**
	 * Fetch emoji reaction from the conversation
	 *
	 * @param array $uriids
	 * @return array
	 */
	private function getEmojis(array $uriids): array
	{
		$emojis = [];

		foreach (Post\Counts::get(['parent-uri-id' => $uriids]) as $count) {
			$emojis[$count['uri-id']][$count['reaction']]['emoji'] = $count['reaction'];
			$emojis[$count['uri-id']][$count['reaction']]['verb']  = Verb::getByID($count['vid']);
			$emojis[$count['uri-id']][$count['reaction']]['total'] = $count['count'];
			$emojis[$count['uri-id']][$count['reaction']]['title'] = [];
		}

		// @todo The following code should be removed, once that we display activity authors on demand
		$activity_verbs = [
			Activity::LIKE,
			Activity::DISLIKE,
			Activity::ATTEND,
			Activity::ATTENDMAYBE,
			Activity::ATTENDNO,
			Activity::ANNOUNCE,
			Activity::VIEW,
			Activity::READ,
		];

		$verbs     = array_merge($activity_verbs, [Activity::EMOJIREACT, Activity::POST]);
		$condition = DBA::mergeConditions(['parent-uri-id' => $uriids, 'gravity' => [ItemModel::GRAVITY_ACTIVITY, ItemModel::GRAVITY_COMMENT], 'verb' => $verbs], ["NOT `deleted`"]);
		$separator = chr(255) . chr(255) . chr(255);

		$sql = "SELECT `parent-uri-id`, `thr-parent-id`, `body`, `verb`, `gravity`, GROUP_CONCAT(REPLACE(`author-name`, '" . $separator . "', ' ') SEPARATOR '" . $separator . "' LIMIT 50) AS `title` FROM `post-view` WHERE " . array_shift($condition) . " GROUP BY `parent-uri-id`, `thr-parent-id`, `verb`, `body`, `gravity`";

		$rows = DBA::p($sql, $condition);
		while ($row = DBA::fetch($rows)) {
			if ($row['gravity'] == ItemModel::GRAVITY_ACTIVITY) {
				$emoji = $row['body'] ?: $row['verb'];
			} else {
				$emoji = '';
			}

			if (isset($emojis[$row['thr-parent-id']][$emoji]['title'])) {
				$emojis[$row['thr-parent-id']][$emoji]['title'] = array_unique(array_merge($emojis[$row['thr-parent-id']][$emoji]['title'] ?? [], explode($separator, $row['title'])));
			}
		}
		DBA::close($rows);

		return $emojis;
	}

	/**
	 * Fetch comment counts from the conversation
	 *
	 * @param array $uriids
	 * @return array
	 */
	private function getCounts(array $uriids): array
	{
		$counts = [];

		foreach (Post\Counts::get(['parent-uri-id' => $uriids, 'verb' => Activity::POST]) as $count) {
			$counts[$count['parent-uri-id']] = ($counts[$count['parent-uri-id']] ?? 0) + $count['count'];
		}

		return $counts;
	}

	/**
	 * Fetch quote shares from the conversation
	 *
	 * @param array $uriids
	 * @return array
	 */
	private function getQuoteShares(array $uriids): array
	{
		$condition = DBA::mergeConditions(['quote-uri-id' => $uriids], ["NOT `quote-uri-id` IS NULL"]);
		$separator = chr(255) . chr(255) . chr(255);

		$sql = "SELECT `quote-uri-id`, COUNT(*) AS `total`, GROUP_CONCAT(REPLACE(`name`, '" . $separator . "', ' ') SEPARATOR '" . $separator . "' LIMIT 50) AS `title` FROM `post-content` INNER JOIN `post` ON `post`.`uri-id` = `post-content`.`uri-id` INNER JOIN `contact` ON `post`.`author-id` = `contact`.`id` WHERE " . array_shift($condition) . " GROUP BY `quote-uri-id`";

		$quotes = [];

		$rows = DBA::p($sql, $condition);
		while ($row = DBA::fetch($rows)) {
			$quotes[$row['quote-uri-id']]['total'] = $row['total'];
			$quotes[$row['quote-uri-id']]['title'] = array_unique(explode($separator, $row['title']));
		}
		DBA::close($rows);

		return $quotes;
	}

	/**
	 * Plucks the children of the given parent from a given item list.
	 *
	 * @param array $item_list
	 * @param array $parent
	 * @param bool  $recursive
	 * @return array
	 */
	private function getItemChildren(array &$item_list, array $parent, bool $recursive = true): array
	{
		$this->profiler->startRecording('rendering');
		$children = [];
		foreach ($item_list as $i => $item) {
			if ($item['gravity'] != ItemModel::GRAVITY_PARENT) {
				if ($recursive) {
					// Fallback to parent-uri if thr-parent is not set
					$thr_parent = $item['thr-parent-id'];
					if ($thr_parent == '') {
						$thr_parent = $item['parent-uri-id'];
					}

					if ($thr_parent == $parent['uri-id']) {
						$item['children'] = $this->getItemChildren($item_list, $item);

						$children[] = $item;
						unset($item_list[$i]);
					}
				} elseif ($item['parent-uri-id'] == $parent['uri-id']) {
					$children[] = $item;
					unset($item_list[$i]);
				}
			}
		}
		$this->profiler->stopRecording();
		return $children;
	}

	/**
	 * Recursively sorts a tree-like item array
	 *
	 * @param array $items
	 * @return array
	 */
	private function sortItemChildren(array $items): array
	{
		$this->profiler->startRecording('rendering');
		$result = $items;
		usort($result, [$this, 'sortThrReceivedRev']);
		foreach ($result as $k => $i) {
			if (isset($result[$k]['children'])) {
				$result[$k]['children'] = $this->sortItemChildren($result[$k]['children']);
			}
		}
		$this->profiler->stopRecording();
		return $result;
	}

	/**
	 * Recursively add all children items at the top level of a list
	 *
	 * @param array $children List of items to append
	 * @param array $item_list
	 */
	private function addChildrenToList(array $children, array &$item_list)
	{
		foreach ($children as $child) {
			$item_list[] = $child;
			if (isset($child['children'])) {
				$this->addChildrenToList($child['children'], $item_list);
			}
		}
	}

	/**
	 * Selectively flattens a tree-like item structure to prevent threading stairs
	 *
	 * This recursive function takes the item tree structure created by conv_sort() and
	 * flatten the extraneous depth levels when people reply sequentially, removing the
	 * stairs effect in threaded conversations limiting the available content width.
	 *
	 * The basic principle is the following: if a post item has only one reply and is
	 * the last reply of its parent, then the reply is moved to the parent.
	 *
	 * This process is rendered somewhat more complicated because items can be either
	 * replies or likes, and these don't factor at all in the reply count/last reply.
	 *
	 * @param array $parent A tree-like array of items
	 * @return array
	 */
	private function smartFlattenConversation(array $parent): array
	{
		$this->profiler->startRecording('rendering');
		if (!isset($parent['children']) || count($parent['children']) == 0) {
			$this->profiler->stopRecording();
			return $parent;
		}

		// We use a for loop to ensure we process the newly-moved items
		for ($i = 0; $i < count($parent['children']); $i++) {
			$child = $parent['children'][$i];

			if (isset($child['children']) && count($child['children'])) {
				// This helps counting only the regular posts
				$count_post_closure = function ($var) {
					$this->profiler->stopRecording();
					return $var['verb'] === Activity::POST;
				};

				$child_post_count = count(array_filter($child['children'], $count_post_closure));

				$remaining_post_count = count(array_filter(array_slice($parent['children'], $i), $count_post_closure));

				// If there's only one child's children post and this is the last child post
				if ($child_post_count == 1 && $remaining_post_count == 1) {

					// Searches the post item in the children
					$j = 0;
					while ($child['children'][$j]['verb'] !== Activity::POST && $j < count($child['children'])) {
						$j++;
					}

					$moved_item = $child['children'][$j];
					unset($parent['children'][$i]['children'][$j]);
					$parent['children'][] = $moved_item;
				} else {
					$parent['children'][$i] = $this->smartFlattenConversation($child);
				}
			}
		}

		$this->profiler->stopRecording();
		return $parent;
	}

	/**
	 * Expands a flat list of items into corresponding tree-like conversation structures.
	 *
	 * sort the top-level posts either on "received" or "commented", and finally
	 * append all the items at the top level (???)
	 *
	 * @param array  $item_list A list of items belonging to one or more conversations
	 * @param string $order     Either on "received" or "commented"
	 * @return array
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private function convSort(array $item_list, string $order): array
	{
		$this->profiler->startRecording('rendering');
		$parents = [];

		if (!(is_array($item_list) && count($item_list))) {
			$this->profiler->stopRecording();
			return $parents;
		}

		$item_array = [];

		// Dedupes the item list on the uri to prevent infinite loops
		foreach ($item_list as $item) {
			$item_array[$item['uri-id']] = $item;
		}

		// Extract the top level items
		foreach ($item_array as $item) {
			if ($item['gravity'] == ItemModel::GRAVITY_PARENT) {
				$parents[] = $item;
			}
		}

		if (stristr($order, 'pinned_received')) {
			usort($parents, [$this, 'sortThrFeaturedReceived']);
		} elseif (stristr($order, 'pinned_commented')) {
			usort($parents, [$this, 'sortThrFeaturedCommented']);
		} elseif (stristr($order, 'pinned_created')) {
			usort($parents, [$this, 'sortThrFeaturedCreated']);
		} elseif (stristr($order, 'received')) {
			usort($parents, [$this, 'sortThrReceived']);
		} elseif (stristr($order, 'commented')) {
			usort($parents, [$this, 'sortThrCommented']);
		} elseif (stristr($order, 'created')) {
			usort($parents, [$this, 'sortThrCreated']);
		}

		/*
		* Plucks children from the item_array, second pass collects eventual orphan
		* items and add them as children of their top-level post.
		*/
		foreach ($parents as $i => $parent) {
			$parents[$i]['children'] = array_merge(
				$this->getItemChildren($item_array, $parent, true),
				$this->getItemChildren($item_array, $parent, false)
			);
		}

		foreach ($parents as $i => $parent) {
			$parents[$i]['children'] = $this->sortItemChildren($parents[$i]['children']);
		}

		if (!$this->pConfig->get($this->session->getLocalUserId(), 'system', 'no_smart_threading', 0)) {
			foreach ($parents as $i => $parent) {
				$parents[$i] = $this->smartFlattenConversation($parent);
			}
		}

		/// @TODO: Stop recursively adding all children back to the top level (!!!)
		/// However, this apparently ensures responses (likes, attendance) display (?!)
		foreach ($parents as $parent) {
			if (count($parent['children'])) {
				$this->addChildrenToList($parent['children'], $parents);
			}
		}

		$this->profiler->stopRecording();
		return $parents;
	}

	/**
	 * usort() callback to sort item arrays by featured and the received key
	 *
	 * @param array $a
	 * @param array $b
	 * @return int
	 */
	private function sortThrFeaturedReceived(array $a, array $b): int
	{
		if ($b['featured'] && !$a['featured']) {
			return 1;
		} elseif (!$b['featured'] && $a['featured']) {
			return -1;
		}

		return strcmp($b['received'], $a['received']);
	}

	/**
	 * usort() callback to sort item arrays by featured and the received key
	 *
	 * @param array $a
	 * @param array $b
	 * @return int
	 */
	private function sortThrFeaturedCommented(array $a, array $b): int
	{
		if ($b['featured'] && !$a['featured']) {
			return 1;
		} elseif (!$b['featured'] && $a['featured']) {
			return -1;
		}

		return strcmp($b['commented'], $a['commented']);
	}

	/**
	 * usort() callback to sort item arrays by featured and the created key
	 *
	 * @param array $a
	 * @param array $b
	 * @return int
	 */
	private function sortThrFeaturedCreated(array $a, array $b): int
	{
		if ($b['featured'] && !$a['featured']) {
			return 1;
		} elseif (!$b['featured'] && $a['featured']) {
			return -1;
		}

		return strcmp($b['created'], $a['created']);
	}

	/**
	 * usort() callback to sort item arrays by the received key
	 *
	 * @param array $a
	 * @param array $b
	 * @return int
	 */
	private function sortThrReceived(array $a, array $b): int
	{
		return strcmp($b['received'], $a['received']);
	}

	/**
	 * usort() callback to reverse sort item arrays by the received key
	 *
	 * @param array $a
	 * @param array $b
	 * @return int
	 */
	private function sortThrReceivedRev(array $a, array $b): int
	{
		return strcmp($a['received'], $b['received']);
	}

	/**
	 * usort() callback to sort item arrays by the commented key
	 *
	 * @param array $a
	 * @param array $b
	 * @return int
	 */
	private function sortThrCommented(array $a, array $b): int
	{
		return strcmp($b['commented'], $a['commented']);
	}

	/**
	 * usort() callback to sort item arrays by the created key
	 *
	 * @param array $a
	 * @param array $b
	 * @return int
	 */
	private function sortThrCreated(array $a, array $b): int
	{
		return strcmp($b['created'], $a['created']);
	}

	/**
	 * "New Item View" on network page or search page results
	 * - just loop through the items and format them minimally for display
	 *
	 * @param array  $items
	 * @param string $mode              One of self::MODE_*
	 * @param bool   $preview           Whether the display is a preview
	 * @param bool   $pagedrop          Whether the user can select the threads for deletion
	 * @param string $formSecurityToken A 'contact_action' form security token
	 * @return array
	 * @throws InternalServerErrorException
	 * @throws \ImagickException
	 */
	public function getContextLessThreadList(array $items, string $mode, bool $preview, bool $pagedrop, string $formSecurityToken): array
	{
		$threads = [];
		$uriids  = [];

		foreach ($items as $item) {
			if (in_array($item['uri-id'], $uriids)) {
				continue;
			}

			$uriids[] = $item['uri-id'];

			if (!$this->item->isVisibleActivity($item)) {
				continue;
			}

			// prevent private email from leaking.
			if ($item['network'] === Protocol::MAIL && $this->session->getLocalUserId() != $item['uid']) {
				continue;
			}

			$profile_name = $item['author-name'];
			if (!empty($item['author-link']) && empty($item['author-name'])) {
				$profile_name = $item['author-link'];
			}

			$tags = Tag::populateFromItem($item);

			$author = [
				'uid'     => 0,
				'id'      => $item['author-id'],
				'network' => $item['author-network'],
				'url'     => $item['author-link'],
				'alias'   => $item['author-alias'],
			];
			$profile_link = Contact::magicLinkByContact($author);

			$sparkle = '';
			if (strpos($profile_link, 'contact/redir/') === 0) {
				$sparkle = ' sparkle';
			}

			$locate = ['location' => $item['location'], 'coord' => $item['coord'], 'html' => ''];
			Hook::callAll('render_location', $locate);
			$location_html = $locate['html'] ?: Strings::escapeHtml($locate['location'] ?: $locate['coord'] ?: '');

			$this->item->localize($item);
			if ($mode === self::MODE_FILED) {
				$dropping = true;
			} else {
				$dropping = false;
			}

			$drop = [
				'dropping' => $dropping,
				'pagedrop' => $pagedrop,
				'select'   => $this->l10n->t('Select'),
				'delete'   => $this->l10n->t('Delete'),
			];

			$likebuttons = [
				'like'     => null,
				'dislike'  => null,
				'share'    => null,
				'announce' => null,
			];

			if ($this->pConfig->get($this->session->getLocalUserId(), 'system', 'hide_dislike')) {
				unset($likebuttons['dislike']);
			}

			$body_html = ItemModel::prepareBody($item, true, $preview);

			list($categories, $folders) = $this->item->determineCategoriesTerms($item, $this->session->getLocalUserId());

			if (!empty($item['featured'])) {
				$pinned = $this->l10n->t('Pinned item');
			} else {
				$pinned = '';
			}

			$tmp_item = [
				'template'             => 'search_item.tpl',
				'id'                   => ($preview ? 'P0' : $item['id']),
				'guid'                 => ($preview ? 'Q0' : $item['guid']),
				'commented'            => $item['commented'],
				'received'             => $item['received'],
				'created_date'         => $item['created'],
				'uriid'                => $item['uri-id'],
				'author_gsid'          => $item['author-gsid'],
				'network'              => $item['network'],
				'network_name'         => ContactSelector::networkToName($item['author-network'], $item['network'], $item['author-gsid']),
				'network_svg'          => ContactSelector::networkToSVG($item['network'], $item['author-gsid'], '', $this->session->getLocalUserId()),
				'linktitle'            => $this->l10n->t('View %s\'s profile @ %s', $profile_name, $item['author-link']),
				'profile_url'          => $profile_link,
				'item_photo_menu_html' => $this->item->photoMenu($item, $formSecurityToken),
				'name'                 => $profile_name,
				'sparkle'              => $sparkle,
				'lock'                 => false,
				'thumb'                => $this->baseURL->remove($this->item->getAuthorAvatar($item)),
				'title'                => $item['title'],
				'summary'              => $item['content-warning'],
				'body_html'            => $body_html,
				'tags'                 => $tags['tags'],
				'hashtags'             => $tags['hashtags'],
				'mentions'             => $tags['mentions'],
				'implicit_mentions'    => $tags['implicit_mentions'],
				'txt_cats'             => $this->l10n->t('Categories:'),
				'txt_folders'          => $this->l10n->t('Filed under:'),
				'has_cats'             => ((count($categories)) ? 'true' : ''),
				'has_folders'          => ((count($folders)) ? 'true' : ''),
				'categories'           => $categories,
				'folders'              => $folders,
				'text'                 => strip_tags($body_html),
				'localtime'            => DateTimeFormat::local($item['created'], 'r'),
				'utc'                  => DateTimeFormat::utc($item['created'], 'c'),
				'ago'                  => (($item['app']) ? $this->l10n->t('%s from %s', Temporal::getRelativeDate($item['created']), $item['app']) : Temporal::getRelativeDate($item['created'])),
				'location_html'        => $location_html,
				'indent'               => '',
				'owner_name'           => '',
				'owner_url'            => '',
				'owner_photo'          => $this->baseURL->remove($this->item->getOwnerAvatar($item)),
				'plink'                => ItemModel::getPlink($item),
				'edpost'               => false,
				'pinned'               => $pinned,
				'isstarred'            => 'unstarred',
				'star'                 => false,
				'drop'                 => $drop,
				'vote'                 => $likebuttons,
				'like_html'            => '',
				'dislike_html '        => '',
				'comment_html'         => '',
				'conv'                 => $preview ? '' : ['href' => 'display/' . $item['guid'], 'title' => $this->l10n->t('View in context')],
				'previewing'           => $preview ? ' preview ' : '',
				'wait'                 => $this->l10n->t('Please wait'),
				'thread_level'         => 1,
			];

			$arr = ['item' => $item, 'output' => $tmp_item];
			Hook::callAll('display_item', $arr);

			$threads[] = [
				'id'      => $item['id'],
				'network' => $item['network'],
				'items'   => [$arr['output']],
			];
		}

		return $threads;
	}
}
