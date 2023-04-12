如何将类移动到 `src` 文件夹
==============

* [首页](help)
  * [开发者介绍](help/Developers-Intro)

Friendica使用[Composer](help/Composer)来管理自动加载。
这意味着将所有PHP类文件移动到`src`文件夹时，当第一次在流中使用了定义的类时，它们都将[自动包含](help/autoloader)。
这比当前的`require`用法改进了，因为文件将根据实际的使用情况包括，而不是基于`require`调用的存在。

然而，在将类文件从`include`文件夹移动到`src`文件夹时，有许多要检查的项目，本页列出了这些要点。

## 确定命名空间

虽然这不是所有技术决策中最重要的，但它具有长远的后果，因为它将是从现在开始用来引用此类的名称。
有[a shared Ethercalc sheet](https://ethercalc.org/friendica_classes)可以建议命名空间/类名称，其中列出了已移动的所有类文件，以供灵感参考。

以下是一些提示：
* `Friendica`是`src`文件夹中所有类的基础命名空间
* 命名空间与目录结构匹配，其中`Friendica`命名空间是基本的`src`目录。在`Friendica\Core`命名空间中设置的`Config`类预计可以在`src/Core/Config.php`中找到。
* 命名空间可以帮助将具有相似目的或与特定功能相关的类进行分组

当你完成确定命名空间时，就该使用它了。
假设我们为`Config`类选择了`Friendica\Core`。

## 使用命名空间

要声明命名空间，文件`src/Core/Config.php`必须以以下语句开头：

````php
namespace Friendica\Core;
````

从现在开始，`Config`类可以被称为`Friendica\Core\Config`，但这并不是非常实用的，特别是当以前使用的类是`Config`时。
幸运的是，PHP通过`use`提供了命名空间快捷方式。

这种语言构造仅为命名空间或类提供了不同的命名方案，但不能单独触发自动加载机制。
以下是您可以使用`use`的不同方法：

````php
// No use
$config = new Friendica\Core\Config();
````
````php
// Namespace shortcut
use Friendica\Core;

$config = new Core\Config();
````
````php
// Class name shortcut
use Friendica\Core\Config;

$config = new Config();
````
````php
// Aliasing
use Friendica\Core\Config as Cfg;

$config = new Cfg();
````

无论选择哪种样式，都必须在整个存储库中进行搜索以找到所有的类名用法，并使用包括命名空间的完全合格的类名或在每个相关文件的开头添加一个 `use` 语句。

## 转义非命名空间类

您刚刚移动的类文件现在位于 `Friendica` 命名空间中，但对于此文件中引用的所有类来说，可能并非如此。
由于我们在文件中添加了一个 `namespace Friendica\Core;`，所有在 `include` 中声明的类名都将被隐式地理解为 `Friendica\Core\ClassName`，这很少是我们期望的。

为避免出现 `Class Friendica\Core\ClassName not found` 错误，所有 `include` 声明的类名都必须以 `\` 开头，它告诉自动加载器不要在命名空间中查找该类，而要在非命名空间类所在的全局空间中查找。如果只有少数引用了一个单独的非命名空间类，只需在其前面加上 `\` 即可。然而，如果有许多实例，我们可以再次使用 `use`

````php
namespace Friendica\Core;
...
if (\DBM::is_result($r)) {
    ...
}
````
````php
namespace Friendica\Core;

use Friendica\Database\DBM;

if (DBM::is_result($r)) {
    ...
}
````

## 删除不必要的 `require`

现在，您已经成功地将类移动到自动加载的 `src` 文件夹中，因此不再需要在应用程序的任何地方包括此文件。请删除所有对以前文件的 `require_once` 提及，因为即使未使用该类，它们也会引发致命错误。

## 其他提示

当您完成移动类之后，请从 Friendica 基本目录运行 `php bin/console.php typo` 检查明显的错误。
然而，此工具并不是万无一失的，建议使用 Friendica 的暂存安装来测试您的类移动，以免影响您的生产服务器（如果您拥有一个）。

Friendica 的大多数进程都在后台运行，因此请确保打开调试日志以检查在简单浏览 Friendica 时不会显示的错误。

检查类文件中是否存在任何魔术常量 `__FILE__` 或 `__DIR__`，因为它们的值已由于您在文件树中移动该类而更改。
大多数情况下它被用于调试目的，但也有可能被用于创建缓存文件夹等情况。

## 相关

* [类自动加载](help/autoloader)
* [使用 Composer](help/Composer)