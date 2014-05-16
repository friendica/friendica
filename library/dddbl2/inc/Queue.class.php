<?php

namespace DDDBL;

/**
  * this class implements a queue of handler, which
  * are called in a specified order.
  *
  * this allows the combiniation of different steps,
  * like database-connection management, query execution
  * and result parsing in a simple list of actions.
  *
  * Queue::getClone() returns a clone of the queue,
  * which allows modifications of the queue by
  * the executed handler.
  * in this way different problems, like substituions,
  * test-cases, statistics and much more can be solved,
  * without destroying the configured order for other queries.
  *
  **/
class Queue {

  /**
    * the sorted (!) queue of handler to execute
    *
    **/
  private $arrHandlerQueue = array();
  
  /**
    * @see \DDDBL\DataObject
    * 
    * an DataObject, which is used to store the states of the queue
    *
    **/
  private $objState   = null;

  /**
    * @param $intPosition - the position to store the handler at
    * @param $cloHandler  - the handler to store in the queue
    *
    * @throws UnexpectedParameterTypeException - if the first parameter is not an integer
    * @throws UnexpectedParameterTypeException - if the second parameter is not a callable
    * @throws \Exception                       - if there is already a handler stored under the given position
    *
    * store the given handler under the given position in the queue.
    * if the position is already in use an expection is thrown.
    *
    **/
  public function addHandler($intPosition, $cloHandler) {
  
    if(!is_int($intPosition))
      throw new UnexpectedParameterTypeException('integer', $intPosition);
    
    if(!is_callable($cloHandler))
      throw new UnexpectedParameterTypeException('callable', $cloHandler);
    
    if(!empty($this->arrHandlerQueue[$intPosition]))
      throw new \Exception("there is already a handler stored for position: $intPosition");
    
    $this->arrHandlerQueue[$intPosition] = $cloHandler;
    
    ksort($this->arrHandlerQueue);
  
  }
  
  /**
    * @param $intPosition - the position the handler for deletion is stored under
    *
    * @throws UnexpectedParameterTypeException - if the parameter is not an integer
    *
    * delete the handler stored under the given position
    *
    **/
  public function deleteHandler($intPosition) {
  
    if(!is_int($intPosition))
      throw new UnexpectedParameterTypeException('integer', $intPosition);
    
    if(array_key_exists($intPosition, $this->arrHandlerQueue))
      unset($this->arrHandlerQueue[$intPosition]);
  
  }
  
  /**
    * @returns (\DDDBL\Queue) - a clone of the queue-instance
    *
    * return a clone of the acutal queue
    *
    **/
  public function getClone() {
  
    return clone $this;
  
  }
  
  /**
    * @param $arrParameter - the parameter to use when executing the queue-handler
    *
    * @returns (mixed) the state of "result"
    *
    * execute all handler in the queue, in the given
    * order from low to high. after execution return the
    * state "result".
    *
    * handler which generates an output
    * are expected to store the result in this state
    *
    **/
  public function execute(array $arrParameter) {
  
    $this->getState()->add(array('result' => null));
    
    foreach($this->arrHandlerQueue AS $cloHandler)
      $cloHandler($this, $arrParameter);
    
    return $this->getState()->get('result');
  
  }
  
  /**
    * @returns (DataObject) - the DataObject which handles the states of the queue
    *
    * returns a reference to the DataObject, which
    * stores all states of the queue.
    *
    * if no object exists till now, a new one is created
    *
    **/
  public function getState() {
  
    if(!is_object($this->objState))
      $this->objState = new DataObject();
  
    return $this->objState;
  
  }
  
}