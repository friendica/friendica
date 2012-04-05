<?php

/**
 * @mainpage
 * OAuth 2.0 server in PHP, originally written for
 * <a href="http://www.opendining.net/"> Open Dining</a>. Supports
 * <a href="http://tools.ietf.org/html/draft-ietf-oauth-v2-10">IETF draft v10</a>.
 *
 * Source repo has sample servers implementations for
 * <a href="http://php.net/manual/en/book.pdo.php"> PHP Data Objects</a> and
 * <a href="http://www.mongodb.org/">MongoDB</a>. Easily adaptable to other
 * storage engines.
 *
 * PHP Data Objects supports a variety of databases, including MySQL,
 * Microsoft SQL Server, SQLite, and Oracle, so you can try out the sample
 * to see how it all works.
 *
 * We're expanding the wiki to include more helpful documentation, but for
 * now, your best bet is to view the oauth.php source - it has lots of
 * comments.
 *
 * @author Tim Ridgely <tim.ridgely@gmail.com>
 * @author Aaron Parecki <aaron@parecki.com>
 * @author Edison Wong <hswong3i@pantarei-design.com>
 *
 * @see http://code.google.com/p/oauth2-php/
 */


/**
 * The default duration in seconds of the access token lifetime.
 */
define("OAUTH2_DEFAULT_ACCESS_TOKEN_LIFETIME", 3600);

/**
 * The default duration in seconds of the authorization code lifetime.
 */
define("OAUTH2_DEFAULT_AUTH_CODE_LIFETIME", 30);

/**
 * The default duration in seconds of the refresh token lifetime.
 */
define("OAUTH2_DEFAULT_REFRESH_TOKEN_LIFETIME", 1209600);


/**
 * @defgroup oauth2_section_2 Client Credentials
 * @{
 *
 * When interacting with the authorization server, the client identifies
 * itself using a client identifier and authenticates using a set of
 * client credentials. This specification provides one mechanism for
 * authenticating the client using password credentials.
 *
 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-2
 */

/**
 * Regex to filter out the client identifier (described in Section 2 of IETF draft).
 *
 * IETF draft does not prescribe a format for these, however I've arbitrarily
 * chosen alphanumeric strings with hyphens and underscores, 3-32 characters
 * long.
 *
 * Feel free to change.
 */
define("OAUTH2_CLIENT_ID_REGEXP", "/^[a-z0-9-_]{3,32}$/i");

/**
 * @}
 */


/**
 * @defgroup oauth2_section_3 Obtaining End-User Authorization
 * @{
 *
 * When the client interacts with an end-user, the end-user MUST first
 * grant the client authorization to access its protected resources.
 * Once obtained, the end-user access grant is expressed as an
 * authorization code which the client uses to obtain an access token.
 * To obtain an end-user authorization, the client sends the end-user to
 * the end-user authorization endpoint.
 *
 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-3
 */

/**
 * Denotes "token" authorization response type.
 */
define("OAUTH2_AUTH_RESPONSE_TYPE_ACCESS_TOKEN", "token");

/**
 * Denotes "code" authorization response type.
 */
define("OAUTH2_AUTH_RESPONSE_TYPE_AUTH_CODE", "code");

/**
 * Denotes "code-and-token" authorization response type.
 */
define("OAUTH2_AUTH_RESPONSE_TYPE_CODE_AND_TOKEN", "code-and-token");

/**
 * Regex to filter out the authorization response type.
 */
define("OAUTH2_AUTH_RESPONSE_TYPE_REGEXP", "/^(token|code|code-and-token)$/");

/**
 * @}
 */


/**
 * @defgroup oauth2_section_4 Obtaining an Access Token
 * @{
 *
 * The client obtains an access token by authenticating with the
 * authorization server and presenting its access grant (in the form of
 * an authorization code, resource owner credentials, an assertion, or a
 * refresh token).
 *
 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-4
 */

/**
 * Denotes "authorization_code" grant types (for token obtaining).
 */
define("OAUTH2_GRANT_TYPE_AUTH_CODE", "authorization_code");

/**
 * Denotes "password" grant types (for token obtaining).
 */
define("OAUTH2_GRANT_TYPE_USER_CREDENTIALS", "password");

/**
 * Denotes "assertion" grant types (for token obtaining).
 */
define("OAUTH2_GRANT_TYPE_ASSERTION", "assertion");

/**
 * Denotes "refresh_token" grant types (for token obtaining).
 */
define("OAUTH2_GRANT_TYPE_REFRESH_TOKEN", "refresh_token");

/**
 * Denotes "none" grant types (for token obtaining).
 */
define("OAUTH2_GRANT_TYPE_NONE", "none");

/**
 * Regex to filter out the grant type.
 */
define("OAUTH2_GRANT_TYPE_REGEXP", "/^(authorization_code|password|assertion|refresh_token|none)$/");

/**
 * @}
 */


/**
 * @defgroup oauth2_section_5 Accessing a Protected Resource
 * @{
 *
 * Clients access protected resources by presenting an access token to
 * the resource server. Access tokens act as bearer tokens, where the
 * token string acts as a shared symmetric secret. This requires
 * treating the access token with the same care as other secrets (e.g.
 * end-user passwords). Access tokens SHOULD NOT be sent in the clear
 * over an insecure channel.
 *
 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-5
 */

/**
 * Used to define the name of the OAuth access token parameter (POST/GET/etc.).
 *
 * IETF Draft sections 5.1.2 and 5.1.3 specify that it should be called
 * "oauth_token" but other implementations use things like "access_token".
 *
 * I won't be heartbroken if you change it, but it might be better to adhere
 * to the spec.
 *
 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-5.1.2
 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-5.1.3
 */
define("OAUTH2_TOKEN_PARAM_NAME", "oauth_token");

/**
 * @}
 */


/**
 * @defgroup oauth2_http_status HTTP status code
 * @{
 */

/**
 * "Found" HTTP status code.
 *
 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-3
 */
define("OAUTH2_HTTP_FOUND", "302 Found");

/**
 * "Bad Request" HTTP status code.
 *
 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-4.3
 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-5.2.1
 */
define("OAUTH2_HTTP_BAD_REQUEST", "400 Bad Request");

/**
 * "Unauthorized" HTTP status code.
 *
 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-4.3
 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-5.2.1
 */
define("OAUTH2_HTTP_UNAUTHORIZED", "401 Unauthorized");

/**
 * "Forbidden" HTTP status code.
 *
 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-5.2.1
 */
