<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Profile;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\AppHelper;
use Friendica\Core\L10n;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Profile as ProfileModel;
use Friendica\Module\BaseProfile;
use Friendica\Module\Response;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class Media extends BaseProfile
{
	/**
	 * @var AppHelper
	 */
	private $appHelper;

	/**
	 * @var IHandleUserSessions
	 */
	private $userSession;

	public function __construct(
		L10n $l10n,
		BaseURL $baseUrl,
		Arguments $args,
		AppHelper $appHelper,
		LoggerInterface $logger,
		Profiler $profiler,
		Response $response,
		IHandleUserSessions $userSession,
		$server,
		array $parameters = []
	) {
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->appHelper   = $appHelper;
		$this->userSession = $userSession;
	}

	protected function content(array $request = []): string
	{
		$profile = ProfileModel::load($this->appHelper, $this->parameters['nickname']);
		if (empty($profile)) {
			throw new HTTPException\NotFoundException(DI::l10n()->t('User not found.'));
		}

		if (!$profile['net-publish']) {
			DI::page()['htmlhead'] .= '<meta content="noindex, noarchive" name="robots" />' . "\n";
		}

		$is_owner = DI::userSession()->getLocalUserId() == $profile['uid'];

		$o = self::getTabsHTML('media', $is_owner, $profile['nickname'], $profile['hide-friends']);

		$o .= Contact::getPostsFromUrl($profile['url'], $this->userSession->getLocalUserId(), true, $request);

		return $o;
	}
}
