<?php
/**
 * Helper that enables access to private and protected methods and properties.
 */
class PrivProxy
{
    private $obj;

    public function __construct($obj)
    {
        $this->obj = $obj;
    }

    public function __call($method, $arguments)
    {
        $rm = new ReflectionMethod($this->obj, $method);
        $rm->setAccessible(true);
        return $rm->invokeArgs($this->obj, $arguments);
    }

    public static function __callStatic($method, $arguments)
    {
        $rm = new ReflectionMethod($this->obj, $method);
        $rm->setAccessible(true);
        return $rm->invokeArgs($this->obj, $arguments);
    }

    public function __set($var, $value)
    {
        $rp = new ReflectionProperty($this->obj, $var);
        $rp->setAccessible(true);
        $rp->setValue($this->obj, $value);
    }

    public function __get($var)
    {
        $rp = new ReflectionProperty($this->obj, $var);
        $rp->setAccessible(true);
        return $rp->getValue($this->obj);
    }
}
?>
