<?php

/**
 * OLPBlackbox_Enterprise_Agean_Triggers class file.
 * 
 * @package OLPBlackbox
 * @author  Rob Voss <rob.voss@sellingsource.com>
 * 
 * @desc Agean Specific triggers can go in here.
 * 
 */
class OLPBlackbox_Enterprise_Agean_Triggers
{
	/**
	 * Email values
	 *
	 * @var array
	 */
	protected static $emails = array(
		1 => 1,
		2 => 2,
		3 => 3,
		4 => 4,
		5 => 5,
	);
	
	/**
	 * Triggers
	 *
	 * @var array
	 */
	protected static $triggers = array(

		2 => array(
			'name' => 'HOME_PHONE_INVALID',
			'description' => 'Home phone number is invalid (may be a work phone).',
			'email' => 3
		),
		
		3 => array(
			'name' => 'WORK_PHONE_UNLISTED',
			'description' => 'Work phone type fail.'
		),

		5 => array(
			'name' => 'SSN_AFTER_2ND_BDAY',
			'description' => 'SSN issued after second birthday.',
			'email' => 1
		),

		6 => array(
			'name' => 'SSN_AFTER_18TH_BDAY',
			'description' => 'SSN issued after 18th birthday.',
			'email' => 1
		),

		8 => array(
			'name' => 'NON_PPS',
			'description' => 'Bank account is a credit union or savings account.'
		),
		
		9 => array(
			'name' => 'DPB_OPEN_LOANS',
			'description' => 'Number of open loans between 1 and 2.'
		),
		
		10 => array(
			'name' => 'DPB_RECENT_INQUIRIES',
			'description' => 'Number of inquiries in last 60 days between 1 and 19.'
		),
		
		11 => array(
			'name' => 'DPB_OLD_CHARGEOFFS',
			'description' => 'Number of chargeoffs older than 180 days > 0',
		),
		
		13 => array(
			'name' => 'RUN_VERITRAC',
			'description' => 'Lacking information needed for underwriting process.'
		),
	
		14 => array(
			'name' => 'NON_JOB_INCOME',
			'description' => 'Income type not from job.',
			'email' => 5
		),

		15 => array(
			'name' => 'DPB_LAST_NAME_MISMATCH',
			'description' => 'Mismatch on last name from DPB.',
			'email' => 1
		),
		
		17 => array(
			'name' => 'TT_OPEN_LOAN',
			'description' => 'Customer has open loans.',
		),
		
		18 => array(
			'name' => 'TT_RECENT_INQUIRIES',
			'description' => 'Customer has inquiries 90 or less.',
		),
		
		19 => array(
			'name' => 'TT_CHARGEOFFS',
			'description' => 'Customer has charge-offs 90 or younger.',
		),
		
		20 => array(
			'name' => 'DPB_RECENT_INQUIRY',
			'description' => 'Number of inquiries in last 60 days <= 1'
		),
		
		21 => array(//Agean CRA/DPB-(Added)[MJ]
			'name' => 'DPB_NO_UNDERWRITING_DATA',
			'description' => 'Data missing from electronic underwriting'
		),

		/*1 => array(// Unused
			'name' => 'SSN_NAME_MISMATCH',
			'email' => 1
		),

		3 => array(// Unused
			'name' => 'WORK_PHONE_UNLISTED',
			'email' => 3
		),

		4 => array(// Unused
			'name' => 'MAIL_ALL_INFO',
			'email' => 4
		),
		
		
		7 => array(// Unused
			'name' => 'BANK_ACCT_FRAUD',
			'email' => 2
		),*/
	);
	
	/**
	 * Logs the specific trigger in the event log.
	 *
	 * @param string $blackbox_mode Blackbox Mode
	 * @param string $trigger trigger 
	 * @return void
	 */
	public static function logTrigger($blackbox_mode, $trigger)
	{
		static $mail_sent;
		
		$trigger = intval($trigger);
		if (!empty($trigger) && !empty(self::$triggers[$trigger]))
		{
			$events = array('AGEAN_TRIGGER_' . $trigger);
			if (!empty(self::$triggers[$trigger]['email']))
			{
				$email_trigger = 'AGEAN_TRIGGER_EMAIL_' . self::$triggers[$trigger]['email'];
				
				if (empty($mail_sent[$email_trigger]))
				{
					$events[] = $email_trigger;
					$mail_sent[$email_trigger] = TRUE;
				}
			}

			foreach ($events as $event)
			{
				// Something here needs to be fixed to hit the Rule hitEvent() function 
				//OLPBlackbox_Rule_Agean_DataX::hitEvent($event, 'VERIFY', NULL, NULL, $blackbox_mode);
			}
		}
	}
	
	/**
	 * Get loan action for the trigger.
	 *
	 * @param string $trigger The trigger
	 * @return unknown
	 */
	public static function getLoanAction($trigger)
	{
		$loan_action = NULL;
		
		$trigger = intval($trigger);
		if (!empty($trigger) && !empty(self::$triggers[$trigger]))
		{
			$loan_action = self::$triggers[$trigger]['name'];
			
		}
		
		return $loan_action;
	}
	
	/**
	 * Gets the email for the trigger.
	 *
	 * @param string $trigger The trigger
	 * @return string
	 */
	public static function getEmail($trigger)
	{
		$email = NULL;
		
		$trigger = intval($trigger);
		if (!empty($trigger) && !empty(self::$emails[$trigger]))
		{
			$email = self::$emails[$trigger];
		}
		
		return $email;
	}
	
	/**
	 * Gets the description of the trigger.
	 *
	 * @param string $trigger The trigger
	 * @return string
	 */
	public static function getDescription($trigger)
	{
		$desc = NULL;
		
		$trigger = intval($trigger);
		if (!empty($trigger) && !empty(self::$triggers[$trigger]))
		{
			$desc = self::$triggers[$trigger]['description'];
			
		}
		
		return $desc;
	}
}
?>