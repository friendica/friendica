<?php
/**
 * PEAR_Proxy
 *
 * HTTP Proxy handling 
 *
 * @category   pear
 * @package    PEAR
 * @author     Nico Boehr 
 * @copyright  1997-2009 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       http://pear.php.net/package/PEAR
 */

class PEAR_Proxy
{
    var $config = null;

    /**
     * @access private
     */
    var $proxy_host;
    /**
     * @access private
     */
    var $proxy_port;
    /**
     * @access private
     */
    var $proxy_user;
    /**
     * @access private
     */
    var $proxy_pass;
    /**
     * @access private
     */
    var $proxy_schema;

    function __construct($config = null)
    {
        $this->config = $config;
        $this->_parseProxyInfo();
    }

    /**
     * @access private
     */
    function _parseProxyInfo()
    {
        $this->proxy_host = $this->proxy_port = $this->proxy_user = $this->proxy_pass = '';
        if ($this->config->get('http_proxy')&&
              $proxy = parse_url($this->config->get('http_proxy'))
        ) {
            $this->proxy_host = isset($proxy['host']) ? $proxy['host'] : null;

            $this->proxy_port   = isset($proxy['port']) ? $proxy['port'] : 8080;
            $this->proxy_user   = isset($proxy['user']) ? urldecode($proxy['user']) : null;
            $this->proxy_pass   = isset($proxy['pass']) ? urldecode($proxy['pass']) : null;
            $this->proxy_schema = (isset($proxy['scheme']) && $proxy['scheme'] == 'https') ? 'https' : 'http';
        }
    }

    /**
     * @access private
     */
    function _httpConnect($fp, $host, $port)
    {
        fwrite($fp, "CONNECT $host:$port HTTP/1.1\r\n");
        fwrite($fp, "Host: $host:$port\r\n\r\n");

        while ($line = trim(fgets($fp, 1024))) {
            if (preg_match('|^HTTP/1.[01] ([0-9]{3}) |', $line, $matches)) {
                $code = (int)$matches[1];

                /* as per RFC 2817 */
                if ($code < 200 || $code >= 300) {
                    return PEAR::raiseError("Establishing a CONNECT tunnel through proxy failed with response code $code");
                }
            }
        }

        // connection was successful -- establish SSL through
        // the tunnel
        $crypto_method = STREAM_CRYPTO_METHOD_TLS_CLIENT;

        if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
            $crypto_method |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
            $crypto_method |= STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT;
        }

        // set the correct hostname for working hostname
        // verification
        stream_context_set_option($fp, 'ssl', 'peer_name', $host);

        // blocking socket needed for
        // stream_socket_enable_crypto()
        // see
        // <http://php.net/manual/en/function.stream-socket-enable-crypto.php>
        stream_set_blocking ($fp, true);
        $crypto_res = stream_socket_enable_crypto($fp, true, $crypto_method);
        if (!$crypto_res) {
            return PEAR::raiseError("Could not establish SSL connection through proxy $proxy_host:$proxy_port: $crypto_res");
        }

        return true;
    }

    /**
     * get the authorization information for the proxy, encoded to be
     * passed in the Proxy-Authentication HTTP header.
     * @return null|string the encoded authentication information if a
     *                     proxy and authentication is configured, null 
     *                     otherwise.
     */
    function getProxyAuth()
    {
        if ($this->isProxyConfigured() && $this->proxy_user != '') {
            return base64_encode($this->proxy_user . ':' . $this->proxy_pass);
        }
        return null;
    }

    function getProxyUser()
    {
        return $this->proxy_user;
    }

    /**
     * Check if we are configured to use a proxy.
     *
     * @return boolean true if we are configured to use a proxy, false
     *                 otherwise.
     * @access public
     */
    function isProxyConfigured()
    {
        return $this->proxy_host != '';
    }

    /**
     * Open a socket to a remote server, possibly involving a HTTP
     * proxy.
     *
     * If an HTTP proxy has been configured (http_proxy PEAR_Config
     * setting), the proxy will be used.
     *
     * @param string $host    the host to connect to
     * @param string $port    the port to connect to
     * @param boolean $secure if true, establish a secure connection
     *                        using TLS.
     * @access public
     */
    function openSocket($host, $port, $secure = false)
    {
        if ($this->isProxyConfigured()) {
            $fp = @fsockopen(
                $this->proxy_host, $this->proxy_port, 
                $errno, $errstr, 15
            );

            if (!$fp) {
                return PEAR::raiseError("Connection to `$proxy_host:$proxy_port' failed: $errstr", -9276);
            }

            /* HTTPS is to be used and we have a proxy, use CONNECT verb */
            if ($secure) {
                $res = $this->_httpConnect($fp, $host, $port);

                if (PEAR::isError($res)) {
                    return $res;
                }
            }
        } else {
            if ($secure) {
                $host = 'ssl://' . $host;
            }

            $fp = @fsockopen($host, $port, $errno, $errstr);
            if (!$fp) {
                return PEAR::raiseError("Connection to `$host:$port' failed: $errstr", $errno);
            }
        }

        return $fp;
    }
}
