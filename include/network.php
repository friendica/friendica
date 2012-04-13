<?php


// curl wrapper. If binary flag is true, return binary
// results. 

if(! function_exists('fetch_url')) {
function fetch_url($url,$binary = false, &$redirects = 0, $timeout = 0, $accept_content=Null) {

	$a = get_app();

	$ch = @curl_init($url);
	if(($redirects > 8) || (! $ch)) 
		return false;

	@curl_setopt($ch, CURLOPT_HEADER, true);
	
	if (!is_null($accept_content)){
		curl_setopt($ch,CURLOPT_HTTPHEADER, array (
			"Accept: " . $accept_content
		));
	}
	
	@curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
	@curl_setopt($ch, CURLOPT_USERAGENT, "Friendica");


	if(intval($timeout)) {
		@curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
	}
	else {
		$curl_time = intval(get_config('system','curl_timeout'));
		@curl_setopt($ch, CURLOPT_TIMEOUT, (($curl_time !== false) ? $curl_time : 60));
	}
	// by default we will allow self-signed certs
	// but you can override this

	$check_cert = get_config('system','verifyssl');
	@curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, (($check_cert) ? true : false));

	$prx = get_config('system','proxy');
	if(strlen($prx)) {
		@curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
		@curl_setopt($ch, CURLOPT_PROXY, $prx);
		$prxusr = @get_config('system','proxyuser');
		if(strlen($prxusr))
			@curl_setopt($ch, CURLOPT_PROXYUSERPWD, $prxusr);
	}
	if($binary)
		@curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);

	$a->set_curl_code(0);

	// don't let curl abort the entire application
	// if it throws any errors.

	$s = @curl_exec($ch);

	$base = $s;
	$curl_info = @curl_getinfo($ch);
	$http_code = $curl_info['http_code'];

//	logger('fetch_url:' . $http_code . ' data: ' . $s);
	$header = '';

	// Pull out multiple headers, e.g. proxy and continuation headers
	// allow for HTTP/2.x without fixing code

	while(preg_match('/^HTTP\/[1-2].+? [1-5][0-9][0-9]/',$base)) {
		$chunk = substr($base,0,strpos($base,"\r\n\r\n")+4);
		$header .= $chunk;
		$base = substr($base,strlen($chunk));
	}

	if($http_code == 301 || $http_code == 302 || $http_code == 303 || $http_code == 307) {
        $matches = array();
        preg_match('/(Location:|URI:)(.*?)\n/', $header, $matches);
        $newurl = trim(array_pop($matches));
		if(strpos($newurl,'/') === 0)
			$newurl = $url . $newurl;
        $url_parsed = @parse_url($newurl);
        if (isset($url_parsed)) {
            $redirects++;
            return fetch_url($newurl,$binary,$redirects,$timeout);
        }
    }

	$a->set_curl_code($http_code);

	$body = substr($s,strlen($header));

	$a->set_curl_headers($header);

	@curl_close($ch);
	return($body);
}}

// post request to $url. $params is an array of post variables.

if(! function_exists('post_url')) {
function post_url($url,$params, $headers = null, &$redirects = 0, $timeout = 0) {
	$a = get_app();
	$ch = curl_init($url);
	if(($redirects > 8) || (! $ch)) 
		return false;

	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
	curl_setopt($ch, CURLOPT_POST,1);
	curl_setopt($ch, CURLOPT_POSTFIELDS,$params);
	curl_setopt($ch, CURLOPT_USERAGENT, "Friendica");

	if(intval($timeout)) {
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
	}
	else {
		$curl_time = intval(get_config('system','curl_timeout'));
		curl_setopt($ch, CURLOPT_TIMEOUT, (($curl_time !== false) ? $curl_time : 60));
	}

	if(defined('LIGHTTPD')) {
		if(!is_array($headers)) {
			$headers = array('Expect:');
		} else {
			if(!in_array('Expect:', $headers)) {
				array_push($headers, 'Expect:');
			}
		}
	}
	if($headers)
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

	$check_cert = get_config('system','verifyssl');
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, (($check_cert) ? true : false));
	$prx = get_config('system','proxy');
	if(strlen($prx)) {
		curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
		curl_setopt($ch, CURLOPT_PROXY, $prx);
		$prxusr = get_config('system','proxyuser');
		if(strlen($prxusr))
			curl_setopt($ch, CURLOPT_PROXYUSERPWD, $prxusr);
	}

	$a->set_curl_code(0);

	// don't let curl abort the entire application
	// if it throws any errors.

	$s = @curl_exec($ch);

	$base = $s;
	$curl_info = curl_getinfo($ch);
	$http_code = $curl_info['http_code'];

	$header = '';

	// Pull out multiple headers, e.g. proxy and continuation headers
	// allow for HTTP/2.x without fixing code

	while(preg_match('/^HTTP\/[1-2].+? [1-5][0-9][0-9]/',$base)) {
		$chunk = substr($base,0,strpos($base,"\r\n\r\n")+4);
		$header .= $chunk;
		$base = substr($base,strlen($chunk));
	}

	if($http_code == 301 || $http_code == 302 || $http_code == 303) {
        $matches = array();
        preg_match('/(Location:|URI:)(.*?)\n/', $header, $matches);
        $newurl = trim(array_pop($matches));
		if(strpos($newurl,'/') === 0)
			$newurl = $url . $newurl;
        $url_parsed = @parse_url($newurl);
        if (isset($url_parsed)) {
            $redirects++;
            return fetch_url($newurl,$binary,$redirects,$timeout);
        }
    }
	$a->set_curl_code($http_code);
	$body = substr($s,strlen($header));

	$a->set_curl_headers($header);

	curl_close($ch);
	return($body);
}}

