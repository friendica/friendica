<?php

/*
 * This file is part of Bowerphp.
 *
 * (c) Piotr Olaszewski <piotroo89 [%] gmail dot com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bowerphp\Util;

/**
 * PackageNameVersionExtractor
 */
class PackageNameVersionExtractor
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $version;

    /**
     * @param string $name
     * @param string $version
     */
    public function __construct($name, $version)
    {
        $this->name = $name;
        $this->version = $version;
    }

    /**
     * @param  string                      $endpoint
     * @return PackageNameVersionExtractor
     */
    public static function fromString($endpoint)
    {
        $map = explode('#', $endpoint);
        $name = isset($map[0]) ? $map[0] : $endpoint;
        $version = isset($map[1]) ? $map[1] : '*';

        // Convert user/package shorthand to GitHub url
        if (preg_match('/^([-_a-z0-9]+)\/([-_a-z0-9]+)$/i', $name)) {
            $name = 'https://github.com/' . $name . '.git';
        }

        return new self($name, $version);
    }
}
