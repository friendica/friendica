How To Move Classes to `src`
==============

* [Home](help)
  * [Developer Intro](help/Developers-Intro)

Friendica uses [Composer](help/Composer) to manage autoloading.
This means that all the PHP class files moved to the `src` folder will be [automatically included](help/autoloader) when the class it defines is first used in the flow.
This is an improvement over the current `require` usage since files will be included on an actual usage basis instead of the presence of a `require` call.

However, there are a significant number of items to check when moving a class file from the `include` folder to the `src` folder, and this page is there to list them.

## Decide the namespace

This isn't the most technical decision of them all, but it has long lasting consequences as it will be the name that will be used to refer to this class from now on.
There is [a shared Ethercalc sheet](https://ethercalc.org/friendica_classes) to suggest namespace/class names that lists all the already moved class files for inspiration.

A few pointers though:
* `Friendica` is the base namespace for all classes in the `src` folder
* Namespaces match the directory structure, with `Friendica` namespace being the base `src` directory. The `Config` class set in the `Friendica\Core` namespace is expected to be found at `src/Core/Config.php`.
* Namespaces can help group classes with a similar purpose or relevant to a particular feature

When you're done deciding the namespace, it's time to use it.
Let's say we choose `Friendica\Core` for the `Config` class.

## Use the namespace

To declare the namespace, the file `src/Core/Config.php` must start with the following statement:

````php
namespace Friendica\Core;
````

From now on, the `Config` class can be referred to as `Friendica\Core\Config`, however it isn't very practical, especially when the class was previously used as `Config`.
Thankfully, PHP provides namespace shortcuts through `use`.

This language construct just provides a different naming scheme for a namespace or a class, but doesn't trigger the autoload mechanism on its own.
Here are the different ways you can use `use`:

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

Whatever the style chosen, a repository-wide search has to be done to find all the class name usage and either use the fully-qualified class name (including the namespace) or add a `use` statement at the start of each relevant file.

## Escape non-namespace classes

The class file you just moved is now in the `Friendica` namespace, but it probably isn't the case for all the classes referenced in this file.
Since we added a `namespace Friendica\Core;` to the file, all the class names still declared in `include` will be implicitly understood as `Friendica\Core\ClassName`, which is rarely what we expect.

To avoid `Class Friendica\Core\ClassName not found` errors, all the `include`-declared class names have to be prepended with a `\`, it tells the autoloader not to look for the class in the namespace but in the global space where non-namespaced classes are set.
If there are only a handful of references to a single non-namespaced class, just prepending `\` is enough. However, if there are many instance, we can use `use` again.

````php
namespace Friendica\Core;
...
if (\dbm::is_result($r)) {
    ...
}
````
````php
namespace Friendica\Core;

use \dbm;

if (dbm::is_result($r)) {
    ...
}
````

## Remove any useless `require`

Now that you successfully moved your class to the autoloaded `src` folder, there's no need to include this file anywhere in the app ever again.
Please remove all the `require_once` mentions of the former file, as they will provoke a Fatal Error even if the class isn't used.

## Miscellaneous tips

When you are done with moving the class, please run `php util/typo.php` from the Friendica base directory to check for obvious mistakes.
Howevever, this tool isn't bullet-proof, and a staging install of Friendica is recommended to test your class move without impairing your production server if you host one.

Most of Friendica processes are run in the background, so make sure to turn on your debug log to check for errors that wouldn't show up while simply browsing Friendica.

Check the class file for any magic constant `__FILE__` or `__DIR__`, as their value changed since you moved the class in the file tree.
Most of the time it's used for debugging purposes but there can be instances where it's used to create cache folders for example.

## Related

* [Class autoloading](help/autoloader)
* [Using Composer](help/Composer)