// Generic XML return
// Outputs a basic dfrn XML status structure to STDOUT, with a <status> variable 
// of $st and an optional text <message> of $message and terminates the current process. 

if(! function_exists('xml_status')) {
function xml_status($st, $message = '') {

	$xml_message = ((strlen($message)) ? "\t<message>" . xmlify($message) . "</message>\r\n" : '');

	if($st)
		logger('xml_status returning non_zero: ' . $st . " message=" . $message);

	header( "Content-type: text/xml" );
	echo '<?xml version="1.0" encoding="UTF-8"?>'."\r\n";
	echo "<result>\r\n\t<status>$st</status>\r\n$xml_message</result>\r\n";
	killme();
}}


if(! function_exists('http_status_exit')) {
function http_status_exit($val) {

	if($val >= 400)
		$err = 'Error';
	if($val >= 200 && $val < 300)
		$err = 'OK';

	logger('http_status_exit ' . $val);	
	header($_SERVER["SERVER_PROTOCOL"] . ' ' . $val . ' ' . $err);
	killme();

}}


// convert an XML document to a normalised, case-corrected array
// used by webfinger

if(! function_exists('convert_xml_element_to_array')) {
function convert_xml_element_to_array($xml_element, &$recursion_depth=0) {

        // If we're getting too deep, bail out
        if ($recursion_depth > 512) {
                return(null);
        }

        if (!is_string($xml_element) &&
        !is_array($xml_element) &&
        (get_class($xml_element) == 'SimpleXMLElement')) {
                $xml_element_copy = $xml_element;
                $xml_element = get_object_vars($xml_element);
        }

        if (is_array($xml_element)) {
                $result_array = array();
                if (count($xml_element) <= 0) {
                        return (trim(strval($xml_element_copy)));
                }

                foreach($xml_element as $key=>$value) {

                        $recursion_depth++;
                        $result_array[strtolower($key)] =
                convert_xml_element_to_array($value, $recursion_depth);
                        $recursion_depth--;
                }
                if ($recursion_depth == 0) {
                        $temp_array = $result_array;
                        $result_array = array(
                                strtolower($xml_element_copy->getName()) => $temp_array,
                        );
                }

                return ($result_array);

        } else {
                return (trim(strval($xml_element)));
        }
}}

// Given an email style address, perform webfinger lookup and 
// return the resulting DFRN profile URL, or if no DFRN profile URL
// is located, returns an OStatus subscription template (prefixed 
// with the string 'stat:' to identify it as on OStatus template).
// If this isn't an email style address just return $s.
// Return an empty string if email-style addresses but webfinger fails,
// or if the resultant personal XRD doesn't contain a supported 
// subscription/friend-request attribute.

// amended 7/9/2011 to return an hcard which could save potentially loading 
// a lengthy content page to scrape dfrn attributes

