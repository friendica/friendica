<?php
/*
Copyright (c) 2009, Beau Lebens
All rights reserved.

Redistribution and use in source and binary forms, with or without modification,
are permitted provided that the following conditions are met:

 - Redistributions of source code must retain the above copyright notice, this 
   list of conditions and the following disclaimer.
 - Redistributions in binary form must reproduce the above copyright notice, 
   this list of conditions and the following disclaimer in the documentation 
   and/or other materials provided with the distribution.
 - Neither the name of Dented Reality nor the names of the authors may be used 
   to endorse or promote products derived from this software without specific 
   prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE 
DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND	ON
ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT	
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
	
*/

// Return options for Slinky_Service->url_get() and ->url_post()
define( 'SLINKY_BODY', 1 ); // Default
define( 'SLINKY_HEADERS', 2 ); // Not implemented yet
define( 'SLINKY_FINAL_URL', 3 ); // Default for lengthening URLs

// So that services may decide what to do with us
define( 'SLINKY_USER_AGENT', 'Slinky v1.0 +http://dentedreality.com.au/projects/slinky/' );

// How many seconds until remote requests should be cut?
define( 'SLINKY_TIMEOUT', 10 );

/**
 * Slinky allows you to go back and forth between "long" and shortened URLs 
 * using popular URL shortening services.
 * 
 * Slinky assumes you have cURL installed and working, and requires the JSON
 * extension installed if you're working with a service that uses JSON.
 * 
 * Slinky will ONLY work with PHP5+
 * 
 * It supports some of the more popular services, with easy extensibility for
 * adding your own relatively easily. It defaults to using TinyURL
 * for shortening URLs. If you want to use some of the other services, you need
 * to set some configuration options before actually shortening/lengthening
 * URLs. I'd strongly suggest that you cache results using memcached, a local
 * DB or whatever to avoid having to hit APIs etc every time you encounter a
 * URL.
 * 
 * Slinky supports shortening, and auto-detection (for lengthening URLs) 
 * using these services:
 * - Bit.ly
 * - Tr.im
 * - TinyURL
 * - Is.Gd
 * - Fon.gs
 * - Micurl.com
 * - ur1.ca
 * - Ptiturl
 * - Tighturl
 * - 2tu.us
 * - Snipr / Snipurl / Snurl.com / Sn.im
 * 
 * 
 * To use Slinky:
 * 
 * $slinky = new Slinky( 'http://dentedreality.com.au/' );
 * - Creates a new Slinky instance, will default to using TinyURL for ->short();
 * 
 * $slinky = new Slinky( 'http://dentedreality.com.au', new Slinky_Bitly() );
 * - Creates new Slinky, forces use of Bit.ly for ->short();
 * 
 * $slinky = new Slinky( 'http://dentedreality.com.au/' );
 * echo $slinky->short();
 * - echos the short version of http://dentedreality.com.au/ (default to TinyURL)
 * 
 * $slinky = new Slinky( 'http://tinyurl.com/jw5sh' );
 * echo $slinky->long();
 * - echos out the long version of http://tinyurl.com/jw5sh (which should be http://dentedreality.com.au/)
 * 
 * $slinky = new Slinky( 'http://dentedreality.com.au/' );
 * echo $slinky->long();
 * - Attempts to lengthen the URL, but will not auto-detect which service it is
 *   so it will just output the original URL. Useful for always attempting to
 *   lengthen any URL you come across (fails gracefully)
 * 
 * $slinky = new Slinky( 'http://dentedreality.com.au/' );
 * $slinky->set_cascade( array( new Slinky_Trim(), new Slinky_IsGd(), new Slinky_TinyURL() ) );
 * echo $slinky->short();
 * - Uses the powerful cascade mode to make sure that we get a short URL from 
 *   Tr.im, Is.Gd or TinyURL (in that order).
 * 
 * See specific service class definitions below for examples of how to use them,
 * as some services allow (or require) additional properties before you can use
 * them (for authentication etc).
 * 
 * To use a different service with Slinky, just create your own class and
 * extend Slinky_Service(). Make sure you implement url_is_short(), url_is_long(),
 * make_short() and make_long(). If you need to GET or POST a URL, you can use
 * ->url_get() and ->url_post(), which your class will have inherited.
**/
class Slinky {
	var $url     = false;
	var $service = false;
	var $cascade = false;
	
