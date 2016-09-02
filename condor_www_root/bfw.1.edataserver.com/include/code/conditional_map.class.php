<?php

/*
 *	Class definition: Conditional_Map (conditional_map.class.php)
 *  @author: Tym Feindel
 *	@@Artifex Verborem: Tym
 * 	@@ v1.4.7
 *
 */

include_once 'server.php';

class Conditional_Map
{

	protected $conditions = array();
	protected $property_short="";
	protected $mode;
	protected $status="new";
	protected $saved=false;
	protected $rpc_params=array();
	protected $error = array();
	protected $delim_redirect=array();
	protected $search_accept="";
	protected $post_type="post";


	public function __construct($mode = 'RC', &$sql = null, $property_short=null)
	{
		//$mode, &$sql, $db = NULL, $mode, $property_short
		$this->setSaved(false);
		$this->mode = $mode;
		$this->property_short = $property_short;

		if(!is_null($property_short)){
			$this->property_short=$property_short;
		}

		//set a bogus conditional up so the display is happy
		$tempo=$this->addCondition('(none)','always','');
		$this->addFieldMap($tempo,'name_first','customer.first.name','');

	}


	public function setConditions($condArray)
	{
		$this->conditions=$condArray;
		$this->setSaved(false);
	}

	/**
	 * Inserts a condition into the object; fieldmaps associated with the condition will be output when
	 * the condition is TRUE
	 *
	 * @param string $ssfieldname name of the sellingsource field, value to be used in the comparison
	 * @param string $conditional a short text conditional operator description-- 'equals', '=>', etc...
	 * @param string $comparator the item the ssfield will be compared against
	 * @param array $mapsArray the fieldmaps to be associated with this object
	 * @return string $tag a UID for the conditional element (for backreference)
	 */
	public function addCondition($ssfieldname, $conditional, $comparator, $mapsArray=null)
	{
		$tag=md5(trim($ssfieldname).trim($conditional).trim($comparator));
		if(array_key_exists($tag, $this->conditions)){
			//there's an overwrite condition, issue warning
			//echo "overwrite condition in addCondition";
		}
		$this->setSaved(false);

		$this->conditions[$tag]=array(
 		'ssfieldname'=>$ssfieldname,
 		'conditional'=>$conditional,
 		'comparator'=>$comparator,
 		'maps'=>$mapsArray
		);

		return $tag;
	}

	/**
	 * function addFieldMap adds a field mapping to a specific condition
	 *
	 * @param string $condtag the MD5 array key for the parent condition
	 * @param string $ssfield the sellingsource field name
	 * @param string $vfield the vendor field name
	 * @param string $dvalue the default value for the (vendor) field
	 * @return string $fondue the calculated MD5 key of the added fieldmap
	 */
	public function addFieldMap($condtag,$ssfield,$vfield,$dvalue)
	{
		//if(strcmp($dvalue,"")) $dvalue=" ";
		$this->setSaved(false);
		$fondue=md5($ssfield . $vfield . $dvalue);
		//echo "fondue is ". $fondue;
		$fish= array('ssfield'=>$ssfield, 'vfield'=>$vfield, 'dvalue'=>$dvalue);
		$this->conditions[$condtag]['maps'][$fondue]=$fish;
		return $fondue;
	}

	/**
	 * function removeFieldMap removes a field mapping from a specific condition
	 *
	 * @param string $condtag the MD5 of the parent condition that the field belongs to
	 * @param string $maptag the MD5 of the field mapping itself
	 */
	public function removeFieldMap($condtag, $maptag)
	{
		$this->setSaved(false);
		unset($this->conditions[$condtag]['maps'][$maptag]);

	}

	/**
	 * removes a specific condition
	 *
	 * @param string $condtag the MD5 value of the condition to be removed
	 */
	public function removeCondition($condtag)
	{
		$this->setSaved(false);
		unset($this->conditions[$condtag]);
	}

	/**
	 * Copies and returns the array of conditions and fieldmaps associated with this instance
	 *
	 * @return array a deep copy of the internal Conditional_Map conditions array
	 */
	public function dumpArray()
	{
		$retme=array();
		$maxdepth=10;
		$this->array_deep_copy($this->conditions,$retme,$maxdepth);
		return $retme;
	}

	public function setPropertyShort($prop_short)
	{
		$this->property_short=$prop_short;
	}

	public function getPropertyShort()
	{
		return $this->property_short;
	}

	private function array_deep_copy (&$array, &$copy, $maxdepth=50, $depth=0)
	{
		if($depth > $maxdepth) { $copy = $array; return; }
		if(!is_array($copy)) $copy = array();
		foreach($array as $k => &$v) {
			if(is_array($v)) {        $this->array_deep_copy($v,$copy[$k],$maxdepth,++$depth);
			} else {
				$copy[$k] = $v;
			}
		}
	}

