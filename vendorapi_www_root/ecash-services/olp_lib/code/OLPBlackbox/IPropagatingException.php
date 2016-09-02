<?php
/**
 * Propagating exceptions will not be caught by error handlers as it is understood
 * that they should propagate back up the Blackbox tree
 *
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
interface OLPBlackbox_IPropagatingException
{
	// Intentionally blank
}
?>