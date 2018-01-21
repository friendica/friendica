#!/usr/bin/env php
<?php

/**
 * @brief tool to silence accounts on the global community page
 *
 * With this tool, you can silence an account on the global community page.
 * Postings from silenced accounts will not be displayed on the community
 * page. This silencing does only affect the display on the community page,
 * accounts following the silenced accounts will still get their postings.
 *
 * Usage: pass the URL of the profile to be silenced account as only parameter
 *        at the command line when running this tool. E.g.
 *
 *        $> util/global_community_silence.php http://example.com/profile/bob
 *
 *        will silence bob@example.com so that his postings won't appear at
 *        the global community page.
 *
 * Author: Tobias Diekershoff
 *
 * License: AGPLv3 or later, same as Friendica
 **/

if ($argc != 2 || $argv[1] == "-h" || $argv[1] == "--help" || $argv[1] == "-?") {
	echo "Usage: ".$argv[0]." [-h|profile_url]\r\n";
	echo "    -h, -?, --help ... show this help\r\n";
	echo "    profile_url ...... The URL of the profile you want to silence\r\n";
	echo "\r\n";
	echo "Example: Silence bob@example.com\r\n";
	echo "$> ".$argv[0]." https://example.com/profiles/bob\r\n";
	echo "\r\n";
	exit(0);
}

use Friendica\Database\DBM;
use Friendica\Network\Probe;

require_once 'boot.php';
require_once 'include/dba.php';
require_once 'include/text.php';
$a = get_app();
require_once '.htconfig.php';

dba::connect($db_host, $db_user, $db_pass, $db_data);
unset($db_host, $db_user, $db_pass, $db_data);

/**
 * 1. make nurl from last parameter
 * 2. check DB (contact) if there is a contact with uid=0 and that nurl, get the ID
 * 3. set the flag hidden=1 for the contact entry with the found ID
 **/
$net = Probe::uri($argv[1]);
if (in_array($net['network'], [NETWORK_PHANTOM, NETWORK_MAIL])) {
	echo "This account seems not to exist.";
	echo "\r\n";
	exit(1);
}
$nurl = normalise_link($net['url']);
$contact = dba::selectFirst("contact", ["id"], ["nurl" => $nurl, "uid" => 0]);
if (DBM::isResult($contact)) {
	dba::update("contact", ["hidden" => true], ["id" => $contact["id"]]);
	echo "NOTICE: The account should be silenced from the global community page\r\n";
} else {
	echo "NOTICE: Could not find any entry for this URL (".$nurl.")\r\n";
}

?>
