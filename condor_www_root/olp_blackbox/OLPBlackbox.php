<?php

/** Adds cleanup for pickWinner in Blackbox.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class OLPBlackbox extends Blackbox
{
	/** Call observers when pickWinner returns.
	 * 
	 * @param Blackbox_Data $data data to run Blackbox validation against
	 * @return Blackbox_IWinner|bool
	 */
	public function pickWinner(Blackbox_Data $data)
	{
		try
		{
			$winner = parent::pickWinner($data);
		}
		catch (Exception $e)
		{
			// We want to rethrow any exceptions that occurred, but still run cleanup.
			$this->cleanUp($e);
			throw $e;
		}
		
		$this->cleanUp();
		
		return $winner;
	}
	
	/** Clean up blackbox after we finish running.
	 *
	 * @param Exception $e An exception that occurred, if any.
	 * @return void
	 */
	protected function cleanUp(Exception $e = NULL)
	{
		$config = OLPBlackbox_Config::getInstance();
		
		Stats_StatPro::getInstance($config->mode, OLPBlackbox_Config::STATS_BBRULES)->flushBatch();
	}
}

?>