	function __construct( $url = false, $service = false ) {
		$this->url     = $url;
		$this->service = $service;
	}
	
	/**
	 * Specify which URL Service to use
	 *
	 * @param Slinky_Service $service Packaged or custom Service object
	 * @return void
	 */
	public function set_service( $service = false ) {
		if ( is_object( $service ) ) {
			$this->service = $service;
		}
	}
	
	/**
	 * If you pass an array of Slinky_Service objects to this method, they will
	 * be used in order to try to get a short URL, so if one fails, it will
	 * try the next and so on, until it gets a valid short URL, or it runs
	 * out of options.
	 * 
	 * @param array $services List of Slinky_Service objects as an array
	**/
	public function set_cascade( $services = false ) {
		if ( !$services || !is_array( $services ) )
			return false;
		
		$this->cascade = $services;
	}
	
	/**
	 * Guess the URL service to use from known domains of short URLs
	 *
	 * @param string $url 
	 */
	public function set_service_from_url( $url = false ) {
		if ( !$url )
			$url = $this->url;

		$host = parse_url( $url, PHP_URL_HOST );
		switch ( str_replace( 'www.', '', $host ) ) {
			case 'bit.ly':
				if ( class_exists( 'Slinky_Bitly' ) ) {
					$this->service = new Slinky_Bitly();
					break;
				}
			case 'tr.im':
				if ( class_exists( 'Slinky_Trim' ) ) {
					$this->service = new Slinky_Trim();
					break;
				}
			case 'tinyurl.com':
				if ( class_exists( 'Slinky_TinyURL' ) ) {
					$this->service = new Slinky_TinyURL();
					break;
				}
			case 'is.gd':
				if ( class_exists( 'Slinky_IsGd' ) ) {
					$this->service = new Slinky_IsGd();
					break;
				}
			case 'fon.gs':
				if ( class_exists( 'Slinky_Fongs' ) ) {
					$this->service = new Slinky_Fongs();
					break;
				}
			case $this->get( 'yourls-url' ):
				if ( class_exists( 'Slinky_YourLS' ) ) {
					$this->service = new Slinky_YourLS();
					break;
				}
			case 'micurl.com':
				if ( class_exists( 'Slinky_Micurl' ) ) {
					$this->service = new Slinky_Micurl();
					break;
				}
			case 'ur1.ca':
				if ( class_exists( 'Slinky_Ur1ca' ) ) {
					$this->service = new Slinky_Ur1ca();
					break;
				}
			case 'ptiturl.com':
				if ( class_exists( 'Slinky_PtitURL' ) ) {
					$this->service = new Slinky_PtitURL();
					break;
				}
			case 'tighturl.com':
			case '2tu.us':
				if ( class_exists( 'Slinky_TightURL' ) ) {
					$this->service = new Slinky_TightURL();
					break;
				}
			case 'snipr.com':
			case 'snipurl.com':
			case 'snurl.com':
			case 'sn.im':
				if ( class_exists( 'Slinky_Snipr' ) ) {
					$this->service = new Slinky_Snipr();
					break;
				}
			default:
				$this->service = new Slinky_Default();
				break;
		}
	}
	
	/**
	 * Take a long URL and make it short. Will avoid "re-shortening" a URL if it
	 * already seems to be short.
	 *
	 * @param string $url Optional URL to shorten, otherwise use $this->url
	 * @return The short version of the URL
	 */
	public function short( $url = false ) {
		if ( $url )
			$this->url = $url;
			
		if ( !$this->service )
			$this->set_service( new Slinky_TinyURL() ); // Defaults to tinyurl because it doesn't require any configuration
		
		if ( !$this->cascade )
			$this->cascade = array( $this->service ); // Use a single service in cascade mode
		
		foreach ( $this->cascade as $service ) {
			if ( $service->url_is_short( $this->url ) )
				return trim( $this->url ); // Identified as already short, using this service

			$response = trim( $service->make_short( $this->url ) );
			if ( $response && $this->url != $response )
				return trim( $response );
		}
		
		return $this->url; // If all else fails, just send back the URL we already know about
	}
	