if(! function_exists('webfinger_dfrn')) {
function webfinger_dfrn($s,&$hcard) {
	if(! strstr($s,'@')) {
		return $s;
	}
	$profile_link = '';

	$links = webfinger($s);
	logger('webfinger_dfrn: ' . $s . ':' . print_r($links,true), LOGGER_DATA);
	if(count($links)) {
		foreach($links as $link) {
			if($link['@attributes']['rel'] === NAMESPACE_DFRN)
				$profile_link = $link['@attributes']['href'];
			if($link['@attributes']['rel'] === NAMESPACE_OSTATUSSUB)
				$profile_link = 'stat:' . $link['@attributes']['template'];	
			if($link['@attributes']['rel'] === 'http://microformats.org/profile/hcard')
				$hcard = $link['@attributes']['href'];				
		}
	}
	return $profile_link;
}}

// Given an email style address, perform webfinger lookup and 
// return the array of link attributes from the personal XRD file.
// On error/failure return an empty array.


if(! function_exists('webfinger')) {
function webfinger($s, $debug = false) {
	$host = '';
	if(strstr($s,'@')) {
		$host = substr($s,strpos($s,'@') + 1);
	}
	if(strlen($host)) {
		$tpl = fetch_lrdd_template($host);
		logger('webfinger: lrdd template: ' . $tpl);
		if(strlen($tpl)) {
			$pxrd = str_replace('{uri}', urlencode('acct:' . $s), $tpl);
			logger('webfinger: pxrd: ' . $pxrd);
			$links = fetch_xrd_links($pxrd);
			if(! count($links)) {
				// try with double slashes
				$pxrd = str_replace('{uri}', urlencode('acct://' . $s), $tpl);
				logger('webfinger: pxrd: ' . $pxrd);
				$links = fetch_xrd_links($pxrd);
			}
			return $links;
		}
	}
	return array();
}}

if(! function_exists('lrdd')) {
function lrdd($uri, $debug = false) {

	$a = get_app();

	// default priority is host priority, host-meta first

	$priority = 'host';

	// All we have is an email address. Resource-priority is irrelevant
	// because our URI isn't directly resolvable.

	if(strstr($uri,'@')) {	
		return(webfinger($uri));
	}

	// get the host meta file

	$host = @parse_url($uri);

	if($host) {
		$url  = ((x($host,'scheme')) ? $host['scheme'] : 'http') . '://';
		$url .= $host['host'] . '/.well-known/host-meta' ;
	}
	else
		return array();

	logger('lrdd: constructed url: ' . $url);

	$xml = fetch_url($url);
	$headers = $a->get_curl_headers();

	if (! $xml)
		return array();

	logger('lrdd: host_meta: ' . $xml, LOGGER_DATA);

	if(! stristr($xml,'<xrd'))
		return array();

	$h = parse_xml_string($xml);
	if(! $h)
		return array();

	$arr = convert_xml_element_to_array($h);

	if(isset($arr['xrd']['property'])) {
		$property = $arr['crd']['property'];
		if(! isset($property[0]))
			$properties = array($property);
		else
			$properties = $property;
		foreach($properties as $prop)
			if((string) $prop['@attributes'] === 'http://lrdd.net/priority/resource')
				$priority = 'resource';
	} 

	// save the links in case we need them

	$links = array();

	if(isset($arr['xrd']['link'])) {
		$link = $arr['xrd']['link'];
		if(! isset($link[0]))
			$links = array($link);
		else
			$links = $link;
	}

	// do we have a template or href?

	if(count($links)) {
		foreach($links as $link) {
			if($link['@attributes']['rel'] && attribute_contains($link['@attributes']['rel'],'lrdd')) {
				if(x($link['@attributes'],'template'))
					$tpl = $link['@attributes']['template'];
				elseif(x($link['@attributes'],'href'))
					$href = $link['@attributes']['href'];
			}
		}		
	}

	if((! isset($tpl)) || (! strpos($tpl,'{uri}')))
		$tpl = '';

	if($priority === 'host') {
		if(strlen($tpl)) 
			$pxrd = str_replace('{uri}', urlencode($uri), $tpl);
		elseif(isset($href))
			$pxrd = $href;
		if(isset($pxrd)) {
			logger('lrdd: (host priority) pxrd: ' . $pxrd);
			$links = fetch_xrd_links($pxrd);
			return $links;
		}

		$lines = explode("\n",$headers);
		if(count($lines)) {
			foreach($lines as $line) {				
				if((stristr($line,'link:')) && preg_match('/<([^>].*)>.*rel\=[\'\"]lrdd[\'\"]/',$line,$matches)) {
					return(fetch_xrd_links($matches[1]));
					break;
				}
			}
		}
	}


	// priority 'resource'


	$html = fetch_url($uri);
	$headers = $a->get_curl_headers();
	logger('lrdd: headers=' . $headers, LOGGER_DEBUG);

	// don't try and parse raw xml as html
	if(! strstr($html,'<?xml')) {
		require_once('library/HTML5/Parser.php');

		try {
			$dom = HTML5_Parser::parse($html);
		} catch (DOMException $e) {
			logger('lrdd: parse error: ' . $e);
		}

		if($dom) {
			$items = $dom->getElementsByTagName('link');
			foreach($items as $item) {
				$x = $item->getAttribute('rel');
				if($x == "lrdd") {
					$pagelink = $item->getAttribute('href');
					break;
				}
			}
		}
	}

	if(isset($pagelink))
		return(fetch_xrd_links($pagelink));

	// next look in HTTP headers

	$lines = explode("\n",$headers);
	if(count($lines)) {
		foreach($lines as $line) {				
			// TODO alter the following regex to support multiple relations (space separated)
			if((stristr($line,'link:')) && preg_match('/<([^>].*)>.*rel\=[\'\"]lrdd[\'\"]/',$line,$matches)) {
				$pagelink = $matches[1];
				break;
			}
			// don't try and run feeds through the html5 parser
			if(stristr($line,'content-type:') && ((stristr($line,'application/atom+xml')) || (stristr($line,'application/rss+xml'))))
				return array();
			if(stristr($html,'<rss') || stristr($html,'<feed'))
				return array();
		}
	}

	if(isset($pagelink))
		return(fetch_xrd_links($pagelink));

	// If we haven't found any links, return the host xrd links (which we have already fetched)

	if(isset($links))
		return $links;

	return array();

}}



