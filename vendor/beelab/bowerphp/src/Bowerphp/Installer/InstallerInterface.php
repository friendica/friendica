<?php

namespace Bowerphp\Installer;

use Bowerphp\Package\PackageInterface;
use Symfony\Component\Finder\Finder;

/**
 * Interface for the package installation manager
 */
interface InstallerInterface
{
    /**
     * Installs specific package
     *
     * @param PackageInterface $package package instance
     */
    public function install(PackageInterface $package);

    /**
     * Updates specific package.
     *
     * @param PackageInterface $package
     *
     * @throws \InvalidArgumentException if package is not installed
     */
    public function update(PackageInterface $package);

    /**
     * Uninstalls specific package
     *
     * @param PackageInterface $package package instance
     */
    public function uninstall(PackageInterface $package);

    /**
     * Get installed packages.
     *
     * @param Finder $finder
     *
     * @return array
     */
    public function getInstalled(Finder $finder);

    /**
     * Find packages that depend on given package
     *
     * @param PackageInterface $package
     * @param Finder           $finder
     *
     * @return array
     */
    public function findDependentPackages(PackageInterface $package, Finder $finder);
}
