<?PHP
/**
** Create DataX XML packets for various
** request calls we have to make.
**/
require_once('minixml/minixml.inc.php');
/**
** The class that everyone calls.
** It's basically a factory to create
** packet of the like real call or stuff
**/
class Data_X
{
	private $license_keys;
	private $company_short;
	private $force_field;
	private $mode;
	private $call_object;

	/**
	 * @var bool
	 */
	private $parse_response;

	/**
	** Basically just sets up license keys
	**/
	function __construct($parse_response = TRUE)
	{
        	$this->license_keys = Array(
			'generic' => Array(
				'licensekey' => 'some_license_key',
				'password'   => 'password'),

		);

		$this->parse_response = $parse_response;
	}

	/**
	** Make the proper DataX_Call Object and execute the request
	** @param string $call_type The type of call we're making
	** @param array $data Associative array of data to build the packet with
	** @param string $mode The execution mode (LIVE or RC)
	** @param string $property The company short name
	** @param string $force Optionally provide a field to force. Here for compatibility.
	** @param string $call_type_override For testing purposes, allows you to override calltype.
	**/
	public function Datax_Call($call_type,$data,$mode,$property,$force = NULL,$call_type_override = NULL)
	{

		$call_obj = $this->find_call_object($call_type);
		if($call_obj == NULL)
		{
			throw new Exception("Invalid DataX Call type: '$call_type'.");
		}

		if(isset($this->call_object))
		{
			unset($this->call_object);
		}

		$property = strtolower($property);

		$this->call_object = new $call_obj(
			$property,
			$this->license_keys[$property]['licensekey'],
			$this->license_keys[$property]['password'],
			is_string($call_type_override) ? $call_type_override : $call_type,
			$data,
			$mode,
			$force
		);

		return $this->call_object->makeRequest($this->parse_response);
	}

	/**
	** Returns the sent packet
	*/
	public function Get_Sent_Packet()
	{
		if(is_object($this->call_object))
		{
			return $this->call_object->Get_Sent_Packet();
		}
		else
		{
			return NULL;
		}
	}

	/**
	** Returns the Received packet
	*/
	public function Get_Received_Packet()
	{
		if(is_object($this->call_object))
		{
			return $this->call_object->Get_Received_Packet();
		}
		else
		{
			return NULL;
		}
	}

	/**
	** Find The call object type
	** @param string $call_type The type of call to get an object for
	**/
	private function find_call_object($call_type)
	{
		switch (strtolower($call_type))
		{
			case 'idv-l1':
			case 'idv-l3':
			case 'idv-l5':
			case 'idv-l7':
			case 'idv-rework':
			case 'clk-perf':
			case 'pdx-impact':
			case 'fundupd-l1':
			case 'agean-fundupd':
			case 'agean-title-fundupd':
			case 'impact-fundupd':

			// GForge ticket 9922
			case 'idv-vetting':
			case 'aalm-fundupd':
			case 'pdx-impactrework':
			case 'idv-compucredit':
			case 'fbod-perf':
 			case 'fbod-debit':
			case 'aalm-perf':
 			/* Added LCS for GForge ticket #9883 [AE] */
			case 'lcs-perf':
			case 'lcs-fundupd':
			case 'impactfs-idve':
 			case 'impactcf-idve':
 			case 'impactpdl-idve':
 			case 'impact-idve':
			case 'impactic-idve':
			// HMS Companies
			case 'hms_nsc-perf':
			case 'hms_bgc-perf':
			case 'hms_ezc-perf':
			case 'hms_csg-perf':
			case 'hms_tgc-perf':
			case 'hms_gtc-perf':
			case 'hms_obb-perf':
			case 'hms_cvc-perf':
			case 'rbv_gct-perf':
			case 'rbv_elc-perf':
			case 'rbv_yem-perf':
			case 'hms-fundupd':
			case 'rbv-fundupd':

 			/* Added QEasy for GForge ticket #10363 [AE] */
			case 'qeasy-perf':
			case 'qeasy-fundupd':

			case 'opm_bsc-perf':
			case 'opm-fundupd':

			case 'dmp_mcc-perf':
			case 'dmp-fundupd':

			// Geneva Roth / MyMoneyPartner
			case 'geneva-fundupd':

				return 'DataX_Data_Call';
			break;

			case 'perf-l1':
			case 'perf-l3':
				return 'DataX_PERF_Call';
				break;

			case 'df-phonetype':
				return 'DataFlux_Phone_Call';
			break;

			case 'agean-title':
				return 'DataX_Agean';
			break;

			case 'agean-perf':
				return 'DataX_Agean_Perf';
			break;
		}

		return NULL;
	}

