<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Settings;

use Friendica\App;
use Friendica\Content\Conversation\Factory;
use Friendica\Content\Conversation\Repository\UserDefinedChannel;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Model\Circle;
use Friendica\Model\User;
use Friendica\Module\BaseSettings;
use Friendica\Module\Response;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use Friendica\Util\Strings;
use Psr\Log\LoggerInterface;

class Channels extends BaseSettings
{
	/** @var UserDefinedChannel */
	private $channel;
	/** @var IManagePersonalConfigValues */
	private $pConfig;
	/** @var Factory\UserDefinedChannel */
	private $userDefinedChannel;
	/** @var IManageConfigValues */
	private $config;

	public function __construct(Factory\UserDefinedChannel $userDefinedChannel, UserDefinedChannel $channel, App\Page $page, IHandleUserSessions $session, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, IManagePersonalConfigValues $pConfig, IManageConfigValues $config, array $server, array $parameters = [])
	{
		parent::__construct($session, $page, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->userDefinedChannel = $userDefinedChannel;
		$this->channel            = $channel;
		$this->config             = $config;
		$this->pConfig            = $pConfig;
	}

	protected function post(array $request = [])
	{
		$uid = $this->session->getLocalUserId();
		if (!$uid) {
			throw new HTTPException\ForbiddenException($this->t('Permission denied.'));
		}

		if (empty($request['edit_channel']) && empty($request['add_channel'])) {
			return;
		}

		self::checkFormSecurityTokenRedirectOnError('/settings/channels', 'settings_channels');

		$channel_languages = User::getWantedLanguages($uid);

		if (!empty($request['add_channel'])) {
			if (!array_diff((array)$request['new_languages'], $channel_languages)) {
				$request['new_languages'] = null;
			}

			$channel = $this->userDefinedChannel->createFromTableRow([
				'label'            => $request['new_label'],
				'description'      => $request['new_description'],
				'access-key'       => substr(mb_strtolower($request['new_access_key']), 0, 1),
				'uid'              => $uid,
				'circle'           => (int)$request['new_circle'],
				'include-tags'     => Strings::cleanTags($request['new_include_tags']),
				'exclude-tags'     => Strings::cleanTags($request['new_exclude_tags']),
				'min-size'         => $request['new_min_size'] != '' ? (int)$request['new_min_size'] : null,
				'max-size'         => $request['new_max_size'] != '' ? (int)$request['new_max_size'] : null,
				'full-text-search' => $request['new_text_search'],
				'media-type'       => ($request['new_image'] ? 1 : 0) | ($request['new_video'] ? 2 : 0) | ($request['new_audio'] ? 4 : 0),
				'languages'        => $request['new_languages'],
			]);
			$saved = $this->channel->save($channel);
			$this->logger->debug('New channel added', ['saved' => $saved]);
			$this->enableTimeline($uid, $saved->code);
			return;
		}

		foreach (array_keys((array)$request['label']) as $id) {
			if ($request['delete'][$id]) {
				$success = $this->channel->deleteById($id, $uid);
				$this->logger->debug('Channel deleted', ['id' => $id, 'success' => $success]);
				continue;
			}

			if (!array_diff((array)$request['languages'][$id], $channel_languages) && (count((array)$request['languages'][$id]) == count($channel_languages))) {
				$request['languages'][$id] = null;
			}

			$channel = $this->userDefinedChannel->createFromTableRow([
				'id'               => $id,
				'label'            => $request['label'][$id],
				'description'      => $request['description'][$id],
				'access-key'       => substr(mb_strtolower($request['access_key'][$id]), 0, 1),
				'uid'              => $uid,
				'circle'           => (int)$request['circle'][$id],
				'include-tags'     => Strings::cleanTags($request['include_tags'][$id]),
				'exclude-tags'     => Strings::cleanTags($request['exclude_tags'][$id]),
				'min-size'         => $request['min_size'][$id] != '' ? (int)$request['min_size'][$id] : null,
				'max-size'         => $request['max_size'][$id] != '' ? (int)$request['max_size'][$id] : null,
				'full-text-search' => $request['text_search'][$id],
				'media-type'       => ($request['image'][$id] ? 1 : 0) | ($request['video'][$id] ? 2 : 0) | ($request['audio'][$id] ? 4 : 0),
				'languages'        => $request['languages'][$id],
				'publish'          => $request['publish'][$id] ?? false,
			]);
			$saved = $this->channel->save($channel);
			$this->logger->debug('Save channel', ['id' => $id, 'saved' => $saved]);
			$this->enableTimeline($uid, $id);
		}
	}

	protected function content(array $request = []): string
	{
		parent::content();

		$uid = $this->session->getLocalUserId();
		if (!$uid) {
			throw new HTTPException\ForbiddenException($this->t('Permission denied.'));
		}

		$user         = User::getById($uid, ['account-type']);
		$account_type = $user['account-type'];

		if (in_array($account_type, [User::ACCOUNT_TYPE_COMMUNITY, User::ACCOUNT_TYPE_RELAY])) {
			$intro   = $this->t('This page can be used to define the channels that will automatically be reshared by your account.');
			$circles = [
				0 => $this->l10n->t('Global Community')
			];
		} else {
			$intro   = $this->t('This page can be used to define your own channels.');
			$circles = [
				0  => $this->l10n->t('Global Community'),
				-5 => $this->l10n->t('Latest Activity'),
				-4 => $this->l10n->t('Latest Posts'),
				-3 => $this->l10n->t('Latest Creation'),
				-1 => $this->l10n->t('Following'),
				-2 => $this->l10n->t('Followers'),
			];
		}

		foreach (Circle::getByUserId($uid) as $circle) {
			$circles[$circle['id']] = $circle['name'];
		}

		$languages         = $this->l10n->getLanguageCodes(true);
		$channel_languages = User::getWantedLanguages($uid);

		$channels = [];
		foreach ($this->channel->selectByUid($uid) as $channel) {
			if (!empty($request['id'])) {
				$open = $channel->code == $request['id'];
			} elseif (!empty($request['new_label'])) {
				$open = $channel->label == $request['new_label'];
			} else {
				$open = false;
			}

			if ($this->config->get('system', 'allow_relay_channels') && in_array($account_type, [User::ACCOUNT_TYPE_COMMUNITY, User::ACCOUNT_TYPE_RELAY])) {
				$publish = ["publish[$channel->code]", $this->t("Publish"), $channel->publish, $this->t("When selected, the channel results are reshared. This only works for public ActivityPub posts from the public timeline or the user defined circles.")];
			} else {
				$publish = null;
			}

			$channels[] = [
				'id'           => $channel->code,
				'open'         => $open,
				'label'        => ["label[$channel->code]", $this->t('Label'), $channel->label, '', $this->t('Required')],
				'description'  => ["description[$channel->code]", $this->t("Description"), $channel->description],
				'access_key'   => ["access_key[$channel->code]", $this->t("Access Key"), $channel->accessKey],
				'circle'       => ["circle[$channel->code]", $this->t('Circle/Channel'), $channel->circle, '', $circles],
				'include_tags' => ["include_tags[$channel->code]", $this->t("Include Tags"), str_replace(',', ', ', $channel->includeTags)],
				'exclude_tags' => ["exclude_tags[$channel->code]", $this->t("Exclude Tags"), str_replace(',', ', ', $channel->excludeTags)],
				'min_size'     => ["min_size[$channel->code]", $this->t("Minimum Size"), $channel->minSize],
				'max_size'     => ["max_size[$channel->code]", $this->t("Maximum Size"), $channel->maxSize],
				'text_search'  => ["text_search[$channel->code]", $this->t("Full Text Search"), $channel->fullTextSearch],
				'image'        => ["image[$channel->code]", $this->t("Images"), $channel->mediaType & 1],
				'video'        => ["video[$channel->code]", $this->t("Videos"), $channel->mediaType & 2],
				'audio'        => ["audio[$channel->code]", $this->t("Audio"), $channel->mediaType & 4],
				'languages'    => ["languages[$channel->code][]", $this->t('Languages'), $channel->languages ?? $channel_languages, $this->t('Select all languages that you want to see in this channel. "Unspecified" describes all posts for which no language information was detected (e.g. posts with just an image or too little text to be sure of the language). If you want to see all languages, you will need to select all items in the list.'), $languages, 'multiple'],
				'publish'      => $publish,
				'delete'       => ["delete[$channel->code]", $this->t("Delete channel") . ' (' . $channel->label . ')', false, $this->t("Check to delete this entry from the channel list")]
			];
		}

		$t = Renderer::getMarkupTemplate('settings/channels.tpl');
		return Renderer::replaceMacros($t, [
			'open'         => count($channels) == 0,
			'label'        => ["new_label", $this->t('Label'), '', $this->t('Short name for the channel. It is displayed on the channels widget.'), $this->t('Required')],
			'description'  => ["new_description", $this->t("Description"), '', $this->t('This should describe the content of the channel in a few word.')],
			'access_key'   => ["new_access_key", $this->t("Access Key"), '', $this->t('When you want to access this channel via an access key, you can define it here. Pay attention to not use an already used one.')],
			'circle'       => ['new_circle', $this->t('Circle/Channel'), 0, $this->t('Select a circle or channel, that your channel should be based on.'), $circles],
			'include_tags' => ["new_include_tags", $this->t("Include Tags"), '', $this->t('Comma separated list of tags. A post will be used when it contains any of the listed tags.')],
			'exclude_tags' => ["new_exclude_tags", $this->t("Exclude Tags"), '', $this->t('Comma separated list of tags. If a post contain any of these tags, then it will not be part of nthis channel.')],
			'min_size'     => ["new_min_size", $this->t("Minimum Size"), '', $this->t('Minimum post size. Leave empty for no minimum size. The size is calculated without links, attached posts, mentions or hashtags.')],
			'max_size'     => ["new_max_size", $this->t("Maximum Size"), '', $this->t('Maximum post size. Leave empty for no maximum size. The size is calculated without links, attached posts, mentions or hashtags.')],
			'text_search'  => ["new_text_search", $this->t("Full Text Search"), '', $this->t('Search terms for the body, supports the "boolean mode" operators from MariaDB. See the help for a complete list of operators and additional keywords: %s', '<a href="help/Channels">help/Channels</a>')],
			'image'        => ['new_image', $this->t("Images"), false, $this->t("Check to display images in the channel.")],
			'video'        => ["new_video", $this->t("Videos"), false, $this->t("Check to display videos in the channel.")],
			'audio'        => ["new_audio", $this->t("Audio"), false, $this->t("Check to display audio in the channel.")],
			'languages'    => ["new_languages[]", $this->t('Languages'), $channel_languages, $this->t('Select all languages that you want to see in this channel.'), $languages, 'multiple'],
			'$l10n'        => [
				'title'          => $this->t('Channels'),
				'intro'          => $intro,
				'addtitle'       => $this->t('Add new entry to the channel list'),
				'addsubmit'      => $this->t('Add'),
				'savechanges'    => $this->t('Save'),
				'currenttitle'   => $this->t('Current Entries in the channel list'),
				'thurl'          => $this->t('Blocked server domain pattern'),
				'threason'       => $this->t('Reason for the block'),
				'delentry'       => $this->t('Delete entry from the channel list'),
				'confirm_delete' => $this->t('Delete entry from the channel list?'),
			],
			'$entries' => $channels,

			'$form_security_token' => self::getFormSecurityToken('settings_channels'),
		]);
	}

	private function enableTimeline(int $uid, int $id)
	{
		$bookmarked_timelines = $this->pConfig->get($uid, 'system', 'network_timelines');
		$enabled_timelines    = $this->pConfig->get($uid, 'system', 'enabled_timelines');

		if (empty($enabled_timelines) || empty($bookmarked_timelines)) {
			return;
		}

		if (in_array($id, $enabled_timelines) || in_array($id, $bookmarked_timelines)) {
			return;
		}

		$enabled_timelines[] = $id;
		$this->pConfig->set($uid, 'system', 'enabled_timelines', $enabled_timelines);
	}
}
