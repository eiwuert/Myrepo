<?php

/**
 * New class for parallel testing the new Blackbox
 * 
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 *
 */
class Blackbox_Adapter_NewParallel extends Blackbox_Adapter_New
{
	/**
	 * Overloads setupConfig to assign a different event log class.
	 *
	 * @return void
	 */
	protected function setupConfig()
	{
		parent::setupConfig();
		
		unset($this->config->event_log);
		$this->config->event_log = new EventLogTemp(
			$this->config->olp_db,
			$this->config->olp_db->db_info['db'],
			$this->config_data->application_id,
			'event_log_new_bbx'
		);
	}
	
	/**
	 * Overloaded function so it doesn't do anything!
	 *
	 * @param OLPBlackbox_Winner $winner
	 * @return void
	 */
	protected function updateSession(OLPBlackbox_Winner $winner)
	{
		// DON'T DO ANYTHING, THIS IS DANGEROUS!
	}
	
	/**
	 * Overloaded preConfigure function, so it sets us not to run DataX.
	 *
	 * @return void
	 */
	protected function preConfigure()
	{
		parent::preConfigure();
		
		$this->debug->setFlag(OLPBlackbox_DebugConf::DATAX_IDV, FALSE);
		$this->debug->setFlag(OLPBlackbox_DebugConf::DATAX_PERF, FALSE);
	}
}
?>