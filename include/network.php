<?php

/**
 * @file include/network.php
 */

use \Friendica\Core\Config;

require_once("include/xml.php");
require_once('include/Probe.php');

/**
 * @brief Curl wrapper
 *
 * If binary flag is true, return binary results.
 * Set the cookiejar argument to a string (e.g. "/tmp/friendica-cookies.txt")
 * to preserve cookies from one request to the next.
 *
 * @param string $url URL to fetch
 * @param boolean $binary default false
 *    TRUE if asked to return binary results (file download)
 * @param integer $redirects The recursion counter for internal use - default 0
 * @param integer $timeout Timeout in seconds, default system config value or 60 seconds
 * @param string $accept_content supply Accept: header with 'accept_content' as the value
 * @param string $cookiejar Path to cookie jar file
 *
 * @return string The fetched content
 */
function fetch_url($url,$binary = false, &$redirects = 0, $timeout = 0, $accept_content=Null, $cookiejar = 0) {

	$ret = z_fetch_url(
		$url,
		$binary,
		$redirects,
		array('timeout'=>$timeout,
		'accept_content'=>$accept_content,
		'cookiejar'=>$cookiejar
		));

	return($ret['body']);
}

/**
 * @brief fetches an URL.
 *
 * @param string $url URL to fetch
 * @param boolean $binary default false
 *    TRUE if asked to return binary results (file download)
 * @param int $redirects The recursion counter for internal use - default 0
 * @param array $opts (optional parameters) assoziative array with:
 *    'accept_content' => supply Accept: header with 'accept_content' as the value
 *    'timeout' => int Timeout in seconds, default system config value or 60 seconds
 *    'http_auth' => username:password
 *    'novalidate' => do not validate SSL certs, default is to validate using our CA list
 *    'nobody' => only return the header
 *    'cookiejar' => path to cookie jar file
 *
 * @return array an assoziative array with:
 *    int 'return_code' => HTTP return code or 0 if timeout or failure
 *    boolean 'success' => boolean true (if HTTP 2xx result) or false
 *    string 'redirect_url' => in case of redirect, content was finally retrieved from this URL
 *    string 'header' => HTTP headers
 *    string 'body' => fetched content
 */
function z_fetch_url($url,$binary = false, &$redirects = 0, $opts=array()) {

	$ret = array('return_code' => 0, 'success' => false, 'header' => "", 'body' => "");


	$stamp1 = microtime(true);

	$a = get_app();

	$ch = @curl_init($url);
	if (($redirects > 8) || (! $ch)) {
		return $ret;
	}

	@curl_setopt($ch, CURLOPT_HEADER, true);

	if (x($opts,"cookiejar")) {
		curl_setopt($ch, CURLOPT_COOKIEJAR, $opts["cookiejar"]);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $opts["cookiejar"]);
	}

