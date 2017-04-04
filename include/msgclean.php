<?php

function savereplace($pattern, $replace, $text)
{
	$save = $text;

	$text = preg_replace($pattern, $replace, $text);

	if ($text == '')
		$text = $save;
	return($text);
}

function unifyattributionline($message)
{
	$quotestr = array('quote', 'spoiler');
	foreach ($quotestr as $quote) {

		$message = savereplace('/----- Original Message -----\s.*?From: "([^<"].*?)" <(.*?)>\s.*?To: (.*?)\s*?Cc: (.*?)\s*?Sent: (.*?)\s.*?Subject: ([^\n].*)\s*\['.$quote.'\]/i', "[".$quote."='$1']\n", $message);
		$message = savereplace('/----- Original Message -----\s.*?From: "([^<"].*?)" <(.*?)>\s.*?To: (.*?)\s*?Sent: (.*?)\s.*?Subject: ([^\n].*)\s*\['.$quote.'\]/i', "[".$quote."='$1']\n", $message);

		$message = savereplace('/-------- Original-Nachricht --------\s*\['.$quote.'\]\nDatum: (.*?)\nVon: (.*?) <(.*?)>\nAn: (.*?)\nBetreff: (.*?)\n/i', "[".$quote."='$2']\n", $message);
		$message = savereplace('/-------- Original-Nachricht --------\s*\['.$quote.'\]\sDatum: (.*?)\s.*Von: "([^<"].*?)" <(.*?)>\s.*An: (.*?)\n.*/i', "[".$quote."='$2']\n", $message);
		$message = savereplace('/-------- Original-Nachricht --------\s*\['.$quote.'\]\nDatum: (.*?)\nVon: (.*?)\nAn: (.*?)\nBetreff: (.*?)\n/i', "[".$quote."='$2']\n", $message);

		$message = savereplace('/-----Urspr.*?ngliche Nachricht-----\sVon: "([^<"].*?)" <(.*?)>\s.*Gesendet: (.*?)\s.*An: (.*?)\s.*Betreff: ([^\n].*?).*:\s*\['.$quote.'\]/i', "[".$quote."='$1']\n", $message);
		$message = savereplace('/-----Urspr.*?ngliche Nachricht-----\sVon: "([^<"].*?)" <(.*?)>\s.*Gesendet: (.*?)\s.*An: (.*?)\s.*Betreff: ([^\n].*?)\s*\['.$quote.'\]/i', "[".$quote."='$1']\n", $message);

		$message = savereplace('/Am (.*?), schrieb (.*?):\s*\['.$quote.'\]/i', "[".$quote."='$2']\n", $message);

		$message = savereplace('/Am .*?, \d+ .*? \d+ \d+:\d+:\d+ \+\d+\sschrieb\s(.*?)\s<(.*?)>:\s*\['.$quote.'\]/i', "[".$quote."='$1']\n", $message);

		$message = savereplace('/Am (.*?) schrieb (.*?) <(.*?)>:\s*\['.$quote.'\]/i', "[".$quote."='$2']\n", $message);
		$message = savereplace('/Am (.*?) schrieb <(.*?)>:\s*\['.$quote.'\]/i', "[".$quote."='$2']\n", $message);
		$message = savereplace('/Am (.*?) schrieb (.*?):\s*\['.$quote.'\]/i', "[".$quote."='$2']\n", $message);
		$message = savereplace('/Am (.*?) schrieb (.*?)\n(.*?):\s*\['.$quote.'\]/i', "[".$quote."='$2']\n", $message);

		$message = savereplace('/(\d+)\/(\d+)\/(\d+) ([^<"].*?) <(.*?)>\s*\['.$quote.'\]/i', "[".$quote."='$4']\n", $message);

		$message = savereplace('/On .*?, \d+ .*? \d+ \d+:\d+:\d+ \+\d+\s(.*?)\s<(.*?)>\swrote:\s*\['.$quote.'\]/i', "[".$quote."='$1']\n", $message);

		$message = savereplace('/On (.*?) at (.*?), (.*?)\s<(.*?)>\swrote:\s*\['.$quote.'\]/i', "[".$quote."='$3']\n", $message);
		$message = savereplace('/On (.*?)\n([^<].*?)\s<(.*?)>\swrote:\s*\['.$quote.'\]/i', "[".$quote."='$2']\n", $message);
		$message = savereplace('/On (.*?), (.*?), (.*?)\s<(.*?)>\swrote:\s*\['.$quote.'\]/i', "[".$quote."='$3']\n", $message);
		$message = savereplace('/On ([^,].*?), (.*?)\swrote:\s*\['.$quote.'\]/i', "[".$quote."='$2']\n", $message);
		$message = savereplace('/On (.*?), (.*?)\swrote\s*\['.$quote.'\]/i', "[".$quote."='$2']\n", $message);

		// Der loescht manchmal den Body - was eigentlich unmoeglich ist
		$message = savereplace('/On (.*?),(.*?),(.*?),(.*?), (.*?) wrote:\s*\['.$quote.'\]/i', "[".$quote."='$5']\n", $message);

		$message = savereplace('/Zitat von ([^<].*?) <(.*?)>:\s*\['.$quote.'\]/i', "[".$quote."='$1']\n", $message);

		$message = savereplace('/Quoting ([^<].*?) <(.*?)>:\s*\['.$quote.'\]/i', "[".$quote."='$1']\n", $message);

		$message = savereplace('/From: "([^<"].*?)" <(.*?)>\s*\['.$quote.'\]/i', "[".$quote."='$1']\n", $message);
		$message = savereplace('/From: <(.*?)>\s*\['.$quote.'\]/i', "[".$quote."='$1']\n", $message);

		$message = savereplace('/Du \(([^)].*?)\) schreibst:\s*\['.$quote.'\]/i', "[".$quote."='$1']\n", $message);

		$message = savereplace('/--- (.*?) <.*?> schrieb am (.*?):\s*\['.$quote.'\]/i', "[".$quote."='$1']\n", $message);
		$message = savereplace('/--- (.*?) schrieb am (.*?):\s*\['.$quote.'\]/i', "[".$quote."='$1']\n", $message);

		$message = savereplace('/\* (.*?) <(.*?)> hat geschrieben:\s*\['.$quote.'\]/i', "[".$quote."='$1']\n", $message);

		$message = savereplace('/(.*?) <(.*?)> schrieb (.*?)\):\s*\['.$quote.'\]/i', "[".$quote."='$1']\n", $message);
		$message = savereplace('/(.*?) <(.*?)> schrieb am (.*?) um (.*):\s*\['.$quote.'\]/i', "[".$quote."='$1']\n", $message);
		$message = savereplace('/(.*?) schrieb am (.*?) um (.*):\s*\['.$quote.'\]/i', "[".$quote."='$1']\n", $message);
		$message = savereplace('/(.*?) \((.*?)\) schrieb:\s*\['.$quote.'\]/i', "[".$quote."='$2']\n", $message);
		$message = savereplace('/(.*?) schrieb:\s*\['.$quote.'\]/i', "[".$quote."='$1']\n", $message);

		$message = savereplace('/(.*?) <(.*?)> writes:\s*\['.$quote.'\]/i', "[".$quote."='$1']\n", $message);
		$message = savereplace('/(.*?) \((.*?)\) writes:\s*\['.$quote.'\]/i', "[".$quote."='$2']\n", $message);
		$message = savereplace('/(.*?) writes:\s*\['.$quote.'\]/i', "[".$quote."='$1']\n", $message);

		$message = savereplace('/\* (.*?) wrote:\s*\['.$quote.'\]/i', "[".$quote."='$1']\n", $message);
		$message = savereplace('/(.*?) wrote \(.*?\):\s*\['.$quote.'\]/i', "[".$quote."='$1']\n", $message);
		$message = savereplace('/(.*?) wrote:\s*\['.$quote.'\]/i', "[".$quote."='$1']\n", $message);

		$message = savereplace('/([^<].*?) <.*?> hat am (.*?)\sum\s(.*)\sgeschrieben:\s*\['.$quote.'\]/i', "[".$quote."='$1']\n", $message);

		$message = savereplace('/(\d+)\/(\d+)\/(\d+) ([^<"].*?) <(.*?)>:\s*\['.$quote.'\]/i', "[".$quote."='$4']\n", $message);
		$message = savereplace('/(\d+)\/(\d+)\/(\d+) (.*?) <(.*?)>\s*\['.$quote.'\]/i', "[".$quote."='$4']\n", $message);
		$message = savereplace('/(\d+)\/(\d+)\/(\d+) <(.*?)>:\s*\['.$quote.'\]/i', "[".$quote."='$4']\n", $message);
		$message = savereplace('/(\d+)\/(\d+)\/(\d+) <(.*?)>\s*\['.$quote.'\]/i', "[".$quote."='$4']\n", $message);

		$message = savereplace('/(.*?) <(.*?)> schrubselte:\s*\['.$quote.'\]/i', "[".$quote."='$1']\n", $message);
		$message = savereplace('/(.*?) \((.*?)\) schrubselte:\s*\['.$quote.'\]/i', "[".$quote."='$2']\n", $message);

	}
	return($message);
}

