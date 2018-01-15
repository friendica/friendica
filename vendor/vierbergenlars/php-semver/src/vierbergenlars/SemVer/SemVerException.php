<?php

namespace vierbergenlars\SemVer;

class SemVerException extends \RuntimeException
{
    protected $version = null;

    public function __construct($message, $version = null)
    {
        $this->version = $version;
        parent::__construct($message . ' [[' . $version . ']]');
    }

    public function getVersion()
    {
        return $this->version;
    }
}
