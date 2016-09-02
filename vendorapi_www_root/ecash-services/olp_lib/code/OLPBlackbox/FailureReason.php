<?php

/**
 * Base class for FailureReasons.
 * 
 * FailureReasons and the accompanying {@see FailureReasonList} are structures
 * put in place to provide human readable representations of failures during
 * the blackbox process. Initially, this is for ecash_react failure reasons which
 * were handled quite clumsily in blackbox v.2
 * 
 * @package OLPBlackbox
 * @subpackage FailureReasons
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
abstract class OLPBlackbox_FailureReason
{
	/**
	 * The data values for each subclass, set up in child constructors.
	 *
	 * @var array
	 */
	protected $data = array();
	
	/**
	 * Returns a description of the failure reason.
	 *
	 * @return string
	 */
	abstract public function getDescription();
	
	/**
	 * Overloaded get function to return class data.
	 *
	 * @param string $name the name of the variable to get
	 * @return mixed
	 */
	public function __get($name)
	{
		if (isset($this->data[$name])) return $this->data[$name];
		
		return NULL;
	}

	/**
	 * Overloaded set function to set class data.
	 *
	 * @param string $name the name of the variable to set
	 * @param string $value the value of the variable to set
	 * @return void
	 */
	public function __set($name, $value)
	{
		if (isset($this->data[$name])) $this->data[$name] = $value;
		throw new InvalidArgumentException("$name doesn't exist and cannot be set");
	}
	
	/**
	 * Overloaded isset function.
	 *
	 * @param string $name the name of the variable to check
	 * @return bool
	 */
	public function __isset($name)
	{
		return isset($this->data[$name]);
	}
	
	/**
	 * If cast to string, provide a human readable description of this failure.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->getDescription();
	}
}

?>
