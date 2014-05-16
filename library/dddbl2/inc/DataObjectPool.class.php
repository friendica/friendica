<?php

namespace DDDBL;

/**
  * The DataObjectPool is a class to  manage
  * the DataObjects for different types.
  *
  * DataObjects are stored within groups. Every group
  * has a validatator, with is applyed to
  * every DataObject stored in the group.
  * If no validatator is set, no validation will
  * be done.
  *
  * A DataObject is referenced by an identifier,
  * which is uniqiue within a group.
  *
  * when creating a DataObjectPool instance,
  * the wanted group is set. All following
  * operations are done at this group.
  *
  **/
class DataObjectPool {

  /**
    * the actual group to operate on
    *
    **/
  private $strGroup = null;
  
  /**
    * list of validators for each group. structure:
    * array([group] => validator-callback,
    *       [group-n] => validator-callback-n, [..])
    *
    **/
  static private $arrValidatorList = array();
  
  /**
    * list of DataObjects. stored in the following structure:
    * array([group][uniqueue-identifier] => DataObject-reference, 
    *       [group-n][uniqueue-identifier-n] => DataObject-reference-n, [..])
    *
    **/
  static private $arrDataObjects = array(); 

  /**
    * @param $strGroup - the group of DataObjects to operate on
    *
    * @throws UnexpectedParameterTypeException - if given group is not a string
    *
    * create an instance of DataObjectPool and store the group
    * to operate on
    *
    **/
  public function __construct($strGroup) {
  
    if(!is_string($strGroup))
      throw new UnexpectedParameterTypeException('string', $strGroup);
    
    $this->strGroup = $strGroup;
    
    if(!array_key_exists($this->strGroup, self::$arrValidatorList))
      self::$arrValidatorList[$this->strGroup] = null;
    
    if(!array_key_exists($this->strGroup, self::$arrDataObjects))
      self::$arrDataObjects[$this->strGroup] = array();

  }
  
  /**
    * @param $cloValidator - the validator to set for the group
    *
    * @throws UnexpectedParameterTypeException - if given validator is not a callable
    *
    * set the validator for the active group. this validator
    * is given to each newly created DataObject.
    * if it is changed, the existing DataObjects are
    * *NOT* revalidated.
    *
    **/
  public function setValidator($cloValidator) {
  
    if(!is_callable($cloValidator))
      throw new UnexpectedParameterTypeException('string', $cloValidator);
    
    self::$arrValidatorList[$this->strGroup] = $cloValidator;
    
  }
  
  /**
    * @param $strIdentifier - the unique identifier of the DataObject
    * @param $arrData       - the data to store in the DataObject
    *
    * @see DataObject:add()
    *
    * @throws UnexpectedParameterTypeException - if identifier is not a string
    * @throws \Exception                       - if given identifier is not unique
    *
    * @returns (DataObject) - reference to the created DataObject-instance
    *
    * create a new DataObject and store it in the pool. The given
    * identifier is the key to retrieve the DataObject from the pool.
    * The given data are stored within the DataObject.
    *
    * After creation and storage of the DataObject, a reference
    * to the object is returned
    *
    **/
  public function add($strIdentifier, array $arrData) {
  
    if(!is_string($strIdentifier))
      throw new UnexpectedParameterTypeException('string', $strIdentifier);
    
    if($this->exists($strIdentifier))
      throw new \Exception ("identifier already in use: $strIdentifier");
    
    $objDataObject = new DataObject(self::$arrValidatorList[$this->strGroup], $arrData);
    
    self::$arrDataObjects[$this->strGroup][$strIdentifier] = $objDataObject;
    
    return $objDataObject;
  
  }
  
  /**
    * @param $strIdentifier - the identifier of the object to delete
    *
    * @throws UnexpectedParameterTypeException - if given identifier is not a string
    * @throws \Exception                       - if the given identifier is not known
    *
    * delete the stored DataObject from the DataObjectPool
    *
    **/
  public function delete($strIdentifier) {
    
    if(!is_string($strIdentifier))
      throw new UnexpectedParameterTypeException('string', $strIdentifier);
    
    if(!$this->exists($strIdentifier))
      throw new \Exception ("DataObject not found, identifier unknown: $strIdentifier");
    
    unset(self::$arrDataObjects[$this->strGroup][$strIdentifier]);
  
  }
  
  /**
    * @param $strIdentifier - the identifier to check
    *
    * @throws UnexpectedParameterTypeException - if given identifier is not a string
    *
    * @returns (boolean) true, if the identifier exists
    * @returns (boolean) false, if the identifier do not exists
    *
    **/
  public function exists($strIdentifier) {
  
    if(!is_string($strIdentifier))
      throw new UnexpectedParameterTypeException('string', $strIdentifier);
      
    if(!isset(self::$arrDataObjects[$this->strGroup][$strIdentifier]))
      return false;
    
    return true;
  
  }
  
  /**
    * @param $strIdentifier - the identifier of the DataObject to retrieve
    *
    * @throws UnexpectedParameterTypeException - if given identifier is not a string
    * @throws \Exception                       - if given identifier is unknown
    *
    * @returns (DataObject) - reference to the DataObject
    *
    * returns a reference to the DataObject stored under the identifer
    *
    **/
  public function get($strIdentifier) {
  
    if(!is_string($strIdentifier))
      throw new UnexpectedParameterTypeException('string', $strIdentifier);
    
    if(!$this->exists($strIdentifier))
      throw new \Exception ("DataObject not found, identifier unknown: $strIdentifier");
      
    return self::$arrDataObjects[$this->strGroup][$strIdentifier];
    
  }
  
  /**
    * @returns (array) - list of all DataObjects of the active group
    *
    * returns an array of all stored DataObjects of the active group
    * with the following structure:
    *
    * array([identifier] => DataObject-reference, 
    *       [identifier-n] => DataObject-reference-n, [..])
    *
    **/
  public function getAll() {
  
    return self::$arrDataObjects[$this->strGroup];
  
  }

}
