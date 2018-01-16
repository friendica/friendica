<?php

/*
 * This file is part of the Bowerphp package.
 *
 * (c) Mauro D'Alatri <mauro.dalatri@bee-lab.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bowerphp\Output;

use Bowerphp\Bowerphp;
use Bowerphp\Package\PackageInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BowerphpConsoleOutput
{
    protected $output;

    /**
     * @param OutputInterface $output
     */
    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * writelnInfoPackage
     *
     * @param PackageInterface $package
     * @param string           $action
     * @param string           $message
     */
    public function writelnInfoPackage(PackageInterface $package, $action = '', $message = '')
    {
        $this->output->writeln(sprintf('bower <info>%s</info> <fg=cyan>%s</fg=cyan> %s',
            str_pad($package->getName() . '#' . $package->getRequiredVersion(), 21, ' ', STR_PAD_RIGHT),
            str_pad($action, 10, ' ', STR_PAD_LEFT),
            $message
        ));
    }

    /**
     * writelnInstalledPackage
     *
     * @param PackageInterface $package
     */
    public function writelnInstalledPackage(PackageInterface $package)
    {
        $this->output->writeln(sprintf('bower <info>%s</info> <fg=cyan>%s</fg=cyan>',
            str_pad($package->getName() . '#' . $package->getVersion(), 21, ' ', STR_PAD_RIGHT),
            str_pad('install', 10, ' ', STR_PAD_LEFT)
        ));
    }

    /**
     * writelnNoBowerJsonFile
     */
    public function writelnNoBowerJsonFile()
    {
        $this->output->writeln(sprintf(
            'bower <info>%s</info> <fg=yellow>%s</fg=yellow> %s',
            str_pad('', 21, ' ', STR_PAD_RIGHT),
            str_pad('no-json', 10, ' ', STR_PAD_LEFT),
            'No bower.json file to save to, use bower init to create one'
        ));
    }

    /**
     * Rewrite json with colors and unescaped slashes
     *
     * @param string $jsonString
     */
    public function writelnJson($jsonString)
    {
        $keyColor = preg_replace('/"(\w+)": /', '<info>$1</info>: ', $jsonString);
        $valColor = preg_replace('/"([^"]+)"/', "<fg=cyan>'$1'</fg=cyan>", $keyColor);

        $this->output->writeln(stripslashes($valColor));
    }

    /**
     * writelnJson
     *
     * @param mixed $jsonPart
     */
    public function writelnJsonText($jsonPart)
    {
        $this->output->writeln(sprintf('<fg=cyan>%s</fg=cyan>', json_encode($jsonPart, JSON_PRETTY_PRINT)));
    }

    /**
     * writelnSearchOrLookup
     *
     * @param string $name
     * @param string $homepage
     * @param int    $pad
     */
    public function writelnSearchOrLookup($name, $homepage, $pad = 0)
    {
        $this->output->writeln(sprintf('%s<fg=cyan>%s</fg=cyan> %s', str_repeat(' ', $pad), $name, $homepage));
    }

    /**
     * writelnListPackage
     *
     * @param PackageInterface $package
     * @param Bowerphp         $bowerphp
     */
    public function writelnListPackage(PackageInterface $package, Bowerphp $bowerphp)
    {
        $this->output->writeln(sprintf('%s#%s<info>%s</info>',
            $package->getName(),
            $package->getVersion(),
            $bowerphp->isPackageExtraneous($package) ? ' extraneous' : ''
        ));
    }

    public function writelnUpdatingPackage(PackageInterface $package)
    {
        $this->output->writeln(sprintf('bower <info>%s</info> <fg=cyan>%s</fg=cyan>',
            str_pad($package->getName() . '#' . $package->getRequiredVersion(), 21, ' ', STR_PAD_RIGHT),
            str_pad('install', 10, ' ', STR_PAD_LEFT)
        ));
    }
}
