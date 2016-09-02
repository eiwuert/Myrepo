<?php
	/*

	*********** Class Documenation ************

	** Constructor:
	$myGPG = new GPG_Functions();

	** Required Config:
	$myGPG->Config ($user, $file2encrypt, $outputfile);
	By default encryption will use current users keyring
	External keyrings can be used, see optional parameters below

	$user: -- Required
	User in keyring to encrypt as, throws error if not set

	$file2encrypt: -- Required
	File to be encrypted, should/can include full path, throws error if not set

	$outputfile: -- Required
	Filename for encrypted file to be saved as, can include path, throws error if not set

	** Optional Parameters
	$myGPG->keypath = "/home/jeffb/.gnupg";
	Setting this variable will use the keyring(s) specififed in this directory
	
	Additionaly required variables can be set like so:
	$myPGP->user = "Username";
	$myPGP->inputfile = "Filename";
	$myPGP->outname = "Output Filename";

	** Encrypt:
	$myGPG->Encrypt();  returns boolean TRUE/FALSE

	*********** Sample Usage ************

	Encrypt Sample:
	----------------------------------------------------
	$user = "Jeff Brown";
	$file2encrypt = "/path/to/file.txt";
	$outputfile = "/path/to/save/output.txt";

	$myGPG = new GPG_Functions();
	$myGPG->keypath = "/home/jeffb/.gnupg";  		<------ optional
	$myGPG->Config ($user, $file2encrypt, $outputfile);
	$tmp = $myGPG->Encrypt();
	----------------------------------------------------

	*/
	class GPG_Functions
	{

	var $user;		// user key you want to use to encrypt
	var $keypath;		// path to key ring to be used
	var $path2file;		// path to file you want to encrypt
	var $inputfile;		// FILE TO BE ENCRYPTED
	var $outname;		// out name
	var $shellcommand;	// command passed to actually encrypt
	var $error;


		function GPG_Functions ()
		{
			return TRUE;
		}
		
		function Config ($user, $file, $outname)
		{
			$this->user = $user;
			$this->inputfile = $file;
			$this->outname = $outname;
			return TRUE;
		}

		function Encrypt ()
		{
			$this->shell_command = "gpg --yes ";

		// output path
			if ($this->outname != "")
			{
				$this->shell_command.="--output $this->outname ";
			}
			
		// check for external keyring
		// if no external keyring is used
		// keyring for current user will be used

			if ($this->keypath != "")
			{
				$this->shell_command.= "--homedir ".$this->keypath." ";
			}

		// this flasg tells gpg to encrypt

			$this->shell_command.="-e ";

		// this flag sets the user to be signed as
			if ($this->user != "")
			{
				$this->shell_command.="-r $this->user ";
			} else
			{
				$this->error = "No user has been specified\n";
				return $this-error;
			}

		// $this->inputfile cannot be blank
			if ($this->inputfile != "")
			{
				$this->shell_command.=$this->inputfile." ";
			} else
			{
				$this->error = "No input file has been specifed\n";
				return $this->error;
			}

		//
		return $this->callGPG();

		}

		function callGPG()
		{
			// echo "command = $this->shell_command";
			shell_exec ($this->shell_command);
			
			// check to see if file exists
			// if it does then was success return true
			if (file_exists ($this->outname))
			{
				return TRUE;
			} else
			{
				return FALSE;
			}
		}
	}
?>
