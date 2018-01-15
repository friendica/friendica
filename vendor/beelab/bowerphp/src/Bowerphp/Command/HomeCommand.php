<?php

/*
 * This file is part of Bowerphp.
 *
 * (c) Massimiliano Arione <massimiliano.arione@bee-lab.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bowerphp\Command;

use Bowerphp\Package\Package;
use Bowerphp\Util\PackageNameVersionExtractor;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * Home
 */
class HomeCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('home')
            ->setDescription('Opens a package homepage into your favorite browser')
            ->addArgument('package', InputArgument::REQUIRED, 'Choose a package.')
            ->setHelp(<<<'EOT'
The <info>%command.name%</info> command opens a package homepage into your favorite browser.

  <info>php %command.full_name% name</info>
EOT
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setGithubToken($output);

        $packageName = $input->getArgument('package');

        $packageNameVersion = PackageNameVersionExtractor::fromString($packageName);

        $package = new Package($packageNameVersion->name, $packageNameVersion->version);
        $bowerphp = $this->getBowerphp($output);

        $url = $bowerphp->getPackageInfo($package);

        $default = $this->getDefaultBrowser();

        $arg = "$default \"$url\"";

        if (OutputInterface::VERBOSITY_DEBUG <= $output->getVerbosity()) {
            $output->writeln($arg);
        } else {
            $output->writeln('');
        }
        // @codeCoverageIgnoreStart
        if (!defined('PHPUNIT_BOWER_TESTSUITE')) {
            $browser = new Process($arg);
            $browser->start();
            while ($browser->isRunning()) {
                // do nothing...
            }
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * @return string
     * @codeCoverageIgnore
     */
    private function getDefaultBrowser()
    {
        $xdgOpen = new Process('which xdg-open');
        $xdgOpen->run();
        if (!$xdgOpen->isSuccessful()) {
            $open = new Process('which open');
            $open->run();
            if (!$open->isSuccessful()) {
                throw new \RuntimeException('Could not open default browser.');
            }

            return trim($open->getOutput());
        }

        return trim($xdgOpen->getOutput());
    }
}
