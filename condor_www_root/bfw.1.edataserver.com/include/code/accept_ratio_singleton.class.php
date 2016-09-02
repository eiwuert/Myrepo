<?php
/**
 * Implements an interface to the Accept_Ratio_Singleton class API.
 *
 * @author Tym Feindel <timothy.feindel@sellingsource.com>
 */

require_once(BFW_CODE_DIR.'Memcache_Singleton.php');

/**
 * Accept_Ratio_Singleton Class
 *
 * @desc handles accept ratio monitoring, DB interaction for the the ill-named "BB Frequency Scoring"
 * 		GF[#3833] and GF[#5565]
 *
 * @author Tym Feindel <timothy.feindel@sellingsource.com>
 */
class Accept_Ratio_Singleton
{
	/**
	 * Instance of this class
	 *
	 * @var Accept_Ratio_Singleton
	 */
	static private $instance;
	
	/**
	 * Database connection
	 *
	 * @var MySQL_4
	 */
	protected $sql;

	/**
	 * Cached vendor scores list so that
	 * we don't need to repeatedly kill
	 * the database.
	 *
	 * @var array
	 */
	protected $vendor_scores;

	/**
	 *	main construct
	 *
	 *	@param object $sql the sql object
	 *	@return none
	 */
	private function __construct($sql)
	{
		$this->sql = Setup_DB::Get_Instance('blackbox', BFW_MODE);

	}

	/**
	 * Overrides the clone object. Private so that no one can clone this object.
	 *
	 */
	private function __clone()
	{
			
	}


	/**
	 * Returns an instance of the Accept_Ratio_Singleton class.
	 *
	 * @return object pointer to Accept_Ratio_Singleton instance
	 */
	static public function getInstance($sql)
	{
		if ( !isset(self::$instance) )
		{
			self::$instance = new Accept_Ratio_Singleton($sql);
		}
		return self::$instance;
	}

	/**
	 * DEPRECATED Retrieve the sum for a timeframe from the DB by email
	 *
	 * @param string $email the email of the applicant
	 * @param string $timestart, $timeend time slices
	 *
	 * @return string the count of rejects for that timeframe
	 */
	public function getRejectsByTime($email, $timestart, $timeend)
	{
		$tmp="";
		$query = "SELECT
				SUM(declined_sum) as sum_sums
			FROM
				vendor_decline_freq as v
			WHERE
				v.client_email = '" . strtoupper($email) . "' AND
				v.date_created BETWEEN '$timestart' AND '$timeend'";
		try {
			$result = $this->sql->Query($this->sql->db_info['db'],$query);
			$tmp = $this->sql->Fetch_Column($result,'sum_sums');
		}
		catch (Exception $e)
		{
			$tmp="0";
		}
		if (is_null($tmp)) $tmp="0";
		return $tmp;
	}

	/**
	 * Test the submitted min/max limits against the standing "freq_score"
	 * @param array $limits(hourly)decline_freq_min, decline_freq_max, (daily) min, max,
	 * (weekly) min,max values from webadmin2 settings as sequentially numbered array
	 * @param string $data the applicant email address
	 * @return boolean TRUE if frequency of declines falls within provided
	 * min/max values, otherwise FALSE.
	 */
	public function testLimits($limits, $email)
	{
		$flagused=FALSE; // set this flag if any of the frequency decline rules are set
		if(!is_array($limits)) return TRUE; //totally unset, let it pass
		foreach($limits as $oneparam)
		{
			if (isset($oneparam) && $oneparam!=0) $flagused=TRUE;
		}
			
		if(!$flagused) return TRUE;  // if there is no min/max just let it pass
		$current_scores=array();
		$standing_scores=$this->getPeriodicDeclines($email); //will hold the current freq scores
			
		for($tempx=0; $tempx<3; $tempx++)
		{
			// sets current_scores 0,1,2,3,4,5 to declines (1,1,2,2,3,3)
			$tempfreq=$standing_scores[$tempx];
			$current_scores[]=$tempfreq;
			$current_scores[]=$tempfreq;
		}
			
		foreach($limits as $onekey=>$oneval)
		{
			if ($oneval==0) continue; //short circuit on 0 values

			// added limit on parsed array GForge #5565 1st,2nd, 3rd Look % Control Mechanism
			if ($onekey > 5) return TRUE;

			if (!($onekey%2))
			{
				// even num (incl 0) must be min value
				if ($current_scores[$onekey] < $limits[$onekey]) return FALSE;
			}
			else
			{
				// odd num, max value
				if ($current_scores[$onekey] >= $limits[$onekey]) return FALSE;
			}
		}
		return TRUE;
	}


