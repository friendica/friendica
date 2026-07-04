<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Content\Conversation;

use Friendica\App;
use Friendica\App\BaseURL;
use Friendica\Content\ContactSelector;
use Friendica\Content\Feature;
use Friendica\Content\Item;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Protocol;
use Friendica\Event\ArrayFilterEvent;
use Friendica\Model\Contact;
use Friendica\Model\Item as ItemModel;
use Friendica\Model\Post;
use Friendica\Model\Tag;
use Friendica\Model\User;
use Friendica\Protocol\Activity;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Strings;
use GuzzleHttp\Psr7\Uri;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * Builds post template data from item data.
 * This class contains logic for building template arrays from item data for rendering.
 */
final class PostTemplateBuilder
{
	public function __construct(
		private readonly L10n $l10n,
		private readonly IManageConfigValues $config,
		private readonly App\Arguments $arguments,
		private readonly BaseURL $baseURL,
		private readonly IManagePersonalConfigValues $pConfig,
		private readonly EventDispatcherInterface $eventDispatcher,
		private readonly \Friendica\Core\Addon\AddonHelper $addonHelper,
		private readonly LoggerInterface $logger,
		private readonly Item $item,
		private readonly ActivityFormatter $activityFormatter,
		private int $uid = 0,
		private ?string $remote_comment = null,
	) {}

	/**
	 * Render one threaded root post (including children) to template data.
	 *
	 * @param array<string, mixed> $item
	 * @param bool $preview
	 * @param bool $writable
	 * @param int $uid
	 * @param array<string, array> $convResponses
	 * @param string $formSecurityToken
	 * @return array<string, mixed>|null
	 */
	public function renderThreadRoot(array $item, bool $preview, bool $writable, int $uid, array $convResponses, string $formSecurityToken, ?string $remote_comment = null): ?array
	{
		if (!isset($item['uri-id'], $item['guid'], $item['id'])) {
			return null;
		}

		$this->uid            = $uid;
		$this->remote_comment = $remote_comment;

		return $this->buildThreadTemplateData($item, $preview, $writable, $uid, $convResponses, $formSecurityToken, 1, []);
	}