define("OAUTH2_HTTP_FORBIDDEN", "403 Forbidden");

/**
 * @}
 */


/**
 * @defgroup oauth2_error Error handling
 * @{
 *
 * @todo Extend for i18n.
 */

/**
 * The request is missing a required parameter, includes an unsupported
 * parameter or parameter value, or is otherwise malformed.
 *
 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-3.2.1
 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-4.3.1
 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-5.2.1
 */
define("OAUTH2_ERROR_INVALID_REQUEST", "invalid_request");

/**
 * The client identifier provided is invalid.
 *
 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-3.2.1
 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-4.3.1
 */
define("OAUTH2_ERROR_INVALID_CLIENT", "invalid_client");

/**
 * The client is not authorized to use the requested response type.
 *
 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-3.2.1
 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-4.3.1
 */
define("OAUTH2_ERROR_UNAUTHORIZED_CLIENT", "unauthorized_client");

/**
 * The redirection URI provided does not match a pre-registered value.
 *
 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-3.2.1
 */
define("OAUTH2_ERROR_REDIRECT_URI_MISMATCH", "redirect_uri_mismatch");

/**
 * The end-user or authorization server denied the request.
 *
 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-3.2.1
 */
define("OAUTH2_ERROR_USER_DENIED", "access_denied");

/**
 * The requested response type is not supported by the authorization server.
 *
 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-3.2.1
 */
define("OAUTH2_ERROR_UNSUPPORTED_RESPONSE_TYPE", "unsupported_response_type");

/**
 * The requested scope is invalid, unknown, or malformed.
 *
 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-3.2.1
 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-4.3.1
 */
define("OAUTH2_ERROR_INVALID_SCOPE", "invalid_scope");

/**
 * The provided access grant is invalid, expired, or revoked (e.g. invalid
 * assertion, expired authorization token, bad end-user password credentials,
 * or mismatching authorization code and redirection URI).
 *
 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-4.3.1
 */
define("OAUTH2_ERROR_INVALID_GRANT", "invalid_grant");

/**
 * The access grant included - its type or another attribute - is not
 * supported by the authorization server.
 *
 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-4.3.1
 */
define("OAUTH2_ERROR_UNSUPPORTED_GRANT_TYPE", "unsupported_grant_type");

/**
 * The access token provided is invalid. Resource servers SHOULD use this
 * error code when receiving an expired token which cannot be refreshed to
 * indicate to the client that a new authorization is necessary. The resource
 * server MUST respond with the HTTP 401 (Unauthorized) status code.
 *
 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-5.2.1
 */
define("OAUTH2_ERROR_INVALID_TOKEN", "invalid_token");

/**
 * The access token provided has expired. Resource servers SHOULD only use
 * this error code when the client is expected to be able to handle the
 * response and request a new access token using the refresh token issued
 * with the expired access token. The resource server MUST respond with the
 * HTTP 401 (Unauthorized) status code.
 *
 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-5.2.1
 */
define("OAUTH2_ERROR_EXPIRED_TOKEN", "expired_token");

/**
 * The request requires higher privileges than provided by the access token.
 * The resource server SHOULD respond with the HTTP 403 (Forbidden) status
 * code and MAY include the "scope" attribute with the scope necessary to
 * access the protected resource.
 *
 * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-5.2.1
 */
define("OAUTH2_ERROR_INSUFFICIENT_SCOPE", "insufficient_scope");

/**
 * @}
 */

/**
 * OAuth2.0 draft v10 server-side implementation.
 *
 * @author Originally written by Tim Ridgely <tim.ridgely@gmail.com>.
 * @author Updated to draft v10 by Aaron Parecki <aaron@parecki.com>.
 * @author Debug, coding style clean up and documented by Edison Wong <hswong3i@pantarei-design.com>.
 */
abstract class OAuth2 {

  /**
   * Array of persistent variables stored.
   */
  protected $conf = array();

  /**
   * Returns a persistent variable.
   *
   * To avoid problems, always use lower case for persistent variable names.
   *
   * @param $name
   *   The name of the variable to return.
   * @param $default
   *   The default value to use if this variable has never been set.
   *
   * @return
   *   The value of the variable.
   */
  public function getVariable($name, $default = NULL) {
    return isset($this->conf[$name]) ? $this->conf[$name] : $default;
  }

  /**
   * Sets a persistent variable.
   *
   * To avoid problems, always use lower case for persistent variable names.
   *
   * @param $name
   *   The name of the variable to set.
   * @param $value
   *   The value to set.
   */
  public function setVariable($name, $value) {
    $this->conf[$name] = $value;
    return $this;
  }

  // Subclasses must implement the following functions.

  /**
   * Make sure that the client credentials is valid.
   *
   * @param $client_id
   *   Client identifier to be check with.
   * @param $client_secret
   *   (optional) If a secret is required, check that they've given the right one.
   *
   * @return
   *   TRUE if client credentials are valid, and MUST return FALSE if invalid.
   *
   * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-2.1
   *
   * @ingroup oauth2_section_2
   */
  abstract protected function checkClientCredentials($client_id, $client_secret = NULL);

  /**
   * Get the registered redirect URI of corresponding client_id.
   *
   * OAuth says we should store request URIs for each registered client.
   * Implement this function to grab the stored URI for a given client id.
   *
   * @param $client_id
   *   Client identifier to be check with.
   *
   * @return
   *   Registered redirect URI of corresponding client identifier, and MUST
   *   return FALSE if the given client does not exist or is invalid.
   *
   * @ingroup oauth2_section_3
   */
  abstract protected function getRedirectUri($client_id);

  /**
   * Look up the supplied oauth_token from storage.
   *
   * We need to retrieve access token data as we create and verify tokens.
   *
   * @param $oauth_token
   *   oauth_token to be check with.
   *
   * @return
   *   An associative array as below, and return NULL if the supplied oauth_token
   *   is invalid:
   *   - client_id: Stored client identifier.
   *   - expires: Stored expiration in unix timestamp.
   *   - scope: (optional) Stored scope values in space-separated string.
   *
   * @ingroup oauth2_section_5
   */
  abstract protected function getAccessToken($oauth_token);

  /**
   * Store the supplied access token values to storage.
   *
   * We need to store access token data as we create and verify tokens.
   *
   * @param $oauth_token
   *   oauth_token to be stored.
   * @param $client_id
   *   Client identifier to be stored.
   * @param $expires
   *   Expiration to be stored.
   * @param $scope
   *   (optional) Scopes to be stored in space-separated string.
   *
   * @ingroup oauth2_section_4
   */
  abstract protected function setAccessToken($oauth_token, $client_id, $expires, $scope = NULL);