function removegpg($message)
{

	$pattern = '/(.*)\s*-----BEGIN PGP SIGNED MESSAGE-----\s*[\r\n].*Hash:.*?[\r\n](.*)'.
		'[\r\n]\s*-----BEGIN PGP SIGNATURE-----\s*[\r\n].*'.
		'[\r\n]\s*-----END PGP SIGNATURE-----(.*)/is';

	preg_match($pattern, $message, $result);

	$cleaned = trim($result[1].$result[2].$result[3]);

	$cleaned = str_replace(array("\n- --\n", "\n- -"), array("\n-- \n", "\n-"), $cleaned);


	if ($cleaned == '')
		$cleaned = $message;

	return($cleaned);
}

function removesig($message)
{
	$sigpos = strrpos($message, "\n-- \n");
	$quotepos = strrpos($message, "[/quote]");

	if ($sigpos == 0) {
		// Speziell fuer web.de, die das als Trenner verwenden
		$message = str_replace("\n___________________________________________________________\n", "\n-- \n", $message);
		$sigpos = strrpos($message, "\n-- \n");
		$quotepos = strrpos($message, "[/quote]");
	}

	// Sollte sich der Signaturtrenner innerhalb eines Quotes befinden
	// wird keine Signaturtrennung ausgefuehrt
	if (($sigpos < $quotepos) and ($sigpos != 0))
		return(array('body' => $message, 'sig' => ''));

	/// @TODO Regexp umstellen, so dass auf 1 oder kein Leerzeichen
	/// geprueft wird
	/// $message = str_replace("\n--\n", "\n-- \n", $message);

	$pattern = '/(.*)[\r\n]-- [\r\n](.*)/is';

	preg_match($pattern, $message, $result);

	if (($result[1] != '') and ($result[2] != '')) {
		$cleaned = trim($result[1])."\n";
		$sig = trim($result[2]);
		//	'[hr][size=x-small][color=darkblue]'.trim($result[2]).'[/color][/size]';
	} else {
		$cleaned = $message;
		$sig = '';
	}

	return(array('body' => $cleaned, 'sig' => $sig));
}

