<?php
/*********************
	THIS WAS MOVED TO ECASH 6/15/04 SO IF YOU USE THIS CODE LET KANSAS KNOW
	NOTE PUT HERE 06/15/04 - SW
**********************/

require_once ("/virtualhosts/lib/xmlrpc.1.php");
require_once ("/virtualhosts/lib/db2.1.php");
require_once ("/virtualhosts/lib/qualify.1.php");
require_once ("/virtualhosts/lib/db2_table_names.php");

class Document_Handler_1
{
	var $copia_host;
	var $copia_port;
	var $copia_path;
	var $testing_email;
	var $testing_fax;
	
	function Document_Handler_1 ($db2_object)
	{
		// How to connect to copia
		$this->copia_host = "www2.nationalmoneyonline.com";
		$this->copia_port = 80;
		$this->copia_path = "/rc.copia/copia.php";
		
		// How to connect to db2
		$this->db_handler = $db2_object;
		
		$this->testing_email = "brad.miller@thesellingsource.com";	
		
		$this->testing_fax = "8885493117";
		
		return true;
	}
	
	function Get_Customer_Details ($transaction_id, $send_email_id, $send_fax_id)
	{
		// Build the customer object that will be sent to copia

		// Modify some of the parameters for a forced email or fax id
		if (is_null ($send_email_id))
		{
			// Use the default
			$email_id = "AND trans.active_email_id = eaddr.email_id";
		}
		else
		{
			// Set the email to the id passed to us
			$email_id = "AND eaddr.email_id = ".$send_email_id;
		}
		
		if (is_null ($send_fax_id))
		{
			// Use the default
			$fax_id = "
						AND EXISTS
						(
							SELECT
								*
							FROM
								".REFERENCE_PHONE_TYPE." ptype
							WHERE
								phone.phone_type_id = ptype.phone_type_id
								AND ptype.name='FAX'
						)";
		}
		else
		{
			// We got one, use it
			$fax_id = "";
		}
		// Start with getting data from the database
		$master_query = "
			SELECT
				transaction_id as application_id,
				decimal (fund_actual,8,2) as fund_amount,
				date_fund_estimated as fund_date,
				date_first_payoff as estimated_payoff_date,
				bank_name,
				accounttype as bank_account_type,
				bank_account as account_number,
				bank_aba as routing_number,
				check_number,
				income_direct_deposit,
				src.name as income_type,
				decimal (income_monthly,8,2) as monthly_net_pay,
				freq.name as pay_frequency,
				income_date_one as pay_date_1,
				income_date_two as pay_date_2,
				(
					SELECT 
						phone_number
					FROM
						".CUSTOMER_PHONE." phone
					WHERE
						phone.customer_id=customer_id
						AND EXISTS
						(
							SELECT
								*
							FROM
								".REFERENCE_PHONE_TYPE." ptype
							WHERE
								phone.phone_type_id = ptype.phone_type_id
								AND ptype.name='HOME'
						)
					ORDER BY
						date_modified desc
					FETCH FIRST 1 ROWS ONLY
				) as home_phone,
				(
					SELECT 
						phone_number
					FROM
						".CUSTOMER_PHONE." phone
					WHERE
						phone.customer_id=customer_id
						".$fax_id."
					ORDER BY
						date_modified desc
					FETCH FIRST 1 ROWS ONLY
				) as fax_phone,
				rtrim(name_first) as first_name,
				rtrim(name_middle) as middle_name,
				rtrim(name_last) as last_name,
				eaddr.email_address as email,
				date_birth as date_of_birth,
				social_security_number,
				(((YEAR(CURRENT_DATE)-YEAR(addr.date_occupied)) * 12)+ABS(MONTH(CURRENT_DATE)-MONTH(addr.date_occupied))) as length_of_residence,
				addr_owner.name as residence_type,
				street as address_1,
				city,
				st.name as state,
				zip,
				unit as apartment,
				emp.name,
				phne.phone_number as work_phone,
				phne.phone_extension as work_ext,
				emp.title
			FROM
				".COMPANY_TRANSACTION." trans,
				".REFERENCE_INCOME_FREQUENCY." freq,
				".REFERENCE_INCOME_SOURCE." src,
				".REFERENCE_ADDRESS_OWNERSHIP." addr_owner,
				".CUSTOMER_CUSTOMER." cust,
				".CUSTOMER_EMPLOYMENT." emp,
				".CUSTOMER_EMAIL." eaddr,
				".CUSTOMER_ADDRESS." addr,
				".REFERENCE_STATE." st,
				".CUSTOMER_PHONE." phne
			WHERE
				transaction_id = ".$transaction_id."
				AND trans.income_frequency_id = freq.income_frequency_id
				AND trans.income_source_id = src.income_source_id 
				AND trans.customer_id = cust.customer_id
				AND trans.active_address_id = addr.address_id
				AND trans.active_employment_id = emp.employment_id
				".$email_id."
				AND addr.state_id = st.state_id
				AND emp.active_phone_id = phne.phone_id
				AND addr.address_ownership_id = addr_owner.address_ownership_id
				AND freq.income_frequency_id = trans.income_frequency_id";

		$result = $this->db_handler->Execute ($master_query);
		Error_2::Error_Test ($result, TRUE);
		$data_object = $result->Fetch_Object ();

		// convert to customer_object
		$customer_map = array 
		(
			"application_id" => "applicant",
			"fund_amount" => "loan_note",
			"fund_date" => "loan_note",
			"estimated_payoff_date" => "loan_note",
			"first_name" => "personal",
			"middle_name" => "personal",
			"last_name" => "personal",
			"home_phone" => "personal",
			"fax_phone" => "personal",
			"email" => "personal",
			"date_of_birth" => "personal",
			"social_security_number" => "personal",
			"residence_type" => "residence",
			"length_of_residence" => "residence",
			"address_1" => "residence",
			"address_2" => "residence",
			"city" => "residence",
			"state" => "residence",
			"zip" => "residence",
			"apartment" => "residence",
			"bank_name" => "bank_info",
			"account_number" => "bank_info",
			"routing_number" => "bank_info",
			"check_number" => "bank_info",
			"direct_deposit" => "bank_info",
			"bank_account_type" =>"bank_info",
			"employer" => "employment",
			"work_phone" => "employment",
			"work_ext" => "employment",
			"title" => "employment",
			"shift" => "employment",
			"income_type" => "employment",
			"net_pay" => "income",
			"pay_frequency" => "income",
			"pay_date_1" => "income",
			"pay_date_2" => "income",
			"monthly_net_pay" => "income"
		);

		foreach ($data_object as $name => $value)
		{
			$work_name = strtolower ($name);
			switch ($customer_map [$work_name])
			{
				case "applicant":
					$customer_object->applicant->{$work_name} = $value;
					$customer_map [$work_name] = TRUE;
					break;
				case "loan_note":
					$customer_object->loan_note->{$work_name} = $value;
					$customer_map [$work_name] = TRUE;
					break;
				case "personal":
					$customer_object->personal->{$work_name} = $value;
					$customer_map [$work_name] = TRUE;
					break;
				case "residence":
					$customer_object->residence->{$work_name} = $value;
					$customer_map [$work_name] = TRUE;
					break;
				case "bank_info":
					$customer_object->bank_info->{$work_name} = $value;
					$customer_map [$work_name] = TRUE;
					break;
				case "employment":
					$customer_object->employment->{$work_name} = $value;
					$customer_map [$work_name] = TRUE;
					break;
				case "income":		
					$customer_object->income->{$work_name} = $value;
					$customer_map [$work_name] = TRUE;
					break;
			}
		}
		 
		// Calculate the needed values (apr, finance charge, total payments, net_pay(per check))
		$qualify = new Qualify_1 (NULL, NULL);
//echo 'payoff:: '.strtotime ($customer_object->loan_note->estimated_payoff_date).'\n\n fund_date::'. strtotime ($customer_object->loan_note->fund_date).'\n\n fund_amount::'. $customer_object->loan_note->fund_amount ;
		$qualify_results = $qualify->Calculate_Loan_Info (strtotime ($customer_object->loan_note->estimated_payoff_date), strtotime ($customer_object->loan_note->fund_date), $customer_object->loan_note->fund_amount);
		$customer_object->loan_note->apr = $qualify_results ["apr"];
		$customer_object->loan_note->finance_charge = $qualify_results ["finance_charge"];
		$customer_object->loan_note->total_payments = $qualify_results ["total_payments"];
		
		switch ($customer_object->income->pay_frequency)
		{
			case "WEEKLY":
				$monthly_parts = 4;
				break;
			case "MONTHLY":
				$monthly_parts = 1;
				break;
			case "TWICE_MONTHLY":
			case "BI_WEEKLY":
				$monthly_parts = 2;
				break;
		}

		$customer_object->income->net_pay = $customer_object->income->monthly_net_pay / $monthly_parts;
		
		// Change values from boolean/integer to char (income_direct_deposit)
		$customer_object->bank_info->direct_deposit = ($data_object->income_direct_deposit == "T" ? "TRUE" : "FALSE");
		return $customer_object;
	}

