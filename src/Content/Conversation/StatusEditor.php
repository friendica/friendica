<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Content\Conversation;

use Friendica\App\Arguments;
use Friendica\App\Mode;
use Friendica\App\Page;
use Friendica\Content\Feature;
use Friendica\Core\ACL;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Event\HtmlFilterEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Friendica\Model\Item as ItemModel;
use Friendica\Model\User;
use Friendica\Util\Crypto;
use Friendica\Util\Profiler;
use Friendica\Util\Temporal;
use Friendica\Core\Theme;

/**
 * Renders the status editor (JOT - Just One Thought) form.
 * This class handles the rendering of the post creation/editing form.
 */
final class StatusEditor
{
	private bool $assetsRegistered = false;

	public function __construct(
		private readonly L10n $l10n,
		private readonly IManageConfigValues $config,
		private readonly IManagePersonalConfigValues $pConfig,
		private readonly EventDispatcherInterface $eventDispatcher,
		private readonly IHandleUserSessions $session,
		private Page $page,
		private readonly Mode $mode,
		private readonly Arguments $args,
		private readonly Profiler $profiler,
	) {}

	/**
	 * Register required assets for the status editor.
	 * Registers typeahead.js and tagsinput CSS/JS files.
	 */
	public function registerAssets(): void
	{
		if ($this->assetsRegistered) {
			return;
		}

		$this->page->registerFooterScript(Theme::getPathForFile('asset/typeahead.js/dist/typeahead.bundle.js'));
		$this->page->registerFooterScript(Theme::getPathForFile('js/friendica-tagsinput/friendica-tagsinput.js'));
		$this->page->registerStylesheet(Theme::getPathForFile('js/friendica-tagsinput/friendica-tagsinput.css'));
		$this->page->registerStylesheet(Theme::getPathForFile('js/friendica-tagsinput/friendica-tagsinput-typeahead.css'));
		$this->assetsRegistered = true;
	}

