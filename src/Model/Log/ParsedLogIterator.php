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

namespace Friendica\Model\Log;

use Friendica\Util\ReversedFileReader;
use Friendica\Object\Log\ParsedLogLine;

/**
 * An iterator which returns `\Friendica\Objec\Log\ParsedLogLine` instances
 *
 * Uses `\Friendica\Util\ReversedFileReader` to fetch log lines
 * from newest to oldest.
 */
class ParsedLogIterator implements \Iterator
{
	/** @var \Iterator */
	private $reader;

	/** @var ParsedLogLine current iterator value*/
	private $value = null;

	/** @var int max number of lines to read */
	private $limit = 0;

	/** @var array filters per column */
	private $filters = [];

	/** @var string search term */
	private $search = '';


	/**
	 * @param ReversedFileReader $reader
	 */
	public function __construct(ReversedFileReader $reader)
	{
		$this->reader = $reader;
	}

	/**
	 * @param string $filename	File to open
	 * @return $this
	 */
	public function open(string $filename): ParsedLogIterator
	{
		$this->reader->open($filename);
		return $this;
	}

	/**
	 * @param int $limit		Max num of lines to read
	 * @return $this
	 */
	public function withLimit(int $limit): ParsedLogIterator
	{
		$this->limit = $limit;
		return $this;
	}

	/**
	 * @param array $filters		filters per column
	 * @return $this
	 */
	public function withFilters(array $filters): ParsedLogIterator
	{
		$this->filters = $filters;
		return $this;
	}

	/**
	 * @param string $search	string to search to filter lines
	 * @return $this
	 */
	public function withSearch(string $search): ParsedLogIterator
	{
		$this->search = $search;
		return $this;
	}

	/**
	 * Check if parsed log line match filters.
	 * Always match if no filters are set.
	 *
	 * @param ParsedLogLine $parsedlogline ParsedLogLine instance
	 * @return bool Wether the parse log line matches
	 */
	private function filter(ParsedLogLine $parsedlogline): bool
	{
		$match = true;
		foreach ($this->filters as $filter => $filtervalue) {
			switch ($filter) {
				case 'level':
					$match = $match && ($parsedlogline->level == strtoupper($filtervalue));
					break;

				case 'context':
					$match = $match && ($parsedlogline->context == $filtervalue);
					break;
			}
		}
		return $match;
	}

	/**
	 * Check if parsed log line match search.
	 * Always match if no search query is set.
	 *
	 * @param ParsedLogLine $parsedlogline
	 * @return bool
	 */
	private function search(ParsedLogLine $parsedlogline): bool
	{
		if ($this->search != '') {
			return strstr($parsedlogline->logline, $this->search) !== false;
		}
		return true;
	}

	/**
	 * Read a line from reader and parse.
	 * Returns null if limit is reached or the reader is invalid.
	 *
	 * @return ?ParsedLogLine
	 */
	private function read()
	{
		$this->reader->next();
		if ($this->limit > 0 && $this->reader->key() > $this->limit || !$this->reader->valid()) {
			return null;
		}

		$line = $this->reader->current();
		return new ParsedLogLine($this->reader->key(), $line);
	}


	/**
	 * Fetch next parsed log line which match with filters or search and
	 * set it as current iterator value.
	 *
	 * @see Iterator::next()
	 * @return void
	 */
	public function next(): void
	{
		$parsed = $this->read();

		while (is_null($parsed) == false && !($this->filter($parsed) && $this->search($parsed))) {
			$parsed = $this->read();
		}
		$this->value = $parsed;
	}


	/**
	 * Rewind the iterator to the first matching log line
	 *
	 * @see Iterator::rewind()
	 * @return void
	 */
	public function rewind(): void
	{
		$this->value = null;
		$this->reader->rewind();
		$this->next();
	}

	/**
	 * Return current parsed log line number
	 *
	 * @see Iterator::key()
	 * @see ReversedFileReader::key()
	 * @return int
	 */
	public function key(): int
	{
		return $this->reader->key();
	}

	/**
	 * Return current iterator value
	 *
	 * @see Iterator::current()
	 * @return ?ParsedLogLine
	 */
	public function current(): ?ParsedLogLine
	{
		return $this->value;
	}

	/**
	 * Checks if current iterator value is valid, that is, not null
	 *
	 * @see Iterator::valid()
	 * @return bool
	 */
	public function valid(): bool
	{
		return !is_null($this->value);
	}
}
