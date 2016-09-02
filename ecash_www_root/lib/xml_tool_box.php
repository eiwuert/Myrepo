<?php

require '/virtualhosts/lib/error_message_resource.php';


class XML_Tool_Box
{
	var $array_xml;
	var $error_msg_resource;


	/**
		@public

		@fn
			XML_Tool_Box()

		@brief
			This is the constructor for the class and initializes
			the local variables.

		@param
			- none

		@return
			- none

		@todo
			- none
	*/
	function XML_Tool_Box()
	{
		$this->array_xml = array();
		$this->error_msg_resource = new Error_Message_Resource();
	}


	/**
		@public

		@fn
			Fill_Local_XML_Array($xml_doc)

		@brief
			This fills the local array (array_xml) with the elements
			from the XML document passed in.

		@param
			- xml_doc  This is the XML document that will be put 
							into the array.

		@return
			- none

		@todo
			- none
	*/
	function Fill_Local_XML_Array($xml_doc)
	{
		$this->array_xml = $this->Generate_Array_From_XML($xml_doc);
	}


	/**
		@public

		@fn
			Generate_Array_From_XML()

		@brief
			This takes an XML document and parses it into an array. Then
			the array is returned.

			It is important to note that his function had some hacking
         done to it. Time restrictions can be difficult and now we all
         have to deal with them.

		@param
			xml_doc  This is an XML document

		@return
			params  This is an array with XML document inside.

		@todo
			- none
	*/
	function Generate_Array_From_XML($xml_doc)
	{

        $parser = xml_parser_create('ISO-8859-1');
        xml_parse_into_struct($parser, $xml_doc, $vals, $index);
        xml_parser_free($parser);

        $params = array();
        $level = array();

        foreach ($vals as $xml_elem)
        {
            if ($xml_elem['type'] == 'open')
            {
                if (array_key_exists('attributes', $xml_elem))
                {
                    list($level[$xml_elem['level']], $extra) = array_values($xml_elem['attributes']);
                }
                else
                {
                    $level[$xml_elem['level']] = $xml_elem['tag'];
                }
            }
            
            if ($xml_elem['type'] == 'complete')
            {
                $start_level = 1;
                $php_stmt = '$params';
                while($start_level < $xml_elem['level'])
                {
                    $php_stmt .= '[$level['.$start_level.']]';
                    $start_level++;
                }

                // This is not so pretty hack!
                if (isset($xml_elem['attributes']['NAME']))
                {
                    $xml_elem['tag'] = $xml_elem['attributes']['NAME'];
                }

                if (!isset($xml_elem['value']))
                {
                    $xml_elem['value'] = '';
                }

                $php_stmt .= '[$xml_elem[\'tag\']] = trim($xml_elem[\'value\']);';
                eval($php_stmt);
            }
        }

        return $params;
	}



	/**
		@public

		@fn
			Get_Data_Array()

		@brief
			This function takes the elements in the array called
			array_xml, under the SIGNATURE and DATA elements and puts
			them all in one array. This array is returned. All keys
			in the array are changed to lowercase.

			- NOTE: $array_xml must have been loaded with the xml
						document to work.

		@param
			- none

		@return
			result  This is an array. All of the elements from the
						xml file are put it this return array.

		@todo
			- none
	*/
	function Get_Data_Array()
	{
		$array_system = array();
		$array_data = array();
		$result = array();

		// gather the signature elements
		reset($this->array_xml);
		if (isset($this->array_xml['TSS_LOAN_REQUEST']['SIGNATURE']))
		{
			$array_system = $this->array_xml['TSS_LOAN_REQUEST']['SIGNATURE'];
			reset($array_system);
			foreach($array_system as $key => $value)
			{
				// this if statement is a hack for checkadvance.com. There promo_id
				// will change from 25856 to 26103
				if((trim(strtolower($key)) == 'promo_id')
						&& (trim($value) == '25856'))
				{
					$result = array_merge($result, array('promo_id' => '26103'));
				}
				else
				{
					$result = array_merge($result, array(strtolower($key) => $value));
				}
			}
		}

		// gather the data elements
		reset($this->array_xml);
		if (isset($this->array_xml['TSS_LOAN_REQUEST']['COLLECTION']))
		{
			$array_data = $this->array_xml['TSS_LOAN_REQUEST']['COLLECTION'];
         if (is_array($array_data))
         {
				foreach($array_data as $key => $value)
				{
					// format the dates correctly
					if((trim(strtolower($key)) == 'date_dob_d')
						|| (trim(strtolower($key)) == 'date_dob_m')
						|| (trim(strtolower($key)) == 'income_date1_d')
						|| (trim(strtolower($key)) == 'income_date1_m')
						|| (trim(strtolower($key)) == 'income_date2_d')
						|| (trim(strtolower($key)) == 'income_date2_m'))
					{
						$result = array_merge($result, array(strtolower($key) => str_pad(trim($value), 2, '0', STR_PAD_LEFT)));
					}
					else
					{
						$result = $result + array(strtolower($key) => $value);
					}
				}
			}
		}

		return $result;
	}



	/**
		@public

		@fn
			Get_Child_Elements()

		@brief

			- NOTE: $array_xml must have been loaded with the xml
						document to work.

		@param
			- parent

		@return
			result  This is an array. All of the elements under
					  the parent param passed in.						

		@todo
			- none
	*/
	function Get_Child_Elements($parent)
	{
		$array_elem = array();
		$result = array();

		// gather the signature elements
		reset($this->array_xml);
		if (isset($this->array_xml['TSS_LOAN_REQUEST'][strtoupper($parent)]))
		{
			$array_elem = $this->array_xml['TSS_LOAN_REQUEST'][strtoupper($parent)];
			reset($array_elem);
			foreach($array_elem as $key => $value)
			{
				$result = $result + array(strtolower($key) => $value);
			}
		}

		return $result;
	}



	/**
		@public

		@fn
			Get_Value()

		@brief
			This searches the local array for a value. If the value is 
			found, it is returned. If it is not found, an empty string
			is returned.

		The local array serched contains the values contained in
		the XML document passed into Fill_Local_XML_Array($xml_doc).

		Get_Value(a, b) takes two parameters. The first parameter is an
		int value. The second value is a string. The first param
		represents the complex element in the XML document.
		A '0' represents the element 'DATA' and a value
		greater then '0' represents the element 'SIGNATURE'. The
		second parameter is the simple element name.

		@param
			complex  This is an int value. A '0' represents the
						complex element 'DATA'. A value greater then
						'1' represents the complex element 'SIGNATURE'.
			simple  This is a string value. This value is the simple
						element contained in the XML document.

		@return
			result  This is a string. If the element was found, it
						is returned. If it was not found, an empty
						string is returned.

		@todo
			- none
	*/
	function Get_Value($complex, $simple)
	{
		$result = '';
		$complex_type = 'COLLECTION';

		if ($complex >= 1)
		{
			$complex_type = 'SIGNATURE';			
		}

		if (isset($this->array_xml['TSS_LOAN_REQUEST'][$complex_type][strtoupper($simple)]));
		{
			$result = trim($this->array_xml['TSS_LOAN_REQUEST'][$complex_type][strtoupper($simple)]);
		}

		return $result;
	}



	/**
		@public

		@fn
			Generate_Server_XML_Document()

		@brief
			This generates a return XML document. It takes the values passed in
			and assembles the XML doc.

		@param
			$errors_array  This is an array. It is an array of error_keys as keys
								and the error messages are the values.
			$content_string  This is a string. It is a string of content elements.
			$param_array  This is an array. It is an array of parameter values that
								get put in the return XML doc.
			$sig_array  This is an array. It contains keys and values for elements
							that get put in the signature section of the XML doc.
			$data_array  This is an array. It contains the data that was submitted
							 by the user.

		@return
			result  This is a string. It is an XML docuement without the heading
						<?xml version="1.0"?> in the heading.

		@todo
			- none
	*/
	function Generate_Server_XML_Doc($errors_array,
												//$content_array,
												$content_string,
												$sig_array,
												$data_array)
	{
		$result = "";
		$name_first = 'UNKNOWN';
		$name_last = 'UNKNOWN';

      if (isset($data_array['name_first']))
      {
         $name_first = $data_array['name_first'];
      }
      if (isset($data_array['name_last']))
      {
         $name_last = $data_array['name_last'];
      }


		$errors_xml = $this->Generate_Error_XML_Struct($errors_array, $name_first, $name_last);
		//$content_xml = $this->Generate_XML_Struct('content', $content_array);
		$sig_xml = $this->Generate_XML_Struct('signature', $sig_array);
		$data_xml = $this->Generate_XML_Struct('collection', $data_array);
		$result = '<tss_loan_response> '. $errors_xml . $content_string//$content_xml
					. $param_xml . $sig_xml . $data_xml . ' </tss_loan_response>';

		return $result;

	}


	/**
		@public

		@fn
			Generate_Error_XML_Struct()

		@brief
				This function generates the error structure of return XML doc.

		@param
				$error_array	This is an array. It contains the error fields 
									that were passed in from the client.

		@return
			result	This is a string. It is the error elements and sub elements
						of the return xml doc.

		@todo
			- none
	*/
	function Generate_Error_XML_Struct($error_array, $name_first, $name_last)
	{
		$result = '<errors />';
		$elements = '';
		$value = '';

		if(count($error_array) > 0)
		{
			reset($error_array);
			foreach($error_array as $key => $field_name)
			{
				// NOTE: This if statement is a dirty hack. The return field names
				// should match the parameters passed in. New ones should not be made
				// up. The last else statement should work for everything.
            if ($key == 'esignature'
                    && substr($field_name, 0, 25) == 'Your Electronic Signature')
				{
					$elements .= '<data name="esignature">'
										. 'Your Electronic Signature must match your name as it appears: '
										. $name_first
										. ' '
										. $name_last
										. '</data>';
				}
				else if ($field_name == 'social_security_number')
				{
					$temp_msg =  $this->error_msg_resource->Get_Error_Desc('social_security_number');
					$elements .= '<data name="ssn_part_1"> ' . $temp_msg . ' </data> '
									. '<data name="ssn_part_2"> ' . $temp_msg . ' </data> '
									. '<data name="ssn_part_3"> ' . $temp_msg . ' </data> ';

				}
				else if ($field_name == 'dob')
				{
					$temp_msg =  $this->error_msg_resource->Get_Error_Desc('dob');
					$elements .= '<data name="date_dob_y"> ' . $temp_msg . ' </data> '
									. '<data name="date_dob_m"> ' . $temp_msg . ' </data> '
									. '<data name="date_dob_d"> ' . $temp_msg . ' </data> ';
				}
				else if (substr($field_name, 0, 9) == 'pay_date1')
				{
					// there is no error message for pay_date1
					if ($field_name != 'pay_date1')
					{
						$temp_msg =  $this->error_msg_resource->Get_Error_Desc($field_name);
						$elements .= '<data name="income_date1_d"> ' . $temp_msg . ' </data> '
										. '<data name="income_date1_m"> ' . $temp_msg . ' </data> '
										. '<data name="income_date1_y"> ' . $temp_msg . ' </data> ';
					}
				}
				else if (substr($field_name, 0, 9) == 'pay_date2')
				{
					// there is no error message for pay_date2
					if ($field_name != 'pay_date2')
					{
						$temp_msg =  $this->error_msg_resource->Get_Error_Desc($field_name);
						$elements .= '<data name="income_date2_d"> ' . $temp_msg . ' </data> '
										. '<data name="income_date2_m"> ' . $temp_msg . ' </data> '
										. '<data name="income_date2_y"> ' . $temp_msg . ' </data> ';
					}
				}
				else if (substr($field_name, 0, 9) == 'pay_date3')
				{
					// there is no error message for pay_date3
					if ($field_name != 'pay_date3')
					{
						$temp_msg =  $this->error_msg_resource->Get_Error_Desc($field_name);
						$elements .= '<data name="income_date3_d"> ' . $temp_msg . ' </data> '
										. '<data name="income_date3_m"> ' . $temp_msg . ' </data> '
										. '<data name="income_date3_y"> ' . $temp_msg . ' </data> ';
					}
				}
				else if (substr($field_name, 0, 9) == 'pay_date4')
				{
					// there is no error message for pay_date4
					if ($field_name != 'pay_date4')
					{
						$temp_msg =  $this->error_msg_resource->Get_Error_Desc($field_name);
						$elements .= '<data name="income_date4_d"> ' . $temp_msg . ' </data> '
										. '<data name="income_date4_m"> ' . $temp_msg . ' </data> '
										. '<data name="income_date4_y"> ' . $temp_msg . ' </data> ';
					}
				}
				else if ($field_name == 'too_many_twice_monthly'
							|| $field_name == 'too_many_monthly')
				{
					$temp_msg =  $this->error_msg_resource->Get_Error_Desc($field_name);
					$elements .= '<data name="income_date1_d"> ' . $temp_msg . ' </data> '
									. '<data name="income_date1_m"> ' . $temp_msg . ' </data> '
									. '<data name="income_date1_y"> ' . $temp_msg . ' </data> '
									. '<data name="income_date2_d"> ' . $temp_msg . ' </data> '
									. '<data name="income_date2_m"> ' . $temp_msg . ' </data> '
									. '<data name="income_date2_y"> ' . $temp_msg . ' </data> ';
				}
				else if ($field_name == 'cali_agree_conditional')
				{
					$temp_msg =  $this->error_msg_resource->Get_Error_Desc($field_name);
					$elements .= '<data name="cali_agree"> ' . $temp_msg . ' </data> ';
				}
				else
				{
					$elements .= '<data name="'. $field_name . '"> '
									. $this->error_msg_resource->Get_Error_Desc($field_name) 
									. ' </data> ';
				}
			}

			$result = '<errors>'. $elements . '</errors>';
		}

		return $result;

	}


	/**
		@public

		@fn
			Generate_XML_Struct()

		@brief
				This function generates the an XML structure for the return doc.

		@param
				$parent	This is a string. It is the parent element of the XML doc.
				$element_array	This is an array. It contains the elements that
									belong under the parent.

		@return
			result	This is a string. It is the elements and sub elements of
						the parent passed in.

		@todo
			- none
	*/
	function Generate_XML_Struct($parent, $element_array)
	{
		$elements = '';
		$result = ''; 

		if(count($element_array) > 0)
		{
			reset($element_array);
			foreach($element_array as $key => $value)
			{
				$elements .= '<data name="'. $key . '"> ' . $value . ' </data> ';
			}

			$result .= '<'. $parent . '>'. $elements . '</' . $parent . '>';
		}
		else
		{
			$result .= '<'. $parent . ' />';
		}

		return $result;
	}



//=============================================================================
//=============================================================================
//                       XML PAGE RETURN PAGE FORMATS
//=============================================================================
//=============================================================================

