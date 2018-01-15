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

class JString extends JObject implements \ArrayAccess
{
    /**
     * Wrapped string
     * @var string
     */
    private $str;

    /**
     *
     * @param string $str
     */
    public function __construct($str)
    {
        $this->str = (string)$str;
    }

    /**
     *
     * @param int $pos
     * @return string
     */
    public function charAt($pos)
    {
        if(isset($this->str[$pos]))
            return $this->str[$pos];
        return '';
    }

    /**
     *
     * @param int $pos
     * @return int
     */
    public function charCodeAt($pos)
    {
        if(!isset($this->str[$pos]))
            return NAN;
        return (int)ord($this->charAt($pos));
    }

    /**
     *
     * @return JString
     */
    public function concat()
    {
        $concat = $this->str;
        $strings = func_get_args();
        foreach($strings as $str) {
            $concat.=$str;
        }
        return new self($concat);
    }

    /**
     *
     * @param string|RegExp $regex
     * @return JSArray
     */
    public function match($regex)
    {
        if(!$regex instanceof RegExp)
            $regex = new RegExp((string)$regex);
        $ret = $regex->exec($this);
        if($ret&&!$regex->global) {
            $ret->input = $this;
        }
        return $ret;
    }

    /**
     *
     * @param RegExp|string $regexOrSubstr
     * @param \Closure|string $newSubstrOrFunction
     * @return JString
     */
    public function replace($regexOrSubstr, $newSubstrOrFunction)
    {
        if($regexOrSubstr instanceof RegExp) {
            if($newSubstrOrFunction instanceof \Closure) {
                $wrap = function($matches)use($newSubstrOrFunction) {
                    $matches = array_map(function($s) {
                        return new JString($s);
                    }, $matches);
                    return call_user_func_array($newSubstrOrFunction, $matches);
                };
                return new self(preg_replace_callback(
                    $regexOrSubstr->_pregSource,
                    $wrap,
                    (string)$this
                ));
            } else {
                return new self(preg_replace(
                    $regexOrSubstr->_pregSource,
                    $newSubstrOrFunction,
                    (string)$this
                ));
            }
        } else {
            return new self(str_replace($regexOrSubstr, $newSubstrOrFunction, (string)$this));
        }
    }

    /**
     *
     * @param string|JString|RegExp $c
     * @return JSArray
     */
    public function split($c)
    {
        if($c instanceof RegExp) {
            $ret = preg_split($c->_pregSource, (string)$this);
        } else {
            $ret = explode((string)$c,(string)$this);
        }
        return new JSArray($ret);
    }

    /**
     *
     * @return JString
     */
    public function toLowerCase()
    {
        return new self(strtolower((string)$this));
    }

    /**
     *
     * @return JString
     */
    public function toString()
    {
        return $this;
    }

    /**
     *
     * @return JString
     */
    public function toUpperCase()
    {
        return new self(strtoupper((string)$this));
    }

    /**
     *
     * @return JString
     */
    public function trim()
    {
        return new self(trim((string)$this));
    }

    /**
     *
     * @return string
     */
    public function valueOf()
    {
        return (string)$this;
    }

    /**
     *
     * @return string
     */
    public function __toString()
    {
        return $this->str;
    }

    public function __get($name)
    {
        switch($name) {
            case 'length':
                return strlen($this->str);
        }
    }

    public function offsetExists($offset)
    {
        return isset($this->str[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->str[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->str[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        throw new \LogicException('Cannot unset a position in a string');
    }

}
