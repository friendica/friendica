<?php
require_once('include/html2plain.php');
require_once('include/msgclean.php');
require_once('include/quoteconvert.php');

function email_connect($mailbox,$username,$password) {
	if (! function_exists('imap_open'))
		return false;

	$mbox = @imap_open($mailbox,$username,$password);

	return $mbox;
}

function email_poll($mbox,$email_addr) {

	if (! ($mbox && $email_addr))
		return array();

	$search1 = @imap_search($mbox,'FROM "' . $email_addr . '"', SE_UID);
	if (! $search1)
		$search1 = array();

	$search2 = @imap_search($mbox,'TO "' . $email_addr . '"', SE_UID);
	if (! $search2)
		$search2 = array();

	$search3 = @imap_search($mbox,'CC "' . $email_addr . '"', SE_UID);
	if (! $search3)
		$search3 = array();

	$search4 = @imap_search($mbox,'BCC "' . $email_addr . '"', SE_UID);
	if (! $search4)
		$search4 = array();

	$res = array_unique(array_merge($search1,$search2,$search3,$search4));

	return $res;
}


function construct_mailbox_name($mailacct) {
	$ret = '{' . $mailacct['server'] . ((intval($mailacct['port'])) ? ':' . $mailacct['port'] : '');
	$ret .= (($mailacct['ssltype']) ?  '/' . $mailacct['ssltype'] . '/novalidate-cert' : '');
	$ret .= '}' . $mailacct['mailbox'];
	return $ret;
}


function email_msg_meta($mbox,$uid) {
	$ret = (($mbox && $uid) ? @imap_fetch_overview($mbox,$uid,FT_UID) : array(array())); // POSSIBLE CLEANUP --> array(array()) is probably redundant now
	return ((count($ret)) ? $ret : array());
}

function email_msg_headers($mbox,$uid) {
	$raw_header = (($mbox && $uid) ? @imap_fetchheader($mbox,$uid,FT_UID) : '');
	$raw_header = str_replace("\r",'',$raw_header);
	$ret = array();
	$h = explode("\n",$raw_header);
	if (count($h))
	foreach ($h as $line ) {
	    if (preg_match("/^[a-zA-Z]/", $line)) {
			$key = substr($line,0,strpos($line,':'));
			$value = substr($line,strpos($line,':')+1);

			$last_entry = strtolower($key);
			$ret[$last_entry] = trim($value);
		}
		else {
			$ret[$last_entry] .= ' ' . trim($line);
    	}
	}
	return $ret;
}


function email_get_msg($mbox,$uid, $reply) {
	$ret = array();

	$struc = (($mbox && $uid) ? @imap_fetchstructure($mbox,$uid,FT_UID) : null);

	if (! $struc)
		return $ret;

	if (! $struc->parts) {
		$ret['body'] = email_get_part($mbox,$uid,$struc,0, 'html');
		$html = $ret['body'];

		if (trim($ret['body']) == '')
			$ret['body'] = email_get_part($mbox,$uid,$struc,0, 'plain');
		else
			$ret['body'] = html2bbcode($ret['body']);
	}
	else {
		$text = '';
		$html = '';
		foreach ($struc->parts as $ptop => $p) {
			$x = email_get_part($mbox,$uid,$p,$ptop + 1, 'plain');
			if ($x) {
				$text .= $x;
			}

			$x = email_get_part($mbox,$uid,$p,$ptop + 1, 'html');
			if ($x) {
				$html .= $x;
			}
		}
		if (trim($html) != '') {
			$ret['body'] = html2bbcode($html);
		} else {
			$ret['body'] = $text;
		}
	}

	$ret['body'] = removegpg($ret['body']);
	$msg = removesig($ret['body']);
	$ret['body'] = $msg['body'];
	$ret['body'] = convertquote($ret['body'], $reply);

	if (trim($html) != '') {
		$ret['body'] = removelinebreak($ret['body']);
	}

	$ret['body'] = unifyattributionline($ret['body']);

	return $ret;
}

// At the moment - only return plain/text.
// Later we'll repackage inline images as data url's and make the HTML safe

