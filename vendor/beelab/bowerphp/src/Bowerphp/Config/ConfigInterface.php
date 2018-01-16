<?php

/*
 * This file is part of Bowerphp.
 *
 * (c) Massimiliano Arione <massimiliano.arione@bee-lab.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bowerphp\Config;

use Bowerphp\Package\PackageInterface;

/**
 * ConfigInterface
 */
interface ConfigInterface
{
    /**
     * @return string
     */
    public function getBasePackagesUrl();

    /**
     * @return string
     */
    public function getCacheDir();

    /**
     * @return string
     */
    public function getInstallDir();

    /**
     * @return bool
     */
    public function isSaveToBowerJsonFile();

    /**
     * Set true|false for decide if add package reference on bower.json file during install procedure
     *
     * @param bool $flag default true
     */
    public function setSaveToBowerJsonFile($flag = true);

    /**
     * Init project's bower.json file
     *
     * @param array $params
     *
     * @return int
     */
    public function initBowerJsonFile(array $params);

    /**
     * Update project's bower.json with a new added package
     *
     * @param PackageInterface $package
     *
     * @return int
     */
    public function updateBowerJsonFile(PackageInterface $package);

    /**
     * Update project's bower.json from a previous existing one
     *
     * @param array $old values of previous bower.json
     * @param array $new new values
     *
     * @return int
     */
    public function updateBowerJsonFile2(array $old, array $new);

    /**
     * Get content from project's bower.json file
     *
     * @return array
     *
     * @throws \Exception if bower.json does not exist
     */
    public function getBowerFileContent();

    /**
     * Retrieve the array of overrides optionally defined in the bower.json file.
     * Each element's key is a package name, and contains an array of other package names
     * and versions that should replace the dependencies found in that package's canonical bower.json
     *
     * @return array The overrides section from the bower.json file, or an empty array if no overrides section is defined
     */
    public function getOverridesSection();

    /**
     * Get the array of overrides defined for the specified package
     *
     * @param string $packageName The name of the package for which dependencies are being overridden
     *
     * @return array A list of dependency name => override versions to be used instead of the target package's normal dependencies.  An empty array if none are defined
     */
    public function getOverrideFor($packageName);

    /**
     * Get content from a packages' bower.json file
     *
     * @param PackageInterface $package
     *
     * @return array
     *
     * @throws \Exception if bower.json or package.json does not exist in a dir of installed package
     */
    public function getPackageBowerFileContent(PackageInterface $package);

    /**
     * Check if project's bower.json file exists
     *
     * @return bool
     */
    public function bowerFileExists();
}
