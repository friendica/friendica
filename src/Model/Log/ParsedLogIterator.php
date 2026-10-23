<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Model\Log;

use Friendica\Util\ReversedFileReader;
use Friendica\Object\Log\ParsedLogLine;

/**
 * An iterator which returns `\Friendica\Object\Log\ParsedLogLine` instances
 *
 * Uses `\Friendica\Util\ReversedFileReader` to fetch log lines
 * from newest to oldest.
 */
class ParsedLogIterator implements \Iterator
{
	/** @var ParsedLogLine|null current iterator value*/
	private $value = null;

	/** @var int max number of lines to return */
	private $limit = 0;

	/** @var int max number of lines to read */
	private $scanLimit = 0;

	/** @var int number of lines returned so far */
	private $count = 0;

	/** @var bool true if reading stopped because of the scan limit */
	private $scanLimitReached = false;

	/** @var int max number of seconds to read */
	private $timeout = 0;

	/** @var float time when reading started */
	private $start = 0;

	/** @var bool true if reading stopped because of the timeout */
	private $timeoutReached = false;

	/** @var array filters per column */
	private $filters = [];

	/** @var string search term */
	private $search = '';

	/** @var string request id whose lines are skipped */
	private $excludedRequestId = '';

	public function __construct(private readonly ReversedFileReader $reader) {}

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
	 * @param int $limit		Max num of lines to return
	 * @return $this
	 */
	public function withLimit(int $limit): ParsedLogIterator
	{
		$this->limit = $limit;
		return $this;
	}

	/**
	 * @param int $scanLimit	Max num of lines to read
	 * @return $this
	 */
	public function withScanLimit(int $scanLimit): ParsedLogIterator
	{
		$this->scanLimit = $scanLimit;
		return $this;
	}

	/**
	 * @param int $timeout		Max num of seconds to read
	 * @return $this
	 */
	public function withTimeout(int $timeout): ParsedLogIterator
	{
		$this->timeout = $timeout;
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
	 * Used to skip the lines of the request that displays the log,
	 * since they contain the search term in the requested URL.
	 *
	 * @param string $requestId	request id whose lines are skipped
	 * @return $this
	 */
	public function withExcludedRequestId(string $requestId): ParsedLogIterator
	{
		$this->excludedRequestId = $requestId;
		return $this;
	}

	/**
	 * @return bool true if reading stopped because of the scan limit
	 */
	public function isScanLimitReached(): bool
	{
		return $this->scanLimitReached;
	}

	/**
	 * @return bool true if reading stopped because of the timeout
	 */
	public function isTimeoutReached(): bool
	{
		return $this->timeoutReached;
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
					$match = $match && ($parsedlogline->level == strtoupper((string) $filtervalue));
					break;

				case 'context':
					$match = $match && ($parsedlogline->context == $filtervalue);
					break;
			}
		}
		return $match;
	}

	/**
	 * Quick check on the raw log line to avoid parsing lines that can't match the filters
	 *
	 * @param string $logline
	 * @return bool
	 */
	private function prefilter(string $logline): bool
	{
		if (!empty($this->filters['level']) && !str_contains($logline, ' [' . strtoupper((string) $this->filters['level']) . ']: ')) {
			return false;
		}

		if (!empty($this->filters['context']) && !str_contains($logline, ' ' . $this->filters['context'] . ' [')) {
			return false;
		}
		return true;
	}

	/**
	 * Check if the raw log line match search.
	 * Always match if no search query is set.
	 *
	 * @param string $logline
	 * @return bool
	 */
	private function search(string $logline): bool
	{
		// The lines of the current request contain the search term in the requested URL
		if ($this->excludedRequestId !== '' && str_contains($logline, '"request-id":"' . $this->excludedRequestId . '"')) {
			return false;
		}

		if ($this->search != '') {
			return str_contains($logline, $this->search);
		}
		return true;
	}

	/**
	 * Read the next line from reader which matches the search and parse it.
	 * Returns null if scan limit is reached or the reader is invalid.
	 *
	 * @return ?ParsedLogLine
	 */
	private function read()
	{
		do {
			$this->reader->next();
			if (!$this->reader->valid()) {
				return null;
			}

			if ($this->scanLimit > 0 && $this->reader->key() > $this->scanLimit) {
				$this->scanLimitReached = true;
				return null;
			}

			if ($this->timeout > 0 && microtime(true) - $this->start > $this->timeout) {
				$this->timeoutReached = true;
				return null;
			}

			$line = $this->reader->current();
		} while (!$this->prefilter($line) || !$this->search($line));

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
		if ($this->limit > 0 && $this->count >= $this->limit) {
			$this->value = null;
			return;
		}

		$parsed = $this->read();

		while (is_null($parsed) == false && !$this->filter($parsed)) {
			$parsed = $this->read();
		}
		$this->value = $parsed;

		if (!is_null($parsed)) {
			$this->count++;
		}
	}


	/**
	 * Rewind the iterator to the first matching log line
	 *
	 * @see Iterator::rewind()
	 * @return void
	 */
	public function rewind(): void
	{
		$this->value            = null;
		$this->count            = 0;
		$this->scanLimitReached = false;
		$this->timeoutReached   = false;
		$this->start            = microtime(true);
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
