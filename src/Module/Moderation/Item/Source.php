<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Moderation\Item;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\App\Page;
use Friendica\AppHelper;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Model;
use Friendica\Module\BaseModeration;
use Friendica\Module\Response;
use Friendica\Navigation\SystemMessages;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class Source extends BaseModeration
{
	/** @var IManageConfigValues */
	private $config;

	public function __construct(IManageConfigValues $config, Page $page, AppHelper $appHelper, SystemMessages $systemMessages, IHandleUserSessions $session, L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($page, $appHelper, $systemMessages, $session, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->config = $config;
	}

	protected function post(array $request = [])
	{
		// @todo check if POST is really used here
		$this->content($request);
	}

	protected function content(array $request = []): string
	{
		parent::content();

		$guid = basename($request['guid'] ?? $this->parameters['guid'] ?? '');

		$item_uri = '';
		$item_id  = '';
		$terms    = [];
		$source   = '';
		if (!empty($guid)) {
			$item = Model\Post::selectFirst(['id', 'uri-id', 'guid', 'uri'], ['guid' => $guid]);

			if ($item) {
				$item_id  = $item['id'];
				$item_uri = $item['uri'];
				$terms    = Model\Tag::getByURIId($item['uri-id'], [Model\Tag::HASHTAG, Model\Tag::MENTION, Model\Tag::IMPLICIT_MENTION]);

				$activity = Model\Post\Activity::getByURIId($item['uri-id']);
				if (!empty($activity)) {
					$source = $activity['activity'];
				}
			}
		}

		$tpl = Renderer::getMarkupTemplate('moderation/item/source.tpl');
		return Renderer::replaceMacros($tpl, [
			'$l10n' => [
				'title'       => $this->t('Moderation'),
				'page'        => $this->t('Item Source'),
				'itemidlbl'   => $this->t('Item Id'),
				'itemurilbl'  => $this->t('Item URI'),
				'submit'      => $this->t('Submit'),
				'termslbl'    => $this->t('Terms'),
				'taglbl'      => $this->t('Tag'),
				'typelbl'     => $this->t('Type'),
				'termlbl'     => $this->t('Term'),
				'urllbl'      => $this->t('URL'),
				'mentionlbl'  => $this->t('Mention'),
				'implicitlbl' => $this->t('Implicit Mention'),
				'error'       => $this->tt('Error', 'Errors', 1),
				'notfound'    => $this->t('Item not found'),
				'nosource'    => $this->t('No source recorded'),
				'noconfig'    => !$this->config->get('debug', 'store_source') ? $this->t('Please make sure the <code>debug.store_source</code> config key is set in <code>config/local.config.php</code> for future items to have sources.') : '',
			],
			'$guid_field' => ['guid', '', $guid, '', '', 'autofocus', '', $this->t('Item Guid')],
			'$guid'       => $guid,
			'$item_uri'   => $item_uri,
			'$item_id'    => $item_id,
			'$terms'      => $terms,
			'$source'     => $source,
		]);
	}
}
