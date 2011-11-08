oauth2-php revision xxx, xxxx-xx-xx (development version)
----------------------

oauth2-php revision 23, 2011-01-25
----------------------
* introduce Drupal style getVariable() and setVariable, replace legacy
  variable get/set functions.
* remove hardcode PHP display_error and errror_reporting, as this should
  be manually implement within 3rd party integration.
* make verbose error as configurable and default disable, as this should
  be manually enable within 3rd party integration.
* add lib/OAuth2Client.inc and lib/OAuth2Exception.inc for client-side
  implementation.

oauth2-php revision 21, 2010-12-18
----------------------
* cleanup tabs and trailing whitespace at the end.
* remove server/examples/mongo/lib/oauth.php and
  server/examples/pdo/lib/oauth.php, so only keep single copy as
  lib /oauth.php.
* issue #5: Wrong variable name in get_access_token() in pdo_oatuh.php.
* issue #6: mysql_create_tables.sql should allow scope to be NULL.
* issue #7: authorize_client_response_type() is never used.
* issue #9: Change "redirect_uri" filtering from FILTER_VALIDATE_URL to
  FILTER_SANITIZE_URL.
* better coding syntax for error() and callback_error().
* better pdo_oauth2.php variable naming with change to
  mysql_create_tables.sql.
* change REGEX_CLIENT_ID as 3-32 characters long, so will work with md5()
  result directly.
* debug linkage to oauth2.php during previous commit.
* debug redirect_uri check for AUTH_CODE_GRANT_TYPE, clone from
  get_authorize_params().
* update mysql_create_tables.sql with phpmyadmin export format.
* rename library files, prepare for adding client-side implementation.
* code cleanup with indent and spacing.
* code cleanup true/false/null with TRUE/FALSE/NULL.
* rename constants with OAUTH2_ prefix, prevent 3rd party integration
  conflict.
* remove HTTP 400 response constant, as useless refer to draft v10.
* merge ERROR_INVALID_CLIENT_ID and ERROR_UNAUTHORIZED_CLIENT as
  OAUTH2_ERROR_INVALID_CLIENT, as refer to that of draft v9 to v10 changes.
* improve constants comment with doxygen syntax.
* update class function call naming.
* coding style clean up.
* update part of documents.
* change expirseRefreshToken() as unsetRefreshToken().
* update token and auth code generation as md5() result, simpler for manual
  debug with web browser.
* update all documents.
* restructure @ingroup.
* rename checkRestrictedClientResponseTypes() as
  checkRestrictedAuthResponseType().
* rename checkRestrictedClientGrantTypes() as checkRestrictedGrantType().
* rename error() as errorJsonResponse().
* rename errorCallback() as errorDoRedirectUriCallback().
* rename send401Unauthorized() as errorWWWAuthenticateResponseHeader(),
  update support with different HTTP status code.
* update __construct() with array input.
* update finishClientAuthorization() with array input.
* add get/set functions for $access_token_lifetime, $auth_code_lifetime and
  $refresh_token_lifetime.
* fix a lots of typos.
* document all sample server implementation.
* more documents.
* add config.doxy for doxygen default setup.
* add MIT LICENSE.txt.
* add CHANGELOG.txt.

oauth2-php revision 9, 2010-09-04
----------------------
- fixes for issues #2 and #4, updates oauth lib in the example folders to
  the latest version in the 'lib' folder.
- updates server library to revision 10 of the OAuth 2.0 spec.
- adds an option for more verbose error messages to be returned in the JSON
  response.
- adds method to be overridden for expiring used refresh tokens.
- fixes bug checking token expiration.
- makes some more methods protected instead of private so they can be
  overridden.
- fixes issue #1 http://code.google.com/p/oauth2-php/issues/detail?id=1

oauth2-php revision 7, 2010-06-29
----------------------
- fixed mongo connection constants.
- updated store_refresh_token to include expires time.
- changed example server directory structure
- corrected "false" return result on get_stored_auth_code.
- implemented PDO example adapter.
- corrected an error in assertion grant type.
- updated for ietf draft v9:
  http://tools.ietf.org/html/draft-ietf-oauth-v2-09.
- updated updated to support v9 lib.
- added mysql table creation script.

oauth2-php revision 0, 2010-06-27
----------------------
- initial commit.
