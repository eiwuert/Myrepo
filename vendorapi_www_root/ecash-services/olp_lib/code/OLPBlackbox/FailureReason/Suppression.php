<?php
/**
 * Transport object for sending suppression failure reasons back to OLP from Blackbox.
 *
 * @package OLPBlackbox
 * @subpackage FailureReasons
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_FailureReason_Suppression extends OLPBlackbox_FailureReason
{
	/**
	 * Constructor for suppression failures.
	 *
	 * @param string $name the name of the suppression list
	 * @param string $type the type of the suppression list
	 * @param string $field the field the suppression list was validated against
	 * @param mixed $submitted_value the value that was validated against
	 */
	public function __construct($name = NULL, $type = NULL, $field = NULL, $submitted_value = NULL)
	{
		$this->data['name'] = $name;
		$this->data['type'] = $type;
		$this->data['field'] = $field;
		$this->data['submitted_value'] = $submitted_value;
	}
	
	/**
	 * Describes this failure reason.
	 *
	 * @return string
	 */
	public function getDescription() 
	{
		// TODO: make this more descriptive, this is the bbv2 text
		return sprintf('React was denied, failed %s %s list.',
			$this->data['name'],
			$this->data['type']
		);
	}
}
?>
