<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\Core\System;
use Friendica\DI;
use Friendica\Model\Item;

/**
 * Posts items that where spooled because they couldn't be posted.
 */
class SpoolPost
{
	public static function execute()
	{
		$path = System::getSpoolPath();

		if (($path != '') && is_writable($path)) {
			if ($dh = opendir($path)) {
				while (($file = readdir($dh)) !== false) {

					// It is not named like a spool file, so we don't care.
					if (substr($file, 0, 5) != "item-") {
						DI::logger()->info('Spool file does not start with "item-"', ['file' => $file]);
						continue;
					}

					$fullfile = $path."/".$file;

					// We don't care about directories either
					if (filetype($fullfile) != "file") {
						DI::logger()->info('Spool file is no file', ['file' => $file]);
						continue;
					}

					// We can't read or write the file? So we don't care about it.
					if (!is_writable($fullfile) || !is_readable($fullfile)) {
						DI::logger()->warning('Spool file has insufficent permissions', ['file' => $file, 'writable' => is_writable($fullfile), 'readable' => is_readable($fullfile)]);
						continue;
					}

					$arr = json_decode(file_get_contents($fullfile), true);

					// If it isn't an array then it is no spool file
					if (!is_array($arr)) {
						DI::logger()->notice('Spool file is no array', ['file' => $file]);
						continue;
					}

					// Skip if it doesn't seem to be an item array
					if (!isset($arr['uid']) && !isset($arr['uri']) && !isset($arr['network'])) {
						DI::logger()->warning('Spool file does not contain the needed fields', ['file' => $file]);
						continue;
					}

					$result = Item::insert($arr);

					DI::logger()->info('Spool file is stored', ['file' => $file, 'result' => $result]);
					unlink($fullfile);
				}
				closedir($dh);
			}
		}
	}
}
