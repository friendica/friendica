<?php

namespace DDDBL;

require_once __DIR__ . '/inc/DataObjectPool.class.php';
require_once __DIR__ . '/inc/DataObject.class.php';
require_once __DIR__ . '/inc/Singleton.class.php';
require_once __DIR__ . '/inc/Queue.class.php';

require_once __DIR__ . '/inc/exceptions/UnexpectedParameterTypeException.class.php';
require_once __DIR__ . '/inc/exceptions/QueryException.class.php';

require_once __DIR__ . '/inc/database.func.php';

# position of handler, which gets the active database-connection into the queue
define('QUEUE_GET_DB_CONNECTION_POSITION', 10);
define('QUEUE_GET_QUERY_POSITION',         20);
define('QUEUE_BIND_DATA_TYPE_POSITION',    30);
define('QUEUE_PREPARE_QUERY_POSITION',     40);
define('QUEUE_EXECUTE_QUERY_POSITION',     50);
define('QUEUE_FORMAT_RESULT_POSITION',     60);
define('QUEUE_CLOSE_CURSOR_POSITION',      70);

###############################################
### set validator for "Database-Definition" ###
###############################################

$objDBDefinitionValidator = function ($arrValues) {

  foreach(array('CONNECTION', 'USER', 'PASS') AS $strDefinitionField)
    if(!isset($arrValues[$strDefinitionField]) || !is_string($arrValues[$strDefinitionField]))
      return false;

  if(isset($arrValues['PDO']) && !is_a($arrValues['PDO'], '\PDO'))
    return false;

  return true;

};

$objDataObjectPool = new DataObjectPool('Database-Definition');
$objDataObjectPool->setValidator($objDBDefinitionValidator);

############################################
### set validator for "Query-Definition" ###
############################################

$objQueryDefinitionValidator = function ($arrValues) {

  if(!isset($arrValues['QUERY']) || !is_string($arrValues['QUERY']))
    return false;

  if(isset($arrValues['HANDLER']) && !is_string($arrValues['HANDLER']))
    return false;

  return true;

};

$objDataObjectPool = new DataObjectPool('Query-Definition');
$objDataObjectPool->setValidator($objQueryDefinitionValidator);

##########################################
### set validator for "Result-Handler" ###
##########################################

$objResultHandlerValidator = function ($arrValues) {

  if(!isset($arrValues['HANDLER']) || !is_callable($arrValues['HANDLER']))
    return false;

  return true;
};

$objDataObjectPool = new DataObjectPool('Result-Handler');
$objDataObjectPool->setValidator($objResultHandlerValidator);

#########################################
### register queue and result handler ###
#########################################

require_once __DIR__ . '/handler/register_queue_handler.inc.php';
require_once __DIR__ . '/handler/register_result_handler.inc.php';
