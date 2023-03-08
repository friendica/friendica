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

namespace Friendica\Object\Log;

/**
 * Parse a log line and offer some utility methods
 */
class ParsedLogLine
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
		$this->logline = $logline;

		// if data is empty is serialized as '[]'. To ease the parsing
		// let's replace it with '{""}'. It will be replaced by null later
		$logline = str_replace(' [] - {', ' {""} - {', $logline);


		if (strstr($logline, ' - {') === false) {
			// the log line is not well formed
			$jsonsource = null;
		} else {
			// here we hope that there will not be the string ' - {' inside the $jsonsource value
			list($logline, $jsonsource) = explode(' - {', $logline);
			$jsonsource                 = '{' . $jsonsource;
		}

		$jsondata = null;
		if (strpos($logline, '{"') > 0) {
			list($logline, $jsondata) = explode('{"', $logline, 2);

			$jsondata = '{"' . $jsondata;
		}

		preg_match(self::REGEXP, $logline, $matches);

		if (count($matches) == 0) {
			// regexp not matching
			$this->message = $this->logline;
		} else {
			$this->date    = $matches[1];
			$this->context = $matches[2];
			$this->level   = $matches[3];
			$this->message = $matches[4];
			$this->data    = $jsondata == '{""}' ? null : $jsondata;
			$this->source  = $jsonsource;
			$this->tryfixjson();
		}

		$this->message = trim($this->message);
	}

	/**
	 * Fix message / data split
	 *
	 * In log boundary between message and json data is not specified.
	 * If message  contains '{' the parser thinks there starts the json data.
	 * This method try to parse the found json and if it fails, search for next '{'
	 * in json data and retry
	 */
	private function tryfixjson()
	{
		if (is_null($this->data) || $this->data == '') {
			return;
		}
		try {
			$d = json_decode($this->data, true, 512, JSON_THROW_ON_ERROR);
		} catch (\JsonException $e) {
			// try to find next { in $str and move string before to 'message'

			$pos = strpos($this->data, '{', 1);
			if ($pos === false) {
				$this->message .= $this->data;
				$this->data = null;
				return;
			}

			$this->message .= substr($this->data, 0, $pos);
			$this->data = substr($this->data, $pos);
			$this->tryfixjson();
		}
	}

	/**
	 * Return decoded `data` as array suitable for template
	 *
	 * @return array
	 */
	public function getData()
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
	public function getSource()
	{
		return json_decode($this->source, true);
	}
}
