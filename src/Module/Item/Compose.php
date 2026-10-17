<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Item;

use DateTime;
use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\App\Page;
use Friendica\AppHelper;
use Friendica\BaseModule;
use Friendica\Content\Feature;
use Friendica\Core\ACL;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Model\UserSession;
use Friendica\Core\Theme;
use Friendica\Database\DBA;
use Friendica\Event\HtmlFilterEvent;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\User;
use Friendica\Module\Response;
use Friendica\Module\Security\Login;
use Friendica\Navigation\SystemMessages;
use Friendica\Network\HTTPException\NotImplementedException;
use Friendica\Util\ACLFormatter;
use Friendica\Util\Crypto;
use Friendica\Util\Profiler;
use Friendica\Util\Temporal;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

class Compose extends BaseModule
{
	public function __construct(private readonly EventDispatcherInterface $eventDispatcher, private readonly AppHelper $appHelper, private readonly UserSession $session, private readonly IManageConfigValues $config, private readonly IManagePersonalConfigValues $pConfig, private readonly Page $page, private readonly ACLFormatter $ACLFormatter, private readonly SystemMessages $systemMessages, L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
	}

	protected function post(array $request = [])
	{
		if (!empty($request['body'])) {
			$_REQUEST['return'] = 'network';
			require_once 'mod/item.php';
			item_post();
		} else {
			$this->systemMessages->addNotice($this->l10n->t('Please enter a post body.'));
		}
	}