// These settings aren't needed. We're following the location already.
//	@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
//	@curl_setopt($ch, CURLOPT_MAXREDIRS, 5);

	if (x($opts,'accept_content')){
		curl_setopt($ch,CURLOPT_HTTPHEADER, array (
			"Accept: " . $opts['accept_content']
		));
	}

	@curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
	@curl_setopt($ch, CURLOPT_USERAGENT, $a->get_useragent());

	$range = intval(Config::get('system', 'curl_range_bytes', 0));
	if ($range > 0) {
		@curl_setopt($ch, CURLOPT_RANGE, '0-'.$range);
	}

	if (x($opts,'headers')){
		@curl_setopt($ch, CURLOPT_HTTPHEADER, $opts['headers']);
	}
	if (x($opts,'nobody')){
		@curl_setopt($ch, CURLOPT_NOBODY, $opts['nobody']);
	}
	if (x($opts,'timeout')){
		@curl_setopt($ch, CURLOPT_TIMEOUT, $opts['timeout']);
	} else {
		$curl_time = intval(get_config('system','curl_timeout'));
		@curl_setopt($ch, CURLOPT_TIMEOUT, (($curl_time !== false) ? $curl_time : 60));
	}

	// by default we will allow self-signed certs
	// but you can override this

	$check_cert = get_config('system','verifyssl');
	@curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, (($check_cert) ? true : false));
	if ($check_cert) {
		@curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
	}

	$prx = get_config('system','proxy');
	if (strlen($prx)) {
		@curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
		@curl_setopt($ch, CURLOPT_PROXY, $prx);
		$prxusr = @get_config('system','proxyuser');
		if (strlen($prxusr))
			@curl_setopt($ch, CURLOPT_PROXYUSERPWD, $prxusr);
	}
	if ($binary)
		@curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);

	$a->set_curl_code(0);

	// don't let curl abort the entire application
	// if it throws any errors.

	$s = @curl_exec($ch);
	if (curl_errno($ch) !== CURLE_OK) {
		logger('fetch_url error fetching '.$url.': '.curl_error($ch), LOGGER_NORMAL);
	}

	$ret['errno'] = curl_errno($ch);

	$base = $s;
	$curl_info = @curl_getinfo($ch);

	$http_code = $curl_info['http_code'];
	logger('fetch_url '.$url.': '.$http_code." ".$s, LOGGER_DATA);
	$header = '';

	// Pull out multiple headers, e.g. proxy and continuation headers
	// allow for HTTP/2.x without fixing code

	while (preg_match('/^HTTP\/[1-2].+? [1-5][0-9][0-9]/',$base)) {
		$chunk = substr($base,0,strpos($base,"\r\n\r\n")+4);
		$header .= $chunk;
		$base = substr($base,strlen($chunk));
	}

	$a->set_curl_code($http_code);
	$a->set_curl_content_type($curl_info['content_type']);
	$a->set_curl_headers($header);

	if ($http_code == 301 || $http_code == 302 || $http_code == 303 || $http_code == 307) {
		$new_location_info = @parse_url($curl_info["redirect_url"]);
		$old_location_info = @parse_url($curl_info["url"]);

		$newurl = $curl_info["redirect_url"];

		if (($new_location_info["path"] == "") AND ($new_location_info["host"] != ""))
			$newurl = $new_location_info["scheme"]."://".$new_location_info["host"].$old_location_info["path"];

		$matches = array();
		if (preg_match('/(Location:|URI:)(.*?)\n/i', $header, $matches)) {
			$newurl = trim(array_pop($matches));
		}
		if (strpos($newurl,'/') === 0)
			$newurl = $old_location_info["scheme"]."://".$old_location_info["host"].$newurl;
		if (filter_var($newurl, FILTER_VALIDATE_URL)) {
			$redirects++;
			@curl_close($ch);
			return z_fetch_url($newurl,$binary, $redirects, $opts);
		}
	}


	$a->set_curl_code($http_code);
	$a->set_curl_content_type($curl_info['content_type']);

	$body = substr($s,strlen($header));



	$rc = intval($http_code);
	$ret['return_code'] = $rc;
	$ret['success'] = (($rc >= 200 && $rc <= 299) ? true : false);
	$ret['redirect_url'] = $url;
	if (! $ret['success']) {
		$ret['error'] = curl_error($ch);
		$ret['debug'] = $curl_info;
		logger('z_fetch_url: error: ' . $url . ': ' . $ret['error'], LOGGER_DEBUG);
		logger('z_fetch_url: debug: ' . print_r($curl_info,true), LOGGER_DATA);
	}
	$ret['body'] = substr($s,strlen($header));
	$ret['header'] = $header;
	if (x($opts,'debug')) {
		$ret['debug'] = $curl_info;
	}
	@curl_close($ch);

	$a->save_timestamp($stamp1, "network");

	return($ret);

}

// post request to $url. $params is an array of post variables.

/**
 * @brief Post request to $url
 *
 * @param string $url URL to post
 * @param mixed $params
 * @param string $headers HTTP headers
 * @param integer $redirects Recursion counter for internal use - default = 0
 * @param integer $timeout The timeout in seconds, default system config value or 60 seconds
 *
 * @return string The content
 */