function removelinebreak($message)
{
	$arrbody = explode("\n", trim($message));

	$lines = array();
	$lineno = 0;

	foreach ($arrbody as $i => $line) {
		$currquotelevel = 0;
		$currline = $line;
		while ((strlen($currline)>0) and ((substr($currline, 0, 1) == '>')
 			or (substr($currline, 0, 1) == ' '))) {
			if (substr($currline, 0, 1) == '>')
				$currquotelevel++;

			$currline = ltrim(substr($currline, 1));
		}

		$quotelevel = 0;
		$nextline = trim($arrbody[$i+1]);
		while ((strlen($nextline)>0) and ((substr($nextline, 0, 1) == '>')
 			or (substr($nextline, 0, 1) == ' '))) {
			if (substr($nextline, 0, 1) == '>')
				$quotelevel++;

			$nextline = ltrim(substr($nextline, 1));
		}

		$len = strlen($line);
		$firstword = strpos($nextline.' ', ' ');

		$specialchars = ((substr(trim($nextline), 0, 1) == '-') or
				(substr(trim($nextline), 0, 1) == '=') or
				(substr(trim($nextline), 0, 1) == '*') or
				(substr(trim($nextline), 0, 1) == '·') or
				(substr(trim($nextline), 0, 4) == '[url') or
				(substr(trim($nextline), 0, 5) == '[size') or
				(substr(trim($nextline), 0, 7) == 'http://') or
				(substr(trim($nextline), 0, 8) == 'https://'));

		if (!$specialchars) 
			$specialchars = ((substr(rtrim($line), -1) == '-') or
					(substr(rtrim($line), -1) == '=') or
					(substr(rtrim($line), -1) == '*') or
					(substr(rtrim($line), -1) == '·') or
					(substr(rtrim($line), -6) == '[/url]') or
					(substr(rtrim($line), -7) == '[/size]'));

		//if ($specialchars)
		//	echo ("Special\n");

		if ($lines[$lineno] != '') {
			if (substr($lines[$lineno], -1) != ' ')
			$lines[$lineno] .= ' ';

			while ((strlen($line)>0) and ((substr($line, 0, 1) == '>')
 			or (substr($line, 0, 1) == ' '))) {

				$line = ltrim(substr($line, 1));
			}

		}
		//else
		//	$lines[$lineno] = $quotelevel.'-'.$len.'-'.$firstword.'-';

		$lines[$lineno] .= $line;
		//if ((($len + $firstword < 68) and (substr($line, -1, 1) != ' '))
		//	or ($quotelevel != $currquotelevel) or $specialchars)
		if (((substr($line, -1, 1) != ' '))
			or ($quotelevel != $currquotelevel))
			$lineno++;
	}
	return(implode("\n", $lines));

}
?>
