使用Composer
===============

* [首页](help)
  * [开发者介绍](help/Developers-Intro)

Friendica使用[Composer]（https://getcomposer.org）来管理依赖库和类自动加载程序，包括库和命名空间Friendica类。

它是一个命令行工具，可以将所需的库下载到“vendor”文件夹中，并使`src`中的任何命名空间类在整个应用程序中可用。

* [类自动加载程序](help/autoloader)

## 如何使用Composer

如果您的系统上没有安装Composer，则Friendica带有一个副本，位于`bin/composer.phar`。
为了此帮助的目的，所有示例都将使用此路径来运行Composer命令，但请随意替换为自己调用Composer的方法。
Composer需要PHP CLI，以下示例假定它在系统范围内可用。

### 安装/更新Friendica

#### 从档案中安装

如果您刚刚解压缩了Friendica发布存档，则根本不必使用Commposer，所有必需的库已经捆绑在存档中。

#### 使用Git安装

如果您喜欢使用`git`，则必须运行Composer以获取所需的库并构建自动加载程序，然后才能运行Friendica。
这里是您需要运行的典型命令：

````
~> git clone https://github.com/friendica/friendica.git friendica
~/friendica> cd friendica
~/friendica> bin/composer.phar install
````

就是这样！ Composer会负责在`vendor`文件夹中获取所有必需的库，并构建自动加载程序以使这些库可供Friendica使用。

#### 使用git更新

使用Git更新Friendica到当前稳定版或最新开发版很容易，只要记得每次分支拉取后运行Composer即可。

````
~> cd friendica
~/friendica> git pull
~/friendica> bin/composer.phar install
````

就是这样。如果Friendica使用的任何库已经升级，Composer将获取Friendica当前使用的版本，并刷新自动加载器以确保最佳性能。

### 开发Friendica

首先，感谢您为Friendica做出贡献！
Composer旨在由开发人员使用，以维护Friendica所需的第三方库。
如果您不需要使用任何第三方库，则除了安装/更新Friendica所述的步骤外，无需使用Composer。

#### 向Friendica添加第三方库

您闪闪发光的[Addon](help/Addons)是否需要依赖于Friendica尚未需要的第三方库？
首先，此库应在[Packagist](https://packagist.org)上可用，以便Composer知道如何通过在`composer.json`中提及其名称来直接获取它。

该文件是Friendica对Composer的配置。它列出了有关Friendica项目的详细信息，还列出了所需依赖项及其目标版本的列表。
这是我们当前在Friendica上使用的简化版本：

````json
{
	"name": "friendica/friendica",
	"description": "A decentralized social network part of The Federation",
	"type": "project",
	...
	"require": {
		"ezyang/htmlpurifier": "~4.7.0",
		"mobiledetect/mobiledetectlib": "2.8.*"
	},
	...
}
````

重要的部分在`require`键下，这是Friendica可能需要运行的所有库的列表。
正如您所看到的，目前我们只需要两个，即HTMLPurifier和MobileDetect。
每个库都有一个不同的目标版本，并且根据[Composer关于版本约束的文档](https://getcomposer.org/doc/articles/versions.md#writing-basic-version-constraints)，这意味着：

* 我们将HTMLPurifier更新到版本4.8.0不包括在内
* 我们将MobileDetect更新到版本2.9.0不包括在内

您可以使用其他运算符，以允许Composer更新该程序包直到下一个不包括主要版本。或者，如果您的代码需要，您可以指定库的确切版本，而Composer将永远不会更新它，尽管这并不推荐。

要添加库，只需将其Packagist标识符添加到“require”列表中，并设置目标版本字符串。

然后，您应该运行`bin/composer.phar update`将其添加到本地的`vendor`文件夹中，并更新指定依赖项当前版本的`composer.lock`文件。

#### 更新现有依赖项

如果一个包需要更新，无论是到下一个次要版本还是到下一个主要版本，只要您在Friendica中更改了适当的代码，都可以编辑`composer.json`以更新相关库的目标版本字符串。

然后，您应该运行`bin/composer.phar update`在本地的“vendor”文件夹中更新它，并更新指定依赖项当前版本的“composer.lock”文件。

请注意，每次更改前者时，都应与您的工作一起提交`composer.json`和`composer.lock`。

## Composer常见问题解答

### 我使用“composer”命令，并收到有关不要作为root运行它的警告。

请参见[https://getcomposer.org/root]()。
Composer应该作为Web服务器用户运行，通常是Apache的`www-data`或nginx的`http`。
如果无法使用`su - [web user]`切换到Web服务器用户，则可以直接使用`sudo -u [web user]`运行Composer命令。

### 使用“sudo”运行Composer会抱怨无法在“/ root / .composer”中创建Composer缓存目录

这是因为`sudo`并不总是更改“HOME”环境变量，这意味着命令作为Web服务器用户运行，但是系统仍然使用“root”主目录。
但是，您可以临时更改执行单个命令的环境变量。
对于Composer，这将是：
````
$> COMPOSER_HOME=/var/tmp/composer sudo -u [web user] bin/composer.phar [mode]
````

## 相关人物

* [类自动加载](help/autoloader)
* [如何将类移动到“src”](help/Developer-How-To-Move-Classes-to-src)