function post_url($url,$params, $headers = null, &$redirects = 0, $timeout = 0) {
	$stamp1 = microtime(true);

	$a = get_app();
	$ch = curl_init($url);
	if (($redirects > 8) || (! $ch))
		return false;

	logger("post_url: start ".$url, LOGGER_DATA);

	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
	curl_setopt($ch, CURLOPT_POST,1);
	curl_setopt($ch, CURLOPT_POSTFIELDS,$params);
	curl_setopt($ch, CURLOPT_USERAGENT, $a->get_useragent());

	if (intval($timeout)) {
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
	}
	else {
		$curl_time = intval(get_config('system','curl_timeout'));
		curl_setopt($ch, CURLOPT_TIMEOUT, (($curl_time !== false) ? $curl_time : 60));
	}

	if (defined('LIGHTTPD')) {
		if (!is_array($headers)) {
			$headers = array('Expect:');
		} else {
			if (!in_array('Expect:', $headers)) {
				array_push($headers, 'Expect:');
			}
		}
	}
	if ($headers)
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

	$check_cert = get_config('system','verifyssl');
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, (($check_cert) ? true : false));
	if ($check_cert) {
		@curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
	}
	$prx = get_config('system','proxy');
	if (strlen($prx)) {
		curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
		curl_setopt($ch, CURLOPT_PROXY, $prx);
		$prxusr = get_config('system','proxyuser');
		if (strlen($prxusr))
			curl_setopt($ch, CURLOPT_PROXYUSERPWD, $prxusr);
	}

	$a->set_curl_code(0);

	// don't let curl abort the entire application
	// if it throws any errors.

	$s = @curl_exec($ch);

	$base = $s;
	$curl_info = curl_getinfo($ch);
	$http_code = $curl_info['http_code'];

	logger("post_url: result ".$http_code." - ".$url, LOGGER_DATA);

	$header = '';

	// Pull out multiple headers, e.g. proxy and continuation headers
	// allow for HTTP/2.x without fixing code

	while (preg_match('/^HTTP\/[1-2].+? [1-5][0-9][0-9]/',$base)) {
		$chunk = substr($base,0,strpos($base,"\r\n\r\n")+4);
		$header .= $chunk;
		$base = substr($base,strlen($chunk));
	}

	if ($http_code == 301 || $http_code == 302 || $http_code == 303 || $http_code == 307) {
		$matches = array();
		preg_match('/(Location:|URI:)(.*?)\n/', $header, $matches);
		$newurl = trim(array_pop($matches));
		if (strpos($newurl,'/') === 0)
			$newurl = $old_location_info["scheme"] . "://" . $old_location_info["host"] . $newurl;
		if (filter_var($newurl, FILTER_VALIDATE_URL)) {
			$redirects++;
			logger("post_url: redirect ".$url." to ".$newurl);
			return post_url($newurl,$params, $headers, $redirects, $timeout);
			//return fetch_url($newurl,false,$redirects,$timeout);
		}
	}
	$a->set_curl_code($http_code);
	$body = substr($s,strlen($header));

	$a->set_curl_headers($header);

	curl_close($ch);

	$a->save_timestamp($stamp1, "network");

	logger("post_url: end ".$url, LOGGER_DATA);

	return($body);
}

// Generic XML return
// Outputs a basic dfrn XML status structure to STDOUT, with a <status> variable
// of $st and an optional text <message> of $message and terminates the current process.

function xml_status($st, $message = '') {

	$xml_message = ((strlen($message)) ? "\t<message>" . xmlify($message) . "</message>\r\n" : '');

	if ($st)
		logger('xml_status returning non_zero: ' . $st . " message=" . $message);

	header( "Content-type: text/xml" );
	echo '<?xml version="1.0" encoding="UTF-8"?>'."\r\n";
	echo "<result>\r\n\t<status>$st</status>\r\n$xml_message</result>\r\n";
	killme();
}

/**
 * @brief Send HTTP status header and exit.
 *
 * @param integer $val HTTP status result value
 * @param array $description optional message
 *    'title' => header title
 *    'description' => optional message
 */

/**
 * @brief Send HTTP status header and exit.
 *
 * @param integer $val HTTP status result value
 * @param array $description optional message
 *    'title' => header title
 *    'description' => optional message
 */
