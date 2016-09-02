<?php

/**
 * Very simple class meant to track the severity of the BlackBox failures
 * to determine a final failure type, which will be used to tell OLP
 * whether or not it should reattempt to sell the lead.
 *
 * @author Brian Ronald <brian.ronald@fitech.com>
 */
class VendorAPI_Blackbox_FailType
{
	const SUCCESS			= 0;
	const FAIL_CAMPAIGN		= 1;
	const FAIL_COMPANY		= 2;
	const FAIL_ENTERPRISE	= 3;

	private $state;

	public function __construct()
	{
		$this->state = self::SUCCESS;
	}

	public function setFail($type)
	{
		if( $type == self::SUCCESS
		||  $type == self::FAIL_CAMPAIGN
		||  $type == self::FAIL_COMPANY
		||  $type == self::FAIL_ENTERPRISE )
		{
			if($type > $this->state)
			{
				$this->state = $type;
			}
		}
	}

	public function getState()
	{
		switch ($this->state)
		{
			case self::SUCCESS:
				return 'SUCCESS';
				break;
			case self::FAIL_CAMPAIGN:
				return 'FAIL_CAMPAIGN';
				break;
			case self::FAIL_COMPANY:
				return 'FAIL_COMPANY';
				break;
			case self::FAIL_ENTERPRISE:
				return 'FAIL_ENTERPRISE';
				break;
		}
	}

}