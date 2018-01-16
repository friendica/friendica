<?php

/*
 * This file is part of Bowerphp.
 *
 * (c) Massimiliano Arione <massimiliano.arione@bee-lab.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bowerphp;

use Bowerphp\Config\ConfigInterface;
use Bowerphp\Installer\InstallerInterface;
use Bowerphp\Output\BowerphpConsoleOutput;
use Bowerphp\Package\Package;
use Bowerphp\Package\PackageInterface;
use Bowerphp\Repository\RepositoryInterface;
use Bowerphp\Util\Filesystem;
use Github\Client;
use Guzzle\Http\Exception\RequestException;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Finder\Finder;
use vierbergenlars\SemVer\expression;
use vierbergenlars\SemVer\version;

/**
 * Main class
 */
class Bowerphp
{
    protected $config;
    protected $filesystem;
    protected $githubClient;
    protected $repository;
    protected $output;

    /**
     * @param ConfigInterface       $config
     * @param Filesystem            $filesystem
     * @param Client                $githubClient
     * @param RepositoryInterface   $repository
     * @param BowerphpConsoleOutput $output
     */
    public function __construct(
        ConfigInterface $config,
        Filesystem $filesystem,
        Client $githubClient,
        RepositoryInterface $repository,
        BowerphpConsoleOutput $output
    ) {
        $this->config = $config;
        $this->filesystem = $filesystem;
        $this->githubClient = $githubClient;
        $this->repository = $repository;
        $this->output = $output;
    }

    /**
     * Init bower.json
     *
     * @param array $params
     */
    public function init(array $params)
    {
        if ($this->config->bowerFileExists()) {
            $bowerJson = $this->config->getBowerFileContent();
            $this->config->setSaveToBowerJsonFile();
            $this->config->updateBowerJsonFile2($bowerJson, $params);
        } else {
            $this->config->initBowerJsonFile($params);
        }
    }

    /**
     * Install a single package
     *
     * @param PackageInterface   $package
     * @param InstallerInterface $installer
     * @param bool               $isDependency
     */
    public function installPackage(PackageInterface $package, InstallerInterface $installer, $isDependency = false)
    {
        if (false !== strpos($package->getName(), 'github')) {
            // install from a github endpoint
            $name = basename($package->getName(), '.git');
            $repoUrl = $package->getName();
            $package = new Package($name, $package->getRequiredVersion());
            $this->repository->setUrl($repoUrl)->setHttpClient($this->githubClient);
            $package->setRepository($this->repository);
            $packageTag = $this->repository->findPackage($package->getRequiredVersion());
            if (is_null($packageTag)) {
                throw new RuntimeException(sprintf('Cannot find package %s version %s.', $package->getName(), $package->getRequiredVersion()));
            }
        } else {
            $packageTag = $this->getPackageTag($package, true);
            $package->setRepository($this->repository);
        }

        $package->setVersion($packageTag);

        $this->updateBowerFile($package, $isDependency);

        // if package is already installed, match current version with latest available version
        if ($this->isPackageInstalled($package)) {
            $this->output->writelnInfoPackage($package, 'validate', sprintf('%s against %s#%s', $packageTag, $package->getName(), $package->getRequiredVersion()));
            $packageBower = $this->config->getPackageBowerFileContent($package);
            if ($packageTag == $packageBower['version']) {
                // if version is fully matching, there's no need to install
                return;
            }
        }

        $this->cachePackage($package);

        $this->output->writelnInstalledPackage($package);
        $installer->install($package);

        $overrides = $this->config->getOverrideFor($package->getName());
        if (array_key_exists('dependencies', $overrides)) {
            $dependencies = $overrides['dependencies'];
        } else {
            $dependencies = $package->getRequires();
        }
        if (!empty($dependencies)) {
            foreach ($dependencies as $name => $version) {
                $depPackage = new Package($name, $version);
                if (!$this->isPackageInstalled($depPackage)) {
                    $this->installPackage($depPackage, $installer, true);
                } elseif ($this->isNeedUpdate($depPackage)) {
                    $this->updatePackage($depPackage, $installer);
                }
            }
        }
    }

    /**
     * Install all dependencies
     *
     * @param InstallerInterface $installer
     */
    public function installDependencies(InstallerInterface $installer)
    {
        $decode = $this->config->getBowerFileContent();
        if (!empty($decode['dependencies'])) {
            foreach ($decode['dependencies'] as $name => $requiredVersion) {
                if (false !== strpos($requiredVersion, 'github')) {
                    list($name, $requiredVersion) = explode('#', $requiredVersion);
                }
                $package = new Package($name, $requiredVersion);
                $this->installPackage($package, $installer, true);
            }
        }
    }

