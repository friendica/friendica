# Where to get started to help improve Friendica

<!-- markdownlint-disable MD010 MD013 -->

* [Home](help)

Do you want to help us improve Friendica?
Here we have compiled some hints on how to get started and some tasks to help you choose.
A project like Friendica is the sum of many different contributions.
**Very different skills are required to make good software, not all of them involve coding!**
We are looking for helpers in all areas, whether you write text or code, whether you spread the word to convince people or design new icons.
Whether you feel like an expert or like a newbie - join us with your ideas!

## Contact us

The discussion of Friendica development takes place in the following Friendica groups:

* The main [group for Friendica development](https://forum.friendi.ca/profile/developers)

## Help other users

Remember the questions you had when you first tried Friendica?
A good place to start can be to help new people find their way around Friendica in the [general support group](https://forum.friendi.ca/profile/helpers).
Welcome them, answer their questions, point them to documentation or ping other helpers directly if you can't help but think you know who can.

## Translation

The documentation contains help on how to translate Friendica [at Transifex](/help/translations) where the UI is translated.
If you don't want to translate the UI, or it is already done to your satisfaction, you might want to work on the translation of the /help files?

## Design

Are you good at designing things?
If you have seen Friendica you probably have ideas to improve it, haven't you?

* If you would like to work with us on enhancing the user interface, please join the [group for Friendica development](https://forum.friendi.ca/profile/developers).
* Make plans for a better Friendica interface design and share them with us.
* Tell us if you are able to realize your ideas or what kind of help you need.
	We can't promise we have the right skills in the group, but we'll try.
* Choose a thing to start with, e.g. work on the icon set of your favorite theme

## Programming

Friendica uses an implementation of [Domain-Driven-Design](help/Developer-Domain-Driven-Design), please make sure to check out the provided links for hints at the expected code architecture.

### Composer

Friendica uses [Composer](https://getcomposer.org) to manage dependencies libraries and the class autoloader both for libraries and namespaced Friendica classes.

It's a command-line tool that downloads required libraries into the `vendor` folder and makes any namespaced class in `src` available through the whole application.

If you want to have git automatically update the dependencies with composer, you can use the `post-merge` [git-hook](https://git-scm.com/book/en/v2/Customizing-Git-Git-Hooks) with a script similar to this one:

    #!/usr/bin/env bash
    # MIT © Sindre Sorhus - sindresorhus.com
    # forked by Gianluca Guarini
    # phponly by Ivo Bathke ;)
    # modified for Friendica by Tobias Diekershoff
    changed_files="$(git diff-tree -r --name-only --no-commit-id ORIG_HEAD HEAD)"
    check_run() {
		    echo "$changed_files" | grep --quiet "$1" && eval "$2"
    }
    # `composer install` if the `composer.lock` file gets changed
    # to update all the php dependencies
    check_run composer.lock "bin/composer.phar install --no-dev"

just place it into `.git/hooks/post-merge` and make it executable.

* [Class autoloading](help/autoloader)
* [Using Composer](help/Composer)
* [How To Move Classes to `src`](help/Developer-How-To-Move-Classes-to-src)

### Coding standards

For the sake of consistency between contribution and general code readability, Friendica follows the widespread [PSR-2 coding standards](http://www.php-fig.org/psr/psr-2/) excepted a few rules.
Here's a few primers if you are new to Friendica or to the PSR-2 coding standards:

* Indentation is tabs, period (not PSR-2).
* By default, strings are enclosed in single quotes, but feel free to use double quotes if it makes more sense (SQL queries, adding tabs and line feeds).
* Operators are wrapped by spaces, e.g. `$var === true`, `$var = 1 + 2` and `'string' . $concat . 'enation'`
* Braces are mandatory in conditions
* Boolean operators are `&&` and `||` for PHP conditions, `AND` and `OR` for SQL queries
* No closing PHP tag
* No trailing spaces
* Array declarations use the new square brackets syntax
* Quoting style is single quotes by default, except for needed string interpolation, SQL query strings by convention and comments that should stay in natural language.

Don't worry, you don't have to know by heart the PSR-2 coding standards to start contributing to Friendica.
There are a few tools you can use to check or fix your files before you commit.

For documentation, we use the standard of *one sentence per line* for the `md` files in the `/doc` and `/doc/$lng` subdirectories.

#### Check with [PHP Code Sniffer](https://github.com/squizlabs/PHP_CodeSniffer)

This tool checks your files against a variety of coding standards, including PSR-2, and outputs a report of all the standard violations.
You can simply install it through PEAR: `pear install PHP_CodeSniffer`
Once it is installed and available in your PATH, here's the command to run before committing your work:

	$> phpcs --standard=ruleset.xml <file or directory>

The output is a list of all the coding standards violations that you should fix before committing your work.
Additionally, `phpcs` integrates with a few IDEs (Eclipse, Netbeans, PHPStorm...) so that you don't have to fiddle with the command line.

#### Fix with PHP Code Beautifier and Fixer (phpbcf) included in PHP Code Sniffer

If you're getting a massive list of standards violations when running `phpcs`, it can be annoying to fix all the violations by hand.
Thankfully, PHP Code Sniffer is shipped with an automatic code fixer that can take care of the tedious task for you.
Here's the command to automatically fix the files you created/modified:

	$> phpcbf --standard=ruleset.xml <file or directory>

If the command-line tools `diff` and `patch` are unavailable for you, `phpcbf` can use slightly slower PHP equivalents by using the `--no-patch` argument.

### Code documentation

If you are interested in having the documentation of the Friendica code outside the code files, you can use [Doxygen](http://doxygen.org) to generate it.
The configuration file for Doxygen is located in the base directory of the project sources.
Run

	$> doxygen Doxyfile

to generate the files which will be located in the `doc/html` subdirectory in the Friendica directory.
You can browse these files with any browser.

If you find missing documentation, don't hesitate to contact us and write it down to enhance the code documentation.

### Issues

Have a look at our [issue tracker](https://github.com/friendica/friendica) on GitHub!

* Try to reproduce a bug that needs more inquiries and write down what you find out.
* If a bug looks fixed, ask the bug reporters for feedback to find out if the bug can be closed.
* Fix a bug if you can. Please make the pull request against the *develop* branch of the repository.
* There is a *[Junior Job](https://github.com/friendica/friendica/issues?q=is%3Aopen+is%3Aissue+label%3A"Junior+Jobs")* label for issues we think might be a good point to start with.
	But you don't have to limit yourself to those issues.

### Web interface

The thing many people want most is a better interface, preferably a responsive Friendica theme.
This is a piece of work!
If you want to get involved here:

* Look at the first steps that were made (e.g. the clean theme).
	Ask us to find out whom to talk to about their experiences.
* Talk to design people if you know any.
* Let us know about your plans [in the dev group](https://forum.friendi.ca/profile/developers)
	Do not worry about cross-posting.

### Client software

As Friendica is using a [Twitter/GNU Social compatible API](help/api) any of the clients for those platforms should work with Friendica as well.
Furthermore, there are several client projects, especially for use with Friendica.
If you are interested in improving those clients, please contact the developers of the clients directly.

* Android / LinageOS: **Friendiqa** [src](https://git.friendi.ca/lubuwest/Friendiqa)/[Google Play](https://play.google.com/store/apps/details?id=org.qtproject.friendiqa) developed by [Marco R](https://freunde.ma-nic.de/profile/marco)
* iOS: *currently no client*
* SailfishOS: **Friendiy** [src](https://kirgroup.com/projects/fabrixxm/harbour-friendly) - developed by [Fabio](https://kirgroup.com/profile/fabrixxm/profile)
* Windows: **Friendica Mobile** for Windows versions [before 8.1](http://windowsphone.com/s?appid=e3257730-c9cf-4935-9620-5261e3505c67) and [Windows 10](https://www.microsoft.com/store/apps/9nblggh0fhmn) - developed by [Gerhard Seeber](http://mozartweg.dyndns.org/friendica/profile/gerhard/profile)

## Backward compatibility

### Backward Compatibility Promise

Friendica can be extended by addons.
These addons relies on many classes and conventions from Friendica.
As developers our work on Friendica should not break things in the addons without giving the addon maintainers a chance to fix their addons.
Our goal is to build trust for the addon maintainers but also allow Friendica developers to move on.
This is called the Backward Compatibility Promise.

Inspired by the [Symonfy BC promise](https://symfony.com/doc/current/contributing/code/bc.html) we promise BC for every class, interface, trait, enum, function, constant, etc., but with the exception of:

- Classes, interfaces, traits, enums, functions, methods, properties and constants marked as `@internal` or `@private`
- Extending or modifying a `final` class or method in any way
- Calling `private` methods (via Reflection)
- Accessing `private` properties (via Reflection)
- Accessing `private` methods (via Reflection)
- Accessing `private` constants (via Reflection)
- New properties on overridden `protected` methods
- Possible name collisions with new methods in an extended class (addon developers should prefix their custom methods in the extending classes in an appropriate way)
- Dropping support for every PHP version that has reached end of life

### Deprecation and removing features

As the development goes by Friendica needs to get rid of old code and concepts.
This will be done in 3 steps to give addon maintainers a chance to adjust their addons.

**1. Label deprecation**

If we as the Friendica maintainers decide to remove some functions, classes, interface, etc. we start this by adding a `@deprecated` PHPDoc note on the code.
For instance the class `Friendica\Core\Logger` should be removed, so we add the following note with a possible replacement:

```php
/**
 * Logger functions
 *
 * @deprecated 2025.02 Use constructor injection or `DI::logger()` instead
 */
class Logger {/* ... */}
```

This way addon developers might be notified early by their IDE or other tools that the usage of the class is deprecated.
In Friendica we can now start to replace all occurrences and usage of this class with the alternative.

The deprecation label COULD be remain over multiple releases.
As long as the code that is labeled with `@deprecated` is used inside Friendica or the official addon repository, it SHOULD NOT be hard deprecated.

**2. Hard deprecation**

If the deprecated code is no longer used inside Friendica or the official addons it MUST be hard deprecated.
The code MUST NOT be deleted.
Starting from the next release, it MUST be stay for at least 5 months.
Hard deprecated code COULD remain longer than 5 months, depending on when a release appears.
Addon developer SHOULD NOT consider that they have more than 5 months to adjust their code.

Hard deprecation code means that the code triggers a muted `E_USER_DEPRECATION` error if it is called.
For instance with the deprecated class `Friendica\Core\Logger` the call of every method should trigger an error:

```php
/**
 * Logger functions
 *
 * @deprecated 2025.02 Use constructor injection or `DI::logger()` instead
 */
class Logger {
	public static function info(string $message, array $context = [])
	{
		@trigger_error('Class `' . __CLASS__ . '` is deprecated since 2025.05 and will be removed after 5 months, use constructor injection or `DI::logger()` instead.', E_USER_DEPRECATED);

		self::getInstance()->info($message, $context);
	}

	/* ... */
}
```

This way the maintainer or users of addons will be notified in the logs that the addon will stop working in one of the next releases.
The addon maintainer now has at least 5 months or at least one release to fix the deprecations.

Please note that the deprecation message contains the release that will be released next.
In the example the code was hard deprecated with release `2025.05`, so it COULD be removed earliest with the `2025.11` release.

**3. Code Removing**

We promise BC for deprecated code for at least 5 months, starting from the release the deprecation was announced.
After this time the deprecated code COULD be remove within the next release.

Breaking changes MUST be happen only in a new release but MUST be hard deprecated first.
The BC promise refers only to releases, respective the `stable` branch.
Deprecated code on other branches like `develop` or RC branches could be removed earlier.
This is not a BC break as long as the release will be published 5 months after the hard deprecation.

If a release breaks BC without deprecation or earlier than 5 months, this SHOULD considered as a bug and BC SHOULD be restored in a bugfix release.