function email_get_part($mbox,$uid,$p,$partno, $subtype) {
	// $partno = '1', '2', '2.1', '2.1.3', etc for multipart, 0 if simple
	global $htmlmsg,$plainmsg,$charset,$attachments;

	//echo $partno."\n";

	// DECODE DATA
	$data = ($partno)
		? @imap_fetchbody($mbox,$uid,$partno, FT_UID|FT_PEEK)
	: @imap_body($mbox,$uid,FT_UID|FT_PEEK);

	// Any part may be encoded, even plain text messages, so check everything.
	if ($p->encoding==4)
		$data = quoted_printable_decode($data);
	elseif ($p->encoding==3)
		$data = base64_decode($data);

	// PARAMETERS
	// get all parameters, like charset, filenames of attachments, etc.
	$params = array();
	if ($p->parameters)
		foreach ($p->parameters as $x)
			$params[strtolower($x->attribute)] = $x->value;
	if (isset($p->dparameters) and $p->dparameters)
		foreach ($p->dparameters as $x)
			$params[strtolower($x->attribute)] = $x->value;

	// ATTACHMENT
	// Any part with a filename is an attachment,
	// so an attached text file (type 0) is not mistaken as the message.

	if ((isset($params['filename']) and $params['filename']) || (isset($params['name']) and $params['name'])) {
		// filename may be given as 'Filename' or 'Name' or both
		$filename = ($params['filename'])? $params['filename'] : $params['name'];
		// filename may be encoded, so see imap_mime_header_decode()
		$attachments[$filename] = $data;  // this is a problem if two files have same name
	}

	// TEXT
	if ($p->type == 0 && $data) {
		// Messages may be split in different parts because of inline attachments,
		// so append parts together with blank row.
		if (strtolower($p->subtype)==$subtype) {
			$data = iconv($params['charset'], 'UTF-8//IGNORE', $data);
			return (trim($data) ."\n\n");
		} else
			$data = '';

 //           $htmlmsg .= $data ."<br><br>";
		$charset = $params['charset'];  // assume all parts are same charset
	}

	// EMBEDDED MESSAGE
	// Many bounce notifications embed the original message as type 2,
	// but AOL uses type 1 (multipart), which is not handled here.
	// There are no PHP functions to parse embedded messages,
	// so this just appends the raw source to the main message.
//	elseif ($p->type==2 && $data) {
//		$plainmsg .= $data."\n\n";
//	}

	// SUBPART RECURSION
	if (isset($p->parts) and $p->parts) {
		$x = "";
		foreach ($p->parts as $partno0=>$p2) {
			$x .=  email_get_part($mbox,$uid,$p2,$partno . '.' . ($partno0+1), $subtype);  // 1.2, 1.2.1, etc.
			//if ($x) {
			//	return $x;
			//}
		}
		return $x;
	}
}



function email_header_encode($in_str, $charset) {
	$out_str = $in_str;
	$need_to_convert = false;

	for ($x = 0; $x < strlen($in_str); $x ++) {
		if ((ord($in_str[$x]) == 0) || ((ord($in_str[$x]) > 128))) {
			$need_to_convert = true;
		}
	}

	if (! $need_to_convert)
		return $in_str;

    if ($out_str && $charset) {

        // define start delimimter, end delimiter and spacer
        $end = "?=";
        $start = "=?" . $charset . "?B?";
        $spacer = $end . "\r\n " . $start;

        // determine length of encoded text within chunks
        // and ensure length is even
        $length = 75 - strlen($start) - strlen($end);

        /*
            [EDIT BY danbrown AT php DOT net: The following
            is a bugfix provided by (gardan AT gmx DOT de)
            on 31-MAR-2005 with the following note:
            "This means: $length should not be even,
            but divisible by 4. The reason is that in
            base64-encoding 3 8-bit-chars are represented
            by 4 6-bit-chars. These 4 chars must not be
            split between two encoded words, according
            to RFC-2047.
        */
        $length = $length - ($length % 4);

        // encode the string and split it into chunks
        // with spacers after each chunk
        $out_str = base64_encode($out_str);
        $out_str = chunk_split($out_str, $length, $spacer);

        // remove trailing spacer and
        // add start and end delimiters
        $spacer = preg_quote($spacer,'/');
        $out_str = preg_replace("/" . $spacer . "$/", "", $out_str);
        $out_str = $start . $out_str . $end;
    }
    return $out_str;
}

/**
 * email_send is used by NETWORK_EMAIL and NETWORK_EMAIL2 code
 * (not to notify the user, but to send items to email contacts)
 *
 * @todo This could be changed to use the Emailer class
 */
function email_send($addr, $subject, $headers, $item) {
	//$headers .= 'MIME-Version: 1.0' . "\n";
	//$headers .= 'Content-Type: text/html; charset=UTF-8' . "\n";
	//$headers .= 'Content-Type: text/plain; charset=UTF-8' . "\n";
	//$headers .= 'Content-Transfer-Encoding: 8bit' . "\n\n";

	$part = uniqid("", true);

	$html    = prepare_body($item);

	$headers .= "Mime-Version: 1.0\n";
	$headers .= 'Content-Type: multipart/alternative; boundary="=_'.$part.'"'."\n\n";

	$body = "\n--=_".$part."\n";
	$body .= "Content-Transfer-Encoding: 8bit\n";
	$body .= "Content-Type: text/plain; charset=utf-8; format=flowed\n\n";

	$body .= html2plain($html)."\n";

	$body .= "--=_".$part."\n";
	$body .= "Content-Transfer-Encoding: 8bit\n";
	$body .= "Content-Type: text/html; charset=utf-8\n\n";

	$body .= '<html><head></head><body style="word-wrap: break-word; -webkit-nbsp-mode: space; -webkit-line-break: after-white-space; ">'.$html."</body></html>\n";

	$body .= "--=_".$part."--";

	//$message = '<html><body>' . $html . '</body></html>';
	//$message = html2plain($html);
	logger('notifier: email delivery to ' . $addr);
	mail($addr, $subject, $body, $headers);
}

function iri2msgid($iri) {
	if (!strpos($iri, "@"))
		$msgid = preg_replace("/urn:(\S+):(\S+)\.(\S+):(\d+):(\S+)/i", "urn!$1!$4!$5@$2.$3", $iri);
	else
		$msgid = $iri;
	return($msgid);
}

function msgid2iri($msgid) {
	if (strpos($msgid, "@"))
		$iri = preg_replace("/urn!(\S+)!(\d+)!(\S+)@(\S+)\.(\S+)/i", "urn:$1:$4.$5:$2:$3", $msgid);
	else
		$iri = $msgid;
	return($iri);
}