	//Since these were publically available in datax.1.php
	//They're now available here as well.

	/**
	** Returns XML Associated with an IDV call
	**/
	public function Build_IDV_Object($data,$type,$force_field)
	{
		$call_object = $this->find_call_object($type);

		$key = $data['licensekey'];
		$pass = $data['password'];
		unset($data['licensekey']);
		unset($data['password']);

		$this->call_object = new $call_obj('',$key,
			$pass,$type,$data,'',$force_field);

		return $this->call_object->Get_XML_String();

	}
    /**
    ** Returns XML Associated with a Performance Call
    **/
    public function Build_Performance_Object($type, $data, $force_field)
    {
        $call_object = $this->find_call_object($type);

        $key = $data['licensekey'];
        $pass = $data['password'];
		unset($data['licensekey']);
		unset($data['password']);

        $this->call_object = new $call_obj('',$key,
            $pass,$type,$data,'',$force_field);

        return $this->call_object->Get_XML_String();

    }
	/**
	** Returns XML associated with a 'fundupdate' call
	**/
	public function Build_Fundupdate_Object($data,$force_field)
	{
        $call_object = $this->find_call_object('fundupd-l1');

        $key = $data['licensekey'];
        $pass = $data['password'];
		unset($data['licensekey']);
		unset($data['password']);

        $this->call_object = new $call_obj('',$key,
            $pass,'fundupd-l1',$data,'',$force_field);

        return $this->call_object->Get_XML_String();
	}

	/**
	** Sends the XML to datax returns the response
	**/
	public function Send($xml, $mode)
	{
		switch($mode)
		{
			case 'LIVE':
				$url = 'http://verihub.com/datax/index.php';
				$timeout = 15;
			break;
			case 'RC':
			default:
				$url = 'http://rc.verihub.com/datax/';
				$timeout = 5;
			break;
		}

		$curl = curl_init();
		curl_setopt( $curl, CURLOPT_URL, $url );
		curl_setopt( $curl, CURLOPT_VERBOSE, 0 );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $curl, CURLOPT_POST, 1 );
		curl_setopt( $curl, CURLOPT_POSTFIELDS, trim( $xml ));
		curl_setopt( $curl, CURLOPT_HEADER, 0 );
		curl_setopt( $curl, CURLOPT_HTTPHEADER,
			array( 'Content-Type: text/xml' ) );
		curl_setopt( $curl, CURLOPT_TIMEOUT, $timeout );
		curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, FALSE );
		curl_setopt( $curl, CURLOPT_SSL_VERIFYHOST, 0 );

		$result = curl_exec( $curl );
		$result = preg_replace('/^[\s\r\n\t]*(?=<\?xml)/', '', $result);

		return $result;
	}
}

/**
** A base class all DataX calls should extend
** to like make things work
**/
abstract class DataX_Call
{
	protected $company;
	protected $mode;
	protected $data;
	protected $type;
	protected $xmlDocument;
	protected $xmlRoot;
	private $sent_packet;
	private $received_packet;

	protected $timeout = 15;

	/**
	** Set all the variables and then initialize the document
	** @param string $company The company short name
	** @param string $key The companies License Key
	** @param string $pass The companies password
	** @param string $type The type of call we're making
	** @param array $data Associative array of data to build the packet with
	** @param string $mode The execution mode (LIVE or RC)
	** @param string $force Optionally provide a field to force. Here for compatibility.
	**/
	public function __construct($company,$key,$pass,$type,$data,$mode,$force=NULL)
	{
		$this->company = $company;
		$this->key = $key;
		$this->pass = $pass;
		$this->mode = $mode;
		$this->type = $type;
		$this->mode = $mode;
		$this->force = $force;
		$this->data =& $data;
		$this->xmlDocument = new DOMDocument('1.0','utf-8');
		$this->xmlRoot = new DOMElement('DATAXINQUIRY');
		$this->xmlDocument->appendChild($this->xmlRoot);

		$auth_elem = new DOMElement('AUTHENTICATION');
		$this->xmlRoot->appendChild($auth_elem);
		$auth_elem->appendChild(new DOMElement('LICENSEKEY',$key));
		$auth_elem->appendChild(new DOMElement('PASSWORD',$pass));

		if($force != NULL)
		{
			$auth_elem->appendChild(new DOMElement('FORCE',$force));
		}

		$this->xmlRoot->appendChild($auth_elem);
	}

