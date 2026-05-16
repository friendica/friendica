<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\Renderer;
use Friendica\Core\System;
use Friendica\DI;
use Friendica\Network\HTTPException;
use Friendica\Util\Strings;

/**
 * Shows the maintenance reason
 * or redirects to the alternate location
 */
class Maintenance extends BaseModule
{
	protected function content(array $request = []): string
	{
		$reason = DI::config()->get('system', 'maintenance_reason') ?? '';

		if ((str_starts_with(Strings::normaliseLink($reason), 'http://'))
			|| (str_starts_with(Strings::normaliseLink($reason), 'https://'))) {
			System::externalRedirect($reason, 307);
		}

		$exception = new HTTPException\ServiceUnavailableException($reason);

		header($_SERVER['SERVER_PROTOCOL'] . ' ' . $exception->getCode() . ' ' . DI::l10n()->t('System down for maintenance'));

		$tpl = Renderer::getMarkupTemplate('exception.tpl');

		return Renderer::replaceMacros($tpl, [
			'$title'       => DI::l10n()->t('System down for maintenance'),
			'$message'     => DI::l10n()->t('This Friendica node is currently in maintenance mode, either automatically because it is self-updating or manually by the node administrator. This condition should be temporary, please come back in a few minutes.'),
			'$thrown'      => $reason,
			'$stack_trace' => '',
			'$trace'       => '',
		]);
	}
}
