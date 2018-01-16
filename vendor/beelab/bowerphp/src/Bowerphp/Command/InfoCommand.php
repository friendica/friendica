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

/**
 * Info
 */
class InfoCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('info')
            ->setDescription('Displays overall information of a package or of a particular version')
            ->addArgument('package', InputArgument::REQUIRED, 'Choose a package.')
            ->addArgument('property', InputArgument::OPTIONAL, 'A property present in bower.json.')
            ->setHelp(<<<'EOT'
The <info>%command.name%</info> command displays overall information of a package or of a particular version.
If you pass a property present in bower.json, you can get the correspondent value.

  <info>php %command.full_name% package</info>
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
        $property = $input->getArgument('property');

        $packageNameVersion = PackageNameVersionExtractor::fromString($packageName);

        $package = new Package($packageNameVersion->name, $packageNameVersion->version);
        $bowerphp = $this->getBowerphp($output);

        $bowerJsonFile = $bowerphp->getPackageBowerFile($package);
        if ('*' == $packageNameVersion->version) {
            $versions = $bowerphp->getPackageInfo($package, 'versions');
        }
        if (!is_null($property)) {
            $bowerArray = json_decode($bowerJsonFile, true);
            $propertyValue = isset($bowerArray[$property]) ? $bowerArray[$property] : '';
            $this->consoleOutput->writelnJsonText($propertyValue);

            return;
        }
        $this->consoleOutput->writelnJson($bowerJsonFile);
        if ('*' != $packageNameVersion->version) {
            return;
        }
        $output->writeln('');
        if (empty($versions)) {
            $output->writeln('No versions available.');
        } else {
            $output->writeln('<fg=cyan>Available versions:</fg=cyan>');
            foreach ($versions as $vrs) {
                $output->writeln("- $vrs");
            }
        }
    }
}