    /**
     * Update a single package
     *
     * @param PackageInterface   $package
     * @param InstallerInterface $installer
     */
    public function updatePackage(PackageInterface $package, InstallerInterface $installer)
    {
        if (!$this->isPackageInstalled($package)) {
            throw new RuntimeException(sprintf('Package %s is not installed.', $package->getName()));
        }
        if (is_null($package->getRequiredVersion())) {
            $decode = $this->config->getBowerFileContent();
            if (empty($decode['dependencies']) || empty($decode['dependencies'][$package->getName()])) {
                throw new InvalidArgumentException(sprintf('Package %s not found in bower.json', $package->getName()));
            }
            $package->setRequiredVersion($decode['dependencies'][$package->getName()]);
        }

        $bower = $this->config->getPackageBowerFileContent($package);
        $package->setInfo($bower);
        $package->setVersion($bower['version']);
        $package->setRequires(isset($bower['dependencies']) ? $bower['dependencies'] : null);

        $packageTag = $this->getPackageTag($package);
        $this->output->writelnInfoPackage($package, 'validate', sprintf('%s against %s#%s', $packageTag, $package->getName(), $package->getRequiredVersion()));

        $package->setRepository($this->repository);
        if ($packageTag == $package->getVersion()) {
            // if version is fully matching, there's no need to update
            return;
        }
        $package->setVersion($packageTag);

        $this->cachePackage($package);

        $this->output->writelnUpdatingPackage($package);
        $installer->update($package);

        $overrides = $this->config->getOverrideFor($package->getName());
        if (array_key_exists('dependencies', $overrides)) {
            $dependencies = $overrides['dependencies'];
        } else {
            $dependencies = $package->getRequires();
        }
        if (!empty($dependencies)) {
            foreach ($dependencies as $name => $requiredVersion) {
                $depPackage = new Package($name, $requiredVersion);
                if (!$this->isPackageInstalled($depPackage)) {
                    $this->installPackage($depPackage, $installer, true);
                } elseif ($this->isNeedUpdate($depPackage)) {
                    $this->updatePackage($depPackage, $installer);
                }
            }
        }
    }

    /**
     * Update all dependencies
     *
     * @param InstallerInterface $installer
     */
    public function updatePackages(InstallerInterface $installer)
    {
        $decode = $this->config->getBowerFileContent();
        if (!empty($decode['dependencies'])) {
            foreach ($decode['dependencies'] as $packageName => $requiredVersion) {
                $this->updatePackage(new Package($packageName, $requiredVersion), $installer);
            }
        }
    }

    /**
     * @param  PackageInterface $package
     * @param  string           $info
     * @return mixed
     */
    public function getPackageInfo(PackageInterface $package, $info = 'url')
    {
        $decode = $this->lookupPackage($package->getName());

        $this->repository->setHttpClient($this->githubClient);

        if ('url' == $info) {
            $this->repository->setUrl($decode['url'], false);

            return $this->repository->getUrl();
        }

        if ('versions' == $info) {
            $tags = $this->repository->getTags();
            usort($tags, function ($a, $b) {
                return version_compare($b, $a);
            });

            return $tags;
        }

        throw new RuntimeException(sprintf('Unsupported info option "%s".', $info));
    }

    /**
     * @param  string $name
     * @return array
     */
    public function lookupPackage($name)
    {
        return $this->findPackage($name);
    }

    /**
     * @param  PackageInterface $package
     * @return string
     */
    public function getPackageBowerFile(PackageInterface $package)
    {
        $this->repository->setHttpClient($this->githubClient);
        $lookupPackage = $this->lookupPackage($package->getName());
        $this->repository->setUrl($lookupPackage['url'], false);
        $tag = $this->repository->findPackage($package->getRequiredVersion());

        return $this->repository->getBower($tag, true, $lookupPackage['url']);
    }

    /**
     * Uninstall a single package
     *
     * @param PackageInterface   $package
     * @param InstallerInterface $installer
     */
    public function uninstallPackage(PackageInterface $package, InstallerInterface $installer)
    {
        if (!$this->isPackageInstalled($package)) {
            throw new RuntimeException(sprintf('Package %s is not installed.', $package->getName()));
        }
        $installer->uninstall($package);
    }

    /**
     * Search packages by name
     *
     * @param  string $name
     * @return array
     */
    public function searchPackages($name)
    {
        try {
            $url = $this->config->getBasePackagesUrl() . 'search/' . $name;
            $response = $this->githubClient->getHttpClient()->get($url);

            return json_decode($response->getBody(true), true);
        } catch (RequestException $e) {
            throw new RuntimeException(sprintf('Cannot get package list from %s.', str_replace('/packages/', '', $this->config->getBasePackagesUrl())));
        }
    }

    /**
     * Get a list of installed packages
     *
     * @param  InstallerInterface $installer
     * @param  Finder             $finder
     * @return array
     */
    public function getInstalledPackages(InstallerInterface $installer, Finder $finder)
    {
        return $installer->getInstalled($finder);
    }

