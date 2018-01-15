<?php

/**
 * Copyright (c) 2013 Lars Vierbergen
 * MIT licensed
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace vierbergenlars\LibJs;

class RegExp extends JObject
{
    /**
     *
     * @var string
     */
    private $regex;

    /**
     *
     * @var string
     * @internal
     */
    public $_pregSource = null;

    /**
     *
     * @var boolean
     */
    public $global = false;

    /**
     *
     * @param string $regex
     * @param string $flags
     */
    public function __construct($regex, $flags = '')
    {
        $this->regex = '/'.$regex.'/'.$flags;
        $this->_pregSource = '/'.$regex.'/';
        if(strpos($flags, 's') !== false)
            $this->global = true;
    }

    /**
     *
     * @param string|JString $str
     */
    public function exec($str)
    {
        if($this->global) {
            if(!preg_match_all($this->_pregSource, (string)$str, $matches))
                return null;
        } else {
            if(!preg_match($this->_pregSource, (string)$str, $matches))
                return null;
        }
        return new JSArray($matches);
    }

    /**
     *
     * @param string|JString $str
     */
    public function test($str)
    {
        if($this->global) {
            return preg_match_all($this->_pregSource, (string)$str);
        } else {
            return preg_match($this->_pregSource, (string)$str);
        }
    }

    public function valueOf()
    {
        return $this->regex;
    }
}
