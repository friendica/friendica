# Package Versions

> This is a backport of `ocramius/package-versions` that support php 5.6, while `ocramius/package-versions` only support php 7+.

This utility provides quick and easy access to version information of composer dependencies.

This information is derived from the ```composer.lock``` file which is (re)generated during ```composer install``` or ```composer update```.

```php
$version = \PackageVersions\Versions::getVersion('samsonasik/package-versions');
var_dump($version); // 1.0.0@0beec7b5ea3f0fdbc95d0dd47f3c5bc275da8a33

$version = \PackageVersions\Versions::getShortVersion('samsonasik/package-versions');
var_dump($version); // 1.0.0

$version = \PackageVersions\Versions::getMajorVersion('samsonasik/package-versions');
var_dump($version); // 1
```

[![Build Status](https://travis-ci.org/samsonasik/PackageVersions.svg?branch=master)](https://travis-ci.org/samsonasik/PackageVersions)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/samsonasik/PackageVersions/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/samsonasik/PackageVersions/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/samsonasik/PackageVersions/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/samsonasik/PackageVersions/?branch=master)
[![Packagist](https://img.shields.io/packagist/v/samsonasik/package-versions.svg)](https://packagist.org/packages/samsonasik/package-versions)
[![Packagist Pre Release](https://img.shields.io/packagist/vpre/samsonasik/package-versions.svg)](https://packagist.org/packages/samsonasik/package-versions)

### Installation

```sh
composer require samsonasik/package-versions
```

It is suggested that you re-dump the autoloader in order to prevent
autoload I/O when accessing the `PackageVersions\Versions` API:

```sh
composer dump-autoload --optimize
```

### Use-cases

This repository implements `PackageVersions\Versions::getVersion()` in such a way that no IO
happens when calling it, because the list of package versions is compiled during composer
installation.

This is especially useful when you want to generate assets/code/artifacts that are computed from
the current version of a certain dependency. Doing so at runtime by checking the installed
version of a package would be too expensive, and this package mitigates that.


