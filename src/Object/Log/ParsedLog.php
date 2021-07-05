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

namespace Friendica\Object\Log;

/**
 * Parse a log line and offer some utility methods
 */
class ParsedLog
{
	const REGEXP = '/^(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[^ ]*) (\w+) \[(\w*)\]: (.*)/';

	/** @var int */
	public $id = 0;

	/** @var string */
	public $date = null;

	/** @var string */
	public $context = null;

	/** @var string */
	public $level = null;

	/** @var string */
	public $message = null;

	/** @var string */
	public $data = null;

	/** @var string */
	public $source = null;

	/** @var string */
	public $logline;

	/**
	 * @param int line id
	 * @param string $logline Source log line to parse
	 */
	public function __construct(int $id, string $logline)
	{
		$this->id = $id;
		$this->parse($logline);
	}

	private function parse($logline)
	{
		list($logline, $jsonsource) = explode(' - ', $logline);

		$jsondata = null;

		if (strpos($logline, '{"') > 0) {
			list($logline, $jsondata) = explode('{"', $logline, 2);

			$jsondata = '{"' . $jsondata;
		}
		preg_match(self::REGEXP, $logline, $matches);

		$this->date    = $matches[1];
		$this->context = $matches[2];
		$this->level   = $matches[3];
		$this->message = $matches[4];
		$this->data    = $jsondata;
		$this->source  = $jsonsource;
		$this->try_fix_json();

		$this->logline = $logline;
	}

	/**
	 * Fix message / data split
	 *
	 * In log boundary between message and json data is not specified.
	 * If message  contains '{' the parser thinks there starts the json data.
	 * This method try to parse the found json and if it fails, search for next '{'
	 * in json data and retry
	 */
	private function try_fix_json()
	{
		if (is_null($this->data) || $this->data == "") {
			return;
		}
		try {
			$d = json_decode($this->data, true, 512, JSON_THROW_ON_ERROR);
		} catch (\JsonException $e) {
			// try to find next { in $str and move string before to 'message'

			$pos = strpos($this->data, '{', 1);

			$this->message .= substr($this->data, 0, $pos);
			$this->data = substr($this->data, $pos);
			$this->try_fix_json();
		}
	}

	/**
	 * Return decoded `data` as array suitable for template
	 *
	 * @return array
	 */
	public function get_data()
	{
		$data = json_decode($this->data, true);
		if ($data) {
			foreach ($data as $k => $v) {
				$data[$k] = print_r($v, true);
			}
		}
		return $data;
	}

	/**
	 * Return decoded `source` as array suitable for template
	 *
	 * @return array
	 */
	public function get_source()
	{
		return json_decode($this->source, true);
	}
}
