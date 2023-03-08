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

namespace Friendica\Moderation;

use Exception;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Database\Database;
use Friendica\Network\HTTPException;
use Friendica\Util\Emailer;

class DomainPatternBlocklist
{
	/** @var IManageConfigValues */
	private $config;

	public function __construct(IManageConfigValues $config)
	{
		$this->config = $config;
	}

	public function get(): array
	{
		return $this->config->get('system', 'blocklist', []);
	}

	public function set(array $blocklist): bool
	{
		return $this->config->set('system', 'blocklist', $blocklist);
	}

	/**
	 * @param string $pattern
	 * @param string $reason
	 *
	 * @return int 0 if the block list couldn't be saved, 1 if the pattern was added, 2 if it was updated in place
	 */
	public function addPattern(string $pattern, string $reason): int
	{
		$update = false;

		$blocklist = [];
		foreach ($this->get() as $blocked) {
			if ($blocked['domain'] === $pattern) {
				$blocklist[] = [
					'domain' => $pattern,
					'reason' => $reason,
				];

				$update = true;
			} else {
				$blocklist[] = $blocked;
			}
		}

		if (!$update) {
			$blocklist[] = [
				'domain' => $pattern,
				'reason' => $reason,
			];
		}

		return $this->set($blocklist) ? ($update ? 2 : 1) : 0;
	}

	/**
	 * @param string $pattern
	 *
	 * @return int 0 if the block list couldn't be saved, 1 if the pattern wasn't found, 2 if it was removed
	 */
	public function removePattern(string $pattern): int
	{
		$found = false;

		$blocklist = [];
		foreach ($this->get() as $blocked) {
			if ($blocked['domain'] === $pattern) {
				$found = true;
			} else {
				$blocklist[] = $blocked;
			}
		}

		return $found ? ($this->set($blocklist) ? 2 : 0) : 1;
	}

	/**
	 * @param string $filename
	 *
	 * @return void
	 * @throws Exception
	 */
	public function exportToFile(string $filename)
	{
		$fp = fopen($filename, 'w');
		if (!$fp) {
			throw new Exception(sprintf('The file "%s" could not be created.', $filename));
		}

		foreach ($this->get() as $domain) {
			fputcsv($fp, $domain);
		}
	}

	/**
	 * Appends to the local block list all the patterns from the provided list that weren't already present.
	 *
	 * @param array $blocklist
	 *
	 * @return int The number of patterns actually added to the block list
	 */
	public function append(array $blocklist): int
	{
		$localBlocklist = $this->get();
		$localPatterns  = array_column($localBlocklist, 'domain');

		$importedPatterns = array_column($blocklist, 'domain');

		$patternsToAppend = array_diff($importedPatterns, $localPatterns);

		if (count($patternsToAppend)) {
			foreach (array_keys($patternsToAppend) as $key) {
				$localBlocklist[] = $blocklist[$key];
			}

			$this->set($localBlocklist);
		}

		return count($patternsToAppend);
	}

	/**
	 * Extracts a server domain pattern block list from the provided CSV file name. Deduplicates the list based on patterns.
	 *
	 * @param string $filename
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function extractFromCSVFile(string $filename): array
	{
		$fp = fopen($filename, 'r');
		if ($fp === false) {
			throw new Exception(sprintf('The file "%s" could not be opened for importing', $filename));
		}

		$blocklist = [];
		while (($data = fgetcsv($fp, 1000)) !== false) {
			$item = [
				'domain' => $data[0],
				'reason' => $data[1] ?? '',
			];
			if (!in_array($item, $blocklist)) {
				$blocklist[] = $item;
			}
		}

		return $blocklist;
	}
}