	/**
	 * Take a short URL and make it long ("resolve" it).
	 *
	 * @param string $url The short URL
	 * @return A long URL
	 */
	public function long( $url = false ) {
		if ( $url )
			$this->url = $url;
			
		if ( !$this->service )
			$this->set_service_from_url();
		
		if ( $this->service->url_is_long( $this->url ) )
			return trim( $this->url );
		
		return trim( $this->service->make_long( $this->url ) );
	}
}

/**
 * Use this class to create a Service implementation for your own URL 
 * shortening service. Extend the class and customize methods to suit your 
 * service. Note that it is an "abstract" class, so there are certain methods 
 * which you *must* define.
**/
abstract class Slinky_Service {
	
	/**
	 * Determine, based on the input URL, if it's already a short URL, from
	 * this particular service. e.g. a Bit.ly URL contains "bit.ly/"
	**/
	abstract function url_is_short( $url );
	
	/**
	 * Determine if this is a "long" URL (just means it hasn't been shortened)
	 * already. e.g. a no-Bit.ly URL would NOT contain "bit.ly/"
	**/
	abstract function url_is_long( $url );
	
	/**
	 * Handle taking the $url and converting it to a short URL via whatever
	 * means is provided at the remote service.
	**/
	abstract function make_short( $url );
	
	/**
	 * Return the long/expanded version of a URL via any API means available
	 * from this service. As a fallback, you might
	 * consider just following the URL and using SLINKY_FINAL_URL as the 
	 * return method from a $this->url_get() call to find out.
	 * 
	 * This one is optional for Services extending this class, if they don't
	 * then the following implementation will work on most services anyway.
	**/
	public function make_long( $url ) {
		return $this->url_get( $url, SLINKY_FINAL_URL );
	}
	
	/**
	 * Method for getting properties that you might need during the process
	 * of shortening/lengthening a URL (e.g. auth credentials)
	**/
	public function get( $prop ) {
		if ( empty( $this->$prop ) )
			return null;
			
		return $this->$prop;
	}
	
	/**
	 * Method for setting properties that you might need during the process
	 * of shortening/lengthening a URL (e.g. auth credentials)
	**/
	public function set( $prop, $val ) {
		$this->$prop = $val;
	}
	
	/**
	 * Internal helper for performing a GET request on a remote URL.
	 * 
	 * @param string $url The URL to GET
	 * @param const $return The return method [ SLINKY_BODY | SLINKY_FINAL_URL | SLINKY_HEADERS ]
	 * @return Mixed, based on the $return var passed in.
	**/
	protected function url_get( $url, $return = SLINKY_BODY ) {
		$ch = curl_init( $url );
		curl_setopt( $ch, CURLOPT_USERAGENT, SLINKY_USER_AGENT );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );			// Don't stress about SSL validity
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );			// Return the response, don't output it
		curl_setopt( $ch, CURLOPT_TIMEOUT, SLINKY_TIMEOUT );	// Limit how long we'll wait for a response
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );			// Allow following of redirections
		$r  = curl_exec( $ch );
		if ( curl_errno( $ch ) ) {
			return false;
		}
		
		// Return whatever we were asked for
		if ( SLINKY_FINAL_URL == $return )
			return curl_getinfo( $ch, CURLINFO_EFFECTIVE_URL );
		else if ( SLINKY_BODY == $return )
			return $r;
		
		return false;
	}
	
	/**
	 * Internal helper for performing a POST request on a remote URL.
	 * 
	 * @param string $url The URL to POST to
	 * @param array $payload Array containing name/value pairs of the parameters to POST
	 * @param const $return The return method [ SLINKY_BODY | SLINKY_FINAL_URL | SLINKY_HEADERS ]
	 * @return Mixed, based on the $return var passed in.
	**/
	protected function url_post( $url, $payload = array(), $return = SLINKY_BODY ) {
		$ch = curl_init( $url );
		curl_setopt( $ch, CURLOPT_POST, true );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, (array) $payload );
		curl_setopt( $ch, CURLOPT_USERAGENT, SLINKY_USER_AGENT );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );			// Don't stress about SSL validity
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );			// Return the response, don't output it
		curl_setopt( $ch, CURLOPT_TIMEOUT, SLINKY_TIMEOUT );	// Limit how long we'll wait for a response
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );			// Allow following of redirections
		$r  = curl_exec( $ch );
		if ( curl_errno( $ch ) ) {
			return false;
		}
		
		// Return whatever we were asked for
		if ( SLINKY_FINAL_URL == $return )
			return curl_getinfo( $ch, CURLINFO_EFFECTIVE_URL );
		else if ( SLINKY_BODY == $return )
			return $r;
		
		return false;
	}
}

