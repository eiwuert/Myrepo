<?php

/**
 * Interface for an object which, when placed inside a {@see Blackbox_StateData}
 * object will be combined when retrieved.
 * 
 * This was introduced for loan_actions in OLPBlackbox because collections AND
 * targets may have loan_action entries and they must all be able to be pulled
 * out of the winner object. The target's loan_actions will normally 'hide' the
 * collection's.
 * 
 * @see Blackbox_StateData
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package Blackbox
 */
interface Blackbox_StateData_ICombineKey
{
	/**
	 * The interface to combine this key with another key within StateData.
	 * @param OLPBlackbox_StateData_ICombineKey $other The other object to 
	 * combine with.
	 * @return OLPBlackbox_StateData_ICombineKey The resulting object, most
	 * likely a simple return of '$this'
	 */
	public function combine(Blackbox_StateData_ICombineKey $other = NULL);
}
?>
