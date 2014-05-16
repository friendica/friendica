<?php

namespace DDDBL;

$objDataObjectPool = new DataObjectPool('Result-Handler');

#################################
### handler for: SINGLE_VALUE ###
#################################

$cloSingleValueHandler = function(\DDDBL\Queue $objQueue) {

  $arrResult = $objQueue->getState()->get('PDOStatement')->fetch();
  $objQueue->getState()->update(array('result' => (empty($arrResult)) ? null : reset($arrResult)));

};

$objDataObjectPool->add('SINGLE_VALUE', array('HANDLER' => $cloSingleValueHandler));

###########################
### handler for: SINGLE ###
###########################

$cloSingleHandler = function(\DDDBL\Queue $objQueue) {

  $arrResult = $objQueue->getState()->get('PDOStatement')->fetch();
  $objQueue->getState()->update(array('result' => (empty($arrResult)) ? null : $arrResult));

};

$objDataObjectPool->add('SINGLE', array('HANDLER' => $cloSingleHandler));

##########################
### handler for: MULTI ###
##########################

$cloMultiHandler = function(\DDDBL\Queue $objQueue) {

  $arrResult = $objQueue->getState()->get('PDOStatement')->fetchAll();
  $objQueue->getState()->update(array('result' => (empty($arrResult)) ? array() : $arrResult));

};

$objDataObjectPool->add('MULTI', array('HANDLER' => $cloMultiHandler));

#########################
### handler for: LIST ###
#########################

$cloListHandler = function(\DDDBL\Queue $objQueue) {

  $objResultCursor = $objQueue->getState()->get('PDOStatement');

  $arrResult = array();
  
  while($arrRow = $objResultCursor->fetch())
    array_push($arrResult, current($arrRow));

  $objQueue->getState()->update(array('result' => $arrResult));

};

$objDataObjectPool->add('LIST', array('HANDLER' => $cloListHandler));

#############################
### handler for: GROUP_BY ###
#############################

$cloGroupedByHandler = function(\DDDBL\Queue $objQueue, $strGroupColumn) {

  $objResultCursor = $objQueue->getState()->get('PDOStatement');

  $arrResult = array();
  
  while($arrRow = $objResultCursor->fetch()) {
    
    if(!isset($arrRow[$strGroupColumn]))
      throw new \Exception ("could not group result by non-existing column: $strGroupColumn");
    
    $arrResult[$arrRow[$strGroupColumn]][] = $arrRow;

  }

  $objQueue->getState()->update(array('result' => $arrResult));

};

$objDataObjectPool->add('GROUP_BY', array('HANDLER' => $cloGroupedByHandler));

#############################
### handler for: NOT_NULL ###
#############################

$cloNotNullHandler = function(\DDDBL\Queue $objQueue) {

  $arrResult = $objQueue->getState()->get('PDOStatement')->fetch();

  $objQueue->getState()->update(array('result' => (empty($arrResult)) ? false : true));

};

$objDataObjectPool->add('NOT_NULL', array('HANDLER' => $cloNotNullHandler));