    /**
     * Check if package is installed
     *
     * @param  PackageInterface $package
     * @return bool
     */
    public function isPackageInstalled(PackageInterface $package)
    {
        return $this->filesystem->exists($this->config->getInstallDir() . '/' . $package->getName() . '/.bower.json');
    }

    /**
     * @param PackageInterface $package
     * @param bool             $checkInstall
     *
     * @return bool
     */
    public function isPackageExtraneous(PackageInterface $package, $checkInstall = false)
    {
        if ($checkInstall && !$this->isPackageInstalled($package)) {
            return false;
        }
        try {
            $bower = $this->config->getBowerFileContent();
        } catch (RuntimeException $e) { // no bower.json file, package is extraneous
            return true;
        }
        if (!isset($bower['dependencies'])) {
            return true;
        }
        // package is a direct dependencies
        if (isset($bower['dependencies'][$package->getName()])) {
            return false;
        }
        // look for dependencies of dependencies
        foreach ($bower['dependencies'] as $name => $version) {
            $dotBowerJson = $this->filesystem->read($this->config->getInstallDir() . '/' . $name . '/.bower.json');
            $depBower = json_decode($dotBowerJson, true);
            if (isset($depBower['dependencies'][$package->getName()])) {
                return false;
            }
            // look for dependencies of dependencies of dependencies
            if (isset($depBower['dependencies'])) {
                foreach ($depBower['dependencies'] as $name1 => $version1) {
                    $dotBowerJson = $this->filesystem->read($this->config->getInstallDir() . '/' . $name1 . '/.bower.json');
                    $depDepBower = json_decode($dotBowerJson, true);
                    if (isset($depDepBower['dependencies'][$package->getName()])) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * @param  array $params
     * @return array
     */
    protected function createAClearBowerFile(array $params)
    {
        $authors = ['Beelab <info@bee-lab.net>'];
        if (!empty($params['author'])) {
            $authors[] = $params['author'];
        }
        $structure = [
            'name'         => $params['name'],
            'authors'      => $authors,
            'private'      => true,
            'dependencies' => new \StdClass(),
        ];

        return $structure;
    }

    /**
     * @param  PackageInterface $package
     * @param  bool             $setInfo
     * @return string
     */
    protected function getPackageTag(PackageInterface $package, $setInfo = false)
    {
        $decode = $this->findPackage($package->getName());
        // open package repository
        $repoUrl = $decode['url'];
        $this->repository->setUrl($repoUrl)->setHttpClient($this->githubClient);
        $packageTag = $this->repository->findPackage($package->getRequiredVersion());
        if (is_null($packageTag)) {
            throw new RuntimeException(sprintf('Cannot find package %s version %s.', $package->getName(), $package->getRequiredVersion()));
        }
        $bowerJson = $this->repository->getBower($packageTag);
        $bower = json_decode($bowerJson, true);
        if (!is_array($bower)) {
            throw new RuntimeException(sprintf('Invalid bower.json found in package %s: %s.', $package->getName(), $bowerJson));
        }
        if ($setInfo) {
            $package->setInfo($bower);
        }

        return $packageTag;
    }

    /**
     * @param  string $name
     * @return array
     */
    protected function findPackage($name)
    {
        try {
            $response = $this->githubClient->getHttpClient()->get($this->config->getBasePackagesUrl() . urlencode($name));
        } catch (RuntimeException $e) {
            throw new RuntimeException(sprintf('Cannot fetch registry info for package %s from search registry (%s).', $name, $e->getMessage()));
        }
        $packageInfo = json_decode($response->getBody(true), true);
        if (!is_array($packageInfo) || empty($packageInfo['url'])) {
            throw new RuntimeException(sprintf('Registry info for package %s has malformed json or is missing "url".', $name));
        }

        return $packageInfo;
    }

    /**
     * @param PackageInterface $package
     */
    private function cachePackage(PackageInterface $package)
    {
        $this->output->writelnInfoPackage($package, 'download');

        // get release archive from repository
        $file = $this->repository->getRelease();

        $tmpFileName = $this->config->getCacheDir() . '/tmp/' . $package->getName();
        $this->filesystem->write($tmpFileName, $file);
    }

    /**
     * @param PackageInterface $package
     * @param bool             $isDependency
     */
    private function updateBowerFile(PackageInterface $package, $isDependency = false)
    {
        if ($this->config->isSaveToBowerJsonFile() && !$isDependency) {
            try {
                $this->config->updateBowerJsonFile($package);
            } catch (RuntimeException $e) {
                $this->output->writelnNoBowerJsonFile();
            }
        }
    }

    /**
     * Update only if needed is greater version
     *
     * @param  PackageInterface $package
     * @return bool
     */
    public function isNeedUpdate($package)
    {
        $packageBower = $this->config->getPackageBowerFileContent($package);
        $semver = new version($packageBower['version']);

        return !$semver->satisfies(new expression($package->getRequiredVersion()));
    }
}
