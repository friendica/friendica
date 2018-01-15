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

use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem as BaseFilesystem;

/**
 * Filesystem
 */
class Filesystem extends BaseFilesystem
{
    /**
     * Read a file
     *
     * @param string $filename
     *
     * @return string
     */
    public function read($filename)
    {
        if (!is_readable($filename)) {
            throw new FileNotFoundException(sprintf('File "%s" does not exist.', $filename), 0, null, $filename);
        }

        return file_get_contents($filename);
    }

    /**
     * Write a file
     *
     * @param string $filename The file to be written to
     * @param string $content  The data to write into the file
     * @param int    $mode     The file mode (octal)
     *
     * @throws \Symfony\Component\Filesystem\Exception\IOException If the file cannot be written to
     */
    public function write($filename, $content, $mode = 0644)
    {
        $this->dumpFile($filename, $content);
    }
}
