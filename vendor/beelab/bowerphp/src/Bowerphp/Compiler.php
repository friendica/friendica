<?php

/*
 * This file is part of Bowerphp.
 *
 * (c) Massimiliano Arione <massimiliano.arione@bee-lab.net>
 * (c) Mauro D'Alatri <mauro.dalatri@bee-lab.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bowerphp;

use Symfony\Component\Finder\Finder;

/**
 * The Compiler class compiles Bower into a phar.
 */
class Compiler
{
    /**
     * @var bool
     */
    private $gz;

    /**
     * @param bool $gz
     */
    public function __construct($gz = false)
    {
        $this->gz = $gz;
    }

    /**
     * Compiles bower into a single phar file
     *
     * @throws \RuntimeException
     * @param  string            $pharFile The full path to the file to create
     */
    public function compile($pharFile = 'bowerphp.phar')
    {
        if (file_exists($pharFile)) {
            unlink($pharFile);
        }

        $phar = new \Phar($pharFile, 0, 'bowerphp.phar');
        $phar->setSignatureAlgorithm(\Phar::SHA1);

        $phar->startBuffering();

        $finder = new Finder();
        $finder->files()
            ->ignoreVCS(true)
            ->name('*.php')
            ->notName('Compiler.php')
            ->notName('ClassLoader.php')
            ->in(__DIR__ . '/..')
        ;

        foreach ($finder as $file) {
            $this->addFile($phar, $file);
        }

        $finder = new Finder();
        $finder->files()
            ->ignoreVCS(true)
            ->name('*.php')
            ->name('*.pem')
            ->name('*.pem.md5')
            ->exclude('Tests')
            ->in(__DIR__ . '/../../vendor/symfony/')
            ->in(__DIR__ . '/../../vendor/guzzle/guzzle/src/')
            ->in(__DIR__ . '/../../vendor/knplabs/github-api/lib/')
            ->in(__DIR__ . '/../../vendor/samsonasik/package-versions/src/PackageVersions/')
            ->in(__DIR__ . '/../../vendor/vierbergenlars/php-semver/src/vierbergenlars/LibJs/')
            ->in(__DIR__ . '/../../vendor/vierbergenlars/php-semver/src/vierbergenlars/SemVer/')
        ;
        // required only for php<7
        if (is_dir(__DIR__ . '/../../vendor/ircmaxell/password-compat/lib/')) {
            $finder->in(__DIR__ . '/../../vendor/ircmaxell/password-compat/lib/');
        }

        foreach ($finder as $file) {
            $this->addFile($phar, $file);
        }

        $this->addFile($phar, new \SplFileInfo(__DIR__ . '/../../vendor/autoload.php'));
        $this->addFile($phar, new \SplFileInfo(__DIR__ . '/../../vendor/composer/autoload_psr4.php'));
        $this->addFile($phar, new \SplFileInfo(__DIR__ . '/../../vendor/composer/autoload_files.php'));
        $this->addFile($phar, new \SplFileInfo(__DIR__ . '/../../vendor/composer/autoload_namespaces.php'));
        $this->addFile($phar, new \SplFileInfo(__DIR__ . '/../../vendor/composer/autoload_classmap.php'));
        $this->addFile($phar, new \SplFileInfo(__DIR__ . '/../../vendor/composer/autoload_real.php'));
        $this->addFile($phar, new \SplFileInfo(__DIR__ . '/../../vendor/composer/autoload_static.php'));
        if (file_exists(__DIR__ . '/../../vendor/composer/include_paths.php')) {
            $this->addFile($phar, new \SplFileInfo(__DIR__ . '/../../vendor/composer/include_paths.php'));
        }
        $this->addFile($phar, new \SplFileInfo(__DIR__ . '/../../vendor/composer/ClassLoader.php'));
        $this->addBin($phar);

        // Stubs
        $phar->setStub($this->getStub());

        $phar->stopBuffering();

        if ($this->gz) {
            $phar->compressFiles(\Phar::GZ);
        }

        $this->addFile($phar, new \SplFileInfo(__DIR__ . '/../../LICENSE'), false);

        unset($phar);
        chmod('bowerphp.phar', 0700);
    }

    /**
     * @param \Phar        $phar
     * @param \SplFileInfo $file
     * @param bool         $strip
     */
    private function addFile(\Phar $phar, \SplFileInfo $file, $strip = true)
    {
        $path = strtr(str_replace(dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR, '', $file->getRealPath()), '\\', '/');

        $content = file_get_contents($file);
        if ($strip) {
            $content = $this->stripWhitespace($content);
        } elseif ('LICENSE' === basename($file)) {
            $content = "\n" . $content . "\n";
        }

        $phar->addFromString($path, $content);
    }

    /**
     * @param \Phar $phar
     */
    private function addBin(\Phar $phar)
    {
        $content = file_get_contents(__DIR__ . '/../../bin/bowerphp');
        $content = preg_replace('{^#!/usr/bin/env php\s*}', '', $content);
        $phar->addFromString('bin/bowerphp', $content);
    }

    /**
     * Removes whitespace from a PHP source string while preserving line numbers.
     *
     * @param  string $source A PHP string
     * @return string The PHP string with the whitespace removed
     */
    private function stripWhitespace($source)
    {
        if (!function_exists('token_get_all')) {
            return $source;
        }

        $output = '';
        foreach (token_get_all($source) as $token) {
            if (is_string($token)) {
                $output .= $token;
            } elseif (in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                $output .= str_repeat("\n", substr_count($token[1], "\n"));
            } elseif (T_WHITESPACE === $token[0]) {
                // reduce wide spaces
                $whitespace = preg_replace('{[ \t]+}', ' ', $token[1]);
                // normalize newlines to \n
                $whitespace = preg_replace('{(?:\r\n|\r|\n)}', "\n", $whitespace);
                // trim leading spaces
                $whitespace = preg_replace('{\n +}', "\n", $whitespace);
                $output .= $whitespace;
            } else {
                $output .= $token[1];
            }
        }

        return $output;
    }

    /**
     * @return string
     */
    private function getStub()
    {
        $stub = <<<'EOF'
#!/usr/bin/env php
<?php
/*
 * This file is part of Bowerphp.
 *
 * (c) Massimiliano Arione <massimiliano.arione@bee-lab.net>
 *     Mauro D'Alatri <mauro.dalatri@bee-lab.net>
 *
 * For the full copyright and license information, please view
 * the license that is located at the bottom of this file.
 */

Phar::mapPhar('bowerphp.phar');

EOF;

        return $stub . <<<'EOF'
require 'phar://bowerphp.phar/bin/bowerphp';

__HALT_COMPILER();
EOF;
    }
}
