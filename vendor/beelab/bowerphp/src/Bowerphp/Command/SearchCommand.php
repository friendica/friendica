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

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Search
 */
class SearchCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('search')
            ->setDescription('Search for a package by name')
            ->addArgument('name', InputArgument::REQUIRED, 'Name to search for.')
            ->setHelp(<<<'EOT'
The <info>%command.name%</info> command searches for a package by name.

  <info>php %command.full_name% name</info>
EOT
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setGithubToken($output);

        $name = $input->getArgument('name');

        $bowerphp = $this->getBowerphp($output);
        $packages = $bowerphp->searchPackages($name);

        if (empty($packages)) {
            $output->writeln('No results.');
        } else {
            $output->writeln('Search results:' . PHP_EOL);
            $consoleOutput = $this->consoleOutput;
            array_walk($packages, function ($package) use ($consoleOutput) {
                $consoleOutput->writelnSearchOrLookup($package['name'], $package['url'], 4);
            });
        }
    }
}