	public function propExists($sqlconn, $property_short, $database, $forcemode="")
	{
		//should only be called from within BFW framework, not in isolated mode
		$tmp="";
		$query="
			SELECT
				conditions_obj
			FROM
				vendor_post_map as v
			WHERE
				v.property_short = '" . $property_short . "'
				AND v.status = 'ACTIVE'";
		try {

			$result = $sqlconn->Query($database, $query);
			$tmp = $sqlconn->Fetch_Column($result,'conditions_obj');
		}
		catch (Exception $e){
			$tmp="";
			// @@ToDo generate a better error response
			$message  = 'Invalid query: ' . mysql_error() . "\n";
			$message .= 'Whole query: ' . $query;
			//die($message);
			return FALSE;
		}

		if(strlen($tmp)>20){
			// 			echo $tmp;
			// 			echo "\nQuery:" . $query;
			// 			echo "\nDB $database ";
			return true;
		}
		else {
			return false;
		}
		return false;

	}

	/**
	 * Attempts to recreate a Conditional_Map object from DB
	 *
	 * @param unknown_type $sqlconn an SQL connection
	 * @param string $property_short
	 * @param string $database
	 * @param string $forcemode unused parameter
	 * @return boolean TRUE for success
	 */
	public function reconstitute($sqlconn, $property_short, $database, $forcemode="")
	{
		//should only be called from within BFW framework, not in isolated mode
		$tmp="";
		$query="
			SELECT
				conditions_obj
			FROM
				vendor_post_map as v
			WHERE
				v.property_short = '" . $property_short . "'
				AND v.status = 'ACTIVE'";
		try {
			$result = $sqlconn->Query($database,$query);
			$tmp = $sqlconn->Fetch_Column($result,'conditions_obj');
		}
		catch (Exception $e){
			$tmp="";
		}

		$holder=unserialize($tmp);

		if($holder instanceOf Conditional_Map){
			foreach (get_object_vars($holder) as $key => $value)
			$this->$key = $value;
			//$this=$holder;

			$this->setPropertyShort($property_short);
			return TRUE;
				
		}
		else {
			return FALSE;
		}

	}

	/**
	 * function getFields
	 *
	 * @param array $reqfields
	 * @param boolean $numindexed (default=FALSE) return a sequential array instead of associative
	 * @return array $fieldmap an associative array of vendor-specific fieldname=>fieldvalue
	 */
	public function getFields($reqfields, $numindexed=FALSE)
	{
		//take a 1-dimensional array based on the passed in *flattened* $lead_data['data']
		$fieldmap=array();
		$tempssval="";
		$worker=$this->conditions;
		//echo "eval conditions<br>\n";
		foreach($worker as $condtag){
			//one condition
			$ssfieldname=$condtag['ssfieldname'];
			$ssfieldval=$reqfields[$ssfieldname];
			$conditional=$condtag['conditional'];
			$comparator=$condtag['comparator'];
			$maps=$condtag['maps'];
			// evaluate the conditional against the value
			//echo "trying eval conditions $ssfieldval, $conditional, $comparator<br>\n";
			if($this->checkcondition($ssfieldval, $conditional, $comparator)){
				//echo "TRUE <br>\n";
				//add the listed mappings
				if(is_array($condtag['maps'])){
					foreach($condtag['maps'] as $maptag)
					{
						$vfield=$maptag['vfield'];   //the vendor field name
						$ssfield=$maptag['ssfield']; //the ss field to map
						$dvalue=$maptag['dvalue'];   //the default value to map to the field

						if((strcmp($ssfield,'(none)')==0) ||
						(!isset($reqfields[$ssfield]))||
						(strlen($reqfields[$ssfield])==0))
						{
							//echo "**using default: $ssfield," .  $reqfields[$ssfield].", <br>\n";
							//use the default val instead if field blank or undefined
							$tempssval=$dvalue;
						}
						else 
						{
							//added to handle format directives
							if(stristr($dvalue,"#FMT_")!==FALSE)
							{
								$tempssval=$this->cmcFormat($dvalue,$reqfields[$ssfield]);
							}
							else
							$tempssval=$reqfields[$ssfield];
						}
						
						// added to handle nested XML stuff with non-unique indexes
						if ($numindexed)
						{
							$fieldmap[]=array($vfield=>$tempssval);
						}
						else
						{
							$fieldmap[$vfield]=$tempssval;
						}

					}
				}// fail means no defined fields here

			}// done checking condition

		}//on to next condition

		return $fieldmap;
	}
	

	public function setSaved($boolsaved)
	{
		$this->saved=$boolsaved;
	}

	public function getSaved()
	{
		return $this->saved;
	}

