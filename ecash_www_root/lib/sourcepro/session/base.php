<?php
	// A class to handle sessions

	class SourcePro_Session extends SourcePro_Entity_Storage
	{
		/**
			Initializes an instance of this class.

			@param store		The storage object used to save or load this object.
			@param schema	The optional schema that this object is stored in.  If null the default schema for the store will be used.
			@param data		The optional array containing initial field values.
		*/
		public function __construct ($store, $schema, $data = NULL)
		{
			parent::__construct ($store, $schema);

			$this->Set_Table ('session');

			$this->Add_Field_Time_Modified ('date_modified', NULL, $data);
			$this->Add_Field_Time_Created ('date_created', NULL, $data);

			$this->Add_Field_Id ('session_id', NULL, $data);
			$this->Add_Field_Key ('session_key', NULL, $data);

			$this->Add_Field_String ('session_data', NULL, $data);
			
			// The parameters for the cookie
			$this->Add_Asset_String ('param_domain', $data);
			$this->Add_Asset_Number ('param_lifetime', $data);
			$this->Add_Asset_String ('param_path', $data);
			$this->Add_Asset_Number ('param_secure', $data);
		}

		public function Start ()
		{
			// Make sure lifetime is not a null value
			if (!strlen ($this->param_lifetime))
				$this->param_lifetime = 0;
				
			session_set_cookie_params ($this->param_lifetime, $this->param_path, $this->param_domain, $this->param_secure);
			
			if (! is_null ($this->session_key))
			{
				session_id ($this->session_key);
			}

			session_set_save_handler
			(
				array (&$this, "Open"),
				array (&$this, "Close"),
				array (&$this, "Read"),
				array (&$this, "Write"),
				array (&$this, "Destroy"),
				array (&$this, "Garbage_Collection")
			);

			session_start();
		}

		public function Open ($save_path, $session_name)
		{
			return TRUE;
		}

		public function Close ()
		{
			return TRUE;
		}

		public function Read ($session_key)
		{
			$this->session_key = $session_key;

			$c = substr ($this->session_key, 0, 1);
			if (preg_match ('/[0-9a-zA-Z]/', $c))
			{
				$this->Set_Table ('session_'.$c);
			}

			$this->Load();

			if (strlen ($this->session_data))
			{
				return @gzuncompress($this->session_data);
			}
			else
			{
				return '';
			}
		}

		public function Write ($session_key, $session_data)
		{
			if (! $this->session_key)
			{
				$this->session_key = $session_key;
			}

			$this->session_data = @gzcompress($session_data);

			$this->Save();

			return TRUE;
		}

		public function Destroy ($session_key)
		{
			$this->Delete();

			return TRUE;
		}

		public function Garbage_Collection ($max_session_age)
		{
			return TRUE;
		}
	}
?>
