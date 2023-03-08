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

namespace Friendica\Util;

/**
 * An iterator which returns lines from file in reversed order
 *
 * original code https://stackoverflow.com/a/10494801
 */
class ReversedFileReader implements \Iterator
{
	const BUFFER_SIZE = 4096;
	const SEPARATOR   = "\n";

	/** @var resource */
	private $fh = null;

	/** @var int */
	private $filesize = -1;

	/** @var int */
	private $pos = -1;

	/** @var array */
	private $buffer = null;

	/** @var int */
	private $key = -1;

	/** @var string */
	private $value = null;

	/**
	 * Open $filename for read and reset iterator
	 *
	 * @param string $filename	File to open
	 * @return $this
	 */
	public function open(string $filename): ReversedFileReader
	{
		$this->fh = fopen($filename, 'r');
		if (!$this->fh) {
			// this should use a custom exception.
			throw new \Exception("Unable to open $filename");
		}
		$this->filesize = filesize($filename);
		$this->pos      = -1;
		$this->buffer   = null;
		$this->key      = -1;
		$this->value    = null;
		return $this;
	}

	/**
	 * Read $size bytes behind last position
	 *
	 * @param int $size
	 * @return string
	 */
	private function _read(int $size): string
	{
		$this->pos -= $size;
		fseek($this->fh, $this->pos);
		return fread($this->fh, $size);
	}

	/**
	 * Read next line from end of file
	 * Return null if no lines are left to read
	 *
	 * @return string|null Depending on data being buffered
	 */
	private function _readline()
	{
		$buffer = & $this->buffer;
		while (true) {
			if ($this->pos == 0) {
				return array_pop($buffer);
			}
			if (is_null($buffer)) {
				return null;
			}
			if (count($buffer) > 1) {
				return array_pop($buffer);
			}
			$buffer = explode(self::SEPARATOR, $this->_read(self::BUFFER_SIZE) . $buffer[0]);
		}
	}

	/**
	 * Fetch next line from end and set it as current iterator value.
	 *
	 * @see Iterator::next()
	 * @return void
	 */
	public function next()
	{
		++$this->key;
		$this->value = $this->_readline();
	}

	/**
	 * Rewind iterator to the first line at the end of file
	 *
	 * @see Iterator::rewind()
	 * @return void
	 */
	public function rewind()
	{
		if ($this->filesize > 0) {
			$this->pos    = $this->filesize;
			$this->value  = null;
			$this->key    = -1;
			$this->buffer = explode(self::SEPARATOR, $this->_read($this->filesize % self::BUFFER_SIZE ?: self::BUFFER_SIZE));
			$this->next();
		}
	}

	/**
	 * Return current line number, starting from zero at the end of file
	 *
	 * @see Iterator::key()
	 * @return int
	 */
	public function key(): int
	{
		return $this->key;
	}

	/**
	 * Return current line
	 *
	 * @see Iterator::current()
	 * @return string
	 */
	public function current(): string
	{
		return $this->value;
	}

	/**
	 * Checks if current iterator value is valid, that is, we readed all lines in files
	 *
	 * @see Iterator::valid()
	 * @return bool
	 */
	public function valid(): bool
	{
		return ! is_null($this->value);
	}
}
