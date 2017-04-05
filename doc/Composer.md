Using Composer
==============

* [Home](help)
  * [Developer Intro](help/Developers-Intro)

Friendica uses [Composer](https://getcomposer.org) to manage dependencies libraries and the class autoloader both for libraries and namespaced Friendica classes.

It's a command-line tool that downloads required libraries into the `vendor` folder and makes any namespaced class in `src` available through the whole application through `boot.php`.

* [Class autoloading](help/autoloader)

## How to use Composer

If you don't have Composer installed on your system, Friendica ships with a copy of it at `util/composer.phar`.
For the purpose of this help, all examples will use this path to run Composer commands, however feel free to replace them with your own way of calling Composer.
Composer requires PHP CLI and the following examples assume it's available system-wide.

### Installing/Updating Friendica

#### From Archive

If you just unpacked a Friendica release archive, you don't have to use Commposer at all, all the required libraries are already bundled in the archive.

#### Installing with Git

If you prefer to use `git`, you will have to run Composer to fetch the required libraries and build the autoloader before you can run Friendica.
Here are the typical commands you will have to run to do so:

````
~> git clone https://github.com/friendica/friendica.git friendica
~/friendica> cd friendica
~/friendica> util/composer.phar install
````

That's it! Composer will take care of fetching all the required libraries in the `vendor` folder and build the autoloader to make those libraries available to Friendica.

#### Updating with Git

Updating Friendica to the current stable or the latest develop version is easy with Git, just remember to run Composer after every branch pull.

````
~> cd friendica
~/friendica> git pull
~/friendica> util/composer.phar install
````

And that's it. If any library used by Friendica has been upgraded, Composer will fetch the version currently used by Friendica and refresh the autoloader to ensure the best performances.

### Developing Friendica

First of all, thanks for contributing to Friendica!
Composer is meant to be used by developers to maintain third-party libraries required by Friendica.
If you don't need to use any third-party library, then you don't need to use Composer beyond what is above to install/update Friendica.

#### Adding a third-party library to Friendica

Does your shiny new [Plugin](help/Plugins) need to rely on a third-party library not required by Friendica yet?
First of all, this library should be available on [Packagist](https://packagist.org) so that Composer knows how to fetch it directly just by mentioning its name in `composer.json`.

This file is the configuration of Friendica for Composer. It lists details about the Friendica project, but also a list of required dependencies and their target version.
Here's a simplified version of the one we currently use on Friendica:

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

The important part is under the `require` key, this is a list of all the libraries Friendica may need to run.
As you can see, at the moment we only require two, HTMLPurifier and MobileDetect.
Each library has a different target version, and [per Composer documentation about version constraints](https://getcomposer.org/doc/articles/versions.md#writing-basic-version-constraints), this means that:

* We will update HTMLPurifier up to version 4.8.0 excluded
* We will update MobileDetect up to version 2.9.0 excluded

There are other operators you can use to allow Composer to update the package up to the next major version excluded.
Or you can specify the exact version of the library if you code requires it, and Composer will never update it although it isn't recommended.

To add a library, just add its Packagist identifier to the `require` list and set a target version string.

Then you should run `util/composer.phar update` to add it to your local `vendor` folder and update the `composer.lock` file that specifies the current versions of the dependencies.

#### Updating an existing dependency

If a package needs to be updated, whether to the next minor version or to the next major version provided you changed the adequate code in Friendica, simply edit `composer.json` to update the target version string of the relevant library.

Then you should run `util/composer.phar update` to update it in your local `vendor` folder and update the `composer.lock` file that specifies the current versions of the dependencies.

Please note that you should commit both `composer.json` and `composer.lock` with your work every time you make a change to the former.

## Composer FAQ

### I used the `composer` command and got a warning about not to run it as root.

See [https://getcomposer.org/root]().
Composer should be run as the web server user, usually `www-data` with Apache or `http` with nginx.
If you can't switch to the web server user using `su - [web user]`, you can directly run the Composer command with `sudo -u [web user]`.

### Running Composer with `sudo` complains about not being able to create the composer cache directory in `/root/.composer`

This is because `sudo` doesn't always change the `HOME` environment variable, which means that the command is run as the web server user but the system still uses `root` home directory.
However, you can temporarily change environment variable for the execution of a single command.
For Composer, this would be:
````
$> COMPOSER_HOME=/var/tmp/composer sudo -u [web user] util/composer.phar [mode]
````