function http_status_exit($val, $description = array()) {
	$err = '';
	if ($val >= 400) {
		$err = 'Error';
		if (!isset($description["title"]))
			$description["title"] = $err." ".$val;
	}
	if ($val >= 200 && $val < 300)
		$err = 'OK';

	logger('http_status_exit ' . $val);
	header($_SERVER["SERVER_PROTOCOL"] . ' ' . $val . ' ' . $err);

	if (isset($description["title"])) {
		$tpl = get_markup_template('http_status.tpl');
		echo replace_macros($tpl, array('$title' => $description["title"],
						'$description' => $description["description"]));
	}

	killme();

}

/**
 * @brief Check URL to se if ts's real
 *
 * Take a URL from the wild, prepend http:// if necessary
 * and check DNS to see if it's real (or check if is a valid IP address)
 *
 * @param string $url The URL to be validated
 * @return boolean True if it's a valid URL, fals if something wrong with it
 */
function validate_url(&$url) {
	if (get_config('system','disable_url_validation'))
		return true;

	// no naked subdomains (allow localhost for tests)
	if (strpos($url,'.') === false && strpos($url,'/localhost/') === false)
		return false;

	if (substr($url,0,4) != 'http')
		$url = 'http://' . $url;

	/// @TODO Really supress function outcomes? Why not find them + debug them?
	$h = @parse_url($url);

	if ((is_array($h)) && (dns_get_record($h['host'], DNS_A + DNS_CNAME + DNS_PTR) || filter_var($h['host'], FILTER_VALIDATE_IP) )) {
		return true;
	}

	return false;
}

/**
 * @brief Checks that email is an actual resolvable internet address
 *
 * @param string $addr The email address
 * @return boolean True if it's a valid email address, false if it's not
 */
function validate_email($addr) {

	if (get_config('system','disable_email_validation'))
		return true;

	if (! strpos($addr,'@'))
		return false;
	$h = substr($addr,strpos($addr,'@') + 1);

	if (($h) && (dns_get_record($h, DNS_A + DNS_CNAME + DNS_PTR + DNS_MX) || filter_var($h, FILTER_VALIDATE_IP) )) {
		return true;
	}
	return false;
}

/**
 * @brief Check if URL is allowed
 *
 * Check $url against our list of allowed sites,
 * wildcards allowed. If allowed_sites is unset return true;
 *
 * @param string $url URL which get tested
 * @return boolean True if url is allowed otherwise return false
 */
function allowed_url($url) {

	$h = @parse_url($url);

	if (! $h) {
		return false;
	}

	$str_allowed = get_config('system','allowed_sites');
	if (! $str_allowed)
		return true;

	$found = false;

	$host = strtolower($h['host']);

	// always allow our own site

	if ($host == strtolower($_SERVER['SERVER_NAME']))
		return true;

	$fnmatch = function_exists('fnmatch');
	$allowed = explode(',',$str_allowed);

	if (count($allowed)) {
		foreach ($allowed as $a) {
			$pat = strtolower(trim($a));
			if (($fnmatch && fnmatch($pat,$host)) || ($pat == $host)) {
				$found = true;
				break;
			}
		}
	}
	return $found;
}

/**
 * @brief Check if email address is allowed to register here.
 *
 * Compare against our list (wildcards allowed).
 *
 * @param type $email
 * @return boolean False if not allowed, true if allowed
 *    or if allowed list is not configured
 */
function allowed_email($email) {

	$domain = strtolower(substr($email,strpos($email,'@') + 1));
	if (! $domain) {
		return false;
	}

	$str_allowed = get_config('system','allowed_email');
	if (! $str_allowed) {
		return true;
	}

	$found = false;

	$fnmatch = function_exists('fnmatch');
	$allowed = explode(',',$str_allowed);

	if (count($allowed)) {
		foreach ($allowed as $a) {
			$pat = strtolower(trim($a));
			if (($fnmatch && fnmatch($pat,$domain)) || ($pat == $domain)) {
				$found = true;
				break;
			}
		}
	}
	return $found;
}

function avatar_img($email) {

	$avatar['size'] = 175;
	$avatar['email'] = $email;
	$avatar['url'] = '';
	$avatar['success'] = false;

	call_hooks('avatar_lookup', $avatar);

	if (! $avatar['success']) {
		$avatar['url'] = App::get_baseurl() . '/images/person-175.jpg';
	}

	logger('Avatar: ' . $avatar['email'] . ' ' . $avatar['url'], LOGGER_DEBUG);
	return $avatar['url'];
}


