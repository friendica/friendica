<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

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

		$features = Feature::get(false);

		foreach ($features as $fname => $fdata) {
			foreach (array_slice($fdata, 1) as $f) {
				$feature = $f[0];
				$feature_state = 'feature_' . $feature;
				$featurelock = 'featurelock_' . $feature;

				if (!empty($_POST[$feature_state])) {
					$val = intval($_POST[$feature_state]);
				} else {
					$val = 0;
				}
				DI::config()->set('feature', $feature, $val);

				if (!empty($_POST[$featurelock])) {
					DI::config()->set('feature_lock', $feature, 1);
				} else {
					DI::config()->delete('feature_lock', $feature);
				}
			}
		}

		DI::baseUrl()->redirect('admin/features');
	}

	protected function content(array $request = []): string
	{
		parent::content();

		$features = [];

		foreach (Feature::get(false) as $fname => $fdata) {
			$features[$fname] = [];
			$features[$fname][0] = $fdata[0];
			foreach (array_slice($fdata, 1) as $f) {
				$set = DI::config()->get('feature', $f[0], $f[3]);
				$features[$fname][1][] = [
					['feature_' . $f[0], $f[1], $set, $f[2]],
					['featurelock_' . $f[0], DI::l10n()->t('Lock feature %s', $f[1]), $f[4], '']
				];
			}
		}

		$tpl = Renderer::getMarkupTemplate('admin/features.tpl');
		$o = Renderer::replaceMacros($tpl, [
			'$form_security_token' => self::getFormSecurityToken("admin_manage_features"),
			'$title'               => DI::l10n()->t('Manage Additional Features'),
			'$features'            => $features,
			'$submit'              => DI::l10n()->t('Save Settings'),
		]);

		return $o;
	}
}
