<?php

/** A wrapper class around lib/statpro_client.php
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class OLPStats
{
	const MODE_LIVE = 'live';
	const MODE_TEST = 'test';
	
	protected $track_key; /**< @var string */
	protected $space_key; /**< @var string */
	protected $global_key; /**< @var string */
	
	protected $mode; /**< @var string */
	protected $statpro; /**< @var StatPro_Client */
	
	protected $statpro_key; /**< @var string */
	protected $statpro_user; /**< @var string */
	protected $statpro_pass; /**< @var string */
	
	/** Setup mode for statpro.
	 *
	 * @param string $mode LIVE, RC, or LOCAL
	 */
	public function __construct($mode)
	{
		$this->setMode($mode);
	}
	
	/** Returns the underlying instance of StatPro_Client.
	 *
	 * @return StatPro_Client
	 */
	public function getStatPro()
	{
		if (!$this->statpro)
		{
			$this->statpro = new StatPro_Client(
				'/opt/statpro/var/' . $this->statpro_key,
				NULL,
				$this->statpro_user,
				$this->statpro_pass
			);
		}
		
		return $this->statpro;
	}
	
	/** Wrapper around statpro client's getSpaceKey.
	 *
	 * @param array $space_definition
	 * @return string
	 */
	public function getSpaceKey(array $space_definition)
	{
		// Because getting the spacekey overwrites the "normal" one
		$old_space_key = $this->getStatPro()->Space_Key();
		$new_space_key = $this->getStatPro()->cli->getSpaceKey($this->statpro_user, $this->statpro_pass, $space_definition);
		$this->getStatPro()->Space_Key($old_space_key);
		
		return $new_space_key;
	}
	
	/** Return the track key.
	 *
	 * @return string
	 */
	public function getTrack()
	{
		return $this->track_key;
	}
	
	/** Sets the track key.
	 *
	 * @param string $key The track key.
	 * @return string
	 */
	public function setTrack($key)
	{
		$this->track_key = $key;
		
		return $this->track_key;
	}
	
	/** Returns the space key.
	 *
	 * @return string
	 */
	public function getSpace()
	{
		return $this->space_key;
	}
	
	/** Sets the space key.
	 *
	 * @param string $key The space key.
	 * @return string
	 */
	public function setSpace($key)
	{
		$this->space_key = $key;
		
		return $this->space_key;
	}
	
	/** Returns the global key.
	 *
	 * @return string
	 */
	public function getGlobal()
	{
		return $this->global_key;
	}
	
	/** Sets the current global key.
	 *
	 * @param string $key The global key.
	 * @return string
	 */
	public function setGlobal($key)
	{
		$this->global_key = $key;
		
		return $this->global_key;
	}
	
	/** Sets the current authentication information based on property ID.
	 *
	 * @param int $property_id Which username/password to use.
	 * @return void
	 */
	public function setProperty($property_id)
	{
		switch ($property_id)
		{
			case 9278:
				$this->statpro_user = 'equityone';
				$this->statpro_pass = '3337b7d5b3321b075c8582540';
				break;
			
			case 37676:
				$this->statpro_user = 'emv';
				$this->statpro_pass = 'a51a5c87c5f2c030de8dee2da';
				break;
			
			case 28400:
				$this->statpro_user = 'leadgen';
				$this->statpro_pass = '04b650f6350a863089a015164';
				break;
			
			case 4967:
				$this->statpro_user = 'ge';
				$this->statpro_pass = '3818ca3aab5960549fb32d4c5';
				break;
			
			case 35459:
				$this->statpro_user = 'pwsites';
				$this->statpro_pass = 'bfa657d3633';
				break;
			
			case 1571:
			case 44024:
				$this->statpro_user = 'cubis';
				$this->statpro_pass = 'FtT7CYMFMyrC0';
				break;
			
			case 48204:
			case 48206:
				$this->statpro_user = 'imp';
				$this->statpro_pass = 'h0l3iny0urp4nts';
				break;
			
			case 57458:
				$this->statpro_user = 'ocp';
				$this->statpro_pass = 'raic9Cei';
				break;
			
			case 31631:
			case 3018:
			case 9751:
			case 1583:
			case 1581:
			case 1579:
			case 1720:
			case 17208:
			case 10985:
				$this->statpro_user = 'clk';
				$this->statpro_pass = 'dfbb7d578d6ca1c136304c845';
				break;
			
			case -889275714:
				$this->statpro_user = 'bbrule';
				$this->statpro_pass = 'greybox';
				break;
			
			default:
				$this->statpro_user = 'catch';
				$this->statpro_pass = 'bd27d44eb515d550d43150b9b';
				break;
		}
		
		// generally, keys are scp_USER_MODE (i.e., spc_clk_live)
		$this->statpro_key = 'spc_'.$this->statpro_user.'_'.$this->mode;
		
		// the key changed, so force recreation of the client
		$this->statpro = NULL;
	}
	
	/** Hits a stat using the current authentication, space, global, and track information.
	 *
	 * @param string $event The stat name to hit.
	 * @param int $date To pre/post date a stat.
	 * @param int $amount How many of them to hit.
	 * @return void
	 */
	public function hitStat($event, $date = NULL, $amount = NULL)
	{
		$event = strtolower($event);
		
		if (STAT_SYSTEM_2)
		{
			$statpro = $this->getStatPro();
			
			$statpro->track_key = $this->track_key;
			$statpro->space_key = $this->space_key;
			
			$statpro->Record_Event($event, $date);
		}
	}
	
	/** Sets the current statpro mode.
	 *
	 * @param string $mode Either LIVE, RC, or LOCAL.
	 * @return void
	 */
	protected function setMode($mode)
	{
		switch (strtoupper($mode))
		{
			case 'LIVE':
				$this->mode = self::MODE_LIVE;
				break;
			
			case 'RC':
			case 'LOCAL':
				$this->mode = self::MODE_TEST;
				break;
			
			default:
				throw new Exception("Invalid stat mode: {$mode}");
		}
	}
}

?>
