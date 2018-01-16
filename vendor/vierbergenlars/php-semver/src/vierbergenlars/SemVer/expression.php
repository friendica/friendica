<?php

namespace vierbergenlars\SemVer;
use vierbergenlars\SemVer\Internal\Range;
use vierbergenlars\SemVer\Internal\G;
use vierbergenlars\LibJs\JSArray;

class expression
{
    /**
     *
     * @var Range
     */
    private $range;

    /**
     * standardizes the comparator/range/whatever-string to chunks
     * @param string $versions
     */
    public function __construct($versions, $loose = false)
    {
        try {
            $this->range = new Range($versions, $loose);
        } catch(\Exception $e) {
            throw new SemVerException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Checks ifthis range is satisfied by the given version
     * @param  version $version
     * @return boolean
     */
    public function satisfiedBy(version $version)
    {
        return $this->range->test($version);
    }

    /**
     * Get the whole or object as a string
     * @return string
     */
    public function getString()
    {
        return (string)$this->range->toString();
    }

    /**
     * Get the object as an expression
     * @return string
     */
    public function __toString()
    {
        return $this->getString();
    }

    /**
     * Get the object as a range expression
     * @return string
     */
    public function validRange()
    {
        return $this->getString();
    }

    /**
     * Find the maximum satisfying version
     * @param  array|string                        $versions An array of version objects or version strings, one version string
     * @return \vierbergenlars\SemVer\version|null
     */
    public function maxSatisfying($versions, $loose = false)
    {
        if(!is_array($versions))
            $versions = array($versions);
        return new version((string)G::maxSatisfying(new JSArray($versions), $this->range, $loose));
    }
}