	protected function content(array $request = []): string
	{
		if (!$this->session->getLocalUserId()) {
			return Login::form('compose');
		}

		if ($this->appHelper->getCurrentTheme() !== 'frio') {
			throw new NotImplementedException($this->l10n->t('This feature is only available with the frio theme.'));
		}

		$posttype = $this->parameters['type'] ?? Item::PT_ARTICLE;
		if (!in_array($posttype, [Item::PT_ARTICLE, Item::PT_PERSONAL_NOTE])) {
			$posttype = match ($posttype) {
				'note'  => Item::PT_PERSONAL_NOTE,
				default => Item::PT_ARTICLE,
			};
		}

		$user = User::getById($this->session->getLocalUserId(), ['allow_cid', 'allow_gid', 'deny_cid', 'deny_gid', 'default-location']);

		$contact_allow_list = $this->ACLFormatter->expand($user['allow_cid']);
		$circle_allow_list  = $this->ACLFormatter->expand($user['allow_gid']);
		$contact_deny_list  = $this->ACLFormatter->expand($user['deny_cid']);
		$circle_deny_list   = $this->ACLFormatter->expand($user['deny_gid']);

		switch ($posttype) {
			case Item::PT_PERSONAL_NOTE:
				$compose_title      = $this->l10n->t('Compose new personal note');
				$type               = 'note';
				$doesFederate       = false;
				$contact_allow_list = [$this->appHelper->getContactId()];
				$circle_allow_list  = [];
				$contact_deny_list  = [];
				$circle_deny_list   = [];
				break;
			default:
				$compose_title = $this->l10n->t('Compose new post');
				$type          = 'post';
				$doesFederate  = true;

				$contact_allow = $request['contact_allow'] ?? '';
				$circle_allow  = $request['circle_allow']  ?? '';
				$contact_deny  = $request['contact_deny']  ?? '';
				$circle_deny   = $request['circle_deny']   ?? '';

				if ($contact_allow
					. $circle_allow
					. $contact_deny
					. $circle_deny) {
					$contact_allow_list = $contact_allow ? explode(',', $contact_allow) : [];
					$circle_allow_list  = $circle_allow  ? explode(',', $circle_allow)  : [];
					$contact_deny_list  = $contact_deny  ? explode(',', $contact_deny)  : [];
					$circle_deny_list   = $circle_deny   ? explode(',', $circle_deny)   : [];
				}

				break;
		}

		$title     = $request['title']     ?? '';
		$summary   = $request['summary']   ?? '';
		$sensitive = $request['sensitive'] ?? false;
		$category  = $request['category']  ?? '';
		$body      = $request['body']      ?? '';
		$location  = $request['location']  ?? $user['default-location'];
		$wall      = $request['wall']      ?? $type == 'post';

		$jotplugins = $this->eventDispatcher->dispatch(
			new HtmlFilterEvent(HtmlFilterEvent::JOT_TOOL, ''),
		)->getHtml();

		// Output
		$this->page->registerFooterScript(Theme::getPathForFile('js/ajaxupload.js'));
		$this->page->registerFooterScript(Theme::getPathForFile('js/linkPreview.js'));
		$this->page->registerFooterScript(Theme::getPathForFile('js/compose.js'));

		$contact = Contact::getById($this->appHelper->getContactId());

		if ($this->pConfig->get($this->session->getLocalUserId(), 'system', 'set_creation_date')) {
			$created_at = Temporal::getDateTimeField(
				new DateTime(DBA::NULL_DATETIME),
				new DateTime('now'),
				null,
				$this->l10n->t('Created at'),
				'created_at',
			);
		} else {
			$created_at = '';
		}

		// Initialize Advanced Composer
		$advancedComposer = $this->pConfig->get($this->session->getLocalUserId(), 'frio', 'enable_advancedcomposer', $this->config->get('frio', 'enable_advancedcomposer', false));
		if ($advancedComposer) {
			$html = $this->getAdvancedComposerHtml();
		} else {
			$html = '';
		}

		$tpl = Renderer::getMarkupTemplate('item/compose.tpl');
		return $html . Renderer::replaceMacros($tpl, [
			'$enableAdvancedComposer' => $advancedComposer,
			'$l10n'                   => [
				'compose_title'        => $compose_title,
				'default'              => '',
				'summary'              => $this->l10n->t('Summary'),
				'visibility_title'     => $this->l10n->t('Visibility'),
				'mytitle'              => $this->l10n->t('This is you'),
				'submit'               => $this->l10n->t('Post'),
				'edbold'               => $this->l10n->t('Bold'),
				'editalic'             => $this->l10n->t('Italic'),
				'eduline'              => $this->l10n->t('Underline'),
				'edquote'              => $this->l10n->t('Quote'),
				'edemojis'             => $this->l10n->t('Add emojis'),
				'contentwarn'          => $this->l10n->t('Content Warning'),
				'edcode'               => $this->l10n->t('Code'),
				'edimg'                => $this->l10n->t('Image'),
				'edurl'                => $this->l10n->t('Link'),
				'edattach'             => $this->l10n->t('Link or Media'),
				'prompttext'           => $this->l10n->t('Please enter a image/video/audio/webpage URL:'),
				'preview'              => $this->l10n->t('Preview'),
				'location_set'         => $this->l10n->t('Set your location'),
				'location_clear'       => $this->l10n->t('Clear the location'),
				'location_unavailable' => $this->l10n->t('Location services are unavailable on your device'),
				'location_disabled'    => $this->l10n->t('Location services are disabled. Please check the website\'s permissions on your device'),
				'wait'                 => $this->l10n->t('Please wait'),
				'btnAssistant'         => $this->l10n->t('Writing Assistant'),
				'btnZen'               => $this->l10n->t('Distraction-Free'),
				'btnEpZen'             => $this->l10n->t('Image Descriptions'),
				'btnFocusPreview'      => $this->l10n->t('Focus Preview'),
				'placeholdertitle'     => $this->l10n->t('Set title'),
				'placeholdersummary'   => Feature::isEnabled($this->session->getLocalUserId(), Feature::SUMMARY) ? $this->l10n->t('Set summary, abstract or spoiler text') : '',
				'placeholdercategory'  => Feature::isEnabled($this->session->getLocalUserId(), Feature::CATEGORIES) ? $this->l10n->t('Categories (comma-separated list)') : '',
				'always_open_compose'  => $this->pConfig->get(
					$this->session->getLocalUserId(),
					'frio',
					'always_open_compose',
					$this->config->get('frio', 'always_open_compose', false),
				) ? ''
						: $this->l10n->t('If you want to always use this editor for posting, you can configure the New Post button to always open it in your <a href="/settings/display">Theme settings</a>.'),
			],

			'$id'           => 0,
			'$posttype'     => $posttype,
			'$type'         => $type,
			'$wall'         => $wall,
			'$mylink'       => $this->baseUrl->remove($contact['url']),
			'$myphoto'      => $this->baseUrl->remove($contact['thumb']),
			'$sensitive'    => ['sensitive', $this->l10n->t('Sensitive post'), $request['sensitive'] ?? false],
			'$scheduled_at' => Temporal::getDateTimeField(
				new DateTime(),
				new DateTime('now + 6 months'),
				null,
				$this->l10n->t('Scheduled at'),
				'scheduled_at',
			),
			'$created_at' => $created_at,
			'$title'      => $title,
			'$summary'    => $summary,
			'sensitive'   => $sensitive,
			'$category'   => $category,
			'$body'       => $body,
			'$location'   => $location,

			'$contact_allow' => implode(',', $contact_allow_list),
			'$circle_allow'  => implode(',', $circle_allow_list),
			'$contact_deny'  => implode(',', $contact_deny_list),
			'$circle_deny'   => implode(',', $circle_deny_list),

			'$jotplugins'   => $jotplugins,
			'$rand_num'     => Crypto::randomDigits(12),
			'$acl_selector' => ACL::getFullSelectorHTML($this->page, $this->session->getLocalUserId(), $doesFederate, [
				'allow_cid' => $contact_allow_list,
				'allow_gid' => $circle_allow_list,
				'deny_cid'  => $contact_deny_list,
				'deny_gid'  => $circle_deny_list,
			]),
		]);
	}