// This default service is used in cases when you try to do something based
// on auto-detection, but we can't detect anything. It will also resolve URLs
// to their "long" version by following all redirects.
class Slinky_Default extends Slinky_Service {
	function url_is_short( $url ) {
		return false;
	}
	
	function url_is_long( $url ) {
		return false;
	}
	
	function make_short( $url ) {
		return $url;
	}
}

// Implementation of TinyURL as a Slinky Service
class Slinky_TinyURL extends Slinky_Service {
	function url_is_short( $url ) {
		return stristr( $url, 'tinyurl.com/' );
	}
	
	function url_is_long( $url ) {
		return !stristr( $url, 'tinyurl.com/' );
	}
	
	function make_short( $url ) {
		return $this->url_get( 'http://tinyurl.com/api-create.php?url=' . urlencode( $url ) );
	}
	
	function make_long( $url ) {
		$bits = parse_url( $url );
		$result = $this->url_get( 'http://tinyurl.com/preview.php?num=' . substr( $bits['path'], 1 ) );
		if ( preg_match('/<a id="redirecturl" href="([^"]+)">/is', $result, $matches ) )
			return $matches[1];
		else
			return $url;
	}
}

// Implementation of Bit.ly as a Slinky Service
/*
To use Bit.ly, you MUST set your login and apiKey for the service first, e.g.

$bitly = new Slinky_Bitly();
$bitly->set( 'login', 'bitly_login' );
$bitly->set( 'apiKey', 'bitly_apiKey' );

$slinky = new Slinky( $url, $bitly );
echo $slinky->short();

You could also do this if the URL was already a bit.ly URL and you 
were going to make it longer, since Bitly is supported for auto-detection:

$slinky = new Slinky( $url );
$slinky->set_service_from_url();
$slinky->service->set( 'login', 'bitly_login' );
$slinky->service->set( 'apiKey', 'bitly_apiKey' );
echo $slinky->long();

*/
class Slinky_Bitly extends Slinky_Service {
	function url_is_short( $url ) {
		return stristr( $url, 'bit.ly/' );
	}
	
	function url_is_long( $url ) {
		return !stristr( $url, 'bit.ly/' );
	}
	
	function make_short( $url ) {
		// Can't do anything unless these 2 properties are set first
		if ( !$this->get( 'login' ) || !$this->get( 'apiKey' ) )
			return $url;
		
		$result = $this->url_post( 'http://api.bit.ly/shorten?version=2.0.1&format=json&login=' . $this->get( 'login' ) . '&apiKey=' . $this->get( 'apiKey' ) . '&longUrl=' . urlencode( $url ) );
		$result = json_decode( $result );
		if ( !$result->errorCode ) {
			foreach ( $result->results as $detail ) {
				return $detail->shortUrl;
			}
		} else {
			return false;
		}
	}
	
	function make_long( $url ) {
		// Can't do anything unless these 2 properties are set first
		if ( !$this->get( 'login' ) || !$this->get( 'apiKey' ) )
			return $url;
		
		$result = $this->url_post( 'http://api.bit.ly/expand?version=2.0.1&format=json&login=' . $this->get( 'login' ) . '&apiKey=' . $this->get( 'apiKey' ) . '&shortUrl=' . urlencode( $url ) );
		$result = json_decode( $result );
		if ( !$result->errorCode ) {
			foreach ( $result->results as $detail ) {
				return $detail->longUrl;
			}
		} else {
			return false;
		}
	}
}

