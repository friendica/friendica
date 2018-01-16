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
use Bowerphp\Util\PackageNameVersionExtractor;
use Bowerphp\Util\ZipArchive;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Uninstall
 */
class UninstallCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('uninstall')
            ->setDescription('Uninstalls a single specified package')
            ->addArgument('package', InputArgument::REQUIRED, 'Choose a package.')
            ->setHelp(<<<'EOT'
The <info>%command.name%</info> command uninstall a package.

  <info>php %command.full_name% packageName</info>
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

        $bowerphp = $this->getBowerphp($output);

        try {
            $installer = new Installer($this->filesystem, new ZipArchive(), $this->config);

            $packageNameVersion = PackageNameVersionExtractor::fromString($packageName);

            $package = new Package($packageNameVersion->name, $packageNameVersion->version);
            $bowerphp->uninstallPackage($package, $installer);
        } catch (\RuntimeException $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));

            return 1;
        }

        $output->writeln('');

        return 0;
    }
}