// Given a host name, locate the LRDD template from that
// host. Returns the LRDD template or an empty string on
// error/failure.

if(! function_exists('fetch_lrdd_template')) {
function fetch_lrdd_template($host) {
	$tpl = '';

	$url1 = 'https://' . $host . '/.well-known/host-meta' ;
	$url2 = 'http://' . $host . '/.well-known/host-meta' ;
	$links = fetch_xrd_links($url1);
	logger('fetch_lrdd_template from: ' . $url1);
	logger('template (https): ' . print_r($links,true));
	if(! count($links)) {
		logger('fetch_lrdd_template from: ' . $url2);
		$links = fetch_xrd_links($url2);
		logger('template (http): ' . print_r($links,true));
	}
	if(count($links)) {
		foreach($links as $link)
			if($link['@attributes']['rel'] && $link['@attributes']['rel'] === 'lrdd')
				$tpl = $link['@attributes']['template'];
	}
	if(! strpos($tpl,'{uri}'))
		$tpl = '';
	return $tpl;
}}

// Given a URL, retrieve the page as an XRD document.
// Return an array of links.
// on error/failure return empty array.

if(! function_exists('fetch_xrd_links')) {
function fetch_xrd_links($url) {

	$xrd_timeout = intval(get_config('system','xrd_timeout'));
	$redirects = 0;
	$xml = fetch_url($url,false,$redirects,(($xrd_timeout) ? $xrd_timeout : 20));

	logger('fetch_xrd_links: ' . $xml, LOGGER_DATA);

	if ((! $xml) || (! stristr($xml,'<xrd')))
		return array();

	// fix diaspora's bad xml
	$xml = str_replace(array('href=&quot;','&quot;/>'),array('href="','"/>'),$xml);

	$h = parse_xml_string($xml);
	if(! $h)
		return array();

	$arr = convert_xml_element_to_array($h);

	$links = array();

	if(isset($arr['xrd']['link'])) {
		$link = $arr['xrd']['link'];
		if(! isset($link[0]))
			$links = array($link);
		else
			$links = $link;
	}
	if(isset($arr['xrd']['alias'])) {
		$alias = $arr['xrd']['alias'];
		if(! isset($alias[0]))
			$aliases = array($alias);
		else
			$aliases = $alias;
		if(is_array($aliases) && count($aliases)) {
			foreach($aliases as $alias) {
				$links[]['@attributes'] = array('rel' => 'alias' , 'href' => $alias);
			}
		}
	}

	logger('fetch_xrd_links: ' . print_r($links,true), LOGGER_DATA);

	return $links;

}}


// Take a URL from the wild, prepend http:// if necessary
// and check DNS to see if it's real
// return true if it's OK, false if something is wrong with it

