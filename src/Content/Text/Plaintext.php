<?php
/**
 * @file src/Content/Text/Plaintext.php
 */
namespace Friendica\Content\Text;

class Plaintext
{
	/**
	 * Shortens message
	 *
	 * @param  string $msg
	 * @param  int    $limit
	 * @return string
	 *
	 * @todo For Twitter URLs aren't shortened, but they have to be calculated as if.
	 */
	public static function shorten($msg, $limit)
	{
		$lines = explode("\n", $msg);
		$msg = "";
		$recycle = html_entity_decode("&#x2672; ", ENT_QUOTES, 'UTF-8');
		$ellipsis = html_entity_decode("&#x2026;", ENT_QUOTES, 'UTF-8');
		foreach ($lines as $row => $line) {
			if (iconv_strlen(trim($msg . "\n" . $line), "UTF-8") <= $limit) {
				$msg = trim($msg . "\n" . $line);
			} elseif (($msg == "") || (($row == 1) && (substr($msg, 0, 4) == $recycle))) {
				// Is the new message empty by now or is it a reshared message?
				$msg = iconv_substr(iconv_substr(trim($msg . "\n" . $line), 0, $limit, "UTF-8"), 0, -3, "UTF-8") . $ellipsis;
			} else {
				break;
			}
		}

		return $msg;
	}

	/**
	 * Returns the character positions of the provided boundaries, optionally skipping a number of first occurrences
	 *
	 * @param string $text        Text to search
	 * @param string $open        Left boundary
	 * @param string $close       Right boundary
	 * @param int    $occurrences Number of first occurrences to skip
	 * @return boolean|array
	 */
	public static function getBoundariesPosition($text, $open, $close, $occurrences = 0)
	{
		if ($occurrences < 0) {
			$occurrences = 0;
		}

		$start_pos = -1;
		for ($i = 0; $i <= $occurrences; $i++) {
			if ($start_pos !== false) {
				$start_pos = strpos($text, $open, $start_pos + 1);
			}
		}

		if ($start_pos === false) {
			return false;
		}

		$end_pos = strpos($text, $close, $start_pos);

		if ($end_pos === false) {
			return false;
		}

		$res = ['start' => $start_pos, 'end' => $end_pos];

		return $res;
	}
}
