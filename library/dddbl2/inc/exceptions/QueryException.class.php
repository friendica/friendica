<?php

namespace DDDBL;

/**
  *
  * create an exception with relevant information, if a query fails
  *
  **/

class QueryException extends \Exception {

  /**
    *
    * @param $objPDO             - the PDO object which caused the error when executed
    * @param $arrQueryDefinition - the complete query definition
    * 
    * create an error message which contains all relevant informations
    * and print them as exception
    *
    **/
    public function __construct(\PDOStatement $objPDO, $arrQueryDefinition) {
        
        $strMessage = self::createErrorMessage($objPDO, $arrQueryDefinition);
        
        parent::__construct($strMessage);
        
    }

  /**
    *
    * @param $objPDO             - the PDO object related with the error
    * @param $arrQueryDefinition - the complete query definition
    * 
    * @return (string) the complete exception message
    * 
    * build and return the exception message out of the given error info
    * and query definition
    *
    **/
    private function createErrorMessage($objPDO, $arrQueryDefinition) {
      
      $strMessage  = self::flattenQueryErrorInfo($objPDO);
      $strMessage .= self::flattenQueryDefiniton($arrQueryDefinition);
      
      return $strMessage;
      
    }

  /**
    *
    * @param $objPDO - PDO object to get error information from
    * 
    * @return (string) a flatten error info from the query object
    * 
    * build and return a flatten error-info 
    * from the driver specific error message
    *
    **/
    private function flattenQueryErrorInfo($objPDO) {
    
      $arrErrorInfo = $objPDO->errorInfo();
      
      $strMessage = '';
      
      if(!empty($arrErrorInfo) && !empty($arrErrorInfo[0]) && '00000' !== $arrErrorInfo[0])
        $strMessage = "\nError-Code: {$arrErrorInfo[0]}\nError-Message: {$arrErrorInfo[2]}\n";
      
      return $strMessage;
    
    }

  /**
    *
    * @param $arrQueryDefinition - the complete query definition
    * 
    * @return (string) a text version of the query definition
    * 
    * create an text, which contains all information 
    * of the query definition
    *
    **/
    private function flattenQueryDefiniton($arrQueryDefinition) {
      
      $strMessage = "\nQuery-Definiton:\n";
      
      foreach($arrQueryDefinition AS $strKeyword => $strContent)
        $strMessage .= "$strKeyword: $strContent\n";
      
      return $strMessage . "\n";
      
    }

}