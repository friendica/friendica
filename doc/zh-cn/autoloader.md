A使用 Composer 的自动加载器

Friendica 使用 [Composer](https://getcomposer.org) 来管理依赖库和类自动加载器，同时支持用于文件库和 Friendica 命名空间类的加载。

Composer 是一种命令行工具，它会将所需的库下载到 `vendor` 文件夹，并使得 `src` 中的任何命名空间类在整个应用程序中都可以使用。

* [使用 Composer](help/Composer)

## 简介：类自动加载

当第一次引用类时，自动加载器会动态地包含定义该类的文件，无需显式使用 "require_once"。无需直接使用自动加载器，您可以直接使用由自动加载器覆盖的任何类（目前为 `vendor` 和 `src`）。

在底层，Composer 通过 [`spl_autoload_register()`](http://php.net/manual/en/function.spl-autoload-register.php) 注册一个回调函数，该函数接收一个类名称作为参数并包含相应的类定义文件。

要了解有关 PHP 自动加载的更多信息，请参阅[官方 PHP 文档](http://php.net/manual/en/language.oop5.autoload.php)。

### 例子

假设您有一个在 `src/` 中定义一个非常有用的类文件：

```php
// src/ItemsManager.php
<?php
namespace Friendica;

class ItemsManager {
	public function getAll() { ... }
	public function getByID($id) { ... }
}
```

类 `ItemsManager` 已在 `Friendica` 命名空间中声明。命名空间对于保持类分离并避免名称冲突非常有用（可能您想要使用的库也定义了一个名为 `ItemsManager` 的类，但只要它在另一个命名空间中，您就不会有任何问题）。

现在假设您需要在视图中加载一些项目，例如在虚构的 `mod/network.php` 中。为了使Composer自动加载器起作用，必须先包含该文件。

代码将如下：

```php
// mod/network.php
<?php

use Friendica\App;

function network_content(App $a) {
	$itemsmanager = new \Friendica\ItemsManager();
	$items = $itemsmanager->getAll();

	// pass $items to template
	// return result
}
```

这是一个相当简单的示例，但请注意：没有 `require()`！如果您需要使用类，则可以直接使用它，而无需做任何其他事情。

继续深入，现在我们有一堆引起一些代码重复的 `*Manager` 类。让我们定义一个 `BaseManager` 类，将所有管理器之间的公共代码移动到此处：

```php
// src/BaseManager.php
<?php
namespace Friendica;

class BaseManager {
	public function thatFunctionEveryManagerUses() { ... }
}
```

然后，让我们更改 ItemsManager 类以使用此代码：

```php
// src/ItemsManager.php
<?php
namespace Friendica;

class ItemsManager extends BaseManager {
	public function getAll() { ... }
	public function getByID($id) { ... }
}
```

即使我们没有显式包含 `src/BaseManager.php` 文件，当该类首次定义时，自动加载器也会为其加载该文件，因为它作为父类被引用。这适用于此处的 "BaseManager" 示例，并且在需要调用静态方法时也适用：

```php
// src/Dfrn.php
<?php
namespace Friendica;

class Dfrn {
	public static function  mail($item, $owner) { ... }
}
```

```php
// mod/mail.php
<?php

mail_post($a){
	...
	Friendica\Protocol\DFRN::mail($item, $owner);
	...
}
```

如果您的代码与所需类在同一命名空间中，则无需在其前面添加命名空间前缀：

```php
// include/delivery.php
<?php

namespace Friendica;

use Friendica\Protocol\DFRN;

// this is the same content of current include/delivery.php,
// but has been declared to be in "Friendica" namespace

[...]
switch($contact['network']) {
	case NETWORK_DFRN:
		if ($mail) {
			$item['body'] = ...
			$atom = DFRN::mail($item, $owner);
		} elseif ($fsuggest) {
			$atom = DFRN::fsuggest($item, $owner);
			q("DELETE FROM `fsuggest` WHERE `id` = %d LIMIT 1", intval($item['id']));
		} elseif ($relocate)
			$atom = DFRN::relocate($owner, $uid);
[...]
```

这是 `include/delivery.php` 的当前代码，并且由于代码被声明为 "Friendica" 命名空间，因此在需要使用 "Dfrn" 类时无需编写它。
但是，如果您想要使用来自另一个库的类，则需要使用完整的命名空间，例如：

```php
// src/Diaspora.php
<?php

namespace Friendica;

class Diaspora {
	public function md2bbcode() {
		$html = \Michelf\MarkdownExtra::defaultTransform($text);
	}
}
```

如果您在代码的许多地方使用该类，并且不想每次都写出完整的类路径，可以使用 "use" PHP 关键字。

```php
// src/Diaspora.php
<?php
namespace Friendica;

use \Michelf\MarkdownExtra;

class Diaspora {
	public function md2bbcode() {
		$html = MarkdownExtra::defaultTransform($text);
	}
}
```

请注意，命名空间类似于文件系统中的路径，由“\”分隔，第一个“\”代表全局作用域。您可以深入到更深层次，例如：

```
// src/Network/Dfrn.php
<?php
namespace Friendica\Network;

class Dfrn {
}
```

请注意，如果命名空间不是简单的 `Friendica`，则定义类的文件位置必须放置在 `src` 的适当子文件夹中。

```
// src/Dba/Mysql
<?php
namespace Friendica\Dba;

class Mysql {
}
```

你可以将命名空间想象成 Unix 文件系统中的文件夹，全局作用域就像根目录("/")一样。

## 相关链接

* [使用 Composer](help/Composer)
* [如何将类移动到 `src` 目录下](help/Developer-How-To-Move-Classes-to-src)