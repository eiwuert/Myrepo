<?php
	
	require_once('hylafax_callback.php');
	require_once('hylafax_routing.php');
	require_once('hylafax_db.php');
	require_once('hylafax_job.php');
	require_once('hylafax_jobcontrol.php');

	
	/**
	 *
	 * An external API for submitting jobs to a HylaFax server.
	 *
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 *
	 */
	class HylaFax_API
	{
		
		const FAX_BIN = '/usr/bin/sendfax';
		const FAX_HOST = 'localhost';
		
		const TEMP_DIR = '/tmp';
		const TEMP_FILE_PREFIX = 'hylafax_';
		
		protected $mode;
		protected $security_object;
				
		function __construct($mode, $security_object)
		{
			$this->mode = $mode;
			$this->security_object = $security_object;
			HylaFax_DB::Get_DB($this->mode);
		}
		
		/**
		 * Submit a job to be faxed. This will return the job ID on success or
		 * boolean FALSE on failure.
		 *
		 * @param string $recipient
		 * @param string $content_type
		 * @param string $content
		 * @param string $callback
		 * @param string $cover_sheet
		 * @return int
		 */
		public function Submit_Job($from, $recipient, $content_type, $content, $callback = NULL, $cover_sheet = NULL)
		{
			
			$job_id = FALSE;
			$temp_file_list = array();
			
			if (is_array($content))
			{
				$temp_file_list = array_map(array('self', 'Temp_File'), $content);
			}
			else
			{
				$temp_file_list = array(self::Temp_File($content));
			}
			
			// Why even bother running this if we don't have any content?
			if (!empty($temp_file_list))
			{
				//This is pointless since we're going to use
				//dynamic header information.
				
				/**
				 * We No longer need to specify the modem
				 * since we use JobControl to change the settings
				 * per customer regardless of modem
				 */
				
				$cmd = trim(`which sendfax`);
				
				$owner = $this->security_object->Get_Agent_Login().'['.$this->security_object->Get_Company_Id().']';
				//Really if we don't have an owner, we're in trouble
				//since we were able to login, but just in case.
				if(!is_string($owner)) $owner = $from;
				
				$cmd .= sprintf(' -o "%s"',str_replace(' ','',$owner));
				if ($callback !== NULL)
				{
					
					$id = HylaFax_Callback::Request();
					
					// we want status notifications: -f specifies an email address to send notifications
					// to, and -R tries to send a notification anytime the job status changes
					$cmd .= ' -f '.escapeshellarg($id.'@callback').' -R ';
					
				}
				else
				{
					// no status notifications
					$cmd .= ' -N ';
				}
				
				// Check for a cover page and make sure there's something in it
				if(!is_null($cover_sheet) && strlen($cover_sheet) > 0)
				{
					// We have a cover sheet to use
					$cover_file_name = $this->Temp_File($cover_sheet);
					$cmd .= " -C $cover_file_name -d ".escapeshellarg($recipient);
				}
				else
				{
					// -n avoids a cover page, -d specifies the destination
					$cmd .= ' -n -d '.escapeshellarg($recipient);
				}
				
				// for simplicity we always have an array, even if there is only one file
				$cmd .= ' '.implode(' ', array_map('escapeshellarg', $temp_file_list));
				
				// the reply from this command is in the format of:
				// request id is [n] (group id [n]) for host [s] ([n] file)
				$out = `$cmd`;
				
				if (preg_match('/^request id is (\d+) \(group id (\d+)\) for host (\w+) \((\d+) files?\)$/', $out, $matches))
				{
					
					$job_id = (int)$matches[1];
					
					if ($callback !== NULL)
					{
						// actually register the callback
						HylaFax_Callback::Register($id, $callback, $job_id);
						$cdata = $this->security_object->getCompanyData();;
						$job_control = new HylaFax_JobControl($this->mode);
						$job_control->registerInfo($job_id, $owner, $cdata);
						
					}
					
				}
				else
				{
					throw new Exception('Unrecognized response while submitting job to HylaFAX. Got: '.$out);
				}
				
				// remove the temp files
				array_map('unlink', $temp_file_list);
				if(isset($cover_file_name)) unlink($cover_file_name);
				
			}
			else
			{
				throw new Exception('Error writing temporary files.');
			}
			
			return $job_id;
			
		}
		
		/**
		 * Returns a HylaFax_Job object. On failure, returns boolean false.
		 *
		 * @param int $job_id
		 * @return HylaFax_Job
		 */
		public function Query_Status($job_id)
		{
			
			$job = FALSE;
			
			if (($queue_file = HylaFax_Job::Find($job_id)) !== FALSE)
			{
				$job = new HylaFax_Job($queue_file);
			}
			
			return $job;
			
		}
		
		/**
		 * Saves a $data to a temporary file, and returns the file name.
		 *
		 * @param string $data
		 * @return string
		 */
		protected static function Temp_File($data)
		{
			
			// get a temporary file name
			$temp_file = tempnam(self::TEMP_DIR, self::TEMP_FILE_PREFIX);
			
			// write the data
			$written = file_put_contents($temp_file, $data);
			
			return $temp_file;
			
		}
		
		public function Test()
		{
			return $this->security_object->Get_Company_Name();
		}
		
	}
?>
