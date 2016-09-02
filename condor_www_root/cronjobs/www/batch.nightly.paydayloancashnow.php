<?
//======================================================
//Paydayloancashnow.com nightly batch process. (PLC)
//
// Sends email of 30 of the previous days' leads.
//
//Author: vinh.trinh@thesellingsource.com 05/01/07
//======================================================


define('BFW_CODE_DIR','/virtualhosts/bfw.1.edataserver.com/include/code/');

require_once(BFW_CODE_DIR.'OLP_TX_Mail_Client.php');
require_once(BFW_CODE_DIR.'server.php');
require_once('mysql.4.php');

class Batch
{
	private $server;
	private $sql;
	
	private $property_short = 'plc';
	private $monthly_income = 1500;
	private $direct_deposit = "TRUE";
	private $excluded_states = '"NY","NJ","CT","VT","NC","MD","WV","NH","CO","MO","VA"';
	
	
	public function __construct($mode,$type)
	{
		$this->server = @Server::Get_Server($mode,$type);
		$this->sql = new Mysql_4($this->server['host'],$this->server['user'],$this->server['password']);
		try{
			$this->sql->Connect();
		}
		catch(Exception $e)
		{
			//Do nothing for now
		}
	}

	public function get_winners($property_short,$start_date,$end_date,$limit)
	{
		
		
		$query = "
			SELECT
				per.application_id,
				per.first_name,
				per.last_name,
				per.home_phone,
				per.cell_phone,
				per.email,
				date_format(per.date_of_birth,'%m/%d/%y') as date_of_birth,
				per.contact_id_1,
				per.contact_id_2,
				per.social_security_number,
				per.drivers_license_number,
				res.address_1,
				res.apartment,
				res.city,
				res.state,
				res.zip,
				emp.employer,
				emp.work_phone,
				emp.income_type,
				bi.bank_name,
				bi.account_number,
				bi.routing_number,
				bi.direct_deposit,
				inc.monthly_net,
				inc.pay_frequency,
				date_format(inc.pay_date_1,'%m/%d/%y') as pay_date1,
				date_format(inc.pay_date_2,'%m/%d/%y') as pay_date2
			FROM
				application app
				JOIN	target t ON (t.target_id = app.target_id)
				JOIN	personal per ON (per.application_id = app.application_id)
				JOIN	residence res ON (res.application_id = app.application_id)
				JOIN	employment emp ON (emp.application_id = app.application_id)
				JOIN	paydate pay ON (pay.application_id = app.application_id)
				JOIN	bank_info bi ON (bi.application_id = app.application_id)
				JOIN	income inc ON (inc.application_id = app.application_id)
			WHERE
				app.created_date BETWEEN '{$start_date}' and '{$end_date}' AND
				app.application_type = 'COMPLETED' AND
				t.property_short = '$property_short'
		";

		try{
			$this->sql->Connect();
			$results = $this->sql->Query($this->server['db'],$query);
		}
		catch(Exception $e)
		{
			print_r($e);
			// Do Nothing
		}
		
		// Remove commas from data, this screws up CSV files
		while($row =  $this->sql->Fetch_Array_Row($results))
		{
			foreach($row as $key => $value)
			{
				$row[$key] = str_replace(","," ",$value);
			}
			$return_value[] = $row;
		}
		
		
		return $return_value;
		
	}
	
	public function get_bb_references($contact_id)
	{

		$query = "
			SELECT
				full_name,
				phone,
				relationship
			FROM
				personal_contact
			WHERE
				contact_id = $contact_id
		";
	
	
		try{
			$results = $this->sql->Query($this->server['db'],$query);
		}
		catch(Exception $e)
		{
			// Do Nothing
		}
		
		// Remove Commas from data, this screws up csv files
		while($row =  $this->sql->Fetch_Array_Row($results))
		{
			foreach($row as $key => $value)
			{
				$row[$key] = str_replace(","," ",$value);
			}
			$return_value[] = $row;
		}
		return $return_value;
	}	
}

echo "<pre>";

$environments = array('LIVE','RC','LOCAL');
switch($argv[1])
{
	case 'LOCAL':
		$mode = 'LOCAL';
		$debug = TRUE;
		$days_old = 1;
		break;
	case 'RC':
		$mode = 'RC';
		$debug = TRUE;
		$stats_mode = 'RC';
		$days_old = 1;
		break;
	case 'LIVE':
		$mode = 'SLAVE';
		$debug = FALSE;
		$days_old = 1; // When live this is run at 3am, so get previous day's leads
		break;
	default:
		echo "Please specify an environment (LIVE,RC,LOCAL)\n";
		die;
}