	/**
	 * Build thread template data recursively.
	 *
	 * @param array<string, mixed> $item
	 * @param bool $preview
	 * @param bool $writable
	 * @param int $profileOwner
	 * @param array<string, array> $convResponses
	 * @param string $formSecurityToken
	 * @param int $threadLevel
	 * @param array<int, array{guid: string, name: string}> $threadParents
	 * @return array<string, mixed>|null
	 */
	private function buildThreadTemplateData(array $item, bool $preview, bool $writable, int $profileOwner, array $convResponses, string $formSecurityToken, int $threadLevel, array $threadParents): ?array
	{
		if (($item['network'] ?? '') === Protocol::MAIL && $this->uid !== ($item['uid'] ?? 0)) {
			return null;
		}

		if (!$this->item->isVisibleActivity($item)) {
			return null;
		}

		$profileName = $item['author-name'] ?? ($item['author-link'] ?? '');
		$profileUrl  = $item['author-link'] ?? '';
		if ($this->uid !== 0 && !empty($item['author-id'])) {
			$author = [
				'uid'     => 0,
				'id'      => $item['author-id'],
				'network' => $item['author-network'] ?? '',
				'url'     => $item['author-link']    ?? '',
				'alias'   => $item['author-alias']   ?? '',
			];
			$profileUrl = Contact::magicLinkByContact($author);
		}

		$sparkle = str_starts_with((string) $profileUrl, 'contact/redir/') ? ' sparkle' : '';
		$this->item->localize($item);
		$bodyHtml = ItemModel::prepareBody($item, true, $preview);
		$tags     = Tag::populateFromItem($item);

		$categories = [];
		$folders    = [];
		if ($this->uid) {
			[$categories, $folders] = $this->item->determineCategoriesTerms($item, $this->uid);
		}

		if (!empty($item['body']) && !empty($item['content-warning']) && $this->item->redundantSummary($item['body'], $item['content-warning'])) {
			$item['content-warning'] = '';
		}

		// Set up parent information for "in reply to" display
		$parent_guid     = $threadParents[$item['thr-parent-id'] ?? '']['guid'] ?? '';
		$parent_username = $threadParents[$item['thr-parent-id'] ?? '']['name'] ?? '';
		$parent_unknown  = $parent_username ? '' : $this->l10n->t('Unknown parent');

		// Set up language detection
		$languages = '';
		$language  = '';
		if (!empty($item['language'])) {
			$languages = $this->l10n->t('Detected languages');
			$language  = array_key_first(json_decode((string) $item['language'], true));
		}

		// Set up browser share
		$browsershare = null;
		if (in_array($item['private'] ?? ItemModel::PUBLIC, [ItemModel::PUBLIC, ItemModel::UNLISTED])
			&& in_array($item['network'] ?? '', Protocol::FEDERATED)) {
			$browsershare = [$this->l10n->t('Share via ...'), $this->l10n->t('Share via external services')];
		}

		// Set up owner information
		$owner_url  = '';
		$owner_name = '';
		if ($item['owner-id'] !== $item['author-id']) {
			$owner = [
				'uid'     => 0,
				'id'      => $item['owner-id'],
				'network' => $item['owner-network'],
				'url'     => $item['owner-link'],
				'alias'   => $item['owner-alias'],
			];
			$owner_url  = Contact::magicLinkByContact($owner);
			$owner_name = $item['owner-name'];
		}

		$edited = false;
		if (strtotime((string) ($item['edited'] ?? '')) - strtotime((string) ($item['created'] ?? '')) > 1) {
			$edited = [
				'label'    => $this->l10n->t('This entry was edited'),
				'date'     => !empty($item['edited']) ? $this->l10n->fullDateTime($item['edited']) : '',
				'relative' => !empty($item['edited']) ? $this->l10n->relativeDateTime($item['edited']) : '',
			];
		}

		$buttons = [
			'like'     => null,
			'dislike'  => null,
			'share'    => null,
			'announce' => null,
		];
		$pinned        = '';
		$pin           = false;
		$star          = false;
		$ignore_thread = false;
		if ($threadLevel === 1 && $this->uid) {
			$ignored = Post\ThreadUser::getIgnored($item['uri-id'] ?? 0, $this->uid);
			if ($ignored || $item['mention']) {
				$ignore_thread = [
					'do'        => $this->l10n->t('Turn off related notifications'),
					'undo'      => $this->l10n->t('Turn on related notifications'),
					'toggle'    => $this->l10n->t('Toggle notifications for this post'),
					'classdo'   => $ignored ? 'hidden' : '',
					'classundo' => $ignored ? '' : 'hidden',
					'ignored'   => $this->l10n->t('Notifications turned off for this post'),
				];
			}
		}

		$ispinned  = 'unpinned';
		$isstarred = 'unstarred';
		$indent    = ($threadLevel > 1 ? 'comment' : '');
		$shiny     = '';
		$osparkle  = '';

		$privacy   = $this->fetchPrivacy($item);
		$lock      = (($item['private'] ?? ItemModel::PUBLIC) === ItemModel::PRIVATE) ? $privacy : false;
		$connector = !in_array($item['network'] ?? '', Protocol::NATIVE_SUPPORT) && (($item['protocol'] ?? '') !== \Friendica\Model\Conversation::PARCEL_JETSTREAM)
			? $this->l10n->t('Connector Message')
			: false;

		$permissions  = $this->determineActionPermissions($item, $profileOwner);
		$shareable    = $permissions['shareable'];
		$announceable = $permissions['announceable'];
		$commentable  = $permissions['commentable'];
		$likeable     = $permissions['likeable'];

		$edpost = false;
		if ($this->uid && $item['origin']) {
			if (!empty($item['event-id'])) {
				$edpost = ['calendar/event/edit/' . $item['event-id'], $this->l10n->t('Edit event')];
			} else {
				$edpost = [sprintf('post/%s/edit', $item['id'] ?? 0), $this->l10n->t('Edit post')];
			}
		}
		if (($item['uid'] ?? 0) === 0) {
			$edpost = false;
		}

		if (!empty($item['featured'])) {
			$pinned = $this->l10n->t('Pinned to your wall');
		}

		$moderationButtons = $this->buildModerationButtons($item);
		$drop              = $moderationButtons['drop'];
		$block             = $moderationButtons['block'];
		$ignore            = $moderationButtons['ignore'];
		$collapse          = $moderationButtons['collapse'];
		$report            = $moderationButtons['report'];
		$ignoreServer      = $moderationButtons['ignoreServer'];

		$filer = $this->uid ? $this->l10n->t('Save to folder') : false;

		$isstarred = (($item['starred'] ?? false) ? 'starred' : 'unstarred');
		$star      = [
			'do'        => $this->l10n->t('Bookmark'),
			'undo'      => $this->l10n->t('Remove bookmark'),
			'classdo'   => !empty($item['starred']) ? 'hidden' : '',
			'classundo' => !empty($item['starred']) ? '' : 'hidden',
			'starred'   => $this->l10n->t('Starred'),
		];

		$tagger = '';
		if ($this->uid && $profileOwner === $this->uid && !empty($item['uid'])) {
			$tagger = [
				'add'   => $this->l10n->t('Add tag to post'),
				'class' => '',
			];
		}

		if (!in_array($item['network'] ?? '', [Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::DIASPORA])) {
			$tagger = '';
		}

		$comment_html          = '';
		$remote_comment_output = '';
		if (!$this->uid && ($item['network'] ?? '') !== Protocol::DIASPORA && $this->remote_comment) {
			$remote_comment_output = [
				$this->l10n->t('Comment this item on your system'),
				$this->l10n->t('Remote comment'),
				str_replace('{uri}', urlencode((string) ($item['uri'] ?? '')), $this->remote_comment),
			];
			$buttons = [];
		} elseif ($commentable) {
			$comment_html = $this->getCommentBox($item, $writable, $profileOwner);
		}

		if (strcmp(DateTimeFormat::utc($item['created'] ?? ''), DateTimeFormat::utc('now - 12 hours')) > 0) {
			$shiny = 'shiny';
		}

		$temporalData = $this->buildTemporalData($item);
		$direction    = $temporalData['direction'];
		$ago          = $temporalData['ago'];

		// process action responses - e.g. like/dislike/attend/agree/whatever
		$eventData = $this->buildEventData($item, $writable);
		$isevent   = $eventData['isevent'];
		$attend    = $eventData['attend'];

		$reactionData = $this->buildReactionData($item, $convResponses);
		$emojis       = $reactionData['emojis'];
		$reactions    = $reactionData['reactions'];
		$responses    = $reactionData['responses'];

		$actionButtons = $this->buildActionButtons($item, $writable, $likeable, $shareable, $announceable);
		$buttons       = $actionButtons['buttons'];
		$hide_dislike  = $actionButtons['hide_dislike'];

		$locationData  = $this->buildLocationData($item);
		$location_html = $locationData['location_html'];

		$tmpItem = [
			'template'               => 'wall_thread.tpl',
			'type'                   => implode('', array_slice(explode('/', (string) ($item['verb'] ?? '')), -1)),
			'comment_firstcollapsed' => false,
			'comment_lastcollapsed'  => false,
			'suppress_tags'          => $this->config->get('system', 'suppress_tags'),
			'tags'                   => $tags['tags'],
			'hashtags'               => $tags['hashtags'],
			'mentions'               => $tags['mentions'],
			'implicit_mentions'      => $tags['implicit_mentions'],
			'txt_cats'               => $this->l10n->t('Categories:'),
			'txt_folders'            => $this->l10n->t('Filed under:'),
			'has_cats'               => (count($categories) ? 'true' : ''),
			'has_folders'            => (count($folders) ? 'true' : ''),
			'categories'             => $categories,
			'folders'                => $folders,
			'body_html'              => $bodyHtml,
			'text'                   => strip_tags($bodyHtml),
			'id'                     => (int) $item['id'],
			'guid'                   => urlencode((string) $item['guid']),
			'isevent'                => $isevent,
			'attend'                 => $attend,
			'linktitle'              => $this->l10n->t('View %s\'s profile @ %s', $profileName, $item['author-link'] ?? ''),
			'olinktitle'             => $this->l10n->t('View %s\'s profile @ %s', $owner_name, $item['owner-link'] ?? ''),
			'to'                     => $this->l10n->t('to'),
			'via'                    => $this->l10n->t('via'),
			'wall'                   => $this->l10n->t('Wall-to-Wall'),
			'vwall'                  => $this->l10n->t('via Wall-To-Wall:'),
			'profile_url'            => $profileUrl,
			'name'                   => $profileName,
			'item_photo_menu_html'   => $this->item->photoMenu($item, $formSecurityToken),
			'thumb'                  => $this->baseURL->remove($this->item->getAuthorAvatar($item)),
			'osparkle'               => $osparkle,
			'sparkle'                => $sparkle,
			'title'                  => $item['title']           ?? '',
			'summary'                => $item['content-warning'] ?? '',
			'localtime'              => !empty($item['created']) ? $this->l10n->fullDateTime($item['created']) : '',
			'utc'                    => !empty($item['created']) ? DateTimeFormat::utc($item['created']) : '',
			'ago'                    => $item['app'] ? $this->l10n->t('%s from %s', $ago, $item['app']) : $ago,
			'app'                    => $item['app'] ?? '',
			'created'                => $ago,
			'lock'                   => $lock,
			'private'                => $item['private'] ?? ItemModel::PUBLIC,
			'privacy'                => $privacy,
			'connector'              => $connector,
			'location_html'          => $location_html,
			'indent'                 => $indent,
			'shiny'                  => $shiny,
			'owner_self'             => $item['origin'],
			'owner_url'              => $owner_url,
			'owner_photo'            => $this->baseURL->remove($this->item->getOwnerAvatar($item)),
			'owner_name'             => $owner_name,
			'plink'                  => ItemModel::getPlink($item),
			'browsershare'           => $browsershare,
			'edpost'                 => $edpost,
			'ispinned'               => $ispinned,
			'pin'                    => $pin,
			'pinned'                 => $pinned,
			'isstarred'              => $isstarred,
			'star'                   => $star,
			'ignore'                 => $ignore_thread,
			'tagger'                 => $tagger,
			'filer'                  => $filer,
			'language'               => $languages,
			'lang'                   => $language,
			'searchtext'             => $this->l10n->t('Raw content'),
			'drop'                   => $drop,
			'block'                  => $block,
			'ignore_author'          => $ignore,
			'collapse'               => $collapse,
			'report'                 => $report,
			'ignore_server'          => $ignoreServer,
			'vote'                   => $buttons,
			'like_html'              => $responses['like']['output'],
			'dislike_html'           => $responses['dislike']['output'],
			'hide_dislike'           => $hide_dislike,
			'emojis'                 => $emojis,
			'missing'                => $item['missing']  ?? 0,
			'existing'               => $item['existing'] ?? [],
			'existing_json'          => !empty($item['existing']) ? json_encode($item['existing']) : '[]',
			'load_more_comments'     => $this->l10n->t('Load more comments'),
			'quoteshares'            => $this->getQuoteShares($item['quoteshares'] ?? []),
			'reactions'              => $reactions,
			'responses'              => $responses,
			'legacy_activities'      => $this->config->get('system', 'legacy_activities'),
			'switchcomment'          => $this->l10n->t('Comment'),
			'reply_label'            => $this->l10n->t('Reply to %s', $profileName),
			'comment_html'           => $comment_html,
			'remote_comment'         => $remote_comment_output,
			'menu'                   => $this->l10n->t('More'),
			'previewing'             => $preview ? ' preview ' : '',
			'wait'                   => $this->l10n->t('Please wait'),
			'loading'                => $this->l10n->t('Loading ...'),
			'thread_level'           => $threadLevel,
			'edited'                 => $edited,
			'author_gsid'            => $item['author-gsid'] ?? 0,
			'network'                => $item['network']     ?? '',
			'network_name'           => ContactSelector::networkToName($item['author-network'] ?? '', $item['network'] ?? '', $item['author-gsid'] ?? 0),
			'network_svg'            => ContactSelector::networkToSVG($item['network'] ?? '', $item['author-gsid'] ?? 0, '', $this->uid),
			'received'               => $item['received']  ?? '',
			'commented'              => $item['commented'] ?? '',
			'created_date'           => $item['created']   ?? '',
			'uriid'                  => $item['uri-id'],
			'return'                 => $this->arguments->getCommand() ? bin2hex($this->arguments->getCommand()) : '',
			'direction'              => $direction,
			'reshared'               => $item['reshared'] ?? '',
			'delivery'               => [
				'queue_count'       => $item['delivery_queue_count'] ?? 0,
				'queue_done'        => ($item['delivery_queue_done'] ?? 0) + ($item['delivery_queue_failed'] ?? 0),
				'notifier_pending'  => $this->l10n->t('Notifier task is pending'),
				'delivery_pending'  => $this->l10n->t('Delivery to remote servers is pending'),
				'delivery_underway' => $this->l10n->t('Delivery to remote servers is underway'),
				'delivery_almost'   => $this->l10n->t('Delivery to remote servers is mostly done'),
				'delivery_done'     => $this->l10n->t('Delivery to remote servers is done'),
			],
			'children'           => [],
			'total_comments_num' => 0,
			'toplevel'           => ($threadLevel === 1 ? 'toplevel_item' : ''),
			'flatten'            => false,
			'threaded'           => true,
			'parentguid'         => $parent_guid,
			'inreplyto'          => $parent_username ? $this->l10n->t('in reply to %s', $parent_username) : '',
			'isunknown'          => $parent_unknown,
			'isunknown_label'    => $this->l10n->t('Parent is probably private or not federated.'),
			'show_text'          => $this->l10n->t('Show comments'),
			'hide_text'          => $this->l10n->t('Close comments'),
		];

		$arr    = ['item' => $item, 'output' => $tmpItem];
		$arr    = $this->eventDispatcher->dispatch(new ArrayFilterEvent(ArrayFilterEvent::DISPLAY_ITEM, $arr))->getArray();
		$result = $arr['output'];

		$children = [];
		if (!empty($item['children']) && is_array($item['children'])) {
			$nextThreadParents                  = $threadParents;
			$nextThreadParents[$item['uri-id']] = ['guid' => $item['guid'], 'name' => $item['author-name'] ?? ''];

			foreach ($item['children'] as $child) {
				$nextThreadParents[$child['uri-id']] = ['guid' => $child['guid'] ?? '', 'name' => $child['author-name'] ?? ''];
				$childData                           = $this->buildThreadTemplateData($child, $preview, $writable, $profileOwner, $convResponses, $formSecurityToken, $threadLevel + 1, $nextThreadParents);
				if ($childData !== null) {
					$children[] = $childData;
				}
			}
			$nb_children = count($children);
			if ((($nb_children > 2) || ($threadLevel > 1)) && isset($children[0])) {
				$children[0]['comment_firstcollapsed'] = true;
				$children[0]['num_comments']           = $this->l10n->tt('%d comment', '%d comments', $item['counts'] ?? 0);
				$children[0]['show_text']              = $this->l10n->t('Show more');
				$children[0]['hide_text']              = $this->l10n->t('Show fewer');
				if ($threadLevel > 1) {
					$children[$nb_children - 1]['comment_lastcollapsed'] = true;
				} else {
					$children[$nb_children - 3]['comment_lastcollapsed'] = true;
				}
			}
		}

		$result['children']           = $children;
		$result['total_comments_num'] = $threadLevel === 1 ? $this->countDescendants($children) : 0;
		return $result;
	}

