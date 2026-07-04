<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Profile;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\AppHelper;
use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Model\Profile;
use Friendica\Module\Response;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class Restricted extends BaseModule
{
	public function __construct(private readonly AppHelper $appHelper, L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
	}

	protected function content(array $request = []): string
	{
		$profile = Profile::load($this->appHelper, $this->parameters['nickname'] ?? '', false);
		if (!$profile) {
			throw new HTTPException\NotFoundException($this->t('Profile not found.'));
		}

		if (empty($profile['hidewall'])) {
			$this->baseUrl->redirect('profile/' . $profile['nickname']);
		}

		$tpl = Renderer::getMarkupTemplate('exception.tpl');
		return Renderer::replaceMacros($tpl, [
			'$title'   => $this->t('Restricted profile'),
			'$message' => $this->t('This profile has been restricted which prevents access to their public content from anonymous visitors.'),
		]);
	}
}
