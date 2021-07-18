<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
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

use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Module\BaseAdmin;
use Friendica\Util\Strings;
use Psr\Log\LogLevel;

class Storage extends BaseAdmin
{
	public static function post(array $parameters = [])
	{
		self::checkAdminAccess();

		self::checkFormSecurityTokenRedirectOnError('/admin/storage', 'admin_storage');

		$storagebackend    = Strings::escapeTags(trim($_POST['storagebackend'] ?? ''));

		// save storage backend form
		if (DI::storageManager()->setBackend($storagebackend)) {
			$storage_opts     = DI::storage()->getOptions();
			$storage_form_prefix = preg_replace('|[^a-zA-Z0-9]|', '', $storagebackend);
			$storage_opts_data   = [];
			foreach ($storage_opts as $name => $info) {
				$fieldname = $storage_form_prefix . '_' . $name;
				switch ($info[0]) { // type
					case 'checkbox':
					case 'yesno':
						$value = !empty($_POST[$fieldname]);
						break;
					default:
						$value = $_POST[$fieldname] ?? '';
				}
				$storage_opts_data[$name] = $value;
			}
			unset($name);
			unset($info);

			$storage_form_errors = DI::storage()->saveOptions($storage_opts_data);
			if (count($storage_form_errors)) {
				foreach ($storage_form_errors as $name => $err) {
					notice('Storage backend, ' . $storage_opts[$name][1] . ': ' . $err);
				}
				DI::baseUrl()->redirect('admin/storage');
			}
		} elseif (!empty($storagebackend)) {
			notice(DI::l10n()->t('Invalid storage backend setting value.'));
		}

		DI::baseUrl()->redirect('admin/storage');
	}

	public static function content(array $parameters = [])
	{
		parent::content($parameters);

		$current_storage_backend = DI::storage();
		$available_storage_backends = [];

		// show legacy option only if it is the current backend:
		// once changed can't be selected anymore
		if ($current_storage_backend == null) {
			$available_storage_backends[''] = DI::l10n()->t('Database (legacy)');
		}

		foreach (DI::storageManager()->listBackends() as $name => $class) {
			$available_storage_backends[$name] = $name;
		}

		// build storage config form,
		$storage_form_prefix = preg_replace('|[^a-zA-Z0-9]|' ,'', $current_storage_backend);

		$storage_form = [];
		if (!is_null($current_storage_backend) && $current_storage_backend != '') {
			foreach ($current_storage_backend->getOptions() as $name => $info) {
				$type = $info[0];
				// Backward compatibilty with yesno field description
				if ($type == 'yesno') {
					$type = 'checkbox';
					// Remove translated labels Yes No from field info
					unset($info[4]);
				}

				$info[0] = $storage_form_prefix . '_' . $name;
				$info['type'] = $type;
				$info['field'] = 'field_' . $type . '.tpl';
				$storage_form[$name] = $info;
			}
		}

		$t = Renderer::getMarkupTemplate('admin/storage.tpl');

		return Renderer::replaceMacros($t, [
			'$title' => DI::l10n()->t('Administration'),
			'$page' => DI::l10n()->t('Storage'),
			'$submit' => DI::l10n()->t('Save Settings'),
			'$clear' => DI::l10n()->t('Clear'),
			'$baseurl' => DI::baseUrl()->get(true),
			'$form_security_token' => self::getFormSecurityToken("admin_storage"),
			'$storagebackend'   => ['storagebackend', DI::l10n()->t('File storage backend'), $current_storage_backend, DI::l10n()->t('The backend used to store uploaded data. If you change the storage backend, you can manually move the existing files. If you do not do so, the files uploaded before the change will still be available at the old backend. Please see <a href="/help/Settings#1_2_3_1">the settings documentation</a> for more information about the choices and the moving procedure.'), $available_storage_backends],
			'$storageform'      => $storage_form,
		]);
	}
}