	function Get_Method_List ()
	{
		$master_query = "
			select 
				* 
			from
				".REFERENCE_DOCUMENT_METHOD;
		$result = $this->db_handler->Execute ($master_query);
		Error_2::Error_Test ($result, TRUE);
		while (FALSE !== ($temp = $result->Fetch_Object ()))
		{
			$method_list [$temp->DOCUMENT_METHOD_ID] = $temp->NAME;
		}

		return $method_list;
	}

	function Copia_Send ($transaction_id, $document_array, $send_email_id=NULL, $send_fax_id=NULL, $is_live=TRUE)
	{
		// Get the customer object
		echo $transaction_id .'trans' . $send_email_id . 'email' . $send_fax_id . 'fax';
		$copia->applicant = $this->Get_Customer_Details ($transaction_id,$send_email_id, $send_fax_id);

		// get list of documents
		$document_list = $this->Get_Document_List ($document_array ["property"]);
	
		// Get a list of methods
		$method_list = $this->Get_Method_List ();

		// List of methods that will generate a "BOTH"
		$multi_send_array = array ("FAX","EMAIL");
	
		// Get the type of event
		$event_list = $this->Get_Event_Type_List ();
		$event_type_id = array_search ("SENT", $event_list);
	
		// Walk the list of docs to send and prep for copia
		foreach ($document_array ["document_list"] as $document_id => $method)
		{
			$document_name = $document_list [$document_id];
			$doc_meth = explode (",", $method);

			if (count ($doc_meth) > 1)
			{
				$multi_send_flag = FALSE;

				// We have multiple documents
				foreach ($doc_meth as $method_id)
				{
					$send_method = $method_list [$method_id];
					switch (TRUE)
					{
						case ($multi_send_flag && in_array ($send_method, $multi_send_array)):
							$send_document_list->{$document_name} = "BOTH";
							$multi_send_flag = TRUE;
							break;

						case (!$multi_send_flag && in_array ($send_method, $multi_send_array)):
							$send_document_list->{$document_name} = $send_method;
							$multi_send_flag = TRUE;
							break;
					}
					
					// Mark the database with the sent document
					$this->Add_Document_Event ($transaction_id, $document_name, $event_type_id, $method_id, $document_array ["agent_id"]);
				}
			}
			else
			{
				// Only one send method
				$send_document_list->{$document_name} = $method_list [$doc_meth [0]];

				// Mark the database with the sent document
				$this->Add_Document_Event ($transaction_id, $document_name, $event_type_id, $doc_meth [0], $document_array ["agent_id"]);
			}
		}

		// check if date was set
		if (!isset ($doc_obj->date))
		{
			$copia->applicant->date = date ("m/d/Y");
		}
		else
		{
			$copia->applicant->date = $doc_obj->date;
		}
		$copia->applicant->agent_name = $doc_obj->agent_name;
	
		//set copia settings
		$copia->copia->fax_phone = $copia->applicant->personal->fax_phone;
		$copia->copia->email_address = $copia->applicant->personal->email;		
		$copia->applicant->application_id = $document_array ["property"] . ' - ' .$copia->applicant->applicant->application_id;
		$copia->applicant->full_name = $copia->applicant->personal->first_name." ".$copia->applicant->personal->last_name;
		$copia->property = $document_array ["property"];
		$copia->send_document_list = $send_document_list;

		//debug
		if (!$is_live)
		{
			$copia->applicant->personal->email = $copia->copia->email_address = $this->testing_email;
			$copia->applicant->personal->fax_phone = $copia->copia->fax_phone = $this->testing_fax;
		}
		
echo "The package object to copia:::\n\n<pre>"; print_r($copia); echo "</pre>\n\n";	
	
		$xmlrpc_envelope["passed_data"] = base64_encode (serialize ($copia));
		$function = "Send_Document";
		// send the document
		$copia_result = Xmlrpc_Request ($this->copia_host, $this->copia_port, $this->copia_path, $function, $xmlrpc_envelope);

		// Mark the document in the database as sent
		return $copia_result;
	}

