<?php
/** DataX live URL. */
define('DATAX_URL', 'http://verihub.com/datax/aba.php');
/** DataX RC URL. */
//define('DATAX_URL', 'http://verihub.com/rcdatax/aba.php');

/** DataX local URL. */
//define('DATAX_URL', 'http://dataxv2.ds62.tss/');

/** DataX license key (BlackBox). */
define('DATAX_LICENSE', 'fbe0044486e20d84f67c795619242adc');

/** DataX password (BlackBox). */
define('DATAX_PASSWORD', 'password');

require_once('minixml/minixml.inc.php');

/** @class Aba
 * Interface to DataX for ABA number verification.
 */
class ABA_1
{
	/** Constructs a new <code>ABA_1</code>.
	 */
	function ABA_1 ()
	{
	}

	/** Returns information about a routing number. Currently unimplemented by
	 * DataX.
	 * @param $routing_number Routing number to get information for.
	 * @param $field_list Information fields to return.
	 */
	function Info ($routing_number, $field_list)
	{
		$req = "<?xml version=\"1.0\" encoding=\"iso-8859-1\"?>
				<DATAXINQUERY>
					<AUTHENTICATION>
						<LICENSEKEY>" . DATAX_LICENSE . "</LICENSEKEY>
						<PASSWORD>" . DATAX_PASSWORD . "</PASSWORD>
					</AUTHENTICATION>
					<QUERY>
						<TYPE>aba-l2</TYPE>
						<DATA>
							<BANKABA>$routing_number</BANKABA>
							<INFOFIELDS>$field_list</INFOFIELDS>
						</DATA>
					</QUERY>
				</DATAXINQUERY>";

		//Submit request to DataX
		$ch = curl_init(DATAX_URL);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, trim($req));
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURL_SSL_VERIFYHOST, 0);
        $result = curl_exec($ch);
		if (curl_errno($ch))
			return array('DataXError' => curl_error($ch));

		//Parse reply and extract info
		$doc = new MiniXMLDoc();
		$result = trim($result);
		$doc->fromString($result);
		$xml_array = $doc->toArray();
		$result = array();
		if (!is_array($xml_array['DataxResponse']['ABA']['Data']['info']))
			return array('DataXError' => 'Invald Aba Data Info');

		foreach ($xml_array['DataxResponse']['ABA']['Data']['info'] as $infos)
		{
			if (!is_array($infos))
				continue;
			$info_array = array();

			$tmp = array_key_exists('field', $infos) ? $infos['field'] : $infos;
			foreach ($tmp as $info)
			{
				if (!is_array($info))
					continue;
				$field_array = array();

				foreach ($info as $name => $value)
					$field_array[$name] = $value;
				$info_array[] = $field_array;
			}
			$result[] = $info_array;
		}
		return $result;
	}

	/** Verifies an ABA routing number.
	 * @param $license_key Ignored.
	 * @param $application_id Unique track ID to associate with this DataX
	 * transaction.
	 * @param $aba_number ABA routing number to verify.
	 * @return Array containing verification result and bank name.
	 */
	function Verify ($license_key, $application_id, $aba_number)
	{
		$req = "<?xml version=\"1.0\" encoding=\"iso-8859-1\"?>
				<DATAXINQUERY>
					<AUTHENTICATION>
						<LICENSEKEY>" . DATAX_LICENSE . "</LICENSEKEY>
						<PASSWORD>" . DATAX_PASSWORD . "</PASSWORD>
					</AUTHENTICATION>
					<QUERY>
						<TRACKID>$application_id</TRACKID>
						<TYPE>aba-l1</TYPE>
						<DATA>
							<BANKABA>$aba_number</BANKABA>
						</DATA>
					</QUERY>
				</DATAXINQUERY>";

		//Submit request to DataX
		$ch = curl_init(DATAX_URL);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, trim($req));
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURL_SSL_VERIFYHOST, 0);
        $result = curl_exec($ch);
		if (curl_errno($ch))
			return array('DataXError' => curl_error($ch));

		//Parse reply and extract info
		$doc = new MiniXMLDoc();
		$result = trim($result);
		$doc->fromString($result);
		$xml_array = $doc->toArray();
		if (!is_array($xml_array['DataxResponse']['ABA']['Data']))
			return array('DataXError' => 'Invalid Aba Data');

		$valid = $xml_array['DataxResponse']['ABA']['Data']['Valid'] == 'Y';
		$bank_name = $xml_array['DataxResponse']['ABA']['Data']['BankName'];
		$result = array('valid' => $valid, 'bank_name' => $bank_name);
		return $result;
	}

	/** Verifies an ABA routing number verbosely.
	 * @param $license_key Ignored.
	 * @param $application_id Unique track ID to associate with this DataX
	 * transaction.
	 * @param $aba_number ABA routing number to verify.
	 * @return Array containing verification result, fail code and bank name.
	 */
	function VerifyVerbose ($license_key, $application_id, $aba_number)
	{
		$req = "<?xml version=\"1.0\" encoding=\"iso-8859-1\"?>
				<DATAXINQUERY>
					<AUTHENTICATION>
						<LICENSEKEY>" . DATAX_LICENSE . "</LICENSEKEY>
						<PASSWORD>" . DATAX_PASSWORD . "</PASSWORD>
					</AUTHENTICATION>
					<QUERY>
						<TRACKID>$application_id</TRACKID>
						<TYPE>aba-verbose</TYPE>
						<DATA>
							<BANKABA>$aba_number</BANKABA>
						</DATA>
					</QUERY>
				</DATAXINQUERY>";

		//Submit request to DataX
		$ch = curl_init(DATAX_URL);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, trim($req));
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURL_SSL_VERIFYHOST, 0);
        $result = curl_exec($ch);
		if (curl_errno($ch))
			return array('DataXError' => curl_error($ch));

		//Parse reply and extract info
		$doc = new MiniXMLDoc();
		$result = trim($result);
		$doc->fromString($result);
		$xml_array = $doc->toArray();
		if (!is_array($xml_array['DataxResponse']['ABA']['Data']))
			return array('DataXError' => 'Invalid Aba Data');

		/**
		 * Fail codes:
		 * I - Invalid ABA number (Failed Mod-10 check)
		 * L - Lookup failed (Not found in database)
		 * C - ABA/Bank's ACH transactions not permitted for consumers
		 * N - ABA/Bank's ACH transactions not permitted
		 * D - Cannot debit/credit account
		 */
			
		$valid = $xml_array['DataxResponse']['ABA']['Data']['Valid'] == 'Y';
		$fail_code = $xml_array['DataxResponse']['ABA']['Data']['FailCode'];
		$bank_name = $xml_array['DataxResponse']['ABA']['Data']['BankName'];
		$result = array('valid' => $valid, 'fail_code' => $fail_code, 'bank_name' => $bank_name);
		return $result;
	}
}

//#################
//Example usage
//#################
//$aba = new ABA_1();
//$result = $aba->Info("011000138", "7,22");
//$result = $aba->Verify(NULL, NULL,  "011000138");
//print_r($result);

?>