	/**
	 * Retrieve the sum for a predefined timeframe from the DB by email addy
	 * The pastperiod string can be numeric (keyed on 1,2,3, defaults to 1/1 hr) or
	 * explicit ("1 hour")
	 *
	 * @param string $email the email of the applicant
	 * @param string $pastperiod the depth of ongoing history to look through ala "7 days" or "1 hour"
	 * @return string the count of rejects for that timeframe
	 */
	public function getRejectsByHistory($email, $pastperiod="1 hour")
	{
		/*
		 * 1) Last 1 hour 2) Last 24 hrs 3) Last 7 days.
		 */
		if(strlen($pastperiod)==1)
		{
			$tmpval="1 hour";
			switch ($pastperiod)
			{
				case '1':
					$tmpval="1 hour";
					break;
				case '2':
					$tmpval="24 hours";
					break;
				case '3':
					$tmpval="7 days";
					break;
				default:
					$tmpval="1 hour";
					break;
			}
			$pastperiod=$tmpval;
		}

		$endtime=date('YmdHis');
		$begintime=date('YmdHis',strtotime(" -". $pastperiod));
		$tmp="0";
		$query = "SELECT
				SUM(declined_sum) as sum_sums
			FROM
				vendor_decline_freq as v
			WHERE
				v.client_email = '" . strtoupper($email) . "' AND
				v.date_created BETWEEN '$begintime' AND '$endtime'";		
		try {
			$result = $this->sql->Query($this->sql->db_info['db'],$query);
			$tmp = $this->sql->Fetch_Column($result,'sum_sums');

			// add the current score from memcache, to handle soap apps
			$tmp = $tmp + $this->getMemScore(strtoupper($email));
		}
		catch (Exception $e)
		{
			$tmp="0";
			return "0";
			// hit errlog
		}
		if (is_null($tmp)) $tmp="0";
		return $tmp;
	}

	/**
	 * Write the score to db **on accept only
	 * @param $email, $count, $prop_short, $app_id
	 */
	private function writeAccept($email, $prop_short, $app_id)
	{
		$nowtime=date('YmdHis');
		$email=strtoupper($email);
		$count = $this->getMemScore($email);
		$query = "INSERT INTO vendor_decline_freq
			(date_created,client_email,declined_sum,accept_property_short,
			application_id) VALUES ('$nowtime','$email', 
			'$count','$prop_short', '$app_id')";
		try
		{

			$result = $this->sql->Query($this->sql->db_info['db'],$query);
			$this->addTotalDecline($email); //kill the memcached stuff, we have it in DB now
		}
		catch (Exception $e)
		{
			//bad things

		}
	}

	/**
	 * Write the score to memcache
	 * @param string $email
	 */
	public function addPost($email)
	{
		$mem_object=Memcache_Singleton::Get_Instance();
		$standing_val=$mem_object->get("AR" . strtoupper($email));
		$rep_val=1;
		if($standing_val)
		{
			$rep_val=$standing_val+1;
		}
		$mem_object->set("AR" .strtoupper($email), $rep_val, 600, NULL);
	}

	/**
	 * Record the score **on accept only, broker mode
	 * @param $email, $prop_short, $app_id
	 */
	public function addAccept($email, $prop_short, $app_id)
	{
		$this->writeAccept(strtoupper($email), $prop_short, $app_id);
	}

	/**
	 * Retrieve a current frequency score by email
	 * @param string $email an email address
	 */
	public function getMemScore($email)
	{
		$mem_object=Memcache_Singleton::Get_Instance();
		$standing_val=$mem_object->get("AR" . strtoupper($email));
		if($standing_val)
		{
			return $standing_val;
		}
		return 0;
	}

	/**
	 * Zero the frequency score in memcache
	 * @param string $email
	 */
	public function addTotalDecline($email)
	{
		$mem_object=Memcache_Singleton::Get_Instance();
		$mem_object->set("AR" .strtoupper($email), 0, 600, NULL);
	}

