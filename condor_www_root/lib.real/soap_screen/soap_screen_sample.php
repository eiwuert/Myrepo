<?php
	// make sure we have SOAP loaded
	if (!(extension_loaded('soap') || @dl('soap')))
	{
		die('The PHP SOAP extension is not loaded. Please let us know.');
	}
	
	// make sure that notice errors don't wreck wsdl in case server has E_ALL errors displayed.
	set_error_handler('My_Error_Handler');  

	// $default_wsdl = 'http://ds57.tss:8080/wsdl_parser/example_wsdl.php';  // This is galileo SOAP ONLY
	// $default_wsdl = 'https://secure1.galileoprocessing.com/wstest/GalileoIntegration/GalileoIntegration.asmx?wsdl';
	// $default_wsdl = 'http://ccs.1.edataserver.com.ds57.tss:8080/api/ccs_api.php?WSDL';
	// $default_wsdl = 'http://nms.1.edataserver.com/cm_soap.php?wsdl';
	$default_wsdl = 'http://ds57.tss:8080/wsdl_parser/lib/soap_screen_sample.php?wsdl';
	
	// This will either be a WSDL request, a SOAP request, or else we'll use the Soap_Screen to
	// present an interactive menu of functions based on the WSDL.
	$wsdl_request = strtolower( $_SERVER['QUERY_STRING'] ) == 'wsdl' ? true : false;
	$soap_request = false;


	if ( $wsdl_request )
	{
		header('Content-Type: text/xml');
		echo '<' . '?xml version="1.0"?' . ">\r\n";  // This line gets interpreted as PHP if the < and the ? are not separated.
		// The XML WSDL is included below and will be sent to the browser following this header.
		// Place this file somewhere accessible by your webserver and enter the path to it in your browser.
		// You should see a menu of functions defined by the WSDL.  If you append "/wsdl" to the url you
		// should see the WSDL.  And if you try the "soap_screen_sample_client.php" from the
		// command line you should see the result of actually performing an actual SOAP call to the SampleSoapServerClass class.
	}
	else if ( $_SERVER['REQUEST_METHOD'] == 'POST' && isset($HTTP_RAW_POST_DATA) && !empty($HTTP_RAW_POST_DATA) && !isset($_POST['soap_screen_running_flag']) )
	{
		// The raw data posted to this server will be available in the $HTTP_RAW_POST_DATA variable provided the php.ini has
		// always_populate_raw_post_data = On.  There is an improved, less memory intensive approach to getting the post data
		// which is:  php://input

		// Since all POSTs will populate the $HTTP_RAW_POST_DATA field, we need to check the
		// field $_POST['soap_screen_running_flag'] to know that this is NOT a SOAP request.  A better
		// way to see if this is a SOAP request would be to check for the presence of soap envelope xml tags
		// but this approach is quick and simple.
		
		$soap_request = true;
		$server = new SoapServer($default_wsdl);
		$server->setClass('SampleSoapServerClass', 'blah', 'blah2');
		$server->handle();
	}
	else
	{
		require_once('soap_screen/soap_screen.php');


		// use this approach for the standard HTML possibly, using a customized template path
		// ----------------------------------------------------------------------------------
		// $soap_screen = new Soap_Screen($default_wsdl, false, '/custom/template/path/');  
		$soap_screen = new Soap_Screen($default_wsdl);


		// use this approach to use a customized HTML template class
		// This is an example of how to modify the standard template engine in order
		// to customize the HTML.  Of course, you can simply make one from scratch provided
		// all the method signatures match up with the standard version.  In this example, we
		// are bolding and underlining the field names within the data entry form.
		// ----------------------------------------------------------------------------------
		// require_once('soap_screen/soap_screen_html.php');
		// require_once('soap_screen/soap_screen_html_customized.php');
		// $soap_screen = new Soap_Screen( $default_wsdl, false, NULL, new Soap_Screen_Html_Customized() );
		
		
		$soap_screen->setClass( 'SampleSoapServerClass', 'blah', 'blah2' );
		$soap_screen->handle();

	}
	



	class SampleSoapServerClass
	{
		public function __construct( $in1 = 'cdef1', $in2 = 'cdef2' ) {
			$result = __METHOD__ . ": in1=$in1, in2=$in2";
			return $result;
		}

		public function testMethod( $in1 = 'mdef1', $in2 = 'mdef2' ) {
			$result =  __METHOD__ . ": in1=$in1, in2=$in2";
			return $result;
		}

		public function Start_Session( $client_id, $client_passwd ) {
			$result = __METHOD__ . ": client_id=$client_id, client_passwd=$client_passwd";
			return $result;
		}
		
		public function Close_Session( $client_id, $client_passwd, $session_id ) {
			$result = __METHOD__ . ": client_id=$client_id, client_passwd=$client_passwd, session_id=$session_id";
			return $result;
		}
		
		public function ODS_Enrollment( $session_id, $account_id, $bank_aba, $bank_account, $ssn, $name_first, $name_last, $name_suffix, $email, $ipaddress, $permanent_resident, $dob, $employer_name, $employer_length, $shift, $direct_deposit, $income_type, $income_stream, $income_monthly_net, $income_frequency, $income_date_1, $income_date_2, $residence_type, $street, $unit, $city, $state, $zip, $phone_home, $phone_cell, $phone_work, $phone_work_ext, $legal_id_number, $legal_id_state, $ref_01_name, $ref_01_phone, $ref_01_relationship, $ref_02_name, $ref_02_phone, $ref_02_relationship ) {
			$result = __METHOD__ . ": session_id=$session_id, account_id=$account_id, bank_aba=$bank_aba, bank_account=$bank_account, ssn=$ssn, name_first=$name_first, name_last=$name_last, name_suffix=$name_suffix, email=$email, ipaddress=$ipaddress, permanent_resident=$permanent_resident, dob=$dob, employer_name=$employer_name, employer_length=$employer_length, shift=$shift, direct_deposit=$direct_deposit, income_type=$income_type, income_stream=$income_stream, income_monthly_net=$income_monthly_net, income_frequency=$income_frequency, income_date_1=$income_date_1, income_date_2=$income_date_2, residence_type=$residence_type, street=$street, unit=$unit, city=$city, state=$state, zip=$zip, phone_home=$phone_home, phone_cell=$phone_cell, phone_work=$phone_work, phone_work_ext=$phone_work_ext, legal_id_number=$legal_id_number, legal_id_state=$legal_id_state, ref_01_name=$ref_01_name, ref_01_phone=$ref_01_phone, ref_01_relationship=$ref_01_relationship, ref_02_name=$ref_02_name, ref_02_phone=$ref_02_phone, ref_02_relationship=$ref_02_relationship";
			return $result;
		}
		
		public function ODS_Transaction_Acknowledgement( $session_id, $account_id, $amount, $trans_date ) {
			$result = __METHOD__ . ": session_id=$session_id, account_id=$account_id, amount=$amount, trans_date=$trans_date";
			return $result;
		}
		
		public function is_Valid_Card_Id( $session_id, $card_id ) {
			$result = __METHOD__ . ": session_id=$session_id, card_id=$card_id";
			return $result;
		}
		
		public function Run_Debit_Credit_Transaction( $session_id, $card_id, $amount, $transaction_type ) {
			$result = __METHOD__ . ": session_id=$session_id, card_id=$card_id, amount=$amount, transaction_type=$transaction_type";
			return $result;
		}
		
	}

	

	function My_Error_Handler($error_type, $error_string, $error_file, $error_line)
	{
		// make sure that notice errors don't wreck wsdl in case server has E_ALL errors displayed.
		// If there is a minor PHP notice echo'd back with the WSDL, the SOAP WSDL will then be invalid
		// so catch minor errors here and ignore all but serious errors.
		
		if ($error_type & E_ERROR)
		{
			// This is some kind of serious error that should be logged.
			// MyErrorLogger( "Error_Handler: error_type=$error_type, error_string=$error_string, error_file=$error_file, error_line=$error_line" );
		}
	}

