<?php

namespace vierbergenlars\SemVer;

use vierbergenlars\SemVer\Internal\SemVer;
use vierbergenlars\SemVer\Internal\G;

class version
{
    /**
     *
     * @var SemVer
     */
    private $version;

    /**
     * Initializes the version object with a simple version
     * @param  string          $version A simple, single version string
     * @param  bool            $loose
     * @throws SemVerException
     */
    public function __construct($version, $loose = false)
    {
        try {
            $this->version = new SemVer($version, $loose);
        } catch(\RuntimeException $ex) {
            throw new SemVerException($ex->getMessage(), $version);
        }
    }

    /**
     * Get the full version
     * @return string
     */
    public function getVersion()
    {
        return (string) $this->version;
    }

    /**
     * Get the major version number
     * @return int
     */
    public function getMajor()
    {
        return (int) $this->version->major;
    }

    /**
     * Get the minor version number
     * @return int
     */
    public function getMinor()
    {
        return (int) $this->version->minor;
    }

    /**
     * Get the patch version number
     * @return int
     */
    public function getPatch()
    {
        return (int) $this->version->patch;
    }

    /**
     * Get the build number
     * @return array
     */
    public function getBuild()
    {
        return (array) $this->version->build->valueOf();
    }

    /**
     * Get the prerelease appended to the version
     * @return array
     */
    public function getPrerelease()
    {
        return (array) $this->version->prerelease->valueOf();
    }

    /**
     * Returns a valid version
     * @return string
     * @see self::getVersion()
     */
    public function valid()
    {
        return $this->getVersion();
    }

    /**
     * Increment the version number
     * @param  string                         $what One of 'major', 'minor', 'patch' or 'prerelease'
     * @return \vierbergenlars\SemVer\version
     * @throws LogicException                When an invalid increment value is given
     */
    public function inc($what)
    {
        $this->version->inc($what);
        return $this;
    }

    /**
     * Checks whether this version satisfies an expression
     * @param  expression $versions The expression to check against
     * @return bool
     */
    public function satisfies(expression $versions)
    {
        return $versions->satisfiedBy($this);
    }

    public function __toString()
    {
        return $this->getVersion();
    }

    /**
     * Compare two versions
     * @param  string                   $v1  The first version
     * @param  string                   $cmp The comparator, one of '==', '!=', '>', '>=', '<', '<=', '===', '!=='
     * @param  string                   $v2  The second version
     * @param  bool                     $loose
     * @return bool
     * @throws LogicException
     */
    public static function cmp($v1, $cmp, $v2, $loose = false)
    {
        if($v1 instanceof self)
            $v1 = $v1->getVersion();
        if($v2 instanceof self)
            $v2 = $v2->getVersion();
        try {
            return G::cmp($v1, $cmp, $v2, $loose);
        } catch(\LogicException $e) {
            throw new \UnexpectedValueException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Checks ifa given string is greater than another
     * @param  string|version $v1 The first version
     * @param  string|version $v2 The second version
     * @param  bool           $loose
     * @return boolean
     */
    public static function gt($v1, $v2, $loose = false)
    {
        if($v1 instanceof self)
            $v1 = $v1->getVersion();
        if($v2 instanceof self)
            $v2 = $v2->getVersion();
        return G::gt($v1, $v2, $loose);
    }

    /**
     * Checks ifa given string is greater than, or equal to another
     * @param  string|version $v1 The first version
     * @param  string|version $v2 The second version
     * @param  bool           $loose
     * @return boolean
     */
    public static function gte($v1, $v2, $loose = false)
    {
        if($v1 instanceof self)
            $v1 = $v1->getVersion();
        if($v2 instanceof self)
            $v2 = $v2->getVersion();
        return G::gte($v1, $v2, $loose);
    }

    /**
     * Checks ifa given string is less than another
     * @param  string|version $v1 The first version
     * @param  string|version $v2 The second version
     * @param  bool           $loose
     * @return boolean
     */
    public static function lt($v1, $v2, $loose = false)
    {
        if($v1 instanceof self)
            $v1 = $v1->getVersion();
        if($v2 instanceof self)
            $v2 = $v2->getVersion();
        return G::lt($v1, $v2, $loose);
    }

    /**
     * Checks ifa given string is less than, or equal to another
     * @param  string|version $v1 The first version
     * @param  string|version $v2 The second version
     * @param  bool           $loose
     * @return boolean
     */
    public static function lte($v1, $v2, $loose = false)
    {
        if($v1 instanceof self)
            $v1 = $v1->getVersion();
        if($v2 instanceof self)
            $v2 = $v2->getVersion();
        return G::lte($v1, $v2, $loose);
    }

    /**
     * Checks ifa given string is equal to another
     * @param  string|version $v1 The first version
     * @param  string|version $v2 The second version
     * @param  bool           $loose
     * @return boolean
     */
    public static function eq($v1, $v2, $loose = false)
    {
        if($v1 instanceof self)
            $v1 = $v1->getVersion();
        if($v2 instanceof self)
            $v2 = $v2->getVersion();
        return G::eq($v1, $v2, $loose);
    }

    /**
     * Checks ifa given string is not equal to another
     * @param  string|version $v1 The first version
     * @param  string|version $v2 The second version
     * @param  bool           $loose
     * @return boolean
     */
    public static function neq($v1, $v2, $loose = false)
    {
        if($v1 instanceof self)
            $v1 = $v1->getVersion();
        if($v2 instanceof self)
            $v2 = $v2->getVersion();
        return G::neq($v1, $v2, $loose);
    }

    /**
     * Compares two versions, can be used with usort()
     * @param  string|version $v1 The first version
     * @param  string|version $v2 The second version
     * @param  bool           $loose
     * @return int            0 when they are equal, -1 ifthe second version is smaller, 1 ifthe second version is greater
     */
    public static function compare($v1, $v2, $loose = false)
    {
        if($v1 instanceof self)
            $v1 = $v1->getVersion();
        if($v2 instanceof self)
            $v2 = $v2->getVersion();
        return G::compare($v1, $v2, $loose);
    }

    /**
     * Reverse compares two versions, can be used with usort()
     * @param  string|version $v1 The first version
     * @param  string|version $v2 The second version
     * @param  bool           $loose
     * @return int            0 when they are equal, 1 ifthe second version is smaller, -1 ifthe second version is greater
     */
    public static function rcompare($v1, $v2, $loose = false)
    {
        return self::compare($v2, $v1, $loose);
    }
}