	/**
	 * Determines action permissions for the item.
	 *
	 * @param array<string, mixed> $item
	 * @param int $profileOwner
	 * @return array{shareable: bool, announceable: bool, commentable: bool, likeable: bool}
	 */
	private function determineActionPermissions(array $item, int $profileOwner): array
	{
		$shareable    = in_array($profileOwner, [0, $this->uid]) && ($item['private'] ?? ItemModel::PUBLIC) !== ItemModel::PRIVATE;
		$announceable = $shareable && in_array($item['network'] ?? '', [Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::DIASPORA, Protocol::TWITTER, Protocol::TUMBLR, Protocol::ATPROTO]);
		$commentable  = ($item['network'] ?? '') !== Protocol::TUMBLR;
		$likeable     = true;

		if ($commentable && $this->uid && !empty($item['author-id']) && Contact\User::isIsBlocked($item['author-id'], $this->uid)) {
			$commentable = false;
		}

		if ($announceable && ($item['network'] ?? '') === Protocol::DIASPORA && (($item['gravity'] ?? 0) !== ItemModel::GRAVITY_PARENT)) {
			$announceable = false;
		}

		if (!empty($item['restrictions']) && ($item['restrictions'] & ItemModel::CANT_REPLY)) {
			$commentable = false;
		}
		if (!empty($item['restrictions']) && ($item['restrictions'] & ItemModel::CANT_LIKE)) {
			$likeable = false;
		}
		if (!empty($item['restrictions']) && ($item['restrictions'] & ItemModel::CANT_ANNOUNCE)) {
			$announceable = false;
		}
		if (!empty($item['restrictions']) && ($item['restrictions'] & ItemModel::CANT_QUOTE)) {
			$shareable = false;
		}

		return ['shareable' => $shareable, 'announceable' => $announceable, 'commentable' => $commentable, 'likeable' => $likeable];
	}