if(! function_exists('validate_url')) {
function validate_url(&$url) {
	
	// no naked subdomains (allow localhost for tests)
	if(strpos($url,'.') === false && strpos($url,'/localhost/') === false)
		return false;
	if(substr($url,0,4) != 'http')
		$url = 'http://' . $url;
	$h = @parse_url($url);
	
	if(($h) && (dns_get_record($h['host'], DNS_A + DNS_CNAME + DNS_PTR))) {
		return true;
	}
	return false;
}}

// checks that email is an actual resolvable internet address

if(! function_exists('validate_email')) {
function validate_email($addr) {

	if(! strpos($addr,'@'))
		return false;
	$h = substr($addr,strpos($addr,'@') + 1);

	if(($h) && (dns_get_record($h, DNS_A + DNS_CNAME + DNS_PTR + DNS_MX))) {
		return true;
	}
	return false;
}}

// Check $url against our list of allowed sites,
// wildcards allowed. If allowed_sites is unset return true;
// If url is allowed, return true.
// otherwise, return false

if(! function_exists('allowed_url')) {
function allowed_url($url) {

	$h = @parse_url($url);

	if(! $h) {
		return false;
	}

	$str_allowed = get_config('system','allowed_sites');
	if(! $str_allowed)
		return true;

	$found = false;

	$host = strtolower($h['host']);

	// always allow our own site

	if($host == strtolower($_SERVER['SERVER_NAME']))
		return true;

	$fnmatch = function_exists('fnmatch');
	$allowed = explode(',',$str_allowed);

	if(count($allowed)) {
		foreach($allowed as $a) {
			$pat = strtolower(trim($a));
			if(($fnmatch && fnmatch($pat,$host)) || ($pat == $host)) {
				$found = true; 
				break;
			}
		}
	}
	return $found;
}}

// check if email address is allowed to register here.
// Compare against our list (wildcards allowed).
// Returns false if not allowed, true if allowed or if
// allowed list is not configured.

if(! function_exists('allowed_email')) {
function allowed_email($email) {


	$domain = strtolower(substr($email,strpos($email,'@') + 1));
	if(! $domain)
		return false;

	$str_allowed = get_config('system','allowed_email');
	if(! $str_allowed)
		return true;

	$found = false;

	$fnmatch = function_exists('fnmatch');
	$allowed = explode(',',$str_allowed);

	if(count($allowed)) {
		foreach($allowed as $a) {
			$pat = strtolower(trim($a));
			if(($fnmatch && fnmatch($pat,$domain)) || ($pat == $domain)) {
				$found = true; 
				break;
			}
		}
	}
	return $found;
}}


if(! function_exists('avatar_img')) {
function avatar_img($email) {

	$a = get_app();

	$avatar['size'] = 175;
	$avatar['email'] = $email;
	$avatar['url'] = '';
	$avatar['success'] = false;

	call_hooks('avatar_lookup', $avatar);

	if(! $avatar['success'])
		$avatar['url'] = $a->get_baseurl() . '/images/person-175.jpg';

	logger('Avatar: ' . $avatar['email'] . ' ' . $avatar['url'], LOGGER_DEBUG);
	return $avatar['url'];
}}


if(! function_exists('parse_xml_string')) {
function parse_xml_string($s,$strict = true) {
	if($strict) {
		if(! strstr($s,'<?xml'))
			return false;
		$s2 = substr($s,strpos($s,'<?xml'));
	}
	else
		$s2 = $s;
	libxml_use_internal_errors(true);

	$x = @simplexml_load_string($s2);
	if(! $x) {
		logger('libxml: parse: error: ' . $s2, LOGGER_DATA);
		foreach(libxml_get_errors() as $err)
			logger('libxml: parse: ' . $err->code." at ".$err->line.":".$err->column." : ".$err->message, LOGGER_DATA);
		libxml_clear_errors();
	}
	return $x;
}}

