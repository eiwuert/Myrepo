<?php

/**
 * Base class for Blacbox_Models_View_* objects, NOT WRITABLE.
 *
 * Ideally, this class would extend a View from libolution, but no such class
 * exists at the moment. Therefore, I'll just make sure decendants of this class
 * cannot be used to write anything.
 * 
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package Blackbox
 * @subpackage Blackbox_Models 
 */
abstract class Blackbox_Models_View_Base extends Blackbox_Models_WriteableModel 
{
	/**
	 * Throw an exception because a write method was called on this read-only
	 * object.
	 * 
	 * There's a lot of exceptions we could throw here, most notably 
	 * BadMethodCallException or RuntimeException. However, since this will
	 * be an error that happens exclusively in blackbox runs, it makes sense that
	 * the caller would want to handle this  as a general Blackbox error.
	 *  
	 * @throws Blackbox_Exception
	 * @param string $method_name The name of the method which should not be
	 * called.
	 * @return void
	 */
	final protected function badMethod($method_name)
	{
		throw new Blackbox_Exception(
			"cannot call $method_name, object is read only!"
		);
	}
	
	/**
	 * INVALID METHOD, DO NOT CALL.
	 * @return void
	 */
	final public function save()
	{
		$this->badMethod(__METHOD__);
	}
	
	/**
	 * INVALID METHOD, DO NOT CALL.
	 * @return void
	 */
	final public function update()
	{
		$this->badMethod(__METHOD__);
	}
	
	/**
	 * INVALID METHOD, DO NOT CALL.
	 * @param bool $deleted IRRELEVANT, DO NOT CALL.
	 * @return void
	 */
	final public function setDeleted($deleted)
	{
		$this->badMethod(__METHOD__);
	}
	
	/**
	 * INVALID METHOD, DO NOT CALL.
	 * @param bool $state IRRELEVANT, DO NOT CALL.
	 * @return void
	 */
	final public function setReadOnly($state = FALSE)
	{
		$this->badMethod(__METHOD__);
	}
	
	/**
	 * INVALID METHOD, DO NOT CALL.
	 * @param int $mode IRRELEVANT, DO NOT CALL.
	 * @return void
	 */
	final public function setInsertMode($mode = self::INSERT_STANDARD)
	{
		$this->badMethod(__METHOD__);
	}
}

?>
