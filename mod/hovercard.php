<?php

/**
 * Name: Frio Hovercard
 * Description: Hovercard addon for the frio theme
 * Version: 0.1
 * Author: Rabuzarus <https://github.com/rabuzarus>
 * License: GNU AFFERO GENERAL PUBLIC LICENSE (Version 3)
 */

require_once("include/socgraph.php");
require_once("include/Contact.php");

function hovercard_init(&$a) {
	// Just for testing purposes
	$_GET["mode"] = "minimal";
}
function hovercard_content() {
	$profileurl	=	(x($_REQUEST,'profileurl')	? $_REQUEST['profileurl']	: "");
	$datatype	=	(x($_REQUEST,'datatype')	?$_REQUEST['datatype']		: "json");

	// Get out if the system doesn't have public access allowed
	if(intval(get_config('system','block_public')))
		http_status_exit(401);

	// Return the raw content of the template. We use this to make templates usable for js functions.
	// Look at hovercard.js (function getHoverCardTemplate()).
	// This part should be moved in it's own module. Maybe we could make more templates accessabel.
	// (We need to discuss possible security lacks before doing this)
	if ($datatype == "tpl") {
		$templatecontent = get_template_content("hovercard.tpl");
		echo $templatecontent;
		killme();
	}

	// If a contact is connected the url is internally changed to "redir/CID". We need the pure url to search for
	// the contact. So we strip out the contact id from the internal url and look in the contact table for
	// the real url (nurl)
	if(local_user() && strpos($profileurl, "redir/") === 0) {
		$cid = intval(substr($profileurl, 6));
		$r = q("SELECT `nurl`, `self`  FROM `contact` WHERE `id` = '%d' LIMIT 1", intval($cid));
		$profileurl = ($r[0]["nurl"] ? $r[0]["nurl"] : "");
		$self = ($r[0]["self"] ? $r[0]["self"] : "");
	}

	// if it's the url containing https it should be converted to http
	$nurl = normalise_link(clean_contact_url($profileurl));
	if($nurl) {
		// Search for contact data
		$contact = get_contact_details_by_url($nurl);
	}

	if(!is_array($contact))
		return;

	// Get the photo_menu - the menu if possible contact actions
	if(local_user())
		$actions = contact_photo_menu($contact);


	// Move the contact data to the profile array so we can deliver it to
	//
	$profile = array(
		'name' => $contact["name"],
		'nick'	=> $contact["nick"],
		'addr'	=> (($contact["addr"] != "") ? $contact["addr"] : $contact["url"]),
		'thumb' => proxy_url($contact["thumb"], false, PROXY_SIZE_THUMB),
		'url' => ($cid ? ("redir/".$cid) : zrl($contact["url"])),
		'nurl' => $contact["nurl"], // We additionally store the nurl as identifier
//		'alias' => $contact["alias"],
		'location' => $contact["location"],
		'gender' => $contact["gender"],
		'about' => $contact["about"],
		'network' => format_network_name($contact["network"], $contact["url"]),
		'tags' => $contact["keywords"],
//		'nsfw' => intval($contact["nsfw"]),
//		'server_url' => $contact["server_url"],
		'bd' => (($contact["birthday"] == "0000-00-00") ? "" : $contact["birthday"]),
//		'generation' => $contact["generation"],
		'account_type' => account_type($contact),
		'actions' => $actions,
	);
	if($datatype == "html") {
		$t = get_markup_template("hovercard.tpl");

		$o = replace_macros($t, array(
			'$profile' => $profile,
		));

		return $o;

	} else {
		json_return_and_die($profile);
	}
}

/**
 * @brief Get the raw content of a template file
 * 
 * @param string $template The name of the template
 * @param string $root Directory of the template
 * 
 * @return string|bool Output the raw content if existent, otherwise false
 */
function get_template_content($template, $root = "") {

	// We load the whole template system to get the filename.
	// Maybe we can do it a little bit smarter if I get time.
	$t = get_markup_template($template, $root);
	$filename = $t->filename;

	// Get the content of the template file
	if(file_exists($filename)) {
		$content = file_get_contents($filename);

		return $content;
	}

	return false;
}
