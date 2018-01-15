<?php

namespace Bowerphp\Package;

use Bowerphp\Repository\RepositoryInterface;

/**
 * Defines the essential information a package has that is used during solving/installation
 */
interface PackageInterface
{
    /**
     * Returns the package's name without version info, thus not a unique identifier
     *
     * @return string package name
     */
    public function getName();

    /**
     * Returns the version of this package
     *
     * @return string version
     */
    public function getVersion();

    /**
     * Set the version of this package
     *
     * @param string $version
     */
    public function setVersion($version);

    /**
     * Returns the required version of this package
     *
     * @return string version
     */
    public function getRequiredVersion();

    /**
     * Set the required version of this package
     *
     * @param string $version
     */
    public function setRequiredVersion($version);

    /**
     * Returns a set of links to packages which need to be installed before
     * this package can be installed
     *
     * @return array|null An array of package links defining required packages
     */
    public function getRequires();

    /**
     * Set the required packages
     *
     * @param array|null $requires A set of package links
     */
    public function setRequires(array $requires = null);

    /**
     * Returns all package info (e.g. info from package's bower.json)
     *
     * @return array
     */
    public function getInfo();

    /**
     * Set package info
     *
     * @param array $info
     */
    public function setInfo(array $info);

    /**
     * Stores a reference to the repository that owns the package
     *
     * @param RepositoryInterface $repository
     */
    public function setRepository(RepositoryInterface $repository);

    /**
     * Converts the package into a readable and unique string
     *
     * @return string
     */
    public function __toString();
}