   function Preview_Docs($name_first,
								$name_last,
								$property_short,
								$application_id,
								$site_name,
								$promo_id,
								$home_street,
								$home_unit,
								$home_city,
								$home_state,
								$home_zip,
								$phone_home,
								$phone_fax,
								$phone_cell,
								$phone_work,
								$date_dob_d,
								$date_dob_m,
								$date_dob_y,
								$ssn_part_1,
								$ssn_part_2,
								$ssn_part_3,
								$email_primary,
								$state_id_number,
								$title,
								$shift,
								$income_type,
								$bank_name,
								$bank_aba,
								$bank_account,
								$check_number,
								$ref_01_name_full,
								$ref_01_relationship,
								$ref_01_phone_home,
								$ref_02_name_full,
								$ref_02_relationship,
								$ref_02_phone_home,
								$esignature,
								$apr,
								$net_pay,
								$pay_date1,
								$pay_date2,
								$pay_date3,
								$pay_date4,
								$income_direct_deposit,
								$income_frequency,
								$support_fax,
								$finance_charge,
								$fund_amount,
								$payoff_date,
								$fund_date,
								$property_name,
								$total_payments,
								$employer_name)
   {
		$today_date = date("m/d/Y");
		
		$content_string = '<section>'
								.	'<verbiage>'
								.		'<![CDATA['
////////////////////////////////
////////////////////////////////
// ENTIRE DOCUMENT START
////////////////////////////////
////////////////////////////////
								.			'<table width="700">'
								.				'<tr>'
								.					'<td>'
////////////////////////////////
////////////////////////////////
// APPLICATION START
////////////////////////////////
////////////////////////////////
								.					'<a name="application"></a>'
								.						'<table style="width:100%; margin:auto;" border="0" cellspacing="0" cellpadding="2">'
								.							'<tr>'
								.								'<td style="font-family: Arial, Helvetica, sans-serif; font-size:12px; line-height:11px; text-align:center !important; padding:auto 2px !important;" width="38%">'
								.									'Applicant: '
								.									'<b>'
								.										'<u>'
								.											'<span style="font-family: Arial, Helvetica, sans-serif; font-size:11px; line-height: 10px;">'
								.												$name_first
								.												' '
								.												$name_last
								.											'</span>'
								.										'</u>'
								.									'</b>'
								.									'<br />'
								.									'Loan ID: '
								.									'<b>'
								.										$property_short
								.										'-'
								.										$application_id
								.									'</b>'
								.								'</td>'
								.								'<td style="font-family:Arial, Helvetica, sans-serif; font-size:28px;line-height:14px;font-weight:bold; text-align:center !important; padding:auto 2px !important" width="24%">'
								.									'Application'
								.								'</td>'
								.								'<td style="font-family:Arial, Helvetica, sans-serif; font-size:11px; line-height:10px; text-align:center !important; padding: auto 2px !important;" width="38%">'
								.									'Date : '
								.									$today_date
								.									'<br />'
								.									'src: '
								.									$site_name
								.									' : '
								.									$promo_id
								.								'</td>'
								.							'</tr>'
								.						'</table>'
								.						'<table border="2" style="width:100%; margin:auto; border: 2px solid #000000;" cellspacing="0" cellpadding="1">'
								.							'<tr>'
								.								'<td colspan="2" align="center" style="font-family:Arial, Helvetica, sans-serif; font-size:14:px; line-height:14px;">'
								.									'<b>Personal Information</b>'
								.								'</td>'
								.							'</tr>'
								.							'<tr>'
								.								'<td width="50%" style="font-family: Arial, Helvetica, sans-serif; font-size:12px; line-height:11px;">'
								.									'<b>Applicant Name: </b>'
								.									'<span style="font-family:Arial, Helvetica, sans-serif; font-size:11px; line-height:10px;">'
								.										$name_first 
								.										" "
								. 										$name_last
								.									'</span>'
								.								'</td>'
								.								'<td style="font-family: Arial, Helvetica, sans-serif; font-size:12px; line-height:11px;" rowspan="3">'
								.									'<b>Applicants Address:</b>'
								.									'<br />'
								.									$home_street 
								. 									" "
								.									$home_unit
								.									'<br />'
								.									$home_city
								.									', '
								.									$home_state
								.									' '
								.									$home_zip
								.								'</td>'
								.							'</tr>'
								.							'<tr>'
								.								'<td width="50%" style="font-family:Arial, Helvetica, sans-serif; font-size:12px; line-height:11px;">'
								.									'<b>DOB:</b>'
								.									' '
								.									$date_dob_m
								.									'-'
								.									$date_dob_d
								.									'-'
								.									$date_dob_y
								.								'</td>'
								.							'</tr>'
								.							'<tr>'
								.								'<td width="50%" style="font-family:Arial, Helvetica, sans-serif; font-size:12px; line-height:11px;">'
								.									'<b>SS#</b>'
								.									':'
								.									$ssn_part_1
								.									'-'
								.									$ssn_part_2
								.									'-'
								.									$ssn_part_3
								.								'</td>'
								.							'</tr>'
								.							'<tr>'
								.								'<td width="50%" style="font-family:Arial, Helvetica, sans-serif; font-size:12px; line-height:11px;">'
								.									'<b>Home Phone #</b>'
								.									': '
								.									$phone_home
								.								'</td>'
								.								'<td style="font-family:Arial, Helvetica, sans-serif; font-size:12px; line-height:11px;">'
								.									'<b>Length at address</b>'
								.									': NA'
								.								'</td>'
								.							'</tr>'
								.							'<tr>'
								.								'<td width="50%" style="font-family:Arial, Helvetica, sans-serif; font-size:12px; line-height:11px;">'
								.									'<b>Fax Number</b>'
								.									': '
								.									$phone_fax
								.								'</td>'
								.								'<td style="font-family:Arial, Helvetica, sans-serif; font-size:12px; line-height:11px;">'
								.									'<b>E-Mail address</b>'
								.									': '
								.									$email_primary
								.								'</td>'
								.							'</tr>'
								.							'<tr>'
								.								'<td width="50%" style="font-family:Arial, Helvetica, sans-serif; font-size:12px; line-height:11px;">'
								.									'<b>Cell Number</b>'
								.									': '
								.									$phone_cell
								.								'</td>'
								.								'<td style="font-family:Arial, Helvetica, sans-serif; font-size:12px; line-height:11px;">'
								.									'<b>Drivers License</b>'
								.									': '
								.									$state_id_number
								.								'</td>'
								.							'</tr>'
								.							'<tr>'
								.								'<td colspan="2" align="center" style="font-family:Arial, Helvetica, sans-serif; font-size:14px; line-height:14px;">'
								.									'<b>'
								.										'<span style="font-family:Arial, Helvetica, sans-serif; font-size:14px; line-height:14px;">'
								.											'Employment / Income Information'
								.									'</b>'
								.								'</td>'
								.							'</tr>'
								.							'<tr>'
								.								'<td width="50%" style="font-family:Arial, Helvetica, sans-serif; font-size:12px; line-height:11px;">'
								.									'<b>Employer</b>'
								.									': '
								.									$employer_name
								.								'</td>'
								.								'<td style="font-family:Arial, Helvetica, sans-serif; font-size:12px; line-height:11px;">'
								.									'<b>Income comes from: </b>'
								.									$income_type
								.								'</td>'
								.							'</tr>'
								.							'<tr>'
								.								'<td width="50%" style="font-family:Arial, Helvetica, sans-serif; font-size:12px; line-height:11px;">'
								.									'<b>Your work phone</b>'
								.									': '
								.									$phone_work
								.								'</td>'
								.								'<td width="50%" style="font-family:Arial, Helvetica, sans-serif; font-size:12px; line-height:11px;">'
								.									'&nbsp; '
								.								'</td>'
								.							'</tr>'
								.							'<tr>'
								.								'<td width="50%" style="font-family:Arial, Helvetica, sans-serif; font-size:12px; line-height:11px;">'
								.									'<b>Length of Employment</b>'
								.									': '
								.									'3+ Months&nbsp;&nbsp;&nbsp;&nbsp;'
								.								'</td>'
								.								'<td style="font-family:Arial, Helvetica, sans-serif; font-size:12px; line-height:11px;">'
								.									'<b>Monthly Take Home pay*</b>'
								.									': $'
								.									$net_pay
								.								'</td>'
								.							'</tr>'
								.							'<tr>'
								.								'<td width="50%" style="font-family:Arial, Helvetica, sans-serif; font-size:12px; line-height:11px;">'
								.									'<b>Position</b>'
								.									': '
								.									$title
								.								'</td>'
								.								'<td style="font-family:Arial, Helvetica, sans-serif; font-size:12px; line-height:11px;">'
								.									'<b>Net pay each pay check*</b>'
								.									': NA'
								.								'</td>'
								.							'</tr>'
								.							'<tr>'
								.								'<td width="50%" style="font-family:Arial, Helvetica, sans-serif; font-size:12px; line-height:11px;">'
								.									'<b>Shift/Hours</b>'
								.									': '
								.									$shift
								.								'</td>'
								.								'<td style="font-family:Arial, Helvetica, sans-serif; font-size:12px; line-height:11px;">'
								.									'<b>Next four pay dates </b>'
								.									': '
								.									$pay_date1 
								.									' & '
								.									$pay_date2
								.									' & '
								.									$pay_date3
								.									' & '
								.									$pay_date4
								.								'</td>'
								.							'</tr>'
								.							'<tr>'
								.								'<td width="50%" style="font-family:Arial, Helvetica, sans-serif; font-size:12px; line-height:11px;">'
								.									'<b>Direct Deposit</b>'
								.									': '
								.									$income_direct_deposit
								.								'</td>'
								.								'<td style="font-family:Arial, Helvetica, sans-serif; font-size:12px; line-height:11px;">'
								.									'<b>Paid how often</b>'
								.									': '
								.									$income_frequency
								.								'</td>'
								.							'</tr>'
								.							'<tr>'
								.								'<td align="center" colspan="2" style="font-family:Arial,Helvetica, sans-serif; font-size:14px; line-height:14px;">'
								.									'<b>Checking Account Information</b>'
								.								'</td>'
								.							'</tr>'
								.							'<tr>'
								.								'<td width="50%" style="font-family:Arial, Helvetica, sans-serif; font-size:12px; line-height:11px;">'
								.									'<b>BANK NAME</b>'
								.									': '
								.									$bank_name
								.								'</td>'
								.								'<td width="50%" style="font-family:Arial, Helvetica, sans-serif; font-size:12px; line-height:11px;">'
								.									'<b>ABA/ROUTING </b>'
								.									': '
								.									$bank_aba
								.								'</td>'
								.							'</tr>'
								.							'<tr>'
								.								'<td width="50%" style="font-family:Arial, Helvetica, sans-serif; font-size:12px; line-height:11px;">'
								.									'<b>ACCOUNT NUMBER</b>'
								.									': '
								.									$bank_account
								.								'</td>'
								.								'<td style="font-family:Arial, Helvetica, sans-serif; font-size:12px; line-height:11px;">'
								.									'<b>NEXT CHECK NUMBER</b>'
								.									': '
								.									$check_number
								.								'</td>'
								.							'</tr>'
								.							'<tr>'
								.								'<td align="center" colspan="2" style="font-family:Arial,Helvetica, sans-serif; font-size:14px; line-height:14px;">'
								.									'<b>Personal References</b>'
								.								'</td>'
								.							'</tr>'
								.							'<tr>'
								.								'<td width="50%" style="font-family:Arial, Helvetica, sans-serif; font-size:12px; line-height:11px;">'
								.									'<b>Ref #1 name</b>'
								.									': '
								.									$ref_01_name_full
								.								'</td>'
								.								'<td style="font-family:Arial, Helvetica, sans-serif; font-size:12px; line-height:11px;">'
								.									'<b>Ref #2 name</b>'
								.									': '
								.									$ref_02_name_full
								.								'</td>'
								.							'</tr>'
								.							'<tr>'
								.								'<td width="50%" style="font-family:Arial, Helvetica, sans-serif; font-size:12px; line-height:11px;">'
								.									'<b>Ref #1 phone</b>'
								.									': '
								.									$ref_01_phone_home
								.								'</td>'
								.								'<td style="font-family:Arial, Helvetica, sans-serif; font-size:12px; line-height:11px;">'
								.									'<b>Ref #2 phone</b>'
								.									': '
								.									$ref_02_phone_home
								.								'</td>'
								.							'</tr>'
								.							'<tr>'
								.								'<td width="50%" style="font-family:Arial, Helvetica, sans-serif; font-size:12px; line-height:11px;">'
								.									'<b>Ref #1 relationship</b>'
								.									': '
								.									$ref_01_relationship
								.								'</td>'
								.								'<td style="font-family:Arial, Helvetica, sans-serif; font-size:12px; line-height:11px;">'
								.									'<b>Ref #2 relationship</b>'
								.									': '
								.									$ref_02_relationship
								.								'</td>'
								.							'</tr>'
								.						'</table>'
								.						'<p style="font-family:Arial, Helvetica, sans-serif; font-size:9px; line-height:9px; text-align: left !important; padding-left: 2px !important;">'
								.							'*or other source of income periodically deposited to your account. However, alimony, child support, or separate maintenance income need not be revealed if you do not wish to have it considered as a basis for repaying this obligation.'
								.							'<br /><br />'
								.							'NOTICE: We are required by law to adopt procedures to request and retain in our records information necessary to verify your identity.'
								.						'</p>'
								.						'<table style="font-family:Arial, Helvetica, sans-serif; font-size:9px; line-height:9px; text-align: left !important; padding-left: 2px !important;">'
								.							'<tr>'
								.								'<td>'
								.									'<p>'
								.										'ARBITRATION OF ALL DISPUTES: You and we agree that any and all claims, disputes or controversies between you and us, any claim by either of us against the other (or the employees, officers, directors, agents, servicers or assigns of the other) and any claim arising from or relating to your application for this loan, regarding this loan or any other loan you previously or may later obtain from us, this Note, this agreement to arbitrate all disputes, your agreement not to bring, join or participate in class actions, regarding collection of the loan, alleging fraud or misrepresentation, whether under common law or pursuant to federal, state or local statute, regulation or ordinance, including disputes regarding the matters subject to arbitration, or otherwise, shall be resolved by binding individual (and not joint) arbitration by and under the Code of Procedure of the National Arbitration Forum (&quot;NAF&quot;) in effect at the time the claim is filed. No class arbitration.  All disputes including any Representative Claims against us and/or related third parties shall be resolved by binding arbitration only on an individual basis with you. THEREFORE, THE ARBITRATOR SHALL NOT CONDUCT CLASS ARBITRATION; THAT IS, THE ARBITRATOR SHALL NOT ALLOW YOU TO SERVE AS A REPRESENTATIVE, AS A PRIVATE ATTORNEY GENERAL, OR IN ANY OTHER REPRESENTATIVE CAPACITY FOR OTHERS IN THE ARBITRATION. This agreement to arbitrate all disputes shall apply no matter by whom or against whom the claim is filed. Rules and forms of the NAF may be obtained and all claims shall be filed at any NAF office, on the World Wide Web at www.arb-forum.com, by telephone at 800-474-2371, or at &quot;National Arbitration Forum, P.O.  Box 50191, Minneapolis, Minnesota 55405.&quot; Your arbitration fees will be waived by the NAF in the event you cannot afford to pay them. The cost of any participatory, documentary or telephone hearing, if one is held at your or our request, will be paid for solely by us as provided in the NAF Rules and, if a participatory hearing is requested, it will take place at a location near your residence. This arbitration agreement is made pursuant to a transaction involving interstate commerce. It shall be governed by the Federal Arbitration Act, 9 U.S.C. Sections 1-16. Judgment upon the award may be entered by any party in any court having jurisdiction.'
								.									'</p>'
								.									'<p>'
								.										'NOTICE: YOU AND WE WOULD HAVE HAD A RIGHT OR OPPORTUNITY TO LITIGATE DISPUTES THROUGH A COURT AND HAVE A JUDGE OR JURY DECIDE THE DISPUTES BUT HAVE AGREED INSTEAD TO RESOLVE DISPUTES THROUGH BINDING ARBITRATION.'
								.									'</p>'
								.									'<p>'
								.										'AGREEMENT NOT TO BRING, JOIN OR PARTICIPATE IN CLASS ACTIONS: To the extent permitted by law, you agree that you will not bring, join or participate in any class action as to any claim, dispute or controversy you may have against us, our employees, officers, directors, servicers and assigns. You agree to the entry of injunctive relief to stop such a lawsuit or to remove you as a participant in the suit. You agree to pay the attorney\'s fees and court costs we incur in seeking such relief. This agreement does not constitute a waiver of any of your rights and remedies to pursue a claim individually and not as a class action in binding arbitration as provided above.This agreement not to bring, join, or participate in class actions is an independent agreement and shall survive the closing and repayment of the loan for which you are applying.'
								.										'<br />'
								.										'<b>Borrower\'s Electronic Signature to the above Agreements Appears Below</b>'
								.									'</p>'
								.									'<p>'
								.										'By electronically signing this Application you certify that all of the information provided above is true, complete and correct and provided to us, '
								.										$site_name
								.										', for the purpose of inducing us to make the loan for which you are applying. By electronically signing below you also agree to the Agreement to Arbitrate All Disputes and the Agreement Not To Bring, Join Or Participate in Class Actions. By electronically signing below you authorize the Company to share information in your Application and with regard to the processing, funding, servicing, repayment and collection of your loan.'
								.									'</p>'
								.								'</td>'
								.							'</tr>'
								.						'</table>'
								.						'<br />'
								.						'<table id="wf-legal-cancelauth" width="100%" border="0" cellspacing="0" cellpadding="4">'
								.							'<tr>'
								.								'<td style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 14px;  text-align: left !important; padding-left: 2px !important;">'
								.									'<b>(X) </b>'
								.									$esignature
								.								'</td>'
								.								'<td style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 14px;  text-align: left !important; padding-left: 2px !important;">'
								.									'<b>(X) </b>'
								.									'<u>'
								.										'<span style="font-family: Arial,Helvetica,sans-serif; font-size: 11px; line-height:10px;">'
								.											'<b>'
								.												$name_first 
								.												' '
								.												$name_last
								.											'</b>'
								.										'</span>'
								.									'</u>'
								.								'</td>'
								.								'<td style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 14px;  text-align: left !important; padding-left: 2px !important;">'
								.									'<b>'
								.										'(X)'
								.										' '
								.										'<u>'
								.											'<span style="font-family: Arial,Helvetica,sans-serif; font-size: 11px; line-height:10px;">'
                .												date ("m/d/Y")
								.											'</span>'
								.										'</u>'
								.									'</b>'
								.								'</td>'
								.							'</tr>'
								.							'<tr>'
								.								'<td style="font-family: Arial, Helvetica,sans-serif; font-size: 14px; line-height: 14:px;">'
								.									'&nbsp;Electronic Signature of Applicant'
								.								'</td>'
								.								'<td style="font-family: Arial, Helvetica,sans-serif; font-size: 14px; line-height: 14:px;">'
								.									'&nbsp;Printed Name of Applicant'
								.								'</td>'
								.								'<td style="font-family: Arial, Helvetica,sans-serif; font-size: 14px; line-height: 14:px;">'
								.									'&nbsp;Date'
								.								'</td>'
								.							'</tr>'
								.						'</table>'
								.						'<br style="page-break-before: always;" />'
								.						'<br />'
								.						'<br />'
								.						'<p style="font-family: Arial, Helvetica, sans-serif; font-size: 9px; line-height:9px;" >'
								.							'<b>'
								.								'SHORT TERM LOANS PROVIDE THE CASH NEEDED TO MEET IMMEDIATE SHORT-TERM CASH FLOW PROBLEMS. THEY ARE NOT A SOLUTION FOR LONGER TERM FINANCIAL PROBLEMS FOR WHICH OTHER KINDS OF FINANCING MAY BE MORE APPROPRIATE. YOU MAY WANT TO DISCUSS YOUR FINANCIAL SITUATION WITH A NONPROFIT FINANCIAL COUNSELING SERVICE.'
								.							'</b>'
								.						'</p>'
								.						'<br />'
/////////////////////////////////	
/////////////////////////////////
// PRIVACY POLICY START
////////////////////////////////
////////////////////////////////
								.					'<a name="privacy_policy"></a>'
								.						'<div style="border: 1px solid #000000;">'
								.							'<br />'
								.							'<div style="font-family: Arial, Helvetica, sans-serif; font-size: 28px; line-height: 14px; font-weight: bold; text-align: center !important; padding: auto 2px !important;">'
								.								'<u>Privacy Policy</u>'
								.							'</div>'
								.							'<p style="text-align: left !important; padding-left: 2px !important; font-size: 14px;">'
								.								'<b>PRIVACY POLICY</b>'
								.								'. Protecting your privacy is important to '
								.								$site_name
								.								' and our employees. We want you to understand what information we collect and how we use it. In order to provide our customers with short term loans as effectively and conveniently as possible, we use technology to manage and maintain customer information, The following policy serves as a standard for all '
								.								$site_name
								.								' employees for collection, use, retention, and security of nonpublic personal information related to our short term programs. '
								.								'<p style="text-align: left !important; padding-left: 2px !important; font-size: 14px;">'
								.									'<b>WHAT INFORMATION WE COLLECT</b>'
								.									'. We may collect &quot;nonpublic personal information&quot; about you from the following sources: Information we receive from you on applications or other loan forms, such as your name, address, social security number, assets and income; Information about your loan transactions with us, such as your payment history and loan balances; and Information we receive from third parties, such as consumer reporting agencies and other lenders, regarding your creditworthiness and credit history. &quot;Nonpublic personal information&quot; is nonpublic information about you that we obtain in connection with providing a short term loan to you or list derived using that information. For example, as noted above, nonpublic personal information includes your name, social security number, payment history, and the like.'
								.								'</p>'
								.								'<p style="text-align: left !important; padding-left: 2px !important; font-size: 14px;">'
								.									'<b>WHAT INFORMATION WE DISCLOSE</b>'
								.									'. We are permitted by law to disclose nonpublic personal information about you to third parties in certain circumstances, For example, we may disclose nonpublic personal information about your short term loan to consumer reporting agencies and to government entities in response to subpoenas. Moreover, we may disclose all of the nonpublic personal information about you that we collect, as described above, to financial service providers that perform services on our behalf, such as the marketers and services of your short term loan, and to financial institutions with which we have joint marketing arrangements. Such disclosures are made as necessary to effect, administer and enforce the loan you request or authorize. Otherwise, we do not disclose nonpublic financial information about our customers or former customers to anyone, except as permitted by law.'
								.									'<br />'
								.								'</p>'
								.								'<p style="font-family: Arial, Helvetica, sans-serif; font-size: 12px; line-height: 11px; text-align: center !important; padding: auto 2px !important;">'
								.									'<b>'
								.										'RIGHT TO CANCEL: YOU MAY CANCEL THIS LOAN WITHOUT COST OR FURTHER OBLIGATION TO US, IF YOU DO SO BY THE END OF BUSINESS ON THE BUSINESS DAY AFTER THE LOAN PROCEEDS ARE DEPOSITED INTO YOUR CHECKING ACCOUNT.'
								.									'</b>'
								.								'</p>'
								.								'<table width="680" align="center" color="#FFFFFF" style="border: 1px solid #000000;">'
								.									'<tr>'
								.										'<td>'
								.											'<p style="font-family: Arial, Helvetica, sans-serif; font-size: 11px; line-height: 10px;">'
								.												'To cancel, you must to alert us of your intention to cancel. You must also complete the information in this box, and sign and fax it to us at '
								.												$support_fax
								.												'. If you follow these procedures but there are insufficient funds available in your deposit account to enable us to reverse the transfer of loan proceeds at the time we effect an ACH debit entry of your checking account, your cancellation will not be effective and you will be required to pay the loan and our charges on the scheduled maturity date.'
								.											'</p>'
								.											'<p style="font-family: Arial, Helvetica, sans-serif; font-size: 11px; line-height: 10px;">'
								.												'<b>YOU WISH TO CANCEL.</b>'
								.												'You authorize us to initiate ACH debit entry to your checking account of the amount of the loan proceeds we deposited to that account at your request '
								.											'</p>'
								.											'<br />'
								.											'<table width="520" border="0" cellspacing="0" cellpadding="2" id="cxl-sig">'
								.												'<tr>'
								.													'<td width="30%" height="24" style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 14px; text-align: left !important; padding-left: 2px !important;">'
								.														'____________________&nbsp;' 
								.													'</td>'
								.													'<td width="31%" height="24" style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 14px; text-align: left !important; padding-left: 2px !important;">'
								.														'____________________&nbsp;'
								.													'</td>'
								.													'<td width="24%" height="24" style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 14px; text-align: left !important; padding-left: 2px !important;">'
								.														'______________&nbsp;'
								.													'</td>'
								.													'<td width="15%" height="24" style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 14px; text-align: left !important; padding-left: 2px !important;">'
								.														'____________'
								.													'</td>'
								.												'</tr>'
								.												'<tr>'
								.													'<td width="30%" style="font-family: Arial, Helvetica, sans-serif; font-size: 9px; line-height: 9px; text-align: left !important; padding-left: 2px !important;">'
								.														'Print Name'
								.													'</td>'
								.													'<td width="31%" style="font-family: Arial, Helvetica, sans-serif; font-size: 9px; line-height: 9px; text-align: left !important; padding-left: 2px !important;">'
								.														'Signature'
								.													'</td>'
								.													'<td width="24%" style="font-family: Arial, Helvetica, sans-serif; font-size: 9px; line-height: 9px; text-align: left !important; padding-left: 2px !important;">'
								.														'Social Security Number'
								.													'</td>'
								.													'<td width="15%" style="font-family: Arial, Helvetica, sans-serif; font-size: 9px; line-height: 9px; text-align: left !important; padding-left: 2px !important;">'
								.														'Date'
								.													'</td>'
								.												'</tr>'
								.											'</table>'
								.										'</td>'
								.									'</tr>'
								.								'</table>'
								.								'<br />'
								.							'</div>'
								.							'<br />'
								.							'<br />'
								.							'<table style="font-family: Arial,Helvetica, sans-serif; font-size: 20px; line-height: 18px; font-weight: bold; decoration: underline;">'
								.								'<tr>'
								.									'<td>'
								.										'NOTICE: DO NOT SIGN THE ABOVE OR FAX THIS PAGE'
								.										'<br />'
								.										'UNLESS YOU INTEND TO CANCEL YOUR LOAN'
								.									'</td>'
								.								'</tr>'
								.							'</table>'
/////////////////////////////////
/////////////////////////////////
// PRIVACY POLICY STOP
/////////////////////////////////
/////////////////////////////////
								.							'<br />'
								.							'<br />'
								.							'<br />'
								.							'<br />'
								.							'<br />'
/////////////////////////////////	
/////////////////////////////////
// LOAN NOTE AND DISCLOSURE START
/////////////////////////////////
/////////////////////////////////
								.					'<a name="loan_note_and_disclosure"></a>'
								.							'<table border="0" cellspacing="0" cellpadding="2" width="100%">'
								.								'<tr>'
								.									'<td width="45%" style="text-align: left !important; padding-left: 2px !important; font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 14px; font-weight: bold; ">'
								.										'LOAN NOTE AND DISCLOSURE'
								.									'</td>'
								.									'<td width="45%" style="text-align: right !important; padding-right: 2px !important; font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 14px; font-weight: bold; ">'
								.										$site_name
								.										'&nbsp;'
								.									'</td>'
								.								'</tr>'
								.								'<tr>'
								.									'<td style="text-align: left !important; padding-left: 2px !important; font-weight: bold; font-family: Arial,Helvetica, sans-serif; font-size: 12px; line-height: 11px;" width="50%">'
								.										'Borrower\'s Name: '
								.										$name_first
								.										' '
								.										$name_last
								.									'</td>'
								.									'<td align="right" style="font-family: Arial; Helvetica, sans-serif; font-size: 12px; line-height: 11px;" width="50%">'
								.										'Date: '
								.										date ("m-d-Y")
								.										'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ID#: '
								.										'<b>'
								.											$application_id
								.										'</b>'
								.									'</td>'
								.								'</tr>'
								.							'</table>'
								.							'<table style="font-family: Arial, Helvetica, sans-serif; font-size: 11px; line-height: 10px; text-align: left !important; padding-left: 2px !important;">'
								.								'<tr>'
								.									'<td>'
								.										'<u>Parties:</u> '
								.										'In this Loan Note and Disclosure (&quot;Note&quot;) you are the person named as Borrower above. We are '
								.										$site_name
								.										'.'
								.										'<br />'
								.										'<u>The Account:</u>'
								.										'	You have deposit account, No. '
								.										$bank_account
								.										'(&quot;Account&quot;), with us or, if the following space is completed, at '
								.										$bank_name
								.										'(&quot;Bank&quot;). You authorize us to effect a credit entry to deposit the proceeds of the Loan (the Amount Financed indicated below) to your Account at the Bank. '
								.										'<br />'
								.										'DISCLOSURE OF CREDIT TERMS: The information in the following box is part of this Note.'
								.									'</td>'
								.								'</tr>'
								.							'</table>'
								.							'<table cellspacing="0" cellpadding="0">'
								.								'<tr>'
								.									'<td  border="2" style="font-family: Arial, Helvetica, sans-serif; font-size: 12px; line-height: 11px; text-align: left !important; padding-left: 2px !important;" width="25%">'
								.										'<table style="border: 2px solid #000000;" cellspacing="0" cellpadding="0">'
								.											'<tr>'
								.												'<td>'
								.													'<table style="border: 1px solid #000000;" cellspacing="0" cellpadding="0">'
								.														'<tr>'
								.															'<td align="center" style="border: 1px solid #000000;" cellspacing="0" cellpadding="0">'
								.																'<b>ANNUAL PERCENTAGE RATE</b>'
								.																'<br />'
								.																'The cost of your credit as a yearly rate (e)'
								.																'<br />'
								.																'<b>'
								.																	$apr
	  							.																	'%'
								.																'</b>'
								.															'</td>'
								.															'<td align="center" style="border: 1px solid #000000;" cellspacing="0" cellpadding="0">'
								.																'<b>FINANCE CHARGE</b>'
								.																'<br />'
								.																'The dollar amount the credit will cost you.'
								.																'<br />'
								.																'<b>'
								.																	'$'
								.																	$finance_charge
								.																'</b>'
								.															'</td>'
								.															'<td align="center" style="border: 1px solid #000000;" cellspacing="0" cellpadding="0">'
								.																'<b>Amount Financed</b>'
								.																'<br />'
								.																'The amount of credit provided to you or on your behalf.'
								.																'<br />'
								.																'<b>'
								.																	'$'
								.																	$fund_amount
								.																'</b>'
								.															'</td>'
								.															'<td align="center" style="border: 1px solid #000000;" cellspacing="0" cellpadding="0">'
								.																'<b>Total of Payments</b>'
								.																'<br />'
								.																'The amount you will have paid after you have made the scheduled payment.'
								.																'<br />'
								.																'<b>'
								.																	'$'
								.																	$total_payments
								.															  '</b>'
								.															'</td>'
								.														'</tr>'
								.													'</table>'
								.												'</td>'
								.											'</tr>'
								.											'<tr>'
								.												'<td colspan="4" style="font-family: Arial, Helvetica, sans-serif; font-size: 11px; line-height: 10px; text-align: left !important; padding-left: 2px !important;">'
								.													'Your '
								.													'<b>'
								.														'Payment Schedule'
								.													'</b>'
								.													' will be: 1 payment of '
								.													'<b>'
								.														$total_payments
								.													'</b>'
								.													' due on '
								.													'<b>'
								.														$payoff_date
								.													'</b>'
								.													', if you decline* the option of refinancing your loan. If refinancing is accepted you will pay the finance charge of '
								.													'<b>'
								.														$finance_charge
								.													'</b>'
								.													' only, on '
								.													'<b>'
								.														$payoff_date
								.													'</b>'
								.													'. You will accrue new finance charges with every refinance of your loan. On your fifth refinance and every refinance thereafter, your loan will be paid down by $50.00. This means your account will be debited the finance charge plus $50.00. This will continue until your loan is paid in full.'
								.													'<br />'
								.													'* To decline the option of refinancing you must sign the Account Summary page and fax it back to our office at least three business days before your loan is due.'
								.													'<br />'
								.													'<b>Security:</b> '
								.													'The loan is unsecured.'
								.													'<br />'
								.													'<b>Prepayment: </b>'
								.													'If you prepay your loan in advance, you will not receive a refund of any Finance Charge.'
								.													'<br />'
								.													'(e) The Annual Percentage Rate is estimated based on the anticipated date the loan proceeds will be deposited to your account, which is '
								.													'<b>'
								.														$fund_date
								.													'</b>'
								.													'.'
								.													'<br />'
								.													'See below and your other contract documents for any additional information about prepayment, nonpayment and default.'
								.												'</td>'
								.											'</tr>'
								.										'</table>'
								.										'<div style="font-family: Arial, Helvetica, sans-serif; font-size: 11px; line-height: 10px; text-align: left !important; padding-left: 2px !important;">'
								.											'<b>'
								.												'<u>'
								.													'Itemization Of Amount Financed of $'
								.													$fund_amount
								.												'</u>'
								.												'; Given to you directly: '
								.												'<u>'
								.													$fund_amount
					 			.												'</u>'
								.												'; Paid on your account '
								.												'<u>'
								.													'$0'
								.												'</u>'
								.											'</b>'
								.											'<br />'
								.											'<b>'
								.												'<u>'
								.													'PROMISE TO PAY:'
								.												'</u>'
								.											'</b>'
								.											'You promise to pay to us or to our order, in 1 payment, on the date indicated in the Payment Schedule, the Total of Payments. On or after the day your loan comes due you authorize us to effect one or more ACH debit entries to your Account at the Bank. You may revoke this authorization at any time up to 3 business days prior to the due date. However, if you timely revoke this authorization, you authorize us to prepare and submit one or more checks drawn on your Account to repay your loan when it comes due. If your Account is with us, you authorize us to deduct the payment from your Account on the day the loan comes due. If there are insufficient funds on deposit in your Account to effect the ACH debit entry or to pay a check or otherwise cover the loan payment on the due date, you promise to pay us all sums you owe by mailing a check or Money Order payable to: '
								.											$property_name
								.											'.'
								.											'<br />'
								.											'<u>'
								.												'<b>'
								.													'RETURN ITEM FEE:'
								.												'</b>'
								.											'</u>'
								.											' You agree to pay $30 if an item in payment of what you owe is returned unpaid or an ACH debit entry, the authorization for which was not properly revoked by you, is rejected by the Bank for any reason. '
								.											'<b>'
								.												'<u>'
								.													'<br />'
								.													'PREPAYMENT:'
								.												'</u>'
								.											'</b>'
								.											' The Finance Charge consists solely of a Loan Fee that is earned in full at the time the Loan is funded. You may pay all or part of what you owe prior to the due date, without penalty. However, if you pay early you will not be entitled to a refund of part or all of the Finance Charge.'
								.											'<br />'
								.											'<u>'
								.												'<b>ARBITRATION OF ALL DISPUTES</b>'
								.												':'
								.											'</u>'
								.											'<b>'
								.												' You and we agree that any and all claims, disputes or controversies between you and us, any claim by either of us against the other (or the employees, officers, directors, agents, servicers or assigns of the other) and any claim arising from or relating to your application for this loan, regarding this loan or any other loan you previously or may later obtain from us, this Note, this agreement to arbitrate all disputes, your agreement not to bring, join or participate in class actions, regarding collection of the loan, alleging fraud or misrepresentation, whether under common law or pursuant to federal, state or local statute, regulation or ordinance, including disputes regarding the matters subject to arbitration, or otherwise, shall be resolved by binding individual (and not joint) arbitration by and under the Code of Procedure of the National Arbitration Forum (&#8220;NAF&#8221;) in effect at the time the claim is filed. No class arbitration. All disputes including any Representative Claims against us and/or related third parties shall be resolved by binding arbitration only on an individual basis with you.'
								.											'</b>'
								.											' THEREFORE, THE ARBITRATOR SHALL NOT CONDUCT CLASS ARBITRATION; THAT IS, THE ARBITRATOR SHALL NOT ALLOW YOU TO SERVE AS A REPRESENTATIVE, AS A PRIVATE ATTORNEY GENERAL, OR IN ANY OTHER REPRESENTATIVE CAPACITY FOR OTHERS IN THE ARBITRATION. This agreement to arbitrate all disputes shall apply no matter by whom or against whom the claim is filed.  Rules and forms of the NAF may be obtained and all claims shall be filed at any NAF office, on the World Wide Web at www.arb-forum.com, by telephone at 800-474-2371, or at &#8220;National Arbitration Forum, P.O. Box 50191, Minneapolis, Minnesota 55405.&#8221; Your arbitration fees will be waived by the NAF in the event you cannot afford to pay them. The cost of any participatory, documentary or telephone hearing, if one is held at your or our request, will be paid for solely by us as provided in the NAF Rules and, if a participatory hearing is requested, it will take place at a location near your residence.  This arbitration agreement is made pursuant to a transaction involving interstate commerce. It shall be governed by the Federal Arbitration Act, 9 U.S.C. Sections 1-16. Judgment upon the award may be entered by any party in any court having jurisdiction.'
								.											'<br />'
								.											'<span style="font-family: Arial, Helvetica,sans-serif; font-size: 9px; line-height: 9px;">'
								.												'<b>NOTICE:</b>'
								.												'YOU AND WE WOULD HAVE HAD A RIGHT OR OPPORTUNITY TO LITIGATE DISPUTES THROUGH A COURT AND HAVE A JUDGE OR JURY DECIDE THE DISPUTES BUT HAVE AGREED INSTEAD TO RESOLVE DISPUTES THROUGH BINDING ARBITRATION.'
								.											'</span>'
								.											'<b>'
								.												'<u>'
								.													'Agreement not to Bring, Join Or Participate In Class Actions: '
								.												'</u>'
								.												'To the extent permitted by law, you agree that you will not bring, join or participate in any class action as to any claim, dispute or controversy you may have against us, our employees, officers, directors, servicers and assigns. You agree to the entry of injunctive relief to stop such a lawsuit or to remove you as a participant in the suit. You agree to pay the attorney&#8217;s fees and court costs we incur in seeking such relief. This agreement does not constitute a waiver of any of your rights and remedies to pursue a claim individually and not as a class action in binding arbitration as provided above.'
								.											'</b>'
								.											'<br />'
								.											'<b>'
								.												'<u>Survival:</u>'
								.											'</b>'
								.											' The provisions of this Loan Note and Disclosure dealing with the Agreement To Arbitrate All Disputes and the Agreement Not To Bring, Join Or Participate In Class Actions shall survive repayment in full and/or default of this Note.'
								.											'<br />'
								.											'<b>'
								.												'<u>'
								.													'No Bankruptcy:'
								.												'</u>'
								.											'</b>'
								.											' By electrinically signing below you represent that you have not recently filed for bankruptcy and you do not plan to do so.'
								.											'<br />'
								.											'<u>'
								.												'BY ELECTRONICALLY SIGNING BELOW, YOU AGREE TO ALL THE TERMS OF THIS NOTE, INCLUDING THE AGREEMENT TO ARBITRATE ALL DISPUTES AND THE AGREEMENT NOT TO BRING, JOIN OR PARTICIPATE IN CLASS ACTIONS.'
								.											'</u>'
								.											'<br />'
								.											'<br />'
								.										'</div>'
								.										'<table border="0" cellspacing="2" cellpadding="0">'
								.											'<tr>'
								.												'<td>'
								.													'<br />'
								.													'<table width="100%" cellspacing="0" cellpadding="1">'
								.														'<tr>'
								.															'<td style="font-family: Arial, Helvetica, sans-serif; font-size: 11px; line-height: 10px;  border-bottom: 1px solid #000000; text-align: left !important; padding-left:2px !important;">'
								.																'<strong>'
								.																	'(X) '
								.																	$esignature 
								.																'</strong>'
								.															'</td>'
								.															'<td width="30%" style="font-family: Arial, Helvetica, sans-serif; font-size: 11px; line-height: 10px;  border-bottom: 1px solid #000000; text-align: center !important; padding: auto 2px !important;">'
								.																date ("m/d/Y")
								.															'</td>'
								.														'</tr>'
								.														'<tr>'
								.															'<td style="font-family: Arial, Helvetica, sans-serif; font-size: 11px; line-height: 10px; text-align: left !important; padding-left: 2px !important;">'
								.																'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Electronic Signature'
								.															'</td>'
								.															'<td width="30%" style="font-family: Arial, Helvetica, sans-serif; font-size: 11px; line-height: 10px; text-align: center !important; padding: auto 2px !important;">'
								.																'Date'
								.															'</td>'
								.														'</tr>'
								.														'<tr>'
								.															'<td style="font-family: Arial, Helvetica, sans-serif; font-size: 11px; line-height: 10px;  border-bottom: 1px solid #000000; text-align: left !important; padding-left:2px !important;">'
								.																'<br />'
								.																'&nbsp;&nbsp;&nbsp;&nbsp;'
								.																$name_first
								.																' '
								.																$name_last
								.															'</td>'
								.														'</tr>'
								.														'<tr>'
								.															'<td colspan="2" style="font-family: Arial, Helvetica, sans-serif; font-size: 11px; line-height: 10px;  text-align: left !important; padding-left: 2px !important;">'
								.																'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Print Name'
								.															'</td>'
								.														'</tr>'
								.													'</table>'
								.												'</td>'
								.												'<td width="40%" style="font-family: Arial, Helvetica, sans-serif; font-size: 9px; line-height: 9px; border: 1px solid #000000;">'
								.													'<b>INSTRUCTIONS: YOU WILL BE ADVISED OF YOUR APPROVAL VIA PHONE OR EMAIL.</b>'
								.													'<br /><br />'
								.												'</td>'
								.											'</tr>'
								.										'</table>'
								.									'</td>'
								.								'</tr>'
								.							'</table>'
/////////////////////////////////
/////////////////////////////////
// LOAN NOTE AND DISCLOSURE STOP
/////////////////////////////////
/////////////////////////////////
								.							'<br /><br /><br /><br /><br /><br />'
/////////////////////////////////
/////////////////////////////////
// AUTHORIZATION AGREEMENT
/////////////////////////////////
/////////////////////////////////
								.					'<a name="auth_agreement"></a>'
								.							'<div style="margin: auto default;">'
								.								'<div style="text-align: right !important; padding-right: 2px !imporant; font-family: Arial, Helvetica, sans-serif; font-size: 20px; line-height: 18px; font-weight: bold: decoration: underline;">'
								.									$site_name
								.								'</div>'
								.								'<table width="700" style="text-align: center !imporatant; padding: auto 2px !important; font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 14px;">'
								.									'<tr>'
								.										'<td>'
								.											'<b>AUTHORIZATION AGREEMENT FOR PREAUTHORIZED PAYMENT</b>'
								.										'</td>'
								.									'</tr>'
								.								'</table>'
								.								'<table width="700" style="text-align: left !important; padding-left: 2px !important;">'
								.									'<tr>'
								.										'<td>'
								.											'<ol>'
								.												'<li>'
								.											'You must fill in you bank name, Routing/ABA No. And checking account number in items 5A, 5B, and 5C below.  And, be sure to sign where indicated by the (x) in item 9 below.'
								.												'</li>'
								.												'<li>'
								.											'Unless the authorization in item 5 below is properly and timely revoked, there will be a $30.00 fee on any ACH debit entry items that are returned at time of collection.'
								.												'</li>'
								.												'<li>'
								.													'You authorize '
								.													$site_name
								.													', and or their servicers and affiliates, to contact you at your place of employment or residence at any time up to 9:00 pm, your local time, regarding your loan.'
								.												'</li>'
								.												'<li>'
								.													'You represent that you have not recently filed for bankruptcy and have no present intentions of doing so.'
								.												'</li>'
								.												'<li>'
								.													'You authorize us, '
								.													$site_name
								.													', or our servicer, agent, or affiliate to initiate one or more ACH debit entries (for example, at our option, one debit entry may be for the principal of the loan and another for the finance charge) to your Deposit Account indicated below for the payments that come due each pay period and/or each due date concerning every refinance, with regard to the loan for which you are applying. The Depository Institution named below, called BANK, will receive and debit such entry to your Checking Account.'
								.													'<table cellspacing="0" cellpadding="3" style="border: 1px solid #000000; width: 90%; margin: auto;">'
								.														'<tr>'
								.															'<td width="33%" style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 14px; border: 1px solid #000000; margin: auto;">'
								.																'A. BANK Name '
								.																'<table style="text-align: center !important; padding: auto 2px !important;">'
								.																	'<tr>'
								.																		'<td>'
								.																			'<b>'
								.																				$bank_name
								.																			'</b>'
								.																		'</td>'
								.																	'</tr>'
								.																'</table>'
								.															'</td>'
								.															'<td width="33%" style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 14px; border: 1px solid #000000; margin: auto;">'
								.																'B. Routing/ABA No. '
								.																'<table style="text-align: center !important; padding: auto 2px !important;">'
								.																	'<tr>'
								.																		'<td>'
								.																			'<b>'
								.																				$bank_aba
								.																			'</b>'
								.																		'</td>'
								.																	'</tr>'
								.																'</table>'
								.															'</td>'
								.															'<td width="33%" style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 14px; border: 1px solid #000000; margin: auto;">'
								.																'C. Checking Account No. '
								.																'<table style="text-align: center !important; padding: auto 2px !important;">'
								.																	'<tr>'
								.																		'<td>'
								.																			'<b>'
								.																				$bank_account
								.																			'</b>'
								.																		'</td>'
								.																	'</tr>'
								.																'</table>'
								.															'</td>'
								.														'</tr>'
								.													'</table>'
								.													'<table width="700" style="text-align: left !important; padding-left: 2px !important; font-family: Arial, Helvetica, sans-serif; font-size: 11px; line-height: 10px;">'
								.														'<tr>'
								.															'<td>'
								.																'This Authorization becomes effective at the time we make you the loan for which you are applying and will remain in full force and effect until we have received notice of revocation from you. It does not authorize us to make debit entries with regard to any other loan. You may revoke this authorization to effect an ACH debit entry to your Account by giving written notice of revocation to us, which must be received no later than 3 business days prior to the due date of you loan. However, if you timely revoke this authorization to effect ACH debit entries before the loan is paid in full, you authorize us to prepare and submit one or more checks drawn on your Account on or after the due date of your loan. This authorization to prepare and submit a check on your behalf may not be revoked by you until such time as the loan is paid in full.'
								.															'</td>'
								.														'</tr>'
								.													'</table>'
								.												'</li>'
								.												'<li>'
								.													'You must provide us with a blank check from your checking account at the bank that is marked &quot;void&quot;. You authorize us to correct any missing or erroneous information you provide in item 5 by capturing the necessary information from that check.'
								.												'</li>'
								.												'<li>'
								.													'<b>Payment Options:</b>'
								.													'<br />'
								.													'<ol type="a" style="font-family: Arial, Helvetica, sans-serif; font-size: 12px; line-height: 11px;">'
								.														'<li>'
								.															'Refinance. Your loan will be refinanced on every* due date unless you notify us of your desire to pay in full or to pay down your principle amount borrowed. You will accrue a new fee every time your loan is refinanced. Any fees accrued will not go toward the principle amount owed.'
								.													'<br />'
								.													'*On your fifth refinance and every refinance thereafter, your loan will be paid down by $50.00. This means your account will be debited for the finance charge plus $50.00, this will continue until your loan is paid in full.'
								.												'</li>'
								.												'<li>'
								.													'Pay Down. You can pay down your principle amount by increments of $50.00. Paying down will decrease the fee charge for refinance. To accept this option you must notify us of your request in writing via fax at '
								.													$support_fax
								.													'at least three full business days before your loan is due.'
								.												'</li>'
								.												'<li>'
								.													'Pay Out. You can payout your full balance, the principle plus the fee for that period. To accept this option you must notify us of your request in writing via fax at '
								.													$support_fax
								.													'. The request must be received at least three full business days before your loan is due.'
								.												'</li>'
								.											'</ol>'
								.										'</li>'
								.										'<li>'
								.													'By electronically signing below, you acknowledge reading and agreeing to the statements in items 2, 3, and 4, the authorization in item 5, and the Payment Options in item 7.'
								.										'</li>'
								.									'</ol>'
								.										'</td>'
								.									'</tr>'
								.								'</table>'
								.								'<br />'
								.								'<br />'
								.								'<table width="600" border="0" cellspacing="0" cellpadding="0">'
								.									'<tr>'
								.										'<td width="4%" style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 14px; border-bottom: 1px solid #000000;">'
								.											'<b>'
								.												'(x)'
								.											'</b>'
								.										'</td>'
								.										'<td width="50%" style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 14px; border-bottom: 1px solid #000000; text-align: left !important; padding-left: 2px !important;">'
								.											'<strong>'
								.												$esignature
								.											'</strong>'
								.										'</td>'
								.										'<td width="45%" style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 14px; border-bottom: 1px solid #000000">'
								.											date("m/d/Y")
								.										'</td>'
								.									'</tr>'
								.									'<tr>'
								.										'<td width="4%" style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 14px;">'
								.											'&nbsp;'
								.										'</td>'
								.										'<td width="50%" style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 14px; text-align: left !important; padding-left: 2px !important;">'
								.											'<b>'
								.												'Electronic Signature'
								.											'</b>'
								.										'</td>'
								.										'<td width="45%" style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 14px;">'
								.											'<b>'
								.												'Date'
								.											'</b>'
								.										'</td>'
								.									'</tr>'
								.									'<tr>'
								.										'<td width="4%" style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 14px; border-bottom: 1px solid #000000;">'
								.											'<br />'
								.											'<b>'
								.												'(x)'
								.											'</b>'
								.										'</td>'
								.										'<td width="50%" style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 14px; border-bottom: 1px solid #000000; text-align: left !important; padding-left: 2px !important;">'
								.											'<br />'
								.											$name_first
								.											' '
								.											$name_last
								.										'</td>'
								.										'<td width="45%" style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 14px; border-bottom: 1px solid #000000; text-align: left !important; padding-left: 2px !important;">'
								.											'<br />'
								.											'<b>'
								.												'(x) '
								.											'</b>'
								.											$ssn_part_1
								.											'-'
								.											$ssn_part_2
								.											'-'
								.											$ssn_part_3
								.										'</td>'
								.									'</tr>'
								.									'<tr>'
								.										'<td width="4%" style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 14px;">'
								.											'&nbsp;'
								.										'</td>'
								.										'<td width="50%" style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 14px; text-align: left !important; padding-left: 2px !important;">'
								.											'<b>'
								.												'PRINT NAME'
								.											'</b>'
								.										'</td>'
								.										'<td width="45%" style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 14px;">'
								.											'<b>'
								.												'SOCIAL SECURITY NUMBER'
								.											'</b>'
								.										'</td>'
								.									'</tr>'
								.								'</table>'
								.								'<br /><br />'
								.								'<div style="text-align: right !important; padding-right: 2px !important; font-family: Arial, Helvetica, sans-serif; font-size: 9px; line-height: 9px;">'
								.									'site: '
								.									$site_name
								.								'</div>'
								.							'</div>'
////////////////////////////////
////////////////////////////////
// ENTIRE DOCUMENT STOP
////////////////////////////////
////////////////////////////////
								.					'</td>'
								.				'</tr>'
								.			'</table>'
								.		']]>'
								.	'</verbiage>' 
								.'</section>';

   return $content_string;
}

// How's that for a freakin' huge function definition?!
function Preview_Docs_New($name_first, $name_last, $property_short, $application_id, $site_name, $promo_id,
	$home_street, $home_unit, $home_city, $home_state, $home_zip, $phone_home, $phone_fax, $phone_cell,
	$phone_work, $date_dob_d, $date_dob_m, $date_dob_y, $ssn_part_1, $ssn_part_2, $ssn_part_3, $email_primary,
	$state_id_number, $title, $shift, $income_type, $bank_name, $bank_aba, $bank_account, $check_number,
	$ref_01_name_full, $ref_01_relationship, $ref_01_phone_home, $ref_02_name_full, $ref_02_relationship,
	$ref_02_phone_home, $esignature, $apr, $monthly_net, $net_pay, $pay_date1, $pay_date2, $pay_date3, $pay_date4,
	$income_direct_deposit, $income_frequency, $support_fax, $finance_charge, $fund_amount, $payoff_date,
	$fund_date, $property_name, $total_payments, $employer_name, $residence_length, $residence_type)
{
	
	$ent_prop_short = array (
		"oneclickcash.com" => "PCL",
		"unitedcashloans.com" => "UCL",
		"ameriloan.com" => "CA",
		"usfastcash.com" => "UFC",
		"500fastcash.com" => "D1");
	
	$ent_legal = array (
			"oneclickcash.com" => "One Click Cash",
			"unitedcashloans.com" => "United Cash Loans",
			"ameriloan.com" => "Ameriloan",
			"usfastcash.com" => "US Fast Cash",
			"500fastcash.com" => "500 Fast Cash",
			);
	
	$property_short = $ent_prop_short[strtolower($site_name)];
	$legal_entity = $ent_legal[strtolower($site_name)];
	
	// combine some fields
	$full_name = $name_first.' '.$name_last;
	$loan_id = $property_short.'-'.$application_id;
	$source = $site_name.' : '.$promo_id;
	$ssn = $ssn_part_1.'-'.$ssn_part_2.'-'.$ssn_part_3;
	$dob = $date_dob_m.'/'.$date_dob_d.'/'.$date_dob_y;
	
	// format as currency
	$total_payments = $this->Display('money', $total_payments);
	$finance_charge = $this->Display('money', finance_charge);
	$fund_amount = $this->Display('money', $fund_amount);
	$monthly_net = $this->Display('money', $monthly_net);
	$net_pay = $this->Display('money', $net_pay);
	
	// format phone numbers
	if ($phone_home && (!$phone_home=='NA')) $phone_home = $this->Display('phone', $phone_home);
	if ($phone_work && (!$phone_work=='NA')) $phone_work = $this->Display('phone', $phone_work);
	if ($phone_cell && (!$phone_cell=='NA')) $phone_cell = $this->Display('phone', $phone_cell);
	if ($phone_fax && (!$phone_fax=='NA')) $phone_fax = $this->Display('phone', $phone_fax);
	
	if ($residence_length)
		$residence_length = floor($residence_length / 12)." yrs ".($residence_length % 12)." mnths";
	else
		$residence_length = "NA";
		
	if ($income_type == "BENEFITS") $income_type = "benefits"; 
	else $income_type = "job";
	
	ob_start();
	
	?>
		
		<style type="text/css">
			
			body {
				 color: #000000;
				 background-color: #ffffff;
				 margin-top: 0px;
				 font-family: Arial, Helvetica, sans-serif;
			}
			
			div.legal-page {
				width: 600px;
				height: 860px;
				align: center;
				text-align: center;
			}
			div.legal-page * {
				margin: auto default;
			}
			div.legal-page * td {
				vertical-align: top;
			}
			.legal-50pctw {
				width: 50%;
				margin: auto;
			}
			.legal-60pctw {
				width: 60%;
				margin: auto;
			}
			.legal-80pctw {
				width: 80%;
				margin: auto;
			}
			.legal-90pctw {
				width: 90%;
				margin: auto;
			}
			.legal-100pctw {
				width: 100%;
				margin: auto;
			}
			div.legal-checkbox {
				width: 18px;
				height: 18px;
				border: 4px solid #000000;
				float: left;
				margin-right: 8px;
			}
			#wf-legal-checkbox-wrap {
				clear: both;
			}
			div.legal-checkbox-group {
				position: relative;
				display: inline;
				float: left;
				margin: 0px 10px;
				width: 46%;
			}
			div.legal-checkbox-group ul {
				margin: 0px;
				padding: 0px;
			}
			