	/**
	 * Render the status editor form (JOT).
	 *
	 * @param array $formData Form data and options
	 * @param int $notesContactId Contact ID for notes
	 * @param bool $popup Whether to render in popup mode
	 * @return string Rendered HTML of the status editor
	 */
	public function renderEditor(array $formData = [], int $notesContactId = 0, bool $popup = false): string
	{
		// The user viewing, not the user being viewed
		$user = User::getById($this->session->getLocalUserId(), ['uid', 'nickname', 'allow_location', 'default-location']);
		if (empty($user['uid'])) {
			return '';
		}

		$this->profiler->startRecording('rendering');
		$o = '';

		$formData['allow_location'] ??= $user['allow_location'];
		$formData['default_location'] ??= $user['default-location'];
		$formData['nickname'] ??= $user['nickname'];
		$formData['lockstate'] = $formData['lockstate'] ?? ACL::getLockstateForUserId($user['uid']) ? 'lock' : 'unlock';
		$formData['acl'] ??= ACL::getFullSelectorHTML($this->page, $user['uid'], true);
		$formData['bang'] ??= '';
		$formData['visitor'] ??= 'block';
		$formData['is_owner'] ??= true;
		$formData['profile_uid'] ??= $this->session->getLocalUserId();

		$geotag = !empty($formData['allow_location']) ? Renderer::replaceMacros(Renderer::getMarkupTemplate('jot_geotag.tpl'), []) : '';

		$tpl = Renderer::getMarkupTemplate('jot-header.tpl');
		$this->page['htmlhead'] .= Renderer::replaceMacros($tpl, [
			'$geotag'        => $geotag,
			'$ispublic'      => $this->l10n->t('Visible to <strong>everybody</strong>'),
			'$linkurl'       => $this->l10n->t('Please enter a image/video/audio/webpage URL:'),
			'$vidurl'        => $this->l10n->t('Please enter a video URL:'),
			'$audurl'        => $this->l10n->t('Please enter an audio URL:'),
			'$term'          => $this->l10n->t('Tag term:'),
			'$fileas'        => $this->l10n->t('Save to Folder'),
			'$whereareu'     => $this->l10n->t('Where are you right now?'),
			'$delitems'      => $this->l10n->t("Delete item\x28s\x29?"),
			'$postPublished' => $this->l10n->t('Post published.'),
			'$goToPost'      => $this->l10n->t('Go to post'),
			'$is_mobile'     => $this->mode->isMobile(),
		]);

		// If user is not owner do not insert jot composer, use Mention modal instead
		if ($formData['is_owner'] === false) {
			return $o;
		}

		$jotplugins = $this->eventDispatcher->dispatch(
			new HtmlFilterEvent(HtmlFilterEvent::JOT_TOOL, ''),
		)->getHtml();

		if ($this->config->get('system', 'set_creation_date')) {
			$created_at = Temporal::getDateTimeField(
				new \DateTime(\Friendica\Database\DBA::NULL_DATETIME),
				new \DateTime('now'),
				null,
				$this->l10n->t('Created at'),
				'created_at',
			);
		} else {
			$created_at = '';
		}

		$tpl = Renderer::getMarkupTemplate('jot.tpl');

		if (isset($formData['contact_account_type']) && $formData['contact_account_type'] === User::ACCOUNT_TYPE_COMMUNITY) {
			$new_post = $this->l10n->t('Post to group');
		} else {
			$new_post = $this->l10n->t('New Post');
		}

		$o .= Renderer::replaceMacros($tpl, [
			'$new_post'            => $new_post,
			'$return_path'         => $this->args->getQueryString(),
			'$action'              => 'item',
			'$share'               => ($formData['button'] ?? '') ?: $this->l10n->t('Post'),
			'$loading'             => $this->l10n->t('Loading...'),
			'$upload'              => $this->l10n->t('Upload photo'),
			'$attach'              => $this->l10n->t('Attach file'),
			'$edbold'              => $this->l10n->t('Bold'),
			'$editalic'            => $this->l10n->t('Italic'),
			'$eduline'             => $this->l10n->t('Underline'),
			'$edquote'             => $this->l10n->t('Quote'),
			'$edemojis'            => $this->l10n->t('Add emojis'),
			'$contentwarn'         => $this->l10n->t('Content Warning'),
			'$edcode'              => $this->l10n->t('Code'),
			'$edurl'               => $this->l10n->t('Link'),
			'$edattach'            => $this->l10n->t('Link or Media'),
			'$setloc'              => $this->l10n->t('Set your location'),
			'$noloc'               => $this->l10n->t('Clear browser location'),
			'$weblink'             => $this->l10n->t('Link'),
			'$video'               => $this->l10n->t('Video'),
			'$audio'               => $this->l10n->t('Audio'),
			'$jot'                 => 1,
			'$title'               => $formData['title'] ?? '',
			'$placeholdertitle'    => $this->l10n->t('Set title'),
			'$summary'             => $formData['summary'] ?? '',
			'$placeholdersummary'  => Feature::isEnabled($this->session->getLocalUserId(), Feature::SUMMARY) ? $this->l10n->t('Set summary, abstract or spoiler text') : '',
			'$category'            => $formData['category'] ?? '',
			'$placeholdercategory' => Feature::isEnabled($this->session->getLocalUserId(), Feature::CATEGORIES) ? $this->l10n->t("Categories \x28comma-separated list\x29") : '',
			'$sensitive'           => ['sensitive', $this->l10n->t('Sensitive post'), $formData['sensitive'] ?? false],
			'$scheduled_at'        => Temporal::getDateTimeField(
				new \DateTime(),
				new \DateTime('now + 6 months'),
				null,
				$this->l10n->t('Scheduled at'),
				'scheduled_at',
			),
			'$created_at'   => $created_at,
			'$wait'         => $this->l10n->t('Please wait'),
			'$permset'      => $this->l10n->t('Permission settings'),
			'$shortpermset' => $this->l10n->t('Permissions'),
			'$wall'         => $notesContactId ? 0 : 1,
			'$posttype'     => $notesContactId ? ItemModel::PT_PERSONAL_NOTE : ItemModel::PT_ARTICLE,
			'$content'      => $formData['content'] ?? '',
			'$post_id'      => $formData['post_id'] ?? '',
			'$defloc'       => $formData['default_location'],
			'$visitor'      => $formData['visitor'],
			'$lockstate'    => $formData['lockstate'],
			'$bang'         => $formData['bang'],
			'$profile_uid'  => $formData['profile_uid'],
			'$preview'      => $this->l10n->t('Preview'),
			'$jotplugins'   => $jotplugins,
			'$notes_cid'    => $notesContactId,
			'$rand_num'     => Crypto::randomDigits(12),

			// ACL permissions box
			'$acl' => $formData['acl'],

			// jot nav tab (used in some themes)
			'$message' => $this->l10n->t('Message'),
			'$browser' => $this->l10n->t('Add file'),

			'$compose_link_title'  => $this->l10n->t('Open Compose page'),
			'$always_open_compose' => $this->pConfig->get($this->session->getLocalUserId(), 'frio', 'always_open_compose', false),
		]);

		if ($popup === true) {
			$o = '<div id="jot-popup" style="display: none;">' . $o . '</div>';
		}

		$this->profiler->stopRecording();
		return $o;
	}
}
