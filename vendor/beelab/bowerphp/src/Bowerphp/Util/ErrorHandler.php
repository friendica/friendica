<?php

/*
 * This file is part of Bowerphp.
 *
 * (c) Massimiliano Arione <massimiliano.arione@bee-lab.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bowerphp\Util;

use ErrorException;

/**
 * Convert PHP errors into exceptions
 * Copied by Composer https://github.com/composer/composer
 */
class ErrorHandler
{
    /**
     * Error handler
     *
     * @param int    $level   Level of the error raised
     * @param string $message Error message
     * @param string $file    Filename that the error was raised in
     * @param int    $line    Line number the error was raised at
     *
     * @static
     * @throws \ErrorException
     */
    public static function handle($level, $message, $file, $line)
    {
        // respect error_reporting being disabled
        if (0 === error_reporting()) {
            return;
        }

        if (ini_get('xdebug.scream')) {
            $message .= "\n\nWarning: You have xdebug.scream enabled, the warning above may be" .
            "\na legitimately suppressed error that you were not supposed to see.";
        }

        throw new ErrorException($message, 0, $level, $file, $line);
    }

    /**
     * Register error handler
     *
     * @static
     */
    public static function register()
    {
        set_error_handler([__CLASS__, 'handle']);
    }
}