	/**
	 * Determines the percentile (by vendor) of accepted leads by frequency score (1,2,3) over the past hour
	 * out of the total accepted leads per vendor over the past hour
	 *
	 * @param string $propertyShort the vendor property short, case insensitive
	 * @param optional boolean $altTime
	 * @return array the leads/last hour (LLH) total, LLH w./score=1, LLH w./score=2, LLH w./score=3,
	 * LLH %total for score=1, LLH %total for score=2, LLH %total for score=3
	 *
	 */
	public function getVendorScores($propertyShort, $altTime=FALSE)
	{
		// If we've already found the result, don't
		// hit the database again to find it.  This is an
		// acceptable risk.
		if (!empty($this->vendor_scores[$propertyShort]))
		{
			return $this->vendor_scores[$propertyShort];
		}
		
		$answer=array(0,0,0,0,0.0,0.0,0.0); // return array, see above "leads/last hour (LLH) total, LLH ...."
		$localResults=array();

		$propertyShort=strtoupper($propertyShort);

		$unixtime=time();
		if ($altTime !== FALSE)
		{
			$unixtime=$altTime;
		}

		$hourpast = date('YmdHis', strtotime('-1 hour', $unixtime));
		$sqltime=date('YmdHis', $unixtime);

		$query="SELECT
					count(*) as num_accepts,
					v.declined_sum 
				FROM vendor_decline_freq v
				WHERE v.date_created BETWEEN '$hourpast' and '$sqltime'
				AND v.accept_property_short = '$propertyShort' 
				GROUP BY v.declined_sum";

		try
		{
			$result = $this->sql->Query($this->sql->db_info['db'],$query);
			if ($result)
			{

			while($tmp = $this->sql->Fetch_Array_Row($result))
			{
				$localResults[]=$tmp;
			}

			// calculate the return array
			$grandTotal=0;
			foreach($localResults as $row)
			{
				if ($row['declined_sum']==1) $answer[1] = $row['num_accepts'];
				if ($row['declined_sum']==2) $answer[2] = $row['num_accepts'];
				if ($row['declined_sum']==3) $answer[3] = $row['num_accepts'];
				$grandTotal+=$row['num_accepts'];
			}

			// The total accepts by this vendor over the last hour:
			$answer[0] = $grandTotal;

			if ($grandTotal >0)
			{
					// These are the current look percentages for the vendor (1st/2nd/3rd)
					$answer[4] = round((100 * $answer[1]) / $grandTotal, 2);
					$answer[5] = round((100 * $answer[2]) / $grandTotal, 2);
					$answer[6] = round((100 * $answer[3]) / $grandTotal, 2);
			}
			}

			$this->vendor_scores[$propertyShort] = $answer;
		}
		catch (Exception $e)
		{
		}

		return $answer;
	}

	/**
	 * DEPRECATED
	 * Tests the frequency score *percentile goal* based on the percentile of first, second,
	 * third looks that this vendor will receive relative to how many leads they have accepted
	 * in the past 60 minutes
	 *
	 * @param array $limits
	 * @param string $pshort
	 */
	public function testVendorLimits($limits, $pshort, $email)
	{
		$flagused=FALSE; // set this flag if any of the frequency decline percentile rules are set
		if(!is_array($limits)) return TRUE; //totally unset, let it pass

		$vlimits=array_slice($limits, 6);

		foreach($vlimits as $oneparam)
		{
			if (isset($oneparam) && $oneparam!=0) $flagused=TRUE;
		}

		if(!$flagused) return TRUE;  // if there is no set percentile just let it pass

		//check for fs > 3
		$leadScore=$this->getMemScore($email);
		if ($leadScore > 3) return TRUE;
			
		$vpercents=$this->getVendorScores($pshort); //will hold the current freq scores

		// $vpercents elements 4,5,6 are the pcntiles for hourly fscore of 1,2,3 look, aka current look %

		$tempx=$leadScore; //NOTE: from memscore, temp score only, 0 for new hit

		if ($vpercents[4+$tempx] > $vlimits[$tempx] && $vlimits[$tempx]!=0) return FALSE;
					
		return TRUE;
	}

