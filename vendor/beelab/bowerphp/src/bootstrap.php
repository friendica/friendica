<?php

/*
 * This file is part of Bowerphp.
 *
 * (c) Massimiliano Arione <massimiliano.arione@bee-lab.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$includeIfExists = function ($file) {
    return file_exists($file) ? include $file : false;
};

if ((!$loader = $includeIfExists(__DIR__ . '/../vendor/autoload.php')) && (!$loader = $includeIfExists(__DIR__ . '/../../../autoload.php'))) {
    $error = 'You must set up the project dependencies, run the following commands:' . PHP_EOL .
        'curl -sS https://getcomposer.org/installer | php' . PHP_EOL .
        'php composer.phar install' . PHP_EOL;
    throw new \Exception($error);
}

return $loader;