function add_fcontact($arr,$update = false) {

	if($update) {
		$r = q("UPDATE `fcontact` SET
			`name` = '%s',
			`photo` = '%s',
			`request` = '%s',
			`nick` = '%s',
			`addr` = '%s',
			`batch` = '%s',
			`notify` = '%s',
			`poll` = '%s',
			`confirm` = '%s',
			`alias` = '%s',
			`pubkey` = '%s',
			`updated` = '%s'
			WHERE `url` = '%s' AND `network` = '%s' LIMIT 1", 
			dbesc($arr['name']),
			dbesc($arr['photo']),
			dbesc($arr['request']),
			dbesc($arr['nick']),
			dbesc($arr['addr']),
			dbesc($arr['batch']),
			dbesc($arr['notify']),
			dbesc($arr['poll']),
			dbesc($arr['confirm']),
			dbesc($arr['alias']),
			dbesc($arr['pubkey']),
			dbesc(datetime_convert()),
			dbesc($arr['url']),
			dbesc($arr['network'])
		);
	}
	else {
		$r = q("insert into fcontact ( `url`,`name`,`photo`,`request`,`nick`,`addr`,
			`batch`, `notify`,`poll`,`confirm`,`network`,`alias`,`pubkey`,`updated` )
			values('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')",
			dbesc($arr['url']),
			dbesc($arr['name']),
			dbesc($arr['photo']),
			dbesc($arr['request']),
			dbesc($arr['nick']),
			dbesc($arr['addr']),
			dbesc($arr['batch']),
			dbesc($arr['notify']),
			dbesc($arr['poll']),
			dbesc($arr['confirm']),
			dbesc($arr['network']),
			dbesc($arr['alias']),
			dbesc($arr['pubkey']),
			dbesc(datetime_convert())
		);
	}

	return $r;
}


function scale_external_images($s,$include_link = true) {

	$a = get_app();

	$matches = null;
	$c = preg_match_all('/\[img\](.*?)\[\/img\]/ism',$s,$matches,PREG_SET_ORDER);
	if($c) {
		require_once('include/Photo.php');
		foreach($matches as $mtch) {
			logger('scale_external_image: ' . $mtch[1]);
			$hostname = str_replace('www.','',substr($a->get_baseurl(),strpos($a->get_baseurl(),'://')+3));
			if(stristr($mtch[1],$hostname))
				continue;
			$i = fetch_url($mtch[1]);
			if($i) {
				$ph = new Photo($i);
				if($ph->is_valid()) {
					$orig_width = $ph->getWidth();
					$orig_height = $ph->getHeight();

					if($orig_width > 640 || $orig_height > 640) {

						$ph->scaleImage(640);
						$new_width = $ph->getWidth();
						$new_height = $ph->getHeight();
						logger('scale_external_images: ' . $orig_width . '->' . $new_width . 'w ' . $orig_height . '->' . $new_height . 'h' . ' match: ' . $mtch[0], LOGGER_DEBUG);
						$s = str_replace($mtch[0],'[img=' . $new_width . 'x' . $new_height. ']' . $mtch[1] . '[/img]'
							. "\n" . (($include_link) 
								? '[url=' . $mtch[1] . ']' . t('view full size') . '[/url]' . "\n"
								: ''),$s);
						logger('scale_external_images: new string: ' . $s, LOGGER_DEBUG);
					}
				}
			}
		}
	}
	return $s;
}


function fix_contact_ssl_policy(&$contact,$new_policy) {

	$ssl_changed = false;
	if((intval($new_policy) == SSL_POLICY_SELFSIGN || $new_policy === 'self') && strstr($contact['url'],'https:')) {
		$ssl_changed = true;
		$contact['url']     = 	str_replace('https:','http:',$contact['url']);
		$contact['request'] = 	str_replace('https:','http:',$contact['request']);
		$contact['notify']  = 	str_replace('https:','http:',$contact['notify']);
		$contact['poll']    = 	str_replace('https:','http:',$contact['poll']);
		$contact['confirm'] = 	str_replace('https:','http:',$contact['confirm']);
		$contact['poco']    = 	str_replace('https:','http:',$contact['poco']);
	}

	if((intval($new_policy) == SSL_POLICY_FULL || $new_policy === 'full') && strstr($contact['url'],'http:')) {
		$ssl_changed = true;
		$contact['url']     = 	str_replace('http:','https:',$contact['url']);
		$contact['request'] = 	str_replace('http:','https:',$contact['request']);
		$contact['notify']  = 	str_replace('http:','https:',$contact['notify']);
		$contact['poll']    = 	str_replace('http:','https:',$contact['poll']);
		$contact['confirm'] = 	str_replace('http:','https:',$contact['confirm']);
		$contact['poco']    = 	str_replace('http:','https:',$contact['poco']);
	}

	if($ssl_changed) {
		q("update contact set 
			url = '%s', 
			request = '%s',
			notify = '%s',
			poll = '%s',
			confirm = '%s',
			poco = '%s'
			where id = %d limit 1",
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

