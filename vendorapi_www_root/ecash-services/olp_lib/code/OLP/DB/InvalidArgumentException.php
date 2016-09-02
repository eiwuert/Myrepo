<?php

/**
 * Represents that a OLP_DB component was fatally misconfigured with a bad argument.
 * 
 * Ideally, this could extend from OLP_DB_Exception and InvalidArgumentException,
 * but unfortunately we have no multiple inheritance in PHP and the InvalidArgumentException
 * does not define a special interface.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package DB
 */
class OLP_DB_InvalidArgumentException extends OLP_DB_Exception
{
	// pass
}

?>