	/**
	 * Builds moderation button data.
	 *
	 * @param array<string, mixed> $item
	 * @return array{drop: array|false, block: array|false, ignore: array|false, collapse: array|false, report: array|false, ignoreServer: array|false, dropping: bool}
	 */
	private function buildModerationButtons(array $item): array
	{
		$drop         = false;
		$block        = false;
		$ignore       = false;
		$collapse     = false;
		$report       = false;
		$ignoreServer = false;

		$origin   = !empty($item['origin']) || !empty($item['parent-origin']);
		$dropping = false;

		if ($this->uid) {
			$dropping = in_array($item['uid'] ?? 0, [0, $this->uid]);
			$drop     = [
				'dropping' => $dropping,
				'pagedrop' => !empty($item['pagedrop']),
				'select'   => $this->l10n->t('Select'),
				'label'    => $origin ? $this->l10n->t('Delete globally') : $this->l10n->t('Remove locally'),
			];
		}

		if (empty($item['self']) && $this->uid) {
			$block = [
				'blocking'  => true,
				'label'     => $this->l10n->t('Block %s', $item['author-name'] ?? ''),
				'author_id' => $item['author-id'] ?? 0,
			];
			$ignore = [
				'ignoring'  => true,
				'label'     => $this->l10n->t('Ignore %s', $item['author-name'] ?? ''),
				'author_id' => $item['author-id'] ?? 0,
			];
			$collapse = [
				'collapsing' => true,
				'label'      => $this->l10n->t('Collapse %s', $item['author-name'] ?? ''),
				'author_id'  => $item['author-id'] ?? 0,
			];
			$report = [
				'label' => $this->l10n->t('Report this post'),
				'href'  => 'moderation/report/create?' . http_build_query([
					'cid'     => $item['author-id'] ?? 0,
					'uri-ids' => [$item['uri-id'] ?? ''],
					'return'  => $this->arguments->getQueryString(),
				]),
			];

			$authorBaseUri = new Uri($item['author-baseurl'] ?? '');
			if ($authorBaseUri->getHost() && !$this->baseURL->isLocalUrl((string) $authorBaseUri)) {
				$ignoreServer = [
					'label' => $this->l10n->t('Ignore %s server', $authorBaseUri->getHost()),
				];
			}
		}

		return ['drop' => $drop, 'block' => $block, 'ignore' => $ignore, 'collapse' => $collapse, 'report' => $report, 'ignoreServer' => $ignoreServer, 'dropping' => $dropping];
	}

