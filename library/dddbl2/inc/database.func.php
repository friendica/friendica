<?php

namespace DDDBL;

/**
  * @returns (PDO) - reference to PDO object
  * @returns (boolean) false, if there is no connection to the database
  *
  * if there is a connection to the database,
  * the PDO object is returned otherwise false
  *
  **/
function getDB() {

  if(!isConnected())
    return false;
  
  $objDB = getDBDataObject();
  
  return $objDB->get('PDO');

}

/**
  * @returns (boolean) true, if connection exists or is established
  * @returns (boolean) false, if connection could not be established
  *
  * if no connection to the database exists, establishe one.
  *
  **/
function connect() {

  if(isConnected())
    return true;

  $objDB = getDBDataObject();

  try {
    $objPDO = new \PDO($objDB->get('CONNECTION'),
                       $objDB->get('USER'),
                       $objDB->get('PASS'));
  } catch (\Exception $objException) {
    return false;
  }

  $objDB->update(array('PDO' => $objPDO));

  return true;

}

/**
  * disconnect from the database
  *
  **/
function disconnect() {

  $objDB = getDBDataObject();
  
  $objPDO = $objDB->get('PDO');
  $objPDO = null;
  
  $objDB->delete('PDO');

}

/**
  * check if a connection to the database is established
  *
  **/
function isConnected() {

  $objDB = getDBDataObject();
  
  if(!$objDB->exists('PDO'))
    return false;
  
  return true;

}

/**
  * @returns (boolean) true, if transaction started
  * @returns (boolean) false, if transaction could not be started
  * @returns (boolean) false, if no connection to database exists
  *
  * start a transaction
  *
  **/
function startTransaction() {

  return mapMethod('beginTransaction');

}

/**
  * @returns (boolean) true, if there is an active transaction
  * @returns (boolean) false, if there is no active transaction
  * @returns (boolean) false, if no connection to database exists
  *
  * check if there is an active transaction
  *
  **/
function inTransaction() {

  return mapMethod('inTransaction');

}

/**
  * @returns (boolean) true, if rollback was successfull
  * @returns (boolean) false, if rollback was not successfull
  * @returns (boolean) false, if no connection to database exists
  *
  * perform a rollback of the active transaction
  *
  **/
function rollback() {

  return mapMethod('rollback');

}

/**
  * @returns (boolean) true, if commit was successfull
  * @returns (boolean) false, if commit was not successfull
  * @returns (boolean) false, if no connection to database exists
  *
  * commit the active transaction
  *
  **/
function commit() {

  return mapMethod('commit');

}

/**
  * @returns (array) - list of error-information
  *
  * get information about an error
  *
  **/
function getErrorInfo() {

  return mapMethod('errorInfo');

}

/**
  * change the active database-connection. all db-functions
  * are performed at the new connection.
  *
  * ATTENTION: the old connection is *not* closed!
  *
  **/
function changeDB($strIdentifier) {

  $objDataObjectPool = new DataObjectPool('Database-Definition');
  
  $objNewDB = $objDataObjectPool->get($strIdentifier);
  
  $objDataObjectPool->delete('DEFAULT');
  $objDataObjectPool->add('DEFAULT', $objNewDB->getAll());

}


/**
  * @returns (DataObject) - reference to the DataObject of default connection
  *
  * returns the DataObject of the default connection.
  *
  **/
function getDBDataObject() {

  $objDataObjectPool = new DataObjectPool('Database-Definition');
  return $objDataObjectPool->get('DEFAULT');

}

/**
  * @throws UnexpectedParameterTypeException - if the given parameter is not a string
  *
  * @returns (boolean) false, if no connection is established
  *
  * check if a connection to the database is established. if so,
  * the given parameter is used, as method to call at the 
  * PDO object. the result of the call is returned
  *
  **/
function mapMethod($strMethod) {

  if(!is_string($strMethod))
    throw new UnexpectedParameterTypeException('string', $strMethod);

  if(!isConnected())
    return false;
    
  $objDB = getDBDataObject();
  
  $objPDO = $objDB->get('PDO');
  
  return $objPDO->$strMethod();

}