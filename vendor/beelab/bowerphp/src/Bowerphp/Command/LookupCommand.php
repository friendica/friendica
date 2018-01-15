<?php

/*
 * This file is part of Bowerphp.
 *
 * (c) Mauro D'Alatri <mauro.dalatri@bee-lab.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bowerphp\Command;

use Bowerphp\Util\PackageNameVersionExtractor;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Lookup
 */
class LookupCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('lookup')
            ->setDescription('Look up a package URL by name')
            ->addArgument('package', InputArgument::REQUIRED, 'Choose a package.')
            ->setHelp(<<<'EOT'
The <info>%command.name%</info> command is used for search with exact match the repository URL package

  <info>php %command.full_name% packageName</info>
EOT
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setGithubToken($output);

        $name = $input->getArgument('package');

        $packageNameVersion = PackageNameVersionExtractor::fromString($name);

        $bowerphp = $this->getBowerphp($output);

        $package = $bowerphp->lookupPackage($packageNameVersion->name);

        $this->consoleOutput->writelnSearchOrLookup($package['name'], $package['url']);
    }
}