	/**
	 * Builds event-specific data (isevent, attend).
	 *
	 * @param array<string, mixed> $item
	 * @param bool $writable
	 * @return array{isevent: bool, attend: array}
	 */
	private function buildEventData(array $item, bool $writable): array
	{
		$isevent = false;
		$attend  = [];

		if (($item['object-type'] ?? '') === Activity\ObjectType::EVENT
			&& in_array($item['network'] ?? '', [Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::DIASPORA])) {
			if ($writable) {
				$isevent = true;
				$attend  = [
					$this->l10n->t('I will attend'),
					$this->l10n->t('I will not attend'),
					$this->l10n->t('I might attend'),
				];
			}
		}

		return ['isevent' => $isevent, 'attend' => $attend];
	}

	/**
	 * Builds temporal data (direction, ago, ago_received).
	 *
	 * @param array<string, mixed> $item
	 * @return array{direction: array, ago: string}
	 */
	private function buildTemporalData(array $item): array
	{
		$direction = [];
		if (!empty($item['direction'])) {
			$direction = $item['direction'];
		}

		$ago          = !empty($item['created']) ? $this->l10n->relativeDateTime($item['created']) : '';
		$ago_received = !empty($item['received']) ? $this->l10n->relativeDateTime($item['received']) : '';
		if ($this->config->get('system', 'show_received') && !empty($item['created']) && !empty($item['received']) && (abs(strtotime((string) $item['created']) - strtotime((string) $item['received'])) > $this->config->get('system', 'show_received_seconds')) && ($ago !== $ago_received)) {
			$ago = $this->l10n->t('%s (Received %s)', $ago, $ago_received);
		}

		return ['direction' => $direction, 'ago' => $ago];
	}

