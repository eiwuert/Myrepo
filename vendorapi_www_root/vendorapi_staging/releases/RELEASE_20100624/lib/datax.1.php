<?php
//
// Call Definitions
//
// idv-l1: 			IDV only. Used by blackbox
// idv-l2: 			IDV and Performance DEPRECATED DO NOT USE AFTER DATAX V1
// idv-l3: 			IDV and Performance. Used by individual companies NOT BLACKBOX
// perf-l1: 			Performance only. Used by companies NOT BLACKBOX
// fundupd-l1: 	Loan funded call. Used by companies when the loan is funded

require_once('minixml/minixml.inc.php');

class Data_X
{
    public function __construct()
    {
    	//$this->log = $applog;
        return;
    }

   function Datax_Call($call_type, $data, $mode, $property, $force = NULL)
   {

		if($force)
		{
			if($force == 'PASS')
			{
				$force_field = '<FORCE>PASS</FORCE>';
			}
			else
			{
				$force_field = '<FORCE>FAIL</FORCE>';
			}
		}
		else
		{
			$force_field = "";
		}

		/*
		Black Box
			LK:fbe0044486e20d84f67c795619242adc\
			PW:9cb5917a3dca
		UCL
			LK:8ea43ffd2eac848a54322d2f6f5c1bc3
			PW:57de3cca3515
		UCL
			LK:d521da2ff38ce1519e27770eef1c3c00
			PW:b8cc1fe0c7f7
		UFC
			LK:83d2c79386c86bfc893cb242fae85c13
			PW:a9ace90a7e4f
		FC500
			LK:78adc9d4cb9f7d8d807726d76a446a69
			PW:d145f25fece2
		Ameriloan
			LK:80b5a0a37de4196e9c59c52ebfa522c4
			PW:8d9a09935c15
		*/
		
		switch( strtolower( $property ) )
		{
			case 'bb':
			{
				$data['licensekey'] = 'fbe0044486e20d84f67c795619242adc';
				$data['password']	= 'password';
				break;
			}
			case 'pw':
			{
				$data['licensekey'] = '2990gg463a5c352c57bcbe5c8edd9a14';
				$data['password'] = 'password';
				break;
			}
			case 'ucl':
			{
				$data['licensekey'] = '8ea43ffd2eac848a54322d2f6f5c1bc3';
				$data['password']	= 'password';
				break;
			}
			case 'pcl':
			{
				$data['licensekey'] = 'd521da2ff38ce1519e27770eef1c3c00';
				$data['password'] 	= 'password';
				break;
			}
			case 'ufc':
			{
				$data['licensekey'] = '83d2c79386c86bfc893cb242fae85c13';
				$data['password']	= 'password';
				break;
			}
			case 'd1':
			{
				$data['licensekey'] = '78adc9d4cb9f7d8d807726d76a446a69';
				$data['password']	= 'password';
				break;
			}
			case 'ca':
			{
				$data['licensekey'] = '80b5a0a37de4196e9c59c52ebfa522c4';
				$data['password']	= 'password';
				break;
			}
			case 'ic':
			{
				$data['licensekey']	= '9090ag46375c352c57bcbe5c8e8d9a11';
				$data['password']	= 'password';
				break;
			}
		}

		// reset this!
		$this->sent_packet = NULL;

		switch ($call_type)
		{
			case 'idv-l1':
			case 'idv-l3':
			case 'idv-l5':
			case 'idv-l7':
			case 'idv-rework':
			case 'pdx-impact':
			case 'pdx-impactrework':
			   $this->sent_packet = $this->Build_IDV_Object($data,$call_type,$force_field);
			break;
			
			case 'perf-l1':
			case 'perf-l3':
			   $this->sent_packet = $this->Build_Performance_Object($call_type, $data, $force_field);
			break;
			
			case 'fundupd-l1':
			   $this->sent_packet = $this->Build_Fundupdate_Object($data, $force_field);
			break;
		}
		
		if (!is_null($this->sent_packet))
		{

			// Send off the packet and pray it returns.
			$this->received_packet = $this->Send($this->sent_packet, $mode);
			
			if ($this->received_packet)
			{
				// We've got a response.  Build our array and return it to the caller
				$result = $this->_To_Array( $this->received_packet );
			}
			else
			{
				// DataX died on us.  Return an empty array.
				$result = array();
			}
			
		}
		else
		{
			// Someone screwed up and specified the wrong call_type.  Let them
			// know by labelling them false.
			$result = FALSE;
		}

	   return($result);
	   
   }

