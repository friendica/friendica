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

use Bowerphp\Installer\Installer;
use Bowerphp\Package\Package;
use Bowerphp\Repository\GithubRepository;
use Bowerphp\Util\PackageNameVersionExtractor;
use Bowerphp\Util\ZipArchive;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Inspired by Composer https://github.com/composer/composer
 */
class InstallCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('install')
            ->setDescription('Installs the project dependencies from the bower.json file or a single specified package')
            ->addOption('save', 'S', InputOption::VALUE_NONE, 'Add installed package to bower.json file.')
            ->addArgument('package', InputArgument::OPTIONAL, 'Choose a package.')
            ->setHelp(<<<'EOT'
The <info>%command.name%</info> command reads the bower.json file from
the current directory, processes it, and downloads and installs all the
libraries and dependencies outlined in that file.

  <info>php %command.full_name%</info>

If an optional package name is passed, that package is installed.

  <info>php %command.full_name% packageName[#version]</info>

If an optional flag <comment>-S</comment> is passed, installed package is added
to bower.json file (only if bower.json file already exists).

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
        $this->config->setSaveToBowerJsonFile($input->getOption('save'));

        $packageName = $input->getArgument('package');

        $bowerphp = $this->getBowerphp($output);

        try {
            $installer = new Installer($this->filesystem, new ZipArchive(), $this->config);

            if (is_null($packageName)) {
                $bowerphp->installDependencies($installer);
            } else {
                if ('bower.json' === substr($packageName, -10)) {
                    if (!is_readable($packageName)) {
                        $output->writeln(sprintf('<error>Cannot read file %s</error>', $packageName));

                        return 1;
                    }
                    $json = json_decode($this->filesystem->read($packageName), true);
                    if (empty($json['dependencies'])) {
                        $output->writeln(sprintf('<error>Nothing to install in %s</error>', $packageName));

                        return 1;
                    }
                    foreach ($json['dependencies'] as $name => $version) {
                        $package = new Package($name, $version);
                        $bowerphp->installPackage($package, $installer);
                    }
                } else {
                    $packageNameVersion = PackageNameVersionExtractor::fromString($packageName);
                    $package = new Package($packageNameVersion->name, $packageNameVersion->version);
                    $bowerphp->installPackage($package, $installer);
                }
            }
        } catch (\RuntimeException $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
            if (GithubRepository::VERSION_NOT_FOUND == $e->getCode() && !empty($package)) {
                $output->writeln(sprintf('Available versions: %s', implode(', ', $bowerphp->getPackageInfo($package, 'versions'))));
            }

            return 1;
        }

        $output->writeln('');

        return 0;
    }
}
