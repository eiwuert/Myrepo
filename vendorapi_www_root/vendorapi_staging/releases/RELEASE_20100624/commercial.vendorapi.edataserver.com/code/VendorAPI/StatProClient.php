<?php
/**
 * Vendor API StatProClient
 *
 * StatPro Client wrapper for VendorAPI
 *
 * @author Raymond Lopez <raymond.lopez@sellingsource.com>
 */

class VendorAPI_StatProClient extends Stats_StatPro_Client_1
{

	/**
	 *
	 * @var Log_ILog_1
	 */
	protected $log;


	public function setSpaceKeyValue($key)
	{
		$this->space_key = $key;
	}

	public function setTrackKeyValue($key)
	{
		$this->track_key = $key;
	}

	public function getSpaceKeyValue()
	{
		return $this->space_key;
	}

	public function getTrackKeyValue()
	{
		return $this->track_key;
	}

	/**
	 * Sets the space key from the normal definition info
	 *
	 * @param int $page_id
	 * @param int $promo_id
	 * @param int $sub_code
	 * @return string
	 */
	public function setSpaceKeyFromCampaign($page_id, $promo_id, $sub_code)
	{
		return $this->space_key = $this->getSpaceKey(array(
			'page_id' => $page_id,
			'promo_id' => $promo_id,
			'promo_sub_code' => $sub_code,
		));
	}

	/**
	 * Records a statpro event
	 *
	 * @param string $stat_name
	 * @param string $track_key
	 * @param string $space_key
	 */
	public function hitStat($stat_name, $track_key = null, $space_key= null)
	{
		if (!$track_key) $track_key = $this->getTrackKeyValue();
		if (!$space_key) $space_key = $this->getSpaceKeyValue();

		try
		{
			$this->recordEvent($track_key, $space_key, $stat_name);
		}
		catch (Exception $e)
		{
			$this->log->Write($e->getMessage());
			throw $e;
		}
	}

	/**
	 *  Set Log
	 */
	public function setLog(Log_ILog_1 $log)
	{
		$this->log = $log;
	}



}
?>