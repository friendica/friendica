<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\DI;
use Friendica\Model\Contact;

/**
 * Process follow request confirmations
 */
class FollowConfirm extends BaseModule
{
	protected function post(array $request = [])
	{
		parent::post($request);
		$uid = DI::userSession()->getLocalUserId();
		if (!$uid) {
			DI::sysmsg()->addNotice(DI::l10n()->t('Permission denied.'));
			return;
		}

		$intro_id = intval($_POST['intro_id'] ?? 0);
		$duplex   = intval($_POST['duplex'] ?? 0);
		$hidden   = intval($_POST['hidden'] ?? 0);

		$intro = DI::intro()->selectOneById($intro_id, DI::userSession()->getLocalUserId());

		Contact\Introduction::confirm($intro, $duplex, $hidden);
		DI::intro()->delete($intro);

		DI::baseUrl()->redirect('contact/' .  $intro->cid);
	}
}