	/**
	** Return the sent packet
	**/
	public function Get_Sent_Packet()
	{
		return $this->sent_packet;
	}

	/**
	** Return Received Packet
	*/
	public function Get_Received_Packet()
	{
		return $this->received_packet;
	}

	/**
	** Actually setup and make a HTTP_POST request to DataX
	** and optionally return the DataxResponse portion of the
	** response as an array (parsed via MiniXML). If the
	** choose not to parse the response, a boolean will be
	** returned indicating whether the response is non-empty.
	**
	** @param bool $parse_response If FALSE, the response is not parsed
	** @return array|bool
	**/
	public function makeRequest($parse_response = TRUE)
	{
		$url = false;
		switch(strtoupper($this->mode))
		{
			case 'LIVE': $url = 'http://verihub.com/datax/index.php'; break;

			/**
			* NOTE FOR OLP: As of this writing, blackbox will ignore the
			* results of minixml and use the olp_lib {@see DataX_Parser} class
			* to reparse the results and decide what to do in {@see OLPBlackbox_Rule_DataX}.
			*/

			case 'RC':
			default:
				$url = 'http://rc.verihub.com/datax/'; break;
		}

		if($url !== false)
		{
			$this->buildPacket();
			$this->xmlDocument->formatOutput = true;
			$packet = $this->xmlDocument->saveXML();
			$this->sent_packet = $packet;
			$curl = curl_init();
			curl_setopt($curl,CURLOPT_URL,$url);
			curl_setopt($curl,CURLOPT_VERBOSE,0);
			curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
			curl_setopt($curl,CURLOPT_POST,1);
			curl_setopt($curl,CURLOPT_POSTFIELDS,$packet);
			curl_setopt($curl,CURLOPT_HEADER,0);
			curl_setopt($curl,CURLOPT_HTTPHEADER,Array('Content-Type: text/xml'));
			curl_setopt($curl,CURLOPT_TIMEOUT,$this->timeout);

			$res = curl_exec($curl);
			$res = preg_replace('/^[\s\r\n\t]*(?=<\?xml)/','',$res);
			$this->received_packet = $res;

			if ($parse_response !== FALSE)
			{
				//WE use minixml because some of the old legacy business rules
				//(idv-l3 for example) can return invalid XML. It's rather
				//difficult to make the DOM parse this at all and minixml does
				//it no problem so here we go.
				$mini = new MiniXMLDoc();
				$mini->fromString($res);
				$ret = $mini->toArray();

				if(!empty($ret['DataxResponse']))
				{
					return $ret['DataxResponse'];
				}
			}
			else
			{
				return !empty($res);
			}
		}

		return NULL;
	}

	public function Get_XML_String()
	{
		if($this->xmlDocument instanceof DOMDocument)
		{
			return $this->xmlDocument->saveXML();
		}

		return NULL;
	}

	protected function xml_entitize($str)
	{
		return utf8_encode(str_replace(
				Array('&','<','>'),
				Array('&amp;','&lt;','&gt;'),
				$str));
	}
	/** Abstract function to be overridden by decendents to actually
	** build the packet.
	**/
	abstract public function buildPacket();
}

