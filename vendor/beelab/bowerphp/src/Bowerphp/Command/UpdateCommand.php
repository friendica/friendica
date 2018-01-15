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
use Bowerphp\Util\ZipArchive;
use RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Inspired by Composer https://github.com/composer/composer
 */
class UpdateCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('update')
            ->setDescription('Update the project dependencies from the bower.json file or a single specified package')
            ->addArgument('package', InputArgument::OPTIONAL, 'Choose a package.')
            ->setHelp(<<<'EOT'
The <info>%command.name%</info> command reads the bower.json file from
the current directory, processes it, and downloads and installs all the
libraries and dependencies outlined in that file.

  <info>php %command.full_name%</info>

If an optional package name is passed, only that package is updated.

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

        $packageName = $input->getArgument('package');
        $installer = new Installer($this->filesystem, new ZipArchive(), $this->config);

        try {
            $bowerphp = $this->getBowerphp($output);
            if (is_null($packageName)) {
                $bowerphp->updatePackages($installer);
            } else {
                $bowerphp->updatePackage(new Package($packageName), $installer);
            }
        } catch (RuntimeException $e) {
            throw new RuntimeException($e->getMessage());
        }
        $output->writeln('');
    }
}
