<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\System;
use Friendica\DI;
use Friendica\Model\Attach as MAttach;

/**
 * Attach Module
 */
class Attach extends BaseModule
{
	/**
	 * Return to user an attached file given the id
	 */
	protected function rawContent(array $request = [])
	{
		if (empty($this->parameters['item'])) {
			throw new \Friendica\Network\HTTPException\BadRequestException();
		}

		$item_id = intval($this->parameters['item']);

		// Check for existence
		$item = MAttach::exists(['id' => $item_id]);
		if ($item === false) {
			throw new \Friendica\Network\HTTPException\NotFoundException(DI::l10n()->t('Item was not found.'));
		}

		// Now we'll fetch the item, if we have enough permission
		$item = MAttach::getByIdWithPermission($item_id);
		if ($item === false) {
			throw new \Friendica\Network\HTTPException\ForbiddenException(DI::l10n()->t('Permission denied.'));
		}

		$data = MAttach::getData($item);
		if (is_null($data)) {
			$this->logger->notice('NULL data for attachment with id ' . $item['id']);
			throw new \Friendica\Network\HTTPException\NotFoundException(DI::l10n()->t('Item was not found.'));
		}

		// Use quotes around the filename to prevent a "multiple Content-Disposition"
		// error in Chrome for filenames with commas in them
		header('Content-type: ' . $item['filetype']);
		header('Content-length: ' . $item['filesize']);
		header('Content-disposition: attachment; filename="' . $item['filename'] . '"');

		echo $data;
		System::exit();
		// NOTREACHED
	}
}