  // Stuff that should get overridden by subclasses.
  //
  // I don't want to make these abstract, because then subclasses would have
  // to implement all of them, which is too much work.
  //
  // So they're just stubs. Override the ones you need.

  /**
   * Return supported grant types.
   *
   * You should override this function with something, or else your OAuth
   * provider won't support any grant types!
   *
   * @return
   *   A list as below. If you support all grant types, then you'd do:
   * @code
   * return array(
   *   OAUTH2_GRANT_TYPE_AUTH_CODE,
   *   OAUTH2_GRANT_TYPE_USER_CREDENTIALS,
   *   OAUTH2_GRANT_TYPE_ASSERTION,
   *   OAUTH2_GRANT_TYPE_REFRESH_TOKEN,
   *   OAUTH2_GRANT_TYPE_NONE,
   * );
   * @endcode
   *
   * @ingroup oauth2_section_4
   */
  protected function getSupportedGrantTypes() {
    return array();
  }

  /**
   * Return supported authorization response types.
   *
   * You should override this function with your supported response types.
   *
   * @return
   *   A list as below. If you support all authorization response types,
   *   then you'd do:
   * @code
   * return array(
   *   OAUTH2_AUTH_RESPONSE_TYPE_AUTH_CODE,
   *   OAUTH2_AUTH_RESPONSE_TYPE_ACCESS_TOKEN,
   *   OAUTH2_AUTH_RESPONSE_TYPE_CODE_AND_TOKEN,
   * );
   * @endcode
   *
   * @ingroup oauth2_section_3
   */
  protected function getSupportedAuthResponseTypes() {
    return array(
      OAUTH2_AUTH_RESPONSE_TYPE_AUTH_CODE,
      OAUTH2_AUTH_RESPONSE_TYPE_ACCESS_TOKEN,
      OAUTH2_AUTH_RESPONSE_TYPE_CODE_AND_TOKEN
    );
  }

  /**
   * Return supported scopes.
   *
   * If you want to support scope use, then have this function return a list
   * of all acceptable scopes (used to throw the invalid-scope error).
   *
   * @return
   *   A list as below, for example:
   * @code
   * return array(
   *   'my-friends',
   *   'photos',
   *   'whatever-else',
   * );
   * @endcode
   *
   * @ingroup oauth2_section_3
   */
  protected function getSupportedScopes() {
    return array();
  }

  /**
   * Check restricted authorization response types of corresponding Client
   * identifier.
   *
   * If you want to restrict clients to certain authorization response types,
   * override this function.
   *
   * @param $client_id
   *   Client identifier to be check with.
   * @param $response_type
   *   Authorization response type to be check with, would be one of the
   *   values contained in OAUTH2_AUTH_RESPONSE_TYPE_REGEXP.
   *
   * @return
   *   TRUE if the authorization response type is supported by this
   *   client identifier, and FALSE if it isn't.
   *
   * @ingroup oauth2_section_3
   */
  protected function checkRestrictedAuthResponseType($client_id, $response_type) {
    return TRUE;
  }

  /**
   * Check restricted grant types of corresponding client identifier.
   *
   * If you want to restrict clients to certain grant types, override this
   * function.
   *
   * @param $client_id
   *   Client identifier to be check with.
   * @param $grant_type
   *   Grant type to be check with, would be one of the values contained in
   *   OAUTH2_GRANT_TYPE_REGEXP.
   *
   * @return
   *   TRUE if the grant type is supported by this client identifier, and
   *   FALSE if it isn't.
   *
   * @ingroup oauth2_section_4
   */
  protected function checkRestrictedGrantType($client_id, $grant_type) {
    return TRUE;
  }

  // Functions that help grant access tokens for various grant types.

  /**
   * Fetch authorization code data (probably the most common grant type).
   *
   * Retrieve the stored data for the given authorization code.
   *
   * Required for OAUTH2_GRANT_TYPE_AUTH_CODE.
   *
   * @param $code
   *   Authorization code to be check with.
   *
   * @return
   *   An associative array as below, and NULL if the code is invalid:
   *   - client_id: Stored client identifier.
   *   - redirect_uri: Stored redirect URI.
   *   - expires: Stored expiration in unix timestamp.
   *   - scope: (optional) Stored scope values in space-separated string.
   *
   * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-4.1.1
   *
   * @ingroup oauth2_section_4
   */
  protected function getAuthCode($code) {
    return NULL;
  }

  /**
   * Take the provided authorization code values and store them somewhere.
   *
   * This function should be the storage counterpart to getAuthCode().
   *
   * If storage fails for some reason, we're not currently checking for
   * any sort of success/failure, so you should bail out of the script
   * and provide a descriptive fail message.
   *
   * Required for OAUTH2_GRANT_TYPE_AUTH_CODE.
   *
   * @param $code
   *   Authorization code to be stored.
   * @param $client_id
   *   Client identifier to be stored.
   * @param $redirect_uri
   *   Redirect URI to be stored.
   * @param $expires
   *   Expiration to be stored.
   * @param $scope
   *   (optional) Scopes to be stored in space-separated string.
   *
   * @ingroup oauth2_section_4
   */
  protected function setAuthCode($code, $client_id, $redirect_uri, $expires, $scope = NULL) {
  }

  /**
   * Grant access tokens for basic user credentials.
   *
   * Check the supplied username and password for validity.
   *
   * You can also use the $client_id param to do any checks required based
   * on a client, if you need that.
   *
   * Required for OAUTH2_GRANT_TYPE_USER_CREDENTIALS.
   *
   * @param $client_id
   *   Client identifier to be check with.
   * @param $username
   *   Username to be check with.
   * @param $password
   *   Password to be check with.
   *
   * @return
   *   TRUE if the username and password are valid, and FALSE if it isn't.
   *   Moreover, if the username and password are valid, and you want to
   *   verify the scope of a user's access, return an associative array
   *   with the scope values as below. We'll check the scope you provide
   *   against the requested scope before providing an access token:
   * @code
   * return array(
   *   'scope' => <stored scope values (space-separated string)>,
   * );
   * @endcode
   *
   * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-4.1.2
   *
   * @ingroup oauth2_section_4
   */
  protected function checkUserCredentials($client_id, $username, $password) {
    return FALSE;
  }