/**
** Build a DataX packet that contains a Data element
**/
class DataX_Data_Call extends DataX_Call
{
	protected function getDataMap()
	{
		$data_map = array();

		switch($this->type)
		{
			case 'fundupd-l1':
			case 'agean-fundupd':
			case 'agean-title-fundupd':
			case 'aalm-fundupd':
			case 'lcs-fundupd':
			case 'impact-fundupd':
			case 'qeasy-fundupd':
			case 'hms-fundupd':
			case 'rbv-fundupd':
			case 'opm-fundupd':
			case 'dmp-fundupd':
			case 'geneva-fundupd':
				$data_map = array(
					'FUNDAMOUNT' => $this->data['fundamount'],
					'FUNDFEE'   => $this->data['fundfee'],
					'FUNDDATE'  => $this->data['funddate'],
					'DUEDATE'   => $this->data['duedate'],
					'NAMEFIRST' => $this->data['namefirst'],
					'NAMELAST'  => $this->data['namelast'],
					'STREET1'   => $this->data['street1'],
					'CITY'      => $this->data['city'],
					'STATE'     => $this->data['state'],
					'ZIP'       => $this->data['zip'],
					'PHONEHOME' => $this->data['homephone'],
					'PHONECELL' => $this->data['cellphone'],
					'EMAIL'     => $this->data['email'],
					'DRIVERLICENSENUMBER' => $this->data['legalid'],
					'DRIVERLICENSESTATE'  => $this->data['legalstate'],
					'DOBYEAR'   => $this->data['dobyear'],
					'DOBMONTH'  => $this->data['dobmonth'],
					'DOBDAY'    => $this->data['dobday'],
					'SSN'       => $this->data['ssn'],
					'NAMEMIDDLE'=> $this->data['namemiddle'],
					'STREET2'   => $this->data['street2'],
					'BANKNAME'  => $this->data['bankname'],
					'BANKACCTNUMBER' => $this->data['bankacct'],
					'BANKABA'	=> $this->data['bankaba'],
					'PAYPERIOD'	=> $this->data['payperiod'],
					'IPADDRESS'	=> $this->data['ipaddress'],
					'PHONEWORK' => $this->data['phonework'],
					'WORKNAME'  => $this->data['employername'],
					'SOURCE'    => $this->data['source'],
					'PROMOID'   => $this->data['promo'],
					'MONTHLYINCOME' => $this->data['monthlyincome'],
					'LEADCOST' => $this->data['leadcost'],
					'APPLICATIONTYPE' => $this->data['applicationtype'],
				);
				break;

			default:
				$data_map = array(
					'NAMEFIRST' => $this->data['namefirst'],
					'NAMELAST'  => $this->data['namelast'],
					'STREET1'   => $this->data['street1'],
					'CITY'      => $this->data['city'],
					'STATE'     => $this->data['state'],
					'ZIP'       => $this->data['zip'],
					'PHONEHOME' => $this->data['homephone'],
					'PHONECELL' => $this->data['cellphone'],
					'EMAIL'     => $this->data['email'],
					'DRIVERLICENSENUMBER' => $this->data['legalid'],
					'DRIVERLICENSESTATE'  => $this->data['legalstate'],
					'DOBYEAR'   => $this->data['dobyear'],
					'DOBMONTH'  => $this->data['dobmonth'],
					'DOBDAY'    => $this->data['dobday'],
					'SSN'       => $this->data['ssn'],
					'NAMEMIDDLE'=> $this->data['namemiddle'],
					'STREET2'   => $this->data['street2'],
					'BANKNAME'  => $this->data['bankname'],
					'BANKACCTNUMBER' => $this->data['bankacct'],
					'BANKABA'	=> $this->data['bankaba'],
					'PAYPERIOD'	=> $this->data['payperiod'],
					'IPADDRESS'	=> $this->data['ipaddress'],
					'PHONEWORK' => $this->data['phonework'],
					'WORKNAME'  => $this->data['employername'],
					'SOURCE'    => $this->data['source'],
					'PROMOID'   => $this->data['promo'],
					'MONTHLYINCOME' => $this->data['monthlyincome'],
					'LEADCOST' => $this->data['leadcost'],
					'APPLICATIONTYPE' => $this->data['applicationtype'],
					'DIRECTDEPOSIT' => $this->data['directdeposit'],
					'AMOUNTREQUESTED' => $this->data['amountrequested'],
				);
				break;
		}
		return $data_map;
	}

