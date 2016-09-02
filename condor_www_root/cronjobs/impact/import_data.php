<?php

/**
	
	@name
	@version 2.0
	@author Chris Barmonde
	
	@desc
		
		Daily cron script to import the data for Impact
		Cash Systems into the database
		
	@todo
	
*/

define ('CRON_ROOT', '/virtualhosts/cronjobs/impact');
define ('DATA', CRON_ROOT.'/data');

require_once('prpc/client.php');
require_once('mysql.4.php');
require_once('sftp.1.php');
require_once('/virtualhosts/bfw.1.edataserver.com/include/code/server.php');



// Let's make sure we can write to the data directory
if(!is_writable(DATA))
{
	die('Unable to write to the data directory: ' . DATA . "\n");
}








	class Impact_Import
	{
		private $sftp;
		private $import_dbs;
		
		private $filename;
		private $data;
		private $fields;
		private $statuses;
		
		private $error;
		private $data_errors;
		
		public function __construct($filename, $mode)
		{
			$this->sftp = null;
			
			$this->filename = $filename;
			$this->data = '';
			
			$this->error = '';
			$this->data_errors = array();
			
			//These are the databases that have sync_cashline_ic to which the
			//data will be imported.
			$this->import_dbs = array(
				Server::Get_Server($mode, 'BLACKBOX', 'ic'),
				Server::Get_Server($mode, 'MYSQL', 'ic')
			);
			
			// cashline_customer_list fields
			$this->fields = array(
				'social_security_number',
				'status',
				'last_payoff_date',
				'date_customer_added',
				'current_service_charge_amount',
				'current_due_date',
				'email_address',
				'routing_number',
				'account_number',
				'home_phone',
				'drivers_license_number'
			);
			
			$this->statuses = array(
				'ACTIVE',
				'BANKRUPTCY',
				'COLLECTION',
				'DENIED',
				'INACTIVE',
				'WITHDRAWN',
				'PENDING'
			);
		}
		
		//Connect to and download the file from the SFTP server.
		public function Download()
		{
			if(empty($this->error))
			{
				//First check if we have the file already, and just use that instead
				//of downloading a new one if it's there.
				$saved_file = DATA . '/' .$this->filename;
				if(file_exists($saved_file))
				{
					$this->data = file_get_contents($saved_file);
				}
				else
				{
					$this->sftp = new SFTP_1('sftp.impactpayments.com', 'sellsource', 'lW3_5$zyl', 22);
					$this->sftp->connect();
		
					if($this->sftp->error_state)
					{
						$this->error = 'Error Connecting: ' . $this->sftp->get_error_msg();
					}
					else
					{
						$this->data = $this->sftp->get($this->filename);
						
						if($this->sftp->error_state)
						{
							$this->error = 'Get Error: ' . $this->sftp->get_error_msg();
						}
						else
						{
							file_put_contents($saved_file, $this->data);
						}
					}
				}
			}
		}
		
		//Import the data into the databases
		public function Import()
		{
			if(empty($this->error))
			{
				$import_data = $this->Get_Import_Data();
	
				//Import it into each DB
				foreach($this->import_dbs as $db_info)
				{
					try
					{
						$host =  $db_info['host'] . ((isset($db_info['port'])) ? ':' . $db_info['port'] : '');
						$sql = new MySQL_4($host, $db_info['user'], $db_info['password']);
						$link = $sql->Connect();
						
						if(!$link)
						{
							$this->error = 'Unable to connect to database ' . $db_info['host'] . ': ' . $sql->Get_Error();
						}
						else
						{
							foreach($import_data as $row)
							{ 
								// Added sync_cashline_ic statically, because the db_info doesn't really work anymore
								// because of Andrew's change. Blame him, not me. [BF]
								$sql->Query('sync_cashline_ic', 'REPLACE INTO cashline_customer_list SET ' . $row );
							}
						}
						
						$sql->Close_Connection();
					}
					catch(Exception $e)
					{
						$this->error = 'Database error: ' . $e->getMessage();
					}
				}
			}
		}
		
		//Here we'll get the data from the file and format it appropriately and check for any errors
		private function Get_Import_Data()
		{
			$lines = explode("\n", trim($this->data));

			$import_data = array();
			foreach($lines as $line_number => $line)
			{
				$line_data = explode(',', $line);
				$line_data = array_map('trim', $line_data);
				
				$data = $this->Map_Columns($line_data, $line_number);
				if($data !== false)
				{
					$import_data[] = $data;
				}
			}

			return $import_data;
		}

		//This is used to map columns from the file to the columns in the database
		private function Map_Columns($line_data, $line_number)
		{
			$final_data = array();
			
			foreach($this->fields as $key => $field)
			{
				$value = trim($line_data[$key], '"');
				
				if(!$this->Validate_Field($field, $value))
				{
					$this->data_errors[] = sprintf('%04d', $line_number) . ": $field failed with value $value\n";
					return false;
				}

				$final_data[] = "{$field} = '{$value}'";
			}
			
			return implode(',', $final_data);
		}
		
		//This will check to make sure the value we've been handed for the column is sane
		private function Validate_Field($field, $value)
		{
			$valid = true;

			switch ($field)
			{
				case 'social_security_number':
					$valid = (preg_match('/^\d{9}$/', $value));
					break;
				case 'home_phone':
					$valid = (preg_match('/^\d{10}$/', $value));
					break;
				case 'status':
					$valid = (in_array(strtoupper($value), $this->statuses));
					break;
				case 'last_payoff_date':
				case 'date_customer_added':
				case 'current_due_date':
					list ($year, $month, $day) = explode('-', $value);
					$valid = (empty($value) || $value == '0000-00-00' || checkdate($month, $day, $year));
					break;
				case 'current_service_charge_amount':
					$valid = (preg_match("/^\d+\.\d{2}$/", $value));
					break;
				case 'email_address':
					$valid = (preg_match('/^[a-zA-Z0-9_-]+(?:\.[a-zA-Z0-9_-]+)*@(?:[a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}$/', $value));
					break;
			}

			return $valid;
		}
		
		
		public function Get_Error()
		{
			return $this->error;
		}

		
		public function Log_Error()
		{
			$error = "\n\nUnexpected Error: {$this->error}\n\n";
			file_put_contents('import_errors.log', $error, FILE_APPEND);
		}
		
		public function Has_Data_Errors()
		{
			return !empty($this->data_errors);
		}
		
		public function Log_Data_Errors()
		{
			$data_errors = implode('', $this->data_errors);
			$data_errors = 'Errors for date: ' . date('Y-m-d') . "\n\n{$data_errors}\n\n\n";

			file_put_contents('import_errors.log', $data_errors, FILE_APPEND);
		}
	}


	if($argc > 1 && in_array(strtolower($argv[1]), array('local', 'rc', 'live')))
	{
		$filename = ($argc > 2) ? $argv[2] : 'ic' . date('mdy') . '.csv';
		$mode = $argv[1];


		$import = new Impact_Import($filename, $mode);


		echo "Downloading file...\n";
		$import->Download();

		echo "Importing Data...\n";
		$import->Import();

		if($import->Has_Data_Errors())
		{
			echo "Logging data errors...\n";
			$import->Log_Data_Errors();
		}

		$error = $import->Get_Error();
		if(!empty($error))
		{
			echo "ERROR!\n{$error}";
			$import->Log_Error();
		}

		echo "Done\n";
	}
	else
	{
		die("usage: {$argv[1]} local|rc|live\n");
	}


?>