	/**
	 * Orders the array of vendors by their proximity to their % lead caps, descending
	 *
	 * @param unknown_type $limitsArr
	 * @param unknown_type $choices
	 * @param unknown_type $email
	 * @return unknown
	 */
	public function orderVendorsByFreq($limitsArr, $choices, $email="emptymail")
	{
		$choicesArr=array_keys($choices);

		$orderedResult=array();
		$vpercents=array();
		$distances=array();
		$stacker=array();

		$flagused=FALSE; // set this flag if any of the frequency decline percentile rules are set
		if(!is_array($limitsArr) || !is_array($choicesArr)) return $orderedResult; //totally unset, let it pass

		$leadScore=$this->getMemScore($email);
		if ($leadScore > 3) return $orderedArray;

		foreach($limitsArr as $tname=>$limits)
		{
			$vlimits[$tname]=array_slice($limits, 6); //leaves the 3 limits%, yanking the maxes
			$vpercents[$tname]=array_slice($this->getVendorScores($tname),4); // leaves the 3 current% scores
				
				
			for($tscore=0; $tscore<3; $tscore++)
			{
				if($vlimits[$tname][$tscore]==0)
				{
					// no limit set for this vendor at this particular look %
					// so set the distance to 100%, ie uncapped
					$vlimits[$tname][$tscore]=100; // 0 limit=100% cap
					$adistance=100;
				}
				else
				{
					$adistance = $vlimits[$tname][$tscore] - $vpercents[$tname][$tscore];
				}
				$stacker[$tscore]['distance'][]=$adistance;

				$stacker[$tscore]['name'][]=$tname;
				$distances[$tname][] = $adistance;
			}
		}

		$antisort=array_keys($stacker[0]['name']); // prevents alpha sorting later

		array_multisort($stacker[0]['distance'], SORT_DESC, $antisort, $stacker[0]['name']);
		array_multisort($stacker[1]['distance'], SORT_DESC, $antisort, $stacker[1]['name']);
		array_multisort($stacker[2]['distance'], SORT_DESC, $antisort, $stacker[2]['name']);

		$countdown=100;
		for($spinner=0; $spinner<3; $spinner++)
		{
			foreach($stacker[$spinner]['name'] as $key=>$onename)
			{
				$orderedResult[$spinner][$onename]=$stacker[$spinner]['distance'][$key];
				$countdown--;
			}
		}

		return $orderedResult;
	}

	/**
	 * New function to more efficiently retrieve 1 hour, 24hr, 1week freq scores by email address
	 *
	 * @param string $email
	 * @return array of 3 integers with values representing the 1 hour, 24hr, 1week freq scores by email address
	 */
	public function getPeriodicDeclines($email)
	{
		$answer=array(0,0,0);
		$unixtime=time();
		$upperemail=strtoupper($email);
		$sqltime=date('YmdHis',$unixtime);
		
		//mySQL date ranging stuff is borked, do ranging here instead
		$hourpast=date('YmdHis',strtotime("-1 hour", $unixtime));
		$daypast=date('YmdHis',strtotime("-1 day", $unixtime));
		$weekpast=date('YmdHis',strtotime("-1 week", $unixtime));

		$query="SELECT
		 SUM(v.declined_sum) as tally
		 ,v.date_created BETWEEN '$hourpast' and '$sqltime' as onehour
		 ,v.date_created BETWEEN '$daypast' and '$sqltime' as oneday
		 ,v.date_created BETWEEN '$weekpast' and '$sqltime' as oneweek
		 FROM 
		 vendor_decline_freq as v 
		 where 
		 v.date_created BETWEEN '$weekpast' and '$sqltime' 
		 and 
		 v.client_email in ('$upperemail') 
		 group by oneday, onehour 
		 order by null";

		try {
			$result = $this->sql->Query($this->sql->db_info['db'],$query);

			if (!$result)
			{
				return $answer;
			}

			while($tmp = $this->sql->Fetch_Array_Row($result))
			{
				$localResults[]=$tmp;
			}
			
			// calculate the return array
			$weekly=0;
			$daily=0;
			$hourly=0;
			
			foreach($localResults as $row)
			{
				$weekly += $row['tally'];
				if ($row['onehour']==1)
				{
					$hourly += $row['tally'];
				}
				if ($row['oneday']==1)
				{
					$daily += $row['tally'];
				}
			}
			
			$answer[0] = $hourly;
			$answer[1] = $daily;
			$answer[2] = $weekly;
			
			return $answer;
			
		}
		catch (Exception $e)
		{
			$tmp="0";
			return $answer;
			// hit errlog
		}
	}
}


?>
