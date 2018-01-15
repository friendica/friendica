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

class JSArray extends JObject implements \ArrayAccess, \Iterator
{
    /**
     *
     * @var array
     */
    private $array;

    /**
     *
     * @var int
     * @public
     */
    private $length = null;

    /**
     *
     * @param array $arr
     */
    public function __construct(array $arr = array())
    {
        $this->array = $arr;
    }

    /**
     *
     * @param \Closure $func
     * @return JSArray
     */
    public function filter(\Closure $func)
    {
        $finalArray = new JSArray();
        foreach($this->array as $key => $value) {
            $key = $this->_convert($key);
            $value = $this->_convert($value);
            $ret = $func($value, $key, $this);
            if($ret)
                $finalArray->push($value);
        }
        return $finalArray;
    }

    public function JSforEach(\Closure $func)
    {
        $a = $this->array;
        foreach($this->array as $key=>$val) {
            $func($this->_convert($val), $this->_convert($key), $this);
        }
    }

    /**
     *
     * @param JString $c
     * @return JString
     */
    public function join($c)
    {
        return new JString(implode($c, $this->array));
    }

    /**
     *
     * @return JSArray
     */
    public function reverse()
    {
        return new self(array_reverse($this->array));
    }

    /**
     *
     * @param \Closure $compareFunction
     * @return JSArray
     */
    public function sort(\Closure $compareFunction = null)
    {
        $a = $this->array;
        if($compareFunction) {
            usort($a, $compareFunction);
        } else {
            sort($a);
        }

        return new self($a);
    }

    /**
     *
     * @param \Closure $func
     * @return JSArray
     */
    public function map(\Closure $func)
    {
        $that = $this;
        $wrap = function($str) use($func, $that) {
            $str = $that->_convert($str);
            return $func($str);
        };
        return new self(array_map($wrap, $this->array));
    }

    /**
     * @return int
     */
    public function push()
    {
        foreach(func_get_args() as $arg)
        {
            $this->array[] = $arg;
        }
        return $this->length = count($this->array);
    }

    public function __get($name)
    {
        switch($name) {
            case 'length':
                if($this->length !== null)
                    return $this->length;
                return $this->length = count($this->array);
        }
    }


    public function current()
    {
        return $this->_convert(current($this->array));
    }

    public function key()
    {
        return $this->_convert(key($this->array));
    }

    public function next()
    {
        return $this->_convert(next($this->array));
    }

    public function offsetExists($offset)
    {
        return isset($this->array[$offset]);
    }

    public function offsetGet($offset)
    {
        if(!$this->offsetExists($offset))
            return null;
        return $this->_convert($this->array[$offset]);
    }

    public function offsetSet($offset, $value)
    {
        $this->array[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->array[$offset]);
    }

    public function rewind()
    {
        return reset($this->array);
    }

    public function valid()
    {
        return each($this->array)!== false;
    }

    public function valueOf()
    {
        return $this->array;
    }
}