	function Add_Document_Event ($transaction_id, $document_name, $event_type_id, $method_id, $agent_id, $dnis="NA", $tiff="NA")
	{
		// Set the document name for the document table
		$db_doc_name = substr ($document_name, 0, (strpos ($document_name, ".") ? strpos ($document_name, ".") : strlen ($document_name))); 

		if (is_object ($this->Verify_Db_Document (strtoupper ($db_doc_name))))
		{
			$use_name = strtoupper ($db_doc_name);
		}
		else
		{
			$use_name = "OTHER";
		}

		// Find the document_id if any
		$doc_id = "
			SELECT
				document_id
			FROM 
				".COMPANY_DOCUMENT."
			WHERE
				transaction_id=".$transaction_id."
				AND name='".$use_name."'
			FETCH FIRST 1 ROWS ONLY";
		$result = $this->db_handler->Execute ($doc_id);
		Error_2::Error_Test ($result, TRUE);
		
		$found = FALSE;
		while (FALSE !== ($temp = $result->Fetch_Object ()))
		{
			// The document exists
			$associate_document_id = $temp->DOCUMENT_ID;
			$found = TRUE;
		}
		if (!$found)
		{
			// No document entry, create one
			$create_doc = "
				INSERT INTO ".COMPANY_DOCUMENT."
					(
						date_created, 
						date_modified, 
						transaction_id, 
						name, 
						required,
						accepted
					)
				VALUES
					(
						CURRENT TIMESTAMP, 
						CURRENT TIMESTAMP, 
						".$transaction_id.", 
						'".$use_name."', 
						(
							SELECT
								required
							FROM
								".REFERENCE_DOCUMENT_LIST."
							WHERE
								name='".$use_name."'
						),
						0
					)";
			$result = $this->db_handler->Execute ($create_doc);
			Error_2::Error_Test ($result, TRUE);
			
			$associate_document_id = $this->db_handler->Insert_Id ();
		}
		
		// Build the event entry
		$insert_event =  "
			INSERT INTO ".COMPANY_DOCUMENT_EVENT."
				(
					date_created, 
					date_modified,
					document_method_id,
					document_event_type_id,
					document_id,
					agent_id,
					dnis, 
					tiff
				)
			VALUES
				(
					CURRENT TIMESTAMP, 
					CURRENT TIMESTAMP,
					".$method_id.",
					".$event_type_id.",
					".$associate_document_id.", 
					".$agent_id.",
					'".$dnis."',
					'".$tiff."'
				)";
		$result = $this->db_handler->Execute ($insert_event);
		Error_2::Error_Test ($result, TRUE);
		
		return TRUE;
	}
	