function parse_xml_string($s,$strict = true) {
	/// @todo Move this function to the xml class
	if ($strict) {
		if (! strstr($s,'<?xml'))
			return false;
		$s2 = substr($s,strpos($s,'<?xml'));
	}
	else
		$s2 = $s;
	libxml_use_internal_errors(true);

	$x = @simplexml_load_string($s2);
	if (! $x) {
		logger('libxml: parse: error: ' . $s2, LOGGER_DATA);
		foreach (libxml_get_errors() as $err) {
			logger('libxml: parse: ' . $err->code." at ".$err->line.":".$err->column." : ".$err->message, LOGGER_DATA);
		}
		libxml_clear_errors();
	}
	return $x;
}

function scale_external_images($srctext, $include_link = true, $scale_replace = false) {

	// Suppress "view full size"
	if (intval(get_config('system','no_view_full_size'))) {
		$include_link = false;
	}

	$a = get_app();

	// Picture addresses can contain special characters
	$s = htmlspecialchars_decode($srctext);

	$matches = null;
	$c = preg_match_all('/\[img.*?\](.*?)\[\/img\]/ism',$s,$matches,PREG_SET_ORDER);
	if ($c) {
		require_once('include/Photo.php');
		foreach ($matches as $mtch) {
			logger('scale_external_image: ' . $mtch[1]);

			$hostname = str_replace('www.','',substr(App::get_baseurl(),strpos(App::get_baseurl(),'://')+3));
			if (stristr($mtch[1],$hostname)) {
				continue;
			}

			// $scale_replace, if passed, is an array of two elements. The
			// first is the name of the full-size image. The second is the
			// name of a remote, scaled-down version of the full size image.
			// This allows Friendica to display the smaller remote image if
			// one exists, while still linking to the full-size image
			if ($scale_replace) {
				$scaled = str_replace($scale_replace[0], $scale_replace[1], $mtch[1]);
			} else {
				$scaled = $mtch[1];
			}
			$i = fetch_url($scaled);
			if (! $i) {
				return $srctext;
			}

			// guess mimetype from headers or filename
			$type = guess_image_type($mtch[1],true);

			if ($i) {
				$ph = new Photo($i, $type);
				if ($ph->is_valid()) {
					$orig_width = $ph->getWidth();
					$orig_height = $ph->getHeight();

					if ($orig_width > 640 || $orig_height > 640) {

						$ph->scaleImage(640);
						$new_width = $ph->getWidth();
						$new_height = $ph->getHeight();
						logger('scale_external_images: ' . $orig_width . '->' . $new_width . 'w ' . $orig_height . '->' . $new_height . 'h' . ' match: ' . $mtch[0], LOGGER_DEBUG);
						$s = str_replace($mtch[0],'[img=' . $new_width . 'x' . $new_height. ']' . $scaled . '[/img]'
							. "\n" . (($include_link)
								? '[url=' . $mtch[1] . ']' . t('view full size') . '[/url]' . "\n"
								: ''),$s);
						logger('scale_external_images: new string: ' . $s, LOGGER_DEBUG);
					}
				}
			}
		}
	}

	// replace the special char encoding
	$s = htmlspecialchars($s,ENT_NOQUOTES,'UTF-8');
	return $s;
}


