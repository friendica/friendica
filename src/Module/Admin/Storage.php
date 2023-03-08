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

use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Core\Storage\Exception\InvalidClassStorageException;
use Friendica\Core\Storage\Capability\ICanConfigureStorage;
use Friendica\Core\Storage\Capability\ICanWriteToStorage;
use Friendica\Module\BaseAdmin;
use Friendica\Util\Strings;

class Storage extends BaseAdmin
{
	protected function post(array $request = [])
	{
		self::checkAdminAccess();

		self::checkFormSecurityTokenRedirectOnError('/admin/storage', 'admin_storage');

		$storagebackend = trim($this->parameters['name'] ?? '');

		try {
			/** @var ICanConfigureStorage|false $newStorageConfig */
			$newStorageConfig = DI::storageManager()->getConfigurationByName($storagebackend);
		} catch (InvalidClassStorageException $storageException) {
			DI::sysmsg()->addNotice(DI::l10n()->t('Storage backend, %s is invalid.', $storagebackend));
			DI::baseUrl()->redirect('admin/storage');
		}

		if ($newStorageConfig !== false) {
			// save storage backend form
			$storage_opts        = $newStorageConfig->getOptions();
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

			$storage_form_errors = $newStorageConfig->saveOptions($storage_opts_data);
			if (count($storage_form_errors)) {
				foreach ($storage_form_errors as $name => $err) {
					DI::sysmsg()->addNotice(DI::l10n()->t('Storage backend %s error: %s', $storage_opts[$name][1], $err));
				}
				DI::baseUrl()->redirect('admin/storage');
			}
		}

		if (!empty($_POST['submit_save_set'])) {
			try {
				$newstorage = DI::storageManager()->getWritableStorageByName($storagebackend);

				if (!DI::storageManager()->setBackend($newstorage)) {
					DI::sysmsg()->addNotice(DI::l10n()->t('Invalid storage backend setting value.'));
				}
			} catch (InvalidClassStorageException $storageException) {
				DI::sysmsg()->addNotice(DI::l10n()->t('Invalid storage backend setting value.'));
			}
		}

		DI::baseUrl()->redirect('admin/storage');
	}

	protected function content(array $request = []): string
	{
		parent::content();

		$current_storage_backend = DI::storage();
		$available_storage_forms = [];

		foreach (DI::storageManager()->listBackends() as $name) {

			// build storage config form,
			$storage_form_prefix = preg_replace('|[^a-zA-Z0-9]|', '', $name);

			$storage_form  = [];
			$storageConfig = DI::storageManager()->getConfigurationByName($name);

			if ($storageConfig !== false) {
				foreach ($storageConfig->getOptions() as $option => $info) {

					$type = $info[0];
					// Backward compatibilty with yesno field description
					if ($type == 'yesno') {
						$type = 'checkbox';
						// Remove translated labels Yes No from field info
						unset($info[4]);
					}

					$info[0]               = $storage_form_prefix . '_' . $option;
					$info['type']          = $type;
					$info['field']         = 'field_' . $type . '.tpl';
					$storage_form[$option] = $info;
				}
			}

			$available_storage_forms[] = [
				'name'   => $name,
				'prefix' => $storage_form_prefix,
				'form'   => $storage_form,
				'active' => $current_storage_backend instanceof ICanWriteToStorage && $name === $current_storage_backend::getName(),
			];
		}

		$t = Renderer::getMarkupTemplate('admin/storage.tpl');

		return Renderer::replaceMacros($t, [
			'$title'                 => DI::l10n()->t('Administration'),
			'$label_current'         => DI::l10n()->t('Current Storage Backend'),
			'$label_config'          => DI::l10n()->t('Storage Configuration'),
			'$page'                  => DI::l10n()->t('Storage'),
			'$save'                  => DI::l10n()->t('Save'),
			'$save_use'              => DI::l10n()->t('Save & Use storage backend'),
			'$use'                   => DI::l10n()->t('Use storage backend'),
			'$save_reload'           => DI::l10n()->t('Save & Reload'),
			'$noconfig'              => DI::l10n()->t('This backend doesn\'t have custom settings'),
			'$form_security_token'   => self::getFormSecurityToken("admin_storage"),
			'$storagebackend'        => $current_storage_backend instanceof ICanWriteToStorage ? $current_storage_backend::getName() : DI::l10n()->t('Database (legacy)'),
			'$availablestorageforms' => $available_storage_forms,
		]);
	}
}
