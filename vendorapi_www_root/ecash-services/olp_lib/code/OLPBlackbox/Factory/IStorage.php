<?php
/**
 * Factory reference list interface, used for lists of cachable objects.
 * 
 * @package OLPBlackbox
 * @subpackage Factory
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * 
 */
interface OLPBlackbox_Factory_IStorage
{
	public function add($object, $key);
	public function get($key);
}

?>