  /**
   * Grant access tokens for assertions.
   *
   * Check the supplied assertion for validity.
   *
   * You can also use the $client_id param to do any checks required based
   * on a client, if you need that.
   *
   * Required for OAUTH2_GRANT_TYPE_ASSERTION.
   *
   * @param $client_id
   *   Client identifier to be check with.
   * @param $assertion_type
   *   The format of the assertion as defined by the authorization server.
   * @param $assertion
   *   The assertion.
   *
   * @return
   *   TRUE if the assertion is valid, and FALSE if it isn't. Moreover, if
   *   the assertion is valid, and you want to verify the scope of an access
   *   request, return an associative array with the scope values as below.
   *   We'll check the scope you provide against the requested scope before
   *   providing an access token:
   * @code
   * return array(
   *   'scope' => <stored scope values (space-separated string)>,
   * );
   * @endcode
   *
   * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-4.1.3
   *
   * @ingroup oauth2_section_4
   */
  protected function checkAssertion($client_id, $assertion_type, $assertion) {
    return FALSE;
  }

  /**
   * Grant refresh access tokens.
   *
   * Retrieve the stored data for the given refresh token.
   *
   * Required for OAUTH2_GRANT_TYPE_REFRESH_TOKEN.
   *
   * @param $refresh_token
   *   Refresh token to be check with.
   *
   * @return
   *   An associative array as below, and NULL if the refresh_token is
   *   invalid:
   *   - client_id: Stored client identifier.
   *   - expires: Stored expiration unix timestamp.
   *   - scope: (optional) Stored scope values in space-separated string.
   *
   * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-4.1.4
   *
   * @ingroup oauth2_section_4
   */
  protected function getRefreshToken($refresh_token) {
    return NULL;
  }

  /**
   * Take the provided refresh token values and store them somewhere.
   *
   * This function should be the storage counterpart to getRefreshToken().
   *
   * If storage fails for some reason, we're not currently checking for
   * any sort of success/failure, so you should bail out of the script
   * and provide a descriptive fail message.
   *
   * Required for OAUTH2_GRANT_TYPE_REFRESH_TOKEN.
   *
   * @param $refresh_token
   *   Refresh token to be stored.
   * @param $client_id
   *   Client identifier to be stored.
   * @param $expires
   *   expires to be stored.
   * @param $scope
   *   (optional) Scopes to be stored in space-separated string.
   *
   * @ingroup oauth2_section_4
   */
  protected function setRefreshToken($refresh_token, $client_id, $expires, $scope = NULL) {
    return;
  }

  /**
   * Expire a used refresh token.
   *
   * This is not explicitly required in the spec, but is almost implied.
   * After granting a new refresh token, the old one is no longer useful and
   * so should be forcibly expired in the data store so it can't be used again.
   *
   * If storage fails for some reason, we're not currently checking for
   * any sort of success/failure, so you should bail out of the script
   * and provide a descriptive fail message.
   *
   * @param $refresh_token
   *   Refresh token to be expirse.
   *
   * @ingroup oauth2_section_4
   */
  protected function unsetRefreshToken($refresh_token) {
    return;
  }

  /**
   * Grant access tokens for the "none" grant type.
   *
   * Not really described in the IETF Draft, so I just left a method
   * stub... Do whatever you want!
   *
   * Required for OAUTH2_GRANT_TYPE_NONE.
   *
   * @ingroup oauth2_section_4
   */
  protected function checkNoneAccess($client_id) {
    return FALSE;
  }

  /**
   * Get default authentication realm for WWW-Authenticate header.
   *
   * Change this to whatever authentication realm you want to send in a
   * WWW-Authenticate header.
   *
   * @return
   *   A string that you want to send in a WWW-Authenticate header.
   *
   * @ingroup oauth2_error
   */
  protected function getDefaultAuthenticationRealm() {
    return "Service";
  }

  // End stuff that should get overridden.

  /**
   * Creates an OAuth2.0 server-side instance.
   *
   * @param $config
   *   An associative array as below:
   *   - access_token_lifetime: (optional) The lifetime of access token in
   *     seconds.
   *   - auth_code_lifetime: (optional) The lifetime of authorization code in
   *     seconds.
   *   - refresh_token_lifetime: (optional) The lifetime of refresh token in
   *     seconds.
   *   - display_error: (optional) Whether to show verbose error messages in
   *     the response.
   */
  public function __construct($config = array()) {
    foreach ($config as $name => $value) {
      $this->setVariable($name, $value);
    }
  }

  // Resource protecting (Section 5).

  /**
   * Check that a valid access token has been provided.
   *
   * The scope parameter defines any required scope that the token must have.
   * If a scope param is provided and the token does not have the required
   * scope, we bounce the request.
   *
   * Some implementations may choose to return a subset of the protected
   * resource (i.e. "public" data) if the user has not provided an access
   * token or if the access token is invalid or expired.
   *
   * The IETF spec says that we should send a 401 Unauthorized header and
   * bail immediately so that's what the defaults are set to.
   *
   * @param $scope
   *   A space-separated string of required scope(s), if you want to check
   *   for scope.
   * @param $exit_not_present
   *   If TRUE and no access token is provided, send a 401 header and exit,
   *   otherwise return FALSE.
   * @param $exit_invalid
   *   If TRUE and the implementation of getAccessToken() returns NULL, exit,
   *   otherwise return FALSE.
   * @param $exit_expired
   *   If TRUE and the access token has expired, exit, otherwise return FALSE.
   * @param $exit_scope
   *   If TRUE the access token does not have the required scope(s), exit,
   *   otherwise return FALSE.
   * @param $realm
   *   If you want to specify a particular realm for the WWW-Authenticate
   *   header, supply it here.
   *
   * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-5
   *
   * @ingroup oauth2_section_5
   */
  public function verifyAccessToken($scope = NULL, $exit_not_present = TRUE, $exit_invalid = TRUE, $exit_expired = TRUE, $exit_scope = TRUE, $realm = NULL) {
    $token_param = $this->getAccessTokenParams();
    if ($token_param === FALSE) // Access token was not provided
      return $exit_not_present ? $this->errorWWWAuthenticateResponseHeader(OAUTH2_HTTP_BAD_REQUEST, $realm, OAUTH2_ERROR_INVALID_REQUEST, 'The request is missing a required parameter, includes an unsupported parameter or parameter value, repeats the same parameter, uses more than one method for including an access token, or is otherwise malformed.', NULL, $scope) : FALSE;
    // Get the stored token data (from the implementing subclass)
    $token = $this->getAccessToken($token_param);
    if ($token === NULL)
      return $exit_invalid ? $this->errorWWWAuthenticateResponseHeader(OAUTH2_HTTP_UNAUTHORIZED, $realm, OAUTH2_ERROR_INVALID_TOKEN, 'The access token provided is invalid.', NULL, $scope) : FALSE;

    // Check token expiration (I'm leaving this check separated, later we'll fill in better error messages)
    if (isset($token["expires"]) && time() > $token["expires"])
      return $exit_expired ? $this->errorWWWAuthenticateResponseHeader(OAUTH2_HTTP_UNAUTHORIZED, $realm, OAUTH2_ERROR_EXPIRED_TOKEN, 'The access token provided has expired.', NULL, $scope) : FALSE;

    // Check scope, if provided
    // If token doesn't have a scope, it's NULL/empty, or it's insufficient, then throw an error
    if ($scope && (!isset($token["scope"]) || !$token["scope"] || !$this->checkScope($scope, $token["scope"])))
      return $exit_scope ? $this->errorWWWAuthenticateResponseHeader(OAUTH2_HTTP_FORBIDDEN, $realm, OAUTH2_ERROR_INSUFFICIENT_SCOPE, 'The request requires higher privileges than provided by the access token.', NULL, $scope) : FALSE;

    return TRUE;
  }

