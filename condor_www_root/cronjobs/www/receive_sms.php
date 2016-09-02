<?php
/**
 * Grabs received SMS messages in the past hour from the MSKG database and
 * emails the response to the company email and temporarily to
 * Crystal@FC500.com.
 *
 * @author Brian Feaver
 * @version 1.0.0
 */

define('OLE_PROPERTY_ID', 17176);

require_once('mysql.4.php');
require_once('mysqli.1.php');
require_once('prpc/client.php');

$sms_server = array(
	'hostname' => '71.4.57.83',
	'username' => 'tss',
	'password' => 'password',
	'database' => 'sms_rt'
);

$ldb_server = array(
	'hostname' => 'db3',
	'username' => 'olp',
	'password' => 'password',
	'database' => 'ldb'
);

$company_email_map = array(
	'ca' => array(	'name' => 'Ameriloan',
					'email' => 'customerservice@ameriloan.com'),
	'd1' => array(	'name' => '500FastCash',
					'email' => 'customerservice@500fastcash.com'),
	'pcl' => array(	'name' => 'OneClickCash',
					'email' => 'customerservice@oneclickcash.com'),
	'ucl' => array(	'name' => 'United Cash Loans',
					'email' => 'customerservice@unitedcashloans.com'),
	'ufc' => array(	'name' => 'USFastCash',
					'email' => 'customerservice@usfastcash.com')
);

$set_time = "SET time_zone = '-8:00'";

// Retrieve all the received SMS messages within the past hour
$query = "
	SELECT
		Cell_Phone,
		Message,
		Company_ID
	FROM
		Received
	WHERE
		Date_Received > DATE_SUB(NOW(), INTERVAL 1 HOUR)";

try
{
	$sql = new MySQL_4($sms_server['hostname'], $sms_server['username'], $sms_server['password']);
	$sql->Connect();

	// Set the time zone first
	$sql->Query($sms_server['database'], $set_time);

	$result = $sql->Query($sms_server['database'], $query);

	while(($row = $sql->Fetch_Array_Row($result)))
	{
		$messages[] = array(
			'cell_phone' => $row['Cell_Phone'],
			'message' => $row['Message'],
			'company_id' => strtolower(trim($row['Company_ID']))
		);
	}

	$sql->Free_Result($result);

	$sql->Close_Connection();
}
catch(Exception $e)
{
	echo $e->getMessage();
}

if($messages)
{
	// Pull additional info (app_id and status) from the ldb database.
	try
	{
		$sqli = new MySQLi_1($ldb_server['hostname'], $ldb_server['username'], $ldb_server['password'], $ldb_server['database']);

		for($i = 0; $i < count($messages); $i++)
		{
			$cell_phone = $messages[$i]['cell_phone'];

			$query = "
				SELECT
					a.application_id,
					asf.level0_name AS status
				FROM
					application a
					JOIN status_history sh ON a.application_id = sh.application_id
					JOIN application_status_flat asf ON sh.application_status_id = asf.application_status_id
				WHERE
					a.phone_cell = '$cell_phone'
					AND sh.status_history_id = (
						SELECT
							MAX(sh2.status_history_id)
						FROM
							status_history sh2
						WHERE
							sh2.application_id = a.application_id
					)";

			$result = $sqli->Query($query);

			while(($row = $result->Fetch_Array_Row()))
			{
				$messages[$i]['application_info'][] = array(
					'application_id' => $row['application_id'],
					'status' => $row['status']
				);
			}
		}
	}
	catch(Exception $e)
	{
		echo $e->getMessage();
	}

	// Initialize PRPC/mail client
	$mail = new prpc_client('prpc://smtp.2.soapdataserver.com/ole_smtp.1.php');

	foreach($messages as $message)
	{
		$application_send_info = "";

		// Only send emails out if there is an application
		if(isset($message['application_info']))
		{
			foreach($message['application_info'] as $application)
			{
				$application_id = $application['application_id'];
				$status = $application['status'];

				$ecash_link = "http://ecash.edataserver.com/?module=funding&action=show_applicant&application_id=$application_id";
				$application_send_info .= "Application #: <a href=\"$ecash_link\">$application_id</a>, Status: $status<br>\n";
			}

			$recipients = array(
				array(	'email_primary_name' => $company_email_map[$message['company_id']]['name'],
						'email_primary' => $company_email_map[$message['company_id']]['email']),
				array(	'email_primary_name' => 'Crystal',
						'email_primary' => 'crystal@fc500.com')
			);
//			$recipients = array(
//				array(	'email_primary_name' => $company_email_map[$message['company_id']]['name'],
//						'email_primary' => 'brian.feaver@sellingsource.com')/*,
//				array(	'email_primary_name' => $company_email_map[$message['company_id']]['name'],
//						'email_primary' => 'mike.genatempo@sellingsource.com')*/
//			);

			$cell_phone = substr($message['cell_phone'], 0, 3).'-'.substr($message['cell_phone'], 3, 3).'-'.substr($message['cell_phone'], 6, 4);

			$data = array(
				'site_name' => 'sellingsource.com',
				'sender_name' => 'SMS Received <no-reply@sellingsource.com>',
				'subject' => "SMS Received from $cell_phone",
				'cell_phone' => $cell_phone,
				'message' => $message['message'],
				'company_id' => $message['company_id'],
				'application_ids' => $application_send_info
			);

			foreach($recipients as $recipient)
			{
				$send_data = array_merge($recipient, $data);

				$mail->Ole_Send_Mail('SMS_RECEIVED', OLE_PROPERTY_ID, $send_data);
			}
		}
	}
}

?>