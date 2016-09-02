<?php 

/**
 * Some sort of reason... Why we failed.. Blackbox.. In the VendorAPI
 * 
 * @author Stephan Soileau <stephan.soileau@sellingsource.com>
 */
class VendorAPI_Blackbox_FailureReason
{
	/**
	 * Construct.. With stuff
	 *
	 * @param string $short
	 * @param string $comment
	 */
	public function __construct($short = NULL, $comment = NULL)
	{
		$this->short($short);
		$this->comment($comment);	
	}
	
	/**
	 * Set the comment string? And return it.
	 *
	 * @param string $comment
	 * @return string
	 */
	public function comment($comment = NULL)
	{
		if (is_string($comment) && !empty($comment))
		{
			$this->comment = $comment;	
		}
		return $this->comment;
	}
	
	/**
	 * Set the short string? And return it
	 *
	 * @param string $short
	 * @return string
	 */
	public function short($short = NULL)
	{
		if (is_string($short) && !empty($short))
		{
			$this->short = $short;
		}
		return $this->short;
	}
	
	/**
	 * Render this dumb thing as some sort
	 * of string contraption.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->comment();
	}
}