  /**
   * Check if everything in required scope is contained in available scope.
   *
   * @param $required_scope
   *   Required scope to be check with.
   * @param $available_scope
   *   Available scope to be compare with.
   *
   * @return
   *   TRUE if everything in required scope is contained in available scope,
   *   and False if it isn't.
   *
   * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-5
   *
   * @ingroup oauth2_section_5
   */
  private function checkScope($required_scope, $available_scope) {
    // The required scope should match or be a subset of the available scope
    if (!is_array($required_scope))
      $required_scope = explode(" ", $required_scope);

    if (!is_array($available_scope))
      $available_scope = explode(" ", $available_scope);

    return (count(array_diff($required_scope, $available_scope)) == 0);
  }

  /**
   * Pulls the access token out of the HTTP request.
   *
   * Either from the Authorization header or GET/POST/etc.
   *
   * @return
   *   Access token value if present, and FALSE if it isn't.
   *
   * @todo Support PUT or DELETE.
   *
   * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-5.1
   *
   * @ingroup oauth2_section_5
   */
  private function getAccessTokenParams() {
    $auth_header = $this->getAuthorizationHeader();

    if ($auth_header !== FALSE) {
      // Make sure only the auth header is set
      if (isset($_GET[OAUTH2_TOKEN_PARAM_NAME]) || isset($_POST[OAUTH2_TOKEN_PARAM_NAME]))
        $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_REQUEST, 'Auth token found in GET or POST when token present in header');

      $auth_header = trim($auth_header);

      // Make sure it's Token authorization
      if (strcmp(substr($auth_header, 0, 5), "OAuth ") !== 0)
        $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_REQUEST, 'Auth header found that doesn\'t start with "OAuth"');

