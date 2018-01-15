<?php

namespace Bowerphp\Package;

use Bowerphp\Repository\RepositoryInterface;

/**
 * Package
 */
class Package implements PackageInterface
{
    protected $name;
    protected $repository;
    protected $requiredVersion;
    protected $version;
    protected $requires = [];
    protected $info = [];

    /**
     * All descendants' constructors should call this parent constructor
     *
     * @param string $name            The package's name
     * @param string $requiredVersion E.g. 1.*
     * @param string $version         E.g. 1.2.3
     * @param array  $requires        The package's dependencies
     * @param array  $info            Package info (e.g. info from bower.json)
     */
    public function __construct($name, $requiredVersion = null, $version = null, $requires = [], $info = [])
    {
        $this->name = $name;
        $this->requiredVersion = 'master' === $requiredVersion ? '*' : $requiredVersion;
        $this->version = $version;
        if (!empty($requires)) {
            $this->requires = $requires;
        }
        if (!empty($info)) {
            $this->info = $info;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * {@inheritdoc}
     */
    public function setVersion($version)
    {
        return $this->version = $version;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequiredVersion()
    {
        return $this->requiredVersion;
    }

    /**
     * {@inheritdoc}
     */
    public function setRequiredVersion($version)
    {
        return $this->requiredVersion = $version;
    }

    /**
     * {@inheritdoc}
     */
    public function setRepository(RepositoryInterface $repository)
    {
        if ($this->repository && $repository !== $this->repository) {
            throw new \LogicException('A package can only be added to one repository');
        }
        $this->repository = $repository;
    }

    /**
     * Returns package unique name, constructed from name, version and release type.
     *
     * @return string
     */
    public function getUniqueName()
    {
        return $this->getName() . '-' . $this->getVersion();
    }

    /**
     * {@inheritdoc}
     */
    public function setRequires(array $requires = null)
    {
        $this->requires = $requires;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequires()
    {
        // see if there is some inside $this->info (e.g. from bower.json)
        if (empty($this->requires) && isset($this->info['dependencies'])) {
            $this->requires = $this->info['dependencies'];
        }

        return $this->requires;
    }

    /**
     * {@inheritdoc}
     */
    public function setInfo(array $info)
    {
        $this->info = $info;
    }

    /**
     * {@inheritdoc}
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * Converts the package into a readable and unique string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getUniqueName();
    }
}
