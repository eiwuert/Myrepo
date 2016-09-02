<?php

	/**************************************/
	// StatPro_Client
	//
	// MODIFIED BY: Andrew Minerd, 3/30/05
	//
	// Handle communication with
	// the Stat Pro 2 client.
	/**************************************/

	require_once ('statProClient.php');
	require_once ('enterpriseProClient.php');

	class StatPro_Client {

		// At one point, when we're no longer using
		// PHP 4, these should all be private
		var $exec_file;
		var $options;
		var $customer_key;
		var $customer_pass;
		var $track_key;
		var $global_key;
		var $space_key;
		var $use_enterprisepro;

		// Options are obsolete
		function StatPro_Client ($exec_file, $options = '', $customer_key, $customer_pass, $use_enterprisepro = TRUE)	{

			// default options
			if (!$options) $options = '-r';

			// set properties
			$this->exec_file = basename($exec_file);
			$this->options = $options;
			$this->customer_key = $customer_key;
			$this->customer_pass = $customer_pass;
			$this->use_enterprisepro = $use_enterprisepro;

			$this->cli = new statProClient ($this->exec_file);

			// for legacy compatibility, just in case
			// this will load values from $_SESSION['statpro2']

			// force regen of space_key on promo_override
			if(@$_SESSION["statpro"]["promo_override"])
			{
				$_SESSION["statpro"]["space_key"] = NULL;
				$_SESSION["statpro"]["promo_override"] = NULL;
			}

			$this->Track_Key();
			$this->Space_Key();
			$this->Global_Key();
			$this->Setup();
		}

		// Should be private:
		// Set stuff up
		function Setup() {
			if (!$this->space_key) {

				// get session data
				if (isset($_SESSION['stat_info']->cache))
				{
					$page_id = $_SESSION['stat_info']->cache->page_id;
					$promo_id = $_SESSION['stat_info']->cache->promo_id;
					$promo_sub_code = $_SESSION['stat_info']->cache->promo_sub_code;
				}
				else
				{
					$page_id = $_SESSION['config']->page_id;
					$promo_id = $_SESSION['config']->promo_id;
					$promo_sub_code = $_SESSION['config']->promo_sub_code;
				}

				// retreive our space key
				$_SESSION['statpro']['space_key'] = $this->space_key = $this->Get_Space_Key($page_id, $promo_id, $promo_sub_code);

			}

			if (!$this->track_key) {
				// create a new track
				$_SESSION['statpro']['track_key'] = $this->track_key = $this->Create_Track();
				if ($this->track_key)	$_SESSION['statpro']['global_key'] = $this->Set_Global($this->track_key, $this->global_key);

			}

		}

		// Get or set the track_key
		function Track_Key($track_key = '') {

			// for compatibility
			if (!$this->track_key && isset($_SESSION['statpro']['track_key']))
			{
				$this->track_key = $_SESSION['statpro']['track_key'];
			}

			if ($track_key) {
				$this->track_key = $track_key;
				$_SESSION['statpro']['track_key'] = $track_key;
			}

			return($this->track_key);

		}

		// Get or set the space_key
		function Space_Key($space_key = '') {
			// for compatibility
			if (!$this->space_key && isset($_SESSION['statpro']['space_key']))
			{
				$this->space_key = $_SESSION['statpro']['space_key'];
			}

			if ($space_key) {
				$this->space_key = $space_key;
				$_SESSION['statpro']['space_key'] = $space_key;
			}

			return($this->space_key);

		}

		// Get or set the global key
		function Global_Key($global_key = '') {

			// for compatibility
			if (!$this->global_key && isset($_SESSION['data']['global_key']))
			{
				$this->global_key = $_SESSION['data']['global_key'];
			}
			if (!$this->global_key && isset( $_SESSION['statpro']['global_key']))
			{
				$this->global_key = $_SESSION['statpro']['global_key'];
			}


			if ($global_key) {
				$this->global_key = $global_key;
				$_SESSION['statpro']['global_key'] = $global_key;
			}

			return($this->global_key);

		}

		function Create_Track($customer_key = '', $customer_pass = '') {

			if (!$customer_key) $customer_key = $this->customer_key;
			if (!$customer_pass) $customer_pass = $this->customer_pass;

			if ($customer_key && $customer_pass) {
				$out = $this->cli->createTrack ($this->customer_key, $this->customer_pass);
				$key = $out['key'];

				if ($key)
				{
					$this->Track_Key($key);
					return($this->track_key);
				}

				mail('andrew.minerd@thesellingsource.com', 'Create_Track', print_r($out, TRUE)."\n{$key}\n");
			}

			return(FALSE);

		}

		function Record_Event($event_type_key, $date_occured = '') {

			$out = '';

			if ($this->track_key && $this->space_key) {

				$out = $this->cli->recordEvent ($this->customer_key, $this->customer_pass,
					$this->track_key, $this->space_key, $event_type_key, $date_occured);

			}

			return ($out) ? TRUE : FALSE;

		}

		function Url_Event ($url)
		{
			$this->cli->urlEvent($this->customer_key, $this->customer_pass, $url);
		}

		function PW_Event ($pwadvid, $event)
		{
			$this->cli->pwEvent($this->customer_key, $this->customer_pass, $pwadvid, $event);
		}

		function PW_Fund ($pwadvid)
		{
			$this->cli->pwFund($this->customer_key, $this->customer_pass, $pwadvid);
		}

		function Set_Track_Space($track_space_A, $track_space_B = '', $track_space_C = '') {

			if ($this->track_key) {

				$out = $this->cli->setTrackSpace ($this->customer_key, $this->customer_pass,
					$this->track_key, $track_space_A, $track_space_B, $track_space_C);
			}

			return ($out) ? TRUE : FALSE;

		}

		function Set_Global($track_key = '', $global_key = '') {

			if (!$track_key) $track_key = $this->track_key;
			if (!$global_key) $global_key = $this->global_key;

			if ($track_key) {

				$out = $this->cli->setGlobal ($this->customer_key, $this->customer_pass,
					$track_key, $global_key);
				$key = $out['key'];

				if ($key)
				{
					$this->Global_Key($key);
					return($this->global_key);
				}

			}

			return(FALSE);

		}

		function Get_Key($out) {

			foreach ($out as $line)
			{
				if (preg_match('/Key:\w*(.{27}|.{40})/', $line, $matches))
				{
					$key = trim($matches[1]);
					break;
				}
			}

			if (!$key)
			{
				$key = FALSE;
			}
			return($key);

		}

		function Get_Space_Key($page_id, $promo_id, $promo_sub_code) {

			$def = array(
				'page_id' => $page_id,
				'promo_id' => $promo_id,
				'promo_sub_code' => $promo_sub_code
			);

			// newer customers do not use enterprise pro at all
			if ($this->use_enterprisepro)
			{
				$epc = new enterpriseProClient ($this->exec_file);
				$result = $epc->getSpaceKey($this->customer_key, $this->customer_pass, $def);
			}
			else
			{
				$result = $this->cli->getSpaceKey($this->customer_key, $this->customer_pass, $def);
			}

			// in case an error object is returned
			if (!is_object($result)) {

				$this->Space_Key($result);

			} else $result = FALSE;

			return($result);

		}

	}

?>
