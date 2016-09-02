<?php
/**
 * Defines the OLPBlackbox_Enterprise_CLK_Factory_TargetCollection class.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */

/**
 * Factory for olp target collections specific to clk.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */

class OLPBlackbox_Enterprise_CLK_Factory_TargetCollection extends OLPBlackbox_Factory_TargetCollection
{
	/**
	 * Cached picker class.
	 *
	 * @var OLPBlackbox_IPicker
	 */
	protected $picker;
	
	/**
	 * Get the picker for the targetCollection
	 *
	 * @param Blackbox_Models_IReadableTarget $target_model
	 * @return OLPBlackbox_Factory_Picker
	 */
	protected function getPicker(Blackbox_Models_IReadableTarget $target_model)
	{
		if (!$this->picker instanceof OLPBlackbox_IPicker)
		{
			$this->picker = parent::getPicker($target_model);
		}
		
		return $this->picker;
	}
}
?>
