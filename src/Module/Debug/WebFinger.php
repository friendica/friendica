<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Debug;

use Friendica\BaseModule;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Network\Probe;

/**
 * Web based module to perform webfinger probing
 */
class WebFinger extends BaseModule
{
	protected function content(array $request = []): string
	{
		if (!DI::userSession()->getLocalUserId()) {
			throw new \Friendica\Network\HTTPException\ForbiddenException(DI::l10n()->t('Only logged in users are permitted to perform a probing.'));
		}

		$addr = $_GET['addr'] ?? '';
		$res  = '';

		if (!empty($addr)) {
			$res = Probe::getWebfingerArray($addr);
			$res = print_r($res, true);
		}

		$tpl = Renderer::getMarkupTemplate('webfinger.tpl');
		return Renderer::replaceMacros($tpl, [
			'$title'  => DI::l10n()->t('Webfinger Diagnostic'),
			'$submit' => DI::l10n()->t('Submit'),
			'$lookup' => DI::l10n()->t('Lookup address:'),
			'$addr'   => $addr,
			'$res'    => $res,
		]);
	}
}
