<?php

/**
 * The default verbiage section for an application that was denied.
 * @author Andrew Minerd
 */
class CM_Verbiage_Sorry {
	public static function build(CM_ResponseBuilder $builder) {
		$builder->createVerbiageSection("<p>We're sorry but you do not qualify for a payday loan at this time.</p>");
	}
}