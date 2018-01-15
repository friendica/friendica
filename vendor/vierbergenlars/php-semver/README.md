# php-semver -- The semantic versioner for npm ported to PHP

Ported from [node-semver 1.1.2](https://github.com/isaacs/node-semver/tree/v1.1.2) to PHP

[![Build Status](https://secure.travis-ci.org/vierbergenlars/php-semver.png?branch=master)](http://travis-ci.org/vierbergenlars/php-semver)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/vierbergenlars/php-semver/badges/quality-score.png?s=89ff49019cde97e70228ae14d2dc08b727e72157)](https://scrutinizer-ci.com/g/vierbergenlars/php-semver/)
[![Latest Stable Version](https://poser.pugx.org/vierbergenlars/php-semver/v/stable.png)](https://packagist.org/packages/vierbergenlars/php-semver)
[![Total Downloads](https://poser.pugx.org/vierbergenlars/php-semver/downloads.png)](https://packagist.org/packages/vierbergenlars/php-semver)

## Usage

```php
<?php

use vierbergenlars\SemVer\version;
use vierbergenlars\SemVer\expression;
use vierbergenlars\SemVer\SemVerException;

// Check if a version is valid
$semver = new version('1.2.3');
$semver = new version('a.b.c'); //SemVerException thrown

//Get a clean version string
$semver = new version('=v1.2.3');
$semver->getVersion(); //'1.2.3'

//Check if a version satisfies a range
$semver = new version('1.2.3');
$semver->satisfies(new expression('1.x || >=2.5.0 || 5.0.0 - 7.2.3')); //true
# OR
$range = new expression('1.x || >=2.5.0 || 5.0.0 - 7.2.3');
$range->satisfiedBy(new version('1.2.3')); //true

//Compare two versions
version::gt('1.2.3', '9.8.7'); //false
version::lt('1.2.3', '9.8.7'); //true
```

## Versions

A version is the following things, in this order:

* a number (Major)
* a period
* a number (minor)
* a period
* a number (patch)
* OPTIONAL: a hyphen, followed by a number (build)
* OPTIONAL: a collection of pretty much any non-whitespace characters
  (tag)

A leading `"="` or `"v"` character is stripped off and ignored.

## Comparisons

The ordering of versions is done using the following algorithm, given
two versions and asked to find the greater of the two:

* If the majors are numerically different, then take the one
  with a bigger major number. `2.3.4 > 1.3.4`
* If the minors are numerically different, then take the one
  with the bigger minor number. `2.3.4 > 2.2.4`
* If the patches are numerically different, then take the one with the
  bigger patch number. `2.3.4 > 2.3.3`
* If only one of them has a build number, then take the one with the
  build number.  `2.3.4-0 > 2.3.4`
* If they both have build numbers, and the build numbers are numerically
  different, then take the one with the bigger build number.
  `2.3.4-10 > 2.3.4-9`
* If only one of them has a tag, then take the one without the tag.
  `2.3.4 > 2.3.4-beta`
* If they both have tags, then take the one with the lexicographically
  larger tag.  `2.3.4-beta > 2.3.4-alpha`
* At this point, they're equal.

## Ranges

The following range styles are supported:

* `>1.2.3` Greater than a specific version.
* `<1.2.3` Less than
* `1.2.3 - 2.3.4` := `>=1.2.3 <=2.3.4`
* `~1.2.3` := `>=1.2.3 <1.3.0`
* `~1.2` := `>=1.2.0 <2.0.0`
* `~1` := `>=1.0.0 <2.0.0`
* `1.2.x` := `>=1.2.0 <1.3.0`
* `1.x` := `>=1.0.0 <2.0.0`

Ranges can be joined with either a space (which implies "and") or a
`||` (which implies "or").

## Functions

* `$version->valid()`: Return the parsed version, or null if it's not valid.
* `$version->inc($type)`: Return the version incremented by the release type
  (major, minor, patch, or build), or null if an invalid release type is provided.

### Comparison

* `version::gt($v1, $v2)`: `v1 > v2`
* `version::gte($v1, $v2)`: `v1 >= v2`
* `version::lt($v1, $v2)`: `v1 < v2`
* `version::lte($v1, $v2)`: `v1 <= v2`
* `version::eq($v1, $v2)`: `v1 == v2` This is true if they're logically equivalent,
  even if they're not the exact same string.  You already know how to
  compare strings.
* `version::neq($v1, $v2)`: `v1 != v2` The opposite of eq.
* `version::cmp($v1, $comparator, $v2)`: Pass in a comparison string, and it'll call
  the corresponding function above.  `"==="` and `"!=="` do simple
  string comparison, but are included for completeness.  Throws if an
  invalid comparison string is provided.
* `version::compare($v1, $v2)`: Return 0 if `v1 == v2`, 1 if `v1 > v2`, or -1 if
  `v1 < v2`.  Sorts in ascending order if passed to [`usort()`](http://php.net/manual/en/function.usort.php)
* `version::rcompare($v1, $v2)`: The reverse of compare.  Sorts an array of versions
  in descending order when passed to [`usort()`](http://php.net/manual/en/function.usort.php).


### Ranges

* `$expression->validRange()`: Return the valid range or `null` if it's not valid
* `$version->satisfies($range)`: Return `true` if the version satisfies the range.
* `$expression->maxSatisfying($versions)`: Return the highest version in the array
  that satisfies the range, or `null` if none of them do.

## Thanks to

All contributors (https://github.com/vierbergenlars/php-semver/graphs/contributors)

[@isaacs](https://github.com/isaacs) and other contributors to [node-semver](https://github.com/isaacs/node-semver)