	public function Get_Received_Packet()
	{
		return($this->received_packet);
	}

	public function Get_Sent_Packet()
	{
		return($this->sent_packet);
	}

	private function _To_Array( $xml )
	{
		$mini = new MiniXMLDoc();
		$mini->fromString( $xml );
		$return = $mini->toArray();
		if(!empty($return['DataxResponse']))
			return $return['DataxResponse'];
		return NULL;
	}


   /**
    * The Build_Object function takes input and turns it into a proper XML-encapsulated string.
    *
    * In addition to XML-izing the data, it also adds the license key and password to go to
    * DataX, along with the proper formatting for that call.
    *
    * @param    string      $track_id           Customer's track ID.
    * @param    array       $data               The normalized_data from the blackbox object.
    *
    * @return   string      $xml                The prepared XML object
    */
    public function Build_IDV_Object($data,$type,$force_field)
    {
		$xml = '<?xml version="1.0" encoding="iso-8859-1"?>
                <DATAXINQUERY>
                    <AUTHENTICATION>
                        <LICENSEKEY>'.$data["licensekey"].'</LICENSEKEY>
                        <PASSWORD>'.$data["password"].'</PASSWORD>
						'.$force_field.'
					</AUTHENTICATION>
                    <QUERY>
                        <TRACKID>'.$data["trackid"].'</TRACKID>
                        <TYPE>'.$type.'</TYPE>';
						if( isset($data['trackhash']) && strlen( $data['trackhash'] ) )
						{
                        	$xml .= '<TRACKHASH>' . $data["trackhash"] . '</TRACKHASH>';
						}
            $xml .='<DATA>
							<NAMEFIRST>'.$data["namefirst"].'</NAMEFIRST>
							<NAMELAST>'.$data["namelast"].'</NAMELAST>
							<STREET1>'.$data["street1"].'</STREET1>
							<CITY>'.$data["city"].'</CITY>
							<STATE>'.$data["state"].'</STATE>
							<ZIP>'.$data["zip"].'</ZIP>
							<PHONEHOME>'.$data["homephone"].'</PHONEHOME>
							<EMAIL>'.$data["email"].'</EMAIL>
							<IPADDRESS>'.$data["ipaddress"].'</IPADDRESS>
							<DRIVERLICENSENUMBER>'.$data["legalid"].'</DRIVERLICENSENUMBER>
							<DRIVERLICENSESTATE>'.$data["legalstate"].'</DRIVERLICENSESTATE>
							<DOBYEAR>'.$data["dobyear"].'</DOBYEAR>
							<DOBMONTH>'.$data["dobmonth"].'</DOBMONTH>
							<DOBDAY>'.$data["dobday"].'</DOBDAY>
							<SSN>'.$data["ssn"].'</SSN>
							<NAMEMIDDLE>'.$data["namemiddle"].'</NAMEMIDDLE>
							<STREET2>'.$data["street2"].'</STREET2>
							<BANKNAME>'.$data["bankname"].'</BANKNAME>
							<BANKACCTNUMBER>'.$data["bankacct"].'</BANKACCTNUMBER>
							<BANKABA>'.$data["bankaba"].'</BANKABA>
							<BANKACCTTYPE>'.$data["banktype"].'</BANKACCTTYPE>
							<PHONEWORK>'.$data['phonework'].'</PHONEWORK>
							<PHONEEXT>'.$data['phoneext'].'</PHONEEXT>
							<WORKNAME>'.$data['employername'].'</WORKNAME>
							<SOURCE>'.$data['source'].'</SOURCE>
							<PROMOID>'.$data['promo'].'</PROMOID>
						</DATA>
                    </QUERY>
                </DATAXINQUERY>';

		return $xml;
	}

    public function Build_Performance_Object($type, $data, $force_field)
    {
		$xml = '<?xml version="1.0" encoding="iso-8859-1"?>
        <DATAXINQUERY>
            <AUTHENTICATION>
                <LICENSEKEY>'.$data["licensekey"].'</LICENSEKEY>
                <PASSWORD>'.$data["password"].'</PASSWORD>
								'.$force_field.'
            </AUTHENTICATION>
            <QUERY>
                <TYPE>'.$type.'</TYPE>
                <TRACKHASH>' . $data["trackhash"] . '</TRACKHASH>
            </QUERY>
        </DATAXINQUERY>';

        return $xml;
    }