?>
<?php if ( $wsdl_request ) { ?>
<definitions name="tss" targetNamespace="urn:tss"
	xmlns="http://schemas.xmlsoap.org/wsdl/"
	xmlns:tns="urn:tss"
	xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/"
	xmlns:xsd="http://www.w3.org/2001/XMLSchema">
	
	<types/>
	
	<message name="Start_Session">
		<part name="client_id" type="xsd:string"/>
		<part name="client_passwd" type="xsd:string"/>
	</message>
	<message name="Start_SessionResponse">
		<part name="result" type="xsd:string"/>
	</message>
	
	<message name="Close_Session">
		<part name="client_id" type="xsd:string"/>
		<part name="client_passwd" type="xsd:string"/>
		<part name="session_id" type="xsd:string"/>
	</message>
	<message name="Close_SessionResponse">
		<part name="result" type="xsd:string"/>
	</message>
	
	<message name="ODS_Enrollment">
		<part name="session_id" type="xsd:string"/>
		<part name="account_id" type="xsd:string"/>
		<part name="bank_aba" type="xsd:string"/>
		<part name="bank_account" type="xsd:string"/>
		<part name="ssn" type="xsd:string"/>
		<part name="name_first" type="xsd:string"/>
		<part name="name_last" type="xsd:string"/>
		<part name="name_suffix" type="xsd:string"/>
		<part name="email" type="xsd:string"/>
		<part name="ip" type="xsd:string"/>
		<part name="permanent_resident" type="xsd:string"/>
		<part name="dob" type="xsd:string"/>
		<part name="employer_name" type="xsd:string"/>
		<part name="employer_length" type="xsd:string"/>
		<part name="shift" type="xsd:string"/>
		<part name="direct_deposit" type="xsd:string"/>
		<part name="income_type" type="xsd:string"/>
		<part name="income_stream" type="xsd:string"/>
		<part name="income_monthly_net" type="xsd:string"/>
		<part name="income_frequency" type="xsd:string"/>
		<part name="income_date_1" type="xsd:string"/>
		<part name="income_date_2" type="xsd:string"/>
		<part name="residence_type" type="xsd:string"/>
		<part name="street" type="xsd:string"/>
		<part name="unit" type="xsd:string"/>
		<part name="city" type="xsd:string"/>
		<part name="state" type="xsd:string"/>
		<part name="zip" type="xsd:string"/>
		<part name="phone_home" type="xsd:string"/>
		<part name="phone_cell" type="xsd:string"/>
		<part name="phone_work" type="xsd:string"/>
		<part name="phone_work_ext" type="xsd:string"/>
		<part name="legal_id_number" type="xsd:string"/>
		<part name="legal_id_state" type="xsd:string"/>
		<part name="ref_01_name" type="xsd:string"/>
		<part name="ref_01_phone" type="xsd:string"/>
		<part name="ref_01_relationship" type="xsd:string"/>
		<part name="ref_02_name" type="xsd:string"/>
		<part name="ref_02_phone" type="xsd:string"/>
		<part name="ref_02_relationship" type="xsd:string"/>
	</message>
	<message name="ODS_EnrollmentResponse">
		<part name="result" type="xsd:string"/>
	</message>
	
	<message name="ODS_Transaction_Acknowledgement">
		<part name="session_id" type="xsd:string"/>
		<part name="account_id" type="xsd:string"/>
		<part name="amount" type="xsd:string"/>
		<part name="trans_date" type="xsd:string"/>
	</message>
	<message name="ODS_Transaction_AcknowledgementResponse">
		<part name="result" type="xsd:string"/>
	</message>
	
	
	<message name="is_Valid_Card_Id">
		<part name="session_id" type="xsd:string"/>
		<part name="card_id" type="xsd:string"/>
	</message>
	<message name="is_Valid_Card_IdResponse">
		<part name="result" type="xsd:string"/>
	</message>
	
	
	<message name="Run_Debit_Credit_Transaction">
		<part name="session_id" type="xsd:string"/>
		<part name="card_id" type="xsd:string"/>
		<part name="amount" type="xsd:string"/>
		<part name="transaction_type" type="xsd:string"/>
	</message>
	<message name="Run_Debit_Credit_TransactionResponse">
		<part name="result" type="xsd:string"/>
	</message>
	
	
	<portType name="tssPort">
		<operation name="Start_Session">
			<input  message="tns:Start_Session"/>
			<output message="tns:Start_SessionResponse"/>
		</operation>
	
		<operation name="Close_Session">
			<input  message="tns:Close_Session"/>
			<output message="tns:Close_SessionResponse"/>
		</operation>
	
		<operation name="ODS_Enrollment">
			<input  message="tns:ODS_Enrollment"/>
			<output message="tns:ODS_EnrollmentResponse"/>
		</operation>
	
		<operation name="ODS_Transaction_Acknowledgement">
			<input  message="tns:ODS_Transaction_Acknowledgement"/>
			<output message="tns:ODS_Transaction_AcknowledgementResponse"/>
		</operation>
	
		<operation name="is_Valid_Card_Id">
			<input  message="tns:is_Valid_Card_Id"/>
			<output message="tns:is_Valid_Card_IdResponse"/>
		</operation>
	
		<operation name="Run_Debit_Credit_Transaction">
			<input  message="tns:Run_Debit_Credit_Transaction"/>
			<output message="tns:Run_Debit_Credit_TransactionResponse"/>
		</operation>
	</portType>

	
	<binding name="tssBinding" type="tns:tssPort">

		<soap:binding style="rpc" transport="http://schemas.xmlsoap.org/soap/http"/>

		<operation name="Start_Session">
			<soap:operation soapAction="urn:tss#soap_demo#Start_Session"/>
			<input>
				<soap:body use="encoded" namespace="urn:tss" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"/>
			</input>
			<output>
				<soap:body use="encoded" namespace="urn:tss" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"/>
			</output>
		</operation>

		<operation name="Close_Session">
			<soap:operation soapAction="urn:tss#soap_demo#Close_Session"/>
			<input>
				<soap:body use="encoded" namespace="urn:tss" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"/>
			</input>
			<output>
				<soap:body use="encoded" namespace="urn:tss" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"/>
			</output>
		</operation>

		<operation name="ODS_Enrollment">
			<soap:operation soapAction="urn:tss#soap_demo#ODS_Enrollment"/>
			<input>
				<soap:body use="encoded" namespace="urn:tss" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"/>
			</input>
			<output>
				<soap:body use="encoded" namespace="urn:tss" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"/>
			</output>
		</operation>

		<operation name="ODS_Transaction_Acknowledgement">
			<soap:operation soapAction="urn:tss#soap_demo#ODS_Transaction_Acknowledgement"/>
			<input>
				<soap:body use="encoded" namespace="urn:tss" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"/>
			</input>
			<output>
				<soap:body use="encoded" namespace="urn:tss" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"/>
			</output>
		</operation>

		<operation name="is_Valid_Card_Id">
			<soap:operation soapAction="urn:tss#soap_demo#is_Valid_Card_Id"/>
			<input>
				<soap:body use="encoded" namespace="urn:tss" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"/>
			</input>
			<output>
				<soap:body use="encoded" namespace="urn:tss" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"/>
			</output>
		</operation>

		<operation name="Run_Debit_Credit_Transaction">
			<soap:operation soapAction="urn:tss#soap_demo#Run_Debit_Credit_Transaction"/>
			<input>
				<soap:body use="encoded" namespace="urn:tss" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"/>
			</input>
			<output>
				<soap:body use="encoded" namespace="urn:tss" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"/>
			</output>
		</operation>
	</binding>

	
	<service name="tssService">
		<documentation/>
		<port name="tssPort" binding="tns:tssBinding">
			<soap:address location="http://ds57.tss:8080/wsdl_parser/soap_screen_sample.php"/>
		</port>
	</service>
	
</definitions>

<?php } ?>