	public function buildPacket()
	{
		$data_map = $this->getDataMap();

		$query = new DOMElement('QUERY');
		$this->xmlRoot->appendChild($query);
		$query->appendChild(new DOMElement('TRACKID',$this->data['trackid']));
		$query->appendChild(new DOMElement('TYPE',$this->type));

		if(isset($this->data['trackhash']) && strlen($this->data['trackhash']) > 0)
		{
			$query->appendChild(new DOMElement('TRACKHASH',$this->data['trackhash']));
		}

		if (isset($this->data['track_key']) && $this->data['track_key'])
		{
			$query->appendChild(new DOMElement('TRACKKEY', $this->data['track_key']));
		}

		$dataElement = new DOMElement('DATA');
		$query->appendChild($dataElement);
		foreach($data_map as $xml_element => $value)
		{
			if(strlen($value) > 0)
			{
				$dataElement->appendChild(
					new DOMElement($xml_element,$this->xml_entitize($value)));
			}
		}
	}
}

class DataX_Agean extends DataX_Data_Call
{
	protected $timeout = 20;

	public function getDataMap()
	{
		return array(
			'NAMEFIRST' => $this->data['namefirst'],
			'NAMELAST'  => $this->data['namelast'],
			'STREET1'   => $this->data['street1'],
			'CITY'      => $this->data['city'],
			'STATE'     => $this->data['state'],
			'ZIP'       => $this->data['zip'],
			'PHONEHOME' => $this->data['homephone'],
			'EMAIL'     => $this->data['email'],
			'DRIVERLICENSENUMBER' => $this->data['legalid'],
			'DRIVERLICENSESTATE'  => $this->data['legalstate'],
			'DOBYEAR'   => $this->data['dobyear'],
			'DOBMONTH'  => $this->data['dobmonth'],
			'DOBDAY'    => $this->data['dobday'],
			'SSN'       => $this->data['ssn'],
			'BANKNAME'  => $this->data['bankname'],
			'BANKACCTNUMBER' => $this->data['bankacct'],
			'BANKABA'	=> $this->data['bankaba'],
			'PAYPERIOD'	=> $this->data['payperiod'],
			'IPADDRESS'	=> $this->data['ipaddress'],
			'PHONEWORK' => $this->data['phonework'],
			'WORKNAME'  => $this->data['employername'],
			'SOURCE'    => $this->data['source'],
			'PROMOID'   => $this->data['promo'],
			'MONTHLYINCOME' => $this->data['monthlyincome'],
		);
	}
}


class DataX_Agean_Perf extends DataX_Agean
{
	public function getDataMap()
	{
		$data_map = parent::getDataMap();
		$data_map['BANKACCTTYPE'] = $this->data['bankaccttype'];

		return $data_map;
	}
}


class DataX_PERF_Call extends DataX_Call
{
	public function buildPacket()
	{

		$query = new DOMElement('QUERY');
		$this->xmlRoot->appendChild($query);
		$query->appendChild(new DOMElement('TYPE',$this->type));

		if(isset($this->data['trackhash']) && strlen($this->data['trackhash']) > 0)
		{
			$query->appendChild(new DOMElement('TRACKHASH',$this->data['trackhash']));
		}

		if (isset($this->data['track_key']) && $this->data['track_key'])
		{
			$query->appendChild(new DOMElement('TRACKKEY', $this->data['track_key']));
		}
	}
}
class DataFlux_Phone_Call extends DataX_Call
{
	public function buildPacket()
	{
		$query = new DOMElement('QUERY');
		$this->xmlRoot->appendChild($query);
		$query->appendChild(new DOMElement('TRACKID',$this->data['trackid']));
		$query->appendChild(new DOMElement('TYPE', $this->type));

		if (isset($this->data['track_key']) && $this->data['track_key'])
		{
			$query->appendChild(new DOMElement('TRACKKEY', $this->data['track_key']));
		}

		$dataElement = new DOMElement('DATA');

		$query->appendChild($dataElement);
		$dataElement->appendChild(new DOMElement('PHONENUMBER', $this->data['cellphone']));

		/*if(isset($this->data['trackhash']) && strlen($this->data['trackhash']) > 0)
			$query->appendChild(new DOMElement('TRACKHASH',$this->data['trackhash']));*/
	}
}
?>
