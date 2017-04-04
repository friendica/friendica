<?php
function convertquote($body, $reply)
{
	// Convert Quotes
	$arrbody = explode("\n", trim($body));
	$arrlevel = array();

	for ($i = 0; $i < count($arrbody); $i++) {
		$quotelevel = 0;
		$quoteline = $arrbody[$i];

		while ((strlen($quoteline)>0) and ((substr($quoteline, 0, 1) == '>')
			or (substr($quoteline, 0, 1) == ' '))) {
			if (substr($quoteline, 0, 1) == '>')
				$quotelevel++;

			$quoteline = ltrim(substr($quoteline, 1));
		}

		//echo $quotelevel.'*'.$quoteline."\r\n";

		$arrlevel[$i] = $quotelevel;
		$arrbody[$i] = $quoteline;
	}

	$quotelevel = 0;
	$previousquote = 0;
	$arrbodyquoted = array();

	for ($i = 0; $i < count($arrbody); $i++) {

		$previousquote = $quotelevel;
		$quotelevel = $arrlevel[$i];
		$currline = $arrbody[$i];

		while ($previousquote < $quotelevel) {
			if ($sender != '') {
				$quote = "[quote title=$sender]";
				$sender = '';
			} else
				$quote = "[quote]";

			$arrbody[$i] = $quote.$arrbody[$i];
			$previousquote++;
		}

		while ($previousquote > $quotelevel) {
			$arrbody[$i] = '[/quote]'.$arrbody[$i];
			$previousquote--;
		}

		$arrbodyquoted[] = $arrbody[$i];
	}
	while ($quotelevel > 0) {
		$arrbodyquoted[] = '[/quote]';
		$quotelevel--;
	}

	$body = implode("\n", $arrbodyquoted);

	if (strlen($body) > 0)
		$body = $body."\n\n";

	if ($reply)
		$body = removetofu($body);

	return($body);
}

function removetofu($message)
{
	$message = trim($message);

	do {
		$oldmessage = $message;
		$message = preg_replace('=\[/quote\][\s](.*?)\[quote\]=i', '$1', $message);
		$message = str_replace("[/quote][quote]", "", $message);
	} while ($message != $oldmessage);

	$quotes = array();

	$startquotes = 0;

	$start = 0;

	while (($pos = strpos($message, '[quote', $start)) > 0) {
		$quotes[$pos] = -1;
		$start = $pos + 7;
		$startquotes++;
	}

	$endquotes = 0;
	$start = 0;

	while (($pos = strpos($message, '[/quote]', $start)) > 0) {
		$start = $pos + 7;
		$endquotes++;
	}

	while ($endquotes < $startquotes) {
		$message .= '[/quote]';
		++$endquotes;
	}

	$start = 0;

	while (($pos = strpos($message, '[/quote]', $start)) > 0) {
		$quotes[$pos] = 1;
		$start = $pos + 7;
	}

	if (strtolower(substr($message, -8)) != '[/quote]')
		return($message);

	krsort($quotes);

	$quotelevel = 0;
	$quotestart = 0;
	foreach ($quotes as $index => $quote) {
		$quotelevel += $quote;

		if (($quotelevel == 0) and ($quotestart == 0))
			$quotestart = $index;
	}

	if ($quotestart != 0) {
		$message = trim(substr($message, 0, $quotestart))."\n[spoiler]".substr($message, $quotestart+7, -8).'[/spoiler]';
	}

	return($message);
}
?>