//$start_date = '2007-01-01';
//$end_date ='2008-03-01';
$start_date = date("Ymd000000",time()-$days_old*24*60*60);
$end_date = date("Ymd235959",time()-$days_old*24*60*60);
$limit = 30;
$property_short = 'PLC';

// Get the data
$batch = new Batch($mode,'BLACKBOX');
$leads = $batch->get_winners($property_short,$start_date,$end_date,$limit);

if(!is_array($leads)) 
{
	echo "Found No Leads.\n";
	die;
}
// Format the data
echo "Total Leads: ".sizeof($leads)."\n";
foreach($leads as $lead_number => $lead_info)
{
	$ref1_data = $batch->get_bb_references($lead_info['contact_id_1']);
	$ref2_data = $batch->get_bb_references($lead_info['contact_id_2']);

	$formatted_data[$lead_number]['Campaign'] = "Payday Leads 3";
	$formatted_data[$lead_number]['Lead Type'] = "Payday";	
	$formatted_data[$lead_number]['ID'] = $lead_info['application_id'];	
	$formatted_data[$lead_number]['State Selection'] = $lead_info['state'];
	$formatted_data[$lead_number]['Working In US'] = "Yes"; // US Citizen & 18 yrs old	
	$formatted_data[$lead_number]['Monthly Income'] = $lead_info['monthly_net'];
	$formatted_data[$lead_number]['Has Bank Account'] = "Checking";
	$formatted_data[$lead_number]['Pay Period'] = $lead_info['pay_frequency'];	
	$formatted_data[$lead_number]['Pay Date 1'] = $lead_info['pay_date1'];
	$formatted_data[$lead_number]['Pay Date  2'] = $lead_info['pay_date2'];	
	$formatted_data[$lead_number]['First Name'] = $lead_info['first_name'];
	$formatted_data[$lead_number]['Last Name'] = $lead_info['last_name'];
	$formatted_data[$lead_number]['SSN'] = $lead_info['social_security_number'];	
	$formatted_data[$lead_number]['DOB'] = $lead_info['date_of_birth'];
	$formatted_data[$lead_number]['DL#'] = $lead_info['drivers_license_number'];

	$formatted_data[$lead_number]['Address'] = $lead_info['address_1'];
	$formatted_data[$lead_number]['Apt'] = $lead_info['apartment'];
	$formatted_data[$lead_number]['City'] = $lead_info['city'];
	$formatted_data[$lead_number]['Zip'] = $lead_info['zip'];
	$formatted_data[$lead_number]['Email'] = $lead_info['email'];
	$formatted_data[$lead_number]['Home Phone'] = $lead_info['home_phone'];
	$formatted_data[$lead_number]['Housing'] = "Not Available";	
	$formatted_data[$lead_number]['Requested Loan Amount'] = "1500";		
	$formatted_data[$lead_number]['Best Time To Call'] = "Not Available";
	$formatted_data[$lead_number]['Months at Residence'] = "Not Available";	
	$formatted_data[$lead_number]['Income Source'] = "Employment"; // Source of Income
	$formatted_data[$lead_number]['Occupation'] = "Not Available";
	$formatted_data[$lead_number]['Employer'] = $lead_info['employer'];	
	$formatted_data[$lead_number]['Supervisor Name'] = "Not Available"; 
	$formatted_data[$lead_number]['Supervisor Phone'] = "Not Available";
	
	$formatted_data[$lead_number]['Work Phone'] = $lead_info['work_phone'];
	$formatted_data[$lead_number]['Months Employed'] = "More than 3";	
	$formatted_data[$lead_number]['Bank Name'] = $lead_info['bank_name'];	
	$formatted_data[$lead_number]['ABA #'] = $lead_info['routing_number'];	
	$formatted_data[$lead_number]['Account #'] = $lead_info['account_number'];		
	$formatted_data[$lead_number]['Bank Phone'] = "Not Available";
	$formatted_data[$lead_number]['Direct Deposit'] = "Yes";	
	$formatted_data[$lead_number]['Next Payday'] = $lead_info['pay_date1'];	
	$formatted_data[$lead_number]['Driving License State'] = $lead_info['state'];
	$formatted_data[$lead_number]['Ref1 Name'] = $ref1_data[0]['full_name'];
	$formatted_data[$lead_number]['Ref1 Relationship'] = $ref1_data[0]['relationship'];
	$formatted_data[$lead_number]['Ref1 Phone'] = $ref1_data[0]['phone'];
	$formatted_data[$lead_number]['Ref2 Name'] = $ref2_data[0]['full_name'];
	$formatted_data[$lead_number]['Ref2 Relationship'] = $ref2_data[0]['relationship'];
	$formatted_data[$lead_number]['Ref2 Phone'] = $ref2_data[0]['phone'];
	
}
if($formatted_data)
{
//Prepare Email
$first_time = 1;
foreach($formatted_data as $id)
{

	foreach($id as $name => $value)
	{
		if($first_time){
			//$csv_header .= $name.",";
		}
		$attachment .= $name.": ".$value."\n";
		//$csv_body .= $value.",";
	}
	
	unset($first_time);
	$attachment .= "\n\n";
	//$csv_body .= "\n";
}

//$csv = $csv_header."\n".$csv_body;

//For trendex
$tx = new OlpTxMailClient(false,$mode);

//For Ole
//$prpc = new Prpc_Client('prpc://smtp.2.soapdataserver.com/ole_smtp.1.php');


$header = array(
	"sender_name"	=> "The Selling Source <no-reply@sellingsource.com>",
	"subject"		=> "Leads to Paydayloanscashnow for " . date("m-d-Y",time()),
	"site_name" 		=> "sellingsource.com",
	"message"		=> "Leads to Paydayloanscashnow for " . date("m-d-Y",time())
	);

/* Used For Trendex */
$attach = array(
	'method' => 'ATTACH',
	'filename' => 'paydayloancashnow.txt',
	'mime_type' => 'text/plain',
	'file_data' => gzcompress($attachment),
	'file_data_size' => strlen($attachment),
);



if($debug == TRUE)
{
	$recipients = array( 
		
		array("email_primary_name" => "Pennie Dade", "email_primary" => "pennied@partnerweekly.com"),
		array("email_primary_name" => "Pennie Dade", "email_primary" => "kalimakai@gmail.com"),
		array("email_primary_name" => "Management", "email_primary" => "management@paydayloancashnow.com"),
		array("email_primary_name" => "Pricila Arceo", "email_primary" => "pricila@paydayloancashnow.com"),
		array("email_primary_name" => "Pricila Arceo", "email_primary" => "Info2@cashusaonline.com"),
		array("email_primary_name" => "Vinh Trinh", "email_primary" => "vinh.trinh@sellingsource.com"),
		array("email_primary_name" => "Peter Finn", "email_primary" => "Peter.Finn@PartnerWeekly.com"),
		array("email_primary_name" => "Pricila Arceo", "email_primary" => "pricila.arceo@gmail.com"),				
//		array("email_primary_name" => "Test Email", "email_primary" => "adam.englander@sellingsource.com"),				
	);
}
else 
{
	$recipients = array(
		array("email_primary_name" => "Vinh Trinh", "email_primary" => "vinh.trinh@sellingsource.com"),	
		array("email_primary_name" => "Management", "email_primary" => "management@paydayloancashnow.com"),
		array("email_primary_name" => "Pricila Arceo", "email_primary" => "pricila.arceo@gmail.com"),
		array("email_primary_name" => "Pricila Arceo", "email_primary" => "pricila@paydayloancashnow.com"),
	);
}

// Send Email
foreach($recipients as $r)
{
	$data = array_merge($r,$header);
	
	//For Ole;
	//$data['attachment_id'] = $prpc->Add_Attachment($attachment,'text/plain','paydayloancashnow.txt',"ATTACH");
	
	//For Trendex
	$result = $tx->sendMessage('live', 'CRON_EMAIL', $data['email_primary'], '', $data, array($attach));

	//For OLE
	//$result = $prpc->Ole_Send_Mail("PDDLEADS_CRON",17176,$data);
	
	
	if($result)
	{
		print "EMAIL HAS BEEN SENT TO: ".$r['email_primary']." \n";
	}
	else
	{
		print "ERROR SENDING EMAIL TO: ".$r['email_primary']." \n";
	}

}
}

echo "</pre>";
?>