// Implementation of Tr.im as a Slinky Service
/*
When using Tr.im, you MAY optionally set your username and password to tie 
URLs to your account, e.g.

$trim = new Slinky_Trim();
$trim->set( 'username', 'trim_username' );
$trim->set( 'password', 'trim_password' );

$slinky = new Slinky( $url, $trim );
echo $slinky->short();

You could also do this if the URL was already a tr.im URL and you 
were going to make it longer, since Tr.im is supported for auto-detection:

$slinky = new Slinky( $url );
$slinky->set_service_from_url();
echo $slinky->long();

*/
class Slinky_Trim extends Slinky_Service {
	function url_is_short( $url ) {
		return stristr( $url, 'tr.im/' );
	}
	
	function url_is_long( $url ) {
		return !stristr( $url, 'tr.im/' );
	}
	
	function make_short( $url ) {
		$url = 'http://api.tr.im/api/trim_simple?url=' . urlencode( $url );

		if ( $this->get( 'username' ) && $this->get( 'password' ) )
			$url .= '&username=' . urlencode( $this->get( 'username' ) ) . '&password=' . urlencode( $this->get( 'password' ) );
		
		return $this->url_get( $url );
	}
	
	function make_long( $url ) {
		$bits = parse_url( $url );
		$result = $this->url_get( 'http://api.tr.im/api/trim_destination.json?trimpath=' . substr( $bits['path'], 1 ) );
		$result = json_decode($result);
		if ( 'OK' == $result->status->result )
			return $result->destination;
		else
			return $url;
	}
}

// Implementation of Is.Gd as a Slinky Service
class Slinky_IsGd extends Slinky_Service {
	function url_is_short( $url ) {
		return stristr( $url, 'is.gd/' );
	}
	
	function url_is_long( $url ) {
		return !stristr( $url, 'is.gd/' );
	}
	
	function make_short( $url ) {
		$response = $this->url_get( 'http://is.gd/api.php?longurl=' . urlencode( $url ) );
		if ( 'error' == substr( strtolower( $response ), 0, 5 ) )
			return false;
		else
			return $response;
	}
}

// Fon.gs
class Slinky_Fongs extends Slinky_Service {
	function url_is_short( $url ) {
		return stristr( $url, 'fon.gs/' );
	}
	
	function url_is_long( $url ) {
		return !stristr( $url, 'fon.gs/' );
	}
	
	function make_short( $url ) {
		$response = $this->url_get( 'http://fon.gs/create.php?url=' . urlencode( $url ) );
		if ( 'OK:' == substr( $response, 0, 3 ) )
			return str_replace( 'OK: ', '', $response );
		else
			return $url;
	}
}

// yourls
class Slinky_YourLS extends Slinky_Service {
	function url_is_short( $url ) {
	return stristr( $url, 'shit.li/' );
    }
    
	function url_is_long( $url ) {
	return !stristr( $url, 'shit.li/' );
    }
    
	function make_short( $url ) {
		echo $this->get( 'username' );
		$use_ssl = $this->get( 'ssl' );
		if ( $use_ssl )
			$use_ssl  = 's';
		else
			$use_ssl = '';
		$result = $this->url_get( 'http'. $use_ssl . '://' . $this->get( 'yourls-url' ) . '/yourls-api.php?username=' . $this->get( 'username' )  . '&password=' . $this->get( 'password' ) . '&action=shorturl&format=simple&url=' . urlencode( $url ) );
		if ( 1 != $result && 2 != $result )
			return $result;
		else
			return $url;
	}
}

// Micu.rl
class Slinky_Micurl extends Slinky_Service {
	function url_is_short( $url ) {
		return stristr( $url, 'micurl.com/' );
	}
	
	function url_is_long( $url ) {
		return !stristr( $url, 'micurl.com/' );
	}
	
	function make_short( $url ) {
		$result = $this->url_get( 'http://micurl.com/api.php?url=' . urlencode( $url ) );
		if ( 1 != $result && 2 != $result )
			return 'http://micurl.com/' . $result;
		else
			return $url;
	}
}

