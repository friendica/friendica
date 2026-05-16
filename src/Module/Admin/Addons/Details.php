<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Admin\Addons;

use Friendica\Content\Text\Markdown;
use Friendica\Core\Addon\AddonInfo;
use Friendica\Core\Addon\Exception\InvalidAddonException;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Module\BaseAdmin;
use Friendica\Util\Strings;

class Details extends BaseAdmin
{
	protected function post(array $request = [])
	{
		self::checkAdminAccess();

		$addon = Strings::sanitizeFilePathItem($this->parameters['addon']);

		$redirect = 'admin/addons/' . $addon;

		if (is_file('addon/' . $addon . '/' . $addon . '.php')) {
			include_once 'addon/' . $addon . '/' . $addon . '.php';

			if (function_exists($addon . '_addon_admin_post')) {
				self::checkFormSecurityTokenRedirectOnError($redirect, 'admin_addons_details');

				$func = $addon . '_addon_admin_post';
				$func();
			}
		}

		DI::baseUrl()->redirect($redirect);
	}

	protected function content(array $request = []): string
	{
		parent::content();

		$addonHelper = DI::addonHelper();

		$addon = Strings::sanitizeFilePathItem($this->parameters['addon']);

		if (!is_file("addon/$addon/$addon.php")) {
			DI::sysmsg()->addNotice(DI::l10n()->t('Addon not found.'));
			$addonHelper->uninstallAddon($addon);
			DI::baseUrl()->redirect('admin/addons');
		}

		if (($_GET['action'] ?? '') == 'toggle') {
			self::checkFormSecurityTokenRedirectOnError('/admin/addons', 'admin_addons_details', 't');

			// Toggle addon status
			if ($addonHelper->isAddonEnabled($addon)) {
				$addonHelper->uninstallAddon($addon);
				DI::sysmsg()->addInfo(DI::l10n()->t('Addon %s disabled.', $addon));
			} else {
				$addonHelper->installAddon($addon);
				DI::sysmsg()->addInfo(DI::l10n()->t('Addon %s enabled.', $addon));
			}

			DI::baseUrl()->redirect('admin/addons/' . $addon);
		}

		// display addon details
		if ($addonHelper->isAddonEnabled($addon)) {
			$status = 'on';
			$action = DI::l10n()->t('Disable');
		} else {
			$status = 'off';
			$action = DI::l10n()->t('Enable');
		}

		$readme = null;
		if (is_file("addon/$addon/README.md")) {
			$readme = Markdown::convert(file_get_contents("addon/$addon/README.md"), false);
		} elseif (is_file("addon/$addon/README")) {
			$readme = '<pre>' . file_get_contents("addon/$addon/README") . '</pre>';
		}

		$addons_admin = $addonHelper->getEnabledAddonsWithAdminSettings();

		$admin_form = '';
		if (in_array($addon, $addons_admin)) {
			require_once "addon/$addon/$addon.php";
			$func = $addon . '_addon_admin';
			$func($admin_form);
		}

		try {
			$addonInfo = $addonHelper->getAddonInfo($addon);
		} catch (InvalidAddonException $th) {
			$this->logger->error('Invalid addon found: ' . $addon, ['exception' => $th]);
			DI::sysmsg()->addNotice(DI::l10n()->t('Invalid Addon found.'));

			$addonInfo = AddonInfo::fromArray(['id' => $addon, 'name' => $addon]);
		}

		$addonAuthors = [];

		foreach ($addonInfo->getAuthors() as $addonAuthor) {
			if (array_key_exists('link', $addonAuthor) && empty(parse_url($addonAuthor['link'], PHP_URL_SCHEME))) {
				$contact = Contact::getByURL($addonAuthor['link'], false);

				if (!empty($contact['url'])) {
					$addonAuthor['link'] = $contact['url'];
				}
			}

			$addonAuthors[] = $addonAuthor;
		}

		$addonMaintainers = [];

		foreach ($addonInfo->getMaintainers() as $addonMaintainer) {
			if (array_key_exists('link', $addonMaintainer) && empty(parse_url($addonMaintainer['link'], PHP_URL_SCHEME))) {
				$contact = Contact::getByURL($addonMaintainer['link'], false);

				if (!empty($contact['url'])) {
					$addonMaintainer['link'] = $contact['url'];
				}
			}

			$addonMaintainers[] = $addonMaintainer;
		}

		$t = Renderer::getMarkupTemplate('admin/addons/details.tpl');

		return Renderer::replaceMacros($t, [
			'$title'    => DI::l10n()->t('Administration'),
			'$page'     => DI::l10n()->t('Addons'),
			'$toggle'   => DI::l10n()->t('Toggle'),
			'$settings' => DI::l10n()->t('Settings'),

			'$addon'  => $addon,
			'$status' => $status,
			'$action' => $action,
			'$info'   => [
				'name'        => $addonInfo->getName(),
				'version'     => $addonInfo->getVersion(),
				'description' => $addonInfo->getDescription(),
				'author'      => $addonAuthors,
				'maintainer'  => $addonMaintainers,
			],
			'$str_author'     => DI::l10n()->t('Author: '),
			'$str_maintainer' => DI::l10n()->t('Maintainer: '),

			'$admin_form' => $admin_form,
			'$function'   => 'addons',
			'$screenshot' => '',
			'$readme'     => $readme,

			'$form_security_token' => self::getFormSecurityToken('admin_addons_details'),
		]);
	}
}
