<?php

namespace DDDBL;

require_once __DIR__ . '/config.inc.php';

/**
  * @throws \Exception                       - if no parameter are given
  * @throws UnexpectedParameterTypeException - if first parameter is not a string
  *
  * @returns (mixed) - the result of the query-definition execution
  *
  * expect a list of parameter with at least one value. the
  * list is handled over to the queue, which will executed
  * with them
  *
  * in the end a result of the execution of the query-definition
  * through the stored handler is returned
  *
  **/
function get() {

  $arrParameter = func_get_args();
  
  if(empty($arrParameter))
    throw new \Exception ("no parameter given for execution");
  
  if(!is_string($arrParameter[0]))
    throw new UnexpectedParameterTypeException('string', $arrParameter[0]);

  # get instance of queue and work with a copy of it
  $objQueue = Singleton::getInstance('\DDDBL\Queue');
  $objQueue = $objQueue->getClone();
  
  return $objQueue->execute($arrParameter);

}

/**
  * @param $strFile - the file with the query definitions to store
  *
  * store all query-definitions from the given file
  *
  **/
function storeQueryFileContent($strFile) {

  storeDefinitionsFromFileInGroup($strFile, 'Query-Definition');

}

/**
  * @param $strDir   - the dir with query-definitions files
  * @param $strMatch - a rule files in the dir have to match
  *
  * iterate through all files in the given dir. if a file matches
  * the rule in $strMatch, the definitions in the file will be stored
  * as query-definitions. all files are match in default.
  *
  **/
function loadQueryDefinitionsInDir($strDir, $strMatch = '*') {

  walkDirForCallback($strDir, '\DDDBL\storeQueryFileContent', $strMatch);

}

/**
  * @param $strFile - the file with the database definitions to store
  *
  * store all database definition from the given file
  *
  **/
function storeDBFileContent($strFile) {

  $cloAdditionalHandler = function ($objDataObjectPool, $arrDefinition) {
    if(!empty($arrDefinition['DEFAULT'])  && true == (boolean) $arrDefinition['DEFAULT'])
      $objDataObjectPool->add('DEFAULT', $arrDefinition);
  };

  storeDefinitionsFromFileInGroup($strFile, 'Database-Definition', $cloAdditionalHandler);

}

/**
  * @param $strDir   - the dir with query-definitions files
  * @param $strMatch - a rule files in the dir have to match
  *
  * iterate through all files in the given dir. if a file matches
  * the rule in $strMatch, the definitions in the file will be stored
  * as database-definitions. all files are matched in default.
  *
  **/
function loadDBDefinitionsInDir($strDir, $strMatch = '*') {

  walkDirForCallback($strDir, '\DDDBL\loadDBDefinitionsInDir', $strMatch);

}

/**
  * @param $strPath          - the path to the dir to handle
  * @param $strCallback      - the callback to call when a matching file is found
  * @param $strFilenameMatch - the rule to filename has to match
  *
  * @throws UnexpectedParameterTypeException - if the given path or filematch-rule is not a string
  * @throws UnexpectedParameterTypeException - if the given callback is not a callable
  * @throws \Exception - if given path is not a directory
  * @throws \Exception - if the directory is not readable
  *
  * reads all files of the given directory (just directory, not recursive)
  * and checks, if the filename matches against the given rule. if
  * a match is found the given callback is called with the full
  * path to the file
  *
  **/
function walkDirForCallback($strPath, $strCallback, $strFilenameMatch) {

  if(!is_string($strPath))
    throw new UnexpectedParameterTypeException('string', $strPath);
  
  if(!is_callable($strCallback))
    throw new UnexpectedParameterTypeException('callable', $strCallback);
    
  if(!is_string($strFilenameMatch))
    throw new UnexpectedParameterTypeException('string', $strFilenameMatch);
  
  if(!is_dir($strPath))
    throw new \Exception ('given path is not an directory: ' . $strPath);
  
  $resDirHandle = opendir($strPath);
  
  if(!is_resource($resDirHandle))
    throw new \Exception ('could not read directory: ' . $strPath);
  
  while($strFile = readdir($resDirHandle))
    if(is_file($strPath.$strFile) && fnmatch($strFilenameMatch, $strFile)) 
      call_user_func_array($strCallback, array($strPath.$strFile));
  
  closedir($resDirHandle);
  
}

/**
  * @param $strFile  - the file with definitions
  * @param $strGroup - the group the definitions should be stored in
  *
  * @throws UnexpectedParameterTypeException - if the given file is not a string
  * @throws UnexpectedParameterTypeException - if the given optional handler is not a callable
  * @throws \Exception - if the given file is not a file or do not exists
  * @throws \Exception - if the given file is not readable
  *
  * generic function to store all definitions in a given file
  * in the specified group.
  *
  * if an additional handler is given, it is called AFTER the storage of the
  * definition. when called it will get the reference to the DataObjectPool and the 
  * found definition as parameter.
  *
  **/
function storeDefinitionsFromFileInGroup($strFile, $strGroup, $cloAdditionalHandler = null) {

  if(!is_string($strGroup))
    throw new UnexpectedParameterTypeException('string', $strGroup);
    
  if(!is_null($cloAdditionalHandler) && !is_callable($cloAdditionalHandler))
    throw new UnexpectedParameterTypeException('callable', $cloAdditionalHandler);

  if(!is_file($strFile) || !file_exists($strFile))
    throw new \Exception ("given file is not a file or doesn't exists: $strFile");

  if(!is_readable($strFile))
    throw new \Exception ("given file is not readable: $strFile");

  $arrDefinitions = parse_ini_file($strFile, true);
  
  $objDataObjectPool = new DataObjectPool($strGroup);
  
  foreach($arrDefinitions AS $strDefinitionAlias => $arrDefinition) {
    $objDataObjectPool->add($strDefinitionAlias, $arrDefinition);
    
    if(!is_null($cloAdditionalHandler))
      $cloAdditionalHandler($objDataObjectPool, $arrDefinition);

  }

}