function fix_contact_ssl_policy(&$contact,$new_policy) {

	$ssl_changed = false;
	if ((intval($new_policy) == SSL_POLICY_SELFSIGN || $new_policy === 'self') && strstr($contact['url'],'https:')) {
		$ssl_changed = true;
		$contact['url']     = 	str_replace('https:','http:',$contact['url']);
		$contact['request'] = 	str_replace('https:','http:',$contact['request']);
		$contact['notify']  = 	str_replace('https:','http:',$contact['notify']);
		$contact['poll']    = 	str_replace('https:','http:',$contact['poll']);
		$contact['confirm'] = 	str_replace('https:','http:',$contact['confirm']);
		$contact['poco']    = 	str_replace('https:','http:',$contact['poco']);
	}

	if ((intval($new_policy) == SSL_POLICY_FULL || $new_policy === 'full') && strstr($contact['url'],'http:')) {
		$ssl_changed = true;
		$contact['url']     = 	str_replace('http:','https:',$contact['url']);
		$contact['request'] = 	str_replace('http:','https:',$contact['request']);
		$contact['notify']  = 	str_replace('http:','https:',$contact['notify']);
		$contact['poll']    = 	str_replace('http:','https:',$contact['poll']);
		$contact['confirm'] = 	str_replace('http:','https:',$contact['confirm']);
		$contact['poco']    = 	str_replace('http:','https:',$contact['poco']);
	}

	if ($ssl_changed) {
		q("UPDATE `contact` SET
			`url` = '%s',
			`request` = '%s',
			`notify` = '%s',
			`poll` = '%s',
			`confirm` = '%s',
			`poco` = '%s'
			WHERE `id` = %d LIMIT 1",
			dbesc($contact['url']),
			dbesc($contact['request']),
			dbesc($contact['notify']),
			dbesc($contact['poll']),
			dbesc($contact['confirm']),
			dbesc($contact['poco']),
			intval($contact['id'])
		);
	}
}

/**
 * @brief Remove Google Analytics and other tracking platforms params from URL
 *
 * @param string $url Any user-submitted URL that may contain tracking params
 * @return string The same URL stripped of tracking parameters
 */
function strip_tracking_query_params($url)
{
	$urldata = parse_url($url);
	if (is_string($urldata["query"])) {
		$query = $urldata["query"];
		parse_str($query, $querydata);

		if (is_array($querydata)) {
			foreach ($querydata AS $param => $value) {
				if (in_array($param, array("utm_source", "utm_medium", "utm_term", "utm_content", "utm_campaign",
							"wt_mc", "pk_campaign", "pk_kwd", "mc_cid", "mc_eid",
							"fb_action_ids", "fb_action_types", "fb_ref",
							"awesm", "wtrid",
							"woo_campaign", "woo_source", "woo_medium", "woo_content", "woo_term"))) {

					$pair = $param . "=" . urlencode($value);
					$url = str_replace($pair, "", $url);

					// Second try: if the url isn't encoded completely
					$pair = $param . "=" . str_replace(" ", "+", $value);
					$url = str_replace($pair, "", $url);

					// Third try: Maybey the url isn't encoded at all
					$pair = $param . "=" . $value;
					$url = str_replace($pair, "", $url);

					$url = str_replace(array("?&", "&&"), array("?", ""), $url);
				}
			}
		}

		if (substr($url, -1, 1) == "?") {
			$url = substr($url, 0, -1);
		}
	}

	return $url;
}

/**
 * @brief Returns the original URL of the provided URL
 *
 * This function strips tracking query params and follows redirections, either
 * through HTTP code or meta refresh tags. Stops after 10 redirections.
 *
 * @todo Remove the $fetchbody parameter that generates an extraneous HEAD request
 *
 * @see ParseUrl::getSiteinfo
 *
 * @param string $url A user-submitted URL
 * @param int $depth The current redirection recursion level (internal)
 * @param bool $fetchbody Wether to fetch the body or not after the HEAD requests
 * @return string A canonical URL
 */