			div.legal-checkbox-group ul li {
				list-style: none;
				margin: 0px;
				padding: 0px;
				clear: both;
				text-align: left;
				height: 2.5em;
			}
			
			#wf-legal-cancel {
				padding: 0px;
				margin: auto 10px;
			}
			#wf-legal-masthead {
				border: 5px solid #cccccc;
				padding: 5px;
			}
			#wf-legal-printbar {
				color: #ffffff;
				font-weight: bold;
				background-color: #000000;
				text-align: center;
				margin-bottom: 1em;
			}
			.legal-boxed, .legal-boxed td {
				border: 1px solid #000000;
			}
			
			#wf-legal-cancelauth, #wf-legal-cancelauth td {
				border-width: 0px !important;
			}
			
			#wf-legal-maininfo * {
				text-align: center;
			}
			
			#cxl-sig, #cxl-sig td {
			    border: 0px !important;
			}
			
			form {
				margin: 0px;
			}
			.legal-underline {
				border-bottom: 1px solid #000000;
			}
			.bigbold {
				font-family: Arial, Helvetica, sans-serif;
				font-size: 28px;
				line-height: 14px;
				font-weight: bold;
			}
				
			.bigboldu {
				font-family: Arial, Helvetica, sans-serif;
				font-size: 20px;
				line-height: 18px;
				font-weight: bold;
				decoration: underline;
			}
				
			.norm {
				font-family: Arial, Helvetica, sans-serif;
				font-size: 14px;
				line-height: 14px;
			}
				
			.norm2 {
				font-family: Arial, Helvetica, sans-serif;
				font-size: 12px;
				line-height: 11px;
			}
				
			.small {
				font-family: Arial, Helvetica, sans-serif;
				font-size: 9px;
				line-height: 9px;
			}
				
			.huge {
				font-family: Arial, Helvetica, sans-serif;
				font-size: 80px;
				font-weight: bold;
			}
				
			.subhead {
				 font-family: Arial, Helvetica, sans-serif;
				font-size: 14px;
				line-height: 16px;
				color: #000000;
			}
				
			.med {
				font-family: Arial, Helvetica, sans-serif;
				font-size: 11px;
				line-height: 10px;
			}
				
			br.break {
				page-break-before: always;
			}
				
			br.breakhere {
				page-break-before: always;
			}
			
			.sh-align-left {
				text-align: left !important;
			    padding-left: 2px !important;
			}
			.sh-align-right {
				text-align: right !important;
			    padding-right: 2px !important;
			}
			.sh-align-center {
				text-align: center !important;
			    padding: auto 2px !important;
			}
			
			.sh-bold {
			    font-weight: bold;
			}
			
		</style>
		
		<div class="legal-page">
			<table class="legal-100pctw" border="0" cellspacing="0" cellpadding="2">
				<tr>
					<td class="norm2 sh-align-center" width="38%">
						Applicant: <b><u><span class="med"><?php echo($full_name); ?></span></u></b><br />
						Loan ID: <b><?php echo($loan_id); ?></b>
					</td>
					<td class="bigboldu sh-align-center" width="24%">Application</td>
					<td class="med sh-align-center" width="38%">
						Date: <?php echo(date("m/d/Y")); ?><br />
						src: <?php echo($source); ?>
					</td>
				</tr>
			</table> 
			<table id="wf-legal-maininfo" class="legal-100pctw legal-boxed" cellspacing="0" cellpadding="1">
				<tr>
					<td colspan="2" class="norm">
						<b>Personal Information</b>
					</td>
				</tr>
				<tr>
					<td width="50%" class="norm2"><b>Applicant Name: <u><span class="med"><?php echo($full_name) ?></span></u></b></td>
					<td class="norm2" rowspan="3">
						<b>Applicants Address:</b><br />
						<?php echo($home_street.' '.$home_unit); ?><br />
						<?php echo($home_city); ?>, <?php echo($home_state); ?>&nbsp;<?php echo($home_zip); ?>
					</td>
				</tr>
				
				<tr><td width="50%" class="norm2"><b>DOB:</b> <?php echo($dob); ?></td></tr>
				<tr><td width="50%" class="norm2"><b>SS#</b>: <?php echo($ssn); ?></td></tr>
				
				<tr>
					<td width="50%" class="norm2"><b>Home Phone #:</b> <?php echo($phone_home); ?></td>
					<td class="norm2">
						<b>Length at address:</b>
						<?php echo($residence_length); ?>
						<br />
						<? echo($residence_type); ?>
					</td>
				</tr>
				<tr>
					<td width="50%" class="norm2"><b>Fax Number:</b> <?php echo($phone_fax); ?></td>
					<td class="norm2"><b>E-Mail address:</b> <?php echo($email_primary); ?></td>
				</tr>
				<tr>
					<td width="50%" class="norm2"><b>Cell Number:</b> <?php echo($phone_cell); ?></td>
					<td class="norm2"><b>Drivers License:</b> <?php echo($state_id_number); ?></td>
				</tr>
				<tr><td colspan="2" class="norm"><b><span class="norm">Employment / Income Information</b></td></tr>
				<tr>
					<td width="50%" class="norm2"><b>Employer:</b> <?php echo $employer_name; ?></td>
					<td class="norm2"><b>Income comes from?</b> <?php echo($income_type); ?></td>
				</tr>
				<tr>
					<td width="50%" class="norm2"><b>Your work phone:</b> <?php echo($phone_work); ?></td>
					<td width="50%" class="norm2">&nbsp;</td>
				</tr>
				<tr>
					<td width="50%" class="norm2"><b>Length of Employment:</b> 0 Yrs&nbsp;&nbsp;&nbsp;&nbsp;3+ Mths&nbsp;&nbsp;&nbsp;&nbsp;</td>
					<td class="norm2"><b>Monthly Take Home pay*:</b> $<?php echo($monthly_net); ?></td>
				</tr>
				<tr>
					<td width="50%" class="norm2"><b>Position:</b> <?php echo($title); ?></td>
					<td class="norm2"><b>Net pay each pay check*:</b> $<?php echo($net_pay); ?></td>
				</tr>
				<tr>
					<td width="50%" class="norm2"><b>Shift/Hours:</b> <?php echo($shift); ?></td>
					<td class="norm2">
						<b>Next four pay dates: </b>
						<? echo($pay_date1); ?> &amp;
						<? echo($pay_date2); ?> &amp;
						<? echo($pay_date3); ?> &amp;
						<? echo($pay_date4); ?>
					</td>
				</tr>
				<tr>
					<td width="50%" class="norm2"><b>Direct Deposit?:</b> <?php echo(($income_direct_deposit == 'TRUE') ? "Yes" : "No"); ?></td>
					<td class="norm2"><b>Paid how often:</b> <?php echo(str_replace("_", "-", strtolower($income_frequency))); ?></td>
				</tr>
				<tr><td colspan="2" class="norm"><b>Checking Account Information</b></td></tr>
				<tr>
					<td width="50%" class="norm2"><b>BANK NAME:</b>	<?php echo($bank_name); ?></td>
					<td class="norm2"><b>ABA/ROUTING: </b><?php echo($bank_aba); ?></td>
				</tr>
				<tr>
					<td width="50%" class="norm2"><b>ACCOUNT NUMBER:</b> <?php echo($bank_account); ?></td>
					<td class="norm2"><b>NEXT CHECK NUMBER:</b> <?php echo($check_number); ?></td>
				</tr>
				<tr><td colspan="2" class="norm"><b>Personal References</b></td></tr>
				<tr>
					<td width="50%" class="norm2"><b>Ref #1 name:</b> <?php echo($ref_01_name_full); ?></td>
					<td class="norm2"><b>Ref #2 name:</b><?php echo($ref_02_name_full); ?></td>
				</tr>
				<tr>
					<td width="50%" class="norm2"><b>Ref #1 phone:</b> <?php echo($ref_01_phone_home); ?></td>
					<td class="norm2"><b>Ref #2 phone:</b> <?php echo($ref_02_phone_home); ?></td>
				</tr>
				<tr>
					<td width="50%" class="norm2"><b>Ref #1 relationship:</b> <?php echo($ref_01_relationship); ?></td>
					<td class="norm2"><b>Ref #2 relationship: </b> <?php echo($ref_02_relationship); ?></td>
				</tr>
			</table>
			
			<div class="small sh-align-left">
				*or other source of income periodically deposited to your account. However, alimony, child support, or separate
				maintenance income need not be revealed if you do not wish to have it considered as a basis for repaying this
				obligation.<br />
				
				<u>NOTICE: We adhere to the Patriot Act and we are required by law to adopt procedures to request and retain in
				our records information  necessary to verify your identity.</u>
				
				<div class="small sh-align-left">
					<u>Agreement to Arbitrate All Disputes</u>: By signing below or electronically signing and to induce us,
					<?php echo($legal_entity); ?>, to process your application for a loan, you and we agree that any and all claims,
					disputes or controversies that we or our servicers or agents have against you or that you have against us, our
					servicers, agents, directors, officers and employees, that arise out of your application for one or more loans,
					the Loan Agreements that govern your repayment obligations, the loan for which you are applying or any other loan
					we previously made or later make to you, this Agreement To Arbitrate All Disputes, collection of the loan or loans,
					or alleging fraud or misrepresentation, whether under the common law or pursuant to federal or state statute or
					regulation, or otherwise, including disputes as to the matters subject to arbitration, shall be resolved by binding
					individual (and not class) arbitration by and under the Code of Procedure of the National Arbitration Forum
					(&quot;NAF&quot;) in effect at the time the claim is filed.  This agreement to arbitrate all disputes shall apply
					no matter by whom or against whom the claim is filed. Rules and forms of the NAF may be obtained and all claims
					shall be filed at any NAF office, on the World Wide Web at <u>www.arb-forum.com</u>, or at &quot;National
					Arbitration Forum, P.O. Box 50191, Minneapolis, Minnesota 55405.&quot;  If you are unable to pay the costs of
					arbitration, your arbitration fees may be waived by the NAF. The cost of a participatory hearing, if one is held
					at your or our request, will be paid for solely by us if the amount of the claim is $15,000 or less. Unless
					otherwise ordered by the arbitrator, you and we agree to equally share the costs of a participatory hearing of the
					claim is for more than $15,000 or less than $75,000. Any participatory hearing will take place at a location near
					your residence. This arbitration agreement is made pursuant to a transaction involving interstate commerce. It shall
					be governed by the Federal Arbitration Act, 9 U.S.C. Sections 1-16.  Judgment upon the award may be entered by any
					party in any court having jurisdiction. This Agreement to Arbitrate All Disputes is an independent agreement and
					shall survive the  closing, funding, repayment and/or default of the loan for which you are applying.<br />
					
					NOTICE: YOU AND WE WOULD HAVE HAD A RIGHT OR OPPORTUNITY TO LITIGATE DISPUTES THROUGH A COURT AND HAVE A JUDGE
					OR JURY DECIDE THE DISPUTES BUT HAVE AGREED INSTEAD TO RESOLVE DISPUTES THROUGH BINDING ARBITRATION.<br />
					
					<u>Agreement Not To Bring, Join Or Participate In Class Actions</u>:
					To the extent permitted by law, by signing below or electronically signing you agree that you will not bring,
					join or participate in any class action as to any claim, dispute or controversy you may have against us or
					our agents, servicers, directors, officers and employees. You agree to the entry of injunctive relief to stop
					such a lawsuit or to remove you as a participant in the suit. You agree to pay the costs we incur, including
					our court costs and attorney's fees, in seeking such relief. This agreement is not a waiver of any of your
					rights and remedies to pursue a claim individually and not as a class action in binding arbitration as provided
					above. This agreement not to bring, join or participate in class action suites is an independent agreement and
					shall survive the closing, funding, repayment, and/or default of the loan for which you are applying.<br/>
					
					<b>Borrower's Electronic Signature to the above Agreements Appears Below</b><br/>
					By signing below or electronically signing this Application you certify that all of the information provided above is
					true, complete and correct  and provided to us, <?php echo($legal_entity); ?>, for the purpose of inducing us to make
					the loan for which you are applying.  You also agree to the Agreement to Arbitrate All Disputes and the Agreement Not
					To Bring, Join Or Participate in Class Actions. You authorize <?php echo($legal_entity); ?> to verify all information
					that you have provided and acknowledge that this information may be used to verify certain past and/or current credit
					or payment history information from third party source(s). <?php echo $legal_entity; ?> may utilize Check Loan Verification
					or other similar consumer-reporting agency for these purposes.  We may disclose all or some of the nonpublic personal
					information about you that we collect to financial service providers that perform services on our behalf, such as the
					servicer of your short term loan, and to financial institutions with which we have joint marketing arrangements. Such
					disclosures are made as necessary to effect, administer and enforce the loan you request or authorize and any loan you
					may request or authorize with other financial institutions with regard to the processing, funding, servicing, repayment
					and collection of your loan. <b>(This Application will be deemed incomplete and will not be processed by us unless signed
					by you below.)</b>
					</p>
				</div>
				<br />
				<table id="wf-legal-cancelauth" width="100%" border="0" cellspacing="0" cellpadding="4">
					<tr>
						<td class="norm sh-align-left"><b>(X) </b><?php echo($esignature);?></td>
						<td class="norm sh-align-left"><b>(X) </b><u><span class="med"><b><?php echo($full_name); ?></b></span></u></td>
						<td class="norm sh-align-left"><b>(X) <u><span class="med"><?php echo(date("m/d/Y")); ?></span></u></b></td>
					</tr>
					<tr>
						<td class="norm">&nbsp;Electronic Signature of Applicant</td>
						<td class="norm">&nbsp;Printed Name of Applicant</td>
						<td class="norm">&nbsp;Date</td>
					</tr>
				</table>
			</div>
			
			<p class="small"><b>SHORT TERM LOANS PROVIDE THE CASH NEEDED TO MEET IMMEDIATE SHORT-TERM CASH FLOW PROBLEMS. THEY ARE NOT
				A SOLUTION FOR LONGER TERM FINANCIAL PROBLEMS FOR WHICH OTHER KINDS OF FINANCING MAY BE MORE APPROPRIATE. YOU MAY WANT TO
				DISCUSSYOUR FINANCIAL SITUATION WITH A NONPROFIT FINANCIAL COUNSELING SERVICE.</b></p>
		</div>
		
		<br class="breakhere" />
		
		<div class="legal-page">
			<br />
			
			<div class="legal-boxed norm2">
				<br />
				
				<div class="bigbold sh-align-center"><a name="privacy_policy"></a>
					<u>Privacy Policy</u>
				</div>
				
				<p class="sh-align-left"><b>PRIVACY POLICY</b>. Protecting your privacy is important to <?php echo($legal_entity); ?> and
					our employees. We want you to understand what information we collect and how we use it. In order to provide our customers
					with short term loans as effectively and conveniently as possible, we use technology to manage and maintain customer
					information, The following policy serves as a standard for all <?php echo($legal_entity); ?> employees for collection,
					use, retention, and security of nonpublic personal information related to our short term programs.</p>
				
				<p class="sh-align-left"><b>WHAT INFORMATION WE COLLECT</b>. We may collect &quot;nonpublic personal information&quot;
					about you from the following sources: Information we receive from you on applications or other loan forms, such as your
					name, address, social security number, assets and income; Information about your loan transactions with us, such as
					your payment history and loan balances; and Information we receive from third parties, such as consumer reporting
					agencies and other lenders, regarding your creditworthiness and credit history. &quot;Nonpublic personal
					information&quot; is nonpublic information about you that we obtain in connection with providing a short term loan to
					you or list derived using that information. For example, as noted above, nonpublic personal information includes your
					name, social security number, payment history, and the like.</p>
				
				<p class="sh-align-left"><b>WHAT INFORMATION WE DISCLOSE</b>. We are permitted by law to disclose nonpublic personal
					information about you to third parties in certain circumstances, For example, we may disclose nonpublic personal
					information about your short term loan to consumer reporting agencies and to government entities in response to
					subpoenas. Moreover, we may disclose all of the nonpublic personal information about you that we collect, as described
					above, to financial service providers that perform services on our behalf, such as the marketers and services of your
					short term loan, and to financial institutions with which we have joint marketing arrangements. Such disclosures are
					made as necessary to effect, administer and enforce the loan you request or authorize. Otherwise, we do not disclose
					nonpublic financial information about our customers or former customers to anyone, except as permitted by law.</p>
				
				<p class="norm2 sh-align-center"><b>RIGHT TO CANCEL: YOU MAY CANCEL THIS LOAN WITHOUT COST OR FURTHER OBLIGATION TO
					US, IF YOU DO SO BY THE END OF BUSINESS ON THE BUSINESS DAY AFTER THE LOAN PROCEEDS ARE DEPOSITED INTO YOUR CHECKING
					ACCOUNT.</b></p>
				
				<div id="wf-legal-cancel" class="med legal-boxed sh-align-left">
					<p class="med">To cancel, you must to alert us of your intention to cancel. You must also complete the information in
						this box, and sign and fax it to us at <?php echo($support_fax); ?>. If you follow these procedures but there are
						insufficient funds available in your deposit account to enable us to reverse the transfer of loan proceeds at the time
						we effect an ACH debit entry of your checking account, your cancellation will not be effective and you will be required
						to pay the loan and our charges on the scheduled maturity date.</p>
					
					<p class="med"><b>YOU WISH TO CANCEL.</b> You authorize us to initiate ACH debit entry to your checking account of the
						amount of the loan proceeds we deposited to that account at your request</p>
		      
					<br />
		      <table width="520" border="0" cellspacing="0" cellpadding="2" id="cxl-sig">
		      	<tr>
		      		<td width="30%" height="24" class="norm sh-align-left">____________________&nbsp;</td>
		      		<td width="31%" height="24" class="norm sh-align-left">____________________&nbsp;</td>
		      		<td width="24%" class="norm sh-align-left">______________&nbsp;</td>
		      		<td width="15%" class="norm sh-align-left">____________</td>
		      	</tr>
		      	<tr>
		      		<td width="30%" class="small sh-align-left">Print Name</td>
		      		<td width="31%" class="small sh-align-left">Signature</td>
							<td width="24%" class="small sh-align-left">Social Security Number</td>
		      		<td width="15%" class="small sh-align-left">Date</td>
		      	</tr>
		      </table>
				</div>
				
				<br />
			</div>
			
			<br /><br />
			<div class="bigboldu">NOTICE: DO NOT SIGN THE ABOVE OR FAX THIS PAGE<br />UNLESS YOU INTEND TO CANCEL YOUR LOAN</div>
		</div>
		
		<br class="breakhere" />
		
		<div class="legal-page">
			
			<a name="loan_note_and_disclosure"></a>
			<table border="0" cellspacing="0" cellpadding="2" width="100%">
				<tr> 
					<td class="sh-align-left norm sh-bold" width="45%">LOAN NOTE AND DISCLOSURE</td>
					<td class="sh-align-right sh-bold norm" width="55%"><?php echo($site_name); ?>&nbsp;</td>
				</tr>
				<tr>
					<td class="sh-align-left sh-bold norm2" width="50%">Borrower's Name: <?php echo($full_name); ?></td>
					<td align="right" class="norm2" width="50%">
						Date: <?php echo(date("m/d/Y")); ?>
						&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
						ID#: <b><?php echo($loan_id); ?></b>
					</td>
				</tr>
			</table>
			
			<div class="legal-underline"></div>
			
			<div class="med sh-align-left">
				<u>Parties:</u>  In this Loan Note and Disclosure (&quot;Note&quot;) you are the person named as Borrower above.
				&quot;We&quot; <?php echo($legal_entity); ?> are the lender (the &quot;Lender&quot;).<br />
				All references to &quot;we&quot;, &quot;us&quot; or &quot;ourselves&quot; means the Lender. Unless this Note specifies
				otherwise or unless we notify you to the contrary in writing, all notices and documents you are to provide to us shall
				be provided to <?php echo($legal_entity); ?> at the fax number and address specified in this Note and in your other
				loan documents.<br />
				<u>The Account</u>: You have deposit account, No. <?php echo($bank_account); ?> (&quot;Account&quot;), at
				<?php echo($bank_name); ?> (&quot;Bank&quot;). You authorize us to effect a credit entry to deposit the proceeds of the
				Loan (the Amount Financed indicated below) to your Account at the Bank.<br />
				DISCLOSURE OF CREDIT TERMS: The information in the following box is part of this Note.
			</div>
			
			<table class="legal-boxed" cellspacing="0" cellpadding="0">
				<tr> 
					<td class="norm2 sh-align-left" width="25%" style="border: 3px solid #000000">
						<b>ANNUAL PERCENTAGE RATE</b><br />The cost of your credit as a yearly rate (e)<br />
						<div class="sh-align-center"><b><?php echo round($apr, 2); ?>%</b></div>
					</td>
					<td class="norm2 sh-align-left" width="25%" style="border: 3px solid #000000">
						<b>FINANCE CHARGE</b><br />The dollar amount the credit will cost you.<br />
						<div align="center"><b>$<?php echo($finance_charge); ?></b></div>
					</td>
					<td class="norm2 sh-align-left" width="25%">
						<b>Amount Financed</b><br />The amount of credit provided to you or on your behalf.
						<div class="sh-align-center"><b>$<?php echo($fund_amount); ?></b></div>
					</td>
					<td class="norm2 sh-align-left" width="25%">
						<b>Total of Payments</b><br />The amount you will have paid after you have made the scheduled payment.<br />
						<div class="sh-align-center"><b>$<?php echo($total_payments); ?></b></div>
					</td>
				</tr>
				<tr> 
					<td colspan="4" class="med sh-align-left">
						Your <b>Payment Schedule</b> will be: 1 payment of <b>$<?php echo($total_payments); ?></b> due on
						<b><?php echo($payoff_date) ?></b>, if you decline* the option of renewing your loan. If renewal is accepted you will
						pay the finance charge of $<?php echo($finance_charge); ?>only, on <?php echo($payoff_date); ?>. You will accrue new
						finance charges with every renewal of your loan. On the due date resulting from a fourth renewal and every renewal due
						date thereafter, your loan must be paid down by $50.00. This means your Account will be debited the finance charge
						plus $50.00 on the due date. This will continue until your loan is paid in full.<br/>
						* To decline the option of renewal you must sign the Account Summary page and fax it back to our office at least
						three Business Days before your loan is due.<br />
						
						<b>Security:</b> The loan is unsecured.<br />
						
						<b>Prepayment</b>: <u>You may prepay your loan only in increments of $50.00.</u> If you prepay your loan in advance,
						you will not receive a refund of any Finance Charge.(e) The Annual Percentage Rate is estimated based on the anticipated
						date the proceeds will be deposited to or paid on your account, which is <?php echo($fund_date); ?>.<br />
						
						See below and your other contract documents for any additional information about prepayment, nonpayment and default.
					</td>
				</tr>
			</table>
			
			<div class="med sh-align-left">
				<b><u>Itemization Of Amount Financed of $<?php echo($fund_amount); ?></u>; Given to you directly:
					<u>$<?php echo($fund_amount); ?></u>; Paid on your account <u>$0</u></b><br />
				
				<b><u>Promise To Pay:</u></b> You promise to pay to us or to our order and our assignees, on the date indicated in the
					Payment Schedule, the Total of Payments, unless this Note is renewed. If this Note is renewed, then on the Due Date, you
					will pay the Finance Charge shown above. This Note will be renewed on the Due Date unless at least three Business Days 
					Before the Due Date either you tell us you do not want to renew the Note or we tell you that the Note will not be renewed.
					Information regarding the renewal of your loan will be sent to you prior to any renewal showing the new due date, finance
					charge and all other disclosures. As used in the Note, the term &quot;Business Day&quot; means a day other than Saturday,
					Sunday or legal holiday, that <?php echo($legal_entity); ?> is open for business. This Note may be renewed four times
					without having to make any principle payments on the Note. If this Note is renewed more than four times, then on the due
					date resulting from your fourth renewal, and on the due date resulting from each and every subsequent renewal, you must
					pay the finance charge required to be paid on that due date and make a principle payment of $50.00. Any payment due on
					the Note shall be made by us effecting one or more ACH debit entries to your Account at the Bank. You authorize us to
					effect this payment by these ACH debit entries. You may revoke this authorization at any time up to three Business Days
					prior to the date any payment becomes due on this Note. However, if you timely revoke this authorization, you authorize
					us to prepare and submit a check drawn on your Account to repay your loan when it comes due. If there are insufficient
					funds on deposit in your Account to effect the ACH debit entry or to pay the check or otherwise cover the loan payment
					on the due date, you promise to pay us all sums you owe by mailing a check or Money Order payable
					to: <?php echo($legal_entity); ?>.<br />
				
				<u><b>Return Item Fee</b></u>: If sufficient funds are not available in the Account on the due date to cover the ACH debit
					entry or check, you agree to pay us a Return Item Fee of $30.<br />
					
		    <b><u>Prepayment</u></b>: The Finance Charge consists solely of a loan fee that is earned in full at the time the loan
			    is funded.  Although you may pay all or part of your loan in advance without penalty, you will not receive a refund or
			    credit of any part or all of the Finance Charge.<br />
		    	
		    <u><b>Governing Law</b></u>: This Note and your Account are governed by the federal laws of the United States, regardless
		    	of which state you may reside.<br />
		    	
				<u><b>Arbitration of All Disputes</b></u>: <b>You and we agree that any and all claims, disputes or controversies between
					you and us, any claim by either of us against the other (or the employees, officers, directors, agents, servicers or
					assigns of the other) and any claim arising from or relating to your application for this loan, regarding this loan or
					any other loan you previously or may later obtain from us, this Note, this agreement to arbitrate all disputes, your
					agreement not to bring, join or participate in class actions, regarding collection of the loan, alleging fraud or
					misrepresentation, whether under common law or pursuant to federal, state or local statute, regulation or ordinance,
					including disputes regarding the matters subject to arbitration, or otherwise, shall be resolved by binding individual
					(and not joint) arbitration by and under the Code of Procedure of the National Arbitration Forum (&quot;NAF&quot;) in
					effect at the time the claim is filed. No class arbitration. All disputes including any Representative Claims against
					us and/or related third parties shall be resolved by binding arbitration only on an individual basis with you. THEREFORE,
					THE ARBITRATOR SHALL NOT CONDUCT CLASS ARBITRATION; THAT IS, THE ARBITRATOR SHALL NOT ALLOW YOU TO SERVE AS A
					REPRESENTATIVE, AS A PRIVATE ATTORNEY GENERAL, OR IN ANY OTHER REPRESENTATIVE CAPACITY FOR OTHERS IN THE ARBITRATION.
					This agreement to arbitrate all disputes shall apply no matter by whom or against whom the claim is filed. Rules and
					forms of the NAF may be obtained and all claims shall be filed at any NAF office, on the World Wide Web at
					<u>www.arb-forum.com</u>, by telephone at 800-474-2371, or at &quot;National Arbitration Forum, P.O. Box 50191,
					Minneapolis, Minnesota 55405.&quot; Your arbitration fees will be waived by the NAF in the event you cannot afford to
					pay them. The cost of any participatory, documentary or telephone hearing, if one is held at your or our request, will
					be paid for solely by us as provided in the NAF Rules and, if a participatory hearing is requested, it will take place
					at a location near your residence. This arbitration agreement is made pursuant to a transaction involving interstate
					commerce. It shall be governed by the Federal Arbitration Act, 9 U.S.C. Sections 1-16. Judgment upon the award may be
					entered by any party in any court having jurisdiction.</b><br />
					
				<span class="small">
			    <b>NOTICE:</b> YOU AND WE WOULD HAVE HAD A RIGHT OR OPPORTUNITY TO LITIGATE DISPUTES THROUGH A COURT AND HAVE A JUDGE
			    OR JURY DECIDE THE DISPUTES BUT HAVE AGREED INSTEAD TO RESOLVE DISPUTES THROUGH BINDING ARBITRATION</td>
				</span>
				
				<b><u>Agreement Not To Bring, Join Or Participate In Class Actions:</u></b> To the extent permitted by law, you agree that
					you will not bring, join or participate in any class action as to any claim, dispute or controversy you may have against
					us, our employees, officers, directors, servicers and assigns.  You agree to the entry of injunctive relief to stop such
					a lawsuit or to remove you as a participant in the suit.  You agree to pay the attorney's fees and court costs we incur
					in seeking such relief.  This agreement does not constitute a waiver of any of your rights and remedies to pursue a
					claim individually and not as a class action in binding arbitration as provided above.<br />
					
				<b><u>Survival:</u></b> The provisions of this Loan Note And Disclosure dealing with the Agreement To Arbitrate All 
					Disputes and the Agreement Not To Bring, Join Or Participate In Class Actions shall survive repayment in full and/or
					default of this Note.<br />
				
		    <b><u>No Bankruptcy:</u></b> By signing below or electronically signing you represent that you have not recently filed
		    	for bankruptcy and you do not plan to do so.<br />
		    	
		  	<b><u>NOTICE: We adhere to the Patriot Act and we are required by law to adopt procedures to request and retain in our
		  		records information necessary to verify your identity.</u></b><br />
		  		
				By signing or electronically signing this Loan Note you certify that all of the information provided above is true,
				complete and correct and provided to us, <?php echo($legal_entity); ?>, for the purpose of inducing us to make the loan
				for which you are applying. By signing below or electronically signing you also agree to the Agreement to Arbitrate All
				Disputes and the Agreement Not To Bring, Join Or Participate in Class Actions. By signing or electronically signing
				this application you authorize <?php echo($legal_entity); ?> to verify all information that you have provided and
				acknowledge that this information may be used to verify certain past and/or current credit or payment history information
				from third party source(s).  <?php echo($legal_entity); ?> may utilize Check Loan Verification or other similar
				consumer-reporting agency for these purposes. We may disclose all or some of the nonpublic personal information about
				you that we collect to financial service providers that perform services on our behalf, such as the servicer of your short
				term loan, and to financial institutions with which we have joint marketing arrangements.  Such disclosures are made as 
				necessary to effect, administer and enforce the loan you request or authorize and any loan you may request or authorize
				with other financial institutions with regard to the processing, funding, servicing, repayment and collection of your
				loan. (This Application will be deemed incomplete and will not be processed by us unless signed by you below.)<br />
				
				<br />
			</div>
			
			<table border="0" cellspacing="2" cellpadding="0">
				<tr> 
					<td>
						<br />
						<table width="100%" cellspacing="0" cellpadding="1">
							<tr>
								<td class="med legal-underline sh-align-left"><strong>(X) <?php echo($esignature);?></strong></td>
								<td class="med legal-underline sh-align-center" width="30%"><?php echo(date("m/d/Y")); ?></td>
							</tr>
							<tr>
								<td class="med sh-align-left">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Electronic Signature</td>
								<td class="med sh-align-center" width="30%">Date</td>
							</tr>
							<tr>
								<td class="med legal-underline sh-align-left" colspan="2"><br />
									&nbsp;&nbsp;&nbsp;&nbsp;<?php echo($full_name); ?>
								</td>
							</tr>
							<tr><td class="med sh-align-left" colspan="2">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Print Name</td></tr>
						</table>
					</td>
					<td width="30%" class="small legal-boxed"><b>INSTRUCTIONS: YOU WILL BE ADVISED OF YOUR APPROVAL VIA PHONE OR
						EMAIL.</b><br /><br /></td>
				</tr>
			</table>
		
		
		<br class="breakhere" />
		
		<div class="legal-page">
			<a name="auth_agreement"></a>
			<div class="sh-align-right bigboldu"><?php echo($legal_entity); ?></div>
			<div class="sh-align-center norm"><b>Privacy Policy and Authorization Agreement</b></div>
			
			<div class="med sh-align-left">
				<b>PRIVACY POLICY.</b> Protecting your privacy is important to <?php echo($legal_entity); ?> and our employees. We want
				you to understand what information we collect and how we use it. In order to provide our customers with short term loans
				as effectively and conveniently as possible, we use technology to manage and maintain customer information. The following
				policy serves as a standard for all <?php echo($legal_entity); ?> employees for collection, use, retention, and security
				of nonpublic personal information related to our short term programs.<br />
				<b>WHAT INFORMATION WE COLLECT.</b> We may collect &quot;nonpublic personal information&quot; about you from the following
				sources: Information we receive from you on applications or other loan forms, such as your name, address, social security
				number, assets and income; Information about your loan transactions with us, such as your payment history and loan
				balances; and Information we receive from third parties, such as consumer reporting agencies and other lenders, regarding
				your creditworthiness and credit history. &quot;Nonpublic personal information&quot; is nonpublic information about you
				that we obtain in connection with providing a short term loan to you.  For example, as noted above, nonpublic personal
				information includes your name, social security number, payment history, and the like.<br />
				<b>WHAT INFORMATION WE DISCLOSE.</b> We are permitted by law to disclose nonpublic personal information about you to third 
				parties in certain circumstances.  For example, we may disclose nonpublic personal information about your short term loans 
				to consumer reporting agencies and to government entities in response to subpoenas. Moreover, we may disclose all of the 
				nonpublic personal information about you that we collect, as described above, to financial service providers that perform 
				services on our behalf, such as the servicer of your short term loan, and to financial institutions with which we have joint 
				marketing arrangements.  Such disclosures are made as necessary to effect, administer and enforce the loan you request or
				authorize.<br />
				If you become an inactive customer, we will continue to adhere to the privacy policies and practices described in this
				notice.<br />
				<b>OUR SECURITY PROCEDURES.</b>  We also take steps to safeguard customer information.  We restrict access to nonpublic 
				personal information about you to those of our and our marketers/servicers employees who need to know that information to 
				provide short term loans to you.  We maintain physical, electronic and procedural safeguards that comply with federal
				standards to guard your nonpublic personal information.
			</div>
			
			<div class="legal-underline"></div>
			
			<div class="norm sh-align-left">
				
				<ol class="norm">
					<li><b>BY SIGNING OR ELECTRONICALLY SIGNING BELOW YOU VERIFY BANK, RESIDENCE, AND EMPLOYMENT INFORMATION as printed in
						item 5 and 6.</b></li>
					<li><b>UNLESS the authorization in item 6 below  is properly and timely revoked, THERE WILL BE A $30.00 FEE ON ANY ACH
						DEBIT ENTRY ITEMS THAT ARE RETURNED AT TIME OF COLLECTION.</b></li>
					<li><b>YOU AUTHORIZE US to contact you at your place of employment or residence at any time up to 9:00 p.m., your local
						time.</b></li>
					<li><b>YOU REPRESENT that you have NOT RECENTLY FILED FOR BANKRUPTCY and have NO PRESENT INTENTIONS OF DOING SO.</b></li>
					<li><b>YOU REPRESENT that your employer remains: <?php echo($employer_name); ?><br />
						and your residence remains:  <?php echo($home_street." ".$home_unit); ?>&nbsp;&nbsp;
							<?php echo($home_city); ?>,<?php echo($home_state); ?>&nbsp;<?php echo($home_zip); ?></b></li>
					<li>
						<b>You authorize us</b>,<?php echo($legal_entity); ?>, or our servicer, agent, or affiliate to initiate one or more
						ACH debit entries (for example, at our option, one debit entry may be for the principle of the loan and another for
						the finance charge) to your Deposit Account indicated below for the payments that come due each pay period and/or each
						due date concerning every renewal, with regard to the loan for which you are applying. <b>YOU REPRESENT </b> that
						your Depository Institution named below, called BANK, will receive and debit such entry to your Bank Account, remains:
						
						<table class="legal-boxed legal-90pctw" cellspacing="0" cellpadding="3">
							<tr> 
								<td width="33%" class="norm"> 
									<b><u>Bank Name</u></b>
									<div class="sh-align-center">
										<b><?php echo($bank_name); ?></b>
									</div>
								</td>
								<td width="33%" class="norm"> 
									<b><u>Routing/ABA No.</u></b>
									<div class="sh-align-center">
										<b><?php echo($bank_aba); ?></b>
									</div>
								</td>
								<td width="33%" class="norm"> 
									<b><u>Account No.</u></b>
									<div class="sh-align-center">
										<b><?php echo($bank_account); ?></b>
									</div>
								</td>
							</tr>
						</table>
						
						<div class="sh-align-center norm">Please See Item 7, below, if any Information has changed.</div><br/><br />
						<div class="sh-align-left med">
							This Authorization becomes effective at the time we make you the loan for which you are applying and will remain in
							full force and effect until we have received notice of revocation from you.  This authorizes us to make debit entries
							with regard to any other loan you may have received with us. You may revoke this authorization to effect an ACH
							debit entry to your Account(s) by giving written notice of revocation to us, which must be received no later than
							3 business days prior to the due date of you loan.  However, if you timely revoke this authorization to effect ACH
							debit entries before the loan(s) is paid in full, you authorize us to prepare and submit one or more checks drawn
							on your Account(s) on or after the due date of your loan. This authorization to prepare and submit a check on your
							behalf may not be revoked by you until such time as the loan(s) is paid in full.
						</div>
					</li>
					<li><b>If there is any change in your Bank Information in item 6 above, you MUST PROVIDE US WITH A NEW BLANK CHECK FROM 
						YOUR CHECKING ACCOUNT MARKED &quot;VOID&quot;.  You authorize us to correct any missing or erroneous information that
						you provide by calling the bank or capturing the necessary information from that check. You must provide us with a
						blank check from your checking account marked &quot;VOID&quot;. You authorized us to correct any missing or erroneous
						information that you provide by calling the bank or capturing the necessary information from that check.</b></li>
					<li style="border: 2px solid #000000">
						<b>Payment Options:</b>
						<ol type="a" class="norm2">
							<li>Renewal.  Your loan will be renewed on every* due date unless you notify us of your desire to pay in 
								full or to pay down your principle amount borrowed. You will accrue a new fee every time your loan is renewed.  
								Any fees accrued will not go toward the principle amount owed.<br />
								*On your fifth renewal and every renewal thereafter, your loan will be paid down by $50.00.  
								This means your account will be debited for the finance charge plus $50.00, this will continue 
								until your loan is paid in full.</li>
							<li>Pay Down.  You can pay down your principle amount by increments of $50.00.  Paying down will decrease the fee
								charge for renewal. To accept this option you must notify us of your request in writing via fax at
								<?php echo($support_fax); ?>, at least three full business days before your loan is due.</li>
							<li>Pay Out.  You can payout your full balance, the principle plus the fee for that period.  To accept this 
								option you must notify us of your request in writing via fax at <?php echo($support_fax); ?>. The request must
								be received at least three full business days before your loan is due.</li>
						</ol>
					</li>
					<li>BY SIGNING OR ELECTRONICALLY SIGNING BELOW, YOU ACKNOWLEDGE READING AND AGREEING TO THE STATEMENTS IN ITEMS 2, 3, 4, 
						AND 5, AND THE AUTHORIZATIONS IN ITEMS 6 AND 7, AND THE PAYMENT OPTIONS IN ITEM 8.</li>
				</ol>
		</div>
		
		<div class="legal-page">
			<table width="600" border="0" cellspacing="0" cellpadding="0">
			<tr> 
				<td class="norm legal-underline" width="4%"><b>(x)</b></td>
				<td class="norm legal-underline sh-align-left" width="50%"><strong><?php echo($esignature);?></strong></td>
				<td class="norm legal-underline" width="45%" class="norm"><?php echo(date("m/d/Y")); ?></td>
			</tr>
			<tr> 
				<td class="norm" width="4%">&nbsp;</td>
				<td class="norm sh-align-left" width="50%"><b>Electronic Signature</b></td>
				<td class="norm" width="45%"><b>Date</b></td>
			</tr>
			<tr> 
				<td class="norm legal-underline" width="4%"><br/><b>(x)</b></td>
				<td class="norm legal-underline sh-align-left" width="50%"><br/><?php echo($full_name); ?></td>
				<td class="norm legal-underline sh-align-left" width="45%" class="norm"><br/><?php echo($ssn); ?></td>
			</tr>
			<tr> 
				<td class="norm" width="4%">&nbsp;</td>
				<td class="norm sh-align-left" width="50%"><b>PRINT NAME</b></td>
				<td class="norm" width="45%"><b>SOCIAL SECURITY NUMBER</b></td>
			</tr>
			</table>
			<br /><br />
			<div class="sh-align-right small">site: <?php echo($site_name); ?></div>
		</div>
		
	<?
	
	$content_string = ob_get_contents();
	ob_end_clean();
	
	$content_string = '<section><verbiage><![CDATA['.$content_string.']]></verbiage></section>';
	
	return($content_string);
	
}



	/**
		@public

		@fn
			Paperless_Legal_Page()

		@brief
				This function generates the paperless legal page in an XML structure.

		@param
				$name_first	This is a string. It is the users first name.
				$name_last	This is a string. It is the users last name.
				$ssn_part_1	This is a string. It is the first 3 digits of the
								the user ssn.
				$ssn_part_2	This is a string. It is the middle 2 digits of the
								the user ssn.
				$ssn_part_3	This is a string. It is the last 4 digits of the
								the user ssn.
				$bank_account	This is a string. It is the bank account name.
				$bank_name	This is a string. It is the bank name.
				$apr	This is a string. This is the APR rate.
				$finance_charge	This is a string. It is the amount financed.
				$amount_financed	This is a string. This is the amount financed.
				$total_payments	This is a string. This is the total price of
										the payment.
				$payoff_date	This is a date. It is the payoff date of the loan.
				$legal_entity  This is a string. It is the legal entity that is
									taking care of the loan

		@return
			result	This is a string. It is a page in an XML format.

		@todo
			- none
	*/
   function Paperless_Legal_Page($name_first,
                                 $name_last,
                                 $ssn_part_1,
                                 $ssn_part_2,
                                 $ssn_part_3,
                                 $bank_account,
                                 $bank_name,
                                 $apr,
                                 $finance_charge,
                                 $amount_financed,
                                 $total_payments,
                                 $payoff_date,
                                 $fund_date,
                                 $legal_entity,
                                 $unique_id)
   {
   	
   	$content_string = '<section>'
								.  '<verbiage>'
								.		'<![CDATA['
								.			'<H2 align="center">'
								.     		'LOAN ACCEPTANCE &amp; eSIGNATURE'
								.			'</H2>'
								.		']]>'
								.  '</verbiage>' 
								.'</section>'
								.'<section>'
								.  '<verbiage>'
								.		'<![CDATA['
								.			'The terms of your loan are described in the '
								.			'<strong>'
								.			'<a href="#" onclick="window.open(\'?page=preview_docs'
								.			'#loan_note_and_disclosure\', \'tss_win\', \'width=800,height=600,resizable=yes,scrollbars=yes,location=yes,toolbar=yes,menubar=yes\'); return false;">'
								.				'LOAN NOTE AND DISCLOSURE '
								.			'</a>'
								.			'</strong>'
								.     'found below. Please review and accept the following related documents.'
								.		']]>'
								.  '</verbiage>'
								.'</section>'
								.'<section>'
								.  '<verbiage>'
								.		'<![CDATA['
								.        'I have read and accept the terms of the '
								.			'<strong>'
								.			'<a href="#" onclick="window.open(\'?page=preview_docs'
								.			'#application\', \'tss_win\', \'width=800,height=600,resizable=yes,scrollbars=yes,location=yes,toolbar=yes,menubar=yes\'); return false;">'
								.				'application' 
								.			'</a>'
								.			'</strong>'
								.			'.'
								.		']]>'
								.  '</verbiage>'
								.  '<question recommend="radio">'
								.     '<option name="legal_approve_docs_1">'
								.        'TRUE'
								.     '</option>'
								.     '<option name="legal_approve_docs_1">'
								.        'FALSE'
								.     '</option>'
								.  '</question>'
								.'</section>'
								.'<section>'
								.  '<verbiage>'
								.		'<![CDATA['
								.        'I have read and accept the terms of the '
								.			'<strong>'
								.			'<a href="#" onclick="window.open(\'?page=preview_docs'
								.			'#privacy_policy\', \'tss_win\', \'width=800,height=600,resizable=yes,scrollbars=yes,location=yes,toolbar=yes,menubar=yes\'); return false;">'
								.				'privacy policy'
								.			'</a>'
								.			'</strong>'
								.			'.'
								.		']]>'
								.  '</verbiage>'
								.  '<question recommend="radio">'
								.     '<option name="legal_approve_docs_2">'
								.        'TRUE'
								.     '</option>'
								.     '<option name="legal_approve_docs_2">'
								.        'FALSE'
								.     '</option>'
								.  '</question>'
								.'</section>'
								.'<section>'
								.  '<verbiage>'
								.		'<![CDATA['
								.        'I have read and accept the terms of the '
								.			'<strong>'
								.			'<a href="#" onclick="window.open(\'?page=preview_docs'
								.			'#auth_agreement\', \'tss_win\', \'width=800,height=600,resizable=yes,scrollbars=yes,location=yes,toolbar=yes,menubar=yes\'); return false;">'
								.				'authorization agreement'
								.			'</a>'
								.			'</strong>'
								.			'.'
								.		']]>'
								.  '</verbiage>'
								.  '<question recommend="radio">'
								.     '<option name="legal_approve_docs_3">'
								.        'TRUE'
								.     '</option>'
								.     '<option name="legal_approve_docs_3">'
								.        'FALSE'
								.     '</option>'
								.  '</question>'
								.'</section>'
								.'<section>'
								.  '<verbiage>'
								.		'<![CDATA['
								.        'I have read and accept the terms of the '
								.			'<strong>'
								.			'<a href="#" onclick="window.open(\'?page=preview_docs'
								.			'#loan_note_and_disclosure\', \'tss_win\', \'width=800,height=600,resizable=yes,scrollbars=yes,location=yes,toolbar=yes,menubar=yes\'); return false;">'
								.				'loan note and disclosure'
								.			'</a>'
								.			'</strong>'
								.			'.'
								.		']]>'
								.  '</verbiage>'
								.  '<question recommend="radio">'
								.     '<option name="legal_approve_docs_4">'
								.        'TRUE'
								.     '</option>'
								.     '<option name="legal_approve_docs_4">'
								.        'FALSE'
								.     '</option>'
								.  '</question>'
								.'</section>'
								.'<section>'
								.  '<verbiage>'
								.		'<![CDATA['
								.	     'To accept the terms of the '
								.  	   '<strong>'
								.			'<a href="#" onclick="window.open(\'?page=preview_docs'
								.			'#loan_note_and_disclosure\', \'tss_win\', \'width=800,height=600,resizable=yes,scrollbars=yes,location=yes,toolbar=yes,menubar=yes\'); return false;">'
								.     	   ' LOAN NOTE AND DISCLOSURE'
								.			'</a>'
								.     	'</strong>'
								.     	', provide your '
								.     	'<strong>'
								.        	'Electronic Signature '
								.     	'</strong>'
								.     	'by typing your full name below. This signature should appear as: '
								.			$name_first
								.			' '
								.			$name_last
								.			'.'
								.		']]>'
								.  '</verbiage>'
								.'</section>'
								.'<section>'
								.  '<verbiage>'
								.		'<![CDATA['
								.     	'<b>'
								.        	'eSIGNATURE'
								.     	'</b>'
								.     	' Enter your full name in the box.'
								.		']]>'
								.  '</verbiage>'
								.  '<question recommend="text">'
								.     '<option name="esignature" />'
								.  '</question>'
								.'</section>'
								.'<section>'
								.  '<verbiage>'
								.		'<![CDATA['
								.			'I AGREE - Send Me My Cash'
								.		']]>'
								.  '</verbiage>'
								.  '<question recommend="radio">'
								.     '<option name="legal_agree">'
								.        'TRUE'
								.		'</option>'
								.     '<option name="legal_agree">'
								.			'FALSE'
								.		'</option>'
								.	'</question>'
								.'</section>'
/////////////// LOAN NOTE AND DISCLOSURE BREAK
								.'<section>'
								.	'<verbiage>'
								.		'<![CDATA['
								.			'<table width="760"; align="center">'
								.				'<tr>'
								.					'<td style="font-family: Verdana, Arial, Helvetica, sans-serif; background-color: #000000; color: #FFFFFF; font-size: 20px; font-weight: bold; text-align: left; width: auto; padding: 10px 0 10px 15px;">'
								.						'LOAN NOTE AND DISCLOSURE'
								.					'</td>'
								.				'</tr>'
								.			'</table>'
								. 			'<div style="font-size: 11px; text-align: left; padding: 0 15px 0 15px;">'
								.			'<br />'
								.			'<table width="720" align="center">'
								.				'<tr>'
								.					'<td>'
								.						'<p style="font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 10pt; margin: auto;">'
								.							'<strong>'
								.								'<u>'
								.									'Parties:'
								.								'</u>'
								.							'</strong>'
								.							' In this Note, we are '
								.							'<strong>'
								.								$legal_entity
								.							'</strong>'
								.							';&nbsp;'
								.							$name_first
								.							' '
								.							$name_last
								.							', [Social Security Number: '
								.							$ssn_part_1 . '-' . $ssn_part_2 . '-' . $ssn_part_3
								.							'] is the person who agrees below.'
								.						'</p>'
								.						'<br />'
								.					'<td>'
								.				'</tr>'
								.				'<tr>'
								.					'<td>'
								.						'<p style="font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 10pt; margin: auto;">'
								.							'<strong>'
								.								'<u>'
								.									'The Account:'
								.								'</u>'
								.							'</strong>'
								.							' You have deposit account #'
								.        				$bank_account
								.							'("Account") with us or, if the following space is completed at '
								.       					$bank_name
								.							'("Bank"). You authorize us to effect a credit entry to deposit the proceeds of the Loan (the Amount Financed indicated below) to your Account at the Bank. The information in the following box is part of this Note.'
								.						'</p>'
								.					'</td>'
								.				'</tr>'
								.			'</table>'
								.			'<br />'
								.			'<table style="font-family: Verdana, Arial, Helvetica, sans-Serif; font-size: 10pt; margin: auto;" bgcolor="#000000"  width="720">'
								.				'<tr>'
								.					'<td style="background-color: #FFFFFF; width: 50%; padding: 4px; margin: 0; text-align: center; border-top: 5px solid black; border-bottom: 5px solid black;">'
								.						'<strong>'
								.							'ANNUAL PERCENTAGE RATE (APR): '
								.                 	$apr
								.							'%'
								.						'</strong>'
								.						'<br />'
								.						'The cost of your credit as a yearly rate (e)'
								.					'</td>'
								.					'<td rowspan="4" style="background-color: #FFFFFF; width: 50%; padding: 6px; 8px 6px 10px; margin: 0; text-align: left; font-size: 10px; border-top: 5px solid black; border-bottom: 5px solid black;">'
								.						'<strong>'
								.							'Payment Schedule'
								.						'</strong>'
								.						'<br />'
								.						'You must make one payment of '
								.						'<strong>'
								.							'$'
								.                 		$amount_financed
								.						'</strong>'
								.						' on '
								.						'<strong>'
								.                 	$payoff_date
								.						'</strong>'
								.						', if you decline* the option of refinancing your loan. If refinancing is accepted you will pay the finance charge of '
								.						'<strong>'
								.							' $'
								.                 	$finance_charge
								.						'</strong>'
								.						' only, on '
								.						'<strong>'
								.              		$payoff_date
								.						'</strong>'
								.						'. You will accrue new finance charges with every refinance of your loan.  On your fifth refinance and every refinance thereafter, your loan will be paid down by $50.00. This means your account will be debited the finance charge plus $50.00. This will continue until your loan is paid in full.'
								.						'<br /><br />'
								.						'* To decline the option of refinancing you must sign the Account summary page and fax it back to our office at least three business days before your loan is due.'
								.						'<br /><br />'
								.						'<strong>'
								.							'Security Interest'
								.						'</strong>'
								.						'. The loan is unsecured. '
								.						'<br /><br />'
								.						'<strong>'
								.							'Prepayment'
								.						'</strong>'
								.						'. If you pay our loan in advance, you will '
								.						'<u>'
								.							'not'
								.						'</u>'
								.						' be Entitled to a refund of all or any part of the finance charge. (e) The Annual Percentage Rate is estimated based on the anticipated date the loan proceeds will be deposited to your Account, which is '
								.						'<strong>'
								.                 	$fund_date
								.						'</strong>'
								.						'. See below for additional information about nonpayment and default.'
								.					'</td>'
								.				'</tr>'
								.				'<tr>'
								.					'<td style="background-color: #FFFFFF; width: 50%; padding: 4px; margin: 0; text-align: center; border-top: 5px solid black; border-bottom: 5px solid black;">'
								.						'<strong>'
								.							'FINANCE CHARGE &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; $'
								.                 		$finance_charge
								.						'</strong>'
								.						'<br />'
								.						'The dollar amount the loan will cost you. '
								.					'</td>'
								.				'</tr>'
								.				'<tr>'
								.					'<td style="background-color: #FFFFFF; width: 50%; padding: 4px; margin: 0; text-align: center; border-top: 5px solid black; border-bottom: 5px solid black;">'
								.						'<strong>'
								.							'AMOUNT FINANCED &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; $'
								.                 		$amount_financed
								.						'</strong>'
								.						'<br />'
								.						'The amount of credit provided to you or on your behalf. '
								.					'</td>'
								.				'</tr>'
								.				'<tr>'
								.					'<td style="background-color: #FFFFFF; width: 50%; padding: 4px; margin: 0; text-align: center; border-top: 5px solid black; border-bottom: 5px solid black;">'
								.						'<strong>'
								.							'TOTAL OF PAYMENTS &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; $'
								.                 		$total_payments
								.						'</strong>'
								.						'<br />'
								.						'The amount you will have paid after you have'
								.						'<br />'
								.						'made the scheduled payment. '
								.					'</td>'
								.				'</tr>'
								.				'<tr>'
								.					'<td style="background-color: #FFFFFF; width: 50%; padding: 4px; margin: 0; text-align: center; border-top: 5px solid black; border-bottom: 5px solid black;">'
								.						'<strong>'
								.							'<u>'
								.								'Itemization Of Amount Financed of $'
								.                    	$amount_financed
								.							'</u>'
								.						'</strong>'
								.					'</td>'
								.					'<td style="background-color: #FFFFFF; width: 50%; padding: 4px; margin: 0; text-align: center; border-top: 5px solid black; border-bottom: 5px solid black;">'
								.						'Given to you directly:'
								.						'<strong>'
								.							'$ '
								.                 	$amount_financed
								.						'</strong>'
								.						' Paid on your account $0'
								.					'</td>'
								.				'</tr>'
								.			'</table>'
								.			'<br />'
								.			'<table style="font-family: Verdana, Arial, Helvetica, sans-Serif; font-size: 10pt; margin: auto;" width="720" valign="top">'
								.				'<tr>'
								.					'<td width="48%">'
								.						'<p>'
								.						'<strong>'
								.						'<u>'
								.							'Promise To Pay:'
								.						'</u>'
								.					'</strong>'
								.					' You promise to pay to us or to our order in 1 payment, on the date indicated in the Payment Schedule the Total of Payments. On or after the day your loan comes due you authorize us to effect this payment by one or more ACH debit entries to your Account at the Bank. You may revoke this authorization at any time up to 3 business days prior to the due date. However, if you timely revoke this authorization, you authorize us to prepare and submit a check drawn on  your Account to repay your loan when it comes due. If your Account is with us, you authorize us to deduct the payment from your Account on the day the loan comes due. If there are insufficient funds on deposit in your Account to effect the ACH debit entry or to pay the check or otherwise cover the loan payment on the due date. You promise to pay us all sums you owe by mailing a check or Money Order to '
								.					'<strong>'
								.						$legal_entity
								.					'</strong>'
								.					'.'
								.				'</p>'
								.				'<p>'
								.					'<strong>'
								.						'<u>'
								.							'Return Item Fee:'
								.						'</u>'
								.					'</strong>'
								.					' If sufficient funds are not available in the Account on the due date to cover the ACH debit entry or check, you agree to pay us a Return Item Fee of $30.'
								.				'</p>'
								.				'<p>'
								.					'<strong>'
								.						'<u>'
								.							'Prepayment:'
								.						'</u>'
								.					'</strong>'
								.					' The finance Charge consists solely of a loan fee that is earned in full at the time the loan is funded.  Although you may pay all or part of your loan in advance without penalty, you will not receive a refund or credit of any part or all of the Finance Charge.'
								.					'</p>'
								.					'<p>'
								.						'<strong>'
								.							'<u>'
								.								'Arbitration of All Disputes:'
								.							'</u>'
								.						'</strong>'
								.						' You and we agree that any and all claims, disputes or controversies between you and us, any claim by either of us against the other (or the employees, officers, directors, agents, servicers or assigns of the other) and any claim  arising from or relating to your application for this loan, regarding this loan  or any other loan you previously or may later obtain from us, this Note, this  agreement to arbitrate all disputes, your agreement not to bring, join or  participate in class actions, regarding collection of the loan, alleging  fraud or misrepresentation, whether under common law or pursuant to federal,  state or local statute, regulation or ordinance, including disputes regarding  the matters subject to arbitration, or otherwise, shall be resolved by binding  individual (and not joint) arbitration by and under the Code of Procedure of  the National Arbitration Forum ("NAF") in effect at the time the  claim is filed. No class arbitration. All disputes including any Representative  Claims against us and/or related third parties shall be resolved by binding  arbitration only on an individual basis with you.  THEREFORE, THE ARBITRATOR  SHALL NOT CONDUCT CLASS ARBITRATION; THAT IS, THE ARBITRATOR SHALL NOT ALLOW  YOU TO SERVE AS A REPRESENTATIVE, AS A PRIVATE ATTORNEY GENERAL, OR IN ANY  OTHER REPRESENTATIVE CAPACITY FOR OTHERS IN THE ARBITRATION.'
								.						'</p>'
								.					'</td>'
								.					'<td width="4%">'
								.					'&nbsp;'
								.				'</td>'
								.				'<td width="48%">'
								.					'<p>'
								.						'This agreement to arbitrate all disputes shall apply no matter by whom or against whom the claim is filed. Rules and forms of the NAF may be obtained and all claims shall be filed at any NAF office, on the World Wide Web at www.arb-forum.com, by telephone at 800-474-2371, or at "National Arbitration Forum, P.O. Box 50191, Minneapolis, Minnesota 55405." Your arbitration fees will be waived by the NAF in the event you cannot afford to pay them. The cost of a participatory hearing, if one is held at your or our request, will be paid for solely by us and will take place at a location near your residence. This arbitration agreement is made pursuant to a transaction involving interstate commerce. It shall be governed by the Federal Arbitration Act, 9 U.S.C. Sections 1-16. Judgment upon the award may be entered by any party in any court having jurisdiction.'
								.					'</p>'
								.					'<p>'
								.						'NOTICE: YOU AND WE WOULD HAVE HAD A RIGHT OR OPPORTUNITY TO LITIGATE DISPUTES THROUGH A COURT AND HAVE A JUDGE OR JURY DECIDE THE DISPUTES BUT HAVE AGREED INSTEAD TO RESOLVE DISPUTES THROUGH BINDING ARBITRATION.'
								.					'</p>'
								.					'<p>'
								.						'<strong>'
								.							'<u>'
								.								'Agreement Not To Bring, Join Or Participate in Class Actions:'
								.							'</u>'
								.						'</strong>'
								.						' To the extent permitted by law, you agree that you will not bring, join or participate in any class action as to any claim, dispute or controversy you may have against us, our employees, officers, directors, servicers and assigns. you agree to the entry of injunctive relief to stop such a lawsuit or to remove you as a participant in the suit. You agree to pay the attorney\'s fees and court costs we incur in seeking such relief. This agreement does not constitute a waiver of any of your rights and remedies to pursue a claim individually and not as a class action in binding arbitration as provided above.'
								.						'</p>'
								.						'<p>'
								.							'<strong>'
								.								'<u>'
								.									'Survival:'
								.								'</u>'
								.							'</strong>'
								.							' The provisions of this Loan Note And Disclosure dealing with the Agreement To Arbitrate All Disputes and the Agreement Not To Bring, Join Or Participate In Class Actions shall survive repayment in full and /or default of this Note.'
								.						'</p>'
								.						'<p>'
								.							'<strong>'
								.								'<u>'
								.									'No Bankruptcy:'
								.								'</u>'
								.							'</strong>'
								.							' By electronically signing above you represent that you have not recently filed for bankruptcy and you do not plan to do so.'
								.						'</p>'
								.						'<p>'
								.							'<strong>'
								.								'<u>'
								.									'BY ELECTRONICALLY SIGNING ABOVE, YOU AGREE TO ALL THE TERMS OF THIS NOTE, INCLUDING THE AGREEMENT TO ARBITRATE ALL DISPUTES AND THE AGREEMENT NOT TO BRING, JOIN OR PARTICIPATE IN CLASS ACTIONS.'
								.								'</u>'
								.							'</strong>'
								.						'</p>'
								.						'<br /><br />'
								.					'</td>'
								.				'</tr>'
								.			'</table>'
								.		']]>'
		                  .  '</verbiage>'
      		            .'</section>';

		return $content_string;
	}

	function Paperless_Legal_Page_New($proc_data_result, $name_first, $name_last, $include_css = TRUE)
	{
		
   	$css = "
			<style>
				#wf-legal-section {	margin: 0 15px 0 15px;  padding-top: 10px; }
				.wf-legal-block {	background-color: #FFFFFF; margin 0; padding: 0; }	
				.wf-legal-title {	background-color: #000000; color:#FFFFFF; font-size: 20px; font-weight: bold;
					text-align: left; width: auto; padding: 10px 0 10px 15px; }
				.wf-legal-table { padding: 0px; margin: auto; width: auto; }
				.wf-legal-table-cell, .wf-legal-table-cell h2, .wf-legal-table-cell h3  {
					padding: 3px; margin: 0; }
				.wf-legal-table-cell-terms { background-color: #FFFFFF; width: 50%; padding: 4px;
					margin: 0; text-align: center; border-top: 5px solid black; border-bottom: 5px solid black; }
				.wf-legal-table-cell-schedule { background-color: #FFFFFF; width: 50%; padding: 6px 8px  6px 10px;
					margin: 0; text-align: left; font-size: 10px; border-top: 5px solid black; border-bottom: 5px solid black; }
				.wf-legal-copy { font-size: 11px; text-align: left; padding: 0 15px 0 15px; }
				.wf-legal-copy li { font-size: 12px; list-style: none; margin: 3px; }
				.wf-legal-link { font-size: 10px; color: blue; }
			</style>
		";
   	
		$doc = $proc_data_result->eds_page['content'];
		$doc = strstr($doc, '<a name="note_and_disclosures">');
		
		$content_string = '<section>'
								.  '<verbiage>'
								.		'<![CDATA['
								.			'<H2 align="center">'
								.     		'LOAN ACCEPTANCE &amp; eSIGNATURE'
								.			'</H2>'
								.		']]>'
								.  '</verbiage>' 
								.'</section>'
								.'<section>'
								.  '<verbiage>'
								.		'<![CDATA['
								.			'The terms of your loan are described in the '
								.			'<strong>'
								.			'<a href="#" onclick="window.open(\'?page=preview_docs'
								.			'#loan_note_and_disclosure\', \'tss_win\', \'width=800,height=600,resizable=yes,scrollbars=yes,location=yes,toolbar=yes,menubar=yes\'); return false;">'
								.				'LOAN NOTE AND DISCLOSURE '
								.			'</a>'
								.			'</strong>'
								.     'found below. Please review and accept the following related documents.'
								.		']]>'
								.  '</verbiage>'
								.'</section>'
								.'<section>'
								.  '<verbiage>'
								.		'<![CDATA['
								.        'I have read and accept the terms of the '
								.			'<strong>'
								.			'<a href="#" onclick="window.open(\'?page=preview_docs'
								.			'#application\', \'tss_win\', \'width=800,height=600,resizable=yes,scrollbars=yes,location=yes,toolbar=yes,menubar=yes\'); return false;">'
								.				'application' 
								.			'</a>'
								.			'</strong>'
								.			'.'
								.		']]>'
								.  '</verbiage>'
								.  '<question recommend="radio">'
								.     '<option name="legal_approve_docs_1">'
								.        'TRUE'
								.     '</option>'
								.     '<option name="legal_approve_docs_1">'
								.        'FALSE'
								.     '</option>'
								.  '</question>'
								.'</section>'
								.'<section>'
								.  '<verbiage>'
								.		'<![CDATA['
								.        'I have read and accept the terms of the '
								.			'<strong>'
								.			'<a href="#" onclick="window.open(\'?page=preview_docs'
								.			'#privacy_policy\', \'tss_win\', \'width=800,height=600,resizable=yes,scrollbars=yes,location=yes,toolbar=yes,menubar=yes\'); return false;">'
								.				'privacy policy'
								.			'</a>'
								.			'</strong>'
								.			'.'
								.		']]>'
								.  '</verbiage>'
								.  '<question recommend="radio">'
								.     '<option name="legal_approve_docs_2">'
								.        'TRUE'
								.     '</option>'
								.     '<option name="legal_approve_docs_2">'
								.        'FALSE'
								.     '</option>'
								.  '</question>'
								.'</section>'
								.'<section>'
								.  '<verbiage>'
								.		'<![CDATA['
								.        'I have read and accept the terms of the '
								.			'<strong>'
								.			'<a href="#" onclick="window.open(\'?page=preview_docs'
								.			'#auth_agreement\', \'tss_win\', \'width=800,height=600,resizable=yes,scrollbars=yes,location=yes,toolbar=yes,menubar=yes\'); return false;">'
								.				'authorization agreement'
								.			'</a>'
								.			'</strong>'
								.			'.'
								.		']]>'
								.  '</verbiage>'
								.  '<question recommend="radio">'
								.     '<option name="legal_approve_docs_3">'
								.        'TRUE'
								.     '</option>'
								.     '<option name="legal_approve_docs_3">'
								.        'FALSE'
								.     '</option>'
								.  '</question>'
								.'</section>'
								.'<section>'
								.  '<verbiage>'
								.		'<![CDATA['
								.        'I have read and accept the terms of the '
								.			'<strong>'
								.			'<a href="#" onclick="window.open(\'?page=preview_docs'
								.			'#loan_note_and_disclosure\', \'tss_win\', \'width=800,height=600,resizable=yes,scrollbars=yes,location=yes,toolbar=yes,menubar=yes\'); return false;">'
								.				'loan note and disclosure'
								.			'</a>'
								.			'</strong>'
								.			'.'
								.		']]>'
								.  '</verbiage>'
								.  '<question recommend="radio">'
								.     '<option name="legal_approve_docs_4">'
								.        'TRUE'
								.     '</option>'
								.     '<option name="legal_approve_docs_4">'
								.        'FALSE'
								.     '</option>'
								.  '</question>'
								.'</section>'
								.'<section>'
								.  '<verbiage>'
								.		'<![CDATA['
								.	     'To accept the terms of the '
								.  	   '<strong>'
								.			'<a href="#" onclick="window.open(\'?page=preview_docs'
								.			'#loan_note_and_disclosure\', \'tss_win\', \'width=800,height=600,resizable=yes,scrollbars=yes,location=yes,toolbar=yes,menubar=yes\'); return false;">'
								.     	   ' LOAN NOTE AND DISCLOSURE'
								.			'</a>'
								.     	'</strong>'
								.     	', provide your '
								.     	'<strong>'
								.        	'Electronic Signature '
								.     	'</strong>'
								.     	'by typing your full name below. This signature should appear as: '
								.			$name_first
								.			' '
								.			$name_last
								.			'.'
								.		']]>'
								.  '</verbiage>'
								.'</section>'
								.'<section>'
								.  '<verbiage>'
								.		'<![CDATA['
								.     	'<b>'
								.        	'eSIGNATURE'
								.     	'</b>'
								.     	' Enter your full name in the box.'
								.		']]>'
								.  '</verbiage>'
								.  '<question recommend="text">'
								.     '<option name="esignature" />'
								.  '</question>'
								.'</section>'
								.'<section>'
								.  '<verbiage>'
								.		'<![CDATA['
								.			'I AGREE - Send Me My Cash'
								.		']]>'
								.  '</verbiage>'
								.  '<question recommend="radio">'
								.     '<option name="legal_agree">'
								.        'TRUE'
								.		'</option>'
								.     '<option name="legal_agree">'
								.			'FALSE'
								.		'</option>'
								.	'</question>'
								.'</section>';
		
		if ($include_css) $doc = $css.$doc;
		$doc = preg_replace('/\s+/', ' ', $doc);
		
		$content_string .= '<section><verbiage><![CDATA['.$doc.']]></verbiage></section>';
   	
		return($content_string);
		
	}



	/**
		@public

		@fn
			Enterprise_Thank_You_Page()

		@brief
			This function generates the thank you page in an XML structure.

		@param
			$name_first	This is a string. It is the users first name.

		@return
			result	This is a string. It is a page in an XML format.

		@todo
			- none
	*/
	function Enterprise_Thank_You_Page($name_first)
	{
		$content_string = '<section>'
								.  '<verbiage>'
								.		'<![CDATA['
								.			'Getting your CASH DEPOSITED in your account is as easy as 1-2-3!'
								.  		'<br />'
								.			'Welcome '
								.			$name_first
								.			'!  Your cash is waiting!'
								.			'<br />'
								.			'Your information &amp; application have been successfully submitted.'
								.		']]>'
								.  '</verbiage>'
                        .'</section>'
								.'<section>'
                        .  '<verbiage>'
								.		'<![CDATA['
								.			'Thank you for your application!'
								.  		'<br />'
								.  		'<br />'
								.			'You will receive an e-mail from us momentarily.'
								.  		'<br />'
								.  		'Due to increasing e-mail restrictions, this email-may accidentally be marked as'
								.  		'<br />'
								.  		'spam and sent to your Bulk Mail(Yahoo!), Junk Mail(MSN Hotmail) or Spam(AOL)'
								.  		'<br />'
								.  		'folder.'
								.  		'<br />'
								.			'PLEASE CHECK YOUR INBOX AND ANY SPAM FOLDERS FOR YOUR CONFORMATION EMAIL!'
								.			'<br />'
								.			'You '
								.			'<u>'
								.				'must'
								.			'</u>'
								.			' follow the directions and confirm your details provided in your e-mail in '
								.			'<br />'
								.			'order for us to process your loan and get your cash to you!'
								.		']]>'
                        .  '</verbiage>'
                        .'</section>';

		return $content_string;
	}




	/**
		@public

		@fn
			Verify_Address_Page()

		@brief
				This function generates a verify page in an XML structure.

		@param
			- none

		@return
			result	This is a string. It is a page in an XML format.

		@todo
			- none
	*/
	function Verify_Address_Page()
	{
		$content_string = '<section>'
                        .  '<verbiage>'
								.		'<![CDATA['
								.     'We check US Postal Service records and didn\'t find your address. Please confirm your address.'
								.		']]>'
								.  '</verbiage>'
								.'</section>'
								.'<section>'
                        .  '<verbiage>'
								.		'<![CDATA['
								.     'Address'
								.		']]>'
								.  '</verbiage>'
								.  '<question recommend="text">'
								.     '<option name="home_street" />'
								.  '</question>'
								.'</section>'
								.'<section>'
                        .  '<verbiage>'
								.		'<![CDATA['
								.     'Apartment'
								.		']]>'
								.  '</verbiage>'
								.  '<question recommend="text">'
								.     '<option name="home_unit" />'
								.  '</question>'
								.'</section>'
								.'<section>'
                        .  '<verbiage>'
								.		'<![CDATA['
								.     'City'
								.		']]>'
								.  '</verbiage>'
								.  '<question recommend="text">'
								.     '<option name="home_city" />'
								.  '</question>'
								.'</section>'
								.'<section>'
                        .  '<verbiage>'
								.		'<![CDATA['
								.     'State'
								.		']]>'
								.  '</verbiage>'
								.  '<question recommend="combo">'
								.     '<option name="home_state">'
								.			'AK'
								.     '</option>'
								.     '<option name="home_state">'
								.			'AL'
								.     '</option>'
								.     '<option name="home_state">'
								.			'AR'
								.     '</option>'
								.     '<option name="home_state">'
								.			'AZ'
								.     '</option>'
								.     '<option name="home_state">'
								.			'CA'
								.     '</option>'
								.     '<option name="home_state">'
								.			'CO'
								.     '</option>'
								.     '<option name="home_state">'
								.			'CT'
								.     '</option>'
								.     '<option name="home_state">'
								.			'DC'
								.     '</option>'
								.     '<option name="home_state">'
								.			'DE'
								.     '</option>'
								.     '<option name="home_state">'
								.			'FL'
								.     '</option>'
								.     '<option name="home_state">'
								.			'GA'
								.     '</option>'
								.     '<option name="home_state">'
								.			'HI'
								.     '</option>'
								.     '<option name="home_state">'
								.			'IA'
								.     '</option>'
								.     '<option name="home_state">'
								.			'ID'
								.     '</option>'
								.     '<option name="home_state">'
								.			'IL'
								.     '</option>'
								.     '<option name="home_state">'
								.			'IN'
								.     '</option>'
								.     '<option name="home_state">'
								.			'KS'
								.     '</option>'
								.     '<option name="home_state">'
								.			'KY'
								.     '</option>'
								.     '<option name="home_state">'
								.			'LA'
								.     '</option>'
								.     '<option name="home_state">'
								.			'MA'
								.     '</option>'
								.     '<option name="home_state">'
								.			'MD'
								.     '</option>'
								.     '<option name="home_state">'
								.			'ME'
								.     '</option>'
								.     '<option name="home_state">'
								.			'MI'
								.     '</option>'
								.     '<option name="home_state">'
								.			'MN'
								.     '</option>'
								.     '<option name="home_state">'
								.			'MO'
								.     '</option>'
								.     '<option name="home_state">'
								.			'MS'
								.     '</option>'
								.     '<option name="home_state">'
								.			'MT'
								.     '</option>'
								.     '<option name="home_state">'
								.			'NC'
								.     '</option>'
								.     '<option name="home_state">'
								.			'ND'
								.     '</option>'
								.     '<option name="home_state">'
								.			'NE'
								.     '</option>'
								.     '<option name="home_state">'
								.			'NH'
								.     '</option>'
								.     '<option name="home_state">'
								.			'NJ'
								.     '</option>'
								.     '<option name="home_state">'
								.			'NM'
								.     '</option>'
								.     '<option name="home_state">'
								.			'NV'
								.     '</option>'
								.     '<option name="home_state">'
								.			'NY'
								.     '</option>'
								.     '<option name="home_state">'
								.			'OH'
								.     '</option>'
								.     '<option name="home_state">'
								.			'OK'
								.     '</option>'
								.     '<option name="home_state">'
								.			'OR'
								.     '</option>'
								.     '<option name="home_state">'
								.			'PA'
								.     '</option>'
								.     '<option name="home_state">'
								.			'PR'
								.     '</option>'
								.     '<option name="home_state">'
								.			'RI'
								.     '</option>'
								.     '<option name="home_state">'
								.			'SC'
								.     '</option>'
								.     '<option name="home_state">'
								.			'SD'
								.     '</option>'
								.     '<option name="home_state">'
								.			'TN'
								.     '</option>'
								.     '<option name="home_state">'
								.			'TX'
								.     '</option>'
								.     '<option name="home_state">'
								.			'UT'
								.     '</option>'
								.     '<option name="home_state">'
								.			'VA'
								.     '</option>'
								.     '<option name="home_state">'
								.			'VI'
								.     '</option>'
								.     '<option name="home_state">'
								.			'VT'
								.     '</option>'
								.     '<option name="home_state">'
								.			'WA'
								.     '</option>'
								.     '<option name="home_state">'
								.			'WI'
								.     '</option>'
								.     '<option name="home_state">'
								.			'WV'
								.     '</option>'
								.     '<option name="home_state">'
								.			'WY'
								.     '</option>'
								.  '</question>'
								.'</section>'
								.'<section>'
                        .  '<verbiage>'
								.		'<![CDATA['
								.     'Zip Code'
								.		']]>'
								.  '</verbiage>'
								.  '<question recommend="text">'
								.     '<option name="home_zip" />'
								.  '</question>'
								.'</section>';

		return $content_string;
	}





	/**
		@public

		@fn
			App_Decline_01_Page()

		@brief
			This function generates an XML structured string containing
			a web page that askes the user to confirm they want to 
			decline the loan.

		@param
			- none

		@return
			result	This is a string. It is a page in an XML format.

		@todo
			- none
	*/
	function App_Decline_01_Page()
	{
		$content_string = '<section>'
                        .  '<verbiage>'
								.		'<![CDATA['
								.			'<p>'
								.				'We understand your needs may be different from those you have automatically qualified for via our online system. For example, our customers often make the following requests:'
								.			'</p>'
								.			'<ol>'
								.				'<li>'
								.					'I wish to qualify for more money'
								.				'</li>'
								.				'<li>'
								.					'I don\'t need that much'
								.				'</li>'
								.				'<li>'
								.					'I will need to extend my loan or change the date which the online documents say I need to repay the loan'
								.				'</li>'
								.				'<li>'
								.					'Other requests'
								.				'</li>'
								.			'</ol>'
								.			'<p>'
								.				'Your Loan Agent or Customer Service Representative can often address these requests once your application has been completed and approved.'
								.			'</p>'
								.			'<p>'
								.				'To complete the process, please select "TRUE" to the "I AGREE - Send Me My Cash" and a loan representative will be in touch shortly to complete the loan process.'
								.			'</p>' 
								.			'<p>'
								.				'If you do not wish to proceed and you do not wish to receive the cash you have qualified for, please select "FALSE" to the "I AGREE - Send Me My Cash Questions". Selecting "FALSE" will terminate your loan application and no further action will be taken.'
								.			'</p>'
								.		']]>'
								.  '</verbiage>'
								.'</section>'
								.'<section>'
                        .  '<verbiage>'
								.		'<![CDATA['
								.     	'I AGREE - Send Me My Cash'
								.		']]>'
								.  '</verbiage>'
								.  '<question recommend="radio">'
								.     '<option name="legal_agree">'
								.			'TRUE'
								.     '</option>'
								.     '<option name="legal_agree">'
								.			'FALSE'
								.     '</option>'
								.  '</question>'
								.'</section>';


		return $content_string;
	}



	/*
		This page should not be displayed but it currently is.
	*/
	function App_Decline_Page()
	{
		$content_string = '<section>'
                        .  '<verbiage>'
								.		'<![CDATA['
								.			'<p>'
								.				'We\'re sorry but you do not qualify for a payday loan at this time.'
								.			'</p>'
								.		']]>'
								.  '</verbiage>'
								.'</section>';

		return $content_string;
	}





	/**
		@public

		@fn
			App_Decline_02_Page()

		@brief
			This function generates an XML structured string containing
			a web page that askes the user to answer a couple of questions
			on why they want to decline the loan.

		@param
			- none

		@return
			result	This is a string. It is a page in an XML format.

		@todo
			- none
	*/
	function App_Decline_02_Page()
	{
		$content_string = '<section>'
                        .  '<verbiage>'
								.		'<![CDATA['
								.			'<p>'
								.				'In order to better understand your needs, please help us out and let us know why you did not want to process your loan:'
								.			'</p>'
								.		']]>'
								.  '</verbiage>'
								.  '<question recommend="combo">'
								.     '<option name="declined_reason">'
								.			'I needed more money'
								.     '</option>'
								.     '<option name="declined_reason">'
								.			'I needed less money'
								.     '</option>'
								.     '<option name="declined_reason">'
								.			'Your fees are too high'
								.     '</option>'
								.     '<option name="declined_reason">'
								.			'Just looking, I don\'t need the money'
								.     '</option>'
								.     '<option name="declined_reason">'
								.			'My questions weren\'t answered'
								.     '</option>'
								.     '<option name="declined_reason">'
								.			'I was never interested'
								.     '</option>'
								.     '<option name="declined_reason">'
								.			'I don\'t have time to get everything together'
								.     '</option>'
								.     '<option name="declined_reason">'
								.			'I probably want a loan in the future, but not right now'
								.     '</option>'
								.     '<option name="declined_reason">'
								.			'The due date is too soon'
								.     '</option>'
								.     '<option name="declined_reason">'
								.			'Other'
								.     '</option>'
								.  '</question>'
								.'</section>';


		return $content_string;
	}



	/**
		@public

		@fn
			EZM_Extra_Page()

		@brief
				This function generates the thank you page in an XML structure.

		@param
			- none

		@return
			result	This is a string. It is a page in an XML format.

		@todo
			- none
	*/
	function EZM_Extra_Page()
	{
		$content_string = '<section>'
                        .  '<verbiage>'
								.		'<![CDATA['
								.		'I do not have 4 or more NSFs on my most recent bank statement.'
								.		']]>'
                        .  '</verbiage>'
								.  '<question recommend="radio">'
								.     '<option name="ezm_nsf_count">'
								.        'YES'
								.     '</option>'
								.     '<option name="ezm_nsf_count">'
								.        'NO'
								.     '</option>'
								.  '</question>'
								.'</section>'
								.'<section>'
                        .  '<verbiage>'
								.		'<![CDATA['
								.     'Please type your signature'
								.		']]>'
								.  '</verbiage>'
								.  '<question recommend="text">'
								.     '<option name="ezm_signature" />'
								.  '</question>'
								.'</section>';

		return $content_string;
	}



	/**
		@public

		@fn
			EZM_Legal_Page()

		@brief
			This function generates the ezm legal page in an XML structure.

		@param
			$rcpt_comapny	This is a string. It is the company name.
			$rcpt_name		This is a string. It is the customer name.

		@return
			result	This is a string. It is a page in an XML format.

		@todo
			- none
	*/
	function EZM_Legal_Page($rcpt_company, $rcpt_name)
	{
		$content_string = '<section>'
                        .  '<verbiage>'
								.		'<![CDATA['
								.		'Your loan is being processed by '
								.		'<strong>'
								.			$rcpt_company
								.		'</strong>'
								.		']]>'
                        .  '</verbiage>'
                        .'</section>'
								.'<section>'
                        .  '<verbiage>'
								.		'<![CDATA['
								.		'I do not have 4 or more NSFs on my most recent bank statement.'
								.		']]>'
                        .  '</verbiage>'
								.  '<question recommend="radio">'
								.     '<option name="ezm_nsf_count">'
								.        'TRUE'
								.     '</option>'
								.     '<option name="ezm_nsf_count">'
								.        'FALSE'
								.     '</option>'
								.  '</question>'
								.'</section>'
								.'<section>'
                        .  '<verbiage>'
								.		'<![CDATA['
								.		'I have read and agree to the Terms and Conditions below.'
								.		']]>'
                        .  '</verbiage>'
								.  '<question recommend="radio">'
								.     '<option name="ezm_terms">'
								.        'TRUE'
								.     '</option>'
								.     '<option name="ezm_terms">'
								.        'FALSE'
								.     '</option>'
								.  '</question>'
								.'</section>'
								.'<section>'
                        .  '<verbiage>'
								.		'<![CDATA['
								.     'Please type your signature'
								.		']]>'
								.  '</verbiage>'
								.  '<question recommend="text">'
								.     '<option name="ezm_signature" />'
								.  '</question>'
								.'</section>'
								.'<section>'
                        .  '<verbiage>'
								.		'<![CDATA['
								.     'This service does not constitute an offer or solicitation for short term loans in all states. This service may or may not be available in your particular state. The states this site services may change from time to time without notice. All aspects and transactions on this site, will be deemed to have taken place in our International Lending Facilities, regardless of where you may be viewing or accessing this site.'
								.		']]>'
								.  '</verbiage>'
								.'</section>'
								.'<section>'
                        .  '<verbiage>'
								.		'<![CDATA['
								.		'You will receive an email shortly confirming receipt of your application and providing additional instructions.'
								.		']]>'
								.  '</verbiage>'
								.'</section>'
								.'<section>'
                        .  '<verbiage>'
								.		'<![CDATA['
								.		'<p>'
								.			'<b>'
								.				'Terms and Conditions'
								.			'</b>'
								.			'<br /><br/>'
								.			'Before clicking submit on this on-line application, please read the following terms and conditions carefully. By submitting this application and faxing the required documents to us, and by accepting any loans from '
								.		$rcpt_name
								.		', you agree to the terms entirely:'
								.		'</p>'
								.		'<p>1) You authorize '
								.			$rcpt_name
								.			' to deposit into your personal checking account indicated a loan amount of $200, and to withdraw on your due-date an amount of $250. You understand that these and all transactions will be done electronically using our ACH system.'
								.		'</p>'
								.		'<p>'
								.			'2) In the event of a default on your account, you will be responsible for all costs that may be associated with the attempt by '
								.			$rcpt_name
								.			' to collect any outstanding balance of this loan and fees, including but not limited to any fees or charges associated with bank NSF or returned checks, court costs, attorney fees, costs of being served, pre and post judgment interest, etc.'
								.		'</p>'
								.		'<p>'
								.			'3) Upon the occurrence of one or more of the following events of default; (1) failure to make any monthly payments when due; (2) failure to perform any obligation under any security agreement securing this note; (3) borrower defaults under any other credit extension with Lender; (4) borrower should die, or become insolvent, or apply for bankruptcy or other relief from creditors; (5) lender reasonably believes itself to be insecure in the repayment of this note. Lender may, at it\'s option, declare the entire unpaid balance of this note to be due immediately and payable without notice or demand.'
								.		'</p>'
								.		'<p>'
								.			'4) By submitting this form and faxing the additional information you are in a binding agreement with '
								.			$rcpt_name
								.			'.'
								.			'<//p>'
								.		']]>'
								.  '</verbiage>'
								.'</section>';
								
		return $content_string;
	}



	/**
		@public

		@fn
			EZM_Thank_You_Page()

		@brief
			This function generates the ezm legal page in an XML structure.

		@param
			$rcpt_company			This is a string. It is the company name.
			$rcpt_cs_email			This is a string. It is the customer support email address.
			$rcpt_cs_phone			This is a string. It is the customer support phone number.
			$url_docs				This is a string. It is the URL to the documents.
			$rcpt_fax				This is a string. It is the company fax number.

		@return
			result	This is a string. It is a page in an XML format.

		@todo
			- none
	*/
	function EZM_Thank_You_Page($rcpt_company,
										$rcpt_cs_email,
										$rcpt_cs_phone,
										$url_docs,
										$rcpt_fax)
	{
		$content_string = '<section>'
                        .  '<verbiage>'
								.		'<![CDATA['
								.		'<table border="0">'
								.			'<tr>'
								.				'<td>'
								.					'Thank You'
								.					'<br/>'
								.					'For Submitting Your Loan Application to'
								.					'<br />'
								.					'<strong>'
								.						$rcpt_company
								.					'</strong>'
								.					'<br/>'
								.					$rcpt_company
								.					' has requested supporting documents'
								.					'<br />'
								.					'For Customer Service eMail: '
								.					'<a href=mailto:"'
								.						$rcpt_cs_email
								.						'">'
								.						$rcpt_cs_email
								.					'</a>'
								.					'<br />'
								.					'Or call us at: '
								.					'<strong>'
								.						$rcpt_cs_phone
								.					'</strong>'
								.					'<br />'
								.				'</td>'
								.			'</tr>'
								.		'</table>'
								.		'<table border="0">'
								.			'<tr>'
								.				'<td>'
								.					'<br /><br />'
								.				'</td>'
								.			'</tr>'
								.			'<tr>'
								.				'<td>'
								.					'<a href="'
								.						$url_docs
								.						'" target="_blank">'
								.						'Cash Advance Forms 2 and 3'
								.					'</a>'
								.					' - '
								.					'<strong>'
								.						'PRINT THESE FORMS, COMPLETE THEM, AND FAX TO US! OTHERWISE WE CANNOT PROCESS YOUR LOAN!'
								.					'</strong>'
								.				'</td>'
								.			'</tr>'
								.			'<tr>'
								.				'<td>'
								.					'<p>'
								.						'<b>'
								.							'Payback for a $200 Cash Advance is $250'
								.						'</b>'
								.					'</p>'
								.					'<table>'
								.						'<tr>'
								.							'<td>'
								.								'<p>'
								.									'IMPORTANT INFORMATION!!!!!'
								.									'<br />'
								.									'Please have the following documents ready to fax after you submit your online application:'
								.									'</p>'
								.									'<ul>'
								.										'<li>'
								.											'Last Pay Stub or Direct Deposit Slip '
								.										'</li>'
								.										'<br />'
								.										'<br />'
								.										'<li>'
								.											'Last Bank Statement (entire statement must be dated within 30 days of the day you apply) '
								.										'</li>'
								.										'<br />'
								.										'<br />'
								.										'<li>'
								.											'One form of Identification with signature (Driver\'s License, social security card, etc.) '
								.										'</li>'
								.										'<br />'
								.										'<br />'
								.										'<li>'
								.											'A copy of your signed personal check made payable to '
								.											$rcpt_company 
								.											' for $250.00. DO NOT VOID THIS CHECK!'
								.										'</li>'
								.									'</ul>'
								.									'<p>'
								.										'When you have completed the application, fax all documents listed above '
								.										'<b>'
								.											'TOLL-FREE to '
								.											$rcpt_fax
								.										'</b>'
								.										'. You will be notified by e-mail when you are approved and when your due date will be.'
								.									'</p>'
								.								'</td>'
								.							'</tr>'
								.						'</table>'
								.						'<p>'
								.							'You have completed the application!'
								.						'</p>'
								.						'<p>'
								.							'You may be denied for an incomplete or inaccurate application, so be sure all of the information is correct. We will notify you by e-mail as to if you were approved, and when your due-date will be.'
								.						'</p>'
								.					'</td>'
								.				'</tr>'
								.			'</table>'
								.		']]>'
                        .		'</verbiage>'
                        .	'</section>';


		return $content_string;
	}





	/**
		@public

		@fn
			EZM_Sorry_Page()

		@brief
			This function generates the ezm sorry page in an XML structure.

		@param
			- none

		@return
			result	This is a string. It is a page in an XML format.

		@todo
			- none
	*/
	function EZM_Sorry_Page()
	{
		$content_string = '<section>'
                        .  '<verbiage>'
								.		'<![CDATA['
								.		'We\'re sorry, but you do not qualify for a payday loan at this time.'
								.		']]>'
                        .  '</verbiage>'
                        .'</section>';

		return $content_string;
	}



	/**
		@public

		@fn
			EZM_Already_Run_Page()

		@brief
			This function generates the ezm already run page in an XML structure.

		@param
			- none

		@return
			result	This is a string. It is a page in an XML format.

		@todo
			- none
	*/
	function EZM_Already_Run_Page()
	{
		$content_string = '<section>'
                        .  '<verbiage>'
								.		'<![CDATA['
								.		'You previously ran an application on '
								.		date('m/d/Y')
								.		' on cashadvanceusa, which is an affiliated website. Please use that application to obtain you loan.'
								.		']]>'
                        .  '</verbiage>'
                        .'</section>';

		return $content_string;
	}



	/**
		@public

		@fn
			Verbiage_Only_Section()

		@brief
			This function generates an XML structure with the parameter
			string in the middle.

		@param
			@html_string	This is a string. It is the HTML that goes in the verbiage section.

		@return
			result	This is a string. It is a page in an XML format.

		@todo
			- none
	*/
	function Verbiage_Only_Section($html_string)
	{
		$content_string = '<section>'
                        .  '<verbiage>'
								.		'<![CDATA['
								.		$html_string
								.		']]>'
                        .  '</verbiage>'
                        .'</section>';

		return $content_string;
	}




	/**
		@public

		@fn
			EZM_Disclosure_Page()

		@brief
			This function generates the ezm disclosure page in an XML structure.

		@param
			- none

		@return
			result	This is a string. It is a page in an XML format.

		@todo
			- none
	*/
	function EZM_Disclosure_Page()
	{
		$content_string = '<section>'
                        .  '<verbiage>'
								.		'<![CDATA['
								.		'<p>'
								.			'FOR VALUE RECEIVED, the undersigned (whether one or more) jointly, severally solidarity, promise to pay to the order the Lender stated above the Total of payments shown above until the full amount of this note shall be paid.'
								.		'</p>'
								.		'<p>'
								.			'In the event that any installment under this note is not paid is full within (10) days following its scheduled due date, a Delinquency charge will be assessed equal to five (5%) percent of the unpaid installment amount or $15.00, whichever is less.'
								.		'</p>'
								.		'<p>'
								.			'The parties hereto further bind themselves to pay reasonable fees of any attorney at law who may be employed to recover the amount of this note, or any part hereof, in principal and interest, or to protect the interest of Lender or to compromise or take any other action with required thereto, which fees are hereby fixed at twenty-five (25%) percent of the amount of the unpaid debt. Upon the occurrence of one or more of the following events of default (1) failure to make any monthly payments when due: (2)'
								.			'<br/>'
								.			'Failure to perform any obligation under any Security Agreement securing this note: (3) borrower defaults under any other credit extension with Lender; (4) Borrower should die, or become insolvent, or apply for bankruptcy or other relief from creditors; (5) Lender reasonably believes itself to be insecure in the repayment of this note. Lender may, at its option, declare the entire unpaid balance of this Note to be due immediately and payable without notice or demand. '
								.		'</p>'
								.		'<p>'
								.			'Borrower agrees that the origination fee, if any, included in the prepaid finance charge disclosed above, is fully earned and is not subject to rebate upon prepayment or acceleration of this note, and not considered interest. '
								.		'</p>'
								.		'<p>'
								.			'All parties hereto severally waive presentment for payment, demand, protest, and notice of protest and non-payment, and all pleas of division and discussion and agree that the payment of this Note may be extended by Lender from time to time, one or more times, without notice, hereby binding themselves jointly, severally, and solidarity, unconditionally waiving all pleas of discussion and division, and as original makers and promissory for the payment hereof in principal, interest, cost and attorney\'s fees. '
								.		'</p>'
								.		'<p>'
								.			'Lender may at any time release any of the parties hereto, in whole or in part, from their obligations hereunder without in any manner affecting or impairing the rights against all other parties hereto not so release. All parties hereto severally consent and agree that any and all collateral securing this note may be exchanged or surrendered or otherwise dealt with from time to time without notice to or from any party hereto and without in any manner releasing or altering the obligations of the parties hereto under this Note. No delay on the part of the Lender in exercising any power or right hereunder shall operate as a waiver of any such power of right nor shall any single or partial exercise of any power or right hereunder preclude other or future exercise thereof or the exercise of any other power of right hereunder. As used herein the term "parties hereto" shall be deemed to include not only the Borrower hereof but also any guarantor or guarantors.  All parties hereto further severally agree that this Note evidences and sets forth their agreement with the holder hereof and that no modifications hereof shall be binding unless in writing and signed by the parties hereto. '
								.		'</p>'
								.		'<p>'
								.			'I (we) further acknowledge receipt of a completed copy of this Truth-in-Lending Disclosure Statement Promissory Note and Security Agreement.'
								.			'<br/><br/>'
								.			'<a href="http://www.paydaycity.com/privacy2.html" target="_blank">'
								.				'Privacy Policy'
								.			'</a>'
								.			' | '
								.			'<a href="http://www.paydaycity.com/apr.html" target="_blank">'
								.				'APR Disclosure'
								.			'</a>'
								.		'</p>'
								.		']]>'
                        .  '</verbiage>'
								.'</section>';

		return $content_string;
	}
}

?>
