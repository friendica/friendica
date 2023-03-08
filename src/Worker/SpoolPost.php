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

namespace Friendica\Worker;

use Friendica\Core\Logger;
use Friendica\Core\System;
use Friendica\Model\Item;

/**
 * Posts items that where spooled because they couldn't be posted.
 */
class SpoolPost {
	public static function execute() {
		$path = System::getSpoolPath();

		if (($path != '') && is_writable($path)){
			if ($dh = opendir($path)) {
				while (($file = readdir($dh)) !== false) {

					// It is not named like a spool file, so we don't care.
					if (substr($file, 0, 5) != "item-") {
						Logger::info('Spool file does does not start with "item-"', ['file' => $file]);
						continue;
					}

					$fullfile = $path."/".$file;

					// We don't care about directories either
					if (filetype($fullfile) != "file") {
						Logger::info('Spool file is no file', ['file' => $file]);
						continue;
					}

					// We can't read or write the file? So we don't care about it.
					if (!is_writable($fullfile) || !is_readable($fullfile)) {
						Logger::warning('Spool file has insufficent permissions', ['file' => $file, 'writable' => is_writable($fullfile), 'readable' => is_readable($fullfile)]);
						continue;
					}

					$arr = json_decode(file_get_contents($fullfile), true);

					// If it isn't an array then it is no spool file
					if (!is_array($arr)) {
						Logger::notice('Spool file is no array', ['file' => $file]);
						continue;
					}

					// Skip if it doesn't seem to be an item array
					if (!isset($arr['uid']) && !isset($arr['uri']) && !isset($arr['network'])) {
						Logger::warning('Spool file does not contain the needed fields', ['file' => $file]);
						continue;
					}

					$result = Item::insert($arr);

					Logger::info('Spool file is stored', ['file' => $file, 'result' => $result]);
					unlink($fullfile);
				}
				closedir($dh);
			}
		}
	}
}