	/**
	 * Builds location data.
	 *
	 * @param array<string, mixed> $item
	 * @return array{location_html: string}
	 */
	private function buildLocationData(array $item): array
	{
		$locate        = ['location' => $item['location'] ?? '', 'coord' => $item['coord'] ?? '', 'html' => ''];
		$locate        = $this->eventDispatcher->dispatch(new ArrayFilterEvent(ArrayFilterEvent::RENDER_LOCATION, $locate))->getArray();
		$location_html = $locate['html'] ?: Strings::escapeHtml($locate['location'] ?: $locate['coord'] ?: '');

		return ['location_html' => $location_html];
	}

	/**
	 * Builds reaction and response data.
	 *
	 * @param array<string, mixed> $item
	 * @param array $convResponses
	 * @return array{emojis: array, reactions: array, responses: array}
	 */
	private function buildReactionData(array $item, array $convResponses): array
	{
		$response_verbs = ['like', 'dislike', 'announce', 'comment'];

		$isevent = ($item['object-type'] ?? '') === Activity\ObjectType::EVENT;
		if ($isevent) {
			$response_verbs[] = 'attendyes';
			$response_verbs[] = 'attendno';
			$response_verbs[] = 'attendmaybe';
		}

		$emojis = $this->getEmojis($item);
		$verbs  = [
			'like'        => Activity::LIKE,
			'dislike'     => Activity::DISLIKE,
			'announce'    => Activity::ANNOUNCE,
			'comment'     => Activity::POST,
			'attendyes'   => Activity::ATTEND,
			'attendno'    => Activity::ATTENDNO,
			'attendmaybe' => Activity::ATTENDMAYBE,
		];

		$reactions = $emojis;
		$responses = [];
		foreach ($response_verbs as $verb) {
			$responses[$verb] = [
				'self'   => $convResponses[$verb][$item['uri-id']]['self'] ?? 0,
				'output' => !empty($convResponses[$verb][$item['uri-id']]) ? $this->activityFormatter->formatActivity($convResponses[$verb][$item['uri-id']]['links'], $verb, $item['uri-id'], $verbs[$verb], $emojis) : '',
				'total'  => $emojis[$verbs[$verb]]['total'] ?? '',
				'title'  => $emojis[$verbs[$verb]]['title'] ?? '',
			];
			unset($reactions[$verbs[$verb]]);
		}

		unset($reactions[Activity::POST]);

		return ['emojis' => $emojis, 'reactions' => $reactions, 'responses' => $responses];
	}