	/**
	 * Generate Advanced Composer HTML
	 * Uses user settings from display configuration
	 * This is an adoption for the Friendica core of the work done by Jools here:
	 * https://git.friendica.dev/Jools/friendica-addons/src/branch/main/easycompose
	 *
	 * @return string HTML for Advanced Composer integration
	 */
	private function getAdvancedComposerHtml(): string
	{
		// Register Advanced Composer CSS and JS
		$this->page->registerStylesheet(Theme::getPathForFile('css/advancedcomposer.css'));
		$this->page->registerFooterScript(Theme::getPathForFile('js/advancedcomposer-lib.js'));
		$this->page->registerFooterScript(Theme::getPathForFile('js/advancedcomposer-layout.js'));
		$this->page->registerFooterScript(Theme::getPathForFile('js/advancedcomposer-analysis.js'));
		$this->page->registerFooterScript(Theme::getPathForFile('js/advancedcomposer-panel.js'));
		$this->page->registerFooterScript(Theme::getPathForFile('js/advancedcomposer-watchers.js'));
		$this->page->registerFooterScript(Theme::getPathForFile('js/advancedcomposer.js'));
		$this->page->registerFooterScript(Theme::getPathForFile('js/advancedcomposer-toggle.js'));
		$this->page->registerFooterScript(Theme::getPathForFile('js/advancedcomposer-distraction.js'));
		$this->page->registerFooterScript(Theme::getPathForFile('js/advancedcomposer-preview.js'));
		$this->page->registerFooterScript(Theme::getPathForFile('js/advancedcomposer-focus-preview.js'));
		$this->page->registerFooterScript(Theme::getPathForFile('js/advancedcomposer-ep-zen.js'));

		// Advanced Composer localization strings
		$advancedComposerL10n = [
			'title'                  => $this->l10n->t('Advanced Composer'),
			'subtitle'               => $this->l10n->t('Writing & Accessibility Assistant'),
			'structureTitle'         => $this->l10n->t('Structure & Balance'),
			'a11yTitle'              => $this->l10n->t('Accessibility Checklist'),
			'readabilityTitle'       => $this->l10n->t('Readability & Style'),
			'lblParagraphs'          => $this->l10n->t('Paragraph Structure'),
			'lblSentenceLength'      => $this->l10n->t('Text Balance'),
			'lblLinks'               => $this->l10n->t('Link Density'),
			'lblHashtags'            => $this->l10n->t('Hashtag Density'),
			'lblParaBalanced'        => $this->l10n->t('Balanced'),
			'lblParaOneBlock'        => $this->l10n->t('One block'),
			'lblParaCompact'         => $this->l10n->t('Very compact'),
			'lblParaStructured'      => $this->l10n->t('Well structured'),
			'lblParaShort'           => $this->l10n->t('Short post'),
			'lblBalanceEasy'         => $this->l10n->t('Easy to read'),
			'lblBalanceNested'       => $this->l10n->t('Complex'),
			'lblBalanceMedium'       => $this->l10n->t('Medium'),
			'lblLinkSubtle'          => $this->l10n->t('Subtle'),
			'lblLinkDense'           => $this->l10n->t('Very dense'),
			'lblLinkMany'            => $this->l10n->t('Many links'),
			'lblHashtagSubtle'       => $this->l10n->t('Subtle'),
			'lblHashtagDense'        => $this->l10n->t('Very dense'),
			'lblHashtagMany'         => $this->l10n->t('Many tags'),
			'a11yAltOk'              => $this->l10n->t('All images have descriptions (Alt-Text)'),
			'a11yAltWarn'            => $this->l10n->t('Images missing descriptions (Alt-Text) detected!'),
			'a11yEmojiOk'            => $this->l10n->t('No emoji overload (max 4 consecutive)'),
			'a11yEmojiWarn'          => $this->l10n->t('Emoji overload detected (hurts screen readers)'),
			'a11yParagraphOk'        => $this->l10n->t('Good text structuring (paragraphs used)'),
			'a11yParagraphWarn'      => $this->l10n->t('No paragraphs found (hard to read)'),
			'a11yParagraphNeutral'   => $this->l10n->t('No paragraphs required for short texts'),
			'tipExcellent'           => $this->l10n->t('Your post is beautifully readable and accessible!'),
			'tipNoParagraphs'        => $this->l10n->t('Tip: Adding double line breaks to create paragraphs makes long texts much easier to scan.'),
			'tipLongSentences'       => $this->l10n->t('Tip: Some sentences are very long (> 25 words). Shortening them increases reading flow!'),
			'tipShouting'            => $this->l10n->t('Tip: Typing in ALL CAPS feels like shouting. Consider using standard capitalization.'),
			'tipTooManyHashtags'     => $this->l10n->t('Tip: Too many hashtags make the text feel restless. Try to focus on the key ones.'),
			'tipEmojiFlood'          => $this->l10n->t('Tip: Emoji clusters can be disruptive for visitors using assistive technology.'),
			'tipMissingAlt'          => $this->l10n->t('Tip: An image is missing a description (Alt-Text). Adding a brief text ensures everyone can participate!'),
			'tipTooLong'             => $this->l10n->t('Tip: Text is extremely long. Real-time analysis paused for performance.'),
			'previewTooLongTitle'    => $this->l10n->t('Preview Paused'),
			'previewTooLongDesc'     => $this->l10n->t('The post is extremely long. The live preview has been paused to keep your browser tab responsive.'),
			'chars'                  => $this->l10n->t('characters'),
			'distractionFree'        => $this->l10n->t('Distraction-free mode'),
			'btnAssistant'           => $this->l10n->t('Writing Assistant'),
			'btnZen'                 => $this->l10n->t('Distraction-Free'),
			'btnEpZen'               => $this->l10n->t('Image Descriptions'),
			'btnPreview'             => $this->l10n->t('Preview'),
			'btnRefresh'             => $this->l10n->t('Refresh Preview'),
			'btnPublish'             => $this->l10n->t('Publish'),
			'btnFocusPreview'        => $this->l10n->t('Focus Preview'),
			'btnDesktop'             => $this->l10n->t('Desktop'),
			'btnMobile'              => $this->l10n->t('Mobile'),
			'btnBackToEditor'        => $this->l10n->t('Back to Editor'),
			'btnLoadingPreview'      => $this->l10n->t('Loading Preview...'),
			'previewTimestamp'       => $this->l10n->t('Just now · Preview'),
			'lblYou'                 => $this->l10n->t('You'),
			'brandText'              => $this->l10n->t('Advanced Composer (deactivatable in settings)'),
			'avatarFallback'         => $this->l10n->t('👤'),
			'helpToggleLabel'        => $this->l10n->t('How does the analysis work?'),
			'helpPrivacyBadge'       => $this->l10n->t('No external services · All processing on your own instance'),
			'helpPrivacyDetail'      => $this->l10n->t('Text analysis runs entirely in your browser - no data is sent anywhere for analysis. No third-party APIs, no cookies, no tracking. The optional post preview works exactly like Friendica\'s built-in preview button: your draft is sent to your own Friendica instance for rendering. The only server-side storage is your personal enable/disable preference in the standard Friendica settings.'),
			'helpParaTitle'          => $this->l10n->t('Paragraph Structure'),
			'helpParaBody'           => $this->l10n->t('Counts how many paragraphs your post contains (separated by blank lines). A single unbroken block of text scores low (30 %) because readers find it hard to scan. Two or more paragraphs score 100 %. Posts under 600 characters are always rated "Short post" regardless of structure.'),
			'helpBalanceTitle'       => $this->l10n->t('Text Balance'),
			'helpBalanceBody'        => $this->l10n->t('Measures average sentence length and flags sentences longer than 25 words. An average above 24 words scores 40 % ("Complex"), above 16 words scores 75 % ("Medium"), everything else scores 100 % ("Easy to read"). Shorter sentences improve readability for all audiences.'),
			'helpLinkTitle'          => $this->l10n->t('Link Density'),
			'helpLinkBody'           => $this->l10n->t('Counts all http/https URLs in your post. More than 5 links scores 30 % ("Very dense") - posts that are mostly links feel like spam to readers and federation filters. Up to 3 links scores 100 % ("Subtle").'),
			'helpHashtagTitle'       => $this->l10n->t('Hashtag Density'),
			'helpHashtagBody'        => $this->l10n->t('Counts #hashtags. More than 6 scores 30 % ("Very dense"). Posts with fewer, focused hashtags reach more people than hashtag-stuffed ones. Up to 3 hashtags scores 100 % ("Subtle").'),
			'helpAltTitle'           => $this->l10n->t('Alt-Text Check'),
			'helpAltBody'            => $this->l10n->t('Checks every [img] BBCode tag in your post for an alt text description. Alt text is essential for screen readers and users with visual impairments - it ensures everyone can participate in the conversation.'),
			'helpEmojiTitle'         => $this->l10n->t('Emoji Check'),
			'helpEmojiBody'          => $this->l10n->t('Detects sequences of 5 or more consecutive emoji. Screen readers read every emoji aloud by name ("grinning face", "thumbs up" ...), so a long cluster becomes an exhausting wall of words for blind users. Up to 4 consecutive emoji is fine.'),
			'helpParagraphA11yTitle' => $this->l10n->t('Paragraph Check (Accessibility)'),
			'helpParagraphA11yBody'  => $this->l10n->t('For posts longer than 300 characters, checks whether at least one paragraph break exists. Screen readers and cognitive-accessibility tools benefit greatly from structured text. Short posts are always rated neutral.'),
			'btnClose'               => $this->l10n->t('Close'),
			'btnCloseSymbol'         => '×',
			'placeholder'            => $this->l10n->t('Image description'),
		];

		// The strings end up in textContent/title, so they must not be HTML escaped.
		// JSON_HEX_* keeps them safe inside the <script> block instead.
		$advancedComposerL10nJson = json_encode($advancedComposerL10n, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

		$tpl = Renderer::getMarkupTemplate('item/advancedcomposer.tpl');
		return Renderer::replaceMacros($tpl, [
			'$advancedComposerL10nJson' => $advancedComposerL10nJson,
		]);
	}
}
