<?php

namespace DDDBL;

/**
  * simple implementation of generic singleton
  * for all classes, which allows additional instances
  * if needed
  *
  **/
  
class Singleton {

  /**
    * @param $strClass - the class we want an instance from
    *
    * @throws UnexpectedParameterTypeException - if given parameter is not a string
    * @throws \Exception                       - if given class do not exists
    *
    * @return (object) - an instance of the given classname
    *
    * get a reference to the instance of the given class.
    * if instance do not exists, create one. after creation
    * always return reference to this reference
    *
    **/
  static function getInstance($strClass) {
  
    if(!is_string($strClass))
      throw new UnexpectedParameterTypeException('string', $strClass);
    
    if(!class_exists($strClass))
      throw new \Exception ("class do not exists: $strClass");
    
    static $arrObjectList = array();
    
    if(!isset($arrObjectList[$strClass]))
      $arrObjectList[$strClass] = new $strClass();
    
    return $arrObjectList[$strClass];
  
  }

}