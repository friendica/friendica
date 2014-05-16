<?php

namespace DDDBL;

$objQueue = Singleton::getInstance('\DDDBL\Queue');

#############################
### db-connection handler ###
#############################

# get (or first establish) connection to database
# and store the DataObject of the connection in the Queue-State

$cloStoreDBConnection = function(\DDDBL\Queue $objQueue, array $arrParameter) {

  if(!isConnected())
    connect();
  
  $objQueue->getState()->update(array('DB' => getDBDataObject()));

};

$objQueue->addHandler(QUEUE_GET_DB_CONNECTION_POSITION, $cloStoreDBConnection);

###############################
### query-definition-loader ###
###############################

# get the DataObject of the query and store it in the queue

$cloGetQuery = function(\DDDBL\Queue $objQueue, array $arrParameter) {

  $objDataObjectPool = new DataObjectPool('Query-Definition');

  # get the first entry of the parameter-list; this is the query-alias
  $strAlias = array_shift($arrParameter);

  if(empty($strAlias) || !is_string($strAlias))
    throw new \Exception('no query-alias defined!');

  if(!$objDataObjectPool->exists($strAlias))
    throw new \Exception("given query alias is unknown: $strAlias");
  
  $objQueue->getState()->update(array('QUERY' => $objDataObjectPool->get($strAlias)));

};

$objQueue->addHandler(QUEUE_GET_QUERY_POSITION, $cloGetQuery);

#################################
### set BIND-DATA-TYPE option ###
#################################

# check if the query has a BIND-DATA-TYPE config.
# if not check if there is one given for the database-connection.
# if yes, store it as setting for the query, otherwise
# set false for this option

$cloSetBindDataTypeConfig = function(\DDDBL\Queue $objQueue, array $arrParameter) {

  $objDB    = $objQueue->getState()->get('DB');
  $objQuery = $objQueue->getState()->get('QUERY');

  # skip this step, if the query itselfs has its own
  if($objQuery->exists('BIND-DATA-TYPE')) {
    $objQuery->update(array('BIND-DATA-TYPE' => (bool) $objQuery->get('BIND-DATA-TYPE'))); #bugfix for php-bug #38409
    return;
  }

  # set type to false, if no config is available, otherwise use the given config
  if(!$objDB->exists('BIND-DATA-TYPE'))
    $objQuery->update(array('BIND-DATA-TYPE' => false));
  else
    $objQuery->update(array('BIND-DATA-TYPE' => (bool) $objDB->get('BIND-DATA-TYPE')));

};

$objQueue->addHandler(QUEUE_BIND_DATA_TYPE_POSITION, $cloSetBindDataTypeConfig);

#####################
### prepare query ###
#####################

# get the stored query and prepare() it for the given database-connection
# store the resulting PDOStatement

$cloPrepareQuery = function(\DDDBL\Queue $objQueue, array $arrParameter) {

  # if query is not prepared yet, do this now
  if(!$objQueue->getState()->get('QUERY')->exists('PDOStatement')) {
    $objPDO = $objQueue->getState()->get('DB')->get('PDO');
    $objPDO = $objPDO->prepare($objQueue->getState()->get('QUERY')->get('QUERY'));
    $objQueue->getState()->get('QUERY')->update(array('PDOStatement' => $objPDO));
  }

  # copy reference of prepared statement into queue for execution
  $objQueue->getState()->update(array('PDOStatement' => $objQueue->getState()->get('QUERY')->get('PDOStatement')));

};

$objQueue->addHandler(QUEUE_PREPARE_QUERY_POSITION, $cloPrepareQuery);

#########################
### execute the query ###
#########################

# handler, which maps the data-type of a variable to the PDO-constants

$cloMapDataType = function($mixedParameter) {

  $arrDataTypeMap = array('NULL'    => \PDO::PARAM_NULL,
                          'boolean' => \PDO::PARAM_BOOL,
                          'integer' => \PDO::PARAM_INT,
                          'string'  => \PDO::PARAM_STR);

  $strDataType = gettype($mixedParameter);

  if(!isset($arrDataTypeMap[$strDataType]))
    throw new \Exception ("could not bind parameters data type - type is not supported by PDO: $strDataType");

  return $arrDataTypeMap[$strDataType];

};

# bind the given parameter to the prepared statement,
# then set the fetch mode and execute the query

$cloQueryExcecute = function(\DDDBL\Queue $objQueue, array $arrParameter) use ($cloMapDataType) {

  $objPDO = $objQueue->getState()->get('PDOStatement');

  # remove the alias from the parameter list
  array_shift($arrParameter);

  $objQuery = $objQueue->getState()->get('QUERY');

  if(true === $objQuery->get('BIND-DATA-TYPE')) {

    foreach($arrParameter AS $intIndex => $mixedParameter)
      $objPDO->bindValue($intIndex + 1, $mixedParameter, $cloMapDataType($mixedParameter));
      
  } else {

    foreach($arrParameter AS $intIndex => $mixedParameter)
      $objPDO->bindValue($intIndex + 1, $mixedParameter);
    
  }
  
  $objPDO->setFetchMode(\PDO::FETCH_ASSOC);

  # execute the query. if execution fails, throw an exception
  if(!$objPDO->execute())
    throw new QueryException($objPDO, $objQuery->getAll());

};

$objQueue->addHandler(QUEUE_EXECUTE_QUERY_POSITION, $cloQueryExcecute);

###############################
### format the query result ###
###############################

# if a result-handler for the query is configured, call it

$cloFormatQueryResult = function(\DDDBL\Queue $objQueue, array $arrParameter) {

  $objQuery = $objQueue->getState()->get('QUERY');

  if(!$objQuery->exists('HANDLER'))
    return ;

  # get the handler and its config
  $strHandlerConfig = $objQuery->get('HANDLER');
  $arrHandlerConfig = preg_split('/\s+/', $strHandlerConfig);
  $strHandler       = array_shift($arrHandlerConfig);

  # remove handler-name from config
  $strHandlerConfig = trim(str_replace($strHandler, '', $strHandlerConfig));
  
  $objDataObjectPool = new DataObjectPool('Result-Handler');

  if(!$objDataObjectPool->exists($strHandler))
    throw new \Exception ("unknown result-handler: $strHandler");

  $objHandler = $objDataObjectPool->get($strHandler);
  $cloHandler = $objHandler->get('HANDLER');

  $cloHandler($objQueue, $strHandlerConfig);

};

$objQueue->addHandler(QUEUE_FORMAT_RESULT_POSITION, $cloFormatQueryResult);

####################
### close cursor ###
####################

# closing the cursor of the PDOStatement. this will free
# the result and enable the connection to execute the next query

$cloCloseCursor = function(\DDDBL\Queue $objQueue, array $arrParameter) {

  $objQueryResult = $objQueue->getState()->get('PDOStatement');
  $objQueryResult->closeCursor();

};

$objQueue->addHandler(QUEUE_CLOSE_CURSOR_POSITION, $cloCloseCursor);
