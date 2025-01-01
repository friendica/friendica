<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Admin;

use Friendica\Content\Feature;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Module\BaseAdmin;

class Features extends BaseAdmin
{
	protected function post(array $request = [])
	{
		self::checkAdminAccess();

		self::checkFormSecurityTokenRedirectOnError('/admin/features', 'admin_manage_features');

		foreach (Feature::get(false) as $fdata) {
			foreach (array_slice($fdata, 1) as $f) {
				$feature = $f[0];
				switch ($_POST['featureselect_' . $feature]) {
					case 0:
						DI::config()->set('feature', $feature, false);
						DI::config()->delete('feature_lock', $feature);
						break;

					case 1:
						DI::config()->set('feature', $feature, true);
						DI::config()->delete('feature_lock', $feature);
						break;

					case 2:
						DI::config()->delete('feature', $feature);
						DI::config()->set('feature_lock', $feature, true);
						break;
				}
			}
		}

		DI::baseUrl()->redirect('admin/features');
	}

	protected function content(array $request = []): string
	{
		parent::content();

		$features  = [];
		$selection = [DI::l10n()->t('No'), DI::l10n()->t('Yes'), DI::l10n()->t('Locked')];
		foreach (Feature::get(false) as $fname => $fdata) {
			$features[$fname]    = [];
			$features[$fname][0] = $fdata[0];
			foreach (array_slice($fdata, 1) as $f) {
				$set                   = DI::config()->get('feature', $f[0], $f[3]);
				$selected              = $f[4] ? 2 : (int)$set;
				$features[$fname][1][] = ['featureselect_' . $f[0], $f[1], $selected, $f[2], $selection];
			}
		}

		$tpl = Renderer::getMarkupTemplate('admin/features.tpl');
		$o   = Renderer::replaceMacros($tpl, [
			'$form_security_token' => self::getFormSecurityToken("admin_manage_features"),
			'$title'               => DI::l10n()->t('Manage Additional Features'),
			'$features'            => $features,
			'$submit'              => DI::l10n()->t('Save Settings'),
		]);

		return $o;
	}
}