// ur1.ca
class Slinky_Ur1ca extends Slinky_Service {
	function url_is_short( $url ) {
		return stristr( $url, 'ur1.ca/' );
	}
	
	function url_is_long( $url ) {
		return !stristr( $url, 'ur1.ca/' );
	}
	
	function make_short( $url ) {
		$result = $this->url_post( 'http://ur1.ca/', array( 'longurl' => $url ) );
		if ( preg_match( '/<p class="success">Your ur1 is: <a href="([^"]+)">/', $result, $matches ) )
			return $matches[1];
		else
			return $url;
	}
}

// PtitURL.com
class Slinky_PtitURL extends Slinky_Service {
	function url_is_short( $url ) {
		return stristr( $url, 'ptiturl.com/' );
	}
	
	function url_is_long( $url ) {
		return !stristr( $url, 'ptiturl.com/' );
	}
	
	function make_short( $url ) {
		$result = $this->url_get( 'http://ptiturl.com/index.php?creer=oui&url=' . urlencode( $url ) );
		if ( preg_match( '/<pre><a href=\'?([^\'>]+)\'?>/', $result, $matches ) )
			return $matches[1];
		else
			return $url;
	}
}

// Tighturl.com
class Slinky_TightURL extends Slinky_Service {
	function url_is_short( $url ) {
		return stristr( $url, 'tighturl.com/' )
				|| stristr( $url, '2tu.us/' );
	}
	
	function url_is_long( $url ) {
		return !stristr( $url, 'tighturl.com/' )
				&& !stristr( $url, '2tu.us/' );
	}
	
	function make_short( $url ) {
		$response = $this->url_get( 'http://tighturl.com/?save=y&url=' . urlencode( $url ) );
		if ( preg_match( '/Your tight URL is: <code><a href=\'([^\']+)\' target=\'_blank\'>/', $response, $matches ) ) {
			return $matches[1];
		} else {
			return $url;
		}
	}
}

// Snipr for Slinky
/*
To use Snipr, you MUST set your user_id and API (key) for the service first, e.g.

$snipr = new Slinky_Snipr();
$snipr->set( 'user_id', 'Snipr User ID' );
$snipr->set( 'API', 'Snipr API Key' );

$slinky = new Slinky( $url, $snipr );
echo $slinky->short();

NOTE: Snipr requires the SimpleXML extension to be installed for lengthening URLs
*/
class Slinky_Snipr extends Slinky_Service {
	// Snipurl, Snurl, Snipr, Sn.im
	function url_is_short( $url ) {
		return stristr( $url, 'snipr.com/' ) || stristr( $url, 'snipurl.com/' ) || stristr( $url, 'snurl.com/' ) || stristr( $url, 'sn.im/' );
	}
	
	function url_is_long( $url ) {
		return !stristr( $url, 'snipr.com/' ) || !stristr( $url, 'snipurl.com/' ) || !stristr( $url, 'snurl.com/' ) || !stristr( $url, 'sn.im/' );
	}
	
	function make_short( $url ) {
		if ( !$this->get( 'user_id' ) || !$this->get( 'API' ) )
			return $url;
		
		$response = $this->url_post( 'http://snipr.com/site/getsnip', array( 'sniplink' => urlencode( $url ), 'snipuser' => $this->get( 'user_id'), 'snipapi' => $this->get( 'API' ), 'snipformat' => 'simple' ) );
		if ( 'ERROR' != substr( $response, 0, 5 ) )
			return $response;
		else
			return $url;
	}
}

// If you're testing things out, http://dentedreality.com.au/ should convert to:
// - http://tinyurl.com/jw5sh
// - http://bit.ly/hEkAD
// - http://tr.im/sk1H
// - http://is.gd/1yJ81
// - http://fon.gs/tc1p8c
// - http://micurl.com/qen3uub
// - http://ur1.ca/7dcd
// - http://ptiturl.com/?id=bac8fb
// - http://tighturl.com/kgd
// - http://snipr.com/nbbw3
// 
// $slinky = new Slinky( 'http://dentedreality.com.au/' );
// echo $slinky->short();