      // Parse the rest of the header
      if (preg_match('/\s*OAuth\s*="(.+)"/', substr($auth_header, 5), $matches) == 0 || count($matches) < 2)
        $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_REQUEST, 'Malformed auth header');

      return $matches[1];
    }

    if (isset($_GET[OAUTH2_TOKEN_PARAM_NAME])) {
      if (isset($_POST[OAUTH2_TOKEN_PARAM_NAME])) // Both GET and POST are not allowed
        $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_REQUEST, 'Only send the token in GET or POST, not both');

      return $_GET[OAUTH2_TOKEN_PARAM_NAME];
    }

    if (isset($_POST[OAUTH2_TOKEN_PARAM_NAME]))
      return $_POST[OAUTH2_TOKEN_PARAM_NAME];

    return FALSE;
  }

  // Access token granting (Section 4).

  /**
   * Grant or deny a requested access token.
   *
   * This would be called from the "/token" endpoint as defined in the spec.
   * Obviously, you can call your endpoint whatever you want.
   *
   * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-4
   *
   * @ingroup oauth2_section_4
   */
  public function grantAccessToken() {
    $filters = array(
      "grant_type" => array("filter" => FILTER_VALIDATE_REGEXP, "options" => array("regexp" => OAUTH2_GRANT_TYPE_REGEXP), "flags" => FILTER_REQUIRE_SCALAR),
      "scope" => array("flags" => FILTER_REQUIRE_SCALAR),
      "code" => array("flags" => FILTER_REQUIRE_SCALAR),
      "redirect_uri" => array("filter" => FILTER_SANITIZE_URL),
      "username" => array("flags" => FILTER_REQUIRE_SCALAR),
      "password" => array("flags" => FILTER_REQUIRE_SCALAR),
      "assertion_type" => array("flags" => FILTER_REQUIRE_SCALAR),
      "assertion" => array("flags" => FILTER_REQUIRE_SCALAR),
      "refresh_token" => array("flags" => FILTER_REQUIRE_SCALAR),
    );

    $input = filter_input_array(INPUT_POST, $filters);

    // Grant Type must be specified.
    if (!$input["grant_type"])
      $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_REQUEST, 'Invalid grant_type parameter or parameter missing');

    // Make sure we've implemented the requested grant type
    if (!in_array($input["grant_type"], $this->getSupportedGrantTypes()))
      $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_UNSUPPORTED_GRANT_TYPE);

    // Authorize the client
    $client = $this->getClientCredentials();

    if ($this->checkClientCredentials($client[0], $client[1]) === FALSE)
      $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_CLIENT);

    if (!$this->checkRestrictedGrantType($client[0], $input["grant_type"]))
      $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_UNAUTHORIZED_CLIENT);

    // Do the granting
    switch ($input["grant_type"]) {
      case OAUTH2_GRANT_TYPE_AUTH_CODE:
        if (!$input["code"] || !$input["redirect_uri"])
          $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_REQUEST);

        $stored = $this->getAuthCode($input["code"]);

        // Ensure that the input uri starts with the stored uri
        if ($stored === NULL || (strcasecmp(substr($input["redirect_uri"], 0, strlen($stored["redirect_uri"])), $stored["redirect_uri"]) !== 0) || $client[0] != $stored["client_id"])
          $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_GRANT);

        if ($stored["expires"] < time())
          $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_EXPIRED_TOKEN);

        break;
      case OAUTH2_GRANT_TYPE_USER_CREDENTIALS:
        if (!$input["username"] || !$input["password"])
          $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_REQUEST, 'Missing parameters. "username" and "password" required');

        $stored = $this->checkUserCredentials($client[0], $input["username"], $input["password"]);

        if ($stored === FALSE)
          $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_GRANT);

        break;
      case OAUTH2_GRANT_TYPE_ASSERTION:
        if (!$input["assertion_type"] || !$input["assertion"])
          $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_REQUEST);

        $stored = $this->checkAssertion($client[0], $input["assertion_type"], $input["assertion"]);

        if ($stored === FALSE)
          $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_GRANT);

        break;
      case OAUTH2_GRANT_TYPE_REFRESH_TOKEN:
        if (!$input["refresh_token"])
          $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_REQUEST, 'No "refresh_token" parameter found');

        $stored = $this->getRefreshToken($input["refresh_token"]);

        if ($stored === NULL || $client[0] != $stored["client_id"])
          $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_GRANT);

        if ($stored["expires"] < time())
          $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_EXPIRED_TOKEN);

        // store the refresh token locally so we can delete it when a new refresh token is generated
        $this->setVariable('_old_refresh_token', $stored["token"]);

        break;
      case OAUTH2_GRANT_TYPE_NONE:
        $stored = $this->checkNoneAccess($client[0]);

        if ($stored === FALSE)
          $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_REQUEST);
    }

    // Check scope, if provided
    if ($input["scope"] && (!is_array($stored) || !isset($stored["scope"]) || !$this->checkScope($input["scope"], $stored["scope"])))
      $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_SCOPE);

    if (!$input["scope"])
      $input["scope"] = NULL;

    $token = $this->createAccessToken($client[0], $input["scope"]);

    $this->sendJsonHeaders();
    echo json_encode($token);
  }

  /**
   * Internal function used to get the client credentials from HTTP basic
   * auth or POST data.
   *
   * @return
   *   A list containing the client identifier and password, for example
   * @code
   * return array(
   *   $_POST["client_id"],
   *   $_POST["client_secret"],
   * );
   * @endcode
   *
   * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-2
   *
   * @ingroup oauth2_section_2
   */
  protected function getClientCredentials() {
    if (isset($_SERVER["PHP_AUTH_USER"]) && $_POST && isset($_POST["client_id"]))
      $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_CLIENT);

    // Try basic auth
    if (isset($_SERVER["PHP_AUTH_USER"]))
      return array($_SERVER["PHP_AUTH_USER"], $_SERVER["PHP_AUTH_PW"]);

    // Try POST
    if ($_POST && isset($_POST["client_id"])) {
      if (isset($_POST["client_secret"]))
        return array($_POST["client_id"], $_POST["client_secret"]);

      return array($_POST["client_id"], NULL);
    }

    // No credentials were specified
    $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_CLIENT);
  }

  // End-user/client Authorization (Section 3 of IETF Draft).

  /**
   * Pull the authorization request data out of the HTTP request.
   *
   * @return
   *   The authorization parameters so the authorization server can prompt
   *   the user for approval if valid.
   *
   * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-3
   *
   * @ingroup oauth2_section_3
   */
  public function getAuthorizeParams() {
    $filters = array(
      "client_id" => array("filter" => FILTER_VALIDATE_REGEXP, "options" => array("regexp" => OAUTH2_CLIENT_ID_REGEXP), "flags" => FILTER_REQUIRE_SCALAR),
      "response_type" => array("filter" => FILTER_VALIDATE_REGEXP, "options" => array("regexp" => OAUTH2_AUTH_RESPONSE_TYPE_REGEXP), "flags" => FILTER_REQUIRE_SCALAR),
      "redirect_uri" => array("filter" => FILTER_SANITIZE_URL),
      "state" => array("flags" => FILTER_REQUIRE_SCALAR),
      "scope" => array("flags" => FILTER_REQUIRE_SCALAR),
    );

    $input = filter_input_array(INPUT_GET, $filters);

    // Make sure a valid client id was supplied
    if (!$input["client_id"]) {
      if ($input["redirect_uri"])
        $this->errorDoRedirectUriCallback($input["redirect_uri"], OAUTH2_ERROR_INVALID_CLIENT, NULL, NULL, $input["state"]);

      $this->errorJsonResponse(OAUTH2_HTTP_FOUND, OAUTH2_ERROR_INVALID_CLIENT); // We don't have a good URI to use
    }

    // redirect_uri is not required if already established via other channels
    // check an existing redirect URI against the one supplied
    $redirect_uri = $this->getRedirectUri($input["client_id"]);

    // At least one of: existing redirect URI or input redirect URI must be specified
    if (!$redirect_uri && !$input["redirect_uri"])
      $this->errorJsonResponse(OAUTH2_HTTP_FOUND, OAUTH2_ERROR_INVALID_REQUEST);

    // getRedirectUri() should return FALSE if the given client ID is invalid
    // this probably saves us from making a separate db call, and simplifies the method set
    if ($redirect_uri === FALSE)
      $this->errorDoRedirectUriCallback($input["redirect_uri"], OAUTH2_ERROR_INVALID_CLIENT, NULL, NULL, $input["state"]);

    // If there's an existing uri and one from input, verify that they match
    if ($redirect_uri && $input["redirect_uri"]) {
      // Ensure that the input uri starts with the stored uri
      if (strcasecmp(substr($input["redirect_uri"], 0, strlen($redirect_uri)), $redirect_uri) !== 0)
        $this->errorDoRedirectUriCallback($input["redirect_uri"], OAUTH2_ERROR_REDIRECT_URI_MISMATCH, NULL, NULL, $input["state"]);
    }
    elseif ($redirect_uri) { // They did not provide a uri from input, so use the stored one
      $input["redirect_uri"] = $redirect_uri;
    }

    // type and client_id are required
    if (!$input["response_type"])
      $this->errorDoRedirectUriCallback($input["redirect_uri"], OAUTH2_ERROR_INVALID_REQUEST, 'Invalid response type.', NULL, $input["state"]);

    // Check requested auth response type against the list of supported types
    if (array_search($input["response_type"], $this->getSupportedAuthResponseTypes()) === FALSE)
      $this->errorDoRedirectUriCallback($input["redirect_uri"], OAUTH2_ERROR_UNSUPPORTED_RESPONSE_TYPE, NULL, NULL, $input["state"]);

    // Restrict clients to certain authorization response types
    if ($this->checkRestrictedAuthResponseType($input["client_id"], $input["response_type"]) === FALSE)
      $this->errorDoRedirectUriCallback($input["redirect_uri"], OAUTH2_ERROR_UNAUTHORIZED_CLIENT, NULL, NULL, $input["state"]);

    // Validate that the requested scope is supported
    if ($input["scope"] && !$this->checkScope($input["scope"], $this->getSupportedScopes()))
      $this->errorDoRedirectUriCallback($input["redirect_uri"], OAUTH2_ERROR_INVALID_SCOPE, NULL, NULL, $input["state"]);

    return $input;
  }

  /**
   * Redirect the user appropriately after approval.
   *
   * After the user has approved or denied the access request the
   * authorization server should call this function to redirect the user
   * appropriately.
   *
   * @param $is_authorized
   *   TRUE or FALSE depending on whether the user authorized the access.
   * @param $params
   *   An associative array as below:
   *   - response_type: The requested response: an access token, an
   *     authorization code, or both.
   *   - client_id: The client identifier as described in Section 2.
   *   - redirect_uri: An absolute URI to which the authorization server
   *     will redirect the user-agent to when the end-user authorization
   *     step is completed.
   *   - scope: (optional) The scope of the access request expressed as a
   *     list of space-delimited strings.
   *   - state: (optional) An opaque value used by the client to maintain
   *     state between the request and callback.
   *
   * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-3
   *
   * @ingroup oauth2_section_3
   */
  public function finishClientAuthorization($is_authorized, $params = array()) {
    $params += array(
      'scope' => NULL,
      'state' => NULL,
    );
    extract($params);

    if ($state !== NULL)
      $result["query"]["state"] = $state;

    if ($is_authorized === FALSE) {
      $result["query"]["error"] = OAUTH2_ERROR_USER_DENIED;
    }
    else {
      if ($response_type == OAUTH2_AUTH_RESPONSE_TYPE_AUTH_CODE || $response_type == OAUTH2_AUTH_RESPONSE_TYPE_CODE_AND_TOKEN)
        $result["query"]["code"] = $this->createAuthCode($client_id, $redirect_uri, $scope);

      if ($response_type == OAUTH2_AUTH_RESPONSE_TYPE_ACCESS_TOKEN || $response_type == OAUTH2_AUTH_RESPONSE_TYPE_CODE_AND_TOKEN)
        $result["fragment"] = $this->createAccessToken($client_id, $scope);
    }

    $this->doRedirectUriCallback($redirect_uri, $result);
  }

  // Other/utility functions.

  /**
   * Redirect the user agent.
   *
   * Handle both redirect for success or error response.
   *
   * @param $redirect_uri
   *   An absolute URI to which the authorization server will redirect
   *   the user-agent to when the end-user authorization step is completed.
   * @param $params
   *   Parameters to be pass though buildUri().
   *
   * @ingroup oauth2_section_3
   */
  private function doRedirectUriCallback($redirect_uri, $params) {
    header("HTTP/1.1 ". OAUTH2_HTTP_FOUND);
    header("Location: " . $this->buildUri($redirect_uri, $params));
    exit;
  }

  /**
   * Build the absolute URI based on supplied URI and parameters.
   *
   * @param $uri
   *   An absolute URI.
   * @param $params
   *   Parameters to be append as GET.
   *
   * @return
   *   An absolute URI with supplied parameters.
   *
   * @ingroup oauth2_section_3
   */
  private function buildUri($uri, $params) {
    $parse_url = parse_url($uri);

    // Add our params to the parsed uri
    foreach ($params as $k => $v) {
      if (isset($parse_url[$k]))
        $parse_url[$k] .= "&" . http_build_query($v);
      else
        $parse_url[$k] = http_build_query($v);
    }

    // Put humpty dumpty back together
    return
      ((isset($parse_url["scheme"])) ? $parse_url["scheme"] . "://" : "")
      . ((isset($parse_url["user"])) ? $parse_url["user"] . ((isset($parse_url["pass"])) ? ":" . $parse_url["pass"] : "") . "@" : "")
      . ((isset($parse_url["host"])) ? $parse_url["host"] : "")
      . ((isset($parse_url["port"])) ? ":" . $parse_url["port"] : "")
      . ((isset($parse_url["path"])) ? $parse_url["path"] : "")
      . ((isset($parse_url["query"])) ? "?" . $parse_url["query"] : "")
      . ((isset($parse_url["fragment"])) ? "#" . $parse_url["fragment"] : "");
  }

  /**
   * Handle the creation of access token, also issue refresh token if support.
   *
   * This belongs in a separate factory, but to keep it simple, I'm just
   * keeping it here.
   *
   * @param $client_id
   *   Client identifier related to the access token.
   * @param $scope
   *   (optional) Scopes to be stored in space-separated string.
   *
   * @ingroup oauth2_section_4
   */
  protected function createAccessToken($client_id, $scope = NULL) {
    $token = array(
      "access_token" => $this->genAccessToken(),
      "expires_in" => $this->getVariable('access_token_lifetime', OAUTH2_DEFAULT_ACCESS_TOKEN_LIFETIME),
      "scope" => $scope
    );

    $this->setAccessToken($token["access_token"], $client_id, time() + $this->getVariable('access_token_lifetime', OAUTH2_DEFAULT_ACCESS_TOKEN_LIFETIME), $scope);

    // Issue a refresh token also, if we support them
    if (in_array(OAUTH2_GRANT_TYPE_REFRESH_TOKEN, $this->getSupportedGrantTypes())) {
      $token["refresh_token"] = $this->genAccessToken();
      $this->setRefreshToken($token["refresh_token"], $client_id, time() + $this->getVariable('refresh_token_lifetime', OAUTH2_DEFAULT_REFRESH_TOKEN_LIFETIME), $scope);
      // If we've granted a new refresh token, expire the old one
      if ($this->getVariable('_old_refresh_token'))
        $this->unsetRefreshToken($this->getVariable('_old_refresh_token'));
    }

    return $token;
  }

  /**
   * Handle the creation of auth code.
   *
   * This belongs in a separate factory, but to keep it simple, I'm just
   * keeping it here.
   *
   * @param $client_id
   *   Client identifier related to the access token.
   * @param $redirect_uri
   *   An absolute URI to which the authorization server will redirect the
   *   user-agent to when the end-user authorization step is completed.
   * @param $scope
   *   (optional) Scopes to be stored in space-separated string.
   *
   * @ingroup oauth2_section_3
   */
  private function createAuthCode($client_id, $redirect_uri, $scope = NULL) {
    $code = $this->genAuthCode();
    $this->setAuthCode($code, $client_id, $redirect_uri, time() + $this->getVariable('auth_code_lifetime', OAUTH2_DEFAULT_AUTH_CODE_LIFETIME), $scope);
    return $code;
  }

  /**
   * Generate unique access token.
   *
   * Implementing classes may want to override these function to implement
   * other access token or auth code generation schemes.
   *
   * @return
   *   An unique access token.
   *
   * @ingroup oauth2_section_4
   */
  protected function genAccessToken() {
    return md5(base64_encode(pack('N6', mt_rand(), mt_rand(), mt_rand(), mt_rand(), mt_rand(), uniqid())));
  }

  /**
   * Generate unique auth code.
   *
   * Implementing classes may want to override these function to implement
   * other access token or auth code generation schemes.
   *
   * @return
   *   An unique auth code.
   *
   * @ingroup oauth2_section_3
   */
  protected function genAuthCode() {
    return md5(base64_encode(pack('N6', mt_rand(), mt_rand(), mt_rand(), mt_rand(), mt_rand(), uniqid())));
  }

  /**
   * Pull out the Authorization HTTP header and return it.
   *
   * Implementing classes may need to override this function for use on
   * non-Apache web servers.
   *
   * @return
   *   The Authorization HTTP header, and FALSE if does not exist.
   *
   * @todo Handle Authorization HTTP header for non-Apache web servers.
   *
   * @ingroup oauth2_section_5
   */
  private function getAuthorizationHeader() {
    if (array_key_exists("HTTP_AUTHORIZATION", $_SERVER))
      return $_SERVER["HTTP_AUTHORIZATION"];

    if (function_exists("apache_request_headers")) {
      $headers = apache_request_headers();

      if (array_key_exists("Authorization", $headers))
        return $headers["Authorization"];
    }

    return FALSE;
  }

  /**
   * Send out HTTP headers for JSON.
   *
   * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-4.2
   * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-4.3
   *
   * @ingroup oauth2_section_4
   */
  private function sendJsonHeaders() {
    header("Content-Type: application/json");
    header("Cache-Control: no-store");
  }

  /**
   * Redirect the end-user's user agent with error message.
   *
   * @param $redirect_uri
   *   An absolute URI to which the authorization server will redirect the
   *   user-agent to when the end-user authorization step is completed.
   * @param $error
   *   A single error code as described in Section 3.2.1.
   * @param $error_description
   *   (optional) A human-readable text providing additional information,
   *   used to assist in the understanding and resolution of the error
   *   occurred.
   * @param $error_uri
   *   (optional) A URI identifying a human-readable web page with
   *   information about the error, used to provide the end-user with
   *   additional information about the error.
   * @param $state
   *   (optional) REQUIRED if the "state" parameter was present in the client
   *   authorization request. Set to the exact value received from the client.
   *
   * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-3.2
   *
   * @ingroup oauth2_error
   */
  private function errorDoRedirectUriCallback($redirect_uri, $error, $error_description = NULL, $error_uri = NULL, $state = NULL) {
    $result["query"]["error"] = $error;

    if ($state)
      $result["query"]["state"] = $state;

    if ($this->getVariable('display_error') && $error_description)
      $result["query"]["error_description"] = $error_description;

    if ($this->getVariable('display_error') && $error_uri)
      $result["query"]["error_uri"] = $error_uri;

    $this->doRedirectUriCallback($redirect_uri, $result);
  }

  /**
   * Send out error message in JSON.
   *
   * @param $http_status_code
   *   HTTP status code message as predefined.
   * @param $error
   *   A single error code.
   * @param $error_description
   *   (optional) A human-readable text providing additional information,
   *   used to assist in the understanding and resolution of the error
   *   occurred.
   * @param $error_uri
   *   (optional) A URI identifying a human-readable web page with
   *   information about the error, used to provide the end-user with
   *   additional information about the error.
   *
   * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-4.3
   *
   * @ingroup oauth2_error
   */
  private function errorJsonResponse($http_status_code, $error, $error_description = NULL, $error_uri = NULL) {
    $result['error'] = $error;

    if ($this->getVariable('display_error') && $error_description)
      $result["error_description"] = $error_description;

    if ($this->getVariable('display_error') && $error_uri)
      $result["error_uri"] = $error_uri;

    header("HTTP/1.1 " . $http_status_code);
    $this->sendJsonHeaders();
    echo json_encode($result);

    exit;
  }

  /**
   * Send a 401 unauthorized header with the given realm and an error, if
   * provided.
   *
   * @param $http_status_code
   *   HTTP status code message as predefined.
   * @param $realm
   *   The "realm" attribute is used to provide the protected resources
   *   partition as defined by [RFC2617].
   * @param $scope
   *   A space-delimited list of scope values indicating the required scope
   *   of the access token for accessing the requested resource.
   * @param $error
   *   The "error" attribute is used to provide the client with the reason
   *   why the access request was declined.
   * @param $error_description
   *   (optional) The "error_description" attribute provides a human-readable text
   *   containing additional information, used to assist in the understanding
   *   and resolution of the error occurred.
   * @param $error_uri
   *   (optional) The "error_uri" attribute provides a URI identifying a human-readable
   *   web page with information about the error, used to offer the end-user
   *   with additional information about the error. If the value is not an
   *   absolute URI, it is relative to the URI of the requested protected
   *   resource.
   *
   * @see http://tools.ietf.org/html/draft-ietf-oauth-v2-10#section-5.2
   *
   * @ingroup oauth2_error
   */
  private function errorWWWAuthenticateResponseHeader($http_status_code, $realm, $error, $error_description = NULL, $error_uri = NULL, $scope = NULL) {
    $realm = $realm === NULL ? $this->getDefaultAuthenticationRealm() : $realm;

    $result = "WWW-Authenticate: OAuth realm='" . $realm . "'";

    if ($error)
      $result .= ", error='" . $error . "'";

    if ($this->getVariable('display_error') && $error_description)
      $result .= ", error_description='" . $error_description . "'";

    if ($this->getVariable('display_error') && $error_uri)
      $result .= ", error_uri='" . $error_uri . "'";

    if ($scope)
      $result .= ", scope='" . $scope . "'";

    header("HTTP/1.1 ". $http_status_code);
    header($result);

    exit;
  }
}
