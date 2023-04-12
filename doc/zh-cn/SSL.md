使用 SSL 与 Friendica
=====================================

* [主页](help)

## 声明

**本文档已于2016年11月更新。
SSL 加密对于安全性是有意义的。
这意味着推荐设置会快速变化。
保持您的设置最新，不要依赖于此文档被更新得像技术变化一样快！**

## 简介

如果您正在运行自己的 Friendica 站点，则可能希望使用 SSL（https）来加密服务器之间以及您和服务器之间的通信。

基本上有两种 SSL 证书：自签名的证书和由证书颁发机构（CA）签名的证书。
从技术上讲，它们都提供相同的有效加密。
但是自签名证书存在问题：
它们既没有在浏览器中安装，也没有在其他服务器上安装。
这就是为什么它们引起“不受信任证书”的警告的原因。
这很令人困惑和烦恼。

因此，我们建议获取由 CA 签名的证书。
通常，您必须为它们付费 - 它们的有效期限制在一定时间内（例如一年或两年）。

有免费获得可信证书的方法。

## 选择您的域名

您的 SSL 证书将针对一个域或甚至仅针对一个子域有效。
在订购证书之前，做出有关您的域名或子域的最终决定。
一旦您拥有了它，更改域名意味着获得新的证书。

### 共享主机

如果您的 Friendica 实例运行在共享托管平台上，则应首先与您的托管提供商联系。
他们会向您提供有关如何在那里进行操作的说明。
您可以随时向您的提供商订购付费证书。
他们要么为您安装它，要么通过网页界面提供轻松的上传证书和密钥的方法。
对于某些提供商，您需要将证书发送给他们。
他们需要证书、密钥和 CA 的中间证书。
**为确保安全，将这三个文件发送给您的提供商时应使用加密通道！**

### 拥有自己的服务器

如果你拥有自己的服务器，我们建议查看 ["Let's Encrypt" 倡议](https://letsencrypt.org/)。
他们不仅提供免费的 SSL 证书，还提供了自动更新证书的方式。
您需要在服务器上安装客户端软件才能使用它。
官方客户端的说明在[这里](https://certbot.eff.org/)。
根据您的需求，您可能需要查看[备选 letsencrypt 客户端列表](https://letsencrypt.org/docs/client-options/)。

## Web 服务器设置

请访问 [Mozilla 维基](https://wiki.mozilla.org/Security/Server_Side_TLS) 以获取有关如何配置安全的 Web 服务器的说明。
他们为[不同的 Web 服务器](https://mozilla.github.io/server-side-tls/ssl-config-generator/)提供建议。

## 测试 SSL 设置

当您完成后，请访问测试网站 [SSL Labs](https://www.ssllabs.com/ssltest/) 以检查是否成功。

## 配置 Friendica

如果您可以通过 https 成功访问 Friendica 实例，则可以采取一些步骤来确保用户将使用 SSL 访问您的实例。

### Web 服务器重定向

这是强制整个站点安全访问的最简单方法。
每次用户尝试通过任何方式（手动地址栏输入或链接）访问任何 Friendica 页面时，Web 服务器都会发出永久重定向响应，并在所请求的 URL 前添加安全协议。

对于 Apache，请启用 rewrite 和 ssl 模块（在共享虚拟主机提供商上，这应该已经启用）：

        sudo a2enmod rewrite ssl

在 Friendica 实例的根文件夹中的 .htaccess 文件中添加以下行（感谢 [AlfredSK](https://github.com/AlfredSK)）：

        RewriteEngine On
        RewriteCond %{SERVER_PORT} 80
        RewriteRule ^(.*)$ https://your.friendica.domain/$1 [R=301,L]

对于 nginx，请将服务器指令配置为以下方式（[文档](https://www.nginx.com/blog/creating-nginx-rewrite-rules/)）：

        server {
             listen 80;
             server_name your.friendica.domain;
             return 301 https://$server_name$request_uri;
        }

### SSL 设置

在管理设置中，有三个与 SSL 相关的设置：

1. **SSL 链接策略**：这会影响 Friendica 如何生成内部链接。如果您的 SSL 安装成功，我们建议选择“强制所有链接使用 SSL”，以防万一您的 Web 服务器配置无法像上面描述的那样更改。
2. **强制 SSL**：这将强制所有外部链接使用 HTTPS，这可能解决混合内容问题，但并非所有网站都支持 HTTPS。请自行决定是否使用。
3. **验证 SSL**：启用此选项将防止 Friendica 与自签名 SSL 站点交互。我们建议您将其保留为默认值，因为自签名 SSL 证书可能会成为中间人攻击的目标。
