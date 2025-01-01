<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Profile;

use Friendica\BaseModule;
use Friendica\Content\Text\BBCode;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Post;
use Friendica\Module\BaseProfile;
use Friendica\Network\HTTPException;
use Friendica\Util\DateTimeFormat;

class Schedule extends BaseProfile
{
	protected function post(array $request = [])
	{
		if (!DI::userSession()->getLocalUserId()) {
			throw new HTTPException\ForbiddenException(DI::l10n()->t('Permission denied.'));
		}

		if (empty($_REQUEST['delete'])) {
			throw new HTTPException\BadRequestException();
		}

		if (!DBA::exists('delayed-post', ['id' => $_REQUEST['delete'], 'uid' => DI::userSession()->getLocalUserId()])) {
			throw new HTTPException\NotFoundException();
		}

		Post\Delayed::deleteById($_REQUEST['delete']);
	}

	protected function content(array $request = []): string
	{
		if (!DI::userSession()->getLocalUserId()) {
			throw new HTTPException\ForbiddenException(DI::l10n()->t('Permission denied.'));
		}

		$o = self::getTabsHTML('schedule', true, DI::userSession()->getLocalUserNickname(), false);

		$schedule = [];
		$delayed  = DBA::select('delayed-post', [], ['uid' => DI::userSession()->getLocalUserId()]);
		while ($row = DBA::fetch($delayed)) {
			$parameter = Post\Delayed::getParametersForid($row['id']);
			if (empty($parameter)) {
				continue;
			}
			$schedule[] = [
				'id'           => $row['id'],
				'scheduled_at' => DateTimeFormat::local($row['delayed']),
				'content'      => BBCode::toPlaintext($parameter['item']['body'], false)
			];
		}
		DBA::close($delayed);

		$tpl = Renderer::getMarkupTemplate('profile/schedule.tpl');
		$o .= Renderer::replaceMacros($tpl, [
			'$form_security_token' => BaseModule::getFormSecurityToken("profile_schedule"),
			'$title'               => DI::l10n()->t('Scheduled Posts'),
			'$nickname'            => $this->parameters['nickname'] ?? '',
			'$scheduled_at'        => DI::l10n()->t('Scheduled'),
			'$content'             => DI::l10n()->t('Content'),
			'$delete'              => DI::l10n()->t('Remove post'),
			'$schedule'            => $schedule,
		]);

		return $o;
	}
}