	function Verify_Db_Document ($name)
	{
		$verify_db = "
			SELECT
				*
			FROM
				".REFERENCE_DOCUMENT_LIST."
			WHERE
				name='".$name."'";
		
		$result = $this->db_handler->Execute ($verify_db);
		Error_2::Error_Test ($result, TRUE);
		
		$found = FALSE;
		while (FALSE !== ($temp = $result->Fetch_Object ()))
		{
			return $temp;
			$found = TRUE;
		}
		if (!found)
		{
			return FALSE;
		}
	}

	function Get_Document_List ($property)
	{
		// Generate the object to send to copia
		$object = new stdClass (); 
		$object->property = $property; 
		 
		// Send the query to copia
		$xmlrpc_envelope = new stdClass (); 
		$xmlrpc_envelope->passed_data = base64_encode (serialize ($object)); 

		$function = "Get_Document_List"; 
		 
		$result = Xmlrpc_Request ($this->copia_host, $this->copia_port, $this->copia_path, $function, $xmlrpc_envelope); 
		
		// Pull the list from the response
		$doc_list = unserialize (base64_decode ($result[0]));  
	
		// Send the list back
		return $doc_list;
	}

	function Get_Event_Type_List ()
	{
		$query = "
			SELECT
				*
			FROM
				".REFERENCE_DOCUMENT_EVENT_TYPE;
		
		$result = $this->db_handler->Execute ($query);
		Error_2::Error_Test ($result, TRUE);
		while (FALSE !== ($temp = $result->Fetch_Object ()))
		{
			$event_type_list [$temp->DOCUMENT_EVENT_TYPE_ID] = $temp->NAME;
		}

		return $event_type_list;
	}

