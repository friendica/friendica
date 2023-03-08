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

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\Logger;
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

		// Now we'll fetch the item, if we have enough permisson
		$item = MAttach::getByIdWithPermission($item_id);
		if ($item === false) {
			throw new \Friendica\Network\HTTPException\ForbiddenException(DI::l10n()->t('Permission denied.'));
		}

		$data = MAttach::getData($item);
		if (is_null($data)) {
			Logger::notice('NULL data for attachment with id ' . $item['id']);
			throw new \Friendica\Network\HTTPException\NotFoundException(DI::l10n()->t('Item was not found.'));
		}

		// Use quotes around the filename to prevent a "multiple Content-Disposition"
		// error in Chrome for filenames with commas in them
		header('Content-type: ' . $item['filetype']);
		header('Content-length: ' . $item['filesize']);
		if (isset($_GET['attachment']) && $_GET['attachment'] === '0') {
			header('Content-disposition: filename="' . $item['filename'] . '"');
		} else {
			header('Content-disposition: attachment; filename="' . $item['filename'] . '"');
		}

		echo $data;
		System::exit();
		// NOTREACHED
	}
}
