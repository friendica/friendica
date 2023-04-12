# 使用API

Friendica提供多个API端点以与第三方应用程序进行交互：

- [Twitter](help/API-Twitter)
- [Mastodon](help/API-Mastodon)
- [Friendica-specific](help/API-Friendica)
- [GNU Social](help/API-GNU-Social)

## 用法

### HTTP方法

API端点可以限制用于请求它们的HTTP方法。
使用无效方法会导致HTTP错误405“方法不允许”。

### 认证

Friendica支持基本的HTTP身份验证和OAuth来对API的用户进行身份验证。

### 错误

当API调用出现错误时，会返回一个HTTP错误代码和一个错误消息。
通常有：

* 400错误请求：如果缺少参数或无法找到项
* 403禁止：如果缺少经过身份验证的用户
* 405方法不允许：如果使用无效方法调用API，例如在API需要POST时使用GET
* 501未实现：如果请求的API不存在
* 500内部服务器错误：在其他错误条件下

错误主体是...

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
RSStoFriendika代码可以用作使用Python API的示例。 发布行的代码位于第21行及其后面。

def tweet(server, message, group_allow=None):
    url = server + '/api/statuses/update'
    urllib2.urlopen(url, urllib.urlencode({'status': message,'group_allow[]':group_allow}, doseq=True))
	
也有一个用于Python 3的模块，可用于使用API。