	function Get_Event_List ($transaction_id, $event_name, $quantity="ALL")
	{
		$query = "
			SELECT
				doc.document_id,
				doc_event.document_event_id as event_id,
				doc.name as document_name,
				doc_event.date_created as date_event,
				agent.login,
				doc_event.dnis,
				doc_event.tiff,
				(
					SELECT
						name
					FROM
						".REFERENCE_DOCUMENT_METHOD." meth
					WHERE
						meth.document_method_id = doc_event.document_method_id
				) as method_name
			FROM
				".COMPANY_DOCUMENT." doc,
				".COMPANY_DOCUMENT_EVENT." doc_event,
				".SECURITY_AGENT." agent
			WHERE
				doc.transaction_id = ".$transaction_id."
				AND doc_event.document_id = doc.document_id
				AND doc_event.document_event_type_id in
				(
					SELECT
						document_event_type_id
					FROM
						".REFERENCE_DOCUMENT_EVENT_TYPE." evnt_type
					WHERE
						name='".$event_name."'
				)
				AND agent.agent_id = doc_event.agent_id
			ORDER BY
				date_event desc
			".($quantity != "ALL" ? "FETCH FIRST ".$quantity." ROWS ONLY" : "");
		
		$result = $this->db_handler->Execute ($query);
		Error_2::Error_Test ($result, TRUE);
		while (FALSE !== ($temp = $result->Fetch_Object ()))
		{
			$event_list [] = $temp;
		}

		return $event_list;
	}
	
	/*TODO: not finding documents for first time!!! */
	function Get_Receive_Document_List ($transaction_id, $property)
	{
		// Get a list of documents that can be received (not other)
		
		// First the sent documents
		$sent_list = $this->Get_Event_List ($transaction_id, "SENT");
		
		// Now the document list from copia
		$copia_list = $this->Get_Document_List ($property);
		
		// Now the list from the database
		$query = "
			SELECT
				*
			FROM
				".REFERENCE_DOCUMENT_LIST;
				
		$result = $this->db_handler->Execute ($query);
		Error_2::Error_Test ($result, TRUE);
		while (FALSE !== ($temp = $result->Fetch_Object ()))
		{
			$db_list [$temp->NAME] = $temp;
			$db_list [$temp->NAME]->SENT = 0;
			$db_list [$temp->NAME]->ALL = 0;
		}
		
		// Before we try to use it, make sure it is what we want!!
		if (is_array ($sent_list))
		{
			foreach ($sent_list as $sent_id => $sent_object)
			{
				$db_list [strtoupper ($sent_object->DOCUMENT_NAME)]->SENT = 1;
			}
		}
		
		if (is_array ($copia_list))
		{
			foreach ($copia_list as $copia_id => $copia_name)
			{
				$db_list [strtoupper (preg_replace ("/\.rtf$/", "", preg_replace ("/_/", " ",$copia_name)))]->ALL = 1;
			}
		}
		
		// Sort the array by document name
		ksort ($db_list);
		reset ($db_list);
		
		// Zero index this to be compatible with flash
		$counter = 0;
		foreach ($db_list as $name => $object)
		{
			if ($name != "OTHER")
			{
				$return_list [$counter]->NAME = $name;
				$return_list [$counter]->REQUIRED = ($object->REQUIRED ? 1 : 0);
				$return_list [$counter]->SENT = ($object->SENT ? 1 : 0);
				$return_list [$counter]->ALL = 1;
				$counter++;
			}
		}
		
		return $return_list;
	}

	function Validate_Tiff ($tiff, $dnis)
	{
		// Call the copia server to find out which documents to look for
		$object = new stdClass ();
		$object->dnis = $dnis;
		$object->tiff = $tiff;

		$xmlrpc_envelope = new stdClass ();
		$xmlrpc_envelope->passed_data = base64_encode (serialize ($object));

		$function = "Validate_Tiff";

		$result = Xmlrpc_Request ($this->copia_host, $this->copia_port, $this->copia_path, $function, $xmlrpc_envelope);

		return unserialize (base64_decode ($result [0]));
	}
}

?>
