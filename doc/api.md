# Using the APIs

<!-- markdownlint-disable MD010 MD013 MD024 -->

* [Home](help)

Friendica offers multiple API endpoints to interface with third-party applications:

- [Twitter](help/API-Twitter)
- [Mastodon](help/API-Mastodon)
- [Friendica-specific](help/API-Friendica)
- [GNU Social](help/API-GNU-Social)

## Usage

### HTTP Method

API endpoints can restrict the HTTP method used to request them.
Using an invalid method results in HTTP error 405 "Method Not Allowed".

### Authentication

Friendica supports basic HTTP Auth and OAuth to authenticate the user to the APIs.

### Errors

When an error occurs in API call, an HTTP error code is returned, with an error message
Usually:

* 400 Bad Request: if parameters are missing or items can't be found
* 403 Forbidden: if the authenticated user is missing
* 405 Method Not Allowed: if API was called with an invalid method, eg. GET when API require POST
* 501 Not Implemented: if the requested API doesn't exist
* 500 Internal Server Error: on other error conditions

Error body is

json:

```json
{
    "error": "Specific error message",
    "request": "API path requested",
    "code": "HTTP error code"
}
```

xml:

```xml
<status>
    <error>Specific error message</error>
    <request>API path requested</request>
    <code>HTTP error code</code>
</status>
```

## Usage Examples

### BASH / cURL

```bash
/usr/bin/curl -u USER:PASS https://YOUR.FRIENDICA.TLD/api/statuses/update.xml -d source="some source id" -d status="the status you want to post"
```

### Python

The [RSStoFriendika](https://github.com/pafcu/RSStoFriendika) code can be used as an example of how to use the API with python.
The lines for posting are located at [line 21](https://github.com/pafcu/RSStoFriendika/blob/master/RSStoFriendika.py#L21) and following.

def tweet(server, message, circle_allow=None):
url = server + '/api/statuses/update'
urllib2.urlopen(url, urllib.urlencode({'status': message, 'circle_allow[]': circle_allow}, doseq=True))

There is also a [module for python 3](https://bitbucket.org/tobiasd/python-friendica) for using the API.
