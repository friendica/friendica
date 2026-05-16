<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Moderation\Report;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Content\Conversation as ConversationContent;
use Friendica\Content\Pager;
use Friendica\Content\Text\BBCode;
use Friendica\Core\L10n;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Model\UserSession;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Moderation\Entity\Report;
use Friendica\Module\Moderation\Utils\ReportUtil;
use Friendica\Module\Response;
use Friendica\Navigation\SystemMessages;
use Friendica\Network\HTTPException\ForbiddenException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class Create extends BaseModule
{
	public const CONTACT_ACTION_NONE     = 0;
	public const CONTACT_ACTION_COLLAPSE = 1;
	public const CONTACT_ACTION_IGNORE   = 2;
	public const CONTACT_ACTION_BLOCK    = 3;

	/** @var SystemMessages */
	private $systemMessages;
	/** @var App\Page */
	private $page;
	/** @var UserSession */
	private $session;
	/** @var \Friendica\Moderation\Factory\Report */
	private $factory;
	/** @var \Friendica\Moderation\Repository\Report */
	private $repository;
	/** @var ReportUtil */
	protected $reportUtil;

	public function __construct(\Friendica\Moderation\Repository\Report $repository, ReportUtil $reportUtil, \Friendica\Moderation\Factory\Report $factory, UserSession $session, App\Page $page, SystemMessages $systemMessages, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->systemMessages = $systemMessages;
		$this->page           = $page;
		$this->reportUtil     = $reportUtil;
		$this->session        = $session;
		$this->factory        = $factory;
		$this->repository     = $repository;
	}

	protected function post(array $request = [])
	{
		if (!$this->session->getLocalUserId()) {
			throw new ForbiddenException();
		}

		$report = [];
		foreach (['cid', 'category', 'rule-ids', 'uri-ids'] as $key) {
			if (isset($request[$key])) {
				$report[$key] = $request[$key];
			}
		}

		if (isset($request['url'])) {
			$cid = Contact::getIdForURL($request['url']);
			if ($cid) {
				$report['cid'] = $cid;
			} else {
				$report['url'] = $request['url'];
				$this->systemMessages->addNotice($this->t('Contact not found or their server is already blocked on this node.'));
			}
		}

		if (isset($request['comment'])) {
			$this->session->set('report_comment', $request['comment']);
			unset($request['comment']);
		}

		if (isset($request['report_create'])) {
			$report = $this->factory->createFromForm(
				System::getRules(true),
				$request['cid'],
				$this->session->getLocalUserId(),
				$request['category'],
				!empty($request['rule-ids']) ? explode(',', $request['rule-ids']) : [],
				$this->session->get('report_comment') ?? '',
				!empty($request['uri-ids']) ? explode(',', $request['uri-ids']) : [],
				(bool) ($request['forward'] ?? false),
			);
			$this->repository->save($report);

			switch ($request['contact_action'] ?? 0) {
				case self::CONTACT_ACTION_COLLAPSE:
					Contact\User::setCollapsed($request['cid'], $this->session->getLocalUserId(), true);
					break;
				case self::CONTACT_ACTION_IGNORE:
					Contact\User::setIgnored($request['cid'], $this->session->getLocalUserId(), true);
					break;
				case self::CONTACT_ACTION_BLOCK:
					Contact\User::setBlocked($request['cid'], $this->session->getLocalUserId(), true);
					break;
			}
		}

		$this->baseUrl->redirect($this->args->getCommand() . '?' . http_build_query($report));
	}

	protected function content(array $request = []): string
	{
		if (!$this->session->getLocalUserId()) {
			throw new ForbiddenException($this->t('Please login to access this page.'));
		}

		$this->page['aside'] = $this->getAside($request);

		if (empty($request['cid'])) {
			return $this->pickContact($request);
		}

		if (empty($request['category'])) {
			return $this->pickCategory($request);
		}

		if ($request['category'] == Report::CATEGORY_VIOLATION && !isset($request['rule-ids'])) {
			return $this->pickRules($request);
		}

		if (!isset($request['uri-ids'])) {
			return $this->pickPosts($request);
		}

		return $this->summary($request);
	}

	private function pickContact(array $request): string
	{
		$tpl = Renderer::getMarkupTemplate('moderation/report/create/pick_contact.tpl');
		return Renderer::replaceMacros($tpl, [
			'$l10n' => [
				'title'       => $this->t('Create Moderation Report'),
				'page'        => $this->t('Pick Contact'),
				'description' => $this->t('Please enter below the contact address or profile URL you would like to create a moderation report about.'),
				'submit'      => $this->t('Submit'),
			],

			'$url' => ['url', $this->t('Contact address/URL'), $request['url'] ?? ''],
		]);
	}

	private function pickCategory(array $request): string
	{
		$tpl = Renderer::getMarkupTemplate('moderation/report/create/pick_category.tpl');
		return Renderer::replaceMacros($tpl, [
			'$l10n' => [
				'title'       => $this->t('Create Moderation Report'),
				'page'        => $this->t('Pick Category'),
				'description' => $this->t('Please pick below the category of your report.'),
				'submit'      => $this->t('Submit'),
			],

			'$category_spam'      => ['category', $this->t('Spam')                     , Report::CATEGORY_SPAM     , $this->t('This contact is publishing many repeated/overly long posts/replies or advertising their product/websites in otherwise irrelevant conversations.'), $request['category'] == Report::CATEGORY_SPAM],
			'$category_illegal'   => ['category', $this->t('Illegal Content')          , Report::CATEGORY_ILLEGAL  , $this->t("This contact is publishing content that is considered illegal in this node's hosting juridiction."), $request['category'] == Report::CATEGORY_ILLEGAL],
			'$category_safety'    => ['category', $this->t('Community Safety')         , Report::CATEGORY_SAFETY   , $this->t("This contact aggravated you or other people, by being provocative or insensitive, intentionally or not. This includes disclosing people's private information (doxxing), posting threats or offensive pictures in posts or replies."), $request['category'] == Report::CATEGORY_SAFETY],
			'$category_unwanted'  => ['category', $this->t('Unwanted Content/Behavior'), Report::CATEGORY_UNWANTED , $this->t("This contact has repeatedly published content irrelevant to the node's theme or is openly criticizing the node's administration/moderation without directly engaging with the relevant people for example or repeatedly nitpicking on a sensitive topic."), $request['category'] == Report::CATEGORY_UNWANTED],
			'$category_violation' => ['category', $this->t('Rules Violation')          , Report::CATEGORY_VIOLATION, $this->t('This contact violated one or more rules of this node. You will be able to pick which one(s) in the next step.'), $request['category'] == Report::CATEGORY_VIOLATION],
			'$category_other'     => ['category', $this->t('Other')                    , Report::CATEGORY_OTHER    , $this->t('Please elaborate below why you submitted this report. The more details you provide, the better your report can be handled.'), $request['category'] == Report::CATEGORY_OTHER],

			'$comment' => ['comment', $this->t('Additional Information'), $this->session->get('report_comment') ?? '', $this->t('Please provide any additional information relevant to this particular report. You will be able to attach posts by this contact in the next step, but any context is welcome.')],
		]);
	}

	private function pickRules(array $request): string
	{
		$rules = [];

		foreach (System::getRules(true) as $rule_line => $rule_text) {
			$rules[] = ['rule-ids[]', $rule_line, $rule_text, in_array($rule_line, $request['rule_ids'] ?? [])];
		}

		$tpl = Renderer::getMarkupTemplate('moderation/report/create/pick_rules.tpl');
		return Renderer::replaceMacros($tpl, [
			'$l10n' => [
				'title'       => $this->t('Create Moderation Report'),
				'page'        => $this->t('Pick Rules'),
				'description' => $this->t('Please pick below the node rules you believe this contact violated.'),
				'submit'      => $this->t('Submit'),
			],

			'$rules' => $rules,
		]);
	}

	private function pickPosts(array $request): string
	{
		$threads = [];

		$contact = DBA::selectFirst('contact', ['contact-type', 'network'], ['id' => $request['cid']]);
		if (DBA::isResult($contact)) {
			$contact_field = $contact['contact-type'] == Contact::TYPE_COMMUNITY || $contact['network'] == Protocol::MAIL ? 'owner-id' : 'author-id';

			$condition = [
				$contact_field => $request['cid'],
				'gravity'      => [Item::GRAVITY_PARENT, Item::GRAVITY_COMMENT],
			];

			if (empty($contact['network']) || in_array($contact['network'], Protocol::FEDERATED)) {
				$condition = DBA::mergeConditions($condition, ['(`uid` = 0 OR (`uid` = ? AND NOT `global`))', DI::userSession()->getLocalUserId()]);
			} else {
				$condition['uid'] = DI::userSession()->getLocalUserId();
			}

			if (DI::mode()->isMobile()) {
				$itemsPerPage = DI::pConfig()->get(
					DI::userSession()->getLocalUserId(),
					'system',
					'itemspage_mobile_network',
					DI::config()->get('system', 'itemspage_network_mobile'),
				);
			} else {
				$itemsPerPage = DI::pConfig()->get(
					DI::userSession()->getLocalUserId(),
					'system',
					'itemspage_network',
					DI::config()->get('system', 'itemspage_network'),
				);
			}

			$pager = new Pager(DI::l10n(), DI::args()->getQueryString(), $itemsPerPage);

			$params = ['order' => ['received' => true], 'limit' => [$pager->getStart(), $pager->getItemsPerPage()]];

			$fields = array_merge(Item::DISPLAY_FIELDLIST, ['featured']);
			$items  = Post::toArray(Post::selectForUser(DI::userSession()->getLocalUserId(), $fields, $condition, $params));

			$formSecurityToken = BaseModule::getFormSecurityToken('contact_action');

			$threads = DI::conversation()->getContextLessThreadList($items, ConversationContent::MODE_CONTACT_POSTS, false, false, $formSecurityToken);
		}

		$tpl = Renderer::getMarkupTemplate('moderation/report/create/pick_posts.tpl');
		return Renderer::replaceMacros($tpl, [
			'$l10n' => [
				'title'       => $this->t('Create Moderation Report'),
				'page'        => $this->t('Pick Posts'),
				'description' => $this->t('Please optionally pick posts to attach to your report.'),
				'submit'      => $this->t('Submit'),
			],

			'$threads' => $threads,
		]);
	}

	private function summary(array $request): string
	{
		$this->page['aside'] = '';

		$contact = Contact::getById($request['cid'], ['url']);

		$tpl = Renderer::getMarkupTemplate('moderation/report/create/summary.tpl');

		$forward_translation = $this->t('Would you like to forward this report to the remote server?');
		// @deprecated 2026.01 this translation is scheduled for removal as a new translation has been added without the typo
		$forward_translation = $this->t('Would you ike to forward this report to the remote server?');

		return Renderer::replaceMacros($tpl, [
			'$l10n' => [
				'title'                => $this->t('Create Moderation Report'),
				'page'                 => $this->t('Summary'),
				'submit'               => $this->t('Submit Report'),
				'contact_action_title' => $this->t('Further Action'),
				'contact_action_desc'  => $this->t('You can also perform one of the following action on the contact you reported:'),
			],

			'$cid'      => $request['cid'],
			'$category' => $request['category'],
			'$ruleIds'  => implode(',', $request['rule-ids'] ?? []),
			'$uriIds'   => implode(',', $request['uri-ids'] ?? []),

			'$nothing'  => ['contact_action', $this->t('Nothing'), self::CONTACT_ACTION_NONE, '', true],
			'$collapse' => ['contact_action', $this->t('Collapse contact'), self::CONTACT_ACTION_COLLAPSE, $this->t('Their posts and replies will keep appearing in your Network page but their content will be collapsed by default.')],
			'$ignore'   => ['contact_action', $this->t('Ignore contact'), self::CONTACT_ACTION_IGNORE, $this->t("Their posts won't appear in your Network page anymore, but their replies can appear in forum threads. They still can follow you.")],
			'$block'    => ['contact_action', $this->t('Block contact'), self::CONTACT_ACTION_BLOCK, $this->t("Their posts won't appear in your Network page anymore, but their replies can appear in forum threads, with their content collapsed by default. They cannot follow you but still can have access to your public posts by other means.")],

			'$display_forward' => !$this->baseUrl->isLocalUrl($contact['url']),
			'$forward'         => ['report_forward', $this->t('Forward report'), self::CONTACT_ACTION_BLOCK, $forward_translation],

			'$summary' => $this->getAside($request),
		]);
	}

	private function getAside(array $request): string
	{
		$contact = null;
		if (!empty($request['cid'])) {
			$contact = Contact::getById($request['cid']);
		}

		if (!empty($request['rule-ids'])) {
			$rules = array_filter(System::getRules(true), function ($rule_id) use ($request) {
				return in_array($rule_id, $request['rule-ids']);
			}, ARRAY_FILTER_USE_KEY);
		}

		$tpl = Renderer::getMarkupTemplate('moderation/report/create/aside.tpl');
		return Renderer::replaceMacros($tpl, [
			'$l10n' => [
				'contact_title'  => $this->t('1. Pick a contact'),
				'category_title' => $this->t('2. Pick a category'),
				'rules_title'    => $this->t('2a. Pick rules'),
				'comment_title'  => $this->t('2b. Add comment'),
				'posts_title'    => $this->t('3. Pick posts'),
			],

			'$contact'  => $contact,
			'$category' => $this->reportUtil->getReportCategoryName($request['category'] ?? 0),
			'$rules'    => $rules ?? [],
			'$comment'  => BBCode::convertForUriId($contact['uri-id'] ?? 0, $this->session->get('report_comment') ?? '', BBCode::EXTERNAL),
			'$posts'    => count($request['uri-ids'] ?? []),
		]);
	}
}
