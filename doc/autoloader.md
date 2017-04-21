Autoloader with Composer
==========

* [Home](help)
  * [Developer Intro](help/Developers-Intro)

Friendica uses [Composer](https://getcomposer.org) to manage dependencies libraries and the class autoloader both for libraries and namespaced Friendica classes.

It's a command-line tool that downloads required libraries into the `vendor` folder and makes any namespaced class in `src` available through the whole application through `boot.php`.

* [Using Composer](help/Composer)

## A quick introduction to class autoloading

The autoloader dynamically includes the file defining a class when it is first referenced, either by instantiating an object or simply making sure that it is available, without the need to explicitly use "require_once".

Once it is set up you don't have to directly use it, you can directly use any class that is covered by the autoloader (currently `vendor` and `src`)

Under the hood, Composer registers a callback with [`spl_autoload_register()`](http://php.net/manual/en/function.spl-autoload-register.php) that receives a class name as an argument and includes the corresponding class definition file.
For more info about PHP autoloading, please refer to the [official PHP documentation](http://php.net/manual/en/language.oop5.autoload.php).

### Example

Let's say you have a PHP file in `src/` that define a very useful class:

```php
	// src/ItemsManager.php
	<?php
	namespace \Friendica;

	class ItemsManager {
		public function getAll() { ... }
		public function getByID($id) { ... }
	}
```

The class `ItemsManager` has been declared in the `Friendica` namespace.
Namespaces are useful to keep classes separated and avoid names conflicts (could be that a library you want to use also defines a class named `ItemsManager`, but as long as it is in another namespace, you don't have any problem)

Let's say now that you need to load some items in a view, maybe in a fictional `mod/network.php`.
In order for the Composer autoloader to work, it must first be included. In Friendica this is already done at the top of `boot.php`, with `require_once('vendor/autoload.php');`.

The code will be something like:

```php
	// mod/network.php
	<?php

	function network_content(App $a) {
		$itemsmanager = new \Friendica\ItemsManager();
		$items = $itemsmanager->getAll();

		// pass $items to template
		// return result
	}
```

That's a quite simple example, but look: no `require()`!
If you need to use a class, you can simply use it and you don't need to do anything else.

Going further: now we have a bunch of `*Manager` classes that cause some code duplication, let's define a `BaseManager` class, where we move all common code between all managers:

```php
	// src/BaseManager.php
	<?php
	namespace \Friendica;

	class BaseManager {
		public function thatFunctionEveryManagerUses() { ... }
	}
```

and then let's change the ItemsManager class to use this code

```php
	// src/ItemsManager.php
	<?php
	namespace \Friendica;

	class ItemsManager extends BaseManager {
		public function getAll() { ... }
		public function getByID($id) { ... }
	}
```

Even though we didn't explicitly include the `src/BaseManager.php` file, the autoloader will when this class is first defined, because it is referenced as a parent class.
It works with the "BaseManager" example here and it works when we need to call static methods:

```php
	// src/Dfrn.php
	<?php
	namespace \Friendica;

	class Dfrn {
		public static function  mail($item, $owner) { ... }
	}
```

```php
	// mod/mail.php
	<?php

	mail_post($a){
		...
		\Friendica\dfrn::mail($item, $owner);
		...
	}
```

If your code is in same namespace as the class you need, you don't need to prepend it:

```php
	// include/delivery.php
	<?php

	namespace \Friendica;

	// this is the same content of current include/delivery.php,
	// but has been declared to be in "Friendica" namespace

	[...]
	switch($contact['network']) {
		case NETWORK_DFRN:
			if ($mail) {
				$item['body'] = ...
				$atom = Dfrn::mail($item, $owner);
			} elseif ($fsuggest) {
				$atom = Dfrn::fsuggest($item, $owner);
				q("DELETE FROM `fsuggest` WHERE `id` = %d LIMIT 1", intval($item['id']));
			} elseif ($relocate)
				$atom = Dfrn::relocate($owner, $uid);
	[...]
```

This is the current code of `include/delivery.php`, and since the code is declared to be in the "Friendica" namespace, you don't need to write it when you need to use the "Dfrn" class.
But if you want to use classes from another library, you need to use the full namespace, e.g.

```php
	// src/Diaspora.php
	<?php

	namespace \Friendica;

	class Diaspora {
		public function md2bbcode() {
			$html = \Michelf\MarkdownExtra::defaultTransform($text);
		}
	}
```

if you use that class in many places of the code and you don't want to write the full path to the class every time, you can use the "use" PHP keyword

```php
	// src/Diaspora.php
	<?php
	namespace \Friendica;

	use \Michelf\MarkdownExtra;

	class Diaspora {
		public function md2bbcode() {
			$html = MarkdownExtra::defaultTransform($text);
		}
	}
```

Note that namespaces are like paths in filesystem, separated by "\", with the first "\" being the global scope.
You can go deeper if you want to, like:

```
	// src/Network/Dfrn.php
    <?php
    namespace \Friendica\Network;

    class Dfrn {
    }
```

Please note that the location of the file defining the class must be placed in the appropriate sub-folders of `src` if the namespace isn't plain `\Friendica`.

or

```
	// src/Dba/Mysql
    <?php
    namespace \Friendica\Dba;

    class Mysql {
    }
```

So you can think of namespaces as folders in a Unix file system, with global scope as the root ("\").