	/**
	 * Builds action buttons (like, dislike, share, announce).
	 *
	 * @param array<string, mixed> $item
	 * @param bool $writable
	 * @param bool $likeable
	 * @param bool $shareable
	 * @param bool $announceable
	 * @return array{buttons: array, hide_dislike: bool}
	 */
	private function buildActionButtons(array $item, bool $writable, bool $likeable, bool $shareable, bool $announceable): array
	{
		$buttons = [
			'like'     => null,
			'dislike'  => null,
			'share'    => null,
			'announce' => null,
		];

		if ($writable) {
			if ($likeable) {
				$buttons['like']    = [$this->l10n->t("I like this (toggle)"), $this->l10n->t('Like')];
				$buttons['dislike'] = [$this->l10n->t("I don't like this (toggle)"), $this->l10n->t('Dislike')];
			}
			if ($shareable) {
				$buttons['share'] = [$this->l10n->t('Quote share this'), $this->l10n->t('Quote Share')];
			}
			if ($announceable) {
				$buttons['announce']   = [$this->l10n->t('Reshare this'), $this->l10n->t('Reshare')];
				$buttons['unannounce'] = [$this->l10n->t('Cancel your Reshare'), $this->l10n->t('Unshare')];
			}
		}

		$hide_dislike = $this->uid ? $this->pConfig->get($this->uid, 'system', 'hide_dislike') : false;

		if ($hide_dislike) {
			$buttons['dislike'] = false;
		}

		if (!in_array($item['network'] ?? '', [Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::DIASPORA])) {
			if ($buttons['dislike']) {
				$buttons['dislike'] = false;
			}
		}

		if ($buttons['like'] && in_array($item['network'] ?? '', [Protocol::FEED, Protocol::MAIL])) {
			$buttons['like'] = false;
		}

		return ['buttons' => $buttons, 'hide_dislike' => $hide_dislike];
	}

	/**
	 * Fetch privacy label
	 *
	 * @param array<string, mixed> $item
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	private function fetchPrivacy(array $item): string
	{
		return match ($item['private'] ?? ItemModel::PUBLIC) {
			ItemModel::PRIVATE  => $this->l10n->t('Private Message'),
			ItemModel::PUBLIC   => $this->l10n->t('Public Message'),
			ItemModel::UNLISTED => $this->l10n->t('Unlisted Message'),
			default             => throw new \InvalidArgumentException('Item privacy ' . ($item['private'] ?? 'unknown') . ' is unsupported'),
		};
	}

	/**
	 * Fetch emojis
	 *
	 * @param array $item
	 * @return array
	 */
	private function getEmojis(array $item): array
	{
		if (empty($item['emojis'])) {
			return [];
		}

		$emojis = [];
		foreach ($item['emojis'] as $index => $element) {
			$key    = $element['verb'];
			$actors = implode(', ', $element['title']);
			switch ($element['verb']) {
				case Activity::ANNOUNCE:
					$title = $this->l10n->t('Reshared by: %s', $actors);
					$icon  = ['fa' => 'ri-repeat-line', 'icon' => 'icon-retweet'];
					break;

				case Activity::VIEW:
					$title = $this->l10n->t('Viewed by: %s', $actors);
					$icon  = ['fa' => 'ri-eye-line', 'icon' => 'icon-eye-open'];
					break;

				case Activity::READ:
					$title = $this->l10n->t('Read by: %s', $actors);
					$icon  = ['fa' => 'ri-book-line', 'icon' => 'icon-book'];
					break;

				case Activity::LIKE:
					$title = $this->l10n->t('Liked by: %s', $actors);
					$icon  = ['fa' => 'ri-thumb-up-line', 'icon' => 'icon-thumbs-up'];
					break;

				case Activity::DISLIKE:
					$title = $this->l10n->t('Disliked by: %s', $actors);
					$icon  = ['fa' => 'ri-thumb-down-line', 'icon' => 'icon-thumbs-down'];
					break;

				case Activity::ATTEND:
					$title = $this->l10n->t('Attended by: %s', $actors);
					$icon  = ['fa' => 'ri-check-line', 'icon' => 'icon-ok'];
					break;

				case Activity::ATTENDMAYBE:
					$title = $this->l10n->t('Maybe attended by: %s', $actors);
					$icon  = ['fa' => 'ri-question-line', 'icon' => 'icon-question'];
					break;

				case Activity::ATTENDNO:
					$title = $this->l10n->t('Not attended by: %s', $actors);
					$icon  = ['fa' => 'ri-close-line', 'icon' => 'icon-remove'];
					break;

				case Activity::POST:
					$title = $this->l10n->t('Commented by: %s', $actors);
					$icon  = ['fa' => 'ri-chat-3-line', 'icon' => 'icon-commenting'];
					break;

				default:
					$title = $this->l10n->t('Reacted with %s by: %s', $element['emoji'], $actors);
					$icon  = [];
					$key   = $element['emoji'];
					break;
			}
			$emojis[$key] = ['emoji' => $element['emoji'], 'total' => $element['total'], 'title' => $title, 'icon' => $icon];
		}

		return $emojis;
	}

	/**
	 * Fetch quote shares
	 *
	 * @param array $quoteshares
	 * @return array
	 */
	private function getQuoteShares(array $quoteshares): array
	{
		if (empty($quoteshares)) {
			return [];
		}

		return ['total' => $quoteshares['total'] ?? 0, 'title' => $this->l10n->t('Quote shared by: %s', implode(', ', $quoteshares['title'] ?? []))];
	}