function original_url($url, $depth = 1, $fetchbody = false) {
	$a = get_app();

	$url = strip_tracking_query_params($url);

	if ($depth > 10)
		return($url);

	$url = trim($url, "'");

	$stamp1 = microtime(true);

	$siteinfo = array();
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLOPT_NOBODY, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERAGENT, $a->get_useragent());

	$header = curl_exec($ch);
	$curl_info = @curl_getinfo($ch);
	$http_code = $curl_info['http_code'];
	curl_close($ch);

	$a->save_timestamp($stamp1, "network");

	if ($http_code == 0)
		return($url);

	if ((($curl_info['http_code'] == "301") OR ($curl_info['http_code'] == "302"))
		AND (($curl_info['redirect_url'] != "") OR ($curl_info['location'] != ""))) {
		if ($curl_info['redirect_url'] != "")
			return(original_url($curl_info['redirect_url'], ++$depth, $fetchbody));
		else
			return(original_url($curl_info['location'], ++$depth, $fetchbody));
	}

	// Check for redirects in the meta elements of the body if there are no redirects in the header.
	if (!$fetchbody)
		return(original_url($url, ++$depth, true));

	// if the file is too large then exit
	if ($curl_info["download_content_length"] > 1000000)
		return($url);

	// if it isn't a HTML file then exit
	if (($curl_info["content_type"] != "") AND !strstr(strtolower($curl_info["content_type"]),"html"))
		return($url);

	$stamp1 = microtime(true);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_NOBODY, 0);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERAGENT, $a->get_useragent());

	$body = curl_exec($ch);
	curl_close($ch);

	$a->save_timestamp($stamp1, "network");

	if (trim($body) == "")
		return($url);

	// Check for redirect in meta elements
	$doc = new DOMDocument();
	@$doc->loadHTML($body);

	$xpath = new DomXPath($doc);

	$list = $xpath->query("//meta[@content]");
	foreach ($list as $node) {
		$attr = array();
		if ($node->attributes->length)
			foreach ($node->attributes as $attribute)
				$attr[$attribute->name] = $attribute->value;

		if (@$attr["http-equiv"] == 'refresh') {
			$path = $attr["content"];
			$pathinfo = explode(";", $path);
			$content = "";
			foreach ($pathinfo AS $value)
				if (substr(strtolower($value), 0, 4) == "url=")
					return(original_url(substr($value, 4), ++$depth));
		}
	}

	return($url);
}

function short_link($url) {
	require_once('library/slinky.php');
	$slinky = new Slinky($url);
	$yourls_url = get_config('yourls','url1');
	if ($yourls_url) {
		$yourls_username = get_config('yourls','username1');
		$yourls_password = get_config('yourls', 'password1');
		$yourls_ssl = get_config('yourls', 'ssl1');
		$yourls = new Slinky_YourLS();
		$yourls->set('username', $yourls_username);
		$yourls->set('password', $yourls_password);
		$yourls->set('ssl', $yourls_ssl);
		$yourls->set('yourls-url', $yourls_url);
		$slinky->set_cascade(array($yourls, new Slinky_Ur1ca(), new Slinky_TinyURL()));
	} else {
		// setup a cascade of shortening services
		// try to get a short link from these services
		// in the order ur1.ca, tinyurl
		$slinky->set_cascade(array(new Slinky_Ur1ca(), new Slinky_TinyURL()));
	}
	return $slinky->short();
}

/**
 * @brief Encodes content to json
 *
 * This function encodes an array to json format
 * and adds an application/json HTTP header to the output.
 * After finishing the process is getting killed.
 *
 * @param array $x The input content
 */
function json_return_and_die($x) {
	header("content-type: application/json");
	echo json_encode($x);
	killme();
}

/**
 * @brief Find the matching part between two url
 *
 * @param string $url1
 * @param string $url2
 * @return string The matching part
 */
function matching_url($url1, $url2) {

	if (($url1 == "") OR ($url2 == ""))
		return "";

	$url1 = normalise_link($url1);
	$url2 = normalise_link($url2);

	$parts1 = parse_url($url1);
	$parts2 = parse_url($url2);

	if (!isset($parts1["host"]) OR !isset($parts2["host"]))
		return "";

	if ($parts1["scheme"] != $parts2["scheme"])
		return "";

	if ($parts1["host"] != $parts2["host"])
		return "";

	if ($parts1["port"] != $parts2["port"])
		return "";

	$match = $parts1["scheme"]."://".$parts1["host"];

	if ($parts1["port"])
		$match .= ":".$parts1["port"];

	$pathparts1 = explode("/", $parts1["path"]);
	$pathparts2 = explode("/", $parts2["path"]);

	$i = 0;
	$path = "";
	do {
		$path1 = $pathparts1[$i];
		$path2 = $pathparts2[$i];

		if ($path1 == $path2)
			$path .= $path1."/";

	} while (($path1 == $path2) AND ($i++ <= count($pathparts1)));

	$match .= $path;

	return normalise_link($match);
}
