<?PHP
			//Set the doc root
			$outside_web_space = realpath ("../")."/";
			$inside_web_space = realpath ("./")."/";
			define ("OUTSIDE_WEB_SPACE", $outside_web_space);
			define ("DATABASE", "rc_expressgoldcard");
			
			//Mysql 3
			require_once ("/virtualhosts/lib/debug.1.php");
			require_once ("/virtualhosts/lib/error.2.php");
			require_once ("/virtualhosts/lib/mysql.3.php");
			require_once ("/virtualhosts/lib/crypt.1.php");
			
			//Old Sql,... Will Remove Later
			include_once ("/virtualhosts/cronjobs/includes/load_balance.mysql.class.php"); // Database wrapper
						
			//XMLRPC
			require_once("/virtualhosts/lib/xmlrpc_client.2.php");
			 			
			//Stats
			require_once("/virtualhosts/lib/setstat.1.php");
			
			//Billing
			require_once("egc_billing.class.php");
				
			$server = new stdClass ();
			$server->cluster1 = new stdClass ();
			$server->cluster1->host = "read1.iwaynetworks.net";
			$server->cluster1->user = "sellingsource";
			$server->cluster1->pass = "%selling\$_db";
			
			$db_object = new stdClass();
			$db_object->CUSTOMER->read = "read2.iwaynetworks.net";
			$db_object->CUSTOMER->write = "write2.iwaynetworks.net";
			$db_object->CUSTOMER->user = "sellingsource";
			$db_object->CUSTOMER->pass = "%selling\$_db";
			$db_object->CUSTOMER->db = "rc_expressgoldcard";
			$db_object->CUSTOMER->port = 3306;
			
			define(CUSTOMER_READ_HOST, $db_object->CUSTOMER->read);
			define(CUSTOMER_WRITE_HOST, $db_object->CUSTOMER->write);
			define(CUSTOMER_USER, $db_object->CUSTOMER->user);
			define(CUSTOMER_PASS, $db_object->CUSTOMER->pass);
			define(CUSTOMER_DB, $db_object->CUSTOMER->db);
			define(CUSTOMER_PORT, $db_object->CUSTOMER->port);			
			
			$customer_sql = new MySQL (CUSTOMER_READ_HOST, CUSTOMER_WRITE_HOST, CUSTOMER_USER, CUSTOMER_PASS, CUSTOMER_DB, CUSTOMER_PORT, "\t".__FILE__."->".__LINE__."\n");
				
			$soap_server_path = "/";
			$soap_server_url = "ucl.soapdataserver.com";
			$soap_server_port = 80;
			
			// Connection Info
			define('SSO_SOAP_SERVER_PATH', '/');
			define('SSO_SOAP_SERVER_URL', 'smartshopperonline.soapdataserver.com');
			define('SSO_SOAP_SERVER_PORT', 80);
		
			// SOAP constants.
			define ("SOAP_SERVER_PATH", $soap_server_path);
			define ("SOAP_SERVER_URL", $soap_server_url);
			define ("SOAP_SERVER_PORT", $soap_server_port);
			define ("OLP_FRONTSIDE_SOAP_FAULT_ID", 9999);

			// Create sql connection(s)
			$sql = new stdClass ();

			foreach ($server as $name => $info)
			{
				$sql->$name = new MySQL_3 ();
				$result = $sql->$name->Connect (NULL, $info->host, $info->user, $info->pass, Debug_1::Trace_Code (__FILE__, __LINE__));
				Error_2::Error_Test ($result, TRUE);
			}
			
					
			//Include the billing module
			// Process Billing
			//$billing = new Billing_Profile_1($sql,DATABASE);
						
			/*
			$date_approved = date("Y-m-d", strtotime("-13 day"));
			$date_stat = date("Y-m-d", strtotime("-20 day"));
			*/
			
			$date_approved = date("Y-m-d", strtotime("-13 day"));
			$date_stat = date("Y-m-d", strtotime("-13 day"));

			// If 14+ days old, add to file
			$query = "SELECT account.cc_number, DATE_FORMAT(account.sign_up, '%Y-%m-%d') as sign_up,
			customer.first_name, customer.last_name, customer.address_1, customer.address_2,
			customer.city, customer.state, customer.zip
			FROM account,customer WHERE customer.cc_number = account.cc_number AND
			account.account_status='PENDING' AND account.sign_up < '".$date_approved."' AND account.sign_up != '00000000000000'";

			$info = $customer_sql->Wrapper($query, "", "\t".__FILE__."->".__LINE__."\n");
			$package_14day = "CC NUMBER, FIRST NAME, LAST NAME, ADDRESS, ADDRESS 2, CITY, STATE, ZIP, SIGN UP\n";
			$desc_comment = "[Status][Package]: Status moved to PACKAGE";

			foreach($info as $record)
			{
				$package_14day .= $record->cc_number.",".$record->first_name.",".$record->last_name.",".$record->address_1.",".$record->address_2.",".$record->city.",".$record->state.",".$record->zip.",".$record->sign_up."\n";

				$cc_list .= "'".$record->cc_number."',";
				$comment_values .= "('".$record->cc_number."', '', NOW(), NOW(), '0', '".$desc_comment."', ''),";
				
				//Process Billing
				//include ('egc_billing.php');
			}

			/*
			$query = "UPDATE account SET account_status = 'PACKAGE' WHERE cc_number in (".substr ($cc_list, 0, -1).")";
			$sql->cluster1->Query (DATABASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));

			$query = "insert into comments values ".substr ($comment_values, 0, -1);
			$sql->cluster1->Query (DATABASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
			
			$query = "INSERT INTO batch_file  (file, origination_date, employee_id, batch_type, total_checks, total_amount) VALUES('".base64_encode ($package_14day)."',NOW(),'0','EGC PACKAGE','0','0')";
			$sql->cluster1->Query (DATABASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
			*/

			// If 21+ days old, add to file
			$date_stat = date('Y-m-d');
			
			$query = "SELECT account.cc_number, DATE_FORMAT(account.sign_up, '%Y-%m-%d') as sign_up,
			customer.first_name, customer.last_name, customer.address_1, customer.address_2,
			customer.city, customer.state, customer.zip FROM account,customer WHERE customer.cc_number = account.cc_number AND
			account.account_status = 'PACKAGE' AND account.sign_up < '".$date_stat."' AND account.sign_up != '00000000000000'";
			
			$info = $customer_sql->Wrapper($query, "", "\t".__FILE__."->".__LINE__."\n");
			
			$query = "SELECT COUNT( customer.promo_id )  AS counter, customer.promo_id, customer.promo_sub_code FROM account, customer WHERE customer.cc_number = account.cc_number AND account.cc_number IN ( 9038303044156557, 9038224840664264, 9038684262846446, 9038541084165814, 9038080030200778, 9038098105431788, 9038186405779898, 9038222080406244, 9038862022424066, 9038594751200377, 9038020802840802, 9038006864428464, 9038402264644644, 9038809748777045, 9038600662866026, 9038440688646004, 9038174900718855, 9038608488044444, 9038624483000397, 9038242228828022, 9038433806377169, 9038866666242200, 9038466406246026, 9038210174670916, 9038808064060028, 9038927898670610, 9038568561189692, 9038391768949185, 9038000622424284, 9038615486011615, 9038288822048002, 9038907910970560, 9038834394851296, 9038099248770591, 9038606264062060, 9038408622406408, 9038086268068446, 9038917451651210, 9038620444228428, 9038716972079978, 9038440648424204, 9038082048262002, 9038662576888770, 9038816176685293, 9038515114968769, 9038200460848606, 9038370198692042, 9038240402004242, 9038460282084028, 9038024175432170, 9038606684642444, 9038166883688031, 9038060868402260, 9038864868808604, 9038648262042284, 9038068864242424, 9038642024868200, 9038301274391904, 9038668000828424, 9038963097626314, 9038809149577462, 9038264624488602, 9038844240206606, 9038046624242486, 9038486202602682, 9038664384238862, 9038542844570727, 9038824442406064, 9038067094451042, 9038024822402804, 9038462240886220, 9038881387782425, 9038624840200486, 9038266646466668, 9038482406004682, 9038420402628062, 9038672035773247, 9038448180526927  )  AND account.sign_up <  '2003-05-09' AND account.sign_up !=  '00000000000000' GROUP  BY customer.promo_id, customer.promo_sub_code";
			
			$statcount = $customer_sql->Wrapper($query, "", "\t".__FILE__."->".__LINE__."\n");
						
			foreach($statcount as $count_hit)
			{
				$promo_id = $count_hit->promo_id;
				$promo_sub_code = $count_hit->promo_sub_code;
				$column = 'approved';
				$value = $count_hit->counter;
				
				$promo_status = new stdclass();
				$promo_status->valid = "valid";
				
				$base = "egc_stat";
				
				$stat_data = Set_Stat_1::Setup_Stats('1833', '0', '1835', $promo_id, $promo_sub_code, $sql->cluster1, $base, $promo_status->valid, $batch_id = NULL);
				Set_Stat_1::Set_Stat ($stat_data->block_id, $stat_data->tablename, $sql->cluster1, $base, $column, $value);
			}
			
			$package_21day = "CC NUMBER, FIRST NAME, LAST NAME, ADDRESS, ADDRESS 2, CITY, STATE, ZIP, SIGN UP\n";
			$desc_comment = "[Status][Package]: Status moved to INACTIVE";
			
			foreach($info as $record)
			{
				$package_21day .= $record->cc_number.",".$record->first_name.",".$record->last_name.",".$record->address_1.",".$record->address_2.",".$record->city.",".$record->state.",".$record->zip.",".$record->sign_up."\n";

				$cc_list2 .= "'".$record->cc_number."',";
				$comment_values2 .= "('".$record->cc_number."', '', NOW(), NOW(), '0', '".$desc_comment."', ''),";
			}

			if(strlen($cc_list2))
			{
				/*
				$query = "UPDATE account SET account_status='INACTIVE', activation = NOW() WHERE cc_number IN (".substr ($cc_list2, 0, -1).")";
				$approved_update = $customer_sql->Wrapper($query, "", "\t".__FILE__."->".__LINE__."\n");

				$query = "insert into comments values ".substr ($comment_values2, 0, -1);
				$sql->cluster1->Query (DATABASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
				*/
			}
			
			/*
			$query = "INSERT INTO batch_file  (file, origination_date, employee_id, batch_type, total_checks, total_amount) VALUES('".base64_encode ($package_21day)."',NOW(),'0','EGC INACTIVE','0','0')";
			$sql->cluster1->Query (DATABASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
			*/

			// ** Start Approval Process ** //

			ini_set ("magic_quotes_runtime", 0);

			define ('MIN_BUSINESS_DAYS', 14);
			/*
			$crypt = new Crypt_1 ();
						
			// Create the xmlrpc_client
			$soap_client = new xmlrpc_client (SSO_SOAP_SERVER_PATH, SSO_SOAP_SERVER_URL, SSO_SOAP_SERVER_PORT);
			*/
			
			// Build the holidays array
			$result = $sql->cluster1->Query ("d2_management", "select * from holidays", Debug_1::Trace_Code (__FILE__, __LINE__));
			Error_2::Error_Test ($result, TRUE);

			$holidays = array ();
			while ($row = $sql->cluster1->Fetch_Object_Row ($result))
			{
				$holidays[$row->date] = TRUE;
			}

			// Calculate the cut off day
			$now = time ();

			$today = mktime (0, 0, 0, date ("n", $now), date ("j", $now), date ("Y", $now));

			$day = $today;
			$days_passed = 0;

			while ($days_passed < MIN_BUSINESS_DAYS)
			{
				$day = strtotime ("-1 day", $day);
				$days_passed++;
			}

			// Get orders that should be considered "approved" now
			$query = "select transaction.transaction_id , transaction.cc_number, transaction.ach_routing_number as routing_number, transaction.ach_account_number as acctno,  customer.first_name, customer.last_name, customer.email, customer.address_1 as address1 , customer.address_2 as address2, customer.city, customer.state, customer.zip, customer.ssn, DATE_FORMAT(account.sign_up, '%Y-%m-%d') as sign_up".
				" from transaction, customer, account ".
				"where transaction.transaction_status = 'SENT' and transaction.transaction_type = 'ENROLLMENT' ".
				"and transaction.cc_number = customer.cc_number and account.cc_number = customer.cc_number and account.account_status = 'PACKAGE'";

			$result = $sql->cluster1->Query (DATABASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
			Error_2::Error_Test ($result, TRUE);

			while ($row = $sql->cluster1->Fetch_Object_Row ($result))
			{

				$row->first_name = ucwords(strtolower($row->first_name));
				$row->last_name = ucwords(strtolower($row->last_name));
				$row->email = strtolower($row->email);
				
							
				//Create the Smart Shopper Account
				$soap_args = array (
					"firstname" => $row->first_name,
					"lastname" => $row->last_name,
					"email" => $row->email,
					"address_line_1" => $row->address1,
					"address_line_2" => $row->address2,
					"address_city" => $row->city,
					"address_state" => $row->state,
					"address_zip" => $row->zip,
					"password_hash" => trim($crypt->Encrypt(substr($row->ssn, -4), 'rodric.nick')),
					"egc_number" => $row->cc_number,
					"ach_routing" => $row->routing_number,
					"ach_account" => $row->acctno,
					"social_security" => preg_replace ("/[^\d]/", "", $row->ssn)
				);
				
				//$soap_client->setDebug (1);

			/*
				$soap_call = new xmlrpcmsg ("Create_Account", array (php_xmlrpc_encode ($soap_args)));

				$soap_result = $soap_client->send ($soap_call);

				if ($soap_result->faultCode ())
				{
					echo "SOAP Fault:".$soap_result->faultCode ().":".$soap_result->faultString ()."\n";
				}
				*/
				
				
				//Set their status
				/*
				$query = "update transaction set transaction_status = 'APPROVED', recieve_batch_date = NOW() where transaction_id = '".$row->transaction_id."' limit 1";
				$sql->cluster1->Query (DATABASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
			*/
				
				// Build their email
				$first_name = $row->first_name;
				$last_name = $row->last_name;
				$user_name = $row->email;
				$password = substr($row->ssn, -4);
				$card = chunk_split ($row->cc_number, 4, " ");
$email = "

Dear $first_name $last_name,

Welcome to Express Gold Card! Your bank account has been successfully debited for $9.95.  You can expect to receive your Express Gold Card with a $7500 credit line in the mail within the next 3 days.  You can however begin shopping right away at www.SmartShopperOnline.com by using the following information when you log on:

Username: $user_name
Password: $password

Express Gold Card Number: $card

We do ask that before you purchase anything from SmartShopperOnline.com you do first go over our terms and conditions, which can be found at http://www.expressgoldcard.com/terms.html.

If you should have any questions regarding this email or your Express Gold Card please email us at info@expressgoldcard.com.";


				//mail ($row->email, "Welcome to Express Gold Card!", $email, "From: Express Gold Card <info@expressgoldcard.com>");
				mail ("nickw@sellingsource.com", "Welcome to Express Gold Card!", $email, "From: Express Gold Card <info@expressgoldcard.com>");

				/*
				$query = "insert into sent_documents set send_date = NOW(),  send_time = NOW(), cc_number = '".$row->cc_number."' , document_name = 'EGC_Approved' , user_id = '0', method = 'EMAIL' ";
				$sql->cluster1->Query (DATABASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
				*/
			}

			$outer_boundry = md5 ("LlamaLlamaLlama");

			$headers =
				"From: noreply <noreply@expressgoldcard.com>\r\n".
				"MIME-Version: 1.0\r\n".
				"Content-Type: Multipart/Mixed;\r\n boundary=\"".$outer_boundry."\"\r\n".
				"This is a multi-part message in MIME format.\r\n\r\n";

			$csv_email =
				"--".$outer_boundry."\r\n".
				"Content-Type: text/plain;\r\n".
				" charset=\"us-ascii\"\r\n".
				"Content-Transfer-Encoding: 7bit\r\n".
				"Content-Disposition: inline\r\n\r\n".
				"Approved Express Gold Cards for ".date("Y-m-d h:i:s")."\r\n".
				"--".$outer_boundry."\r\n".
				"Content-Type: text/plain;\r\n".
				" charset=\"us-ascii\"\r\n".
				" name=\"InactiveReport_21Day - ".date ("md")."\"\r\n".
				"Content-Transfer-Encoding: 7bit\r\n".
				"Content-Disposition: attachment; filename=\"InactiveReport_21Day - ".date ("md").".txt\"\r\n\r\n".
				$package_21day."\r\n".
				"--".$outer_boundry."--\r\n\r\n";

			mail ("nickw@sellingsource.com", "Express Gold Card Approved List - ".date("Y-m-d h:i:s"), $csv_email, $headers);
			//mail ("ndempsey@41cash.com", "Express Gold Card Approved List - ".date("Y-m-d h:i:s"), $csv_email, $headers);
			//mail ("approval-department@expressgoldcard.com", "Express Gold Card Approved List - ".date("Y-m-d h:i:s"), $csv_email, $headers);
			//mail ("kayshah@sellingsource.com", "Express Gold Card Approved List - ".date("Y-m-d h:i:s"), $csv_email, $headers);
?>