	/**
	 * Checks a conditional statement using predefined short string conditionals
	 *
	 * @param string $ssfieldval
	 * @param string $conditional
	 * @param string $comparator
	 * @return boolean TRUE if the comparison statement evalutes as such, else false
	 */
	public function checkcondition($ssfieldval, $conditional, $comparator)
	{
		//do an eval on the passed-in terms
		switch($conditional){
			case "always":
				return true;
				break;
			case "(always)":
				return true;
				break;
			case "equals":
				if(strcasecmp($ssfieldval,$comparator)==0)
				return true;
				else return false;
				break;
			case "not equal":
				if(strcasecmp($ssfieldval,$comparator)!=0)
				return true;
				else return false;
				break;
			case "contains":
				if(strpos($ssfieldval,$comparator)!==FALSE)
				return true;
				else return false;
				break;
			case "&gt;":
			case ">":
				if($ssfieldval>$comparator)
				return true;
				else return false;
				break;
			case "&lt;":
			case "<":
				if($ssfieldval<$comparator)
				return true;
				else return false;
				break;
			case "length &gt;":
			case "length >":
				if(strlen($ssfieldval)> $comparator)
				return true;
				else return false;
				break;
			case "length &lt;":
			case "length <":
				if(strlen($ssfieldval)< $comparator)
				return true;
				else return false;
				break;
			case "length =":
				if(strlen($ssfieldval) == $comparator)
				return true;
				else return false;
				break;
			case "length &gt;=":
			case "length >=":
				if(strlen($ssfieldval)>= $comparator)
				return true;
				else return false;
				break;
			case "length &lt;=":
			case "length <=":
				if(strlen($ssfieldval)<= $comparator)
				return true;
				else return false;
				break;

			default:
				return false;
		}
	}

	/**
	 * Returns the size of the rpc_params in this object
	 *
	 * @return int the size of the rpc_params array
	 */
	public function isRpcSet()
	{
		return sizeof($this->rpc_params);
	}

	public function setRpc($rpc_params)
	{
		$newrpc=array();
		$this->array_deep_copy($rpc_params, $newrpc);
		$this->rpc_params=$newrpc;
	}

	public function getRpc()
	{
		return $this->rpc_params;
	}

	public function getRedirectStart()
	{
		return $this->delim_redirect['start'];
	}

	public function getRedirectEnd()
	{
		return $this->delim_redirect['end'];
	}

	public function setRedirectStart($newstart)
	{
		$this->delim_redirect['start']=$newstart;
	}

	public function setRedirectEnd($newend)
	{
		$this->delim_redirect['end']=$newend;
	}

	/**
	 * Get the match string (for a vendor response indicative of accepting a post)
	 *
	 * @return string
	 */
	public function getAcceptMatch()
	{
		return $this->search_accept;
	}

	/**
	 * Set the match string (for a vendor response indicative of accepting a post)
	 *
	 * @param string $matchme the string to seek
	 */
	public function setAcceptMatch($matchme)
	{
		$this->search_accept=$matchme;
	}
		
	/*
	 * @return string post_type defaults to 'post', alternately 'get' or 'xml'
	 */
	public function getPostType()
	{
		return $this->post_type;
	}
		
	/**
	 * Define how this COnditional_Map will post
	 * @param string $posttype typically 'get', 'post' or 'xml'
	 */
	public function setPostType($posttype="post")
	{
		$this->post_type=$posttype;
	}
	
	public function cmcFormat($format, $item)
	{
		if (preg_match('/^#FMT_date/i', trim($format))) // input item is a date
		{
			$item = empty($item) ? time() : strtotime($item);
		}		
		
		try
		{
			switch ($format)
			{
				case "#FMT_ssn_dash":
					$tmp="";
					$tmp.=substr($item,0,3) . "-";
					$tmp.=substr($item,3,2) . "-";
					$tmp.=substr($item,5,4);
					return $tmp;
				break;
				
				case "#FMT_phone_dash":
					$tmp="";
					$tmp.=substr($item,0,3) . "-";
					$tmp.=substr($item,3,3) . "-";
					$tmp.=substr($item,6,4);
					return $tmp;
				break;
				
				case "#FMT_date_dash":
					return date("m-d-Y", $item);
				break;
				
				case "#FMT_date_ymd_dash":
					return date("Y-m-d", $item);
				break;
				
				case "#FMT_date_ymd_slash":
					return date("Y/m/d", $item);
				break;
					
				case "#FMT_date_ymd":
					return date("Ymd", $item);
				break;
					
				case '#FMT_date_YmdHis':
					return date('YmdHis', $item);
				break;

				case "#FMT_date_dmy_slash":
					return date('d/m/Y', $item);
				break;
				
				case "#FMT_date_day":
					return date('d', $item);
				break;
				
				case "#FMT_date_month":
					return date('m', $item);
				break;
				
				case "#FMT_date_year":
					return date('Y', $item);
				break;
				
				case "#FMT_string_ucfirst":
					return ucfirst(strtolower($item));
				break;
								
				default:
					return $item;
				break;
				
			}
		}
		
		catch (Exception $e)
		{
			return "CMC Format error: $format $item";
		}
	}

}

?>
