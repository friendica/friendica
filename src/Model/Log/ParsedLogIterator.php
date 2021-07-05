<?php
/**
 * @copyright Copyright (C) 2021, Friendica
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

namespace Friendica\Model\Log;

use Friendica\Util\ReversedFileReader;
use Friendica\Object\Log\ParsedLog;

/**
 * An iterator which returns `\Friendica\Objec\Log\ParsedLog` instances
 *
 * Uses `\Friendica\Util\ReversedFileReader` to fetch log lines
 * from newest to oldest.
 */
class ParsedLogIterator implements \Iterator
{
	/** @var \Iterator */
	private $reader;

	/** @var ParsedLog current iterator value*/
	private $value;

	/** @var int max number of lines to read */
	private $limit;

	/** @var array filters per column */
	private $filters;

	/** @var string search term */
	private $search;


	/**
	 * @param string $filename	File to open
	 * @param int $limit		Max num of lines to read
	 * @param array $filter		filters per column
	 * @param string $search	string to search to filter lines
	 */
	public function __construct(string $filename, int $limit = 0, array $filters = [], string $search = "")
	{
		$this->reader  = new ReversedFileReader($filename);
		$this->value   = null;
		$this->limit   = $limit;
		$this->filters = $filters;
		$this->search  = $search;
	}

	/**
	 * Check if parsed log line match filters.
	 * Always match if no filters are set.
	 *
	 * @param ParsedLog $parsedlog
	 * @return bool
	 */
	private function filter($parsedlog)
	{
		$match = true;
		foreach ($this->filters as $filter => $filtervalue) {
			switch ($filter) {
				case "level":
					$match = $match && ($parsedlog->level == strtoupper($filtervalue));
					break;
				case "context":
					$match = $match && ($parsedlog->context == $filtervalue);
					break;
			}
		}
		return $match;
	}

	/**
	 * Check if parsed log line match search.
	 * Always match if no search query is set.
	 *
	 * @param ParsedLog $parsedlog
	 * @return bool
	 */
	private function search($parsedlog)
	{
		if ($this->search != "") {
			return strstr($parsedlog->logline, $this->search) !== false;
		}
		return true;
	}

	/**
	 * Read a line from reader and parse.
	 * Returns null if limit is reached or the reader is invalid.
	 *
	 * @param ParsedLog $parsedlog
	 * @return ?ParsedLog
	 */
	private function read()
	{
		$this->reader->next();
		if ($this->limit > 0 && $this->reader->key() > $this->limit || !$this->reader->valid()) {
			return null;
		}

		$line = $this->reader->current();
		return new ParsedLog($this->reader->key(), $line);
	}

	public function next()
	{
		$parsed = $this->read();

		// if read() has not retuned none and
		// the line don't match filters or search
		// 	read the next line
		while (is_null($parsed) == false && !($this->filter($parsed) && $this->search($parsed))) {
			$parsed = $this->read();
		}
		$this->value = $parsed;
	}

	public function rewind()
	{
		$this->value = null;
		$this->reader->rewind();
		$this->next();
	}

	public function key()
	{
		return $this->reader->key();
	}

	public function current()
	{
		return $this->value;
	}

	public function valid()
	{
		return ! is_null($this->value);
	}
}
