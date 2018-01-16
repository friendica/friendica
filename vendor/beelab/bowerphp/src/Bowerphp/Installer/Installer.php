<?php

namespace Bowerphp\Installer;

use Bowerphp\Config\ConfigInterface;
use Bowerphp\Package\Package;
use Bowerphp\Package\PackageInterface;
use Bowerphp\Util\Filesystem;
use Bowerphp\Util\ZipArchive;
use RuntimeException;
use Symfony\Component\Finder\Finder;

/**
 * Package installation manager
 */
class Installer implements InstallerInterface
{
    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var ZipArchive
     */
    protected $zipArchive;

    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * Initializes library installer.
     *
     * @param Filesystem      $filesystem
     * @param ZipArchive      $zipArchive
     * @param ConfigInterface $config
     */
    public function __construct(Filesystem $filesystem, ZipArchive $zipArchive, ConfigInterface $config)
    {
        $this->filesystem = $filesystem;
        $this->zipArchive = $zipArchive;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function install(PackageInterface $package)
    {
        $tmpFileName = $this->config->getCacheDir() . '/tmp/' . $package->getName();
        if (true !== $this->zipArchive->open($tmpFileName)) {
            throw new RuntimeException(sprintf('Unable to open zip file %s.', $tmpFileName));
        }
        $dirName = trim($this->zipArchive->getNameIndex(0), '/');
        $info = $package->getInfo();
        $files = $this->filterZipFiles($this->zipArchive, isset($info['ignore']) ? $info['ignore'] : [], isset($info['main']) ? (array) $info['main'] : []);
        foreach ($files as $i => $file) {
            $stat = $this->zipArchive->statIndex($i);
            $fileName = $this->config->getInstallDir() . '/' . str_replace($dirName, $package->getName(), $file);
            if ('/' != substr($fileName, -1)) {
                $fileContent = $this->zipArchive->getStream($file);
                $this->filesystem->write($fileName, $fileContent);
                $this->filesystem->touch($fileName, $stat['mtime']);
            }
        }
        // adjust timestamp for directories
        foreach ($files as $i => $file) {
            $stat = $this->zipArchive->statIndex($i);
            $fileName = $this->config->getInstallDir() . '/' . str_replace($dirName, $package->getName(), $file);
            if (is_dir($fileName) && '/' == substr($fileName, -1)) {
                $this->filesystem->touch($fileName, $stat['mtime']);
            }
        }
        $this->zipArchive->close();

        // merge package info with bower.json contents
        $bowerJsonPath = $this->config->getInstallDir() . '/' . $package->getName() . '/bower.json';
        if ($this->filesystem->exists($bowerJsonPath)) {
            $bowerJson = $this->filesystem->read($bowerJsonPath);
            $bower = json_decode($bowerJson, true);
            $package->setInfo(array_merge($bower, $package->getInfo()));
        }

        // create .bower.json metadata file
        // XXX we still need to add some other info
        $dotBowerContent = array_merge($package->getInfo(), ['version' => $package->getVersion()]);
        $dotBowerJson = str_replace('\/', '/', json_encode($dotBowerContent, JSON_PRETTY_PRINT));
        $this->filesystem->write($this->config->getInstallDir() . '/' . $package->getName() . '/.bower.json', $dotBowerJson);
    }

    /**
     * {@inheritdoc}
     */
    public function update(PackageInterface $package)
    {
        $this->install($package);
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall(PackageInterface $package)
    {
        $this->removeDir($this->config->getInstallDir() . '/' . $package->getName());
    }

    /**
     * {@inheritdoc}
     */
    public function getInstalled(Finder $finder)
    {
        $packages = [];
        if (!$this->filesystem->exists($this->config->getInstallDir())) {
            return $packages;
        }

        $directories = $finder->directories()->in($this->config->getInstallDir());

        foreach ($directories as $packageDirectory) {
            if ($this->filesystem->exists($packageDirectory . '/.bower.json')) {
                $bowerJson = $this->filesystem->read($packageDirectory . '/.bower.json');
                $bower = json_decode($bowerJson, true);
                if (is_null($bower)) {
                    throw new RuntimeException(sprintf('Invalid content in .bower.json for package %s.', $packageDirectory));
                }
                $packages[] = new Package($bower['name'], null, $bower['version'], isset($bower['dependencies']) ? $bower['dependencies'] : null, $bower);
            }
        }

        return $packages;
    }

    /**
     * {@inheritdoc}
     */
    public function findDependentPackages(PackageInterface $package, Finder $finder)
    {
        $return = [];
        $packages = $this->getInstalled($finder);
        foreach ($packages as $installedPackage) {
            $requires = $installedPackage->getRequires();
            if (isset($requires[$package->getName()])) {
                $return[$requires[$package->getName()]] = $installedPackage;
            }
        }

        return $return;
    }

    /**
     * Filter archive files based on an "ignore" list.
     *
     * @param  ZipArchive $archive
     * @param  array      $ignore
     * @param  array      $force
     * @return array
     */
    protected function filterZipFiles(ZipArchive $archive, array $ignore = [], array $force = [])
    {
        $dirName = $archive->getNameIndex(0);
        $return = [];
        $numFiles = $archive->getNumFiles();
        for ($i = 0; $i < $numFiles; ++$i) {
            $stat = $archive->statIndex($i);
            $return[] = $stat['name'];
        }
        $that = $this;
        $filter = array_filter($return, function ($var) use ($ignore, $force, $dirName, $that) {
            return !$that->isIgnored($var, $ignore, $force, $dirName);
        });

        return array_values($filter);
    }

    /**
     * @param string $dir
     */
    protected function removeDir($dir)
    {
        $this->filesystem->remove($dir);
    }

    /**
     * Check if a file should be ignored
     *
     * @param  string $name    file's name
     * @param  array  $ignore  list of ignores
     * @param  array  $force   list of files to force (do not ignore)
     * @param  string $dirName dir's name (to be removed from file's name)
     * @return bool
     */
    public function isIgnored($name, array $ignore, array $force, $dirName)
    {
        $vName = substr($name, strlen($dirName));
        if (in_array($vName, $force, true)) {
            return false;
        }
        // first check if there is line that overrides other lines
        foreach ($ignore as $pattern) {
            if (0 !== strpos($pattern, '!')) {
                continue;
            }
            $pattern = ltrim($pattern, '!');
            // the ! negates the line, otherwise the syntax is the same
            if ($this->isIgnored($name, [$pattern], $force, $dirName)) {
                return false;
            }
        }
        foreach ($ignore as $pattern) {
            if (false !== strpos($pattern, '**')) {
                $pattern = str_replace('**', '*', $pattern);
                //$pattern = str_replace('*/*', '*', $pattern);
                if ('/' == substr($pattern, 0, 1)) {
                    $vName = '/' . $vName;
                }
                if ('.' == substr($vName, 0, 1)) {
                    $vName = '/' . $vName;
                }
                if (fnmatch($pattern, $vName, FNM_PATHNAME)) {
                    return true;
                } elseif ('*/*' === $pattern && fnmatch('*', $vName, FNM_PATHNAME)) {
                    // this a special case, where a double asterisk must match also files in the root dir
                    return true;
                }
            } elseif ('/' == substr($pattern, -1)) { // trailing slash
                if ('/' == substr($pattern, 0, 1)) {
                    $pattern = substr($pattern, 1); // remove possible starting slash
                }
                $escPattern = str_replace(['.', '*'], ['\.', '.*'], $pattern);
                if (preg_match('#^' . $escPattern . '#', $vName) > 0) {
                    return true;
                }
            } elseif (false === strpos($pattern, '/')) { // no slash
                $escPattern = str_replace(['.', '*'], ['\.', '.*'], $pattern);
                if (preg_match('#^' . $escPattern . '#', $vName) > 0) {
                    return true;
                }
            } elseif ('/' == substr($pattern, 0, 1)) {    // starting slash
                $escPattern = str_replace(['.', '*'], ['\.', '.*'], $pattern);
                if (preg_match('#^' . $escPattern . '#', '/' . $vName) > 0) {
                    return true;
                }
            } else {
                $escPattern = str_replace(['.', '*'], ['\.', '.*'], $pattern);
                if (preg_match('#^' . $escPattern . '#', $vName) > 0) {
                    return true;
                }
            }
        }

        return false;
    }
}
