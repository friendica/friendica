<?php

namespace DDDBL;

/**
  * exception if the given parameter
  * has an unexpected data-type
  *
  **/
class UnexpectedParameterTypeException extends \Exception {

  /**
    * @param $strExpected - the expected datatype
    * @param $mixedValue  - the given parameter
    *
    * determines the datatype of the given parameter and
    * creates and stores the exception message
    *
    **/
  public function __construct($strExpected, $mixedValue) {

    parent::__construct("value of type $strExpected expected, but got: " . gettype($mixedValue));

  }

}