   /**
    * @param    string      $track_hash 		Hash from the previous request to datax with this customer application
    * @param    array       $data        		Normalized data array
    */
   public function Build_Fundupdate_Object($data,$force_field)
   {
	   $xml = '<?xml version="1.0" encoding="iso-8859-1" ?>
		<DATAXINQUERY>
			<AUTHENTICATION>
				<LICENSEKEY>'.$data["licensekey"].'</LICENSEKEY>
				<PASSWORD>'.$data["password"].'</PASSWORD>
				'.$force_field.'
			</AUTHENTICATION>
		<QUERY>
			<TRACKID>'.$data["trackid"].'</TRACKID>
			<TYPE>fundupd-l1</TYPE>
			<TRACKHASH>'.$data["trackhash"].'</TRACKHASH>
			<DATA>
				<FUNDAMOUNT>'.$data["fundamount"].'</FUNDAMOUNT>
				<FUNDFEE>'.$data["fundfee"].'</FUNDFEE>
				<DUEDATE>'.$data["duedate"].'</DUEDATE>
			</DATA>
		</QUERY>
		</DATAXINQUERY>';

	   return $xml;
   }
   /**
    * @param    string      $xml        XML-Encapsulated Data_X information created by Build_Object().
    */
    public function Send( $xml, $mode )
    {
		switch ($mode)
		{
			case 'LIVE':
				$url = 'http://verihub.com/datax/index.php';
				$timeout = 15;
			break;

			case 'RC':
			default:
				$url = 'http://verihub.com/rcdatax/index.php';
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
    curl_setopt( $curl, CURLOPT_HTTPHEADER, array( 'Content-Type: text/xml' ) );
    curl_setopt( $curl, CURLOPT_TIMEOUT, $timeout );
    curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, FALSE );
    curl_setopt( $curl, CURLOPT_SSL_VERIFYHOST, 0 );
    
    $result = curl_exec( $curl );
    
    // trim off leading spaces and line breaks
    $result = preg_replace('/^[\s\r\n\t]*(?=<\?xml)/', '', $result);
    
    return $result;
    }
}
/*
//
//	IDV test block
*/

/*
$idv = array(
"licensekey" => "fbe0044486e20d84f67c795619242adc",
"password" => "password",
"trackid" => "9898977",
"namefirst" => "Monkey",
"namelast" => "Monkey",
"street1" => "123 Main St",
"city" => "Las Vegas",
"state" => "NV",
"zip" => "89119",
"homephone" => "7029879877",
"email" => "monkey@msn.com",
"ipaddress" => "28.28.28.28",
"legalid" => "nv19827391",
"legalstate" => "NV",
"dobyear" => "1975",
"dobmonth" => "09",
"dobday" => "15",
"ssn" => "256743066",
"namemiddle" => "M",
"street2" => "apt 1094",
"workphone" => "7027667676",
"workext" => "x23",
"bankname" => "Bank of Jungle",
"bankacct" => "9879879879877724",
"bankaba" => "123123123",
"banktype" => "Checking"
);

$xml = new Data_X();
$xml->Datax_Call('idv-l3',$idv,'RC','ucl','PASS');
*/

//
//	Performance test block
/*
$perf = array(
"licensekey" => "8ea43ffd2eac848a54322d2f6f5c1bc3",
"password" => "password",
"trackid" => "9898977",
"trackhash" => "ae2cf50707694b6c9442d90f926d099b"
);

$xml = new Data_X();
$xml->Datax_Call('perf-l1', $perf,'RC','d1','PASS');


/*
//
//	Fund Update test block
//
$fundupd = array(
"licensekey" => "8ea43ffd2eac848a54322d2f6f5c1bc3",
"password" => "password",
"fundamount" => "300",
"fundfee" => "90",
"duedate" => "2005/12/25",
"trackid" => "9898977"
);

$xml = Data_X::Datax_Call('fundupd-l1', $fundupd,'RC');


*/


//var_dump($xml);





?>