	/**
	 * Get default text for comment box
	 *
	 * @param array<string, mixed> $item
	 * @return string
	 */
	private function getDefaultText(array $item): string
	{
		if (!$this->uid) {
			return '';
		}

		$owner = User::getOwnerDataById($this->uid);
		$text  = '';

		if (!empty($item['content-warning']) && Feature::isEnabled($this->uid, Feature::ADD_ABSTRACT)) {
			$text = '[abstract=' . Protocol::ACTIVITYPUB . ']' . $item['content-warning'] . "[/abstract]\n";
		}

		if (!Feature::isEnabled($this->uid, Feature::EXPLICIT_MENTIONS)) {
			return $text;
		}

		if (!empty($item['author-addr']) && ($item['author-addr'] !== ($owner['addr'] ?? '')) && (($item['gravity'] ?? 0) !== ItemModel::GRAVITY_PARENT || !in_array($item['network'] ?? '', [Protocol::DIASPORA]))) {
			$text .= '@' . $item['author-addr'] . ' ';
		}

		$terms = Tag::getByURIId((int) ($item['uri-id'] ?? 0), [Tag::MENTION, Tag::IMPLICIT_MENTION, Tag::EXCLUSIVE_MENTION]);
		foreach ($terms as $term) {
			if (empty($term['url'])) {
				$this->logger->warning('Mention term with no URL', ['term' => $term]);
				continue;
			}

			$profile = Contact::getByURL($term['url'], false, ['addr', 'contact-type']);
			if (!empty($profile['addr']) && (($profile['contact-type'] ?? Contact::TYPE_UNKNOWN) !== Contact::TYPE_COMMUNITY)
				&& ($profile['addr'] !== ($owner['addr'] ?? '')) && !strstr($text, (string) $profile['addr'])) {
				$text .= '@' . $profile['addr'] . ' ';
			}
		}

		return $text;
	}

	/**
	 * Get comment box HTML
	 *
	 * @param array<string, mixed> $item
	 * @param bool $writable
	 * @param int $profileOwner
	 * @return string
	 */
	private function getCommentBox(array $item, bool $writable, int $profileOwner): string
	{
		if (!$this->uid || !$writable) {
			return '';
		}

		$qcomment = null;
		if ($this->addonHelper->isAddonEnabled('qcomment')) {
			$words    = $this->pConfig->get($this->uid, 'qcomment', 'words');
			$qcomment = $words ? explode("\n", $words) : [];
		}

		$uid = $profileOwner;
		if (!empty($item['uid']) && $uid !== $item['uid']) {
			$uid = $item['uid'];
		}

		$owner    = User::getOwnerDataById($this->uid);
		$default  = $this->getDefaultText($item);
		$template = \Friendica\Core\Renderer::getMarkupTemplate('comment_item.tpl');

		return \Friendica\Core\Renderer::replaceMacros($template, [
			'$default'     => $default,
			'$return_path' => $this->arguments->getQueryString(),
			'$threaded'    => true,
			'$jsreload'    => '',
			'$id'          => $item['id'] ?? 0,
			'$parent'      => $item['id'] ?? 0,
			'$qcomment'    => $qcomment,
			'$profile_uid' => $uid,
			'$mylink'      => $this->baseURL->remove($owner['url'] ?? ''),
			'$mytitle'     => $this->l10n->t('This is you'),
			'$myphoto'     => $this->baseURL->remove($owner['thumb'] ?? ''),
			'$comment'     => $this->l10n->t('Comment'),
			'$submit'      => $this->l10n->t('Post comment'),
			'$loading'     => $this->l10n->t('Loading...'),
			'$edbold'      => $this->l10n->t('Bold'),
			'$editalic'    => $this->l10n->t('Italic'),
			'$eduline'     => $this->l10n->t('Underline'),
			'$contentwarn' => $this->l10n->t('Content Warning'),
			'$edquote'     => $this->l10n->t('Quote'),
			'$edemojis'    => $this->l10n->t('Add emojis'),
			'$edcode'      => $this->l10n->t('Code'),
			'$edimg'       => $this->l10n->t('Image'),
			'$edurl'       => $this->l10n->t('Link'),
			'$edattach'    => $this->l10n->t('Link or Media'),
			'$prompttext'  => $this->l10n->t('Please enter a image/video/audio/webpage URL:'),
			'$preview'     => $this->l10n->t('Preview'),
			'$rand_num'    => \Friendica\Util\Crypto::randomDigits(12),
		]);
	}

	/**
	 * Count descendants recursively
	 *
	 * @param array<int, array<string, mixed>> $children
	 */
	private function countDescendants(array $children): int
	{
		$total = count($children);
		foreach ($children as $child) {
			if (!empty($child['children']) && is_array($child['children'])) {
				$total += $this->countDescendants($child['children']);
			}
		}

		return $total;
	}
}
